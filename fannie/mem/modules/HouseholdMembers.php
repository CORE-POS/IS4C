<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op, Duluth, MN

    This file is part of Fannie.

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

class HouseholdMembers extends MemberModule {

	function ShowEditForm($memNum){
		global $FANNIE_URL;

		$dbc = $this->db();
		
		$infoQ = sprintf("SELECT c.FirstName,c.LastName
				FROM custdata AS c 
				WHERE c.CardNo=%d AND c.personNum > 1
				ORDER BY c.personNum",$memNum);
		$infoR = $dbc->query($infoQ);

		$ret = "<fieldset><legend>Household Members</legend>";
		$ret .= "<table class=\"MemFormTable\" 
			border=\"0\">";
		
		$count = 0;	
		while($infoW = $dbc->fetch_row($infoR)){
			$ret .= sprintf('<tr><th>First Name</th>
				<td><input name="HouseholdMembers_fn[]"
				maxlength="30" value="%s" /></td>
				<th>Last Name</th>
				<td><input name="HouseholdMembers_ln[]"
				maxlength="30" value="%s" /></td></tr>',
				$infoW['FirstName'],$infoW['LastName']);
			$count++;
		}

		while($count < 3){
			$ret .= sprintf('<tr><th>First Name</th>
				<td><input name="HouseholdMembers_fn[]"
				maxlength="30" value="" /></td>
				<th>Last Name</th>
				<td><input name="HouseholdMembers_ln[]"
				maxlength="30" value="" /></td></tr>');
			$count++;
		}

		$ret .= "</table></fieldset>";
		return $ret;
	}

	function SaveFormData($memNum){
		$dbc = $this->db();

		$prepQ = sprintf("DELETE FROM custdata WHERE CardNo=%d
			AND personNum > 1",$memNum);
		$test = $dbc->query($prepQ);
		$ret = "";
		if ($test === False)
			$ret = "Error: Problem saving household members<br />";	

		$fns = $_REQUEST['HouseholdMembers_fn'];
		$lns = $_REQUEST['HouseholdMembers_ln'];

		$settingsQ = sprintf("SELECT CashBack,Balance,Discount,MemDiscountLimit,ChargeOk,
				WriteChecks,StoreCoupons,Type,memType,staff,SSI,Purchases,
				NumberOfChecks,memCoupons,Shown FROM custdata
				WHERE CardNo=%d",$memNum);		
		$settingsR = $dbc->query($settingsQ);
		$row = $dbc->fetch_row($settingsR);
		
		$pn = 2;
		for($i=0; $i<count($lns); $i++){
			if (empty($fns[$i]) && empty($lns[$i])) continue;

			$insQ = sprintf("INSERT INTO custdata (CardNo,personNum,LastName,FirstName,CashBack,
				Balance,Discount,MemDiscountLimit,ChargeOk,WriteChecks,StoreCoupons,
				Type,memType,staff,SSI,Purchases,NumberOfChecks,memCoupons,blueLine,
				Shown) VALUES (%d,%d,%s,%s,%f,%f,%d,%f,%d,%d,%d,'%s',%d,%d,%d,%f,
				%d,%d,%s,%d)",$memNum,$pn,$dbc->escape($lns[$i]),$dbc->escape($fns[$i]),
				$row['CashBack'],$row['Balance'],$row['Discount'],$row['MemDiscountLimit'],
				$row['ChargeOk'],$row['WriteChecks'],$row['StoreCoupons'],$row['Type'],
				$row['memType'],$row['staff'],$row['SSI'],$row['Purchases'],
				$row['NumberOfChecks'],$row['memCoupons'],$dbc->escape($memNum.' '.$lns[$i]),
				$row['Shown']);
			$test = $dbc->query($insQ);
			if ($test === False)
				$ret = "Error: Problem saving household members<br />";	
			$pn++;
		}
		return $ret;
	}
}

?>
