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
/*------------------------------------------------------------------------------
additem.php is called by the following files:

as include:
	login3.php
	authenticate3.php
	prehkeys.php
	upcscanned.php
	authenticate.php

additem.php is the bread and butter of IS4C. addItem inserts the information
stream for each item scanned, entered or transaction occurence into localtemptrans.
Each of the above follows the following structure for entry into localtemptrans:
	$strupc, 
	$strdescription, 
	$strtransType, 
	$strtranssubType, 
	$strtransstatus, 
	$intdepartment, 
	$dblquantity, 
	$dblunitPrice, 
	$dbltotal, 
	$dblregPrice, 
	$intscale, 
	$inttax, 
	$intfoodstamp, 
	$dbldiscount, 
	$dblmemDiscount, 
	$intdiscountable, 
	$intdiscounttype, 
	$dblItemQtty, 
	$intvolDiscType, 
	$intvolume, 
	$dblVolSpecial, 
	$intmixMatch, 
	$intmatched, 
	$intvoided

Additionally, additem.php inserts entries into the activity log when a cashier 
signs in
-------------------------------------------------------------------------------*/

if (!function_exists("pDataConnect")) include("connect.php");
if (!function_exists("tDataConnect")) include("connect.php");
if (!function_exists("nullwrap")) include("lib.php");
if (!function_exists("truncate2")) include ("lib.php");


//-------insert line into localtemptrans with standard insert string--------------

