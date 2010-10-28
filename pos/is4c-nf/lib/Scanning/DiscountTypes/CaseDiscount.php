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

if (!function_exists('addcdnotify')) include($IS4C_PATH.'lib/additem.php');

class CaseDiscount extends DiscountType {

	function priceInfo($row,$quantity=1){
		global $IS4C_LOCAL;
		$ret = array();

		$ret["regPrice"] = $row['normal_price'];
		$ret["unitPrice"] = $row['normal_price'];
		$ret['discount'] = 0;
		$ret['memDiscount'] = 0;

		if ($IS4C_LOCAL->get("casediscount") > 0 && $IS4C_LOCAL->get("casediscount") <= 100) {
			$casediscount = (100 - $IS4C_LOCAL->get("casediscount"))/100;
			$ret['unitPrice'] = $casediscount * $ret['unitPrice'];
			$ret['regPrice'] = $ret['unitPrice'];
			$IS4C_LOCAL->set("casediscount",0);
		}
		

		$this->savedRow = $row;
		$this->savedInfo = $ret;
		return $ret;
	}

	function addDiscountLine(){
		global $IS4C_LOCAL;
		addcdnotify();
	}

	function isSale(){
		return false;
	}

	function isMemberOnly(){
		return false;
	}

	function isStaffOnly(){
		return false;
	}

}

?>
