<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

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

include_once($CORE_PATH."ini.php");
if (!function_exists("pDataConnect")) include($CORE_PATH."lib/connect.php");
if (!function_exists("loadglobalvalues")) include($CORE_PATH."lib/loadconfig.php");
if (!function_exists("paycard_reset")) include($CORE_PATH."cc-modules/lib/paycardLib.php");
if (!function_exists("term_object")) include($CORE_PATH."cc-modules/lib/term.php");
if (!isset($CORE_LOCAL)) include($CORE_PATH."lib/LocalStorage/conf.php");

/**
 @file
 @brief Setup session variables
 @depreacted see CoreState
*/

/**
  Populates $CORE_LOCAL with default values.
  Short-hand for calling every other function
  in this file. Normally called once on
  startup.
*/
function initiate_session() {
	system_init();
	memberReset();
	transReset();
	printReset();
	paycard_reset();

	getsubtotals();
	loadglobalvalues();
	loaddata();
	customreceipt();
}

/**
  Initialize system default values in
  $CORE_LOCAL. Variables defined here
  should always exist but won't be reset
  to these values on a regular basis.
*/
function system_init() {
	global $CORE_LOCAL;

	//$CORE_LOCAL->set("datetimestamp",strftime("%Y-%m-%m/%d/%y %T",time()));
	$CORE_LOCAL->set("beep","noBeep");
	$CORE_LOCAL->set("scan","scan");
	$CORE_LOCAL->set("standalone",0);
	$CORE_LOCAL->set("SNR",0);
	$CORE_LOCAL->set("screset","staycool");
	$CORE_LOCAL->set("currentid",1);
	$CORE_LOCAL->set("currenttopid",1);
	$CORE_LOCAL->set("training",0);
	$CORE_LOCAL->set("adminRequest","");
	$CORE_LOCAL->set("weight",0);
	$CORE_LOCAL->set("scale",1);
	$CORE_LOCAL->set("msg",0);
	$CORE_LOCAL->set("plainmsg","");
	//$CORE_LOCAL->set("alert","");
	$CORE_LOCAL->set("away",0);
	$CORE_LOCAL->set("waitforScale",0);
        $CORE_LOCAL->set("ccRemoteServerUp",1);
	$CORE_LOCAL->set("search_or_list",0);
	$CORE_LOCAL->set("ccTermOut","idle");
	$td = term_object();
	if (is_object($td))
		$td->WriteToScale("reset");
}

/**
  Initialize transaction variable in $CORE_LOCAL.
  This function is called after the end of every
  transaction so these values will be the
  the defaults every time.
*/
function transReset() {
	global $CORE_LOCAL;

	$CORE_LOCAL->set("End",0);
	$CORE_LOCAL->set("memberID","0");
	$CORE_LOCAL->set("TaxExempt",0);
	$CORE_LOCAL->set("fstaxable",0);
	$CORE_LOCAL->set("yousaved",0);
	$CORE_LOCAL->set("couldhavesaved",0);
	//$CORE_LOCAL->set("void",0);
	$CORE_LOCAL->set("voided",0);
	$CORE_LOCAL->set("voidTTL",0);
	$CORE_LOCAL->set("tare",0);
	$CORE_LOCAL->set("tenderamt",0);
	$CORE_LOCAL->set("change",0);
	$CORE_LOCAL->set("transstatus","");
	$CORE_LOCAL->set("ccTender",0);
	$CORE_LOCAL->set("ccAmtEntered",0);
	$CORE_LOCAL->set("ccAmt",0);
	$CORE_LOCAL->set("TenderType","XX");				
	$CORE_LOCAL->set("ChgName","Charge Account");			
	$CORE_LOCAL->set("cashOverAmt",0);				
	$CORE_LOCAL->set("chargetender",0);
	$CORE_LOCAL->set("mirequested",0);
	$CORE_LOCAL->set("toggletax",0);
	$CORE_LOCAL->set("togglefoodstamp",0);
	$CORE_LOCAL->set("toggleDiscountable",0);
	//$CORE_LOCAL->set("ttlrequested",0);
	$CORE_LOCAL->set("discounttype",0);
	$CORE_LOCAL->set("discountable",0);
	$CORE_LOCAL->set("refund",0);
	//$CORE_LOCAL->set("istaxable",0);
	$CORE_LOCAL->set("mfcoupon",0);
	$CORE_LOCAL->set("casediscount",0);
	//$CORE_LOCAL->set("ondiscount",0);
	$CORE_LOCAL->set("multiple",0);
	$CORE_LOCAL->set("quantity",0);
	$CORE_LOCAL->set("nd",0); 			// negates default 10% discount at the charge book
	$CORE_LOCAL->set("sc",0); 			// marks transaction as a staff charge at the charge book
	$CORE_LOCAL->set("idSearch","");
	//$CORE_LOCAL->set("repeat",0);
	$CORE_LOCAL->set("strEntered","");
	$CORE_LOCAL->set("strRemembered","");
	$CORE_LOCAL->set("msgrepeat",0);		// when set to 1, pos2.php takes the previous strEntered
	$CORE_LOCAL->set("boxMsg","");		
	$CORE_LOCAL->set("itemPD",0); 		// Item percent discount for the charge book
	$CORE_LOCAL->set("specials",0);
	$CORE_LOCAL->set("ccSwipe","");
	$CORE_LOCAL->set("ccName","");
	$CORE_LOCAL->set("ccType","");
	$CORE_LOCAL->set("troutd","");
	$CORE_LOCAL->set("ouxWait",0);
	
	$CORE_LOCAL->set("warned",0);
	$CORE_LOCAL->set("warnBoxType","");
	$CORE_LOCAL->set("requestType","");
}