function addItem($strupc, $strdescription, $strtransType, $strtranssubType, $strtransstatus, $intdepartment, $dblquantity, $dblunitPrice, $dbltotal, $dblregPrice, $intscale, $inttax, $intfoodstamp, $dbldiscount, $dblmemDiscount, $intdiscountable, $intdiscounttype, $dblItemQtty, $intvolDiscType, $intvolume, $dblVolSpecial, $intmixMatch, $intmatched, $intvoided) {

	//$dbltotal = truncate2(str_replace(",", "", $dbltotal)); replaced by apbw 7/27/05 with the next 4 lines -- to fix thousands place errors

	$dbltotal = str_replace(",", "", $dbltotal);		
	$dbltotal = number_format($dbltotal, 2, '.', '');
	$dblunitPrice = str_replace(",", "", $dblunitPrice);
	$dblunitPrice = number_format($dblunitPrice, 2, '.', '');

	if ($_SESSION["refund"] == 1) {
		$dblquantity = (-1 * $dblquantity);
		$dbltotal = (-1 * $dbltotal);
		$dbldiscount = (-1 * $dbldiscount);
		$dblmemDiscount = (-1 * $dblmemDiscount);

		if ($strtransstatus != "V" && $strtransstatus != "D") $strtransstatus = "R" ;	// edited by apbw 6/04/05 to correct voiding of refunded items

		$_SESSION["refund"] = 0;
	}

	elseif ($_SESSION["void"] == 1) {
		$dblquantity = (-1 * $dblquantity);
		$dbltotal = (-1 * $dbltotal);
		$strtransstatus = "V";
		$_SESSION["void"] = 0;

	}


	$intregisterno = $_SESSION["laneno"];
	$intempno = $_SESSION["CashierNo"];
	$inttransno = $_SESSION["transno"];
	$strCardNo = $_SESSION["memberID"];
	$memType = $_SESSION["memType"];
	$staff = $_SESSION["isStaff"];

	$db = tDataConnect();

	if ($_SESSION["DBMS"] == "mssql") {
		$datetimestamp = strftime("%m/%d/%y %H:%M:%S %p", time());
	} else {
		$datetimestamp = strftime("%Y-%m-%d %H:%M:%S %p", time());
	}

	$_SESSION["datetimestamp"] = $datetimestamp;
	$_SESSION["LastID"] = $_SESSION["LastID"] + 1;
	
	$trans_id = $_SESSION["LastID"];
	
	$strqinsert = "INSERT into localtemptrans (datetime, register_no, emp_no, trans_no, upc, description, trans_type, "
	            ."trans_subtype, trans_status, department, quantity, unitPrice, total, regPrice, scale, tax, "
		      ."foodstamp, discount, memDiscount, discountable, discounttype, ItemQtty, volDiscType, volume, "
		      ."VolSpecial, mixMatch, matched, voided, memType, staff, card_no) "
		      ."values (" 


		      ."'".$datetimestamp."', "
		      .$intregisterno.", "
		      .$intempno.", "
		      .nullwrap($inttransno).", "
		      ."'".nullwrap($strupc)."', "
		      ."'".$strdescription."', "
		      ."'".nullwrap($strtransType)."', "
		      ."'".nullwrap($strtranssubType)."', "
		      ."'".nullwrap($strtransstatus)."', "
		      .nullwrap($intdepartment).", "
		      .nullwrap($dblquantity).", "
		      .nullwrap($dblunitPrice).", "
		      .nullwrap($dbltotal).", "
		      .nullwrap($dblregPrice).", "
		      .nullwrap($intscale).", "
		      .nullwrap($inttax).", "
		      .nullwrap($intfoodstamp).", "
		      .nullwrap($dbldiscount).", "
		      .nullwrap($dblmemDiscount).", "
		      .nullwrap($intdiscountable).", "
		      .nullwrap($intdiscounttype).", "
		      .nullwrap($dblItemQtty).", "
		      .nullwrap($intvolDiscType).", "
		      .nullwrap($intvolume).", "
		      .nullwrap($dblVolSpecial).", "
		      .nullwrap($intmixMatch).", "
		      .nullwrap($intmatched).", "
		      .nullwrap($intvoided).", "
			  .nullwrap($memType).", "
			  .nullwrap($staff).", "
			."'".(string) $strCardNo."') ";
			// .$trans_id.") ";


        //	echo $strqinsert;

	sql_query($strqinsert, $db);
	sql_close($db);

	if ($strtransType == "I" || $strtransType == "D") {
		goodBeep();
		if ($intscale == 1) {
			$_SESSION["screset"] = "rePoll";
		}

		elseif ($_SESSION["weight"] != 0) {
			$_SESSION["screset"] = "rePoll";
		}
		$_SESSION["repeatable"] = 1;
	}
	
	$_SESSION["msgrepeat"] = 0;
	$_SESSION["toggletax"] = 0;
	$_SESSION["togglefoodstamp"] = 0;
	$_SESSION["SNR"] = 0;
	$_SESSION["wgtRequested"] = 0;
	$_SESSION["nd"] = 0;

	$_SESSION["ccAmtEntered"] = 0;
	$_SESSION["ccAmt"] = 0;

}

//________________________________end addItem()


//---------------------------------- insert tax line item --------------------------------------

