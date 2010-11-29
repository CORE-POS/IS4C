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

if (!function_exists("addItem")) include($IS4C_PATH."lib/additem.php");
if (!function_exists("truncate2")) include_once($IS4C_PATH."lib/lib.php");
if (!function_exists("lastpage")) include($IS4C_PATH."lib/listitems.php");
if (!function_exists("paycard_reset")) include($IS4C_PATH."lib/paycardLib.php");
if (!function_exists("blueLine")) include($IS4C_PATH."lib/session.php");
if (!function_exists("boxMsgscreen")) include($IS4C_PATH."lib/clientscripts.php");

function clearMember(){
	global $IS4C_LOCAL;

	memberReset();
	$db = tDataConnect();
	$db->query("UPDATE localtemptrans SET card_no=0,percentDiscount=NULL");
	$IS4C_LOCAL->set("ttlflag",0);	
}

function memberID($member_number) {
	global $IS4C_LOCAL,$IS4C_PATH;

	$query = "select CardNo,personNum,LastName,FirstName,CashBack,Balance,Discount,
		MemDiscountLimit,ChargeOk,WriteChecks,StoreCoupons,Type,memType,staff,
		SSI,Purchases,NumberOfChecks,memCoupons,blueLine,Shown,id from custdata 
		where CardNo = '".$member_number."'";

	$ret = array(
		"main_frame"=>false,
		"output"=>"",
		"target"=>".baseHeight",
		"redraw_footer"=>false
	);
	
	$db = pDataConnect();
	$result = $db->query($query);

	$num_rows = $db->num_rows($result);

	if ($num_rows == 1 && 
		($member_number == $IS4C_LOCAL->get("defaultNonMem")
		|| ($member_number == 5607 && 
		$IS4C_LOCAL->get("requestType") == "member gift"))) {
           	$row = $db->fetch_array($result);
	     	setMember($row["CardNo"], $row["personNum"],$row);
		$ret['output'] = lastpage();
	} 

	// special hard coding for member 5607 WFC 
	// needs to go away
	if ($member_number == "5607"){
		if ($IS4C_LOCAL->get("requestType") == ""){
			$IS4C_LOCAL->set("requestType","member gift");
			$IS4C_LOCAL->set("requestMsg","Card for which member?");
			$ret['main_frame'] = $IS4C_PATH."gui-modules/requestInfo.php";
			$IS4C_LOCAL->set("strEntered","5607id");
		}
		else if ($IS4C_LOCAL->get("requestType") == "member gift"){
			addcomment("CARD FOR #".$IS4C_LOCAL->get("requestMsg"));
			$IS4C_LOCAL->set("requestType","");
		}
	}

	$IS4C_LOCAL->set("idSearch",$member_number);
	$IS4C_LOCAL->set("memberID","0");
	$IS4C_LOCAL->set("memType",0);
	$IS4C_LOCAL->set("percentDiscount",0);
	$IS4C_LOCAL->set("memMsg","");

	if ($ret['main_frame'] === false)
		$ret['main_frame'] = $IS4C_PATH."gui-modules/memlist.php";

	return $ret;
}

//-------------------------------------------------

function setMember($member, $personNumber, $row) {
	global $IS4C_LOCAL;

	$conn = pDataConnect();

	$IS4C_LOCAL->set("memMsg",blueLine($row));
	$chargeOk = chargeOk();
	if ($IS4C_LOCAL->get("balance") != 0 && $member != $IS4C_LOCAL->get("defaultNonMem"))
	      $IS4C_LOCAL->set("memMsg",$IS4C_LOCAL->get("memMsg")." AR");
      
	$IS4C_LOCAL->set("memberID",$member);
	$IS4C_LOCAL->set("memType",$row["memType"]);
	$IS4C_LOCAL->set("lname",$row["LastName"]);
	$IS4C_LOCAL->set("fname",$row["FirstName"]);
	$IS4C_LOCAL->set("Type",$row["Type"]);
	$IS4C_LOCAL->set("percentDiscount",$row["Discount"]);

	$IS4C_LOCAL->set('inactMem',0);
	if ($IS4C_LOCAL->get("Type") == "PC") {
		$IS4C_LOCAL->set("isMember",1);
	} else {
           	$IS4C_LOCAL->set("isMember",0);
		if ($IS4C_LOCAL->get('Type') != 'REG')
			$IS4C_LOCAL->set('inactMem',1);
	}

	$IS4C_LOCAL->set("isStaff",$row["staff"]);
	$IS4C_LOCAL->set("SSI",$row["SSI"]);

	if ($IS4C_LOCAL->get("SSI") == 1) 
		$IS4C_LOCAL->set("memMsg",$IS4C_LOCAL->get("memMsg")." #");

	$conn2 = tDataConnect();
	$memquery = "update localtemptrans set card_no = '".$member."',
	      				memType = ".$IS4C_LOCAL->get("memType").",
					staff = ".$IS4C_LOCAL->get("isStaff");
	if ($IS4C_LOCAL->get("DBMS") == "mssql" && $IS4C_LOCAL->get("store") == "wfc")
		$memquery = str_replace("staff","isStaff",$memquery);

	if ($IS4C_LOCAL->get("store") == "wedge") {
		if ($IS4C_LOCAL->get("isMember") == 0 && $IS4C_LOCAL->get("percentDiscount") == 10) {
			$memquery .= " , percentDiscount = 0 ";
		}
		elseif ($IS4C_LOCAL->get("isStaff") != 1 && $IS4C_LOCAL->get("percentDiscount") == 15) {
			$memquery .= " , percentDiscount = 0 ";
		}
	}

	if ($IS4C_LOCAL->get("discountEnforced") != 0) {
		if ($IS4C_LOCAL->get("percentDiscount") > 0) {
                   discountnotify($IS4C_LOCAL->get("percentDiscount"));
		}
		$memquery .= " , percentDiscount = ".$IS4C_LOCAL->get("percentDiscount")." ";
	}
	else if ($IS4C_LOCAL->get("discountEnforced") == 0 && $IS4C_LOCAL->get("tenderTotal") == 0) {
		$memquery .= " , percentDiscount = 0 ";
	}

	$conn2->query($memquery);

	if ($IS4C_LOCAL->get("isStaff") == 0) {
		$IS4C_LOCAL->set("staffSpecial",0);
	}

	if ($IS4C_LOCAL->get("unlock") != 1) {
		ttl();
	}
	$IS4C_LOCAL->set("unlock",0);

	if ($IS4C_LOCAL->get("mirequested") == 1) {
		$IS4C_LOCAL->set("mirequested",0);
		$IS4C_LOCAL->set("runningTotal",$IS4C_LOCAL->get("amtdue"));
		tender("MI", $IS4C_LOCAL->get("runningTotal") * 100);
	}

	//return check_unpaid_ar($member);
}

