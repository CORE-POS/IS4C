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

if (!function_exists("setDrawerKick")) include($CORE_PATH."lib/setDrawerKick.php");  // apbw 03/29/05 Drawer Kick Patch
if (!function_exists("writeLine")) include_once($CORE_PATH."lib/printLib.php");	// apbw 03/26/05 Wedge Printer Swap Patch
if (!class_exists("ESCPOSPrintHandler")) include_once($CORE_PATH."lib/PrintHandlers/ESCPOSPrintHandler.php");	// apbw 03/26/05 Wedge Printer Swap Patch
if (!isset($CORE_LOCAL)) include($CORE_PATH."lib/LocalStorage/conf.php");

// ----------------------------------------------------------- 
// printReceipt.php is the main page for printing receipts.  
// It invokes the following functions from other pages:  
// -----------------------------------------------------------

$PRINT_OBJ;

function printReceipt($arg1,$second=False) {
	global $CORE_LOCAL, $PRINT_OBJ;

	$PRINT_OBJ = new ESCPOSPrintHandler();

	$dokick = setDrawerKickLater();
	$receipt = "";

	if ($arg1 == "full" and $dokick != 0) {	// ---- apbw 03/29/05 Drawer Kick Patch
		$kick_cmd = $PRINT_OBJ->DrawerKick(2,48*2,30*2);
		$PRINT_OBJ->writeLine($kick_cmd);
		//writeLine(chr(27).chr(112).chr(0).chr(48)."0");
	}

/* --------------------------------------------------------------
  turn off staff charge receipt printing if toggled - apbw 2/1/05 
  ---------------------------------------------------------------- */

	$noreceipt = ($CORE_LOCAL->get("receiptToggle")==1 ? 0 : 1);
	
	$dateTimeStamp = time();		// moved by apbw 2/15/05 SCR

// -- Our Reference number for the transaction.

	$ref = trim($CORE_LOCAL->get("CashierNo"))."-".trim($CORE_LOCAL->get("laneno"))."-".trim($CORE_LOCAL->get("transno"));

	if ($noreceipt != 1){ 		// moved by apbw 2/15/05 SCR
		$receipt = printReceiptHeader($dateTimeStamp, $ref);

		if ($second){
			$ins = $PRINT_OBJ->centerString("( S T O R E   C O P Y )")."\n";
			$receipt = substr($receipt,0,3).$ins.substr($receipt,3);
		}

		// The Nitty Gritty:
		/***** jqh 09/29/05 changes made to following if statement so if the receipt is full, then print new receipt,
		if not full, then print old style receipt *****/
		if ($arg1 == "full") {

			$receipt .= receiptDetail();
			$member = "Member ".trim($CORE_LOCAL->get("memberID"));
			$your_discount = $CORE_LOCAL->get("transDiscount") + $CORE_LOCAL->get("memCouponTTL");

			if ($CORE_LOCAL->get("transDiscount") + 
			   $CORE_LOCAL->get("memCouponTTL") + 
			   $CORE_LOCAL->get("specials") > 0 ) {
				$receipt .= 'TODAY YOU SAVED = $'.
					number_format($your_discount + $CORE_LOCAL->get("specials"),2).
					"\n";
			}
			$receipt .= localTTL();
			$receipt .= "\n";
	
			if (trim($CORE_LOCAL->get("memberID")) != $CORE_LOCAL->get("defaultNonMem")) {
				if ($CORE_LOCAL->get("newReceipt")==1){
					$receipt .= $PRINT_OBJ->TextStyle(True,False,True);
					$receipt .= $PRINT_OBJ->centerString("thank you - owner ".$member,True);
					$receipt .= $PRINT_OBJ->TextStyle(True);
					$receipt .= "\n\n";
				}
				else{
					$receipt .= $PRINT_OBJ->centerString("Thank You - ".$member);
					$receipt .= "\n";
				}
			}
			else {
				if ($CORE_LOCAL->get("newReceipt")==1){
					$receipt .= $PRINT_OBJ->TextStyle(True,False,True);
					$receipt .= $PRINT_OBJ->centerString("thank you",True);
					$receipt .= $PRINT_OBJ->TextStyle(True);
					$receipt .= "\n\n";
				}
				else{
					$receipt .= $PRINT_OBJ->centerString("Thank You!");
					$receipt .= "\n";
				}
			}

			for ($i = 1; $i <= $CORE_LOCAL->get("receiptFooterCount"); $i++){
				$receipt .= $PRINT_OBJ->centerString($CORE_LOCAL->get("receiptFooter$i"));
				$receipt .= "\n";
			}

			if ($CORE_LOCAL->get("store")=="wfc"){
				$refund_date = date("m/d/Y",mktime(0,0,0,date("n"),date("j")+30,date("Y")));
				$receipt .= $PRINT_OBJ->centerString("returns accepted with this receipt through ".$refund_date);
				$receipt .= "\n";
			}

			/***** CvR add charge total to receipt bottom ****/
			$receipt = chargeBalance($receipt);
			/**** CvR end ****/

			// append customer copy to actual lane receipt
			if ($CORE_LOCAL->get('standalone') == 0)
				$receipt .= printCCSigSlip($dateTimeStamp, $ref, false, 0);

			if ($CORE_LOCAL->get("autoReprint") == 1)
				$receipt .= printGCSlip($dateTimeStamp, $ref, false, 1);
			else
				$receipt .= printGCSlip($dateTimeStamp, $ref, true, 1);

			if ($CORE_LOCAL->get("promoMsg") == 1) {
				promoMsg();
			}

			$receipt .= storeCreditIssued($second);

			$CORE_LOCAL->set("headerprinted",0);
		}
		else if ($arg1 == "cab"){
			$ref = $CORE_LOCAL->get("cabReference");
			$receipt = printCabCoupon($dateTimeStamp, $ref);
			$CORE_LOCAL->set("cabReference","");
		}
		else {
			/***** jqh 09/29/05 if receipt isn't full, then display receipt in old style *****/
			$query="select linetoprint from receipt";
			$db = tDataConnect();
			$result = $db->query($query);
			$num_rows = $db->num_rows($result);
	
			// loop through the results to generate the items listing.
			for ($i = 0; $i < $num_rows; $i++) {
				$row = $db->fetch_array($result);
				$receipt .= $row[0]."\n";
			}
			/***** jqh end change *****/

			$dashes = "\n".centerString("----------------------------------------------")."\n";

			if ($arg1 == "partial") {
				$receipt .= $dashes.centerString("*    P A R T I A L  T R A N S A C T I O N    *").$dashes;
			}
			elseif ($arg1 == "cancelled") {
				$receipt .= $dashes.centerString("*  T R A N S A C T I O N  C A N C E L L E D  *").$dashes;
			}
			elseif ($arg1 == "resume") {
				$receipt .= $dashes.centerString("*    T R A N S A C T I O N  R E S U M E D    *").$dashes
				     .centerString("A complete receipt will be printed\n")
				     .centerString("at the end of the transaction");
			}
			elseif ($arg1 == "suspended") {
				$receipt .= $dashes.centerString("*  T R A N S A C T I O N  S U S P E N D E D  *").$dashes
					     .centerString($ref);
			}
			elseif ($arg1 == "ccSlip") {
				$receipt = printCCSigSlip($dateTimeStamp,$ref,True);
			}
			else if ($arg1 == "gcSlip") { // --atf 10/8/07
				if ($CORE_LOCAL->get("autoReprint") == 1){
					$receipt = printGCSlip($dateTimeStamp,$ref,true);
				}
				else {
					$receipt = printGCSlip($dateTimeStamp,$ref,false);
				}
			} 
			else if ($arg1 == "gcBalSlip") { // --atf 10/8/07
				$receipt = printGCBalSlip();
			} 
		
		} /***** jqh end big if statement change *****/
	}
	else {
		$receipt = chargeBalance($receipt);
	}

	/* --------------------------------------------------------------
	  print store copy of charge slip regardless of receipt print setting - apbw 2/14/05 
	  ---------------------------------------------------------------- */
	if ($CORE_LOCAL->get("chargetender") == 1 && $CORE_LOCAL->get("End") == 1) {
		if ($noreceipt == 1) {	
			$receipt = $receipt.printChargeFooterStore($dateTimeStamp, $ref);
		} else {	
			$receipt = $receipt.printChargeFooterStore($dateTimeStamp, $ref);	
		}	
	}		
			
	if ($receipt !== ""){
		$receipt = $receipt."\n\n\n\n\n\n\n";
		//$receipt .= $PRINT_OBJ->LineFeed(7);

		$PRINT_OBJ->writeLine($receipt.chr(27).chr(105));
	}
	
	$receipt = "";
}

?>
