<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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
$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }

if (!class_exists('DiscountType')) include($CORE_PATH.'lib/Scanning/DiscountType.php');
if (!isset($CORE_LOCAL)) include($CORE_PATH."lib/LocalStorage/conf.php");

if (!function_exists('adddiscount')) include($CORE_PATH.'lib/additem.php');

class StaffSale extends DiscountType {

	function priceInfo($row,$quantity=1){
		global $CORE_LOCAL;
		if (is_array($this->savedInfo))
			return $this->savedInfo;

		$ret = array();

		$ret["regPrice"] = $row['normal_price'];
		$ret["unitPrice"] = $row['normal_price'];

		$ret['discount'] = 0;
		$ret['memDiscount'] = ($ret['regPrice'] - $row['special_price']) * $quantity;

		if ($CORE_LOCAL->get("isStaff") == 1)
			$ret["unitPrice"] = $row['special_price'];

		$this->savedRow = $row;
		$this->savedInfo = $ret;
		return $ret;
	}

	function addDiscountLine(){
		global $CORE_LOCAL;	
		if ($CORE_LOCAL->get("isStaff") == 1){
			$CORE_LOCAL->set("voided",2);
			adddiscount($this->savedInfo['memDiscount'],
				$this->savedRow['department']);
		}
	}

	function isSale(){
		return true;
	}

	function isMemberOnly(){
		return false;
	}

	function isStaffOnly(){
		return true;
	}

}

?>
