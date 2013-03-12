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
	  @param $data an SQL result object
	  @return an array of records
	*/
	function filter($data){
		$deptsUsed = array();
		$tenderTTL = 0.00;
		$tax = False;
		$discount = False;
		$returnset = array();

		$tagger = new DefaultReceiptTag();
		$type_map = array(
			'item' => new ItemFormat(),
			'tender' => new TenderFormat(),
			'total' => new TotalFormat(),
			'other' => new OtherFormat()
		);
	
		// walk through backwards and pick rows to keep
		$dbc = Database::tDataConnect();
		$count = 0;
		while($row = $dbc->fetch_row($data)){
			if ($tax === False && $row['upc'] == 'TAX'){
				// keep tax row. relevant to total and subtotal
				$tax = $tagger->tag($row);
				$tax['output'] = $type_map[$tax['tag']]->format($tax);
				if ($type_map[$tax['tag']]->is_bold)
					$tax['bold'] = True;
			}
			else if ($discount === False && $row['upc'] == 'DISCOUNT'){ 
				if ($discount['total'] == 0) continue;
				// keep discount row. need to pick up proper % discount still
				$discount = $tagger->tag($row);
				$discount['trans_type'] = 'S';
				$discount['output'] = $type_map[$discount['tag']]->format($tax);
				if ($type_map[$discount['tag']]->is_bold)
					$discount['bold'] = True;
			}
			else if ($row['trans_type'] == 'T'){
				// keep tender rows. use them to calculate total
				$tenderTTL += $row['total'];
				$tender = $tagger->tag($row);
				if ($type_map[$tender['tag']]->is_bold)
					$tender['bold'] = True;
				$returnset[] = $type_map[$tender['tag']]->format($tender);
				$count++;	
			}
			else if ($row['trans_type'] == 'I' || $row['trans_type'] == 'D'){
				// keep item rows
				// save department for fetching category headers
				// and update discount row if necessary
				if ($discount !== False && $row['percentDiscount'] > $discount['percentDiscount'])
					$discount['percentDiscount'] = $row['percentDiscount'];
				if (!isset($reverseMap[$row['category']]))
					$reverseMap[$row['category']] = True;
				$item = $tagger->tag($row);
				$item['output'] = $type_map[$item['tag']]->format($item);
				if ($type_map[$item['tag']]->is_bold)
					$item['bold'] = True;
				$returnset[] = $item;
				$count++;	
			}
			else if ($row['trans_status'] == '0' && substr($row['description'],0,7)=="** Tare"){
				// only deal with tare lines
				$prev = $count-1;
				if (isset($returnset[$prev]) && strlen($returnset[$prev]['description'])>7
				   && substr($returnset[$prev],0,7)=="** Tare"){
					continue; // ignore repeat tares
				}
				$tare = $tagger->tag($row);
				if (isset($returnset[$prev]))
					$tare['category'] = $returnset[$prev]['category'];
				$tare['output'] = $type_map[$tare['tag']]->format($tare);
				if ($type_map[$tare['tag']]->is_bold)
					$tare['bold'] = True;
				$returnset[] = $tare;
				$count++;
			}
		}

		$returnset = array_reverse($returnset);

		// add discount, subtotal, tax, and total records to the end
		if ($discount)
			$returnset[] = $discount;
		$subtotal = $tagger->tag(array('upc'=>'SUBTOTAL','trans_type'=>'S','total'=>(-1*$tenderTTL) - $tax['total']));
		$subtotal['output'] = $type_map[$subtotal['tag']]->format($subtotal);
		if ($type_map[$subtotal['tag']]->is_bold)
			$subtotal['bold'] = True;
		$returnset[] = $subtotal;
		if ($tax)
			$returnset[] = $tax;
		$ttlrow = $tagger->tag(array('upc'=>'TOTAL','trans_type'=>'S','total'=>-1*$tenderTTL));
		$ttlrow['output'] = $type_map[$subtotal['tag']]->format($ttlrow);
		if ($type_map[$ttlrow['tag']]->is_bold)
			$ttlrow['bold'] = True;
		$returnset[] = $ttlrow;
			
		// add category headers
		foreach($reverseMap as $catName => $val){
			$header = $tagger->tag(array('upc'=>'CAT_HEADER','trans_type'=>'H','description'=>$catName));
			$header['output'] = $type_map[$header['tag']]->format($header);
			if ($type_map[$header['tag']]->is_bold)
				$header['bold'] = True;
			$returnset[] = $header;
		}

		return $returnset;
	}

}
