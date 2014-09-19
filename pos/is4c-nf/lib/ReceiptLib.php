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

        $printer_port = $CORE_LOCAL->get('printerPort');
        if (substr($printer_port, 0, 6) == "tcp://") {
            self::printToServer(substr($printer_port, 6), $text);
        } else {
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
}

/**
  Write text to server via TCP socket
  @param $print_server [string] host or host:port
  @param $text [string] text to print
  @return
   - [int]  1 => success
   - [int]  0 => problem sending text
   - [int] -1 => sent but no response. printer might be stuck/blocked
*/
static public function printToServer($printer_server, $text)
{
    $port = 9450;
    if (strstr($printer_server, ':')) {
        list($printer_server, $port) = explode(':', $printer_server, 2);
    }
    if (!function_exists('socket_create')) {
        return 0;
    }

    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if ($socket === false) {
        return 0;
    }

    socket_set_block($socket);
    socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 1, 'usec' => 0)); 
    socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 2, 'usec' => 0)); 
    if (!socket_connect($socket, $printer_server, $port)) {
        return false;
    }

    $send_failed = false;
    while(true) {
        $num_written = socket_write($socket, $text);
        if ($num_written === false) {
            // error occurred
            $send_failed = true;
            break; 
        }

        if ($num_written >= strlen($text)) {
            // whole message has been sent
            // send ETX to signal message complete
            socket_write($socket, chr(0x3));
            break;
        } else {
            $text = substr($text, $num_written);
        }
    }

    $ack = socket_read($socket, 3);
    socket_close($socket);

    if ($send_failed) {
        return 0;
    } else if ($ack === false) {
        return -1;
    } else {
        return 1;
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
    // do not open drawer on non-local requests
    // this may not work on all web servers or 
    // network configurations
    /**
      30Apr14 - Does not work correctly
      Needs more investigation. IPv6 maybe?
    if (isset($_SERVER) && isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
        return;
    }
    */
	$pin = self::currentDrawer();
	if ($pin == 1)
		self::writeLine(chr(27).chr(112).chr(0).chr(48)."0");
	elseif ($pin == 2)
		self::writeLine(chr(27).chr(112).chr(1).chr(48)."0");
	//self::writeLine(chr(27).chr(112).chr(48).chr(55).chr(121));
}

/**
  Which drawer is currently in use
  @return
    1 - Use the first drawer
    2 - Use the second drawer
    0 - Current cashier has no drawer

  This always returns 1 when dual drawer mode
  is enabled. Assignments in the table aren't
  relevant.
*/
static public function currentDrawer(){
	global $CORE_LOCAL;
	if ($CORE_LOCAL->get('dualDrawerMode') !== 1) return 1;
	$db = Database::pDataConnect();
	$chkQ = 'SELECT drawer_no FROM drawerowner WHERE emp_no='.$CORE_LOCAL->get('CashierNo');
	$chkR = $db->query($chkQ);
	if ($db->num_rows($chkR) == 0) return 0;
	else return array_pop($db->fetch_row($chkR));
}

/**
  Assign drawer to cashier
  @param $emp the employee number
  @param $num the drawer number
  @return success True/False
*/
static public function assignDrawer($emp,$num){
	$db = Database::pDataConnect();
	$upQ = sprintf('UPDATE drawerowner SET emp_no=%d WHERE drawer_no=%d',$emp,$num);
	$upR = $db->query($upQ);
	return ($upR !== False) ? True : False;
}

/**
  Unassign drawer
  @param $num the drawer number
  @return success True/False
*/
static public function freeDrawer($num){
	$db = Database::pDataConnect();
	$upQ = sprintf('UPDATE drawerowner SET emp_no=NULL WHERE drawer_no=%d',$num);
	$upR = $db->query($upQ);
	return ($upR !== False) ? True : False;
}

/**
  Get list of available drawers
  @return array of drawer numbers
*/
static public function availableDrawers(){
	global $CORE_LOCAL;
	$db = Database::pDataConnect();
	$q = 'SELECT drawer_no FROM drawerowner WHERE emp_no IS NULL ORDER BY drawer_no';
	$r = $db->query($q);
	$ret = array();
	while($w = $db->fetch_row($r))
		$ret[] = $w['drawer_no'];
	return $ret;
}

