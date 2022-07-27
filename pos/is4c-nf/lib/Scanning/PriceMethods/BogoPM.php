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
        }

        /**
         * Find all items in the set ordered by price
         * Divide by two to get pairs (because every record is one item)
         * Change first ${number of pairs} to $0.00
         * Change remainder to regular price
         */
        $mixMatch  = $row["mixmatchcode"];
        $queryt = "select trans_id, unitPrice, regPrice
                from localtemptrans 
                where trans_status <> 'R' AND 
                mixMatch = '".$mixMatch."' order by unitPrice";
        if (!$mixMatch || $mixMatch == '0') {
            $mixMatch = 0;
            $queryt = "select trans_id, unitPrice, regPrice from "
                ."localtemptrans where trans_status<>'R' AND "
                ."upc = '".$row['upc']."' order by unitPrice";
        }
        $dbc = Database::tDataConnect();
        $queryR = $dbc->query($queryt);
        $pairs = floor($dbc->numRows($queryR) / 2);
        $count = 0;
        $prep = $dbc->prepare("UPDATE localtemptrans SET total=?, discount=? WHERE trans_id=?");
        while ($row = $dbc->fetchRow($queryR)) {
            if ($count < $pairs) {
                $dbc->execute($prep, array(0, $row['unitPrice'], $row['trans_id']));
            } else {
                $dbc->execute($prep, array($row['unitPrice'], 0, $row['trans_id']));
            }
            $count++;
        }

        return True;
    }
}

