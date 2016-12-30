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
  @class DefaultReceiptFilter
  Module for sorting receipt records
  Subclasses can modify the sort()
  method to alter behavior
*/
class DefaultReceiptSort 
{

    private $BLANKLINE = array(
        'upc' => 'BLANKLINE',
        'description' => '',
        'trans_type' => '0',
        'total' => 0,
    );

    /**
      Sorting function
      @param $rowset an array of records
      @return an array of records
    */
    public function sort(array $rowset)
    {
        $tax = false;
        $discount = false;
        $total = false;
        $subtotal = false;
        $items = array('_uncategorized'=>array());
        $headers = array();
        $tenders = array();

        // accumulate the various pieces
        foreach($rowset as $row) {
            if ($row['upc'] == 'TAX') {
                $tax = $row;
            } elseif($row['upc'] == 'DISCOUNT') {
                $discount = $row;
            } elseif($row['upc'] == 'SUBTOTAL') {
                $subtotal = $row;
            } elseif($row['upc'] == 'TOTAL') {
                $total = $row;
            } elseif($row['upc'] == 'CAT_HEADER') {
                $headers[] = $row;
            } elseif($row['trans_type'] == 'T' && $row['department'] == 0) {
                $tenders[] = $row;
            } else {
                $set = '_uncategorized';
                if(isset($row['category']) && !empty($row['category'])) {
                    if (!isset($items[$row['category']])) {
                        $items[$row['category']] = array();
                    }
                    $set = $row['category'];
                }
                $items[$set][] = $row;
            }
        }

        $returnset = array();
    
        // first add uncategorized item records
        if (count($items['_uncategorized'] > 0)) {
            usort($items['_uncategorized'],array('COREPOS\\pos\\lib\\ReceiptBuilding\\Sort\\DefaultReceiptSort','recordCompare'));
            foreach($items['_uncategorized'] as $row) {
                $returnset[] = $row;
            }
        }

        // next do categorized alphabetically
        // if there are items for a given header,
        // add that header followed by those items
        if (count($headers) > 0) {
            asort($headers);
            foreach($headers as $hrow) {
                if (count($items[$hrow['description']]) > 0) {
                    $returnset[] = $hrow;
                    usort($items[$hrow['description']],array('COREPOS\\pos\\lib\\ReceiptBuilding\\Sort\\DefaultReceiptSort','recordCompare'));
                    foreach($items[$hrow['description']] as $irow) {
                        $returnset[] = $irow;
                    }
                }
            }
        }

        // blank line between items & totals
        $returnset[] = $this->BLANKLINE;

        // then discount, subtotal, tax, total
        if ($discount !== false) {
            $returnset[] = $discount;
        }
        if ($subtotal !== false) {
            $returnset[] = $subtotal;
        }
        if ($tax !== false) {
            $returnset[] = $tax;
        }
        if ($total !== false) {
            $returnset[] = $total;
        }

        // blank line between totals & tenders
        $returnset[] = $this->BLANKLINE;

        // finally tenders
        if(count($tenders) > 0) {
            usort($tenders, array('COREPOS\\pos\\lib\\ReceiptBuilding\\Sort\\DefaultReceiptSort','recordCompare'));
            foreach($tenders as $trow) {
                $returnset[] = $trow;
            }
        }

        return $returnset;
    }

    // utility function to sort records by the trans_id field
    static public function recordCompare(array $rec1, array $rec2){
        if (!isset($rec1['trans_id']) || !isset($rec2['trans_id'])) {
            return 0;
        } elseif ($rec1['trans_id'] == $rec2['trans_id']) {
            return 0;
        }
        return $rec1['trans_id'] < $rec2['trans_id'] ? -1 : 1;
    }
}    

