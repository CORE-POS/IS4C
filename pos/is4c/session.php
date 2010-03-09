<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

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

include_once("ini/ini.php");
if (!function_exists("pDataConnect")) include("connect.php");
if (!function_exists("tDataConnect")) include("connect.php");
if (!function_exists("loadglobalvalues")) include("loadconfig.php");
if (!function_exists("getsubtotals")) include("connect.php");

// initiate_session();

function initiate_session() {

	// session_start();

	system_init();
	memberReset();
	transReset();
	printReset();

	getsubtotals();
	loadglobalvalues();
	loaddata();
}

function system_init() {

	$_SESSION["datetimestamp"] = strftime("%Y-%m-%m/%d/%y %T",time());
	$_SESSION["beep"] = "noBeep";
	$_SESSION["scan"] = "scan";
	$_SESSION["standalone"] = 0;
	$_SESSION["SNR"] = 0;
	$_SESSION["screset"] = "staycool";
	$_SESSION["currentid"] = 1;
	$_SESSION["currenttopid"] = 1;
	$_SESSION["test"] = "";
	$_SESSION["training"] = 0;
	$_SESSION["weight"] = 0;
	$_SESSION["scale"] = 1;
	$_SESSION["msg"] = 0;
	$_SESSION["plainmsg"] = "";
	$_SESSION["alert"] = "";
	$_SESSION["productsearch"] = "";
	$_SESSION["away"] = 0;
	$_SESSION["waitforScale"] = 0;
	$_SESSION["locked"] = 0;
	$_SESSION["lastscale"] = "S";
	$_SESSION["endofshift"] = 0;
}

function transReset() {

	$_SESSION["End"] = 0;
	$_SESSION["memberID"] = "0";
	$_SESSION["TaxExempt"] = 0;
	$_SESSION["fstaxable"] = 0;
	$_SESSION["fstendered"] = 0;
	$_SESSION["yousaved"] = 0;
	$_SESSION["couldhavesaved"] = 0;
	$_SESSION["void"] = 0;
	$_SESSION["voided"] = 0;
	$_SESSION["tare"] = 0;
	$_SESSION["tenderamt"] = 0;
	$_SESSION["change"] = 0;
	$_SESSION["transstatus"] = "";
	$_SESSION["ccTender"] = 0;
	$_SESSION["ccAmtEntered"] = 0;
	$_SESSION["ccAmt"] = 0;
	$_SESSION["TenderType"] = "XX";				
	$_SESSION["ChgName"] = "Charge Account";			
	$_SESSION["cashOverAmt"] = 0;				
	$_SESSION["chargetender"] = 0;
	$_SESSION["mirequested"] = 0;
	$_SESSION["toggletax"] = 0;
	$_SESSION["togglefoodstamp"] = 0;
	$_SESSION["toggleDiscountable"] = 0;
	$_SESSION["ttlrequested"] = 0;
	$_SESSION["discounttype"] = 0;
	$_SESSION["discountable"] = 0;
	$_SESSION["refund"] = 0;
	$_SESSION["istaxable"] = 0;
	$_SESSION["mfcoupon"] = 0;
	$_SESSION["casediscount"] = 0;
	$_SESSION["ondiscount"] = 0;
	$_SESSION["multiple"] = 0;
	$_SESSION["quantity"] = 0;
	$_SESSION["scAmtDue"] = 0;
	$_SESSION["nd"] = 0; 			// negates default 10% discount at the charge book
	$_SESSION["sc"] = 0; 			// marks transaction as a staff charge at the charge book
	$_SESSION["idSearch"] = "";
	$_SESSION["repeat"] = 0;
	$_SESSION["strEntered"] = "";
	$_SESSION["strRemembered"] = "";
	$_SESSION["msgrepeat"] = 0;		// when set to 1, pos2.php takes the previous strEntered
	$_SESSION["boxMsg"] = "";		
	$_SESSION["itemPD"] = 0; 		// Item percent discount for the charge book
	$_SESSION["itemDiscount"] = 0;  	// Item percent discount, general.
	$_SESSION["specials"] = 0;
	$_SESSION["msgrepeat"] = 0;
	$_SESSION["ccSwipe"] = "";
	$_SESSION["ccName"] = "";
	$_SESSION["ccType"] = "";
	$_SESSION["troutd"] = "";
	$_SESSION["ouxWait"] = 0;
	$_SESSION["unlocked"] = 0;



}

