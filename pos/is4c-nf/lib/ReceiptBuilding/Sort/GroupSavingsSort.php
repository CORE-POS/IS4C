<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op.

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
  @class InOrderReceiptSort
  Does nothing. Leave items in the order they
  were entered.
*/
class GroupSavingsSort extends DefaultReceiptSort 
{

    /**
      Find coupons and member special lines
    */
    private function getCouponsAndSpecials(array $items)
    {
        $splice = array();
        $coupons = array();
        $memspecial = array();
        for ($i=0; $i<count($items); $i++) {
            $item = $items[$i];
            if (!isset($item['trans_type']) || !isset($item['trans_status'])) {
                continue;
            }
            if ($item['trans_type'] == 'T' && $item['department'] != 0) {
                $splice[] = $i;
                $coupons[] = $item;
            } elseif ($item['trans_status'] == 'M') {
                $splice[] = $i;
                $memspecial[] = $item;
            }
        }

        return array($coupons, $memspecial, $splice);
    }

    /**
      Insert $lines adjacent to an $item where they
      share a common $field value
    */
    private function addLinesNearField(array $items, array $lines, $field)
    {
        foreach ($lines as $line) {
            if ($line['total'] == 0) {
                continue;
            }
            $added = false;
            for ($i=0; $i<count($items); $i++) {
                if (!isset($items[$i][$field])) {
                    continue;
                }
                if ($items[$i][$field] == $line[$field]) {
                    $replacement = array($items[$i], $line);
                    array_splice($items, $i, 1, $replacement);
                    $added = true;
                    break;
                }
                if (!$added) {
                    $items[] = $line;
                }
            }
        }

        return $items;
    }

    /**
      Sorting function
      @param $rowset an array of records
      @return an array of records
    */
    public function sort(array $rowset)
    {
        /**
          Split rows into tenders, items, and the middle section
        */
        $items = array();
        $tenders = array();
        $middle = array();
        foreach ($rowset as $row) {
            if ($row['trans_type'] == 'T' && $row['department'] == 0) {
                $tenders[] = $row;
            } elseif ($row['upc'] == 'TOTAL' || $row['upc'] == 'SUBTOTAL' || $row['upc'] == 'TAX' || $row['upc'] == 'DISCOUNT') {
                $middle[] = $row;
            } else {
                $items[] = $row;
            }
        }

        list($coupons, $memspecial, $splice) = $this->getCouponsAndSpecials($items);

        /**
          Remove coupon and member special lines
        */
        foreach ($splice as $index) {
            array_splice($items, $index, 1);
        }

        $items = $this->addLinesNearField($items, $memspecial, 'upc');
        $items = $this->addLinesNearField($items, $coupons, 'department');

        return array_merge($items, $middle, $tenders);
    }

}    

