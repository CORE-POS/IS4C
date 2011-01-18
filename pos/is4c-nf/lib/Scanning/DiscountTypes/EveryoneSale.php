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
$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

if (!class_exists('DiscountType')) include($IS4C_PATH.'lib/Scanning/DiscountType.php');
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

if (!function_exists('adddiscount')) include($IS4C_PATH.'lib/additem.php');

class EveryoneSale extends DiscountType {

	function priceInfo($row,$quantity=1){
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
		if (isset($row['stickerprice']) && $row['scale'] == 0){
			$ret['regPrice'] = $row['stickerprice'];
		}

		$ret['discount'] = ($ret['regPrice'] - $row['special_price']) * $quantity;
		$ret['memDiscount'] = 0;

		$this->savedRow = $row;
		$this->savedInfo = $ret;
		return $ret;
	}

	function addDiscountLine(){
		global $IS4C_LOCAL;	
		$IS4C_LOCAL->set("voided",2);
		adddiscount($this->savedInfo['discount'],
			$this->savedRow['department']);
	}

	function isSale(){
		return true;
	}

	function isMemberOnly(){
		return false;
	}

	function isStaffOnly(){
		return false;
	}

}

?>
