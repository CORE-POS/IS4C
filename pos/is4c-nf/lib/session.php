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

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

include_once($IS4C_PATH."ini.php");
if (!function_exists("pDataConnect")) include($IS4C_PATH."lib/connect.php");
if (!function_exists("loadglobalvalues")) include($IS4C_PATH."lib/loadconfig.php");
if (!function_exists("paycard_reset")) include($IS4C_PATH."lib/paycardLib.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

// initiate_session();

function initiate_session() {

	system_init();
	memberReset();
	transReset();
	printReset();
	paycard_reset();

	getsubtotals();
	loadglobalvalues();
	loaddata();
}

function system_init() {
	global $IS4C_LOCAL;

	//$IS4C_LOCAL->set("datetimestamp",strftime("%Y-%m-%m/%d/%y %T",time()));
	$IS4C_LOCAL->set("beep","noBeep");
	$IS4C_LOCAL->set("scan","scan");
	$IS4C_LOCAL->set("standalone",0);
	$IS4C_LOCAL->set("SNR",0);
	$IS4C_LOCAL->set("screset","staycool");
	$IS4C_LOCAL->set("currentid",1);
	$IS4C_LOCAL->set("currenttopid",1);
	$IS4C_LOCAL->set("training",0);
	$IS4C_LOCAL->set("adminRequest","");
	$IS4C_LOCAL->set("weight",0);
	$IS4C_LOCAL->set("scale",1);
	$IS4C_LOCAL->set("msg",0);
	$IS4C_LOCAL->set("plainmsg","");
	//$IS4C_LOCAL->set("alert","");
	$IS4C_LOCAL->set("away",0);
	$IS4C_LOCAL->set("waitforScale",0);
        $IS4C_LOCAL->set("ccRemoteServerUp",1);
	$IS4C_LOCAL->set("ccTermOut","idle");
	$IS4C_LOCAL->set("search_or_list",0);

	if($IS4C_LOCAL->get("CCintegrate") == 1){
	   testcc();
	}
}

function transReset() {
	global $IS4C_LOCAL;

	$IS4C_LOCAL->set("End",0);
	$IS4C_LOCAL->set("memberID","0");
	$IS4C_LOCAL->set("TaxExempt",0);
	$IS4C_LOCAL->set("fstaxable",0);
	$IS4C_LOCAL->set("yousaved",0);
	$IS4C_LOCAL->set("couldhavesaved",0);
	//$IS4C_LOCAL->set("void",0);
	$IS4C_LOCAL->set("voided",0);
	$IS4C_LOCAL->set("tare",0);
	$IS4C_LOCAL->set("tenderamt",0);
	$IS4C_LOCAL->set("change",0);
	$IS4C_LOCAL->set("transstatus","");
	$IS4C_LOCAL->set("ccTender",0);
	$IS4C_LOCAL->set("ccAmtEntered",0);
	$IS4C_LOCAL->set("ccAmt",0);
	$IS4C_LOCAL->set("TenderType","XX");				
	$IS4C_LOCAL->set("ChgName","Charge Account");			
	$IS4C_LOCAL->set("cashOverAmt",0);				
	$IS4C_LOCAL->set("chargetender",0);
	$IS4C_LOCAL->set("mirequested",0);
	$IS4C_LOCAL->set("toggletax",0);
	$IS4C_LOCAL->set("togglefoodstamp",0);
	$IS4C_LOCAL->set("toggleDiscountable",0);
	//$IS4C_LOCAL->set("ttlrequested",0);
	$IS4C_LOCAL->set("discounttype",0);
	$IS4C_LOCAL->set("discountable",0);
	$IS4C_LOCAL->set("refund",0);
	//$IS4C_LOCAL->set("istaxable",0);
	$IS4C_LOCAL->set("mfcoupon",0);
	$IS4C_LOCAL->set("casediscount",0);
	//$IS4C_LOCAL->set("ondiscount",0);
	$IS4C_LOCAL->set("multiple",0);
	$IS4C_LOCAL->set("quantity",0);
	$IS4C_LOCAL->set("nd",0); 			// negates default 10% discount at the charge book
	$IS4C_LOCAL->set("sc",0); 			// marks transaction as a staff charge at the charge book
	$IS4C_LOCAL->set("idSearch","");
	//$IS4C_LOCAL->set("repeat",0);
	$IS4C_LOCAL->set("strEntered","");
	$IS4C_LOCAL->set("strRemembered","");
	$IS4C_LOCAL->set("msgrepeat",0);		// when set to 1, pos2.php takes the previous strEntered
	$IS4C_LOCAL->set("boxMsg","");		
	$IS4C_LOCAL->set("itemPD",0); 		// Item percent discount for the charge book
	$IS4C_LOCAL->set("specials",0);
	$IS4C_LOCAL->set("ccSwipe","");
	$IS4C_LOCAL->set("ccName","");
	$IS4C_LOCAL->set("ccType","");
	$IS4C_LOCAL->set("troutd","");
	$IS4C_LOCAL->set("ouxWait",0);
	
	$IS4C_LOCAL->set("warned",0);
	$IS4C_LOCAL->set("warnBoxType","");
	$IS4C_LOCAL->set("requestType","");
}

function printReset() {
	global $IS4C_LOCAL;

	//$IS4C_LOCAL->set("franking",0);
	//$IS4C_LOCAL->set("noreceipt",0);
	$IS4C_LOCAL->set("receiptToggle",1);
	$IS4C_LOCAL->set("receiptType","");
	$IS4C_LOCAL->set("endorseType","");
	//$IS4C_LOCAL->set("kick",1);	

	$IS4C_LOCAL->set("autoReprint",0);
	$IS4C_LOCAL->set("reprintNameLookup",0);
}

function memberReset() {
	global $IS4C_LOCAL;

	$IS4C_LOCAL->set("memberID","0");
	$IS4C_LOCAL->set("isMember",0);
	$IS4C_LOCAL->set("isStaff",0);
	$IS4C_LOCAL->set("SSI",0);
	//$IS4C_LOCAL->set("discountcap",0);
	$IS4C_LOCAL->set("memMsg","");
	$IS4C_LOCAL->set("memType",0);
	$IS4C_LOCAL->set("balance",0);
	$IS4C_LOCAL->set("availBal",0);
	$IS4C_LOCAL->set("percentDiscount",0);

	$IS4C_LOCAL->set("ar_paid",0);
	$IS4C_LOCAL->set("inactMem",0);
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
	global $IS4C_LOCAL;
	
	$query_local = "select * from localtemptrans";
	
	// not used for anything - andy 4/12/07
	$query_product = "select * from products where upc='0000000000029'";

	$db_local = tDataConnect();
	$result_local = $db_local->query($query_local);
	$num_rows_local = $db_local->num_rows($result_local);

	if ($num_rows_local > 0) {
		$row_local = $db_local->fetch_array($result_local);
		
		if ($row_local["card_no"] && strlen($row_local["card_no"]) > 0) {
			$IS4C_LOCAL->set("memberID",$row_local["card_no"]);
		}
	}
	// moved, no need to stay open - andy 4/12/07
	$db_local->close();

	if ($IS4C_LOCAL->get("memberID") == "0") {
		// not used - andy 4/12/07
		$IS4C_LOCAL->set("percentDiscount",0);
		$IS4C_LOCAL->set("memType",0);
	}
	else {
		$query_member = "select CardNo,memType,Type,Discount,staff,SSI,
				MemDiscountLimit,blueLine,FirstName,LastName
				from custdata where CardNo = '".$IS4C_LOCAL->get("memberID")."'";
		$db_product = pDataConnect();
		$result = $db_product->query($query_member);
		if ($db_product->num_rows($result) > 0) {
			$row = $db_product->fetch_array($result);
			$IS4C_LOCAL->set("memMsg",blueLine($row));
			$IS4C_LOCAL->set("memType",$row["memType"]);
			$IS4C_LOCAL->set("percentDiscount",$row["Discount"]);

			if ($row["Type"] == "PC") $IS4C_LOCAL->set("isMember",1);
			else $IS4C_LOCAL->set("isMember",0);

			$IS4C_LOCAL->set("isStaff",$row["staff"]);
			$IS4C_LOCAL->set("SSI",$row["SSI"]);
			$IS4C_LOCAL->set("discountcap",$row["MemDiscountLimit"]);

			if ($IS4C_LOCAL->get("SSI") == 1) 
				$IS4C_LOCAL->set("memMsg",$IS4C_LOCAL->get("memMsg")." #");
		}
		// moved for proper scope - andy 4/12/07
		$db_product->close();
	}
}


?>
