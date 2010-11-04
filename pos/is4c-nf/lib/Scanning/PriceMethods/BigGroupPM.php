<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

    This file is part of IS4C.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/* Big Group Price Method
   
  elaborate set matching; can require up to
  11 separate items
  Qualifying item(s) have a mixmatch code
  with a matching 'stem' plus '_qX'
  (e.g., mmitemstem_q0, mmitemstem_q1, etc)
  Discount item has the same stem plus '_d'
  (e.g, mmitemstem_d)

  Customer has to buy one item from each
  qualifying group as well as an item from
  the discount group. 
*/
$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

if (!class_exists('PriceMethod')) include($IS4C_PATH.'lib/Scanning/PriceMethod.php');
if (!function_exists('addItem')) include($IS4C_PATH.'lib/additem.php');
if (!function_exists('truncate2')) include($IS4C_PATH.'lib/lib.php');

class BigGroupPM extends PriceMethod {

	function addItem($row,$quantity,$priceObj){
		if ($quantity == 0) return false;

		$pricing = $priceObj->priceInfo($row,$quantity);

		$stem = substr($mixMatch,0,10);	
		$sets = 99;
		// count up total sets
		for($i=0; $i<=$volume; $i++){
			$tmp = $stem."_q".$i;
			if ($volume == $i) $tmp = $stem.'_d';

			$chkQ = "SELECT sum(CASE WHEN scale=0 THEN ItemQtty ELSE 1 END) 
				FROM localtemptrans WHERE mixmatch='$tmp' 
				and trans_status<>'R'";
			$chkR = $dbt->query($chkQ);
			$tsets = 0;
			if ($dbt->num_rows($chkR) > 0){
				$tsets = array_pop($dbt->fetch_row($chkR));
			}
			if ($tmp == $mixMatch){
				$tsets += is_int($quantity)?$quantity:1;
			}

			if ($tsets < $sets)
				$sets = $tsets;

			// item not found, no point continuing
			if ($sets == 0) break;
		}

		// count existing sets
		$matches = 0;
		$mQ = "SELECT sum(matched) FROM localtemptrans WHERE
			left(mixmatch,11)='{$stem}_'";
		$mR = $dbt->query($mQ);
		if ($dbt->num_rows($mR) > 0)
			$matches = array_pop($dbt->fetch_row($mR));
		$sets -= $matches;

		// this means the current item
		// completes a new set
		if ($sets > 0){
			if ($priceObj->isSale()){
				if ($priceObj->isMemberSale() || $priceObj->isStaffSale())
					$pricing['memDiscount'] = truncate2($row['specialgroupprice'] * $quantity);
				else
					$pricing['discount'] = truncate2($row['specialgroupprice'] * $quantity);
			}
			else {
				$pricing['unitPrice'] = $pricing['unitPrice'] - $row['specialgroupprice'];
			}

			addItem($row['upc'],
				$row['description'],
				'I',
				' ',
				' ',
				$row['department'],
				$quantity,
				$pricing['unitPrice'],
				truncate2($pricing['unitPrice'] * $quantity),
				$pricing['regPrice'],
				$row['scale'],
				$row['tax'],
				$row['foodstamp'],
				$pricing['discount'],
				$pricing['memDiscount'],
				$row['discount'],
				$row['discounttype'],
				$quantity,
				($priceObj->isSale() ? $row['specialpricemethod'] : $row['pricemethod']),
				($priceObj->isSale() ? $row['specialquantity'] : $row['quantity']),
				($priceObj->isSale() ? $row['specialgroupprice'] : $row['groupprice']),
				$row['mixmatchcode'],
				$sets,
				0,
				(isset($row['cost'])?$row['cost']*$quantity:0.00),
				(isset($row['numflag'])?$row['numflag']:0),
				(isset($row['charflag'])?$row['charflag']:'')
			);
		}
		else {
			// not a new set, treat as a regular item
			addItem($row['upc'],
				$row['description'],
				'I',
				' ',
				' ',
				$row['department'],
				$quantity,
				$pricing['unitPrice'],
				truncate2($pricing['unitPrice'] * $quantity),
				$pricing['regPrice'],
				$row['scale'],
				$row['tax'],
				$row['foodstamp'],
				$pricing['discount'],
				$pricing['memDiscount'],
				$row['discount'],
				$row['discounttype'],
				$quantity,
				$row['pricemethod'],
				$row['quantity'],
				$row['groupprice'],
				$row['mixmatchcode'],
				0,
				0,
				(isset($row['cost'])?$row['cost']*$quantity:0.00),
				(isset($row['numflag'])?$row['numflag']:0),
				(isset($row['charflag'])?$row['charflag']:'')
			);
		}
	}
}

?>
