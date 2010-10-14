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

if (!function_exists("setDrawerKick")) include($IS4C_PATH."lib/setDrawerKick.php");  // apbw 03/29/05 Drawer Kick Patch
if (!function_exists("writeLine")) include_once($IS4C_PATH."lib/printLib.php");	// apbw 03/26/05 Wedge Printer Swap Patch
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

// ----------------------------------------------------------- 
// printReceipt.php is the main page for printing receipts.  
// It invokes the following functions from other pages:  
// -----------------------------------------------------------



function printReceipt($arg1,$second=False) {
	global $IS4C_LOCAL;

	$dokick = setDrawerKickLater();
	$receipt = "";


	if ($arg1 == "full" and $dokick != 0) {	// ---- apbw 03/29/05 Drawer Kick Patch
		writeLine(chr(27).chr(112).chr(0).chr(48)."0");
	}

/* --------------------------------------------------------------
  turn off staff charge receipt printing if toggled - apbw 2/1/05 
  ---------------------------------------------------------------- */

	$noreceipt = ($IS4C_LOCAL->get("receiptToggle")==1 ? 0 : 1);
	
	$dateTimeStamp = time();		// moved by apbw 2/15/05 SCR

// -- Our Reference number for the transaction.

	$ref = trim($IS4C_LOCAL->get("CashierNo"))."-".trim($IS4C_LOCAL->get("laneno"))."-".trim($IS4C_LOCAL->get("transno"));


	if ($noreceipt != 1) 		// moved by apbw 2/15/05 SCR
	{
	$receipt = printReceiptHeader($dateTimeStamp, $ref);

	if ($second){
		$ins = centerString("( S T O R E   C O P Y )")."\n";
		$receipt = substr($receipt,0,3).$ins.substr($receipt,3);
	}

	// The Nitty Gritty:
	/***** jqh 09/29/05 changes made to following if statement so if the receipt is full, then print new receipt,
			if not full, then print old style receipt *****/
	if ($arg1 == "full") {

		$receipt .= receiptDetail();
		$member = "Member ".trim($IS4C_LOCAL->get("memberID"));
		$your_discount = $IS4C_LOCAL->get("transDiscount") + $IS4C_LOCAL->get("memCouponTTL");

		if ($IS4C_LOCAL->get("transDiscount") + $IS4C_LOCAL->get("memCouponTTL") + $IS4C_LOCAL->get("specials") > 0) {
			$receipt .= "\n".centerString("------------------ YOUR SAVINGS -------------------")."\n";

			if ($your_discount > 0) {
				$receipt .= "    DISCOUNTS: $".number_format($your_discount, 2)."\n";
			}

			if ($IS4C_LOCAL->get("specials") > 0) {
				$receipt .= "    SPECIALS: $".number_format($IS4C_LOCAL->get("specials"), 2)."\n";
			}

			$receipt .= centerString("---------------------------------------------------")."\n";
		}
		$receipt .= "\n";
	
		if (trim($IS4C_LOCAL->get("memberID")) != $IS4C_LOCAL->get("defaultNonMem")) {
			/***** jqh 09/29/05 change made to thank you line depending on session newReceipt *****/
			if ($IS4C_LOCAL->get("newReceipt")==1){
				$receipt .= biggerFont(centerBig("Thank You - ".$member."!!"))."\n\n";
			}else{
				$receipt .= centerString("Thank You - ".$member."!!!")."\n";
			}
			/***** jqh end change *****/
		}
		else {
			/***** jqh 09/29/05 change made to thank you line depending on session newReceipt *****/
			if ($IS4C_LOCAL->get("newReceipt")==1){
				$receipt .= biggerFont(centerBig("Thank You!!!"))."\n\n";
			}else{
				$receipt .= centerString("Thank You!!!")."\n";
			}
			/***** jqh end change *****/
		}

		if ($IS4C_LOCAL->get("yousaved") > 0) {
			$receipt .= centerString("You Saved $".number_format($IS4C_LOCAL->get("yousaved"), 2))."\n";
		}

		if ($IS4C_LOCAL->get("couldhavesaved") > 0 && $IS4C_LOCAL->get("yousaved") > 0) {
			$receipt .= centerString("You could have saved an additional $"
				    .number_format($IS4C_LOCAL->get("couldhavesaved"), 2))."\n";
		}
		elseif ($IS4C_LOCAL->get("couldhavesaved") > 0) {
			$receipt .= centerString("You could have saved $"
				    .number_format($IS4C_LOCAL->get("couldhavesaved"), 2))."\n";
		}

		$receipt .= localTTL();

		/***** jqh 09/29/05 change made to receipt footer depending on session newReceipt *****/
		for ($i = 1; $i <= $IS4C_LOCAL->get("receiptFooterCount"); $i++)
			$receipt .= centerString($IS4C_LOCAL->get("receiptFooter$i"))."\n";
		/***** jqh end change *****/

		/***** CvR add charge total to receipt bottom ****/
		
		$receipt = chargeBalance($receipt);
		
		/**** CvR end ****/

		// --- apbw 2/15/05 SCR ---
		/*
		if ($IS4C_LOCAL->get("chargetender") == 1 && $IS4C_LOCAL->get("End") == 1) {						
			$receipt = $receipt.printChargeFooterCust($dateTimeStamp, $ref);	
		}
		 */

		/*
		if ($IS4C_LOCAL->get("ccTender") == 1) {
			$receipt = $receipt.printCCFooter($dateTimeStamp,$ref);
		}
		 */

		// append customer copy to actual lane receipt
		if ($IS4C_LOCAL->get('standalone') == 0)
			$receipt .= printCCSigSlip($dateTimeStamp, $ref, false, 1);

		if ($IS4C_LOCAL->get("autoReprint") == 1)
			$receipt .= printGCSlip($dateTimeStamp, $ref, false, 1);
		else
			$receipt .= printGCSlip($dateTimeStamp, $ref, true, 1);

		if ($IS4C_LOCAL->get("promoMsg") == 1) {
			promoMsg();

		}

		$receipt .= storeCreditIssued($second);

		$IS4C_LOCAL->set("headerprinted",0);

	}
	else if ($arg1 == "cab"){
		$ref = $IS4C_LOCAL->get("cabReference");
		$receipt = printCabCoupon($dateTimeStamp, $ref);
		$IS4C_LOCAL->set("cabReference","");
	}
	else {

		/***** jqh 09/29/05 if receipt isn't full, then display receipt in old style *****/
		$query="select linetoprint from receipt";
		$db = tDataConnect();
		$result = $db->query($query);
		$num_rows = $db->num_rows($result);
	
//		loop through the results to generate the items listing.
	
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
		/***** CvR 09/30/05 Added to print CC signature slip after 
		authorization, before tendering *****/
		
		elseif ($arg1 == "ccSlip") {
			//if ($IS4C_LOCAL->get("ccCustCopy") == 1){
			//	$receipt = printCCSigSlip($dateTimeStamp,$ref,False);
			//}
			//else {
				$receipt = printCCSigSlip($dateTimeStamp,$ref,True);
			//}
		}
		else if ($arg1 == "gcSlip") { // --atf 10/8/07
			if ($IS4C_LOCAL->get("autoReprint") == 1){
				$receipt = printGCSlip($dateTimeStamp,$ref,true);
			}
			else {
				$receipt = printGCSlip($dateTimeStamp,$ref,false);
			}
		} 
		else if ($arg1 == "gcBalSlip") { // --atf 10/8/07
			$receipt = printGCBalSlip();
		} 
		
		/***** CvR 09/30/05 END *****/
	} /***** jqh end big if statement change *****/
}
else {
	$receipt = chargeBalance($receipt);
}


/* --------------------------------------------------------------
  print store copy of charge slip regardless of receipt print setting - apbw 2/14/05 
  ---------------------------------------------------------------- */
if ($IS4C_LOCAL->get("chargetender") == 1 && $IS4C_LOCAL->get("End") == 1) {
	if ($noreceipt == 1) {	
		$receipt = $receipt.printChargeFooterStore($dateTimeStamp, $ref);
	} else {	
		$receipt = $receipt.printChargeFooterStore($dateTimeStamp, $ref);	
	}	
}		
//-------------------------------------------------------------------
			


if ($receipt !== ""){
	$receipt = $receipt."\n\n\n\n\n\n\n";

	writeLine($receipt.chr(27).chr(105));
}
	
$receipt = "";

}

?>