function check_unpaid_ar($cardno){
	global $IS4C_LOCAL;

	// only attempt if server is available
	// and not the default non-member
	if ($cardno == $IS4C_LOCAL->get("defaultNonMem")) return False;
	if ($IS4C_LOCAL->get("standalone") == 1) return False;
	if ($IS4C_LOCAL->get("balance") == 0) return False;

	$db = mDataConnect();

	if (!$db->table_exists("unpaid_ar_today")) return False;

	$query = "SELECT old_balance,recent_payments FROM unpaid_ar_today
		WHERE card_no = $cardno";
	$result = $db->query($query);

	// should always be a row, but just in case
	if ($db->num_rows($result) == 0) return False;
	$row = $db->fetch_row($result);

	$bal = $row["old_balance"];
	$paid = $row["recent_payments"];
	if ($IS4C_LOCAL->get("memChargeTotal") > 0)
		$paid += $IS4C_LOCAL->get("memChargeTotal");
	
	if ($bal <= 0) return False;
	if ($paid >= $bal) return False;

	// only case where customer prompt should appear
	if ($bal > 0 && $paid < $bal){
		$IS4C_LOCAL->set("old_ar_balance",$bal - $paid);
		return True;
	}

	// just in case i forgot anything...
	return False;
}


//-------------------------------------------------

function checkstatus($num) {
	global $IS4C_LOCAL;

	if (!$num) {
		$num = 0;
	}

	$query = "select voided,unitPrice,discountable,
		discounttype,trans_status
		from localtemptrans where trans_id = ".$num;

	$db = tDataConnect();
	$result = $db->query($query);


	$num_rows = $db->num_rows($result);

	if ($num_rows > 0) {
		$row = $db->fetch_array($result);
		$IS4C_LOCAL->set("voided",$row["voided"]);
		$IS4C_LOCAL->set("scaleprice",$row["unitPrice"]);
		$IS4C_LOCAL->set("discountable",$row["discountable"]);
		$IS4C_LOCAL->set("discounttype",$row["discounttype"]);
		$IS4C_LOCAL->set("caseprice",$row["unitPrice"]);

		if ($row["trans_status"] == "V") {
			$IS4C_LOCAL->set("transstatus","V");
		}

// added by apbw 6/04/05 to correct voiding of refunded items 

		if ($row["trans_status"] == "R") {
			$IS4C_LOCAL->set("refund",1);
			$IS4C_LOCAL->set("autoReprint",1);
		}

	}

	$db->close();
}

//---------------------------------------------------

