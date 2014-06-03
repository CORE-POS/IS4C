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

class BasicPM extends PriceMethod 
{

    private $error_msg = '';

    function addItem($row,$quantity,$priceObj)
    {
        global $CORE_LOCAL;
        if ($quantity == 0) return False;

        // enforce limit on discounting sale items
        $dsi = $CORE_LOCAL->get('DiscountableSaleItems');
        if ($dsi == 0 && $dsi !== '' && $priceObj->isSale()) {
            $row['discount'] = 0;
        }

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
            'memDiscount' => $pricing['memDiscount'],
            'discountable' => $row['discount'],
            'discounttype' => $row['discounttype'],
            'ItemQtty' => $quantity,
            'volDiscType' => $row['pricemethod'],
            'volume' => $row['quantity'],
            'VolSpecial' => $row['groupprice'],
            'mixMatch' => $row['mixmatchcode'],
            'cost' => (isset($row['cost'])?$row['cost']*$quantity:0.00),
            'numflag' => (isset($row['numflag'])?$row['numflag']:0),
            'charflag' => (isset($row['charflag'])?$row['charflag']:'')
        ));

        return true;
    }

    function errorInfo()
    {
        return $this->error_msg;
    }
}

