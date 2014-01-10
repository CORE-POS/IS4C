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
  Module for adding and removing
  records from the set that will be
  printed. Subclasses may modify the
  filter() method to alter behavior
*/
class DefaultReceiptFilter 
{

	/**
	  Filtering function
	  @param $data an SQL result object
	  @return an array of records
	*/
	public function filter($data)
    {
		$deptsUsed = array();
		$tenderTTL = 0.00;
		$tax = False;
		$discount = False;
		$returnset = array();

		// walk through backwards and pick rows to keep
		$dbc = Database::tDataConnect();
		$count = 0;
		while($row = $dbc->fetch_row($data)) {
			if ($tax === False && $row['upc'] == 'TAX') {
				// keep tax row. relevant to total and subtotal
				$tax = $row;
			} else if ($discount === False && $row['upc'] == 'DISCOUNT') { 
				if ($row['total'] == 0) continue;
				// keep discount row. need to pick up proper % discount still
				$discount = $row;
				$discount['trans_type'] = 'S';
			} else if ($row['trans_type'] == 'T' && $row['department'] == 0){
				// keep tender rows. use them to calculate total
                // rows with departments are usually coupons and those
                // should be treated more like items than like tenders
				$tenderTTL += $row['total'];
				$returnset[] = $row;
				$count++;
			} else if ($row['trans_type'] == 'I' || $row['trans_type'] == 'D' || ($row['trans_type']=='T' && $row['department'] != 0)){
				// keep item rows
				// save department for fetching category headers
				// and update discount row if necessary
				if ($discount !== False && $row['percentDiscount'] > $discount['percentDiscount']) {
					$discount['percentDiscount'] = $row['percentDiscount'];
                }
				if (!isset($reverseMap[$row['category']])) {
					$reverseMap[$row['category']] = true;
                }
				$returnset[] = $row;
				$count++;	
			} else if ($row['trans_status'] == '0' && substr($row['description'],0,7)=="** Tare"){
				// only deal with tare lines
				$prev = $count-1;
				if (isset($returnset[$prev]) && strlen($returnset[$prev]['description'])>7
				   && substr($returnset[$prev],0,7)=="** Tare") {
					continue; // ignore repeat tares
				}
				$tare = $row;
				if (isset($returnset[$prev])) {
					$tare['category'] = $returnset[$prev]['category'];
                }
				$returnset[] = $tare;
				$count++;
			}
		}

		$returnset = array_reverse($returnset);

		// add discount, subtotal, tax, and total records to the end
		if ($discount) {
			$returnset[] = $discount;
		}
		$returnset[] = array('upc'=>'SUBTOTAL','trans_type'=>'S',
				'total'=>(-1*$tenderTTL) - $tax['total']);
		if ($tax) {
			$returnset[] = $tax;
		}
		$returnset[] = array('upc'=>'TOTAL','trans_type'=>'S','total'=>-1*$tenderTTL);
			
		// add category headers
		foreach($reverseMap as $catName => $val) {
            if (!empty($catName)) {
                $returnset[] = array('upc'=>'CAT_HEADER','trans_type'=>'H','description'=>$catName);
            }
		}

		return $returnset;
	}

}

