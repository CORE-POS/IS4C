<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op.

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

namespace COREPOS\pos\lib\ReceiptBuilding\Sort;

/**
  @class DiscountFirstReceiptSort
*/
class DiscountFirstReceiptSort extends DefaultReceiptSort 
{
    public function sort(array $rowset)
    {
        $rowset = parent::sort($rowset);
        $newset = array();
        for ($i=0; $i<count($rowset); $i++) {
            if ($rowset[$i]['upc'] == 'DISCOUNT') {
                /**
                  Discount should be followed by a subtotal line. If it
                  isn't, just bail out and return the default sort
                */
                if (!isset($rowset[$i+1]) || $rowset[$i+1]['upc'] != 'SUBTOTAL') {
                    return $rowset;
                }
                /**
                  Swap the discount row with the subtotal row
                  and and put the prediscount amount in subtotal
                */
                $discount_amount = $rowset[$i]['total'];
                $subtotal_amount = $rowset[$i+1]['total'];
                $subtotal_amount -= $discount_amount; 
                $rowset[$i+1]['total'] = $subtotal_amount;
                $newset[] = $rowset[$i+1];
                $newset[] = $rowset[$i];
                $i++;
            } else {
                $newset[] = $rowset[$i];
            }
        }

        return $newset;
    }
}

