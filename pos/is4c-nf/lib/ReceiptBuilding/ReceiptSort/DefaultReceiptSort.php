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

/**
  @class DefaultReceiptFilter
  Module for sorting receipt records
  Subclasses can modify the sort()
  method to alter behavior
*/
class DefaultReceiptSort 
{

    private $BLANK_LINE = array(
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
	public function sort($rowset)
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
				if(isset($row['category']) && !empty($row['category'])) {
					if (!isset($items[$row['category']])) {
						$items[$row['category']] = array();
                    }
					//$items[$row['category']] = $this->upc_merge($items[$row['category']],$row);
					$items[$row['category']][] = $row;
				} else {
					//$items['_uncategorized'] = $this->upc_merge($items['_uncategorized'],$row);
					$items['_uncategorized'][] = $row;
				}
			}
		}

		$returnset = array();
	
		// first add uncategorized item records
		if (count($items['_uncategorized'] > 0)) {
			usort($items['_uncategorized'],array('DefaultReceiptSort','record_compare'));
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
					usort($items[$hrow['description']],array('DefaultReceiptSort','record_compare'));
					foreach($items[$hrow['description']] as $irow) {
						$returnset[] = $irow;
                    }
				}
			}
		}

        // blank line between items & totals
        $returnset[] = $this->BLANK_LINE;

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
        $returnset[] = $this->BLANK_LINE;

		// finally tenders
		if(count($tenders) > 0) {
			usort($tenders, array('DefaultReceiptSort','record_compare'));
			foreach($tenders as $trow) {
				$returnset[] = $trow;
            }
		}

		return $returnset;
	}

	// utility function to sort records by the trans_id field
	static public function record_compare($r1,$r2){
		if (!isset($r1['trans_id']) || !isset($r2['trans_id'])) {
			return 0;
        } else if ($r1['trans_id'] == $r2['trans_id']) {
            return 0;
        } else {
            return $r1['trans_id'] < $r2['trans_id'] ? -1 : 1;
        }
	}

	/**
	  Combine item records when appropriate
	  @param $cur an array of records some of which
		are keyed by UPC
	  @param $new a new record
	  @return $cur with the new record added
	*/
	protected function upc_merge($cur, $new) {
		if ($new['trans_status'] != '' || $new['trans_type'] != 'I'
		   || $new['scale'] != 0 || $new['matched'] != 0) {
			/**
			  By-weight, refund, void, or group discount
			  items shouldn't be combined. They
			  get added with a simple numerical key
			*/
			$cur[] = $new;
		} else if (isset($cur[$new['upc']])) {
			/**
			  Valid item to merge; add to the existing record
			*/
			$cur[$new['upc']]['ItemQtty'] += $new['ItemQtty'];
			$cur[$new['upc']]['quantity'] += $new['quantity'];
			$cur[$new['upc']]['total'] += $new['total'];
		} else {
			/**
			  Valid item to merge; add record with
			  UPC key.
			*/
			$cur[$new['upc']] = $new;
		}

		return $cur;
	}
}	