/**
  Initialize print related variables in $CORE_LOCAL.
  This function is called after the end of
  every transaction.
*/
function printReset() {
	global $CORE_LOCAL;

	//$CORE_LOCAL->set("franking",0);
	//$CORE_LOCAL->set("noreceipt",0);
	$CORE_LOCAL->set("receiptToggle",1);
	$CORE_LOCAL->set("receiptType","");
	$CORE_LOCAL->set("endorseType","");
	//$CORE_LOCAL->set("kick",1);	

	$CORE_LOCAL->set("autoReprint",0);
	$CORE_LOCAL->set("reprintNameLookup",0);
}

/**
  Initialize member related variables in $CORE_LOCAL.
  This function is called after the end of
  every transaction.
*/
function memberReset() {
	global $CORE_LOCAL;

	$CORE_LOCAL->set("memberID","0");
	$CORE_LOCAL->set("isMember",0);
	$CORE_LOCAL->set("isStaff",0);
	$CORE_LOCAL->set("SSI",0);
	//$CORE_LOCAL->set("discountcap",0);
	$CORE_LOCAL->set("memMsg","");
	$CORE_LOCAL->set("memType",0);
	$CORE_LOCAL->set("balance",0);
	$CORE_LOCAL->set("availBal",0);
	$CORE_LOCAL->set("percentDiscount",0);

	$CORE_LOCAL->set("ar_paid",0);
	$CORE_LOCAL->set("inactMem",0);
	$CORE_LOCAL->set("memAge",date('Ymd'));
}

/**
  Get member information line for a given member
  @param $row a record from custdata
  @return string
  @deprecated
  Just define blueLine in custdata.
*/
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

/**
  If there are records in localtemptrans, get the 
  member number and initialize $CORE_LOCAL member
  variables.

  The only time this function does anything is
  in crash recovery - if a browser is closed and
  re-opened or the computer is rebooted in the
  middle of a transaction.
*/
function loaddata() {
	global $CORE_LOCAL;
	
	$query_local = "select card_no from localtemptrans";
	
	$db_local = tDataConnect();
	$result_local = $db_local->query($query_local);
	$num_rows_local = $db_local->num_rows($result_local);

	if ($num_rows_local > 0) {
		$row_local = $db_local->fetch_array($result_local);
		
		if ($row_local["card_no"] && strlen($row_local["card_no"]) > 0) {
			$CORE_LOCAL->set("memberID",$row_local["card_no"]);
		}
	}
	// moved, no need to stay open - andy 4/12/07
	$db_local->close();

	if ($CORE_LOCAL->get("memberID") == "0") {
		// not used - andy 4/12/07
		$CORE_LOCAL->set("percentDiscount",0);
		$CORE_LOCAL->set("memType",0);
	}
	else {
		$query_member = "select CardNo,memType,Type,Discount,staff,SSI,
				MemDiscountLimit,blueLine,FirstName,LastName
				from custdata where CardNo = '".$CORE_LOCAL->get("memberID")."'";
		$db_product = pDataConnect();
		$result = $db_product->query($query_member);
		if ($db_product->num_rows($result) > 0) {
			$row = $db_product->fetch_array($result);
			$CORE_LOCAL->set("memMsg",blueLine($row));
			$CORE_LOCAL->set("memType",$row["memType"]);
			$CORE_LOCAL->set("percentDiscount",$row["Discount"]);

			if ($row["Type"] == "PC") $CORE_LOCAL->set("isMember",1);
			else $CORE_LOCAL->set("isMember",0);

			$CORE_LOCAL->set("isStaff",$row["staff"]);
			$CORE_LOCAL->set("SSI",$row["SSI"]);
			$CORE_LOCAL->set("discountcap",$row["MemDiscountLimit"]);

			if ($CORE_LOCAL->get("SSI") == 1) 
				$CORE_LOCAL->set("memMsg",$CORE_LOCAL->get("memMsg")." #");
		}
		// moved for proper scope - andy 4/12/07
		$db_product->close();
	}
}

/** 
   Fetch text fields from the customReceipt table
   These fields are used for various messages that
   invariably must be customized at every store.
 */
function customreceipt(){
	global $CORE_LOCAL;

	$db = pDataConnect(); 
	$headerQ = "select text,type,seq from customReceipt order by seq";
	$headerR = $db->query($headerQ);
	$counts = array();
	while($headerW = $db->fetch_row($headerR)){
		$typeStr = $headerW['type'];
		$numeral = $headerW['seq']+1;
		$text = $headerW['text'];
		
		// translation for really old data
		if (strtolower($typeStr)=="header")
			$typeStr = "receiptHeader";
		elseif(strtolower($typeStr)=="footer")
			$typeStr = "receiptFooter";

		$CORE_LOCAL->set($typeStr.$numeral,$text);

		if (!isset($counts[$typeStr]))
			$counts[$typeStr] = 1;
		else
			$counts[$typeStr]++;
	}
	
	foreach($counts as $key => $num){
		$CORE_LOCAL->set($key."Count",$num);
	}

	$db->db_close();
}

?>
