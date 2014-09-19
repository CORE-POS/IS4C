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

class EveryoneSale extends DiscountType 
{

    public function priceInfo($row,$quantity=1)
    {
        global $CORE_LOCAL;
        if (is_array($this->savedInfo)) {
            return $this->savedInfo;
        }

        $ret = array();

        $ret["regPrice"] = $row['normal_price'];
        $ret["unitPrice"] = $row['special_price'];

        /* if not by weight, just use the sticker price 
           (for scaled items, the UPC parse module
           calculates a weight estimate and sets a quantity
           so normal_price can be used. This could be done
           for all items, but typically the deli doesn't
           keep good track of whether their items are
           marked scale correctly since it only matters when an
           item goes on sale
        */
        if (isset($row['stickerprice']) && $row['scale'] == 0) {
            $ret['regPrice'] = $row['stickerprice'];
        }

        $ret['discount'] = ($ret['regPrice'] - $row['special_price']) * $quantity;
        $ret['memDiscount'] = 0;

        if ($row['line_item_discountable'] == 1 && $CORE_LOCAL->get("itemPD") > 0) {
            $discount = $row['special_price'] * (($CORE_LOCAL->get("itemPD")/100));
            $ret["unitPrice"] = $row['special_price'] - $discount;
            $ret["discount"] += ($discount * $quantity);
        }

        // enforce per-transaction limit
        if ($row['specialpricemethod']==0 && $row['specialquantity'] > 0) {
            $tdb = Database::tDataConnect();
            $chkQ = "SELECT sum(ItemQtty) FROM
                localtemptrans WHERE upc='{$row['upc']}'";
            if (strlen($row['mixmatchcode'])>0 && $row['mixmatchcode'][0]=='b') {
                $chkQ .= " OR mixMatch='{$row['mixmatchcode']}'";
            }
            $chkR = $tdb->query($chkQ);
            $prevSales = 0;
            if ($tdb->num_rows($chkR) > 0) {
                $prevSales = array_pop($tdb->fetch_row($chkR));
            }

            if ($prevSales >= $row['specialquantity']) {
                // already sold the limit; use non-sale price
                $ret['unitPrice'] = $row['normal_price'];
                $ret['discount'] = 0;
            } else if ( ($prevSales+$quantity) > $row['specialquantity'] ) {
                // this multiple qty ring will pass the limit
                // set discount based on appropriate quantity
                // and adjust unitPrice so total comes out correctly
                $discountQty = $row['specialquantity'] - $prevSales;
                $ret['discount'] = ($ret['regPrice'] - $row['special_price']) * $discountQty;
                $total = ($ret['regPrice'] * $quantity) - $ret['discount'];
                $ret['unitPrice'] = MiscLib::truncate2($total / $quantity);
            }
        }

        $this->savedRow = $row;
        $this->savedInfo = $ret;

        return $ret;
    }

    public function addDiscountLine()
    {
        global $CORE_LOCAL;    
        if ($this->savedRow['specialpricemethod'] == 0 && $this->savedInfo['discount'] != 0) {
            TransRecord::adddiscount($this->savedInfo['discount'],
                $this->savedRow['department']);
        }
    }

    public function isSale()
    {
        return true;
    }

    public function isMemberOnly()
    {
        return false;
    }

    public function isStaffOnly()
    {
        return false;
    }

}