function printReset() {

	$_SESSION["franking"] = 0;
	$_SESSION["noreceipt"] = 0;
	$_SESSION["receiptToggle"] = 1;
	$_SESSION["receiptType"] = "";
	$_SESSION["endorseType"] = "";
	$_SESSION["kick"] = 1;	
}

function memberReset() {

	$_SESSION["memberID"] = "0";
	$_SESSION["isMember"] = 0;
	$_SESSION["isStaff"] = 0;
	$_SESSION["SSI"] = 0;
	$_SESSION["discountcap"] = 0;
	$_SESSION["memMsg"] = "";
	$_SESSION["memType"] = 0;
	$_SESSION["balance"] = 0;
	$_SESSION["availBal"] = 0;
	$_SESSION["chargeOk"] = 0;
	$_SESSION["memID"] = 0;
	$_SESSION["volunteerDiscount"] = 0;
	$_SESSION["togglePatronage"] = 0;			// for patronage refund tracking module 	~joel 2006-12-19
	$_SESSION["trackPatronage"] = 0;			// for patronage refund tracking module 	~joel 2006-12-27
}

function blueLine($row) {
	$status = array('Non-Owner', 'Shareholder', 'Subscriber', 'Inactive', 'Refund', 'On Hold', 'Sister Org.', 'Other Co-ops');
	if ($row["blueLine"]) {			// custom blueLine as defined by db
		return $row["blueLine"];
	} elseif (isset($row["blueLine"])) {	// 0 - default blueLine with out name
		return '#'.$row['CardNo'].' - '.$row['Discount'].'% - '.$status[$row['memType']];
	} else {				// NULL - default blueLine including name
		return '#'.$row['CardNo'].' - '.$status[$row['memType']].': '.$row['FirstName'].' '.$row['LastName'];
	}
}

function loaddata() {
	$query_local = "select * from localtemptrans";
	$query_product = "select * from products where upc='0000000000029'";

	$db_product = pDataConnect();
	$result_pro = sql_query($query_product, $db_product);

	$db_local = tDataConnect();
	$result_local = sql_query($query_local, $db_local);
	$num_rows_local = sql_num_rows($result_local);

	if ($num_rows_local > 0) {
		$row_local = sql_fetch_array($result_local);
		
		if ($row_local["card_no"] && strlen($row_local["card_no"]) > 0) {
			$_SESSION["memberID"] = $row_local["card_no"];
		}
	}

	if ($_SESSION["memberID"] == "0") {
		$query_member = "select * from custdata where CardNo = '205203'";
		$db_product = pDataConnect();
		sql_query($query_member, $db_product);
		$_SESSION["memType"] = 0;
		$_SESSION["percentDiscount"] = 0;
	}
	else {
		$query_member = "select * from custdata where CardNo = '".$_SESSION["memberID"]."'";
		$db_product = pDataConnect();
		$result = sql_query($query_member, $db_product);
		if (sql_num_rows($result) > 0) {
			$row = sql_fetch_array($result);
			$_SESSION["memMsg"] = $row["blueLine"];
			$_SESSION["memType"] = $row["memType"];
			$_SESSION["percentDiscount"] = $row["Discount"];

			if ($row["Type"] == "PC") $_SESSION["isMember"] = 1;
			else $_SESSION["isMember"] = 0;

			$_SESSION["isStaff"] = $row["staff"];
			$_SESSION["SSI"] = $row["SSI"];
			$_SESSION["discountcap"] = $row["MemDiscountLimit"];

			if ($_SESSION["SSI"] == 1) $_SESSION["memMsg"] .= " #";
		}
	}

	sql_close($db_local);
	sql_close($db_product);
}


?>
