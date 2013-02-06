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

/**
  @class ReceiptLib
  Receipt functions
*/
class ReceiptLib extends LibraryClass {

	static private $PRINT_OBJ;

// --------------------------------------------------------------
static public function build_time($timestamp) {

	return strftime("%m/%d/%y %I:%M %p", $timestamp);
}
// --------------------------------------------------------------
static public function centerString($text) {

		return self::center($text, 59);
}
// --------------------------------------------------------------
static public function writeLine($text) {
	global $CORE_LOCAL;

	if ($CORE_LOCAL->get("print") != 0) {
		/* check fails on LTP1: in PHP4
		   suppress open errors and check result
		   instead 
		*/
		//if (is_writable($CORE_LOCAL->get("printerPort"))){
		$fp = fopen($CORE_LOCAL->get("printerPort"), "w");
		fwrite($fp, $text);
		fclose($fp);
	}
}
// --------------------------------------------------------------
static public function center_check($text) {

//	return str_repeat(" ", 22).center($text, 60);	// apbw 03/24/05 Wedge printer swap patch
	return self::center($text, 60);				// apbw 03/24/05 Wedge printer swap patch
}

// --------------------------------------------------------------
// concatenated by tt/apbw 3/16/05 old wedge printer Franking Patch II

static public function endorse($text) {

	self::writeLine(chr(27).chr(64).chr(27).chr(99).chr(48).chr(4)  	
	// .chr(27).chr(33).chr(10)
	.$text
	.chr(27).chr(99).chr(48).chr(1)
	.chr(12)
	.chr(27).chr(33).chr(5));
}
// -------------------------------------------------------------

static public function center($text, $linewidth) {
	$blank = str_repeat(" ", 59);
	$text = trim($text);
	$lead = (int) (($linewidth - strlen($text)) / 2);
	$newline = substr($blank, 0, $lead).$text;
	return $newline;
}
// -------------------------------------------------------------
static public function drawerKick() {

		self::writeLine(chr(27).chr(112).chr(0).chr(48)."0");
		//self::writeLine(chr(27).chr(112).chr(48).chr(55).chr(121));
}

// -------------------------------------------------------------
static public function printReceiptHeader($dateTimeStamp, $ref) {
	global $CORE_LOCAL;

	$receipt = self::$PRINT_OBJ->TextStyle(True);

	$i = 2; // for headers below
	if ($CORE_LOCAL->get("newReceipt")==1 && $CORE_LOCAL->get("store") != "wfc"){
		if ($CORE_LOCAL->get("ReceiptHeaderImage") != ""){
			$img = self::$PRINT_OBJ->RenderBitmapFromFile(MiscLib::base_url()."graphics/" . $CORE_LOCAL->get("ReceiptHeaderImage"));
			$receipt .= $img."\n";
			$i=4;
			$receipt .= "\n";
		} 
		else {
			$receipt .= self::$PRINT_OBJ->TextStyle(True, False, True);
			$receipt .= self::$PRINT_OBJ->centerString($CORE_LOCAL->get("receiptHeader1"),True);
			$receipt .= self::$PRINT_OBJ->TextStyle(True);
			$receipt .= "\n\n";
		}
	}
	else if ($CORE_LOCAL->get("newReceipt")==1 && $CORE_LOCAL->get("store") == "wfc"){
		$img = self::$PRINT_OBJ->RenderBitmapFromFile(MiscLib::base_url()."graphics/WFC_Logo.bmp");
		$receipt .= $img."\n";
		$i=4;
		$receipt .= "\n";
	}
	else{
		// zero-indexing the receipt header and footer list
		$receipt .= self::$PRINT_OBJ->TextStyle(True, False, True);
		$receipt .= self::$PRINT_OBJ->centerString($CORE_LOCAL->get("receiptHeader1"),True);
		$receipt .= self::$PRINT_OBJ->TextStyle(True);
		$receipt .= "\n";
	}

	// and continuing on 
	for (; $i <= $CORE_LOCAL->get("receiptHeaderCount"); $i++){
		$receipt .= self::$PRINT_OBJ->centerString($CORE_LOCAL->get("receiptHeader$i"));
		$receipt .= "\n";
	}

	$receipt .= "\n";
	$receipt .= "Cashier: ".$CORE_LOCAL->get("cashier")."\n\n";

	$time = self::build_time($dateTimeStamp);
	$time = str_replace(" ","     ",$time);
	$spaces = 55 - strlen($time) - strlen($ref);
	$receipt .= $time.str_repeat(' ',$spaces).$ref."\n";
			
	return $receipt;
}
// -------------------------------------------------------------
static public function promoMsg() {

}

// Charge Footer split into two functions by apbw 2/1/05

static public function printChargeFooterCust($dateTimeStamp, $ref) {	// apbw 2/14/05 SCR
	global $CORE_LOCAL;

	$chgName = self::getChgName();			// added by apbw 2/14/05 SCR

	$date = self::build_time($dateTimeStamp);

	$receipt = chr(27).chr(33).chr(5)."\n\n\n".self::centerString("C U S T O M E R   C O P Y")."\n"
		   .self::centerString("................................................")."\n"
		   .self::centerString($CORE_LOCAL->get("chargeSlip1"))."\n\n"
		   ."CUSTOMER CHARGE ACCOUNT\n"
		   ."Name: ".trim($CORE_LOCAL->get("ChgName"))."\n"		// changed by apbw 2/14/05 SCR
		   ."Member Number: ".trim($CORE_LOCAL->get("memberID"))."\n"
		   ."Date: ".$date."\n"
		   ."REFERENCE #: ".$ref."\n"
		   ."Charge Amount: $".number_format(-1 * $CORE_LOCAL->get("chargeTotal"), 2)."\n"
		   .self::centerString("................................................")."\n"
		   ."\n\n\n\n\n\n\n"
		   .chr(27).chr(105);

	return $receipt;

}

// Charge Footer split into two functions by apbw 2/1/05

static public function printChargeFooterStore($dateTimeStamp, $ref) {	// apbw 2/14/05 SCR
	global $CORE_LOCAL;

	
	$chgName = self::getChgName();			// added by apbw 2/14/05 SCR
	
	$date = self::build_time($dateTimeStamp);

	$receipt = "\n\n\n\n\n\n\n"
		   .chr(27).chr(105)
		   .chr(27).chr(33).chr(5)		// apbw 3/18/05 
		   ."\n".self::centerString($CORE_LOCAL->get("chargeSlip2"))."\n"
		   .self::centerString("................................................")."\n"
		   .self::centerString($CORE_LOCAL->get("chargeSlip1"))."\n\n"
		   ."CUSTOMER CHARGE ACCOUNT\n"
		   ."Name: ".trim($CORE_LOCAL->get("ChgName"))."\n"		// changed by apbw 2/14/05 SCR
		   ."Member Number: ".trim($CORE_LOCAL->get("memberID"))."\n"
		   ."Date: ".$date."\n"
		   ."REFERENCE #: ".$ref."\n"
		   ."Charge Amount: $".number_format(-1 * $CORE_LOCAL->get("chargeTotal"), 2)."\n"
		   ."I AGREE TO PAY THE ABOVE AMOUNT\n"
		   ."TO MY CHARGE ACCOUNT\n"
		   ."Purchaser Sign Below\n\n\n"
		   ."X____________________________________________\n"
		   .$CORE_LOCAL->get("fname")." ".$CORE_LOCAL->get("lname")."\n\n"
		   .self::centerString(".................................................")."\n\n";
	$CORE_LOCAL->set("chargetender",0);	// apbw 2/14/05 SCR (moved up a line for Reprint patch on 3/10/05)

	return $receipt;


}

static public function printCabCoupon($dateTimeStamp, $ref){
	global $CORE_LOCAL;

	/* no cut
	$receipt = "\n\n\n\n\n\n\n"
		   .chr(27).chr(105)
		   .chr(27).chr(33).chr(5)
		   ."\n";
	 */
	$receipt = "\n";

	$receipt .= self::biggerFont(self::centerBig("WHOLE FOODS COMMUNITY CO-OP"))."\n\n";
	$receipt .= self::centerString("(218) 728-0884")."\n";
	$receipt .= self::centerString("MEMBER OWNED SINCE 1970")."\n";
	$receipt .= self::centerString(self::build_time($dateTimeStamp))."\n";
	$receipt .= self::centerString('Effective this date ONLY')."\n";
	$parts = explode("-",$ref);
	$receipt .= self::centerString("Cashier: $parts[0]")."\n";
	$receipt .= self::centerString("Transaction: $ref")."\n";
	$receipt .= "\n";
	$receipt .= "Your net purchase today of at least $30.00"."\n";
	$receipt .= "qualifies you for a WFC CAB COUPON"."\n";
	$receipt .= "in the amount of $3.00";
	$receipt .= " with\n\n";
	$receipt .= "GO GREEN TAXI (722-8090) or"."\n";
	$receipt .= "YELLOW CAB OF DULUTH (727-1515)"."\n";
	$receipt .= "from WFC toward the destination of\n";
	$receipt .= "your choice TODAY"."\n\n";

		
	$receipt .= ""
		."This coupon is not transferable.\n" 
		."One coupon/day/customer.\n"
		."Any amount of fare UNDER the value of this coupon\n"
		."is the property of the cab company.\n"
		."Any amount of fare OVER the value of this coupon\n"
	       	."is your responsibility.\n"
		."Tips are NOT covered by this coupon.\n"
		."Acceptance of this coupon by the cab driver is\n"
		."subject to the terms and conditions noted above.\n"; 

	return $receipt;
}

// -------------  frank.php incorporated into printlib on 3/24/05 apbw (from here to eof) -------

static public function frank() {
	global $CORE_LOCAL;

	$date = strftime("%m/%d/%y %I:%M %p", time());
	$ref = trim($CORE_LOCAL->get("memberID"))." ".trim($CORE_LOCAL->get("CashierNo"))." ".trim($CORE_LOCAL->get("laneno"))." ".trim($CORE_LOCAL->get("transno"));
	$tender = "AMT: ".MiscLib::truncate2($CORE_LOCAL->get("tenderamt"))."  CHANGE: ".MiscLib::truncate2($CORE_LOCAL->get("change"));
	$output = self::center_check($ref)."\n"
		.self::center_check($date)."\n"
		.self::center_check($CORE_LOCAL->get("ckEndorse1"))."\n"
		.self::center_check($CORE_LOCAL->get("ckEndorse2"))."\n"
		.self::center_check($CORE_LOCAL->get("ckEndorse3"))."\n"
		.self::center_check($CORE_LOCAL->get("ckEndorse4"))."\n"
		.self::center_check($tender)."\n";



	self::endorse($output);
}

// -----------------------------------------------------

static public function frankgiftcert() {
	global $CORE_LOCAL;

	$ref = trim($CORE_LOCAL->get("CashierNo"))."-".trim($CORE_LOCAL->get("laneno"))."-".trim($CORE_LOCAL->get("transno"));
	$time_now = strftime("%m/%d/%y", time());				// apbw 3/10/05 "%D" didn't work - Franking patch
	$next_year_stamp = mktime(0,0,0,date("m"), date("d"), date("Y")+1);
	$next_year = strftime("%m/%d/%y", $next_year_stamp);		// apbw 3/10/05 "%D" didn't work - Franking patch
	// lines 200-207 edited 03/24/05 apbw Wedge Printer Swap Patch
	$output = "";
	$output .= str_repeat("\n", 6);
	$output .= "ref: " .$ref. "\n";
	$output .= str_repeat(" ", 5).$time_now;
	$output .= str_repeat(" ", 12).$next_year;
	$output .= str_repeat("\n", 3);
	$output .= str_repeat(" ", 75);
      $output .= "$".MiscLib::truncate2($CORE_LOCAL->get("tenderamt"));
	self::endorse($output); 

}

// -----------------------------------------------------

static public function frankstock() {
	global $CORE_LOCAL;

	$time_now = strftime("%m/%d/%y", time());		// apbw 3/10/05 "%D" didn't work - Franking patch
	/* pointless
	if ($CORE_LOCAL->get("franking") == 0) {
		$CORE_LOCAL->set("franking",1);
	}
	 */
	$ref = trim($CORE_LOCAL->get("CashierNo"))."-".trim($CORE_LOCAL->get("laneno"))."-".trim($CORE_LOCAL->get("transno"));
	$output  = "";
	$output .= str_repeat("\n", 40);	// 03/24/05 apbw Wedge Printer Swap Patch
	if ($CORE_LOCAL->get("equityAmt")){
		$output = "Equity Payment ref: ".$ref."   ".$time_now; // WFC 
		$CORE_LOCAL->set("equityAmt","");
		$CORE_LOCAL->set("LastEquityReference",$ref);
	}
	else {
		$output .= "Stock Payment $".$CORE_LOCAL->get("tenderamt")." ref: ".$ref."   ".$time_now; // apbw 3/24/05 Wedge Printer Swap Patch
	}

	self::endorse($output);
}
//-------------------------------------------------------


static public function frankclassreg() {
	global $CORE_LOCAL;

	$ref = trim($CORE_LOCAL->get("CashierNo"))."-".trim($CORE_LOCAL->get("laneno"))."-".trim($CORE_LOCAL->get("transno"));
	$time_now = strftime("%m/%d/%y", time());		// apbw 3/10/05 "%D" didn't work - Franking patch
	$output  = "";		
	$output .= str_repeat("\n", 11);		// apbw 3/24/05 Wedge Printer Swap Patch
	$output .= str_repeat(" ", 5);		// apbw 3/24/05 Wedge Printer Swap Patch
	$output .= "Validated: ".$time_now."  ref: ".$ref; 	// apbw 3/24/05 Wedge Printer Swap Patch

	self::endorse($output);	

}

//----------------------------------Credit Card footer----by CvR

/**
  @deprecated
  Not called, ccTotal session var has been removed
*/
static public function printCCFooter($dateTimeStamp, $ref) {
	global $CORE_LOCAL;

	$date = self::build_time($dateTimeStamp);


			
	$receipt = "\n".self::centerString("C U S T O M E R   C O P Y")."\n"
		   .self::centerString("................................................")."\n"
               .self::centerString($CORE_LOCAL->get("chargeSlip1"))."\n\n"
		   .self::centerString("Cardholder acknowledges receipt of goods/services")."\n"
               .self::centerString("in the amount shown and agrees to pay for them")."\n"
               .self::centerString("according to card issuer agreement.")."\n\n"
		   ."CREDIT CARD CHARGE\n"
		   ."Name: ".trim($CORE_LOCAL->get("ccName"))."\n"
		   ."Member Number: ".trim($CORE_LOCAL->get("memberID"))."\n"
		   ."Date: ".$date."\n"
		   ."REFERENCE #: ".$ref."\n"
               ."TROUTD: ".trim($CORE_LOCAL->get("troutd"))."\n"
		   ."Charge Amount: $".number_format(-1*$CORE_LOCAL->get("ccTotal"), 2)."\n"  //changed 04/01/05 Tak & CvR
		   .self::centerString("................................................")."\n"
		   ."\n\n\n\n\n\n\n"
		   .chr(27).chr(105)

	// self::writeLine($receipt1.chr(27).chr(105));
	// self::writeLine(chr(27).chr(105));

	// $receipt2 =""

		   .self::centerString($CORE_LOCAL->get("chargeSlip2"))."\n"
		   .self::centerString("................................................")."\n"
		   .self::centerString($CORE_LOCAL->get("chargeSlip1"))."\n\n"
		   ."CREDIT CARD CHARGE\n"
		   ."Name: ".trim($CORE_LOCAL->get("ccName"))."\n"
		   ."Member Number: ".trim($CORE_LOCAL->get("memberID"))."\n"
		   ."Date: ".$date."\n"
		   ."REFERENCE #: ".$ref."\n"
               ."TROUTD: ".trim($CORE_LOCAL->get("troutd"))."\n"
		   ."Charge Amount: $".number_format(-1*$CORE_LOCAL->get("ccTotal"), 2)."\n\n" //changed 04/01/05  Tak and CvR
		   .self::centerString("I agree to pay the above total amount")."\n"
		   .self::centerString("according to card issuer agreement.")."\n\n"
		   ."Purchaser Sign Below\n\n\n"
		   ."X____________________________________________\n\n"
		   .self::centerString(".................................................")."\n\n";
		
		


	// self::writeLine(chr(27).chr(105));

	return $receipt;

}

/***** jqh 09/29/05 functions added for new receipt *****/
static public function biggerFont($str) {
	$receipt=chr(29).chr(33).chr(17);
	$receipt.=$str;
	$receipt.=chr(29).chr(33).chr(00);

	return $receipt;
}
static public function centerBig($text) {
	$blank = str_repeat(" ", 30);
	$text = trim($text);
	$lead = (int) ((30 - strlen($text)) / 2);
	$newline = substr($blank, 0, $lead).$text;
	return $newline;
}
/***** jqh end change *****/

/***** CvR 06/28/06 calculate current balance for receipt ****/
static public function chargeBalance($receipt){
	global $CORE_LOCAL;
	PrehLib::chargeOK();

	$db = Database::tDataConnect();
	$checkQ = "select trans_id from localtemptrans where department=990 or trans_subtype='MI'";
	$checkR = $db->query($checkQ);
	$num_rows = $db->num_rows($checkR);

	$currActivity = $CORE_LOCAL->get("memChargeTotal");
	$currBalance = $CORE_LOCAL->get("balance") - $currActivity;
	
	if(($num_rows > 0 || $currBalance != 0) && $CORE_LOCAL->get("memberID") != 11){
 		$chargeString = "Current IOU Balance: $".sprintf("%.2f",$currBalance);
		$receipt = $receipt."\n\n".self::biggerFont(self::centerBig($chargeString));
	}
	
	return $receipt;
}

static public function storeCreditIssued($second){
	global $CORE_LOCAL;
	if ($second) return "";

	$db = Database::tDataConnect();
	$checkQ = "select sum(total) from localtemptrans where trans_subtype='SC' and trans_type='T'";
	$checkR = $db->query($checkQ);

	$num_rows = $db->num_rows($checkR);
	if ($num_rows == 0) return "";

	$row = $db->fetch_row($checkR);
	$issued = $row[0];
	if ($issued <= 0) return "";


	$slip = self::centerString("................................................")."\n\n";
	$slip .= self::centerString("( C U S T O M E R   C O P Y )")."\n";
	$slip .= self::biggerFont("Store credit issued")."\n\n";
	$slip .= self::biggerFont(sprintf("Amount \$%.2f",$issued))."\n\n";

	if ( $CORE_LOCAL->get("fname") != "" && $CORE_LOCAL->get("lname") != ""){
		$slip .= "Name: ".$CORE_LOCAL->get("fname")." ".$CORE_LOCAL->get("lname")."\n\n";
	}
	else {
		$slip .= "Name: ____________________________________________\n\n";
	}
	$slip .= "Ph #: ____________________________________________\n\n";

	$slip .= " * no cash back on store credit refunds\n";
	$slip .= " * change amount is not transferable to\n   another store credit\n";
	$slip .= self::centerString("................................................")."\n";
	return $slip;
}

static public function getChgName() {
	/*      
		the name that appears beneath the signature 
		line on the customer copy is pulled from $CORE_LOCAL. 
		Pulling the name here from custdata w/o respecting
		personNum can cause this name to differ from the 
		signature line, so I'm using $CORE_LOCAL here too. I'm 
		leaving the query in place as a check that memberID
		is valid; shouldn't slow anything down noticably.

		I also changed the memberID strlen qualifier because the 
		!= 4 or == 4 decision was causing inconsistent behavior 
		with older memberships that have memberIDs shorter than 
		4 digits.

		andy
	*/
	global $CORE_LOCAL;
	$query = "select LastName, FirstName from custdata where CardNo = '" .$CORE_LOCAL->get("memberID") ."'";
	$connection = Database::pDataConnect();
	$result = $connection->query($query);
	$num_rows = $connection->num_rows($result);

	if ($num_rows > 0) {
		$LastInit = substr($CORE_LOCAL->get("lname"), 0, 1).".";
		$CORE_LOCAL->set("ChgName",trim($CORE_LOCAL->get("fname")) ." ". $LastInit);
	}
	else{
		$CORE_LOCAL->set("ChgName",$CORE_LOCAL->get("memMsg"));
	}

	$connection->close();
}

static public function printCCSigSlip($dateTimeStamp,$ref,$storeCopy=True,$rp=0){
	global $CORE_LOCAL;
	self::normalFont();

	$date = self::build_time($dateTimeStamp);
	$ert = explode("-",$ref);
	$emp = $ert[0];
	$reg = $ert[1];
	$trans = $ert[2];
	$slip = "";
	$db = -1;
	$idclause = "";
	$limit = "";
	$sort = "";

	if ( $rp != 0 ) {	// if this is a reprint of a previous transaction, loop through all cc slips for that transaction
		$db = Database::mDataConnect();
	} else {		// else if current transaction, just grab most recent 
		if ($storeCopy){
			$idclause = " and transID = ".$CORE_LOCAL->get("paycard_id");
			$limit = " TOP 1 ";
		}
		$sort = " desc ";
		$db = Database::tDataConnect();
	}
	// query database for cc receipt info 
	$query = "select ".$limit." tranType, amount, PAN, entryMethod, issuer, xResultMessage, xApprovalNumber, xTransactionID, name, "
		." datetime from ccReceiptView where date=".date('Ymd',$dateTimeStamp)
		." and cashierNo = ".$emp." and laneNo = ".$reg
		." and transNo = ".$trans ." ".$idclause
		." order by datetime, cashierNo, laneNo, transNo, xTransactionID, transID ".$sort.", sortorder ".$sort;
	if ($CORE_LOCAL->get("DBMS") == "mysql" && $rp == 0){
		$query = str_replace("[date]","date",$query);
		if ($limit != ""){
			$query = str_replace($limit,"",$query);
			$query .= " LIMIT 1";
		}
	}
	$result = $db->query($query);
	$num_rows = $db->num_rows($result);

	for ($i=0;$i<$num_rows;$i++) { 
		$row = $db->fetch_array($result);	
		$trantype = $row['tranType'];  
		if ($row['amount'] < 0) {
			$amt = "-$".number_format(-1*$row['amount'],2);
		} else {
			$amt = "$".number_format($row['amount'],2);
		}
		$pan = $row['PAN']; // already masked in the database
		$entryMethod = $row['entryMethod'];
		$cardBrand = $row['issuer'];
		$approvalPhrase = $row['xResultMessage'];
		$authCode = "#".$row['xApprovalNumber'];
		$sequenceNum = $row['xTransactionID'];  
		$name = $row["name"];

		// store copy is 22 lines long
		if (!$storeCopy){
			//$slip .= "CC".self::centerString("C U S T O M E R   C O P Y")."\n";	// "wedge copy"
		}
		else {
			$slip .= "CC".substr(self::centerString($CORE_LOCAL->get("chargeSlip2")),2)."\n";	// "wedge copy"
		}
		$slip .= self::centerString("................................................")."\n";
		if ($storeCopy){
			$slip .= self::centerString($CORE_LOCAL->get("chargeSlip1"))."\n"		// store name 
				.self::centerString($CORE_LOCAL->get("chargeSlip3").", ".$CORE_LOCAL->get("chargeSlip4"))."\n"  // address
				.self::centerString($CORE_LOCAL->get("chargeSlip5"))."\n"		// merchant code 
				.self::centerString($CORE_LOCAL->get("receiptHeader2"))."\n\n";	// phone
		}
				
		if ($storeCopy){
			$slip .= $trantype."\n"			// trans type:  purchase, canceled purchase, refund or canceled refund
				."Card: ".$cardBrand."  ".$pan."\n"
				."Reference:  ".$ref."\n"
				."Date & Time:  ".$date."\n"
				."Entry Method:  ".$entryMethod."\n"  		// swiped or manual entry
				."Sequence Number:  ".$sequenceNum."\n"	// their sequence #		
				//."Authorization:  ".$approvalPhrase." ".$authCode."\n"		// result + auth number
				."Authorization:  ".$approvalPhrase."\n"		// result + auth number
				.self::boldFont()  // change to bold font for the total
				."Amount: ".$amt."\n"		
				.self::normalFont();
		}
		else {
			// use columns instead
			$c1 = array();
			$c2 = array();
			$c1[] = $trantype;
			$c1[] = "Entry Method:  ".$entryMethod;
			$c1[] = "Sequence Number:  ".$sequenceNum;
			$c2[] = $cardBrand."  ".$pan;
			$c2[] = "Authorization:  ".$approvalPhrase;
			$c2[] = self::boldFont()."Amount: ".$amt.self::normalFont();
			$slip .= self::twoColumns($c1,$c2);
		}
		if ($storeCopy){
			$slip .= self::centerString("I agree to pay above total amount")."\n"
			.self::centerString("according to card issuer agreement.")."\n\n"
			
			.self::centerString("X____________________________________________")."\n"
			.self::centerString($name)."\n";
		}
		$slip .= self::centerString(".................................................")."\n"
				."\n";
		// if more than one sig slip, cut after each one (except the last)	
		if ($num_rows > 1 && $i < $num_rows-1 && $storeCopy) { 
			$slip .= "\n\n\n\n".chr(27).chr(105);
		}			
	}

	if ($CORE_LOCAL->get("SigCapture") != "" && $CORE_LOCAL->get("SigSlipType") == "ccSlip"){
		$sig_file = $_SESSION["INCLUDE_PATH"]."/graphics/SigImages/"
			.$CORE_LOCAL->get("CapturedSigFile");

		$bmp = new Bitmap();
		$bmp->Load($sig_file);

		$bmpData = $bmp->GetRawData();
		$bmpWidth = $bmp->GetWidth();
		$bmpHeight = $bmp->GetHeight();
		$bmpRawBytes = (int)(($bmpWidth + 7)/8);

		$printer = new ESCPOSPrintHandler();
		$stripes = $printer->TransposeBitmapData($bmpData, $bmpWidth);
		for($i=0; $i<count($stripes); $i++)
			$stripes[$i] = $printer->InlineBitmap($stripes[$i], $bmpWidth);

		$slip .= $printer->AlignCenter();
		if (count($stripes) > 1)
			$slip .= $printer->LineSpacing(0);
		$slip .= implode("\n",$stripes);
		if (count($stripes) > 1)
			$slip .= $printer->ResetLineSpacing()."\n";
		$slip .= $printer->AlignLeft();
	}

 	return $slip; 
}

static public function normalFont() {
	return chr(27).chr(33).chr(5);
}
static public function boldFont() {
	return chr(27).chr(33).chr(9);
}

static public function localTTL(){
	global $CORE_LOCAL;

	if ($CORE_LOCAL->get("localTotal") == 0) return "";

	$str = sprintf("LOCAL PURCHASES = \$%.2f",
		$CORE_LOCAL->get("localTotal"));
	return $str."\n";
}

static public function receiptFromBuilders($reprint=False,$trans_num=''){
	global $CORE_LOCAL;

	$empNo=0;$laneNo=0;$transNo=0;
	if ($reprint){
		$temp = explode("-",$trans_num);
		$empNo= $temp[0];
		$laneNo = $temp[1];
		$transNo = $temp[2];
	}

	// read records from transaction database
	$query = "SELECT * FROM localtemptrans ORDER BY trans_id";
	if ($reprint){
		$query = sprintf("SELECT * FROM localtranstoday WHERE
			emp_no=%d AND register_no=%d AND trans_no=%d
			ORDER BY trans_id",$empNo,$laneNo,$transNo);
	}
	$sql = Database::tDataConnect();
	$result = $sql->query($query);
	$recordset = array();
	while($row = $sql->fetch_row($result))
		$recordset[] = $row;
	$sql->close();

	// load module configuration
	$FILTER_MOD = $CORE_LOCAL->get("RBFilter");
	if($FILTER_MOD=="") $FILTER_MOD = "DefaultReceiptFilter";
	$SORT_MOD = $CORE_LOCAL->get("RBSort");
	if($SORT_MOD=="") $SORT_MOD = "DefaultReceiptSort";
	$TAG_MOD = $CORE_LOCAL->get("RBTag");
	if($TAG_MOD=="") $TAG_MOD = "DefaultReceiptTag";
	$TYPE_MAP = $CORE_LOCAL->get("RBFormatMap");
	if (!is_array($TYPE_MAP)){
		$TYPE_MAP = array(
			'item' => 'ItemFormat',
			'tender' => 'TenderFormat',
			'total' => 'TotalFormat',
			'other' => 'OtherFormat'
		);
	}

	$f = new $FILTER_MOD();
	$recordset = $f->filter($recordset);

	$s = new $SORT_MOD();
	$recordset = $s->sort($recordset);

	$t = new $TAG_MOD();
	$recordset = $t->tag($recordset);

	$ret = "";
	foreach($recordset as $record){
		$type = $record['tag'];
		if(!isset($TYPE_MAP[$type])) continue;

		$class = $TYPE_MAP[$type];
		$obj = new $class();

		$line = $obj->format($record);

		if($obj->is_bold){
			$ret .= self::$PRINT_OBJ->TextStyle(True,True);
			$ret .= $line;
			$ret .= self::$PRINT_OBJ->TextStyle(True,False);
			$ret .= "\n";
		}
		else {
			$ret .= $line;
			$ret .= "\n";
		}
	}

	return $ret;
}

static public function receiptDetail($reprint=False,$trans_num='') { // put into its own function to make it easier to follow, and slightly modified for wider-spread use of joe's "new" receipt format --- apbw 7/3/2007
	global $CORE_LOCAL;

	if ($CORE_LOCAL->get("newReceipt") == 2)
		return self::receiptFromBuilders($reprint,$trans_num);

	$detail = "";
	$empNo=0;$laneNo=0;$transNo=0;
	if ($reprint){
		$temp = explode("-",$trans_num);
		$empNo= $temp[0];
		$laneNo = $temp[1];
		$transNo = $temp[2];
	}
		
	if ($CORE_LOCAL->get("newReceipt") == 0 ) {
		// if old style has been specifically requested 
		// for a partial or reprint, use old format
		$query="select linetoprint from receipt";
		if ($reprint){
			$query = "select linetoprint from rp_receipt
				where emp_no=$empNo and register_no=$laneNo
				and trans_no=$transNo order by trans_id";
		}
		$db = Database::tDataConnect();
		$result = $db->query($query);
		$num_rows = $db->num_rows($result);
		// loop through the results to generate the items listing.
		for ($i = 0; $i < $num_rows; $i++) {
			$row = $db->fetch_array($result);
			$detail .= $row[0]."\n";
		}
	} 
	else { 
		// otherwise use new format 
		$query = "select linetoprint,sequence,dept_name,ordered, 0 as [local] "
			    ." from receipt_reorder_unions_g order by ordered,dept_name, " 
			    ." case when ordered=4 then '' else upc end, [sequence]";
		if ($reprint){
			$query = "select linetoprint,sequence,dept_name,ordered, 0 as [local] "
				." from rp_receipt_reorder_unions_g where emp_no=$empNo and "
				." register_no=$laneNo and trans_no=$transNo "
				." order by ordered,dept_name, " 
				." case when ordered=4 then '' else upc end, [sequence]";
		}

		$db = Database::tDataConnect();
		if ($CORE_LOCAL->get("DBMS") == "mysql"){
			$query = str_replace("[","",$query);
			$query = str_replace("]","",$query);
		}
		$result = $db->query($query);
		$num_rows = $db->num_rows($result);
			
		// loop through the results to generate the items listing.
		$lastDept="";
		for ($i = 0; $i < $num_rows; $i++) {
			$row = $db->fetch_array($result);
			if ($row[2]!=$lastDept){  // department header
				
				if ($row['2']==''){
					$detail .= "\n";
				}
				else{
					$detail .= self::$PRINT_OBJ->TextStyle(True,True);
					$detail .= $row[2];
					$detail .= self::$PRINT_OBJ->TextStyle(True,False);
					$detail .= "\n";
				}
			}
			/***** jqh 12/14/05 fix tax exempt on receipt *****/
			if ($row[1]==2 and $CORE_LOCAL->get("TaxExempt")==1){
				$detail .= "                                         TAX    0.00\n";
			}
			elseif ($row[1]==1 and $CORE_LOCAL->get("TaxExempt")==1){
				$queryExempt="select 
					right((space(44) + upper(rtrim('SUBTOTAL'))), 44) 
					+ right((space(8) + convert(varchar,runningTotal-tenderTotal)), 8) 
					+ right((space(4) + ''), 4) as linetoprint,1 as sequence,null as dept_name,3 as ordered,'' as upc
					from lttSummary";
				$resultExempt = $db->query($queryExempt);
				$rowExempt = $db->fetch_array($resultExempt);
				$detail .= $rowExempt[0]."\n";
			}
			else{
				if ($CORE_LOCAL->get("promoMsg") == 1 && $row[4] == 1 ){ 
					// '*' added to local items 8/15/2007 apbw for eat local challenge 
					$detail .= '*'.$row[0]."\n";
				} else {
					if ( strpos($row[0]," TOTAL") ) { 		
						// if it's the grand total line . . .
						$detail .= self::$PRINT_OBJ->TextStyle(True,True);
						$detail .= $row[0]."\n";
						$detail .= self::$PRINT_OBJ->TextStyle(True,False);
					} else {
						$detail .= $row[0]."\n";
					}
				}
			}
			/***** jqh end change *****/
			
			$lastDept=$row[2];
		} // end for loop
	}

	return $detail;
}

/*
 * gift card receipt functions --atf 10/8/07
 */
static public function printGCSlip($dateTimeStamp, $ref, $storeCopy=true, $rp=0) {
	global $CORE_LOCAL;

	$date = self::build_time($dateTimeStamp);
	$ert = explode("-",$ref);
	$emp = $ert[0];
	$reg = $ert[1];
	$trans = $ert[2];
	$slip = "";
	
	// query database for gc receipt info 
	$limit = "";
	$order = "";
	$where = "[date]=".date('Ymd',$dateTimeStamp)." AND cashierNo=".$emp." AND laneNo=".$reg." AND transNo=".$trans;
	if( $rp == 0) {
		$limit = " TOP 1";
		$order = " desc";
		$where .= " AND transID=".$CORE_LOCAL->get("paycard_id");
	}
	$sql = "SELECT".$limit." * FROM gcReceiptView WHERE ".$where." ORDER BY [datetime]".$order.", sortorder".$order;
	$db = Database::tDataConnect();
	if ($CORE_LOCAL->get("DBMS") == "mysql"){
		$sql = "SELECT * FROM gcReceiptView WHERE ".$where." ORDER BY [datetime]".$order.", sortorder".$order." ".$limit;
		$sql = str_replace("[","",$sql);
		$sql = str_replace("]","",$sql);
		$sql = str_replace("TOP","LIMIT",$sql);
	}
	$result = $db->query($sql);
	$num = $db->num_rows($result);

	// print a receipt for each row returned
	for( $x = 0; $row = $db->fetch_array($result); $x++) {
		// special stuff for the store copy only
		if( $storeCopy) {
			// cut before each slip after the first
			if( $x > 0)
				$slip .= "\n\n\n\n".chr(27).chr(105);
			// reprint header
			if( $rp != 0)
				$slip .= chr(27).chr(33).chr(5).self::centerString("***    R E P R I N T    ***")."\n";
			// store header
			$slip .= "GC".substr(self::centerString($CORE_LOCAL->get("chargeSlip2")),2)."\n"  // "wedge copy"
					. self::centerString("................................................")."\n"
					. self::centerString($CORE_LOCAL->get("chargeSlip1"))."\n"  // store name 
					. self::centerString($CORE_LOCAL->get("chargeSlip3").", ".$CORE_LOCAL->get("chargeSlip4"))."\n"  // address
					. self::centerString($CORE_LOCAL->get("receiptHeader2"))."\n"  // phone
					. "\n";
		} else {
			if( $x == 0) {
				if( $num > 1)  $slip .= self::centerString("------- C A R D H O L D E R   C O P I E S -------")."\n";
				else           $slip .= self::centerString("--------- C A R D H O L D E R   C O P Y ---------")."\n";
				//$slip .= self::centerString("................................................")."\n";
			}
		}
		// transaction data
		if( true) { // two-column layout
			$col1 = array();
			$col2 = array();
			$col1[] = $row['tranType'];
			$col2[] = "Date: ".date('m/d/y h:i a', strtotime($row['datetime']));
			$col1[] = "Terminal ID: ".$row['terminalID'];
			$col2[] = "Reference: ".$ref."-".$row['transID'];
			$col1[] = "Card: ".$row['PAN'];
			$col2[] = "Entry Method: ".$row['entryMethod'];
			if( ((int)$row['xVoidCode']) > 0) {
				$col1[] = "Void Auth: ".$row['xVoidCode'];
				$col2[] = "Orig Auth: ".$row['xAuthorizationCode'];
			} else {
				$col1[] = "Authorization: ".$row['xAuthorizationCode'];
				$col2[] = "";
			}
			$col1[] = self::boldFont()."Amount: ".PaycardLib::paycard_moneyFormat($row['amount']).self::normalFont(); // bold ttls apbw 11/3/07
			$col2[] = "New Balance: ".PaycardLib::paycard_moneyFormat($row['xBalance']);
			$slip .= self::twoColumns($col1, $col2);
		} else { // all-left layout
			$slip .= $row['tranType']."\n"
					. "Card: ".$row['PAN']."\n"
					. "Date: ".date('m/d/y h:i a', strtotime($row['datetime']))."\n"
					. "Terminal ID: ".$row['terminalID']."\n"
					. "Reference: ".$ref."-".$row['transID']."\n"
					. "Entry Method: ".$row['entryMethod']."\n";
			if( ((int)$row['xVoidCode']) > 0) {
				$slip .= "Original Authorization: ".$row['xAuthorizationCode']."\n"
						. "Void Authorization: ".$row['xVoidCode']."\n";
			} else {
				$slip .= "Authorization: ".$row['xAuthorizationCode']."\n";
			}
			$slip .= self::boldFont()."Amount: ".PaycardLib::paycard_moneyFormat($row['amount']).self::normalFont()."\n" // bold ttls apbw 11/3/07
					. "New Balance: ".PaycardLib::paycard_moneyFormat($row['xBalance'])."\n";
		}
		// name/phone on activation only
		if( $row['tranType'] == 'Gift Card Activation' && $storeCopy) {
			$slip .= "\n".self::centerString("Name:  ___________________________________")."\n"
					."\n".self::centerString("Phone: ___________________________________")."\n";
		}
		$slip .= self::centerString(".................................................")."\n";
		// reprint footer
		if( $storeCopy && $rp != 0)
			$slip .= chr(27).chr(33).chr(5).self::centerString("***    R E P R I N T    ***")."\n";
	} // foreach row
	
	// add normal font ONLY IF we printed something else, too
	if( strlen($slip) > 0)
		$slip = self::normalFont() . $slip;
	
	return $slip;
} // printGCSlip()

static public function printGCBalSlip() {
	global $CORE_LOCAL;

	// balance inquiries are not logged and have no meaning in a reprint,
	// so we can assume that it just happened now and all data is still in session vars
	$tempArr = $CORE_LOCAL->get("paycard_response");
	$bal = "$".number_format($tempArr["Balance"],2);
	$pan = $CORE_LOCAL->get("paycard_PAN"); // no need to mask gift card numbers
	$slip = self::normalFont()
			.self::centerString(".................................................")."\n"
			.self::centerString($CORE_LOCAL->get("chargeSlip1"))."\n"		// store name 
			.self::centerString($CORE_LOCAL->get("chargeSlip3").", ".$CORE_LOCAL->get("chargeSlip4"))."\n"  // address
			.self::centerString($CORE_LOCAL->get("receiptHeader2"))."\n"	// phone
			."\n"
			."Gift Card Balance\n"
			."Card: ".$pan."\n"
			."Date: ".date('m/d/y h:i a')."\n"
			.self::boldFont()  // change to bold font for the total
			."Balance: ".$bal."\n"
			.self::normalFont()
			.self::centerString(".................................................")."\n"
			."\n";
  return $slip;
} // printGCBalSlip()

static public function twoColumns($col1, $col2) {
	// init
	$max = 56;
	$text = "";
	// find longest string in each column, ignoring font change strings
	$c1max = 0;
	$col1s = array();
	foreach( $col1 as $c1) {
		$c1s = trim(str_replace(array(self::boldFont(),self::normalFont()), "", $c1));
		$col1s[] = $c1s;
		$c1max = max($c1max, strlen($c1s));
	}
	$c2max = 0;
	$col2s = array();
	foreach( $col2 as $c2) {
		$c2s = trim(str_replace(array(self::boldFont(),self::normalFont()), "", $c2));
		$col2s[] = $c2s;
		$c2max = max($c2max, strlen($c2s));
	}
	// space the columns as much as they'll fit
	$spacer = $max - $c1max - $c2max;
	// scan both columns
	for( $x=0; isset($col1[$x]) && isset($col2[$x]); $x++) {
		$c1 = trim($col1[$x]);  $c1l = strlen($col1s[$x]);
		$c2 = trim($col2[$x]);  $c2l = strlen($col2s[$x]);
		if( ($c1max+$spacer+$c2l) <= $max) {
			$text .= $c1 . @str_repeat(" ", ($c1max+$spacer)-$c1l) . $c2 . "\n";
		} else {
			$text .= $c1 . "\n" . str_repeat(" ", $c1max+$spacer) . $c2 . "\n";
		}
	}
	// if one column is longer than the other, print the extras
	// (only one of these should happen since the loop above runs as long as both columns still have rows)
	for( $y=$x; isset($col1[$y]); $y++) {
		$text .= trim($col1[$y]) . "\n";
	} // col1 extras
	for( $y=$x; isset($col2[$y]); $y++) {
		$text .= str_repeat(" ", $c1max+$spacer) . trim($col2[$y]) . "\n";
	} // col2 extras
	return $text;
}

/**
  generates a receipt string
  @param $arg1 string receipt type
  @param $second boolean indicating it's a second receipt
  @return string receipt content
*/
static public function printReceipt($arg1,$second=False) {
	global $CORE_LOCAL;

	self::$PRINT_OBJ = new ESCPOSPrintHandler();

	$kicker_class = ($CORE_LOCAL->get("kickerModule")=="") ? 'Kicker' : $CORE_LOCAL->get('kickerModule');
	$kicker_obj = new $kicker_class();
	if (!is_object($kicker_object)) $kicker_object = new Kicker();
	$dokick = $kicker_obj->doKick();
	$receipt = "";

	if ($arg1 == "full" && $dokick) {	// ---- apbw 03/29/05 Drawer Kick Patch
		$kick_cmd = self::$PRINT_OBJ->DrawerKick(2,48*2,30*2);
		self::$PRINT_OBJ->writeLine($kick_cmd);
		//self:::writeLine(chr(27).chr(112).chr(0).chr(48)."0");
	}

/* --------------------------------------------------------------
  turn off staff charge receipt printing if toggled - apbw 2/1/05 
  ---------------------------------------------------------------- */

	$noreceipt = ($CORE_LOCAL->get("receiptToggle")==1 ? 0 : 1);
	
	$dateTimeStamp = time();		// moved by apbw 2/15/05 SCR

// -- Our Reference number for the transaction.

	$ref = trim($CORE_LOCAL->get("CashierNo"))."-".trim($CORE_LOCAL->get("laneno"))."-".trim($CORE_LOCAL->get("transno"));

	if ($noreceipt != 1){ 		// moved by apbw 2/15/05 SCR
		$receipt = self::printReceiptHeader($dateTimeStamp, $ref);

		if ($second){
			$ins = self::$PRINT_OBJ->centerString("( S T O R E   C O P Y )")."\n";
			$receipt = substr($receipt,0,3).$ins.substr($receipt,3);
		}

		// The Nitty Gritty:
		/***** jqh 09/29/05 changes made to following if statement so if the receipt is full, then print new receipt,
		if not full, then print old style receipt *****/
		if ($arg1 == "full") {

			$receipt .= self::receiptDetail();
			$member = "Member ".trim($CORE_LOCAL->get("memberID"));
			$your_discount = $CORE_LOCAL->get("transDiscount");

			if ($CORE_LOCAL->get("transDiscount") + 
			   $CORE_LOCAL->get("specials") > 0 ) {
				$receipt .= 'TODAY YOU SAVED = $'.
					number_format($your_discount + $CORE_LOCAL->get("specials"),2).
					"\n";
			}
			$receipt .= self::localTTL();
			$receipt .= "\n";
	
			if (trim($CORE_LOCAL->get("memberID")) != $CORE_LOCAL->get("defaultNonMem")) {
				if ($CORE_LOCAL->get("newReceipt")==1){
					$receipt .= self::$PRINT_OBJ->TextStyle(True,False,True);
					$receipt .= self::$PRINT_OBJ->centerString("thank you - owner ".$member,True);
					$receipt .= self::$PRINT_OBJ->TextStyle(True);
					$receipt .= "\n\n";
				}
				else{
					$receipt .= self::$PRINT_OBJ->centerString("Thank You - ".$member);
					$receipt .= "\n";
				}
			}
			else {
				if ($CORE_LOCAL->get("newReceipt")==1){
					$receipt .= self::$PRINT_OBJ->TextStyle(True,False,True);
					$receipt .= self::$PRINT_OBJ->centerString("thank you",True);
					$receipt .= self::$PRINT_OBJ->TextStyle(True);
					$receipt .= "\n\n";
				}
				else{
					$receipt .= self::$PRINT_OBJ->centerString("Thank You!");
					$receipt .= "\n";
				}
			}

			for ($i = 1; $i <= $CORE_LOCAL->get("receiptFooterCount"); $i++){
				$receipt .= self::$PRINT_OBJ->centerString($CORE_LOCAL->get("receiptFooter$i"));
				$receipt .= "\n";
			}

			if ($CORE_LOCAL->get("store")=="wfc"){
				$refund_date = date("m/d/Y",mktime(0,0,0,date("n"),date("j")+30,date("Y")));
				$receipt .= self::$PRINT_OBJ->centerString("returns accepted with this receipt through ".$refund_date);
				$receipt .= "\n";
			}

			/***** CvR add charge total to receipt bottom ****/
			$receipt = self::chargeBalance($receipt);
			/**** CvR end ****/

			// append customer copy to actual lane receipt
			if ($CORE_LOCAL->get('standalone') == 0)
				$receipt .= self::printCCSigSlip($dateTimeStamp, $ref, false, 0);

			if ($CORE_LOCAL->get("autoReprint") == 1)
				$receipt .= self::printGCSlip($dateTimeStamp, $ref, false, 1);
			else
				$receipt .= self::printGCSlip($dateTimeStamp, $ref, true, 1);

			if ($CORE_LOCAL->get("promoMsg") == 1) {
				self::promoMsg();
			}

			$receipt .= self::storeCreditIssued($second);

			$CORE_LOCAL->set("headerprinted",0);
		}
		else if ($arg1 == "cab"){
			$ref = $CORE_LOCAL->get("cabReference");
			$receipt = self::printCabCoupon($dateTimeStamp, $ref);
			$CORE_LOCAL->set("cabReference","");
		}
		else {
			/***** jqh 09/29/05 if receipt isn't full, then display receipt in old style *****/
			$query="select linetoprint from receipt";
			$db = Database::tDataConnect();
			$result = $db->query($query);
			$num_rows = $db->num_rows($result);
	
			// loop through the results to generate the items listing.
			for ($i = 0; $i < $num_rows; $i++) {
				$row = $db->fetch_array($result);
				$receipt .= $row[0]."\n";
			}
			/***** jqh end change *****/

			$dashes = "\n".self::centerString("----------------------------------------------")."\n";

			if ($arg1 == "partial") {
				$receipt .= $dashes.self::centerString("*    P A R T I A L  T R A N S A C T I O N    *").$dashes;
			}
			elseif ($arg1 == "cancelled") {
				$receipt .= $dashes.self::centerString("*  T R A N S A C T I O N  C A N C E L L E D  *").$dashes;
			}
			elseif ($arg1 == "resume") {
				$receipt .= $dashes.self::centerString("*    T R A N S A C T I O N  R E S U M E D    *").$dashes
				     .self::centerString("A complete receipt will be printed\n")
				     .self::centerString("at the end of the transaction");
			}
			elseif ($arg1 == "suspended") {
				$receipt .= $dashes.self::centerString("*  T R A N S A C T I O N  S U S P E N D E D  *").$dashes
					     .self::centerString($ref);
			}
			elseif ($arg1 == "ccSlip") {
				$receipt = self::printCCSigSlip($dateTimeStamp,$ref,True);
			}
			else if ($arg1 == "gcSlip") { // --atf 10/8/07
				if ($CORE_LOCAL->get("autoReprint") == 1){
					$receipt = self::printGCSlip($dateTimeStamp,$ref,true);
				}
				else {
					$receipt = self::printGCSlip($dateTimeStamp,$ref,false);
				}
			} 
			else if ($arg1 == "gcBalSlip") { // --atf 10/8/07
				$receipt = self::printGCBalSlip();
			} 
		
		} /***** jqh end big if statement change *****/
	}
	else {
	}

	/* --------------------------------------------------------------
	  print store copy of charge slip regardless of receipt print setting - apbw 2/14/05 
	  ---------------------------------------------------------------- */
	if ($CORE_LOCAL->get("chargetender") == 1 && $CORE_LOCAL->get("End") == 1) {
		if ($noreceipt == 1) {	
			$receipt = $receipt.self::printChargeFooterStore($dateTimeStamp, $ref);
		} else {	
			$receipt = $receipt.self::printChargeFooterStore($dateTimeStamp, $ref);	
		}	
	}		
			
	if ($receipt !== ""){
		$receipt = $receipt."\n\n\n\n\n\n\n";
		$receipt .= chr(27).chr(105);
	}
	
	$CORE_LOCAL->set("receiptToggle",1);
	return $receipt;
}

static public function reprintReceipt($trans_num=""){
	global $CORE_LOCAL;

	self::$PRINT_OBJ = new ESCPOSPrintHandler();

	if (strlen($trans_num) >= 1) {
		$title = chr(27).chr(33).chr(5).self::centerString("***    R E P R I N T    ***")."\n\n\n";

		$arpspec = explode("::", $trans_num);
		$laneno = $arpspec[0];
		$cashierNo = $arpspec[1];
		$transno = $arpspec[2];

		$ref = trim($cashierNo)."-".trim($laneno)."-".trim($transno);

		$queryHeader = "select * from rp_receipt_header where register_no = ".$laneno." and emp_no = ".$cashierNo." and trans_no = ".$transno;
 	
		$connect = Database::tDataConnect();
		$header = $connect->query($queryHeader);
		$headerRow = $connect->fetch_array($header);

		$dateTimeStamp = $headerRow["dateTimeStamp"];
		$dateTimeStamp = strtotime($dateTimeStamp);

		$CORE_LOCAL->set("memberID",$headerRow["memberID"]);
		$CORE_LOCAL->set("memCouponTLL",$headerRow["couponTotal"]);
		$CORE_LOCAL->set("transDiscount",$headerRow["transDiscount"]);
		$CORE_LOCAL->set("chargeTotal",-1*$headerRow["chargeTotal"]);

		if ($CORE_LOCAL->get("chargeTotal") != 0) { 
			$CORE_LOCAL->set("chargetender",1);
		} else {
			$CORE_LOCAL->set("chargetender",0);
		}

		$CORE_LOCAL->set("discounttotal",$headerRow["discountTTL"]);
		$CORE_LOCAL->set("memSpecial",$headerRow["memSpecial"]);

		$connect->close();

		$connID = Database::pDataConnect();
		$queryID = "select LastName,FirstName,Type,blueLine from custdata 
			where CardNo = '".$CORE_LOCAL->get("memberID")."' and personNum=1";
		$result = $connID->query($queryID);
		$row = $connID->fetch_array($result);

		// restore names for charge slips
		$CORE_LOCAL->set("lname",$row["LastName"]);
		$CORE_LOCAL->set("fname",$row["FirstName"]);

		if ($row["Type"] == "PC") {
			$CORE_LOCAL->set("isMember",1);
		}
		else {
			$CORE_LOCAL->set("isMember",0);
		}
		$CORE_LOCAL->set("memMsg",$row["blueLine"]);
	
		$connID->close();

		if ($CORE_LOCAL->get("isMember") == 1) {
			$CORE_LOCAL->set("yousaved",number_format( $CORE_LOCAL->get("transDiscount") + $CORE_LOCAL->get("discounttotal") + $CORE_LOCAL->get("memSpecial"), 2));
			$CORE_LOCAL->set("couldhavesaved",0);
			$CORE_LOCAL->set("specials",number_format($CORE_LOCAL->get("discounttotal") + $CORE_LOCAL->get("memSpecial"), 2));
		}
		else {
			$dblyousaved = number_format($CORE_LOCAL->get("memSpecial"), 2);
			$CORE_LOCAL->set("yousaved",$CORE_LOCAL->get("discounttotal"));
			$CORE_LOCAL->set("couldhavesaved",number_format($CORE_LOCAL->get("memSpecial"), 2));
			$CORE_LOCAL->set("specials",$CORE_LOCAL->get("discounttotal"));
		}


		// call to transLog, the body of the receipt comes from the view 'receipt'
		$receipt = $title.self::printReceiptHeader($dateTimeStamp, $ref);
		
		$receipt .= self::receiptDetail(True,$ref);

		// The Nitty Gritty:
		$member = "Member ".trim($CORE_LOCAL->get("memberID"));
		// if ($member == 0) $member = $CORE_LOCAL->get("defaultNonMem");
		$your_discount = $CORE_LOCAL->get("transDiscount");

		if ($CORE_LOCAL->get("transDiscount") + $CORE_LOCAL->get("specials") > 0) {
			$receipt .= "\n".self::centerString("------------------ YOUR SAVINGS -------------------")."\n";

			if ($your_discount > 0) {
				$receipt .= "    DISCOUNTS: $".number_format($your_discount, 2)."\n";
			}

			if ($CORE_LOCAL->get("specials") > 0) {
				$receipt .= "    SPECIALS: $".number_format($CORE_LOCAL->get("specials"), 2)."\n";
			}

			$receipt .= self::centerString("---------------------------------------------------")."\n";
		}
		$receipt .= "\n";
	
		if (trim($CORE_LOCAL->get("memberID")) != $CORE_LOCAL->get("defaultNonMem")) {
			$receipt .= self::centerString("Thank You - ".$member)."\n";
		}
		else {
			$receipt .= self::centerString("Thank You!")."\n";
		}

		if ($CORE_LOCAL->get("yousaved") > 0) {
			$receipt .= self::centerString("You Saved $".number_format($CORE_LOCAL->get("yousaved"), 2))."\n";
		}

		if ($CORE_LOCAL->get("couldhavesaved") > 0 && $CORE_LOCAL->get("yousaved") > 0) {
			$receipt .= self::centerString("You could have saved an additional $"
				    .number_format($CORE_LOCAL->get("couldhavesaved"), 2))."\n";
		}
		elseif ($CORE_LOCAL->get("couldhavesaved") > 0) {
			$receipt .= self::centerString("You could have saved $"
				    .number_format($CORE_LOCAL->get("couldhavesaved"), 2))."\n";
		}

		for ($i = 1; $i <= $CORE_LOCAL->get("receiptFooterCount"); $i++){
			$receipt .= self::$PRINT_OBJ->centerString($CORE_LOCAL->get("receiptFooter$i"));
			$receipt .= "\n";
		}


		if ($CORE_LOCAL->get("chargetender") != 0 ) {			// apbw 03/10/05 Reprint patch
			$receipt = $receipt.self::printChargeFooterStore($dateTimeStamp, $ref);	// apbw 03/10/05 Reprint patch
		}			// apbw 03/10/05 Reprint patch

		$receipt .= self::printGCSlip($dateTimeStamp, $ref, true, 1);
		$receipt .= self::printCCSigSlip($dateTimeStamp, $ref, False, 1);
	
		$receipt = $receipt."\n\n\n\n\n\n\n";			// apbw 03/10/05 Reprint patch
		self::writeLine($receipt.chr(27).chr(105));			// apbw 03/10/05 Reprint patch
		$receipt = "";			// apbw 03/10/05 Reprint patch

		$CORE_LOCAL->set("memMsg","");
		$CORE_LOCAL->set("memberID","0");
		$CORE_LOCAL->set("percentDiscount",0);
	}
}

/**
  Check whether drawer should open on this transaction
  @return
   - 1 open drawer
   - 0 do not open
  @deprecated use Kicker modules
*/
static public function setDrawerKick()

{
	global $CORE_LOCAL;

//	this, the simplest version, kicks the drawer for every tender *except* staff charge & business charge (MI, CX)
// 	apbw 05/03/05 KickFix added !=0 criteria

	if ($CORE_LOCAL->get("chargeTotal") == $CORE_LOCAL->get("tenderTotal") && $CORE_LOCAL->get("chargeTotal") != 0 && $CORE_LOCAL->get("tenderTotal") != 0 ) {	
		if (in_array($CORE_LOCAL->get("TenderType"),$CORE_LOCAL->get("DrawerKickMedia"))) {
			return 1;
		} else {
			//$_SESSION["kick"] = 0; 						
			return 0;
		}
	} else {						
		//$_SESSION["kick"] = 1;	
		return 1;
	}							
}

/**
  Variant check for when to open cash drawer
  @return
   - 1 open drawer
   - 0 do not open

  Opens on cash transactions, credit card
  transactions > $25, and stamp sales.

  @deprecated use Kicker modules
*/
static public function setDrawerKickLater()

{

// 	this more complex version can be modified to kick the drawer under whatever circumstances the FE Mgr sees fit
//	it currently kicks the drawer *only* for cash in & out
//	and credit card - andy
 

	$db = Database::tDataConnect();

	$query = "select * from localtemptrans where (trans_subtype = 'CA' and total <> 0) or (trans_subtype = 'CC' AND (total < -25 or total > 0)) or upc='0000000001065'";

	$result = $db->query($query);
	$num_rows = $db->num_rows($result);
	$row = $db->fetch_array($result);

	if ($num_rows != 0) {
	 //$_SESSION["kick"] = 1;
	 return 1;
	} else {
	//$_SESSION["kick"] = 0;
	 return 0;
	}

}

}

?>
