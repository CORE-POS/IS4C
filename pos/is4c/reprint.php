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

if (!function_exists("pDataConnect")) include("connect.php");
if (!function_exists("tDataConnect")) include("connect.php");
if (!function_exists("gohome")) include("maindisplay.php");
if (!function_exists("writeLine")) include("printLib.php");
if (!function_exists("blueLine")) include("session.php");

if (isset($_POST["selectlist"])) {
	$reprnt = strtoupper(trim($_POST["selectlist"]));
}
else {
	$reprnt = "";
}




if ($reprnt && strlen($reprnt) >= 1) {

	// writeLine(centerString("***    R E P R I N T    ***")."\n\n\n");
	$title = chr(27).chr(33).chr(5).centerString("***    R E P R I N T    ***")."\n\n\n";

	$arpspec = explode("::", $reprnt);
	$laneno = $arpspec[0];
	$cashierNo = $arpspec[1];
	$transno = $arpspec[2];

	$ref = trim($cashierNo)."-".trim($laneno)."-".trim($transno);

	$queryHeader = "select * from rp_receipt_header where register_no = ".$laneno." and emp_no = ".$cashierNo." and trans_no = ".$transno;
 	
	$connect = tDataConnect();
	$header = sql_query($queryHeader, $connect);
	$headerRow = sql_fetch_array($header);

	$dateTimeStamp = $headerRow["dateTimeStamp"];
	$dateTimeStamp = strtotime($dateTimeStamp);

	$_SESSION["memberID"] = $headerRow["memberID"];
	$_SESSION["memCouponTLL"] = $headerRow["couponTotal"];
	$_SESSION["transDiscount"] = $headerRow["transDiscount"];
	$_SESSION["chargeTotal"] =  -1*$headerRow["chargeTotal"];

	if ($_SESSION["chargeTotal"] != 0) { 
		
		$_SESSION["chargetender"] = 1;
	} else {
		$_SESSION["chargetender"] = 0;
	}

	$_SESSION["discounttotal"] = $headerRow["discountTTL"];
	$_SESSION["memSpecial"] = $headerRow["memSpecial"];

	sql_close($connect);

	$queryID = "select * from custdata where CardNo = '".$_SESSION["memberID"]."'";
	
	$connID = pDataConnect();
	$result = sql_query($queryID, $connID);
	$row = sql_fetch_array($result);

	if ($row["Type"] == "PC") {
		$_SESSION["isMember"] = 1;
	}
	else {
		$_SESSION["isMember"] = 0;
	}
	$_SESSION["memMsg"] = blueLine($row);
	
	sql_close($connID);

	if ($_SESSION["isMember"] == 1) {
		$_SESSION["yousaved"] = number_format( $_SESSION["transDiscount"] + $_SESSION["discounttotal"] + $_SESSION["memSpecial"] + $_SESSION["memCouponTTL"], 2);
		$_SESSION["couldhavesaved"] = 0;
		$_SESSION["specials"] = number_format($_SESSION["discounttotal"] + $_SESSION["memSpecial"], 2);
	}
	else {
		$dblyousaved = number_format($_SESSION["memSpecial"], 2);
		$_SESSION["yousaved"] = $_SESSION["discounttotal"];
		$_SESSION["couldhavesaved"] = number_format($_SESSION["memSpecial"], 2);
		$_SESSION["specials"] = $_SESSION["discounttotal"];
	}


// -- Our Reference number for the transaction.
//	$ref = trim($_SESSION["CashierNo"])."-".trim($_SESSION["laneno"])."-".trim($_SESSION["transno"]);
//	incorrect - commented out by apbw 5/3/05 - correct ref is set above at line 54


//	call to transLog, the body of the receipt comes from the view 'receipt'

	$query = "select * from rp_receipt where register_no = ".$laneno." and emp_no = ".$cashierNo." and trans_no = ".$transno." order by trans_id";
	$db = tDataConnect();
	$result = sql_query($query, $db);
	$num_rows = sql_num_rows($result);

	$receipt = $title.printReceiptHeader($dateTimeStamp, $ref);
	
//	loop through the results to generate the items listing.

	for ($i = 0; $i < $num_rows; $i++) {
		$row = sql_fetch_array($result);
		$receipt .= $row["linetoprint"]."\n";
	}

	// The Nitty Gritty:


	$member = "Member ".trim($_SESSION["memberID"]);
	$your_discount = $_SESSION["transDiscount"] + $_SESSION["memCouponTTL"];

	if ($_SESSION["transDiscount"] + $_SESSION["memCouponTTL"] + $_SESSION["specials"] > 0) {
		$receipt .= "\n".centerString("------------------ YOUR SAVINGS -------------------")."\n";

		if ($your_discount > 0) {
			$receipt .= "    DISCOUNTS: $".number_format($your_discount, 2)."\n";
		}

		if ($_SESSION["specials"] > 0) {
			$receipt .= "    SPECIALS: $".number_format($_SESSION["specials"], 2)."\n";
		}

		$receipt .= centerString("---------------------------------------------------")."\n";
	}

	$receipt .= "\n";
	
		if (strlen(trim($_SESSION["memberID"])) != 99999) {
			$receipt .= centerString("Thank You - ".$member)."\n";
		}
		else {
			$receipt .= centerString("Thank You!")."\n";
		}

		if ($_SESSION["yousaved"] > 0) {
			$receipt .= centerString("You Saved $".number_format($_SESSION["yousaved"]), 2)."\n";
		}

		if ($_SESSION["couldhavesaved"] > 0 && $_SESSION["yousaved"] > 0) {
			$receipt .= centerString("You could have saved an additional $"
				    .number_format($_SESSION["couldhavesaved"], 2))."\n";
		}
		elseif ($_SESSION["couldhavesaved"] > 0) {
			$receipt .= centerString("You could have saved $"
				    .number_format($_SESSION["couldhavesaved"]), 2)."\n";
		}

		$receipt .= centerString($_SESSION["receiptFooter1"])."\n"
			.centerString($_SESSION["receiptFooter2"])."\n"
			.centerString($_SESSION["receiptFooter3"])."\n"
			.centerString($_SESSION["receiptFooter4"])."\n";


		if ($_SESSION["chargetender"] != 0 ) {			// apbw 03/10/05 Reprint patch
			$receipt = $receipt.printChargeFooterCust($dateTimeStamp, $ref);	// apbw 03/10/05 Reprint patch
			$receipt = $receipt.printChargeFooterStore($dateTimeStamp, $ref);	// apbw 03/10/05 Reprint patch
		}			// apbw 03/10/05 Reprint patch
	
	$receipt = $receipt."\n\n\n\n\n\n\n";			// apbw 03/10/05 Reprint patch
	writeLine($receipt.chr(27).chr(105));			// apbw 03/10/05 Reprint patch
	$receipt = "";			// apbw 03/10/05 Reprint patch

	$_SESSION["memMsg"] = "";
	$_SESSION["memberID"] = "0";
	$_SESSION["memType"] = 0;
	$_SESSION["percentDiscount"] = 0;

	getsubtotals();

}

gohome();

?>