function tender($right, $strl) {
	global $IS4C_LOCAL;
	$tender_upc = "";

	$ret = array('main_frame'=>false,
		'redraw_footer'=>false,
		'target'=>'.baseHeight',
		'output'=>"");

	if ($IS4C_LOCAL->get("LastID") == 0){
		$ret['output'] = boxMsg("No transaction in progress");
		return $ret;
	}
	elseif ($strl > 999999){
	       $ret['output'] =	xboxMsg("tender amount of ".truncate2($strl/100)."<BR>exceeds allowable limit");
	       return $ret;
	}
	elseif ($right == "WT"){
	       $ret['output'] =	xboxMsg("WIC tender not applicable");
	       return $ret;
	}
	elseif ($right == "CK" && $IS4C_LOCAL->get("ttlflag") == 1 && ($IS4C_LOCAL->get("isMember") != 0 || $IS4C_LOCAL->get("isStaff") != 0) && (($strl/100 - $IS4C_LOCAL->get("amtdue") - 0.005) > $IS4C_LOCAL->get("dollarOver")) && ($IS4C_LOCAL->get("cashOverLimit") == 1)){
		$ret['output'] = boxMsg("member or staff check tender cannot 
			exceed total purchase by over $".$IS4C_LOCAL->get("dollarOver"));
		return $ret;
	}
	elseif ((($right == "CC" || $right == "TB" || $right == "GD") && $strl/100 > ($IS4C_LOCAL->get("amtdue") + 0.005)) && $IS4C_LOCAL->get("amtdue") >= 0){ 
		$ret['output'] = xboxMsg("tender cannot exceed purchase amount");
		return $ret;
	}
	elseif($right == "EC" && $strl/100 > $IS4C_LOCAL->get("amtdue")){
		$ret['output'] = xboxMsg("no cash back with EBT cash tender");
		return $ret;
	}
	elseif($right == "CK" && $IS4C_LOCAL->get("ttlflag") == 1 && $IS4C_LOCAL->get("isMember") == 0 and $IS4C_LOCAL->get("isStaff") == 0 && ($strl/100 - $IS4C_LOCAL->get("amtdue") - 0.005) > 5){ 
		$ret['output'] = xboxMsg("non-member check tender cannot exceed total purchase by over $5.00");
		return $ret;
	}

	getsubtotals();

	if ($IS4C_LOCAL->get("ttlflag") == 1 && ($right == "CX" || $right == "MI")) {			// added ttlflag on 2/28/05 apbw 

		$charge_ok = chargeOk();
		if ($right == "CX" && $charge_ok == 1 && strlen($IS4C_LOCAL->get("memberID")) == 5 && substr($IS4C_LOCAL->get("memberID"), 0, 1) == "5") $charge_ok = 1;
		elseif ($right == "MI" && $charge_ok == 1) $charge_ok = 1;
		else $charge_ok = 0;
	}

	/* when processing as strings, weird things happen
	 * in excess of 1000, so use floating point */
	$strl .= ""; // force type to string
	$mult = 1;
	if ($strl[0] == "-"){
		$mult = -1;
		$strl = substr($strl,1,strlen($strl));
	}
	$dollars = (int)substr($strl,0,strlen($strl)-2);
	$cents = ((int)substr($strl,-2))/100.0;
	$strl = (double)($dollars+round($cents,2));
	$strl *= $mult;

	if ($IS4C_LOCAL->get("ttlflag") == 0) {
		$ret['output'] = boxMsg("transaction must be totaled before tender can be accepted");
		return $ret;
	}
	elseif (($right == "FS" || $right == "EF") && $IS4C_LOCAL->get("fntlflag") == 0) {
		$ret['output'] = boxMsg("eligble amount must be totaled before foodstamp tender can be accepted");
		return $ret;
	}
	elseif ($right == "EF" && $IS4C_LOCAL->get("fntlflag") == 1 && $IS4C_LOCAL->get("fsEligible") + 10 <= $strl) {
		$ret['output'] = xboxMsg("Foodstamp tender cannot exceed elible amount by pver $10.00");
		return $ret;
	}
	elseif ($right == "CX" && $charge_ok == 0) {
		$ret['output'] = xboxMsg("member ".$IS4C_LOCAL->get("memberID")."<BR>is not authorized<BR>to make corporate charges");
		return $ret;
	}
	//alert customer that charge exceeds avail balance
	elseif ($right == "MI" && $charge_ok == 0 && $IS4C_LOCAL->get("availBal") < 0) {
		$ret['output'] = xboxMsg("member ".$IS4C_LOCAL->get("memberID")."<BR> has $" . $IS4C_LOCAL->get("availBal") . " available.");
		return $ret;
	}
	elseif ($right == "MI" && $charge_ok == 1 && $IS4C_LOCAL->get("availBal") < 0) {
		$ret['output'] = xboxMsg("member ".$IS4C_LOCAL->get("memberID")."<BR>is overlimit");
		return $ret;
	}
	elseif ($right == "MI" && $charge_ok == 0) {
		$ret['output'] = xboxMsg("member ".$IS4C_LOCAL->get("memberID")."<BR>is not authorized to make employee charges");
		return $ret;
	}
	elseif ($right == "MI" && $charge_ok == 1 && ($IS4C_LOCAL->get("availBal") + $IS4C_LOCAL->get("memChargeTotal") - $strl) < 0) {
		$ret['output'] = xboxMsg("member ".$IS4C_LOCAL->get("memberID")."<br> bhas exceeded charge limit");
		return $ret;
	}
	elseif ($right == "MI" && $charge_ok == 1 && (ABS($IS4C_LOCAL->get("memChargeTotal"))+ $strl) >= ($IS4C_LOCAL->get("availBal") + 0.005) && $IS4C_LOCAL->get("store")=="WFC") {
		$memChargeRemain = $IS4C_LOCAL->get("availBal");
		$memChargeCommitted = $memChargeRemain + $IS4C_LOCAL->get("memChargeTotal");
		$ret['output'] = xboxMsg("available balance for charge <br>is only $" .$memChargeCommitted. ".<br><b><font size = 5>$" . number_format($memChargeRemain,2) . "</font></b><br>may still be used on this purchase.");
		return $ret;
	}
	elseif(($right == "MI" || $right == "CX") && truncate2($IS4C_LOCAL->get("amtdue")) < truncate2($strl)) {
		$ret['output'] = xboxMsg("charge tender exceeds purchase amount");
		return $ret;
	}

	$db = pDataConnect();
	$query = "select TenderID,TenderCode,TenderName,TenderType,
		ChangeMessage,MinAmount,MaxAmount,MaxRefund from 
		tenders where tendercode = '".$right."'";
	$result = $db->query($query);

	$num_rows = $db->num_rows($result);

	if ($num_rows == 0) {
		$ret['output'] = inputUnknown();
		return $ret;
	}

	$row = $db->fetch_array($result);
	$tender_code = $right;
	$tendered = -1 * $strl;
				
	if($tender_code == "CC" && $IS4C_LOCAL->get("CCintegrate") == 1) {
		$tender_upc = $IS4C_LOCAL->get("troutd");
	}
	$tender_desc = $row["TenderName"];				
	$IS4C_LOCAL->set("tenderamt",$strl);
	$unit_price = 0;

	if ($tender_code == "FS") {
		$IS4C_LOCAL->set("boxMsg","WFC no longer excepts paper foods stamps. Please choose a different tender type");
		$ret['main_frame'] = '/gui-modules/boxMsg2.php';
		return $ret;
	}
	elseif ($tender_code == "CP" && $strl > $row["MaxAmount"] && $IS4C_LOCAL->get("msgrepeat") == 0){
		$IS4C_LOCAL->set("boxMsg","$".$strl." is greater than coupon limit<P>"
		."<FONT size='-1'>[clear] to cancel, [enter] to proceed</FONT>");
		$ret['main_frame'] = '/gui-modules/boxMsg2.php';
		return $ret;
	}
	elseif ($strl > $row["MaxAmount"] && $IS4C_LOCAL->get("msgrepeat") == 0){
		$IS4C_LOCAL->set("boxMsg","$".$strl." is greater than tender limit "
		."for ".$row['TenderName']."<p>"
		."<FONT size='-1'>[clear] to cancel, [enter] to proceed</FONT>");
		$ret['main_frame'] = '/gui-modules/boxMsg2.php';
		return $ret;
	}
	elseif ($right == "GD" || $right == "TC"){
		$IS4C_LOCAL->set("autoReprint",1);
	}

	if ($strl - $IS4C_LOCAL->get("amtdue") > 0) {
		$IS4C_LOCAL->set("change",$strl - $IS4C_LOCAL->get("amtdue"));
	}
	else {
		$IS4C_LOCAL->set("change",0);
	}

	$ref = trim($IS4C_LOCAL->get("CashierNo"))."-"
		.trim($IS4C_LOCAL->get("laneno"))."-"
		.trim($IS4C_LOCAL->get("transno"));
	if ($right == "CK" && $IS4C_LOCAL->get("msgrepeat") == 0) {
		$msg = "<BR>insert check</B><BR>press [enter] to endorse<P><FONT size='-1'>[clear] to cancel</FONT>";
		if ($IS4C_LOCAL->get("LastEquityReference") == $ref){
			$msg .= "<div style=\"background:#993300;color:#ffffff;
				margin:3px;padding: 3px;\">
				There was an equity sale on this transaction. Did it get
				endorsed yet?</div>";
		}
		$IS4C_LOCAL->set("boxMsg",$msg);
		$IS4C_LOCAL->set("endorseType","check");
		$ret['main_frame'] = '/gui-modules/boxMsg2.php';
		return $ret;
	}
	elseif ($right == "TV" && $IS4C_LOCAL->get("msgrepeat") == 0) {
		$msg = "<BR>insert travelers check</B><BR>press [enter] to endorse<P><FONT size='-1'>[clear] to cancel</FONT>";
		if ($IS4C_LOCAL->get("LastEquityReference") == $ref){
			$msg .= "<div style=\"background:#993300;color:#ffffff;
				margin:3px;padding: 3px;\">
				There was an equity sale on this transaction. Did it get
				endorsed yet?</div>";
		}
		$IS4C_LOCAL->set("boxMsg",$msg);
		$IS4C_LOCAL->set("endorseType","check");
		$ret['main_frame'] = '/gui-modules/boxMsg2.php';
		return $ret;
	}
	elseif ($right == "RC" && $IS4C_LOCAL->get("msgrepeat") == 0) {
		$msg = "<BR>insert rebate check</B><BR>press [enter] to endorse<P><FONT size='-1'>[clear] to cancel</FONT>";
		if ($IS4C_LOCAL->get("LastEquityReference") == $ref){
			$msg .= "<div style=\"background:#993300;color:#ffffff;
				margin:3px;padding: 3px;\">
				There was an equity sale on this transaction. Did it get
				endorsed yet?</div>";
		}
		$IS4C_LOCAL->set("boxMsg",$msg);
		$IS4C_LOCAL->set("endorseType","check");
		$ret['main_frame'] = '/gui-modules/boxMsg2.php';
		return $ret;
	}
	elseif ($right == "TC" && $IS4C_LOCAL->get("msgrepeat") == 0) {
		$IS4C_LOCAL->set("boxMsg","<B> insert gift certificate<B><BR>press [enter] to endorse<P><FONT size='-1'>[clear] to cancel</FONT>");
		$IS4C_LOCAL->set("endorseType","check");
		$ret['main_frame'] = '/gui-modules/boxMsg2.php';
		return $ret;
	}

	if ($tender_code == "TV")
		addItem($tender_upc, $tender_desc, "T", "CK", "", 0, 0, $unit_price, $tendered, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
	elseif ($tender_code == "RC"){
		addItem($tender_upc, $tender_desc, "T", "CK", "", 0, 0, $unit_price, $tendered, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
	}
	else
		addItem($tender_upc, $tender_desc, "T", $tender_code, "", 0, 0, $unit_price, $tendered, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
	$IS4C_LOCAL->set("msgrepeat",0);
	$IS4C_LOCAL->set("TenderType",$tender_code);			/***added by apbw 2/1/05 SCR ***/

	if ($IS4C_LOCAL->get("TenderType") == "MI" || $IS4C_LOCAL->get("TenderType") == "CX") { 	// apbw 2/28/05 SCR
		$IS4C_LOCAL->set("chargetender",1);							// apbw 2/28/05 SCR
	}													// apbw 2/28/05 SCR

	getsubtotals();

	if ($right == "FS" || $right == "EF") {
		addfsTaxExempt();
	}

	if ($right == "FS") {
		$fs = -1 * $IS4C_LOCAL->get("fsEligible");
		$fs_ones = (($fs * 100) - (($fs * 100) % 100))/100;
		$fs_change = $fs - $fs_ones;

		if ($fs_ones > 0) {
			addfsones($fs_ones);
		}

		if ($fs_change > 0) {
			addchange($fs_change);
		}
		getsubtotals();
	}

	if ($IS4C_LOCAL->get("amtdue") <= 0.005) {
		if ($IS4C_LOCAL->get("paycard_mode") == PAYCARD_MODE_AUTH
		    && ($right == "CC" || $right == "GD")){
			$IS4C_LOCAL->set("change",0);
			$IS4C_LOCAL->set("fntlflag",0);
			$chk = ttl();
			if ($chk === True)
				$ret['output'] = lastpage();
			else
				$ret['main_frame'] = $chk;
			return $ret;
		}

		$IS4C_LOCAL->set("change",-1 * $IS4C_LOCAL->get("amtdue"));
		$cash_return = $IS4C_LOCAL->get("change");

		if ($right != "FS") {
			addchange($cash_return);
		}

		if ($right == "CK" && $cash_return > 0) 
			$IS4C_LOCAL->set("cashOverAmt",1); // apbw/cvr 3/5/05 cash back beep
					
		$IS4C_LOCAL->set("End",1);
		$IS4C_LOCAL->set("beep","rePoll");
		$IS4C_LOCAL->set("receiptType","full");
		$ret['receipt'] = 'full';
		$ret['output'] = printReceiptfooter();
	}
	else {
		$IS4C_LOCAL->set("change",0);
		$IS4C_LOCAL->set("fntlflag",0);
		$chk = ttl();
		if ($chk === True)
			$ret['output'] = lastpage();
		else
			$ret['main_frame'] = $chk;
	}
	$ret['redraw_footer'] = true;
	return $ret;
}

//-------------------------------------------------------

function deptkey($price, $dept,$ret=array()) {
	global $IS4C_LOCAL;

	$intvoided = 0;

	if ($IS4C_LOCAL->get("quantity") == 0 && $IS4C_LOCAL->get("multiple") == 0) {
			$IS4C_LOCAL->set("quantity",1);
	}
		
	if (!is_numeric($dept) || !is_numeric($price) || strlen($price) < 1 || strlen($dept) < 2) {
		$ret['output'] = inputUnknown();
		$IS4C_LOCAL->set("quantity",1);
		$ret['udpmsg'] = 'errorBeep';
		return $ret;
	}

	$strprice = $price;
	$strdept = $dept;
	$price = $price/100;
	$dept = $dept/10;

	/* auto reprint on ar  */
	if ($dept == 990){
		$IS4C_LOCAL->set("autoReprint",1);
	}
	/* auto reprint on gift card sales */
	if ($dept == 902)
		$IS4C_LOCAL->set("autoReprint",1);
	
	if ($IS4C_LOCAL->get("casediscount") > 0 && $IS4C_LOCAL->get("casediscount") <= 100) {
		$case_discount = (100 - $IS4C_LOCAL->get("casediscount"))/100;
		$price = $case_discount * $price;
	}
	$total = $price * $IS4C_LOCAL->get("quantity");
	$intdept = $dept;

	$query = "select dept_no,dept_name,dept_tax,dept_fs,dept_limit,
		dept_minimum,dept_discount from departments where dept_no = ".$intdept;
	$db = pDataConnect();
	$result = $db->query($query);

	$num_rows = $db->num_rows($result);
	if ($num_rows == 0) {
		$ret['output'] = boxMsg("department unknown");
		$ret['udpmsg'] = 'errorBeep';
		$IS4C_LOCAL->set("quantity",1);
	}
	elseif ($IS4C_LOCAL->get("mfcoupon") == 1) {
		$row = $db->fetch_array($result);
		$IS4C_LOCAL->set("mfcoupon",0);
		$query2 = "select department, sum(total) as total from localtemptrans where department = "
			.$dept." group by department";

		$db2 = tDataConnect();
		$result2 = $db2->query($query2);

		$num_rows2 = $db2->num_rows($result2);
		if ($num_rows2 == 0) {
			$ret['output'] = boxMsg("no item found in<BR>".$row["dept_name"]);
			$ret['udpmsg'] = 'errorBeep';
		}
		else {
			$row2 = $db2->fetch_array($result2);
			if ($price > $row2["total"]) {
				$ret['output'] = boxMsg("coupon amount greater than department total");
				$ret['udpmsg'] = 'errorBeep';
			}
			else {
				addItem("", $row["dept_name"]." Coupon", "I", "CP", "C", $dept, 1, -1 * $price, -1 * $price, -1 * $price, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, $intvoided);
				$IS4C_LOCAL->set("ttlflag",0);
				//$IS4C_LOCAL->set("ttlrequested",0);
				$IS4C_LOCAL->set("beep","goodBeep");
				$ret['output'] = lastpage();
				$ret['redraw_footer'] = True;
				$ret['udpmsg'] = 'goodBeep';
			}
		}
	}
	else {
		$row = $db->fetch_array($result);
		if (!$row["dept_limit"]) $deptmax = 0;
		else $deptmax = $row["dept_limit"];

		if (!$row["dept_minimum"]) $deptmin = 0;
		else $deptmin = $row["dept_minimum"];
		$tax = $row["dept_tax"];

		if ($row["dept_fs"] != 0) $foodstamp = 1;
		else $foodstamp = 0;

		$deptDiscount = $row["dept_discount"];

		if ($IS4C_LOCAL->get("toggleDiscountable") == 1) {
			$IS4C_LOCAL->set("toggleDiscountable",0);
			if  ($deptDiscount == 0) {
				$deptDiscount = 1;
			} else {
				$deptDiscount = 0;
			}
		}

		if ($IS4C_LOCAL->get("togglefoodstamp") == 1) {
			$foodstamp = ($foodstamp + 1) % 2;
			$IS4C_LOCAL->set("togglefoodstamp",0);
		}

		// Hard coding starts
		if ($dept == 606) {
			$price = -1 * $price;
			$total = -1 * $total;
		}
		// Hard coding ends

		if ($IS4C_LOCAL->get("ddNotify") != 0 &&  $IS4C_LOCAL->get("itemPD") == 10) {  
			$IS4C_LOCAL->set("itemPD",0);
			$deptDiscount = 7;
			$intvoided = 22;
		}

		if ($price > $deptmax && $IS4C_LOCAL->get("msgrepeat") == 0) {

			$IS4C_LOCAL->set("boxMsg","$".$price." is greater than department limit<P>"
					."<FONT size='-1'>[clear] to cancel, [enter] to proceed</FONT>");
			$ret['main_frame'] = '/gui-modules/boxMsg2.php';
		}
		elseif ($price < $deptmin && $IS4C_LOCAL->get("msgrepeat") == 0) {
			$IS4C_LOCAL->set("boxMsg","$".$price." is lower than department minimum<P>"
				."<FONT size='-1'>[clear] to cancel, [enter] to proceed</FONT>");
			$ret['main_frame'] = '/gui-modules/boxMsg2.php';
		}
		else {
			if ($IS4C_LOCAL->get("casediscount") > 0) {
				addcdnotify();
				$IS4C_LOCAL->set("casediscount",0);
			}
			
			if ($IS4C_LOCAL->get("toggletax") == 1) {
				if ($tax > 0) $tax = 0;
				else $tax = 1;
				$IS4C_LOCAL->set("toggletax",0);
			}

			if ($dept == "77"){
				$db2 = tDataConnect();
				$taxratesQ = "SELECT rate FROM taxrates WHERE id=$tax";
				$taxratesR = $db2->query($taxratesQ);
				$rate = array_pop($db2->fetch_row($taxratesR));

				$price /= (1+$rate);
				$price = truncate2($price);
				$total = $price * $IS4C_LOCAL->get("quantity");
			}

			addItem($price."DP".$dept, $row["dept_name"], "D", " ", " ", $dept, $IS4C_LOCAL->get("quantity"), $price, $total, $price, 0 ,$tax, $foodstamp, 0, 0, $deptDiscount, 0, $IS4C_LOCAL->get("quantity"), 0, 0, 0, 0, 0, $intvoided);
			$IS4C_LOCAL->set("ttlflag",0);
			//$IS4C_LOCAL->set("ttlrequested",0);
			$ret['output'] = lastpage();
			$ret['redraw_footer'] = True;
			$ret['udpmsg'] = 'goodBeep';
			$IS4C_LOCAL->set("msgrepeat",0);
		}
	}

	$IS4C_LOCAL->set("quantity",0);
	$IS4C_LOCAL->set("itemPD",0);

	return $ret;
}

//-------------------------------------------------

// return value: true on success, URL on failure
function ttl() {
	global $IS4C_LOCAL,$IS4C_PATH;

	if ($IS4C_LOCAL->get("memberID") == "0") {
		return $IS4C_PATH."gui-modules/memlist.php";
	}
	else {
		$mconn = tDataConnect();
		$query = "";
		$query2 = "";
		if ($IS4C_LOCAL->get("isMember") == 1) {
			$cols = localMatchingColumns($mconn,"localtemptrans","memdiscountadd");
			$query = "INSERT INTO localtemptrans ({$cols}) SELECT {$cols} FROM memdiscountadd";
		} else {
			$cols = localMatchingColumns($mconn,"localtemptrans","memdiscountremove");
			$query = "INSERT INTO localtemptrans ({$cols}) SELECT {$cols} FROM memdiscountremove";
		}

		if ($IS4C_LOCAL->get("isStaff") != 0) {
			$cols = localMatchingColumns($mconn,"localtemptrans","staffdiscountadd");
			$query2 = "INSERT INTO localtemptrans ({$cols}) SELECT {$cols} FROM staffdiscountadd";
		} else {
			$cols = localMatchingColumns($mconn,"localtemptrans","staffdiscountremove");
			$query2 = "INSERT INTO localtemptrans ({$cols}) SELECT {$cols} FROM staffdiscountremove";
		}

		$result = $mconn->query($query);
		$result2 = $mconn->query($query2);

		$IS4C_LOCAL->set("ttlflag",1);
		setglobalvalue("TTLFlag", 1);
		$temp = chargeOk();
		if ($IS4C_LOCAL->get("balance") < $IS4C_LOCAL->get("memChargeTotal") && $IS4C_LOCAL->get("memChargeTotal") > 0){
			if ($IS4C_LOCAL->get("warned") == 1 and $IS4C_LOCAL->get("warnBoxType") == "warnOverpay"){
				$IS4C_LOCAL->set("warned",0);
				$IS4C_LOCAL->set("warnBoxType","");
			}
			else {
				$IS4C_LOCAL->set("warned",1);
				$IS4C_LOCAL->set("warnBoxType","warnOverpay");
				$IS4C_LOCAL->set("boxMsg",sprintf("<b>A/R Imbalance</b><br />Total AR payments $%.2f exceeds AR balance %.2f<br /><font size=-1>[enter] to continue, [clear] to cancel</font>",
						$IS4C_LOCAL->get("memChargeTotal"),
						$IS4C_LOCAL->get("balance")));
				$IS4C_LOCAL->set("strEntered","TL");
				return $IS4C_PATH."gui-modules/boxMsg2.php";
			}
		}
		else {
			$IS4C_LOCAL->set("warned",0);
			$IS4C_LOCAL->set("warnBoxType","");
		}

		if ($IS4C_LOCAL->get("percentDiscount") > 0) {
			addItem("", $IS4C_LOCAL->get("percentDiscount")."% Discount", "C", "", "D", 0, 0, truncate2(-1 * $IS4C_LOCAL->get("transDiscount")), 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5);
		}
		$amtDue = str_replace(",", "", $IS4C_LOCAL->get("amtdue"));

		// check in case something else like an
		// approval code is already being sent
		// to the cc terminal
		if ($IS4C_LOCAL->get("ccTermOut")=="idle"){
			$IS4C_LOCAL->set("ccTermOut","total:".
				str_replace(".","",sprintf("%.2f",$amtDue)));
		}

		$peek = peekItem();
		if (substr($peek,0,9) != "Subtotal "){
			addItem("", "Subtotal ".truncate2($IS4C_LOCAL->get("subtotal")).", Tax ".truncate2($IS4C_LOCAL->get("taxTotal")), "C", "", "D", 0, 0, $amtDue, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3);
		}
	
		if ($IS4C_LOCAL->get("fntlflag") == 1) {
			addItem("", "Foodstamps Eligible", "", "", "D", 0, 0, truncate2($IS4C_LOCAL->get("fsEligible")), 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 7);
		}

	}
	return True;
}

function peekItem(){
	$db = tDataConnect();
	$q = "SELECT description FROM localTempTrans ORDER BY trans_id DESC";
	$r = $db->query($q);
	$w = $db->fetch_row($r);
	return (isset($w['description'])?$w['description']:'');
}

//---------------------------------------

function finalttl() {
	global $IS4C_LOCAL;
	if ($IS4C_LOCAL->get("percentDiscount") > 0) {
		addItem("", "Discount", "C", "", "D", 0, 0, truncate2(-1 * $IS4C_LOCAL->get("transDiscount")), 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5);
	}

	addItem("Subtotal", "Subtotal", "C", "", "D", 0, 0, truncate2($IS4C_LOCAL->get("taxTotal") - $IS4C_LOCAL->get("fsTaxExempt")), 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 11);


	if ($IS4C_LOCAL->get("fsTaxExempt")  != 0) {
		addItem("Tax", truncate2($IS4C_LOCAL->get("fstaxable"))." Taxable", "C", "", "D", 0, 0, truncate2($IS4C_LOCAL->get("fsTaxExempt")), 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 7);
	}

	addItem("Total", "Total", "C", "", "D", 0, 0, truncate2($IS4C_LOCAL->get("amtdue")), 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 11);

}

//---------------------------------------

//-------------------------------------------

function fsEligible() {
	global $IS4C_LOCAL;
	getsubtotals();
	if ($IS4C_LOCAL->get("fsEligible") < 0 && False) {
		$IS4C_LOCAL->set("boxMsg","Foodstamp eligible amount inapplicable<P>Please void out earlier tender and apply foodstamp first");
		return $IS4C_PATH."gui-modules/boxMsg2.php";
	}
	else {
		$IS4C_LOCAL->set("fntlflag",1);
		setglobalvalue("FntlFlag", 1);
		if ($IS4C_LOCAL->get("ttlflag") != 1) return ttl();
		else addItem("", "Foodstamps Eligible", "" , "", "D", 0, 0, truncate2($IS4C_LOCAL->get("fsEligible")), 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 7);

		return True;
	}
}

//------------------------------------------

function percentDiscount($strl,$json=array()) {
	if ($strl == 10.01) $strl = 10;

	if (!is_numeric($strl) || $strl > 100 || $strl < 0) $json['output'] = boxMsg("discount invalid");
	else {
		$query = "select sum(total) as total from localtemptrans where upc = '0000000008005' group by upc";

		$db = tDataConnect();
		$result = $db->query($query);

		$num_rows = $db->num_rows($result);
			if ($num_rows == 0) $couponTotal = 0;
		else {
			$row = $db->fetch_array($result);
			$couponTotal = nullwrap($row["total"]);
		}
			if ($couponTotal == 0 || $strl == 0) {

				if ($strl != 0) discountnotify($strl);
				$db->query("update localtemptrans set percentDiscount = ".$strl);
			$chk = ttl();
			if ($chk !== True)
				$json['main_frame'] = $chk;
			$json['output'] = lastpage();
		}
		else $json['output'] = xboxMsg("10% discount already applied");
		$db->close();
	}
	return $json;
}

//------------------------------------------

function chargeOk() {
	global $IS4C_LOCAL;

	getsubtotals();

	$conn = pDataConnect();
	$query = "select m.availBal,m.balance,c.ChargeOk from memchargebalance as m
		left join custdata AS c ON m.CardNo=c.CardNo AND c.personNum=1
		where CardNo = '".$IS4C_LOCAL->get("memberID")."'";

	$result = $conn->query($query);
	$num_rows = $conn->num_rows($result);
	$row = $conn->fetch_array($result);

	$availBal = $row["availBal"] + $IS4C_LOCAL->get("memChargeTotal");
	
	$IS4C_LOCAL->set("balance",$row["balance"]);
	$IS4C_LOCAL->set("availBal",number_format($availBal,2,'.',''));	
	
	$chargeOk = 1;
	if ($num_rows == 0 || !$row["ChargeOk"]) {
		$chargeOk = 0;
	}
	elseif ( $row["ChargeOk"] == 0 ) {
		$chargeOk = 0;	
	}

	return $chargeOk;

}

//----------------------------------------------------------

function madCoupon(){
	getsubtotals();
	addMadCoup();
	lastpage();

}

function comment($comment){
	addComment($comment);
	lastpage();
}
//----------------------------------------------------------

function staffCharge($arg,$json=array()) {
	global $IS4C_LOCAL;

	$IS4C_LOCAL->set("sc",1);
	$staffID = substr($arg, 0, 4);

	$pQuery = "select staffID,chargecode,blueLine from chargecodeview where chargecode = '".$arg."'";
	$pConn = pDataConnect();
	$result = $pConn->query($pQuery);
	$num_rows = $pConn->num_rows($result);
	$row = $pConn->fetch_array($result);

	if ($num_rows == 0) {
		$json['output'] = xboxMsg("unable to authenticate staff ".$staffID);
		$IS4C_LOCAL->set("isStaff",0);			// apbw 03/05/05 SCR
		return $json;
	}
	else {
		$IS4C_LOCAL->set("isStaff",1);			// apbw 03/05/05 SCR
		$IS4C_LOCAL->set("memMsg",$row["blueLine"]);
		$tQuery = "update localtemptrans set card_no = '".$staffID."', percentDiscount = 15";
		$tConn = tDataConnect();

		addscDiscount();		
		discountnotify(15);
		$tConn->query($tQuery);
		getsubtotals();

		$chk = ttl();
		if ($chk !== True){
			$json['main_frame'] = $chk;
			return $json;
		}
		$IS4C_LOCAL->set("runningTotal",$IS4C_LOCAL->get("amtdue"));
		return tender("MI", $IS4C_LOCAL->get("runningTotal") * 100);

	}

}

function endofShift($json) {
	global $IS4C_LOCAL;

	$IS4C_LOCAL->set("memberID","99999");
	$IS4C_LOCAL->set("memMsg","End of Shift");
	addEndofShift();
	getsubtotals();
	$chk = ttl();
	if ($chk !== True){
		$json['main_frame'] = $chk;
		return $json;
	}
	$IS4C_LOCAL->set("runningtotal",$IS4C_LOCAL->get("amtdue"));
	return tender("CA", $IS4C_LOCAL->get("runningtotal") * 100);
}

//---------------------------	WORKING MEMBER DISCOUNT	-------------------------- 
function wmdiscount() {
	global $IS4C_LOCAL;

	$sconn = mDataConnect();
	$conn2 = tDataConnect();
		
	$volQ = "SELECT * FROM is4c_op.volunteerDiscounts WHERE CardNo = ".$IS4C_LOCAL->get("memberID");
	
	$volR = $sconn->query($volQ);
	$row = $sconn->fetch_array($volR);
	$total = $row["total"];
	
	if ($row["staff"] == 3) {
		if ($IS4C_LOCAL->get("discountableTotal") > $total) {
			$a = $total * .15;																// apply 15% disocunt
			$b = ($IS4C_LOCAL->get("discountableTotal") - $total) * .02 ;								// apply 2% discount
			$c = $a + $b;
			$aggdisc = number_format(($c / $IS4C_LOCAL->get("discountableTotal")) * 100,2);				// aggregate discount

			$IS4C_LOCAL->set("transDiscount",$c);
			$IS4C_LOCAL->set("percentDiscount",$aggdisc);
		}
		elseif ($IS4C_LOCAL->get("discountableTotal") <= $total) {
			$IS4C_LOCAL->set("percentDiscount",15);
			$IS4C_LOCAL->set("transDiscount",$IS4C_LOCAL->get("discountableTotal") * .15);
		}
	}
	elseif ($row["staff"] == 6) {
			if ($IS4C_LOCAL->get("discountableTotal") > $total) {
			$a = $total * .05;																// apply 15% disocunt
			$aggdisc = number_format(($a / $IS4C_LOCAL->get("discountableTotal")) * 100,2);				// aggregate discount

			$IS4C_LOCAL->set("transDiscount",$a);
			$IS4C_LOCAL->set("percentDiscount",$aggdisc);
		}
		elseif ($IS4C_LOCAL->get("discountableTotal") <= $total) {
			$IS4C_LOCAL->set("percentDiscount",5);
			$IS4C_LOCAL->set("transDiscount",$IS4C_LOCAL->get("discountableTotal") * .05);
		}
	}

//	discountnotify($IS4C_LOCAL->get("percentDiscount"));
	$conn2->query("update localtemptrans set percentDiscount = ".$IS4C_LOCAL->get("percentDiscount"));

	if ($IS4C_LOCAL->get("discountableTotal") < $total) {
		$a = number_format($IS4C_LOCAL->get("discountableTotal") / 20,2);
		$arr = explode(".",$a);
		if ($arr[1] >= 75 && $arr[1] != 00) $dec = 75;
		elseif ($arr[1] >= 50 && $arr[1] < 75) $dec = 50;
		elseif ($arr[1] >= 25 && $arr[1] < 50) $dec = 25;
		elseif ($arr[1] >= 00 && $arr[1] < 25) $dec = 00;
	
		$IS4C_LOCAL->set("volunteerDiscount",$arr[0]. "." .$dec);
	}
	else {
		$IS4C_LOCAL->set("volunteerDiscount",$total / 20);
	}
	
//	echo "voldisc: " .$IS4C_LOCAL->get("volunteerDiscount");
}
//------------------------- END WORKING MEMBER DISCOUNT	-------------------------
?>