// -------------------------------------------------------------
static public function printReceiptHeader($dateTimeStamp, $ref) {
	global $CORE_LOCAL;

	$receipt = self::$PRINT_OBJ->TextStyle(True);
	$img_cache = $CORE_LOCAL->get('ImageCache');
	if (!is_array($img_cache)) $img_cache = array();

	for ($i=1; $i <= $CORE_LOCAL->get("receiptHeaderCount"); $i++){

		/**
		  If the receipt header line is a .bmp file (and it exists),
		  print it on the receipt. Otherwise just print the line of
		  text centered.
		*/
		$headerLine = $CORE_LOCAL->get("receiptHeader".$i);
		$graphics_path = MiscLib::base_url().'graphics';
		if (substr($headerLine,-4) == ".bmp" && file_exists($graphics_path.'/'.$headerLine)){
			// save image bytes in cache so they're not recalculated
			// on every receipt
			$img_file = $graphics_path.'/'.$headerLine;
			if (isset($img_cache[basename($img_file)]) && !empty($img_cache[basename($img_file)]) && get_class(self::$PRINT_OBJ)!='EmailPrintHandler'){
				$receipt .= $img_cache[basename($img_file)]."\n";
			}
			else {
				$img = self::$PRINT_OBJ->RenderBitmapFromFile($img_file);
				$receipt .= $img."\n";
				$img_cache[basename($img_file)] = $img;
				$CORE_LOCAL->set('ImageCache',$img_cache);
				$receipt .= "\n";
			}
		}
		else {
			$bold = ($i==1) ? True : False;
			$receipt .= self::$PRINT_OBJ->centerString($CORE_LOCAL->get("receiptHeader$i"), $bold);
			$receipt .= "\n";
		}
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
//#'C - is this never called?
static public function printChargeFooterCust($dateTimeStamp, $ref, $program="charge") {	// apbw 2/14/05 SCR
	global $CORE_LOCAL;

	$chgName = self::getChgName();			// added by apbw 2/14/05 SCR

	$date = self::build_time($dateTimeStamp);

	/* Where should the label values come from, be entered?

       24Apr14 Andy
       Implementing these as ReceiptMessage subclasses might work
       better. Plugins could provide their own ReceiptMessage subclass
       with the proper labels (or config settings for the labels)
	*/
	$labels = array();
	$labels['charge'] = array("CUSTOMER CHARGE ACCOUNT\n", "Charge Amount:");
	$labels['coopcred'] = array("COOP CRED ACCOUNT\n", "Credit Amount:");
	$labels['debit'] = array("CUSTOMER DEBIT ACCOUNT\n", "Debit Amount:");
	/* Could append labels from other modules
	foreach (plugin as $CORE_LOCAL->get('plugins'))
		if isset($CORE_LOCAL Plugins[$plugin]['printChargeFooterCustLabels']
		if isset($plugin['printChargeFooterCustLabels']
			$labels[]=$plugin['printChargeFooterCustLabels']
	*/

	$receipt = chr(27).chr(33).chr(5)."\n\n\n".self::centerString("C U S T O M E R   C O P Y")."\n"
		   .self::centerString("................................................")."\n"
		   .self::centerString($CORE_LOCAL->get("chargeSlip1"))."\n\n"
		   . $labels["$program"][0]
		   ."Name: ".trim($chgName)."\n"		// changed by apbw 2/14/05 SCR
		   ."Member Number: ".trim($CORE_LOCAL->get("memberID"))."\n"
		   ."Date: ".$date."\n"
		   ."REFERENCE #: ".$ref."\n"
		   . $labels["$program"][1] . " $".number_format(-1 * $CORE_LOCAL->get("chargeTotal"), 2)."\n"
		   .self::centerString("................................................")."\n"
		   ."\n\n\n\n\n\n\n"
		   .chr(27).chr(105);

	return $receipt;

}

// Charge Footer split into two functions by apbw 2/1/05
//#'S
static public function printChargeFooterStore($dateTimeStamp, $ref, $program="charge") {	// apbw 2/14/05 SCR
	global $CORE_LOCAL;

	
	$chgName = self::getChgName();			// added by apbw 2/14/05 SCR
	
	$date = self::build_time($dateTimeStamp);

	/* Where should the label values come from, be entered?

       24Apr14 Andy
       Implementing these as ReceiptMessage subclasses might work
       better. Plugins could provide their own ReceiptMessage subclass
       with the proper labels (or config settings for the labels)
	*/
	$labels = array();
	$labels['charge'] = array("CUSTOMER CHARGE ACCOUNT\n"
			, "Charge Amount:"
			, "I AGREE TO PAY THE ABOVE AMOUNT\n"
			, "TO MY CHARGE ACCOUNT\n"
	);
	$labels['coopcred'] = array("COOP CRED ACCOUNT\n"
			, "Debit Amount:"
			, "I ACKNOWLEDGE THE ABOVE DEBIT\n"
			, "TO MY COOP CRED ACCOUNT\n"
	);
	$labels['debit'] = array("CUSTOMER DEBIT ACCOUNT\n"
			, "Debit Amount:"
			, "I ACKNOWLEDGE THE ABOVE DEBIT\n"
			, "TO MY DEBIT ACCOUNT\n"
	);

	/* Could append labels from other modules
	foreach (plugin as $CORE_LOCAL->get('plugins'))
		if isset($CORE_LOCAL Plugins[$plugin]['printChargeFooterCustLabels']
		if isset($plugin['printChargeFooterCustLabels']
			$labels[]=$plugin['printChargeFooterCustLabels']
	*/

	$receipt = "\n\n\n\n\n\n\n"
		   .chr(27).chr(105)
		   .chr(27).chr(33).chr(5)		// apbw 3/18/05 
		   ."\n".self::centerString($CORE_LOCAL->get("chargeSlip2"))."\n"
		   .self::centerString("................................................")."\n"
		   .self::centerString($CORE_LOCAL->get("chargeSlip1"))."\n\n"
		   . $labels["$program"][0]
		   ."Name: ".trim($chgName)."\n"		// changed by apbw 2/14/05 SCR
		   ."Member Number: ".trim($CORE_LOCAL->get("memberID"))."\n"
		   ."Date: ".$date."\n"
		   ."REFERENCE #: ".$ref."\n"
		   .$labels["$program"][1] . " $".number_format(-1 * $CORE_LOCAL->get("chargeTotal"), 2)."\n"
		   . $labels["$program"][2]
		   . $labels["$program"][3]
		   ."Purchaser Sign Below\n\n\n"
		   ."X____________________________________________\n"
		   .$CORE_LOCAL->get("fname")." ".$CORE_LOCAL->get("lname")."\n\n"
		   .self::centerString(".................................................")."\n\n";

	return self::chargeBalance($receipt, $program, $ref);

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

static public function frank($amount) {
	global $CORE_LOCAL;

	$date = strftime("%m/%d/%y %I:%M %p", time());
	$ref = trim($CORE_LOCAL->get("memberID"))." ".trim($CORE_LOCAL->get("CashierNo"))." ".trim($CORE_LOCAL->get("laneno"))." ".trim($CORE_LOCAL->get("transno"));
	$tender = "AMT: ".MiscLib::truncate2($amount)."  CHANGE: ".MiscLib::truncate2($CORE_LOCAL->get("change"));
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

static public function frankgiftcert($amount) {
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
	$output .= "$".MiscLib::truncate2($amount);
	self::endorse($output); 

}

// -----------------------------------------------------

static public function frankstock($amount) {
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
		$output .= "Stock Payment $".$amount." ref: ".$ref."   ".$time_now; // apbw 3/24/05 Wedge Printer Swap Patch
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
static public function chargeBalance($receipt, $program="charge", $trans_num=''){
	global $CORE_LOCAL;
	PrehLib::chargeOK();
	/*
	Should be checking a lane version of: $FANNIE_AR_DEPARTMENTS = '1005 1010'
	Should be checking: $CORE_LOCAL->get('defaultNonMem'), not 11
	*/
	$labels = array();
	$labels['charge'] = array("Current IOU Balance:"
			, 1
	);
	$labels['coopcred'] = array("Coop Cred Available:"
			, -1
	);
	$labels['debit'] = array("Debit available:"
			, -1
	);

	$db = Database::tDataConnect();
    list($emp, $reg, $trans) = explode('-', $trans_num, 3);
	$checkQ = "SELECT trans_id 
               FROM localtranstoday 
               WHERE (department=990 or trans_subtype='MI')
                AND emp_no=" . ((int)$emp) . "
                AND register_no=" . ((int)$reg) . "
                AND trans_no=" . ((int)$trans);
	$checkR = $db->query($checkQ);
	$num_rows = $db->num_rows($checkR);

	$currActivity = $CORE_LOCAL->get("memChargeTotal");
	$currBalance = $CORE_LOCAL->get("balance") - $currActivity;
	
	if(($num_rows > 0 || $currBalance != 0) && $CORE_LOCAL->get("memberID") != 11){
 		$chargeString = $labels["$program"][0] ." $".sprintf("%.2f",($labels["$program"][1] * $currBalance));
		$receipt = $receipt."\n\n".self::biggerFont(self::centerBig($chargeString));
	}
	
	return $receipt;
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
		return trim($CORE_LOCAL->get("fname")) ." ". $LastInit;
	}
	else{
		return $CORE_LOCAL->get('memMsg');
	}
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
	$sort = "";

	if ( $rp != 0 ) {	// if this is a reprint of a previous transaction, loop through all cc slips for that transaction
		$db = Database::mDataConnect();
	} else {		// else if current transaction, just grab most recent 
		if ($storeCopy){
			$idclause = " and transID = ".$CORE_LOCAL->get("paycard_id");
		}
		$sort = " desc ";
		$db = Database::tDataConnect();
	}
	// query database for cc receipt info 
	$query = "select  tranType, amount, PAN, entryMethod, issuer, xResultMessage, xApprovalNumber, xTransactionID, name, "
		." datetime from ccReceiptView where date=".date('Ymd',$dateTimeStamp)
		." and cashierNo = ".$emp." and laneNo = ".$reg
		." and transNo = ".$trans ." ".$idclause
		." order by datetime, cashierNo, laneNo, transNo, xTransactionID, transID ".$sort.", sortorder ".$sort;
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
		$bmp->load($sig_file);

		$bmpData = $bmp->getRawData();
		$bmpWidth = $bmp->getWidth();
		$bmpHeight = $bmp->getHeight();
		$bmpRawBytes = (int)(($bmpWidth + 7)/8);

		$print_class = $CORE_LOCAL->get('ReceiptDriver');
		if ($print_class === '' || !class_exists($print_class))
			$print_class = 'ESCPOSPrintHandler';
		$printer = new $print_class();
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
static public function bold()
{
    global $CORE_LOCAL;
    if (!is_object(self::$PRINT_OBJ)) {
        $print_class = $CORE_LOCAL->get('ReceiptDriver');
        if ($print_class === '' || !class_exists($print_class))
            $print_class = 'ESCPOSPrintHandler';
        self::$PRINT_OBJ = new $print_class();
    }
    
    return self::$PRINT_OBJ->TextStyle(true, true);
}
static public function unbold()
{
    global $CORE_LOCAL;
    if (!is_object(self::$PRINT_OBJ)) {
        $print_class = $CORE_LOCAL->get('ReceiptDriver');
        if ($print_class === '' || !class_exists($print_class))
            $print_class = 'ESCPOSPrintHandler';
        self::$PRINT_OBJ = new $print_class();
    }
    
    return self::$PRINT_OBJ->TextStyle(true, false);
}

static public function localTTL(){
	global $CORE_LOCAL;

	if ($CORE_LOCAL->get("localTotal") == 0) return "";

	$str = sprintf("LOCAL PURCHASES = \$%.2f",
		$CORE_LOCAL->get("localTotal"));
	return $str."\n";
}

static public function graphedLocalTTL(){
	global $CORE_LOCAL;
	$db = Database::tDataConnect();

	$lookup = "SELECT 
		SUM(CASE WHEN p.local=1 THEN l.total ELSE 0 END) as localTTL,
		SUM(CASE WHEN l.trans_type IN ('I','D') then l.total ELSE 0 END) as itemTTL
		FROM localtemptrans AS l LEFT JOIN ".
		$CORE_LOCAL->get('pDatabase').$db->sep()."products AS p
		ON l.upc=p.upc
		WHERE l.trans_type IN ('I','D')";
	$lookup = $db->query($lookup);
	if ($db->num_rows($lookup) == 0)
		return '';
	$row = $db->fetch_row($lookup);
	if ($row['localTTL'] == 0) 
		return '';

	$percent = ((float)$row['localTTL']) / ((float)$row['itemTTL']);
	$str = sprintf('LOCAL PURCHASES = $%.2f (%.2f%%)', 
			$row['localTTL'], 100*$percent);
	$str .= "\n";

	$str .= self::$PRINT_OBJ->RenderBitmap(Bitmap::barGraph($percent), 'L');
	return $str."\n";
}

static public function receiptFromBuilders($reprint=False,$trans_num=''){
	global $CORE_LOCAL;

	$empNo=0;$laneNo=0;$transNo=0;
    list($empNo, $laneNo, $transNo) = explode('-', $trans_num, 3);

	$FETCH_MOD = $CORE_LOCAL->get("RBFetchData");
	if($FETCH_MOD=="") $FETCH_MOD = "DefaultReceiptDataFetch";
	$mod = new $FETCH_MOD();
	$data = array();
    $data = $mod->fetch($empNo,$laneNo,$transNo);

	// load module configuration
	$FILTER_MOD = $CORE_LOCAL->get("RBFilter");
	if($FILTER_MOD=="") $FILTER_MOD = "DefaultReceiptFilter";
	$SORT_MOD = $CORE_LOCAL->get("RBSort");
	if($SORT_MOD=="") $SORT_MOD = "DefaultReceiptSort";
	$TAG_MOD = $CORE_LOCAL->get("RBTag");
	if($TAG_MOD=="") $TAG_MOD = "DefaultReceiptTag";

	$f = new $FILTER_MOD();
	$recordset = $f->filter($data);

	$s = new $SORT_MOD();
	$recordset = $s->sort($recordset);

	$t = new $TAG_MOD();
	$recordset = $t->tag($recordset);

	$ret = "";
	foreach($recordset as $record){
		$class_name = $record['tag'].'ReceiptFormat';
		if (!class_exists($class_name)) continue;
		$obj = new $class_name();

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

static public function receiptDetail($reprint=false, $trans_num='') { 
    // put into its own function to make it easier to follow, and slightly 
    // modified for wider-spread use of joe's "new" receipt format --- apbw 7/3/2007
	global $CORE_LOCAL;

	if ($CORE_LOCAL->get("newReceipt") == 2) {
		return self::receiptFromBuilders($reprint, $trans_num);
    }

	$detail = "";
	$empNo=0; $laneNo=0; $transNo=0;
    list($empNo, $laneNo, $transNo) = explode('-', $trans_num, 3);
		
	if ($CORE_LOCAL->get("newReceipt") == 0 ) {
		// if old style has been specifically requested 
		// for a partial or reprint, use old format
        $query = "select linetoprint from rp_receipt
            where emp_no=$empNo and register_no=$laneNo
            and trans_no=$transNo order by trans_id";
		$db = Database::tDataConnect();
		$result = $db->query($query);
		$num_rows = $db->num_rows($result);
		// loop through the results to generate the items listing.
		for ($i = 0; $i < $num_rows; $i++) {
			$row = $db->fetch_array($result);
			$detail .= $row[0]."\n";
		}
	} else { 
		$db = Database::tDataConnect();

		// otherwise use new format 
        $query = "select linetoprint,sequence,dept_name,ordered, 0 as ".
                $db->identifier_escape('local')
            ." from rp_receipt_reorder_unions_g where emp_no=$empNo and "
            ." register_no=$laneNo and trans_no=$transNo "
            ." order by ordered,dept_name, " 
            ." case when ordered=4 then '' else upc end, "
                .$db->identifier_escape('sequence');

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
				$queryExempt="select ".$db->concat(
				"right(".$db->concat('space(44)',"'SUBTOTAL'",'').", 44)",
				"right(".$db->concat('space(8)',$db->convert('runningTotal-tenderTotal','char'),'').", 8)", 
				"space(4)",'')." as linetoprint,
				1 as sequence,null as dept_name,3 as ordered,'' as upc
				from lttsummary";
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
	$db = Database::tDataConnect();
	$order = "";
	$where = $db->identifier_escape('date')."=".date('Ymd',$dateTimeStamp)
		." AND cashierNo=".$emp." AND laneNo=".$reg." AND transNo=".$trans;
	if( $rp == 0) {
		$order = " desc";
		$where .= " AND transID=".$CORE_LOCAL->get("paycard_id");
	}
	$sql = "SELECT * FROM gcReceiptView WHERE ".$where." ORDER BY "
		.$db->identifier_escape('datetime').$order.", sortorder".$order;
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

		if ($rp == 0) break; // easier that row-limiting the query
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
  @param $ref string transaction identifier
  @param $second boolean indicating it's a second receipt
  @param $email generate email-style receipt
  @return string receipt content
*/
//#'P
static public function printReceipt($arg1, $ref, $second=False, $email=False) {
	global $CORE_LOCAL;

	if($second) $email = False; // store copy always prints
	if($arg1 != "full") $email = False;

	$dateTimeStamp = time();

	$reprint = $arg1 == 'reprint' ? true : false;
    $emp=$reg=$trans=0;
    if (strstr($ref, '-')) {
        list($emp, $reg, $trans) = explode('-', $ref, 3);
    } else if (strstr($ref, '::')) {
        // values in different order; rebuild correct $ref
        list($reg, $emp, $trans) = explode('::', $ref, 3);
		$ref = sprintf('%d-%d-%d',$emp,$reg,$trans);
    } else {
        list($emp, $reg, $trans) = explode('-', self::mostRecentReceipt(), 3);
    }
    $where = sprintf('emp_no=%d AND register_no=%d AND trans_no=%d',
                    $emp, $reg, $trans);

	/**
	  This block deprecates ReceiptLib::reprintReceipt()
	*/
	if ($reprint) {
		$arg1 = 'full';
		$email = false;
		$second = false;

		// lookup trans information
		$queryHeader = "select * from rp_receipt_header where " . $where;
		$db = Database::tDataConnect();
		$header = $db->query($queryHeader);
		$row = $db->fetch_row($header);
		$dateTimeStamp = $row["dateTimeStamp"];
		$dateTimeStamp = strtotime($dateTimeStamp);
		
		// set session variables from trans information
		$CORE_LOCAL->set("memberID",$row["memberID"]);
		$CORE_LOCAL->set("memCouponTLL",$row["couponTotal"]);
		$CORE_LOCAL->set("transDiscount",$row["transDiscount"]);
		$CORE_LOCAL->set("chargeTotal",-1*$row["chargeTotal"]);
		$CORE_LOCAL->set("discounttotal",$row["discountTTL"]);
		$CORE_LOCAL->set("memSpecial",$row["memSpecial"]);

		// lookup member info
		$db = Database::pDataConnect();
		$queryID = "select LastName,FirstName,Type,blueLine from custdata 
			where CardNo = '".$CORE_LOCAL->get("memberID")."' and personNum=1";
		$result = $db->query($queryID);
		$row = $db->fetch_array($result);

		// set session variables from member info
		$CORE_LOCAL->set("lname",$row["LastName"]);
		$CORE_LOCAL->set("fname",$row["FirstName"]);
		$CORE_LOCAL->set('isMember', ($row['Type']=='PC' ? 1 : 0));
		$CORE_LOCAL->set("memMsg",$row["blueLine"]);
		if ($CORE_LOCAL->get("isMember") == 1) {
			$CORE_LOCAL->set("yousaved",number_format( $CORE_LOCAL->get("transDiscount") 
					+ $CORE_LOCAL->get("discounttotal") + $CORE_LOCAL->get("memSpecial"), 2));
			$CORE_LOCAL->set("couldhavesaved",0);
			$CORE_LOCAL->set("specials",number_format($CORE_LOCAL->get("discounttotal") 
					+ $CORE_LOCAL->get("memSpecial"), 2));
		} else {
			$CORE_LOCAL->set("yousaved",$CORE_LOCAL->get("discounttotal"));
			$CORE_LOCAL->set("couldhavesaved",number_format($CORE_LOCAL->get("memSpecial"), 2));
			$CORE_LOCAL->set("specials",$CORE_LOCAL->get("discounttotal"));
		}
	}

	/*#'Q chargeProgram - where to get it or how to decide
		Since a member can only be in one program this could work
		  if made part of the session setup, when ID established.
		   base on shrinkRange (99900-99998, which is also a plugin var
		$chargeProgram = $CORE_LOCAL->get('chargeProgram');
		if (!isset($chargeProgram) $chargeProgram = 'charge';
	*/
	if ($CORE_LOCAL->get("store") == 'WEFC_Toronto' && $CORE_LOCAL->get("memberID") < 99900) {
		$chargeProgram = 'coopcred';
	} else {
		$chargeProgram = 'charge';
	}

	$print_class = $CORE_LOCAL->get('ReceiptDriver');
	if ($print_class === '' || !class_exists($print_class)) {
		$print_class = 'ESCPOSPrintHandler';
    }
	self::$PRINT_OBJ = new $print_class();
	$receipt = "";

	$noreceipt = ($CORE_LOCAL->get("receiptToggle")==1 ? 0 : 1);
	$ignoreNR = array("ccSlip");

	// find receipt types provided via modules
	$message_mods = $CORE_LOCAL->get('ReceiptMessageMods');
	if (!is_array($message_mods)) $message_mods = array();
	$type_map = array();
	foreach($message_mods as $class){
		if (!class_exists($class)) continue;
		$obj = new $class();
		if ($obj->standalone_receipt_type != '')
			$type_map[$obj->standalone_receipt_type] = $obj;
	}

	if ($noreceipt != 1 || in_array($arg1,$ignoreNR) || $email){
		$receipt = self::printReceiptHeader($dateTimeStamp, $ref);

		if ($second) {
			$ins = self::$PRINT_OBJ->centerString("( S T O R E   C O P Y )")."\n";
			$receipt = substr($receipt,0,3).$ins.substr($receipt,3);
		} else if ($reprint !== false) {
			$ins = self::$PRINT_OBJ->centerString("***   R E P R I N T   ***")."\n";
			$receipt = substr($receipt,0,3).$ins.substr($receipt,3);
		}

		if ($arg1 == "full") {
			$receipt = array('any'=>'','print'=>'');
			if ($email) self::$PRINT_OBJ = new EmailPrintHandler();
			$receipt['any'] = self::printReceiptHeader($dateTimeStamp, $ref);

            $receipt['any'] .= self::receiptDetail($reprint, $ref);
			$member = trim($CORE_LOCAL->get("memberID"));
			$your_discount = $CORE_LOCAL->get("transDiscount");

			if ($CORE_LOCAL->get("transDiscount") + 
			   $CORE_LOCAL->get("specials") > 0 ) {
				$receipt['any'] .= 'TODAY YOU SAVED = $'.
					number_format($your_discount + $CORE_LOCAL->get("specials"),2).
					"\n";
			}
			$receipt['any'] .= self::localTTL();
			//$receipt['any'] .= self::graphedLocalTTL();
			$receipt['any'] .= "\n";
	
			if (trim($CORE_LOCAL->get("memberID")) != $CORE_LOCAL->get("defaultNonMem")) {
				if ($CORE_LOCAL->get("newReceipt")>=1){
					$receipt['any'] .= self::$PRINT_OBJ->TextStyle(True,False,True);
					$receipt['any'] .= self::$PRINT_OBJ->centerString("thank you - owner ".$member,True);
					$receipt['any'] .= self::$PRINT_OBJ->TextStyle(True);
					$receipt['any'] .= "\n\n";
				} else {
					$receipt['any'] .= self::$PRINT_OBJ->centerString("Thank You - member ".$member);
					$receipt['any'] .= "\n";
				}
			} else {
				if ($CORE_LOCAL->get("newReceipt")>=1){
					$receipt['any'] .= self::$PRINT_OBJ->TextStyle(True,False,True);
					$receipt['any'] .= self::$PRINT_OBJ->centerString("thank you",True);
					$receipt['any'] .= self::$PRINT_OBJ->TextStyle(True);
					$receipt['any'] .= "\n\n";
				} else {
					$receipt['any'] .= self::$PRINT_OBJ->centerString("Thank You!");
					$receipt['any'] .= "\n";
				}
			}

			for ($i = 1; $i <= $CORE_LOCAL->get("receiptFooterCount"); $i++){
				$receipt['any'] .= self::$PRINT_OBJ->centerString($CORE_LOCAL->get("receiptFooter$i"));
				$receipt['any'] .= "\n";
			}

			if ($CORE_LOCAL->get("store")=="wfc") {
				$refund_date = date("m/d/Y",mktime(0,0,0,date("n"),date("j")+30,date("Y")));
				$receipt['any'] .= self::$PRINT_OBJ->centerString("returns accepted with this receipt through ".$refund_date);
				$receipt['any'] .= "\n";
			}

			/***** CvR add charge total to receipt bottom ****/
			$receipt['any'] = self::chargeBalance($receipt['any'], $chargeProgram, $ref);
			/**** CvR end ****/

			// check if message mods have data
			// and add them to the receipt
			$db = Database::tDataConnect();
			$q = "SELECT ";
			$select_mods = array();
			foreach($message_mods as $class){
				if (!class_exists($class)) continue;
				$obj = new $class();
				$q .= $obj->select_condition().' AS '.$db->identifier_escape($class).',';
				$select_mods[$class] = $obj;
			}
			$q = rtrim($q,',');
			if (count($select_mods) > 0){
				$q .= ' FROM localtranstoday
                        WHERE ' . $where . '
                            AND datetime >= ' . $db->curdate();
				$r = $db->query($q);
				$row = array();
				if ($db->num_rows($r) > 0) $row = $db->fetch_row($r);
				foreach($select_mods as $class => $obj){
					if (!isset($row[$class])) continue;	
					if ($obj->paper_only)
						$receipt['print'] .= $obj->message($row[$class], $ref, $reprint);
					else
						$receipt['any'] .= $obj->message($row[$class], $ref, $reprint);
				}
			}

			if ($CORE_LOCAL->get('memberID') != $CORE_LOCAL->get('defaultNonMem'))
				$receipt['any'] .= self::memReceiptMessages($CORE_LOCAL->get("memberID"));
			$CORE_LOCAL->set("equityNoticeAmt",0);

			// knit pieces back together if not emailing
			if (!$email) $receipt = ''.$receipt['any'].$receipt['print'];

			$CORE_LOCAL->set("headerprinted",0);
		} else if (isset($type_map[$arg1])) {
			$obj = $type_map[$arg1];
			$receipt = $obj->standalone_receipt($ref, $reprint);
		} else if ($arg1 == "cab") {
			$ref = $CORE_LOCAL->get("cabReference");
			$receipt = self::printCabCoupon($dateTimeStamp, $ref);
			$CORE_LOCAL->set("cabReference","");
		} else {
			/***** jqh 09/29/05 if receipt isn't full, then display receipt in old style *****/
			$query="select linetoprint from rp_receipt WHERE " . $where . ' ORDER BY trans_id';
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
		
		} /***** jqh end big if statement change *****/
	}

	/* --------------------------------------------------------------
	  print store copy of charge slip regardless of receipt print setting - apbw 2/14/05 
	  ---------------------------------------------------------------- */
    $tmap = $CORE_LOCAL->get('TenderMap');
    // skip signature slips if using electronic signature capture (unless it's a reprint)
    if ((is_array($tmap) && isset($tmap['MI']) && $tmap['MI'] != 'SignedStoreChargeTender') || $reprint) {
        if ($CORE_LOCAL->get("chargeTotal") != 0 && (($CORE_LOCAL->get("End") == 1 && !$second) || $reprint)) {
            if (is_array($receipt)) {
                $receipt['print'] .= self::printChargeFooterStore($dateTimeStamp, $ref);
            } else {
                $receipt .= self::printChargeFooterStore($dateTimeStamp, $ref);
            }
        }
    }
			
	if (is_array($receipt)){
		if ($second){
			// second always prints
			$receipt['print'] = $receipt['any'].$receipt['print'];
			$receipt['any'] = '';
		}
		if ($receipt['print'] !== ''){
			$receipt['print'] = $receipt['print']."\n\n\n\n\n\n\n";
			$receipt['print'] .= chr(27).chr(105);
		}
	}
	elseif ($receipt !== ""){
		$receipt = $receipt."\n\n\n\n\n\n\n";
		$receipt .= chr(27).chr(105);
	}
	
	if (!in_array($arg1,$ignoreNR))
		$CORE_LOCAL->set("receiptToggle",1);
	if ($reprint){
		$CORE_LOCAL->set("memMsg","");
		$CORE_LOCAL->set("memberID","0");
		$CORE_LOCAL->set("percentDiscount",0);
		$CORE_LOCAL->set('isMember', 0);
	}
	return $receipt;
}

static public function reprintReceipt($trans_num=""){
	global $CORE_LOCAL;

	$print_class = $CORE_LOCAL->get('ReceiptDriver');
	if ($print_class === '' || !class_exists($print_class))
		$print_class = 'ESCPOSPrintHandler';
	self::$PRINT_OBJ = new $print_class();

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

		$CORE_LOCAL->set("discounttotal",$headerRow["discountTTL"]);
		$CORE_LOCAL->set("memSpecial",$headerRow["memSpecial"]);

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


		if ($CORE_LOCAL->get("chargeTotal") != 0 ) {			// apbw 03/10/05 Reprint patch
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
		$CORE_LOCAL->set('isMember', 0);
	}
}

static public function memReceiptMessages($card_no){
	$db = Database::pDataConnect();
	$q = "SELECT msg_text,modifier_module FROM custReceiptMessage WHERE card_no=".$card_no;
	$r = $db->query($q);
	$ret = "";
	while($w = $db->fetch_row($r)){
		if (file_exists(dirname(__FILE__).'/ReceiptBuilding/custMessages/'.$w['modifier_module'].'.php')){
			$class_name = $w['modifier_module'];
			if (!class_exists($class_name)){
				include(dirname(__FILE__).'/ReceiptBuilding/custMessages/'.$class_name.'.php');
			}
			$obj = new $class_name();
			$ret .= $obj->message($w['msg_text']);
		}
		else {
			$ret .= $w['msg_text']."\n";
		}
	}
	return $ret;
}

static public function shutdownFunction()
{
    $error = error_get_last(); 
    if ($error !== null && $error['type'] == 1) {
        // fatal error occurred
        ob_end_clean();

        echo '{ "error" : "Printer is not responding" }';
    }
}

/**
  get current receipt number
*/
static public function receiptNumber()
{
    global $CORE_LOCAL;

    return $CORE_LOCAL->get('CashierNo')
           . '-'
           . $CORE_LOCAL->get('laneno')
           . '-'
           . $CORE_LOCAL->get('transno');
}

/**
  Get most recent receipt number
*/
static public function mostRecentReceipt()
{
    global $CORE_LOCAL;
    $dbc = Database::tDataConnect();
    $query = "SELECT emp_no, register_no, trans_no
              FROM localtranstoday 
              ORDER BY datetime DESC";
    $query = $dbc->addSelectLimit($query, 1);
    $result = $dbc->query($query);
    if ($dbc->num_rows($result) == 0) {
        return false;
    } else {
        $row = $dbc->fetch_array($result);
        return $row['emp_no'] . '-' . $row['register_no'] . '-' . $row['trans_no'];
    }
}

}

?>
