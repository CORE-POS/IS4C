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

if (!function_exists("getChgName")) include("chgName.php");		// added by apbw 2/14/05 SCR
if (!function_exists("truncate2")) include_once("lib.php");		// 3/24/05 apbw Wedge Printer Swap Patch

// --------------------------------------------------------------
function build_time($timestamp) {

	return strftime("%m/%d/%y %I:%M %p", $timestamp);
}
// --------------------------------------------------------------
function centerString($text) {

		return center($text, 59);
}
// --------------------------------------------------------------
function writeLine($text) {

	if ($_SESSION["print"] != 0) {

	$fp = fopen($_SESSION["printerPort"], "w");
	fwrite($fp, $text);
	fclose($fp);
	}
}
// --------------------------------------------------------------
function center_check($text) {

//	return str_repeat(" ", 22).center($text, 60);	// apbw 03/24/05 Wedge printer swap patch
	return center($text, 60);				// apbw 03/24/05 Wedge printer swap patch
}

// --------------------------------------------------------------
// concatenated by tt/apbw 3/16/05 old wedge printer Franking Patch II

function endorse($text) {

	writeLine(chr(27).chr(64).chr(27).chr(99).chr(48).chr(4)  	
	// .chr(27).chr(33).chr(10)
	.$text
	.chr(27).chr(99).chr(48).chr(1)
	.chr(12)
	.chr(27).chr(33).chr(5));
}
// -------------------------------------------------------------

function center($text, $linewidth) {
	$blank = str_repeat(" ", 59);
	$text = trim($text);
	$lead = (int) (($linewidth - strlen($text)) / 2);
	$newline = substr($blank, 0, $lead).$text;
	return $newline;
}
// -------------------------------------------------------------
function drawerKick() {

		writeLine(chr(27).chr(112).chr(0).chr(48)."0");
}

// -------------------------------------------------------------
function cutreceipt() {

		writeLine(chr(27).chr(105));
}
// -------------------------------------------------------------
function printReceiptHeader($dateTimeStamp, $ref) {

//	writeLine(chr(27).chr(33).chr(5)); // Compress font	// apbw/tt old Wedge receipt printers Franking II

	$receipt = ""
		.chr(27).chr(33).chr(5)
	 	.centerString($_SESSION["receiptHeader1"])."\n"
		.centerString($_SESSION["receiptHeader2"])."\n"
		.centerString($_SESSION["receiptHeader3"])."\n"
		.centerString(build_time($dateTimeStamp)."     ".$ref)."\n"
		.centerString("Cashier: ".$_SESSION["cashier"])."\n"
		."\n\n";

	
	return $receipt;

}
// -------------------------------------------------------------
function receiptFooter() {

}

// -------------------------------------------------------------
function promoMsg() {

}

// Charge Footer split into two functions by apbw 2/1/05

function printChargeFooterCust($dateTimeStamp, $ref) {	// apbw 2/14/05 SCR

	$chgName = getChgName();			// added by apbw 2/14/05 SCR

	$date = build_time($dateTimeStamp);

	$receipt = chr(27).chr(33).chr(5)."\n\n\n".centerString("C U S T O M E R   C O P Y")."\n"
		   .centerString("................................................")."\n"
		   .centerString($_SESSION["chargeSlip1"])."\n\n"
		   ."CUSTOMER CHARGE ACCOUNT\n"
		   ."Name: ".trim($_SESSION["ChgName"])."\n"		// changed by apbw 2/14/05 SCR
		   ."Member Number: ".trim($_SESSION["memberID"])."\n"
		   ."Date: ".$date."\n"
		   ."REFERENCE #: ".$ref."\n"
		   ."Charge Amount: $".number_format(-1 * $_SESSION["chargeTotal"], 2)."\n"
		   .centerString("................................................")."\n"
		   ."\n\n\n\n\n\n\n"
		   .chr(27).chr(105);

	return $receipt;

}

// Charge Footer split into two functions by apbw 2/1/05

function printChargeFooterStore($dateTimeStamp, $ref) {	// apbw 2/14/05 SCR

	
	$chgName = getChgName();			// added by apbw 2/14/05 SCR
	
	$date = build_time($dateTimeStamp);

	$receipt = chr(27).chr(33).chr(5)		// apbw 3/18/05 
		   ."\n".centerString($_SESSION["chargeSlip2"])."\n"
		   .centerString("................................................")."\n"
		   .centerString($_SESSION["chargeSlip1"])."\n\n"
		   ."CUSTOMER CHARGE ACCOUNT\n"
		   ."Name: ".trim($_SESSION["ChgName"])."\n"		// changed by apbw 2/14/05 SCR
		   ."Member Number: ".trim($_SESSION["memberID"])."\n"
		   ."Date: ".$date."\n"
		   ."REFERENCE #: ".$ref."\n"
		   ."Charge Amount: $".number_format(-1 * $_SESSION["chargeTotal"], 2)."\n"
		   ."I AGREE TO PAY THE ABOVE AMOUNT\n"
		   ."TO MY CHARGE ACCOUNT\n"
		   ."Purchaser Sign Below\n\n\n"
		   ."X____________________________________________\n\n"
		   .centerString(".................................................")."\n\n";
	$_SESSION["chargetender"] = 0;	// apbw 2/14/05 SCR (moved up a line for Reprint patch on 3/10/05)

	return $receipt;


}

