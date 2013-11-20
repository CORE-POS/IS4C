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
   @class GroupPM
   
   This module provides the simplest form of group price:
   each item is sold for the group price divided by the 
   group size. Buying a "complete" group is not required.

   In most locations, this is pricemethod 1 or 2
*/

class GroupPM extends PriceMethod {

    function addItem($row,$quantity,$priceObj){
        if ($quantity == 0) return false;

        $pricing = $priceObj->priceInfo($row,$quantity);

        if ($priceObj->isSale()){
            $disc = $pricing['unitPrice'] - ($row['specialgroupprice'] / $row['specialquantity']);
            if ($priceObj->isMemberSale() || $priceObj->isStaffSale())
                $pricing['memDiscount'] = MiscLib::truncate2($disc * $quantity);
            else
                $pricing['discount'] = MiscLib::truncate2($disc * $quantity);
        }
        else {
            $pricing['unitPrice'] = $row['groupprice'] / $row['quantity'];
        }

        TransRecord::addItem($row['upc'],
            $row['description'],
            'I',
            ' ',
            ' ',
            $row['department'],
            $quantity,
            $pricing['unitPrice'],
            MiscLib::truncate2($pricing['unitPrice'] * $quantity),
            $pricing['regPrice'],
            $row['scale'],
            $row['tax'],
            $row['foodstamp'],
            $pricing['discount'],
            $pricing['memDiscount'],
            $row['discount'],
            $row['discounttype'],
            $quantity,
            ($priceObj->isSale() ? $row['specialpricemethod'] : $row['pricemethod']),
            ($priceObj->isSale() ? $row['specialquantity'] : $row['quantity']),
            ($priceObj->isSale() ? $row['specialgroupprice'] : $row['groupprice']),
            $row['mixmatchcode'],
            0,
            0,
            (isset($row['cost'])?$row['cost']*$quantity:0.00),
            (isset($row['numflag'])?$row['numflag']:0),
            (isset($row['charflag'])?$row['charflag']:'')
        );
    
        return True;
    }
}

?>
