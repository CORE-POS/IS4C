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

// ----------------------------------------------------------- 
// reprint the specified receipt 
// -----------------------------------------------------------

if (!function_exists("pDataConnect")) include($CORE_PATH."lib/connect.php");
if (!function_exists("writeLine")) include($CORE_PATH."lib/printLib.php");
if (!class_exists("ESCPOSPrintHandler")) include_once($CORE_PATH."lib/PrintHandlers/ESCPOSPrintHandler.php");
if (!isset($CORE_LOCAL)) include($CORE_PATH."lib/LocalStorage/conf.php");

$PRINT_OBJ;

function reprintReceipt($trans_num=""){
	global $CORE_LOCAL, $PRINT_OBJ;

	$PRINT_OBJ = new ESCPOSPrintHandler();

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

		$connID = pDataConnect();
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
			$CORE_LOCAL->set("yousaved",number_format( $CORE_LOCAL->get("transDiscount") + $CORE_LOCAL->get("discounttotal") + $CORE_LOCAL->get("memSpecial") + $CORE_LOCAL->get("memCouponTTL"), 2));
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
		$receipt = $title.printReceiptHeader($dateTimeStamp, $ref);
		
		$receipt .= receiptDetail(True,$ref);

		// The Nitty Gritty:
		$member = "Member ".trim($CORE_LOCAL->get("memberID"));
		if ($member == 0) $member = $CORE_LOCAL->get("defaultNonMem");
		$your_discount = $CORE_LOCAL->get("transDiscount") + $CORE_LOCAL->get("memCouponTTL");

		if ($CORE_LOCAL->get("transDiscount") + $CORE_LOCAL->get("memCouponTTL") + $CORE_LOCAL->get("specials") > 0) {
			$receipt .= "\n".centerString("------------------ YOUR SAVINGS -------------------")."\n";

			if ($your_discount > 0) {
				$receipt .= "    DISCOUNTS: $".number_format($your_discount, 2)."\n";
			}

			if ($CORE_LOCAL->get("specials") > 0) {
				$receipt .= "    SPECIALS: $".number_format($CORE_LOCAL->get("specials"), 2)."\n";
			}

			$receipt .= centerString("---------------------------------------------------")."\n";
		}
		$receipt .= "\n";
	
		if (trim($CORE_LOCAL->get("memberID")) != $CORE_LOCAL->get("defaultNonMem")) {
			$receipt .= centerString("Thank You - ".$member)."\n";
		}
		else {
			$receipt .= centerString("Thank You!")."\n";
		}

		if ($CORE_LOCAL->get("yousaved") > 0) {
			$receipt .= centerString("You Saved $".number_format($CORE_LOCAL->get("yousaved"), 2))."\n";
		}

		if ($CORE_LOCAL->get("couldhavesaved") > 0 && $CORE_LOCAL->get("yousaved") > 0) {
			$receipt .= centerString("You could have saved an additional $"
				    .number_format($CORE_LOCAL->get("couldhavesaved"), 2))."\n";
		}
		elseif ($CORE_LOCAL->get("couldhavesaved") > 0) {
			$receipt .= centerString("You could have saved $"
				    .number_format($CORE_LOCAL->get("couldhavesaved"), 2))."\n";
		}

		for ($i = 1; $i <= $CORE_LOCAL->get("receiptFooterCount"); $i++){
			$receipt .= $PRINT_OBJ->centerString($CORE_LOCAL->get("receiptFooter$i"));
			$receipt .= "\n";
		}


		if ($CORE_LOCAL->get("chargetender") != 0 ) {			// apbw 03/10/05 Reprint patch
			$receipt = $receipt.printChargeFooterStore($dateTimeStamp, $ref);	// apbw 03/10/05 Reprint patch
		}			// apbw 03/10/05 Reprint patch

		$receipt .= printGCSlip($dateTimeStamp, $ref, true, 1);
		$receipt .= printCCSigSlip($dateTimeStamp, $ref, False, 1);
	
		$receipt = $receipt."\n\n\n\n\n\n\n";			// apbw 03/10/05 Reprint patch
		writeLine($receipt.chr(27).chr(105));			// apbw 03/10/05 Reprint patch
		$receipt = "";			// apbw 03/10/05 Reprint patch

		$CORE_LOCAL->set("memMsg","");
		$CORE_LOCAL->set("memberID","0");
		$CORE_LOCAL->set("percentDiscount",0);
	}
}

?>
