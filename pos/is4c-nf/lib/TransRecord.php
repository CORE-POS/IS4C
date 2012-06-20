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
  @class TransRecord
  Defines functions for adding records to the transaction
*/
class TransRecord extends LibraryClass {

/*------------------------------------------------------------------------------
additem.php is the bread and butter of IT CORE. addItem inserts the information
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

/**
  Add an item to localtemptrans.
  Parameters correspond to columns in localtemptrans. See that table
  for valid types.
  @param $strupc localtemptrans.upc
  @param $strdescription localtemptrans.description
  @param $strtransType localtemptrans.trans_type
  @param $strtranssubType localtemptrans.trans_subtype
  @param $strtransstatuts localtemptrans.trans_status
  @param $dblquantity localtemptrans.quantity
  @param $dblunitPrice localtemptrans.unitPrice
  @param $dbltotal localtemptrans.total
  @param $dblregPrice localtemptrans.regPrice
  @param $intscale localtemptrans.scale
  @param $inttax localtemptrans.tax
  @param $intfoodstamp localtemptrans.foodstamp
  @param $dbldiscount localtemptrans.discount
  @param $dblmemDiscount localtemptrans.memDiscount
  @param $intdiscountable localtemptrans.discounttable
  @param $intdiscounttype localtemptrans.discounttype
  @param $dblItemQtty localtemptrans.ItemQtty
  @param $intvolDiscType localtemptrans.volDiscType
  @param $intvolume localtemptrans.volume
  @param $dblVolSpecial localtemptrans.VolSpecial
  @param $intmixMatch localtemptrans.mixMatch
  @param $intmatched localtemptrans.matched
  @param $intvoided localtemptrans.voided
  @param $cost localtemptrans.cost
  @param $numflag localtemptrans.numflag
  @param $charflag localtemptrans.charflag

  In many cases there is a simpler function that takes far
  fewer arguments and adds a specific type of record.
  All such functions should be in this file.
*/
static public function addItem($strupc = '', 
							   $strdescription,
							   $strtransType,
							   $strtranssubType = '',
							   $strtransstatus = '',
							   $intdepartment = 0,
							   $dblquantity = 0,
							   $dblunitPrice = 0,
							   $dbltotal = 0,
							   $dblregPrice = 0,
							   $intscale = 0,
							   $inttax = 0,
							   $intfoodstamp = 0,
							   $dbldiscount = 0,
							   $dblmemDiscount = 0,
							   $intdiscountable = 0,
							   $intdiscounttype = 0,
							   $dblItemQtty = 0,
							   $intvolDiscType = 0,
							   $intvolume = 0,
							   $dblVolSpecial = 0,
							   $intmixMatch = 0,
							   $intmatched = 0,
							   $intvoided = 0,
							   $cost = 0,
							   $numflag = 0,
							   $charflag = '' ) {
	global $CORE_LOCAL;
	//$dbltotal = MiscLib::truncate2(str_replace(",", "", $dbltotal)); replaced by apbw 7/27/05 with the next 4 lines -- to fix thousands place errors

	$dbltotal = str_replace(",", "", $dbltotal);		
	$dbltotal = number_format($dbltotal, 2, '.', '');
	$dblunitPrice = str_replace(",", "", $dblunitPrice);
	$dblunitPrice = number_format($dblunitPrice, 2, '.', '');

	if ($CORE_LOCAL->get("refund") == 1) {
		$dblquantity    = (-1 * $dblquantity);
		$dbltotal       = (-1 * $dbltotal);
		$dbldiscount    = (-1 * $dbldiscount);
		$dblmemDiscount = (-1 * $dblmemDiscount);

		if ($strtransstatus != "V" && $strtransstatus != "D") $strtransstatus = "R" ;	// edited by apbw 6/04/05 to correct voiding of refunded items

		$CORE_LOCAL->set("refund",        0 );
		$CORE_LOCAL->set("refundComment", "");
	}

	/* Nothing in the code can set $_SESSION["void"] to 1
	elseif ($_SESSION["void"] == 1) {
		$dblquantity = (-1 * $dblquantity);
		$dbltotal = (-1 * $dbltotal);
		$strtransstatus = "V";
		$_SESSION["void"] = 0;
	}
	 */


	$intregisterno = $CORE_LOCAL->get("laneno");
	$intempno      = $CORE_LOCAL->get("CashierNo");
	$inttransno    = $CORE_LOCAL->get("transno");
	$strCardNo     = $CORE_LOCAL->get("memberID");
	$memType       = $CORE_LOCAL->get("memType");
	$staff         = $CORE_LOCAL->get("isStaff");

	$db = Database::tDataConnect();

	$datetimestamp = "";
	if ($CORE_LOCAL->get("DBMS") == "mssql") {
		$datetimestamp = strftime("%m/%d/%y %H:%M:%S %p", time());
	} else {
		$datetimestamp = strftime("%Y-%m-%d %H:%M:%S", time());
	}

	// this session variable never gets used
	//$_SESSION["datetimestamp"] = $datetimestamp;
	$CORE_LOCAL->set("LastID", $CORE_LOCAL->get("LastID") + 1);
	
	$trans_id = $CORE_LOCAL->get("LastID");

	$values = array(
		'datetime'	=> $datetimestamp,
		'register_no'	=> $intregisterno,
		'emp_no'	=> $intempno,
		'trans_no'	=> MiscLib::nullwrap($inttransno),
		'upc'		=> MiscLib::nullwrap($strupc),
		'description'	=> $db->escape($strdescription),
		'trans_type'	=> MiscLib::nullwrap($strtransType),
		'trans_subtype'	=> MiscLib::nullwrap($strtranssubType),
		'trans_status'	=> MiscLib::nullwrap($strtransstatus),
		'department'	=> MiscLib::nullwrap($intdepartment),
		'quantity'	=> MiscLib::nullwrap($dblquantity),
		'cost'		=> MiscLib::nullwrap($cost),
		'unitPrice'	=> MiscLib::nullwrap($dblunitPrice),
		'total'		=> MiscLib::nullwrap($dbltotal),
		'regPrice'	=> MiscLib::nullwrap($dblregPrice),
		'scale'		=> MiscLib::nullwrap($intscale),
		'tax'		=> MiscLib::nullwrap($inttax),
		'foodstamp'	=> MiscLib::nullwrap($intfoodstamp),
		'discount'	=> MiscLib::nullwrap($dbldiscount),
		'memDiscount'	=> MiscLib::nullwrap($dblmemDiscount),
		'discountable'	=> MiscLib::nullwrap($intdiscountable),
		'discounttype'	=> MiscLib::nullwrap($intdiscounttype),
		'ItemQtty'	=> MiscLib::nullwrap($dblItemQtty),
		'volDiscType'	=> MiscLib::nullwrap($intvolDiscType),
		'volume'	=> MiscLib::nullwrap($intvolume),
		'VolSpecial'	=> MiscLib::nullwrap($dblVolSpecial),
		'mixMatch'	=> MiscLib::nullwrap($intmixMatch),
		'matched'	=> MiscLib::nullwrap($intmatched),
		'voided'	=> MiscLib::nullwrap($intvoided),
		'memType'	=> MiscLib::nullwrap($memType),
		'staff'		=> MiscLib::nullwrap($staff),
		'numflag'	=> MiscLib::nullwrap($numflag),
		'charflag'	=> $charflag,
		'card_no'	=> (string)$strCardNo
		);
	if (($CORE_LOCAL->get("DBMS") == "mssql") &&
    	($CORE_LOCAL->get("store") == "wfc")){
		unset($values["staff"]);
		$values["isStaff"] = MiscLib::nullwrap($staff);
	}

	$db->smart_insert("localtemptrans", $values);
	$db->close();

	if (($strtransType == "I") ||
    	($strtransType == "D")) {
		$CORE_LOCAL->set("beep", "goodBeep");
		if ($intscale == 1) {
			$CORE_LOCAL->set("screset", "rePoll");
		}
		elseif ($CORE_LOCAL->get("weight") != 0) {
			$CORE_LOCAL->set("screset", "rePoll");
		}
		$CORE_LOCAL->set("repeatable", 1);
	}
	
	$CORE_LOCAL->set("msgrepeat", 0);
	$CORE_LOCAL->set("toggletax", 0);
	$CORE_LOCAL->set("togglefoodstamp", 0);
	$CORE_LOCAL->set("SNR", 0);
	$CORE_LOCAL->set("wgtRequested", 0);
	$CORE_LOCAL->set("nd", 0);

	$CORE_LOCAL->set("ccAmtEntered", 0);
	$CORE_LOCAL->set("ccAmt", 0);

}

//________________________________end addItem()


//---------------------------------- insert tax line item --------------------------------------

/**
   Add a tax record to the transaction. Amount is
   pulled from session info automatically.
*/
static public function addtax() {
	global $CORE_LOCAL;
/*  self::addItem($strupc => "TAX",
                  $strdescription => "Tax",
                  $strtransType => "A",
				  $dbltotal => $CORE_LOCAL->get("taxTotal") );
*/
	self::addItem(array("strupc" => "TAX",
                        "strdescription" => "Tax",
                        "strtransType" => "A",
				        "dbltotal" => $CORE_LOCAL->get("taxTotal") ) );
}

//________________________________end addtax()


//---------------------------------- insert tender line item -----------------------------------

/**
  Add a tender record to the transaction
  @param $strtenderdesc is a description, such as "Credit Card"
  @param $strtendercode is a 1-2 character code, such as "CC"
  @param $dbltendered is the amount. Remember that payments are
  <i>negative</i> amounts. 
*/
static public function addtender($strtenderdesc, $strtendercode, $dbltendered) {
	self::addItem(array("strdescription" => $strtenderdesc,
                        "strtransType" => "T",
				        "strtranssubType" => $strtendercode,
				        "dbltotal" => $dbltendered ) );
}

//_______________________________end addtender()


/**
  Add a comment to the transaction
  @param $comment is the comment text. Max length allowed 
  is 30 characters.
*/
static public function addcomment($comment) {
	if (strlen($comment) > 30)
		$comment = substr($comment,0,30);
	self::addItem(array("strdescription" => $comment,
                        "strtransType" => "C",
                        "strtranssubType" => "CM",
                        "strtransstatus" => "D" ) );
}


//--------------------------------- insert change line item ------------------------------------

/**
  Add a change record (a special type of tender record)
  @param $dblcashreturn the change amount
*/
static public function addchange($dblcashreturn) {
	self::addItem(array("strdescription" => "Change",
				        "strtransType" => "T",
                        "strtranssubType" => "CA",
				        "dbltotal" => $dblcashreturn,
				        "intvoided" => 8 ) );
}

//_______________________________end addchange()


//-------------------------------- insert foods stamp change item ------------------------------

/**
  Add a foodstamp change record
  @param $intfsones the change amount

  Please do verify cashback is permitted with EBT transactions
  in your area before using this.
*/
static public function addfsones($intfsones) {
//	self::addItem("", "FS Change", "T", "FS", "", 0, 0, 0, $intfsones, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 8);
	self::addItem(array("strdescription" => "FS Change",
                        "strtransType" => "T",
                        "strtranssubType" => "FS",
				        "dbltotal" => $intfsones,
				        "intvoided" => 8 ) );
}

//_______________________________end addfsones()

/**
  Add end of shift record
  @deprecated
*/
static public function addEndofShift() {
	self::addItem("ENDOFSHIFT", "End of Shift", "S", "", "", 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
}

//-------------------------------- insert deli discount (Wedge specific) -----------------------

/**
  Add Wedge deli discount
  @deprecated
*/
static public function addscDiscount() {
	global $CORE_LOCAL;

	if ($CORE_LOCAL->get("scDiscount") != 0) {
		self::addItem("DISCOUNT", "** 10% Deli Discount **", "I", "", "", 0, 1, MiscLib::truncate2(-1 * $CORE_LOCAL->get("scDiscount")), MiscLib::truncate2(-1 * $CORE_LOCAL->get("scDiscount")), 0, 0, 0, 0, 0, 0, 1, 0, 1, 0, 0, 0, 0, 0, 2);
	}
//	addStaffCoffeeDiscount();

}

/**
  Add Wedge coffee discount
  @deprecated
*/
static public function addStaffCoffeeDiscount() {
	global $CORE_LOCAL;

	if ($CORE_LOCAL->get("staffCoffeeDiscount") != 0) {
		self::addItem("DISCOUNT", "** Coffee Discount **", "I", "", "", 0, 1, MiscLib::truncate2(-1 * $CORE_LOCAL->get("staffCoffeeDiscount")), MiscLib::truncate2(-1 * $CORE_LOCAL->get("staffCoffeeDiscount")), 0, 0, 0, 0, 0, 0, 1, 0, 1, 0, 0, 0, 0, 0, 2);
	}
}

//_______________________________end addscDiscount()


//------------------------------- insert discount line -----------------------------------------

/***** jqh 09/29/05 changed adddiscount function to write the department to localtemptrans *****/
/**
  Add a "YOU SAVED" record to the transaction. This is just informational
  and will not alter totals.
  @param $dbldiscount discount amount
  @param $department associated department
*/
static public function adddiscount($dbldiscount,$department) {
	$strsaved = "** YOU SAVED $".MiscLib::truncate2($dbldiscount)." **";
	self::addItem("", $strsaved, "I", "", "D", $department, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2);
}

//_____________________________end adddiscount()


//------------------------------ insert Food Stamp Tax Exempt line -----------------------------


/**
  Add tax exemption for foodstamps
*/
static public function addfsTaxExempt() {
	global $CORE_LOCAL;

	Database::getsubtotals();
	self::addItem("FS Tax Exempt", " Fs Tax Exempt ", "C", "", "D", 0, 0, $CORE_LOCAL->get("fsTaxExempt"), 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 17);
}

//_____________________________end addfsTaxExempt()


//------------------------------ insert 'discount applied' line --------------------------------

/**
  Add a information record showing transaction-wide percent discount
  @param $discPct the percentage
 
*/
static public function discountnotify($discPct) {
/*
	if ($strl == 10.01) {
		$strl = 10;       // DHermann 20jun12: was strL -- 'if' is for odd WFC value?
	}
*/

//  DHermann 20jun12: starting rewrite ...
//	self::addItem("", "** ".$strl."% Discount Applied **", "", "", "D", 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4);
	
	if ($discPct == 10.01) {
		$discPct = 10;
	}
	self::addItem(array("strdescription" => ("** " . $discPct . "% Discount Applied **"),
		  		        "strtransstatus" => "D",
				        "intvoided" => 4) );
}

//_____________________________end discountnotify()


//------------------------------- insert discount line -----------------------------------------

//------------------------------- insert tax exempt statement line -----------------------------

/**
  Add tax exemption record to transaction
*/
static public function addTaxExempt() {
	global $CORE_LOCAL;

	$CORE_LOCAL->set("TaxExempt",1);
//  self::addItem("", "** Order is Tax Exempt **", "", "", "D", 0, 0, 0, 0, 0, 0, 9, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 10);
	self::addItem(array("strdescription" => "** Order is Tax Exempt **",
		  		        "strtransstatus" => "D",
				        "inttax" => 9,
				        "intvoided" => 10) );
	Database::setglobalvalue("TaxExempt", 1);
}

//_____________________________end addTaxExempt()


//------------------------------ insert reverse tax exempt statement ---------------------------

/**
  Add record to undo tax exemption
*/
static public function reverseTaxExempt() {
	global $CORE_LOCAL;
//  self::addItem("", "** Order is Tax Exempt **",    "", "", "D", 0, 0, 0, 0, 0, 0, 9, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 10);  FOR COMPARISON
//	self::addItem("", "** Tax Exemption Reversed **", "", "", "D", 0, 0, 0, 0, 0, 0, 9, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 10);
	self::addItem(array("strdescription" => "** Tax Exemption Reversed **",
		  		        "strtransstatus" => "D",
				        "inttax" => 9,
				        "intvoided" => 10 ) );
	$CORE_LOCAL->set("TaxExempt", 0);
	Datbase::setglobalvalue("TaxExempt", 0);
}

//_____________________________end reverseTaxExempt()

//------------------------------ insert case discount statement --------------------------------

/** 
  Add an informational record noting case discount
  $CORE_LOCAL setting "casediscount" controls the percentage
  shown
*/
static public function addcdnotify() {
	global $CORE_LOCAL;
	self::addItem("", "** ".$CORE_LOCAL->get("casediscount")."% Case Discount Applied", "", "", "D", 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 6);
}

//____________________________end addcdnotify()

//------------------------------ insert manufacturer coupon statement --------------------------

/**
  Add a manufacturer coupon record
  @param $strupc coupon UPC
  @param $intdepartment associated POS department
  @param $dbltotal coupon amount (should be negative)
  @param $foodstamp mark coupon foodstamp-able
*/
static public function addCoupon($strupc, $intdepartment, $dbltotal, $foodstamp=0) {
	self::addItem($strupc, " * Manufacturers Coupon", "I", "CP", "C", $intdepartment, 1, $dbltotal, $dbltotal, $dbltotal, 0, 0, $foodstamp, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0);	
}

/**
  Add an in-store coupon
  @param $strupc coupon UPC
  @param $intdepartment associated POS department
  @param $dbltotal coupon amount (should be negative)
*/
static public function addhousecoupon($strupc, $intdepartment, $dbltotal) {
	self::addItem($strupc, " * WFC Coupon", "I", "IC", "C", $intdepartment, 1, $dbltotal, $dbltotal, $dbltotal, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0);
}

/**
  Add a line-item discount
  @param $intdepartment POS department
  @param $dbltotal discount amount (should be <b>positive</b>)
*/
static public function additemdiscount($intdepartment, $dbltotal) {
	$dbltotal *= -1;
	self::addItem('ITEMDISCOUNT'," * Item Discount", "I", "", "", $intdepartment, 1, $dbltotal, $dbltotal, $dbltotal, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0);
}


//___________________________end addCoupon()

//------------------------------ insert tare statement -----------------------------------------

/**
  Add a tare record
  @param $dbltare the tare weight. The weight
  gets divided by 100, so an argument of 5 gives tare 0.05
*/
/*
static public function addItem($strupc = '', 
							   $strdescription,
							   $strtransType,
							   $strtranssubType = '',
							   $strtransstatus = '',
							   $intdepartment = 0,
							   $dblquantity = 0,
							   $dblunitPrice = 0,
							   $dbltotal = 0,
							   $dblregPrice = 0,
							   $intscale = 0,
							   $inttax = 0,
							   $intfoodstamp = 0,
							   $dbldiscount = 0,
							   $dblmemDiscount = 0,
							   $intdiscountable = 0,
							   $intdiscounttype = 0,
							   $dblItemQtty = 0,
							   $intvolDiscType = 0,
							   $intvolume = 0,
							   $dblVolSpecial = 0,
							   $intmixMatch = 0,
							   $intmatched = 0,
							   $intvoided = 0,
							   $cost = 0,
							   $numflag = 0,
							   $charflag = '' ) {
*/
static public function addTare($dbltare) {
	global $CORE_LOCAL;
	$CORE_LOCAL->set("tare",$dbltare/100);
//	self::addItem("", "** Tare Weight ".$CORE_LOCAL->get("tare")." **", "", "", "D", 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 6);
	self::addItem(array("strdescription" => ("** Tare Weight " . $CORE_LOCAL->get("tare") . " **"),
                        "strtranstatus" => "D",
				        "intvoided" => 6 ) );
}

//___________________________end addTare()


//------------------------------- insert MAD coupon statement (WFC specific) -------------------

/**
  Add WFC virtual coupon
  @deprecated
*/
static public function addMadCoup() {
	global $CORE_LOCAL;

	$madCoup = -1 * $CORE_LOCAL->get("madCoup");
	self::addItem("MAD Coupon", "Member Appreciation Coupon", "I", "CP", "C", 0, 1, $madCoup, $madCoup, $madCoup, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 17);
		
}

/**
  Add a virtual coupon by ID
  @param $id identifier in the VirtualCoupon table
*/
static public function addVirtualCoupon($id){
	global $CORE_LOCAL;
	$sql = Database::pDataConnect();
	$fetchQ = "select name,type,value,max from VirtualCoupon WHERE flag=$id";
	$fetchR = $sql->query($fetchQ);
	$coupW = $sql->fetch_row($fetchR);

	$val = (double)$coupW["value"];
	$limit = (double)$coupW["max"];
	$type = $coupW["type"];
	$desc = substr($coupW["name"],0,35);
	switch(strtoupper($type)){
	case 'PERCENT':
		$val = $val * $CORE_LOCAL->get("discountableTotal");
		break;
	}
	if ($limit != 0 && $val > $limit)
		$val = $limit;
	$val *= -1;
	$upc = str_pad($id,13,'0',STR_PAD_LEFT);

	self::addItem($upc, $desc, "I", "CP", "C", 0, 1, $val, $val, $val, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0);
}


//___________________________end addMadCoupon()

/**
  Add a deposit
  @deprecated
  Use deposit column in products table
*/
static public function addDeposit($quantity, $deposit, $foodstamp) {

	$total = $quantity * $deposit;
	$chardeposit = 100 * $deposit;
	if($foodstamp == 1){  //  ACG HARDCODED DEPARTMENTS....
		$dept = 43;
	}else{
		$dept = 42;
	}
	self::addItem("DEPOSIT" * $chardeposit, "Deposit", "I", "", "", $dept, $quantity, $deposit, $total, $deposit, 0, 0, $foodstamp, 0, 0, 0, 0, $quantity, 0, 0, 0, 0, 0, 0);
		
}

// ----------------------------- insert transaction discount -----------------------------------

/**
  Add transaction discount record
*/
static public function addTransDiscount() {
	global $CORE_LOCAL;
	self::addItem("DISCOUNT", "Discount", "I", "", "", 0, 1, MiscLib::truncate2(-1 * $CORE_LOCAL->get("transDiscount")), MiscLib::truncate2(-1 * $CORE_LOCAL->get("transDiscount")), 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0);
}

/**
  Add cash drop record
*/
static public function addCashDrop($ttl) {
	self::addItem("DROP", "Cash Drop", "I", "", "X", 0, 1, MiscLib::truncate2(-1 * $amt), MiscLib::truncate2(-1 * $amt), 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0.00, 0, 'CD');
}

// ---------------------------- insert stamp in activitytemplog --------------------------------

/**
  Add an activity record to activitytemplog
  @param $activity identifier

  No one really uses activity logging currently.
*/
static public function addactivity($activity) {
	global $CORE_LOCAL;

	$timeNow = time();

	if ($CORE_LOCAL->get("CashierNo") > 0 && $CORE_LOCAL->get("CashierNo") < 256) {
		$intcashier = $CORE_LOCAL->get("CashierNo");
	}
	else {
		$intcashier = 0;
	}

	if ($CORE_LOCAL->get("DBMS") == "mssql") {
		$strqtime = "select max(datetime) as maxDateTime, getdate() as rightNow from activitytemplog";
	} else {
		$strqtime = "select max(datetime) as maxDateTime, now() as rightNow from activitytemplog";
	}


	$db = Database::tDataConnect();
	$result = $db->query($strqtime);


	$row = $db->fetch_array($result);

	if (!$row || !$row[0]) {

		$interval = 0;
	}
	else {

		$interval = strtotime($row["rightNow"]) - strtotime($row["maxDateTime"]);
	}
		
	//$_SESSION["datetimestamp"] = strftime("%Y-%m-%d %H:%M:%S", $timeNow);
	$datetimestamp = strftime("%Y-%m-%d %H:%M:%S", $timeNow);

	$values = array(
		'datetime'	=> MiscLib::nullwrap($datetimestamp),
		'LaneNo'	=> MiscLib::nullwrap($CORE_LOCAL->get("laneno")),
		'CashierNo'	=> MiscLib::nullwrap($intcashier),
		'TransNo'	=> MiscLib::nullwrap($CORE_LOCAL->get("transno")),
		'Activity'	=> MiscLib::nullwrap($activity),
		'Interval'	=> MiscLib::nullwrap($interval)
		);
		/*
	if ($CORE_LOCAL->get("DBMS")=="mysql"){
		unset($values['Interval']);
		$values['`Interval`'] = MiscLib::nullwrap($interval);
	}
	*/
	$result = $db->smart_insert("activitytemplog",$values);

	$db->close();

}

// ------------------------------------------------------------------------


}

?>