// -------------  frank.php incorporated into printlib on 3/24/05 apbw (from here to eof) -------

function frank() {

	$date = strftime("%m/%d/%y %I:%M %p", time());
	$ref = trim($_SESSION["memberID"])." ".trim($_SESSION["CashierNo"])." ".trim($_SESSION["laneno"])." ".trim($_SESSION["transno"]);
	$tender = "AMT: ".truncate2($_SESSION["tenderamt"])."  CHANGE: ".truncate2($_SESSION["change"]);
	$output = center_check($ref)."\n"
		.center_check($date)."\n"
		.center_check($_SESSION["ckEndorse1"])."\n"
		.center_check($_SESSION["ckEndorse2"])."\n"
		.center_check($_SESSION["ckEndorse3"])."\n"
		.center_check($_SESSION["ckEndorse4"])."\n"
		.center_check($tender)."\n";



	endorse($output);
}

// -----------------------------------------------------

function frankgiftcert() {

	$ref = trim($_SESSION["CashierNo"])."-".trim($_SESSION["laneno"])."-".trim($_SESSION["transno"]);
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
      $output .= "$".truncate2($_SESSION["tenderamt"]);
	endorse($output); 

}

// -----------------------------------------------------

function frankstock() {

	$time_now = strftime("%m/%d/%y", time());		// apbw 3/10/05 "%D" didn't work - Franking patch
	if ($_SESSION["franking"] == 0) {
		$_SESSION["franking"] = 1;
	}
	$ref = trim($_SESSION["CashierNo"])."-".trim($_SESSION["laneno"])."-".trim($_SESSION["transno"]);
	$output  = "";
	$output .= str_repeat("\n", 40);	// 03/24/05 apbw Wedge Printer Swap Patch
	$output .= "Stock Payment $".$_SESSION["tenderamt"]." ref: ".$ref."   ".$time_now; // apbw 3/24/05 Wedge Printer Swap Patch

	endorse($output);

}
//-------------------------------------------------------


function frankclassreg() {

	$ref = trim($_SESSION["CashierNo"])."-".trim($_SESSION["laneno"])."-".trim($_SESSION["transno"]);
	$time_now = strftime("%m/%d/%y", time());		// apbw 3/10/05 "%D" didn't work - Franking patch
	$output  = "";		
	$output .= str_repeat("\n", 11);		// apbw 3/24/05 Wedge Printer Swap Patch
	$output .= str_repeat(" ", 5);		// apbw 3/24/05 Wedge Printer Swap Patch
	$output .= "Validated: ".$time_now."  ref: ".$ref; 	// apbw 3/24/05 Wedge Printer Swap Patch

	endorse($output);	

}

//----------------------------------Credit Card footer----by CvR

function printCCFooter($dateTimeStamp, $ref) {

	$date = build_time($dateTimeStamp);


			
	$receipt = "\n".centerString("C U S T O M E R   C O P Y")."\n"
		   .centerString("................................................")."\n"
               .centerString($_SESSION["chargeSlip1"])."\n\n"
		   .centerString("Cardholder acknowledges receipt of goods/services")."\n"
               .centerString("in the amount shown and agrees to pay for them")."\n"
               .centerString("according to card issuer agreement.")."\n\n"
		   ."CREDIT CARD CHARGE\n"
		   ."Name: ".trim($_SESSION["ccName"])."\n"
		   ."Member Number: ".trim($_SESSION["memberID"])."\n"
		   ."Date: ".$date."\n"
		   ."REFERENCE #: ".$ref."\n"
               ."TROUTD: ".trim($_SESSION["troutd"])."\n"
		   ."Charge Amount: $".number_format(-1*$_SESSION["ccTotal"], 2)."\n"  //changed 04/01/05 Tak & CvR
		   .centerString("................................................")."\n"
		   ."\n\n\n\n\n\n\n"
		   .chr(27).chr(105)

	// writeLine($receipt1.chr(27).chr(105));
	// writeLine(chr(27).chr(105));

	// $receipt2 =""

		   .centerString($_SESSION["chargeSlip2"])."\n"
		   .centerString("................................................")."\n"
		   .centerString($_SESSION["chargeSlip1"])."\n\n"
		   ."CREDIT CARD CHARGE\n"
		   ."Name: ".trim($_SESSION["ccName"])."\n"
		   ."Member Number: ".trim($_SESSION["memberID"])."\n"
		   ."Date: ".$date."\n"
		   ."REFERENCE #: ".$ref."\n"
               ."TROUTD: ".trim($_SESSION["troutd"])."\n"
		   ."Charge Amount: $".number_format(-1*$_SESSION["ccTotal"], 2)."\n\n" //changed 04/01/05  Tak and CvR
		   .centerString("I agree to pay the above total amount")."\n"
		   .centerString("according to card issuer agreement.")."\n\n"
		   ."Purchaser Sign Below\n\n\n"
		   ."X____________________________________________\n\n"
		   .centerString(".................................................")."\n\n";
		
		


	// writeLine(chr(27).chr(105));

	return $receipt;

}


?>
