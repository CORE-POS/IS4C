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
		$reverseMap = array();
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
                if ($row['trans_type'] == 'I' && $row['matched'] == 0 && $row['scale'] == 0) {
                    // merge duplicate items
                    $merged = false;
                    for ($i=0; $i<count($returnset); $i++) {
                        if ($row['upc'] == $returnset[$i]['upc']
                            && $returnset[$i]['matched'] == 0
                            && $returnset[$i]['scale'] == 0
                            && $row['unitPrice'] == $returnset[$i]['unitPrice']
                            && $row['regPrice'] == $returnset[$i]['regPrice']
                            && $row['trans_status'] == $returnset[$i]['trans_status']) {

                            $returnset[$i]['ItemQtty'] += $row['ItemQtty'];
                            $returnset[$i]['quantity'] += $row['quantity'];
                            $returnset[$i]['total'] += $row['total'];
                            $merged = true;
                            break;
                        }
                    }
                    if (!$merged) {
                        $returnset[] = $row;
                        $count++;	
                    }
                } else {
                    $returnset[] = $row;
                    $count++;	
                }
            } else if ($row['trans_type'] == 'C' && $row['trans_subtype'] == 'CM') {
                // print comment rows as if they were items
                $row['trans_type'] = 'I';
                $row['upc'] = 'COMMENT';
				$returnset[] = $row;
				$count++;	
			} else if ($row['trans_type'] == '0' && substr($row['description'],0,7)=="** Tare"){
				// only deal with tare lines
				$prev = $count-1;
				if (isset($returnset[$prev]) && 
                    strlen($returnset[$prev]['description']) > 7 &&
				    substr($returnset[$prev]['description'], 0, 7) == '** Tare'
                   ) { 
					continue; // ignore repeated tares
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

        /**
          Re-write trans_id on member special lines to
          be adjacent to applicable item
        */
        $removes = array();
        for ($i=0; $i<count($returnset); $i++) {
            if (!isset($returnset[$i]['trans_type']) || !isset($returnset[$i]['trans_status'])) {
                continue;
            }
            if ($returnset[$i]['trans_type'] == 'I' && $returnset[$i]['trans_status'] == 'M') {
                if ($returnset[$i]['total'] == 0) {
                    $removes[] = $i;
                    continue;
                }
                for ($j=0; $j<count($returnset); $j++) {
                    if (!isset($returnset[$j]['trans_type']) || !isset($returnset[$j]['trans_status'])) {
                        continue;
                    }
                    if ($returnset[$j]['trans_status'] == 'M') {
                        continue;
                    }
                    if ($returnset[$j]['upc'] == $returnset[$i]['upc']) {
                        $returnset[$i]['trans_id'] = $returnset[$j]['trans_id'] + 0.25;
                        break;
                    }
                }
            }
        }
        foreach ($removes as $index) {
            array_splice($returnset, $index, 1);
        }

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

