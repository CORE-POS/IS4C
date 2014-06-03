<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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
   @class MoreThanQttyPM
   
   This method provides a discount
   on all items in the group beyond
   the required quantity. Discount is
   reflected as a percentage in groupprice.
   Groups are defined via mixmatch.
*/

class MoreThanQttyPM extends PriceMethod {

    function addItem($row,$quantity,$priceObj){
        global $CORE_LOCAL;
        if ($quantity == 0) return false;

        // enforce limit on discounting sale items
        $dsi = $CORE_LOCAL->get('DiscountableSaleItems');
        if ($dsi == 0 && $dsi !== '' && $priceObj->isSale()) {
            $row['discount'] = 0;
        }

        $pricing = $priceObj->priceInfo($row,$quantity);

        /* group definition: number of items
           that make up a group, price for a
           full set. Use "special" rows if the
           item is on sale */
        $groupQty = $row['quantity'];
        $groupPrice = $row['groupprice'];
        if ($priceObj->isSale()){
            $groupQty = $row['specialquantity'];
            $groupPrice = $row['specialgroupprice'];    
        }

        /* count items in the transaction
           from the given group 

           Also note the total of items already
           rung in that did not receive a discount
        */
        $mixMatch  = $row["mixmatchcode"];
        $queryt = "select sum(ItemQtty) as mmqtty, 
            sum(CASE WHEN discount=0 THEN total ELSE 0 END) as unDiscountedTotal,
            mixMatch from localtemptrans 
            where trans_status <> 'R' AND 
            mixMatch = '".$mixMatch."' group by mixMatch";
        if (!$mixMatch || $mixMatch == '0') {
            $mixMatch = 0;
            $queryt = "select sum(ItemQtty) as mmqtty, 
                sum(CASE WHEN discount=0 THEN total ELSE 0 END) as unDiscountedTotal,
                from "
                ."localtemptrans where trans_status<>'R' AND "
                ."upc = '".$row['upc']."' group by upc";
        }
        $dbt = Database::tDataConnect();
        $resultt = $dbt->query($queryt);
        $num_rowst = $dbt->num_rows($resultt);

        $trans_qty = 0;
        $undisc_ttl = 0;
        if ($num_rowst > 0){
            $rowt = $dbt->fetch_array($resultt);
            $trans_qty = floor($rowt['mmqtty']);
            $undisc_ttl = $rowt['unDiscountedTotal'];
        }
        /* include the items in this ring */
        $trans_qty += $quantity;

        /* if purchases exceed then requirement, apply
           the discount */
        if ($trans_qty >= $groupQty){
            $discountAmt = $pricing['unitPrice'] * $groupPrice;

            if ( ($trans_qty - $quantity) < $groupQty){
                /* this ring puts us over the threshold.
                   extra math to account for discount on
                   previously rung items */
                $totalDiscount = ($undisc_ttl * $groupPrice) + ($discountAmt * $quantity);
                $actualTotal = ($pricing['unitPrice']*$quantity) - $totalDiscount;
                $pricing['discount'] = $totalDiscount;
                $pricing['unitPrice'] = $actualTotal / $quantity;
            }
            else {
                $pricing['discount'] = $discountAmt * $quantity;
                $pricing['unitPrice'] -= $discountAmt;
            }
        }
    
        /* add the item */
        TransRecord::addRecord(array(
            'upc' => $row['upc'],
            'description' => $row['description'],
            'trans_type' => 'I',
            'department' => $row['department'],
            'quantity' => $quantity,
            'unitPrice' => $pricing['unitPrice'],
            'total' => MiscLib::truncate2($pricing['unitPrice'] * $quantity),
            'regPrice' => $pricing['regPrice'],
            'scale' => $row['scale'],
            'tax' => $row['tax'],
            'foodstamp' => $row['foodstamp'],
            'discount' => $pricing['discount'],        
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

        return True;
    }
}

?>
