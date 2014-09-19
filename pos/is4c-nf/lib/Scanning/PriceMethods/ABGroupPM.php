<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

/** 
   @class ABGroupPM
   
   This module provides a group price where
   the customer must buy something from
   item group A *and* item group B to receive
   the associated discount (ex: buy salsa,
   save $0.50 on chips)

   This is pricemethod 4 in earlier WFC releases
   and may not exist anywhere else.
*/

class ABGroupPM extends PriceMethod 
{

    function addItem($row,$quantity,$priceObj)
    {
        global $CORE_LOCAL;
        if ($quantity == 0) {
            return false;
        }

        $pricing = $priceObj->priceInfo($row,$quantity);

        // enforce limit on discounting sale items
        $dsi = $CORE_LOCAL->get('DiscountableSaleItems');
        if ($dsi == 0 && $dsi !== '' && $priceObj->isSale()) {
            $row['discount'] = 0;
        }

        $mixMatch = $row['mixmatchcode'];
        /* group definition: number of items
           that make up a group, price for a
           full set. Use "special" rows if the
           item is on sale */
        $groupQty = $row['quantity'];
        $groupPrice = $row['groupprice'];
        if ($priceObj->isSale()) {
            $groupQty = $row['specialquantity'];
            $groupPrice = $row['specialgroupprice'];    
        }

        /* not straight-up interchangable
         * ex: buy item A, get $1 off item B
         * need strict pairs AB 
         *
         * type 3 tries to split the discount amount
         * across A & B's departments; type 4
         * does not 
         */
        $qualMM = abs($mixMatch);
        $discMM = -1*abs($mixMatch);

        $dbt = Database::tDataConnect();

        // lookup existing qualifiers (i.e., item As)
        // by-weight items are rounded down here
        $q1 = "SELECT floor(sum(ItemQtty)),max(department) 
            FROM localtemptrans WHERE mixMatch='$qualMM' 
            and trans_status <> 'R'";
        $r1 = $dbt->query($q1);
        $quals = 0;
        $dept1 = 0;
        if($dbt->num_rows($r1)>0){
            $rowq = $dbt->fetch_row($r1);
            $quals = round($rowq[0]);
            $dept1 = $rowq[1];    
        }

        // lookup existing discounters (i.e., item Bs)
        // by-weight items are counted per-line here
        //
        // extra checks to make sure the maximum
        // discount on scale items is "free"
        $q2 = "SELECT sum(CASE WHEN scale=0 THEN ItemQtty ELSE 1 END),
            max(department),max(scale),max(total),max(quantity) FROM localtemptrans 
            WHERE mixMatch='$discMM' 
            and trans_status <> 'R'";
        $r2 = $dbt->query($q2);
        $dept2 = 0;
        $discs = 0;
        $discountIsScale = false;
        $discountScaleQty = 0;
        $scaleDiscMax = 0;
        if($dbt->num_rows($r2)>0){
            $rowd = $dbt->fetch_row($r2);
            $discs = round($rowd[0]);
            $dept2 = $rowd[1];
            if ($rowd[2]==1) $discountIsScale = true;
            $scaleDiscMax = $rowd[3];
            $discountScaleQty = $rowd[4];
        }
        if ($quantity != (int)$quantity && $mixMatch < 0){
            $discountIsScale = true;
            $scaleDiscMax = $quantity * $pricing['regPrice'];
            $discountScaleQty = $quantity;
        }

        // items that have already been used in an AB set
        $q3 = "SELECT sum(matched) FROM localtemptrans WHERE
            mixmatch IN ('$qualMM','$discMM')";
        $r3 = $dbt->query($q3);
        $matches = ($dbt->num_rows($r3)>0)?array_pop($dbt->fetch_array($r3)):0;

        // reduce totals by existing matches
        // implicit: quantity required for B = 1
        // i.e., buy X item A save on 1 item B
        $matches = $matches/$groupQty;
        $quals -= $matches*($groupQty-1);
        $discs -= $matches;

        // where does the currently scanned item go?
        if ($mixMatch > 0){
            $quals = ($quals >0)?$quals+floor($quantity):floor($quantity);
            $dept1 = $row['department'];
        }
        else {
            // again, scaled items count once per line
            if ($quantity != (int)$quantity)
                $discs = ($discs >0)?$discs+1:1;
            else
                $discs = ($discs >0)?$discs+$quantity:$quantity;
            $dept2 = $row['department'];
        }

        // count up complete sets
        $sets = 0;
        while($discs > 0 && $quals >= ($groupQty-1) ){
            $discs -= 1;
            $quals -= ($groupQty -1);
            $sets++;
        }

        if ($sets > 0){
            $maxDiscount = $sets*$groupPrice;
            if ($discountIsScale && $discountScaleQty != 0)
                $maxDiscount = $sets*$discountScaleQty*$groupPrice;
            if ($scaleDiscMax != 0 && $maxDiscount > $scaleDiscMax)
                $maxDiscount = $scaleDiscMax;

            // if the current item is by-weight, quantity
            // decrement has to be corrected, but matches
            // should still be an integer
            $ttlMatches = $sets;
            if($quantity != (int)$quantity) $sets = $quantity;
            $quantity = $quantity - $sets;

            TransRecord::addRecord(array(
                'upc' => $row['upc'],
                'description' => $row['description'],
                'trans_type' => 'I',
                'department' => $row['department'],
                'quantity' => $sets,
                'unitPrice' => $pricing['regPrice'],
                'total' => MiscLib::truncate2($sets*$pricing['regPrice']),
                'regPrice' => $pricing['regPrice'],
                'scale' => $row['scale'],
                'tax' => $row['tax'],
                'foodstamp' => $row['foodstamp'],
                'discount' => ($priceObj->isMemberSale() || $priceObj->isStaffSale()) ? 0 : MiscLib::truncate2($maxDiscount),
                'memDiscount' => ($priceObj->isMemberSale() || $priceObj->isStaffSale()) ? MiscLib::truncate2($maxDiscount) : 0,
                'discountable' => $row['discount'],
                'discounttype' => $row['discounttype'],
                'ItemQtty' => $sets,
                'volDiscType' => ($priceObj->isSale() ? $row['specialpricemethod'] : $row['pricemethod']),
                'volume' => ($priceObj->isSale() ? $row['specialquantity'] : $row['quantity']),
                'VolSpecial' => ($priceObj->isSale() ? $row['specialgroupprice'] : $row['groupprice']),
                'mixMatch' => $row['mixmatchcode'],
                'matched' => $ttlMatches * $groupQty,
                'cost' => (isset($row['cost']) ? $row['cost']*$new_sets*$groupQty : 0.00),
                'numflag' => (isset($row['numflag']) ? $row['numflag'] : 0),
                'charflag' => (isset($row['charflag']) ? $row['charflag'] : '')
            ));

            if (!$priceObj->isMemberSale() && !$priceObj->isStaffSale()){
                TransRecord::additemdiscount($dept2,MiscLib::truncate2($maxDiscount));
            }
        }

        /* any remaining quantity added without
           grouping discount */
        if ($quantity > 0){
            TransRecord::addRecord(array(
                'upc' => $row['upc'],
                'description' => $row['description'],
                'trans_type' => 'I',
                'department' => $row['department'],
                'quantity' => $quantity,
                'unitPrice' => $pricing['regPrice'],
                'total' => MiscLib::truncate2($pricing['regPrice'] * $quantity),
                'regPrice' => $pricing['regPrice'],
                'scale' => $row['scale'],
                'tax' => $row['tax'],
                'foodstamp' => $row['foodstamp'],
                'discountable' => $row['discount'],
                'discounttype' => $row['discounttype'],
                'ItemQtty' => $quantity,
                'volDiscType' => ($priceObj->isSale() ? $row['specialpricemethod'] : $row['pricemethod']),
                'volume' => ($priceObj->isSale() ? $row['specialquantity'] : $row['quantity']),
                'VolSpecial' => ($priceObj->isSale() ? $row['specialgroupprice'] : $row['groupprice']),
                'mixMatch' => $row['mixmatchcode'],
                'cost' => (isset($row['cost'])?$row['cost']*$quantity:0.00),
                'numflag' => (isset($row['numflag'])?$row['numflag']:0),
                'charflag' => (isset($row['charflag'])?$row['charflag']:'')
            ));
        }

        return true;
    }
}

