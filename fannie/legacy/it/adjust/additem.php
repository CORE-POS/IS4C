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

//-------insert line into localtemptrans with standard insert string--------------

function addItem($strupc, $strdescription, $strtransType, $strtranssubType, $strtransstatus, $intdepartment, $dblquantity, $dblunitPrice, $dbltotal, $dblregPrice, $intscale, $inttax, $intfoodstamp, $dbldiscount, $dblmemDiscount, $intdiscountable, $intdiscounttype, $dblItemQtty, $intvolDiscType, $intvolume, $dblVolSpecial, $intmixMatch, $intmatched, $intvoided,$registerno=30,$empno=1001) {
	global $CARDNO,$TRANSNO,$DATESTR,$TRANS_ID,$TODAY_FLAG,$sql,$MEMTYPE,$ISSTAFF;

	$dbltotal = str_replace(",", "", $dbltotal);		
	$dbltotal = number_format($dbltotal, 2, '.', '');
	$dblunitPrice = str_replace(",", "", $dblunitPrice);
	$dblunitPrice = number_format($dblunitPrice, 2, '.', '');

	$intregisterno = $registerno;
	$intempno = $empno;
	$inttransno = $TRANSNO;
	$strCardNo = $CARDNO;

	$datetimestamp = $DATESTR;
	$TRANS_ID += 1;
	
	$strqinsert = "(datetime, register_no, emp_no, trans_no, upc, description, trans_type, "
	            ."trans_subtype, trans_status, department, quantity, unitPrice, total, regPrice, scale, tax, "
		      ."foodstamp, discount, memDiscount, discountable, discounttype, ItemQtty, volDiscType, volume, "
		      ."VolSpecial, mixMatch, matched, voided, memType, isStaff, card_no, trans_id) "
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
			.nullwrap($MEMTYPE).", "
			.nullwrap($ISSTAFF).", "
			."'".(string) $strCardNo."',"
			.$TRANS_ID.")";

	$sql->query("INSERT dtransactions ".$strqinsert);
	if ($TODAY_FLAG != 1)
		$sql->query("INSERT transarchive ".$strqinsert);
}

//________________________________end addItem()

//---------------------------------- insert tax line item --------------------------------------

function addtax($tax) {
	addItem("TAX", "Tax", "A", "", "", 0, 0, 0, $tax, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
}

//________________________________end addtax()

// ----------------------------- insert transaction discount -----------------------------------

function addtransDiscount($disc) {
	addItem("DISCOUNT", "Discount", "I", "", "", 0, 1, number_format($disc,2), number_format($disc,2), 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0);
}


//---------------------------------- insert tender line item -----------------------------------
function addtender($strtenderdesc, $strtendercode, $dbltendered) {
	addItem("", $strtenderdesc, "T", $strtendercode, "", 0, 0, 0, $dbltendered, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
}

//_______________________________end addtender()

function addcomment($comment) {
	addItem("",$comment, "C", "CM", "D", 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
}

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


//------------------------------- insert discount line -----------------------------------------

function adddiscount($dbldiscount,$department) {
	$strsaved = "** YOU SAVED $".number_format($dbldiscount,2)." **";
	addItem("", $strsaved, "I", "", "D", $department, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2);
}

//_____________________________end adddiscount()


//------------------------------ insert 'discount applied' line --------------------------------

function discountnotify($strl) {
	if ($strl == 10.01) {
		$strL = 10;
	}
	addItem("", "** ".$strl."% Discount Applied **", "", "", "D", 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4);
}

//_____________________________end discountnotify()


//------------------------------- insert tax exempt statement line -----------------------------

function addTaxExempt() {
	addItem("", "** Order is Tax Exempt **", "", "", "D", 0, 0, 0, 0, 0, 0, 9, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 10);
}

//_____________________________end addTaxExempt()


//------------------------------ insert reverse tax exempt statement ---------------------------

function reverseTaxExempt() {
	addItem("", "** Tax Exemption Reversed **", "", "", "D", 0, 0, 0, 0, 0, 0, 9, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 10);
}

//_____________________________end reverseTaxExempt()

//------------------------------ insert manufacturer coupon statement --------------------------

function addCoupon($strupc, $intdepartment, $dbltotal) {
	addItem($strupc, " * Manufacturers Coupon", "I", "CP", "C", $intdepartment, 1, $dbltotal, $dbltotal, $dbltotal, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0);	
}

//___________________________end addCoupon()

//------------------------------ insert tare statement -----------------------------------------

function addTare($dbltare) {
	$tare = $dbltare/100;
	addItem("", "** Tare Weight ".$tare." **", "", "", "D", 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 6);
}

//___________________________end addTare()


//------------------------------- insert MAD coupon statement (WFC specific) -------------------

function addMadCoup() {
		$madCoup = -1 * $_SESSION["madCoup"];
		addItem("MAD Coupon", "Member Appreciation Coupon", "I", "CP", "C", 0, 1, $madCoup, $madCoup, $madCoup, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 17);
		
}

//___________________________end addMadCoupon()

function nullwrap($num) {


	if ( !$num ) {
		 return 0;
	}
	elseif (!is_numeric($num) && strlen($num) < 1) {
		return " ";
	}
	else {
		return $num;
	}
}

?>
