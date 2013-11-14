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
   @class BasicPM
   
   This module just adds the item with the given
   quantity and price/discount information

   Classically, this is pricemethod=0
*/

class BasicPM extends PriceMethod {

    private $error_msg = '';

    function addItem($row,$quantity,$priceObj){
        if ($quantity == 0) return False;

        /*
          Use "quantity" field in products record as a per-transaction
          limit. This is analogous to a similar feature with sale items.
        */
        if (!$priceObj->isSale() && $row['quantity'] > 0){
            $db = Database::tDataConnect();
            $query = "SELECT SUM(quantity) as qty FROM localtemptrans
                WHERE upc='{$row['upc']}'";
            $result = $db->query($query);
            if ($db->num_rows($result) > 0){
                $chkRow = $db->fetch_row($result);
                if (($chkRow['qty']+$quantity) > $row['quantity']){
                    $this->error_msg = _("item only allows ")
                            .$row['quantity']
                            ._(" per transaction");
                    return False;
                }
            }
        }

        $pricing = $priceObj->priceInfo($row,$quantity);

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
            $row['pricemethod'],
            $row['quantity'],
            $row['groupprice'],
            $row['mixmatchcode'],
            0,
            0,
            (isset($row['cost'])?$row['cost']*$quantity:0.00),
            (isset($row['numflag'])?$row['numflag']:0),
            (isset($row['charflag'])?$row['charflag']:'')
        );

        return True;
    }

    function errorInfo(){
        return $this->error_msg;
    }
}

?>