function addtax() {
	addItem("TAX", "Tax", "A", "", "", 0, 0, 0, $_SESSION["taxTotal"], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
}

//________________________________end addtax()


//---------------------------------- insert tender line item -----------------------------------

function addtender($strtenderdesc, $strtendercode, $dbltendered) {
	addItem("", $strtenderdesc, "T", $strtendercode, "", 0, 0, 0, $dbltendered, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
}

//_______________________________end addtender()


//---------------------------------- insert foodstamps line item ------------------------------

function addfstender($strtenderdesc, $strtendercode, $dbltendered) {
	addItem("", $strtenderdesc, "T", $strtendercode, "", 0, 0, truncate2($_SESSION["fsEligible"]), $dbltendered, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
}

//_______________________________end addfstender()


//--------------------------------- insert change line item ------------------------------------

function addchange($dblcashreturn) {
	addItem("", "Change", "T", "CA", "", 0, 0, 0, $dblcashreturn, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 8);
}

//_______________________________end addchange()


//-------------------------------- insert foods stamp change item ------------------------------

function addfsones($intfsones) {
	addItem("", "FS Change", "T", "FS", "", 0, 0, 0, $intfsones, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 8);
}

//_______________________________end addfsones()

//---------------------------------- insert loan line item --------------------------------------

//function addloan($loanamt) {
//	addItem("LOAN", "Loan", "L", "", "", 0, 0, 0, $loanamt, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
//}

//________________________________end addloan()


//---------------------------------- insert End of Shift  --------------------------$
function addEndofShift() {
	addItem("ENDOFSHIFT", "End of Shift", "S", "", "", 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
}


//________________________________ End End of Shift ____________________________

//-------------------------------- insert deli discount (Wedge specific) -----------------------

function addscDiscount() {

	if ($_SESSION["scDiscount"] != 0) {
		addItem("DISCOUNT", "** 10% Deli Discount **", "I", "", "", 0, 1, truncate2(-1 * $_SESSION["scDiscount"]), truncate2(-1 * $_SESSION["scDiscount"]), 0, 0, 0, 0, 0, 0, 1, 0, 1, 0, 0, 0, 0, 0, 2);
	}
//	addStaffCoffeeDiscount();

}

function addStaffCoffeeDiscount() {
	if ($_SESSION["staffCoffeeDiscount"] != 0) {
		addItem("DISCOUNT", "** Coffee Discount **", "I", "", "", 0, 1, truncate2(-1 * $_SESSION["staffCoffeeDiscount"]), truncate2(-1 * $_SESSION["staffCoffeeDiscount"]), 0, 0, 0, 0, 0, 0, 1, 0, 1, 0, 0, 0, 0, 0, 2);
	}
}



//_______________________________end addscDiscount()


//------------------------------- insert discount line -----------------------------------------

function adddiscount($dbldiscount) {
	$strsaved = "** YOU SAVED $".truncate2($dbldiscount)." **";
	addItem("", $strsaved, "I", "", "D", 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2);
}

//_____________________________end adddiscount()


//------------------------------ insert Food Stamp Tax Exempt line -----------------------------


function addfsTaxExempt() {
	getsubtotals();
	addItem("FS Tax Exempt", " Fs Tax Exempt ", "C", "", "D", 0, 0, $_SESSION["fsTaxExempt"], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 17);
}

//_____________________________end addfsTaxExempt()


//------------------------------ insert 'discount applied' line --------------------------------

function discountnotify($strl) {
	if ($strl == 10.01) {
		$strL = 10;
	}
	addItem("", "** ".$strl."% Discount Applied **", "", "", "D", 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4);
}

//_____________________________end discountnotify()


//------------------------------- insert discount line -----------------------------------------

function addpercentDiscount() {
	addtender($_SESSION["percentDiscount"]."% Discount", "PD", truncate2($_SESSION["transDiscount"]));
}

//_____________________________end addpercentDiscount()


//------------------------------- insert tax exempt statement line -----------------------------

function addTaxExempt() {
	addItem("", "** Order is Tax Exempt **", "", "", "D", 0, 0, 0, 0, 0, 0, 9, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 10);
	$_SESSION["TaxExempt"] = 1;
	setglobalvalue("TaxExempt", 1);
}

//_____________________________end addTaxExempt()


//------------------------------ insert reverse tax exempt statement ---------------------------

function reverseTaxExempt() {
	addItem("", "** Tax Exemption Reversed **", "", "", "D", 0, 0, 0, 0, 0, 0, 9, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 10);
	$_SESSION["TaxExempt"] = 0;
	setglobalvalue("TaxExempt", 0);
}

//_____________________________end reverseTaxExempt()

//------------------------------ insert case discount statement --------------------------------

function addcdnotify() {
	addItem("", "** ".$_SESSION["casediscount"]."% Case Discount Applied", "", "", "D", 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 6);
}

//____________________________end addcdnotify()

//------------------------------ insert manufacturer coupon statement --------------------------

function addCoupon($strupc, $intdepartment, $dbltotal) {
	addItem($strupc, " * Manufacturers Coupon", "I", "MC", "C", $intdepartment, 1, $dbltotal, $dbltotal, $dbltotal, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0);	
}

//___________________________end addCoupon()

//------------------------------ insert tare statement -----------------------------------------

function addTare($dbltare) {
	$_SESSION["tare"] = $dbltare/100;
	addItem("", "** Tare Weight ".$_SESSION["tare"]." **", "", "", "D", 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 6);
}

//___________________________end addTare()


//------------------------------- insert MAD coupon statement (WFC specific) -------------------

function addMadCoup() {

		$madCoup = -1 * $_SESSION["madCoup"];
		addItem("MAD Coupon", "Member Appreciation Coupon", "I", "CP", "C", 0, 1, $madCoup, $madCoup, $madCoup, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 17);
}


//___________________________end addMadCoupon()


//-------------------------------- insert deposit record --------------------------------------

function addDeposit($quantity, $deposit, $foodstamp) {

	$total = $quantity * $deposit;
	$chardeposit = 100 * $deposit;
	if($foodstamp == 1){  //  ACG HARDCODED DEPARTMENTS....
		$dept = 43;
	}else{
		$dept = 42;
	}
	addItem("DEPOSIT" * $chardeposit, "Deposit", "I", "", "", $dept, $quantity, $deposit, $total, $deposit, 0, 0, $foodstamp, 0, 0, 0, 0, $quantity, 0, 0, 0, 0, 0, 0);
		
}

//_________________________________end addDeposit


// ----------------------------- insert transaction discount -----------------------------------

function addtransDiscount() {
	addItem("DISCOUNT", "Discount", "I", "", "", 0, 1, truncate2(-1 * $_SESSION["transDiscount"]), truncate2(-1 * $_SESSION["transDiscount"]), 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0);
}

// ---------------------------- insert stamp in activitytemplog --------------------------------

function addactivity($activity) {

	$timeNow = time();

	if ($_SESSION["CashierNo"] > 0 && $_SESSION["CashierNo"] < 256) {
		$intcashier = $_SESSION["CashierNo"];
	}
	else {
		$intcashier = 0;
	}

	if ($_SESSION["DBMS"] == "mssql") {
		$strqtime = "select max(datetime) as maxDateTime, getdate() as rightNow from activitytemplog";
	} else {
		$strqtime = "select max(datetime) as maxDateTime, now() as rightNow from activitytemplog";
	}


	$db = tDataConnect();
	$result = sql_query($strqtime, $db);


	$row = sql_fetch_array($result);

	if (!$row || !$row[0]) {

		$interval = 0;
	}
	else {

		$interval = strtotime($row["rightNow"]) - strtotime($row["maxDateTime"]);
	}
		
	$_SESSION["datetimestamp"] = strftime("%Y-%m-%d %H:%M:%S", $timeNow);

	$strq = "insert into activitytemplog values ("
		."'".nullwrap($_SESSION["datetimestamp"])."', "
		.nullwrap($_SESSION["laneno"]).", "
		.nullwrap($intcashier).", "
		.nullwrap($_SESSION["transno"]).", "
		.nullwrap($activity).", "
		.nullwrap($interval).")";

	// echo $strq;

	$result = sql_query($strq, $db);
	
	// $_SESSION["datetimestamp"] = time();

	sql_close($db);

}


// THESE MUST BE TIMESTAMPS !!

function timeinterval($date1, $date2) {

	$interval = $date2 - $date1;
	return $interval;

}

// function secInterval($date1, $date2)


//
//	hourVal= hour(date)
//	minVal = minute(date)
//	secondVal = second(date)
//
//	seconds = (hourVal * 3600) + (minVal * 60) + secondVal	
//
//end function

// ------------------------------------------------------------------------


?>
