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


// ----------------------------------------------------------- 
// reprint the specified receipt 
// -----------------------------------------------------------

if (!function_exists("pDataConnect")) include($_SERVER["DOCUMENT_ROOT"]."/lib/connect.php");
if (!function_exists("writeLine")) include($_SERVER["DOCUMENT_ROOT"]."/lib/printLib.php");
if (!isset($IS4C_LOCAL)) include($_SERVER["DOCUMENT_ROOT"]."/lib/LocalStorage/conf.php");

function reprintReceipt($trans_num=""){
	global $IS4C_LOCAL;

	if (strlen($trans_num) >= 1) {
		$title = chr(27).chr(33).chr(5).centerString("***    R E P R I N T    ***")."\n\n\n";

		$arpspec = explode("::", $trans_num);
		$laneno = $arpspec[0];
		$cashierNo = $arpspec[1];
		$transno = $arpspec[2];

		$ref = trim($cashierNo)."-".trim($laneno)."-".trim($transno);

		$queryHeader = "select * from rp_receipt_header where register_no = ".$laneno." and emp_no = ".$cashierNo." and trans_no = ".$transno;
 	
		$connect = tDataConnect();
		$header = $connect->query($queryHeader);
		$headerRow = $connect->fetch_array($header);

		$dateTimeStamp = $headerRow["dateTimeStamp"];
		$dateTimeStamp = strtotime($dateTimeStamp);

		$IS4C_LOCAL->set("memberID",$headerRow["memberID"]);
		$IS4C_LOCAL->set("memCouponTLL",$headerRow["couponTotal"]);
		$IS4C_LOCAL->set("transDiscount",$headerRow["transDiscount"]);
		$IS4C_LOCAL->set("chargeTotal",-1*$headerRow["chargeTotal"]);

		if ($IS4C_LOCAL->get("chargeTotal") != 0) { 
			$IS4C_LOCAL->set("chargetender",1);
		} else {
			$IS4C_LOCAL->set("chargetender",0);
		}

		$IS4C_LOCAL->set("discounttotal",$headerRow["discountTTL"]);
		$IS4C_LOCAL->set("memSpecial",$headerRow["memSpecial"]);

		$connect->close();

		$connID = pDataConnect();
		$queryID = "select * from custdata where CardNo = '".$IS4C_LOCAL->get("memberID")."' and personNum=1";
		$result = $connID->query($queryID);
		$row = $connID->fetch_array($result);

		// restore names for charge slips
		$IS4C_LOCAL->set("lname",$row["LastName"]);
		$IS4C_LOCAL->set("fname",$row["FirstName"]);

		if ($row["Type"] == "PC") {
			$IS4C_LOCAL->set("isMember",1);
		}
		else {
			$IS4C_LOCAL->set("isMember",0);
		}
		$IS4C_LOCAL->set("memMsg",$row["blueLine"]);
	
		$connID->close();

		if ($IS4C_LOCAL->get("isMember") == 1) {
			$IS4C_LOCAL->set("yousaved",number_format( $IS4C_LOCAL->get("transDiscount") + $IS4C_LOCAL->get("discounttotal") + $IS4C_LOCAL->get("memSpecial") + $IS4C_LOCAL->get("memCouponTTL"), 2));
			$IS4C_LOCAL->set("couldhavesaved",0);
			$IS4C_LOCAL->set("specials",number_format($IS4C_LOCAL->get("discounttotal") + $IS4C_LOCAL->get("memSpecial"), 2));
		}
		else {
			$dblyousaved = number_format($IS4C_LOCAL->get("memSpecial"), 2);
			$IS4C_LOCAL->set("yousaved",$IS4C_LOCAL->get("discounttotal"));
			$IS4C_LOCAL->set("couldhavesaved",number_format($IS4C_LOCAL->get("memSpecial"), 2));
			$IS4C_LOCAL->set("specials",$IS4C_LOCAL->get("discounttotal"));
		}


		// call to transLog, the body of the receipt comes from the view 'receipt'
		$receipt = $title.printReceiptHeader($dateTimeStamp, $ref);
		
		$receipt .= receiptDetail(True,$ref);

		// The Nitty Gritty:
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
			$receipt .= centerString("Thank You - ".$member."!!!")."\n";
		}
		else {
			$receipt .= centerString("Thank You!!!")."\n";
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

		$receipt .= centerString("Returns accepted with receipt")."\n"
			.centerString("within 30 days of purchase.")."\n\n\n";


		if ($IS4C_LOCAL->get("chargetender") != 0 ) {			// apbw 03/10/05 Reprint patch
			$receipt = $receipt.printChargeFooterStore($dateTimeStamp, $ref);	// apbw 03/10/05 Reprint patch
		}			// apbw 03/10/05 Reprint patch

		$receipt .= printGCSlip($dateTimeStamp, $ref, true, 1);
		$receipt .= printCCSigSlip($dateTimeStamp, $ref, False, 1);
	
		$receipt = $receipt."\n\n\n\n\n\n\n";			// apbw 03/10/05 Reprint patch
		writeLine($receipt.chr(27).chr(105));			// apbw 03/10/05 Reprint patch
		$receipt = "";			// apbw 03/10/05 Reprint patch

		$IS4C_LOCAL->set("memMsg","");
		$IS4C_LOCAL->set("memberID","0");
		$IS4C_LOCAL->set("percentDiscount",0);
	}
}

?>
