<?php
/*******************************************************************************

    Copyright 2022 Whole Foods Co-op

    This file is part of IT CORE.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

namespace COREPOS\pos\lib\Scanning\PriceMethods;
use COREPOS\pos\lib\Scanning\PriceMethod;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\TransRecord;
use COREPOS\pos\lib\LaneLogger;

/** 
   @class BogoPM
   
   This price method strictly handles BOGOs
   (Buy one, get one free)
*/

class BogoPM extends PriceMethod {

    public function addItem(array $row, $quantity, $priceObj)
    {
        if ($quantity == 0) return false;

        $pricing = $priceObj->priceInfo($row,$quantity);

        // enforce limit on discounting sale items
        $dsi = $this->session->get('DiscountableSaleItems');
        if ($dsi == 0 && $dsi !== '' && $priceObj->isSale()) {
            $row['discount'] = 0;
        }

        $limited = false;
        $maxPairs = false;
        $alreadyRung = 0;
        $log = new LaneLogger();
        if ($row['special_limit'] > 0) {
            $limited = true;
            $dbc = Database::tDataConnect();
            $appliedQ = "
                SELECT SUM(quantity) AS saleQty
                FROM " . $this->session->get('tDatabase') . $dbc->sep() . "localtemptrans
                WHERE discounttype <> 0
                    AND (
                        upc='{$row['upc']}'
                        OR (mixMatch='{$row['mixmatchcode']}' AND mixMatch<>''
                            AND mixMatch<>'0' AND mixMatch IS NOT NULL
                            AND upc <> 'ITEMDISCOUNT')
                    )";
            $appliedR = $dbc->query($appliedQ);
            if ($appliedR && $dbc->num_rows($appliedR)) {
                $appliedW = $dbc->fetch_row($appliedR);
                $alreadyRung = $appliedW['saleQty'];
                $maxPairs = floor($row['special_limit'] / 2);
            }
        }

        /**
         * Unroll any quantity specified and create one record
         * per item unit
         */
        for ($i = 0; $i < $quantity; $i++) {
            TransRecord::addRecord(array(
                'upc' => $row['upc'],
                'description' => $row['description'],
                'trans_type' => 'I',
                'trans_subtype' => (isset($row['trans_subtype'])) ? $row['trans_subtype'] : '',
                'department' => $row['department'],
                'quantity' => 1,
                'unitPrice' => $pricing['unitPrice'],
                'total' => MiscLib::truncate2($pricing['unitPrice']),
                'regPrice' => $pricing['regPrice'],
                'scale' => $row['scale'],
                'tax' => $row['tax'],
                'foodstamp' => $row['foodstamp'],
                'discountable' => $row['discount'],
                'discounttype' => $row['discounttype'],
                'ItemQtty' => 1,
                'volDiscType' => ($priceObj->isSale() ? $row['specialpricemethod'] : $row['pricemethod']),
                'volume' => ($priceObj->isSale() ? $row['specialquantity'] : $row['quantity']),
                'VolSpecial' => ($priceObj->isSale() ? $row['specialgroupprice'] : $row['groupprice']),
                'mixMatch' => $row['mixmatchcode'],
                'cost' => (isset($row['cost'])?$row['cost']*$quantity:0.00),
                'numflag' => (isset($row['numflag'])?$row['numflag']:0),
                'charflag' => (isset($row['charflag'])?$row['charflag']:'')
            ));
            if ($limited) {
                $alreadyRung++;
                if ($alreadyRung >= $row['special_limit']) {
                    $row['discounttype'] = 0;
                    $row['special_price'] = 0;
                    $row['specialpricemethod'] = 0;
                    $row['specialquantity'] = 0;
                    $row['specialgroupprice'] = 0;
                }
            }
        }

        /**
         * Find all items in the set ordered by price
         * Divide by two to get pairs (because every record is one item)
         * Calculate total discount for cheapest paired items
         * Also gather dept/tax/fs info for discount
         */
        $mixMatch  = $row["mixmatchcode"];
        $queryt = "select trans_id, unitPrice, regPrice, department, tax, foodstamp
                from localtemptrans 
                where trans_status <> 'R' AND upc <> 'ITEMDISCOUNT' AND
                mixMatch = '".$mixMatch."' order by unitPrice, trans_id DESC";
        if (!$mixMatch || $mixMatch == '0') {
            $mixMatch = 0;
            $queryt = "select trans_id, unitPrice, regPrice, department, tax, foodstamp
                from "
                ."localtemptrans where trans_status<>'R' AND upc <> 'ITEMDISCOUNT' AND "
                ."upc = '".$row['upc']."' order by unitPrice, trans_id DESC";
        }
        $dbc = Database::tDataConnect();
        $queryR = $dbc->query($queryt);
        $pairs = floor($dbc->numRows($queryR) / 2);
        $count = 0;
        $totalDiscount = 0;
        $dept = 0;
        $tax = 0;
        $fs = 0;
        if ($maxPairs !== false && $maxPairs < $pairs) {
            $pairs = $maxPairs;
        }
        while ($pairW = $dbc->fetchRow($queryR)) {
            if ($count < $pairs) {
                $totalDiscount += $pairW['unitPrice'];
                $dept = $pairW['department'];
                $tax = $pairW['tax'];
                $fs = $pairW['foodstamp'];
            } else {
                break;
            }
            $count++;
        }

        /**
         * Examine BOGO discounts already applied, if any
         * Add additional discount as needed
         * While this will typically be used with a mixMatch value,
         * if it isn't the item UPC will be put in the mixMatch
         * field so corresponding discount records can be located
         * on subsequent rings of the same item
         */
        $discountQ = "select SUM(-total) AS ttl
                from localtemptrans 
                where upc='ITEMDISCOUNT'
                AND mixMatch = '".$mixMatch."'";
        if ($mixMatch === 0) {
            $discountQ = "select SUM(-total) AS ttl "
                ."from localtemptrans where upc='ITEMDISCOUNT' AND "
                ."mixMatch = '".$row['upc']."' order by unitPrice, trans_id DESC";
        }
        $discountR = $dbc->query($discountQ);
        $discountW = $dbc->fetchRow($discountR);
        if ($discountW) {
            $totalDiscount -= $discountW['ttl'];
        }
        if (abs($totalDiscount > 0.005)) {
            TransRecord::addBogoDiscount(
                $dept,
                $totalDiscount,
                $tax,
                $fs,
                ($mixMatch === 0 ? $row['upc'] : $mixMatch)
            );
        }

        return true;
    }
}

