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
class DefaultReceiptFilter {

	/**
	  Filtering function
	  @param $rowset an array of records
	  @return an array of records
	*/
	function filter($rowset){
		$deptsUsed = array();
		$tenderTTL = 0.00;
		$tax = False;
		$discount = False;
		$returnset = array();
	
		// walk through backwards and pick rows to keep
		for($i=count($rowset)-1; $i>=0; $i--){
			if ($tax === False && $rowset[$i]['upc'] == 'TAX'){
				// keep tax row. relevant to total and subtotal
				$tax = $rowset[$i];
			}
			else if ($discount === False && $rowset[$i]['upc'] == 'DISCOUNT'){ 
				// keep discount row. need to pick up proper % discount still
				$discount = $rowset[$i];
				$discount['trans_type'] = 'S';
				if ($discount['total'] == 0) $discount = False;
			}
			else if ($rowset[$i]['trans_type'] == 'T'){
				// keep tender rows. use them to calculate total
				$tenderTTL += $rowset[$i]['total'];
				$returnset[] = $rowset[$i];
			}
			else if ($rowset[$i]['trans_type'] == 'I' || $rowset[$i]['trans_type'] == 'D'){
				// keep item rows
				// save department for fetching category headers
				// and update discount row if necessary
				$deptsUsed[$rowset[$i]['department']] = True;
				if ($discount !== False && $rowset[$i]['percentDiscount'] > $discount['percentDiscount'])
					$discount['percentDiscount'] = $rowset[$i]['percentDiscount'];
				$returnset[] = $rowset[$i];
			}
			else if ($rowset[$i]['trans_status'] == '0'){
				// keep tare lines but only if the next record is NOT a tare line
				if (substr($rowset[$i]['description'],0,7) == "** Tare" &&
				    (!isset($rowset[$i+1]) || strlen($rowset[$i+1]['description']) < 7
				     || substr($rowset[$i+1]['description'],0,7) != "** Tare") ){
					$returnset[] = $rowset[$i];
				}
			}
		}

		// reverse the return array since it was built backwards
		$returnset = array_reverse($returnset);

		// add discount, subtotal, tax, and total records to the end
		if ($discount)
			$returnset[] = $discount;
		$returnset[] = array('upc'=>'SUBTOTAL','trans_type'=>'S','total'=>(-1*$tenderTTL) - $tax['total']);
		if ($tax)
			$returnset[] = $tax;
		$returnset[] = array('upc'=>'TOTAL','trans_type'=>'S','total'=>-1*$tenderTTL);
			
		// look up category names
		$deptclause = "(";
		foreach($deptsUsed as $number => $val){
			$deptclause .= $number.",";
		}
		$deptclause = rtrim($deptclause,",").")";
		$dbc = Database::pDataConnect();
		$q = "SELECT subdept_name,dept_ID FROM subdepts WHERE dept_ID IN ".$deptclause;
		$r = $dbc->query($q);		
		$categoryMap = array();
		while($w = $dbc->fetch_row($r)){
			$categoryMap[$w['dept_ID']] = $w['subdept_name'];
		}
		
		// add categories to the appropriate records
		$reverseMap = array();
		for($i=0;$i<count($returnset);$i++){
			if ($returnset[$i]['trans_type'] != 'I' && $returnset[$i]['trans_type'] != 'D' 
			    && $returnset[$i]['trans_type'] != '0'){
				continue;
			}

			if ($returnset[$i]['trans_type'] != '0' && isset($categoryMap[$returnset[$i]['department']])){
				// add category to item & department records
				$returnset[$i]['category'] = $categoryMap[$returnset[$i]['department']];
				$reverseMap[$returnset[$i]['category']] = True;
			}
			elseif ($returnset[$i]['trans_type'] == '0' && isset($returnset[$i+1]) &&
				// add category to tare records
				isset($categoryMap[$returnset[$i+1]['department']])){
				$returnset[$i]['category'] = $categoryMap[$returnset[$i+1]['department']];
			}
		}

		// add category headers
		foreach($reverseMap as $catName => $val){
			$returnset[] = array('upc'=>'CAT_HEADER','trans_type'=>'H','description'=>$catName);
		}

		return $returnset;
	}

}
