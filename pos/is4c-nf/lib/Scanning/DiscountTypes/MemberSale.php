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

class MemberSale extends DiscountType {

	function priceInfo($row,$quantity=1){
		global $CORE_LOCAL;
		if (is_array($this->savedInfo))
			return $this->savedInfo;

		$ret = array();

		$ret["regPrice"] = $row['normal_price'];
		$ret["unitPrice"] = $row['normal_price'];

		$ret['discount'] = 0;
		$ret['memDiscount'] = MiscLib::truncate2(($ret['regPrice'] - $row['special_price']) * $quantity);

		if ($CORE_LOCAL->get("isMember") == 1 || $CORE_LOCAL->get("memberID") == $CORE_LOCAL->get("visitingMem"))
			$ret["unitPrice"] = $row['special_price'];

		if ($CORE_LOCAL->get("itemPD") > 0){
			$discount = $ret['unitPrice'] * (($CORE_LOCAL->get("itemPD")/100));
			$ret["unitPrice"] -= $discount;
			$ret["discount"] += ($discount * $quantity);
		}

		if ($row['special_price'] == $row['specialgroupprice'] && $row['special_price'] <> 0){
			// volumed priced
			$ret['unitPrice'] = $row['normal_price'];
			$ret['memDiscount'] = 0;
		}

		$this->savedRow = $row;
		$this->savedInfo = $ret;
		return $ret;
	}

	function addDiscountLine(){
		global $CORE_LOCAL;	
		if ($CORE_LOCAL->get("isMember") == 1 || $CORE_LOCAL->get("memberID") == $CORE_LOCAL->get("visitingMem")){
			TransRecord::adddiscount($this->savedInfo['memDiscount'],
				$this->savedRow['department']);
		}
		if ($this->savedInfo['discount'] != 0){
			TransRecord::adddiscount($this->savedInfo['discount'],
					$this->savedRow['department']);
		}
	}

	function isSale(){
		return true;
	}

	function isMemberOnly(){
		return true;
	}

	function isStaffOnly(){
		return false;
	}

}

?>
