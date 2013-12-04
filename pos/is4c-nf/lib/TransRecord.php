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

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	* 10Apr2013 Andy Theuninck Filter backslash out of comments
	* 19Jan2013 Eric Lee Fix typo "Datbase" in reverseTaxExempt

*/

/**
  @class TransRecord
  Defines functions for adding records to the transaction
*/
class TransRecord extends LibraryClass 
{

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
static public function addItem($strupc, $strdescription, $strtransType, $strtranssubType, $strtransstatus, $intdepartment, $dblquantity, $dblunitPrice, $dbltotal, $dblregPrice, $intscale, $inttax, $intfoodstamp, $dbldiscount, $dblmemDiscount, $intdiscountable, $intdiscounttype, $dblItemQtty, $intvolDiscType, $intvolume, $dblVolSpecial, $intmixMatch, $intmatched, $intvoided, $cost=0, $numflag=0, $charflag='') 
{
	global $CORE_LOCAL;
	//$dbltotal = MiscLib::truncate2(str_replace(",", "", $dbltotal)); replaced by apbw 7/27/05 with the next 4 lines -- to fix thousands place errors

	$dbltotal = str_replace(",", "", $dbltotal);		
	$dbltotal = number_format($dbltotal, 2, '.', '');
	$dblunitPrice = str_replace(",", "", $dblunitPrice);
	$dblunitPrice = number_format($dblunitPrice, 2, '.', '');

	if ($CORE_LOCAL->get("refund") == 1) {
		$dblquantity = (-1 * $dblquantity);
		$dbltotal = (-1 * $dbltotal);
		$dbldiscount = (-1 * $dbldiscount);
		$dblmemDiscount = (-1 * $dblmemDiscount);

		if ($strtransstatus != "V" && $strtransstatus != "D") {
            $strtransstatus = "R" ;	// edited by apbw 6/04/05 to correct voiding of refunded items
        }

		$CORE_LOCAL->set("refund",0);
		$CORE_LOCAL->set("refundComment","");
		$CORE_LOCAL->set("autoReprint",1);

		if ($CORE_LOCAL->get("refundDiscountable")==0) {
			$intdiscountable = 0;
        }
	}

	$intregisterno = $CORE_LOCAL->get("laneno");
	$intempno = $CORE_LOCAL->get("CashierNo");
	$inttransno = $CORE_LOCAL->get("transno");
	$strCardNo = $CORE_LOCAL->get("memberID");
	$memType = $CORE_LOCAL->get("memType");
	$staff = $CORE_LOCAL->get("isStaff");
	$percentDiscount = $CORE_LOCAL->get("percentDiscount");

	$db = Database::tDataConnect();

	$datetimestamp = "";
	if ($CORE_LOCAL->get("DBMS") == "mssql") {
		$datetimestamp = strftime("%m/%d/%y %H:%M:%S %p", time());
	} else {
		$datetimestamp = strftime("%Y-%m-%d %H:%M:%S", time());
	}

	$CORE_LOCAL->set("LastID",$CORE_LOCAL->get("LastID") + 1);

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
		'percentDiscount'=> MiscLib::nullwrap($percentDiscount),
		'numflag'	=> MiscLib::nullwrap($numflag),
		'charflag'	=> $charflag,
		'card_no'	=> (string)$strCardNo
		);
	if ($CORE_LOCAL->get("DBMS") == "mssql" && $CORE_LOCAL->get("store") == "wfc") {
		unset($values["staff"]);
		$values["isStaff"] = MiscLib::nullwrap($staff);
	}

	$db->smart_insert("localtemptrans",$values);

	if ($strtransType == "I" || $strtransType == "D") {
		$CORE_LOCAL->set("repeatable",1);
	}

	$CORE_LOCAL->set("msgrepeat",0);
	$CORE_LOCAL->set("toggletax",0);
	$CORE_LOCAL->set("togglefoodstamp",0);
	$CORE_LOCAL->set("SNR",0);

	if ($intscale == 1) {
		$CORE_LOCAL->set("lastWeight",$dblquantity);
    }
}

/**
  Add a item, but not until the end of the transaction
  Use this for records that shouldn't be displayed
*/
static public function addQueued($upc, $description, $numflag=0, $charflag='',$regPrice=0)
{
	global $CORE_LOCAL;
	$queue = $CORE_LOCAL->get("infoRecordQueue");	
	if (!is_array($queue)) {
        $queue = array();
    }
	$queue[] = array('upc'=>$upc,'description'=>$description,
			'numflag'=>$numflag,'charflag'=>$charflag,
			'regPrice'=>$regPrice);
	$CORE_LOCAL->set("infoRecordQueue", $queue);
}

/**
   Add records queued by TransRecord::addQueued
   to the current transaction then clear the queue.
   Records get trans_type C, trans_status D 
*/
static public function emptyQueue()
{
	global $CORE_LOCAL;
	$queue = $CORE_LOCAL->get("infoRecordQueue");	
	if (!is_array($queue)) {
        $queue = array();
    }
	foreach($queue as $record) {
		if (!isset($record['upc']) || !isset($record['description']) ||
		    !isset($record['numflag']) || !isset($record['charflag']) ||
		    !isset($record['regPrice'])) {
			continue; //skip incomplete
		}
		self::addItem($record['upc'], $record['description'], "C", "", "D", 
			0, 0, 0, 0, $record['regPrice'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
			0, $record['numflag'], $record['charflag']);
	}
	$CORE_LOCAL->set("infoRecordQueue",array());
}

/**
   Add a tax record to the transaction. Amount is
   pulled from session info automatically.
*/
static public function addtax() 
{
	global $CORE_LOCAL;

	if (true){
		self::addItem("TAX", "Tax", "A", "", "", 0, 0, 0, $CORE_LOCAL->get("taxTotal"), 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
		return;
	}

	/* line-item taxes in transaction
	   intentionally disabled for now
	*/

	$db = Database::tDataConnect();
	$q = "SELECT id, description, taxTotal, fsTaxable, fsTaxTotal, foodstampTender, taxrate
		FROM taxView ORDER BY taxrate DESC";
	$r = $db->query($q);

	$fsTenderAvailable = null;
	while($w = $db->fetch_row($r)) {
		if ($fsTenderAvailable === null) $fsTenderAvailable = (double)$w['foodstampTender'];
		
		if ($fsTenderAvailable >= $w['fsTaxable']) {
            // whole amount purchased w/ foodstamps; exempt all fsTax
			$w['taxTotal'] -= $w['fsTaxTotal'];
			$fsTenderAvailable -= $w['fsTaxable'];
		} else if ($fsTenderAvailable > 0 && $fsTenderAvailable < $w['fsTaxable']) {
            // partial; exempt proportionally
			$exempt = $fsTenderAvailable * $w['taxrate'];
			$w['taxTotal'] -= $exempt;
			$fsTenderAvailable = 0.00;
		}

		self::addItem("TAX", substr($w['description']." Tax",0,35), "A", "", "", 0, 0, 0, 
			MiscLib::truncate2($w['taxTotal']), 0, 0, $w['id'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
	}

}

/**
  Add a tender record to the transaction
  @param $strtenderdesc is a description, such as "Credit Card"
  @param $strtendercode is a 1-2 character code, such as "CC"
  @param $dbltendered is the amount. Remember that payments are
  <i>negative</i> amounts. 
*/
static public function addtender($strtenderdesc, $strtendercode, $dbltendered) 
{
	self::addItem("", $strtenderdesc, "T", $strtendercode, "", 0, 0, 0, $dbltendered, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
}

/**
  Add a comment to the transaction
  @param $comment is the comment text. Max length allowed 
  is 30 characters.
*/
static public function addcomment($comment) 
{
	if (strlen($comment) > 30) {
		$comment = substr($comment,0,30);
    }
	$comment = str_replace("\\",'',$comment);
	self::addItem("",$comment, "C", "CM", "D", 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
}

/**
  Add a change record (a special type of tender record)
  @param $dblcashreturn the change amount
*/
static public function addchange($dblcashreturn,$strtendercode='CA') 
{
	global $CORE_LOCAL;
	self::addItem("", "Change", "T", $strtendercode, "", 0, 0, 0, $dblcashreturn, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 8);
}

/**
  Add a foodstamp change record
  @param $intfsones the change amount

  Please do verify cashback is permitted with EBT transactions
  in your area before using this.
*/
static public function addfsones($intfsones) 
{
	self::addItem("", "FS Change", "T", "FS", "", 0, 0, 0, $intfsones, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 8);
}

/**
  Add a "YOU SAVED" record to the transaction. This is just informational
  and will not alter totals.
  @param $dbldiscount discount amount
  @param $department associated department
*/
static public function adddiscount($dbldiscount,$department) 
{
	global $CORE_LOCAL;
	$strsaved = "** YOU SAVED $".MiscLib::truncate2($dbldiscount)." **";
	if ($CORE_LOCAL->get("itemPD") > 0) {
		$strsaved = sprintf("** YOU SAVED \$%.2f (%d%%) **",
			$dbldiscount,$CORE_LOCAL->get("itemPD"));
	}
	self::addItem("", $strsaved, "I", "", "D", $department, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2);
}

/**
  Add tax exemption for foodstamps
*/
static public function addfsTaxExempt() 
{
	global $CORE_LOCAL;

	Database::getsubtotals();
	self::addItem("FS Tax Exempt", " Fs Tax Exempt ", "C", "", "D", 0, 0, $CORE_LOCAL->get("fsTaxExempt"), 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 17);
}

/**
  Add a information record showing transaction percent discount
  @param $strl the percentage
*/
static public function discountnotify($strl) 
{
	if ($strl == 10.01) {
		$strL = 10;
	}
	self::addItem("", "** ".$strl."% Discount Applied **", "", "", "D", 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4);
}

/**
  Add tax exemption record to transaction
*/
static public function addTaxExempt() 
{
	global $CORE_LOCAL;

	self::addItem("", "** Order is Tax Exempt **", "", "", "D", 0, 0, 0, 0, 0, 0, 9, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 10);
	$CORE_LOCAL->set("TaxExempt",1);
	Database::setglobalvalue("TaxExempt", 1);
}

/**
  Add record to undo tax exemption
*/
static public function reverseTaxExempt() 
{
	global $CORE_LOCAL;
	self::addItem("", "** Tax Exemption Reversed **", "", "", "D", 0, 0, 0, 0, 0, 0, 9, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 10);
	$CORE_LOCAL->set("TaxExempt",0);
	Database::setglobalvalue("TaxExempt", 0);
}

/** 
  Add an informational record noting case discount
  $CORE_LOCAL setting "casediscount" controls the percentage
  shown
*/
static public function addcdnotify() 
{
	global $CORE_LOCAL;
	self::addItem("", "** ".$CORE_LOCAL->get("casediscount")."% Case Discount Applied", "", "", "D", 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 6);
}

/**
  Add a manufacturer coupon record
  @param $strupc coupon UPC
  @param $intdepartment associated POS department
  @param $dbltotal coupon amount (should be negative)
  @param $foodstamp mark coupon foodstamp-able
  @param $tax mark coupon as taxable

  Marking a coupon as taxable will *reduce* the taxable
  total by the coupon amount. This is not desirable in 
  all tax jurisdictions. The ini setting 'CouponsAreTaxable'
  controls whether the tax parameter is used.
*/
static public function addCoupon($strupc, $intdepartment, $dbltotal, $foodstamp=0, $tax=0) 
{
	global $CORE_LOCAL;
	if ($CORE_LOCAL->get('CouponsAreTaxable') !== 0) {
		$tax = 0;
    }
	self::addItem($strupc, " * Manufacturers Coupon", "I", "CP", "C", $intdepartment, 1, $dbltotal, $dbltotal, $dbltotal, 0, $tax, $foodstamp, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0);	
}

/**
  Add an in-store coupon
  @param $strupc coupon UPC
  @param $intdepartment associated POS department
  @param $dbltotal coupon amount (should be negative)
*/
static public function addhousecoupon($strupc, $intdepartment, $dbltotal) 
{
	self::addItem($strupc, " * Store Coupon", "I", "IC", "C", $intdepartment, 1, $dbltotal, $dbltotal, $dbltotal, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0);
}

/**
  Add a line-item discount
  @param $intdepartment POS department
  @param $dbltotal discount amount (should be <b>positive</b>)
*/
static public function additemdiscount($intdepartment, $dbltotal) 
{
	$dbltotal *= -1;
	self::addItem('ITEMDISCOUNT'," * Item Discount", "I", "", "", $intdepartment, 1, $dbltotal, $dbltotal, $dbltotal, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0);
}

/**
  Add a tare record
  @param $dbltare the tare weight. The weight
  gets divided by 100, so an argument of 5 gives tare 0.05
*/
static public function addTare($dbltare) 
{
	global $CORE_LOCAL;
	$CORE_LOCAL->set("tare",$dbltare/100);
	$rf = $CORE_LOCAL->get("refund");
	$rc = $CORE_LOCAL->get("refundComment");
	self::addItem("", "** Tare Weight ".$CORE_LOCAL->get("tare")." **", "", "", "D", 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 6);
	$CORE_LOCAL->set("refund",$rf);
	$CORE_LOCAL->set("refundComment",$rc);
}

/**
  Add a virtual coupon by ID
  @param $id identifier in the VirtualCoupon table
*/
static public function addVirtualCoupon($id)
{
	global $CORE_LOCAL;
	$sql = Database::pDataConnect();
	$fetchQ = "select name,type,value,max from VirtualCoupon WHERE flag=$id";
	$fetchR = $sql->query($fetchQ);
	$coupW = $sql->fetch_row($fetchR);

	$val = (double)$coupW["value"];
	$limit = (double)$coupW["max"];
	$type = $coupW["type"];
	$desc = substr($coupW["name"],0,35);
	switch(strtoupper($type)) {
        case 'PERCENT':
            $val = $val * $CORE_LOCAL->get("discountableTotal");
            break;
	}
	if ($limit != 0 && $val > $limit) {
		$val = $limit;
    }
	$val *= -1;
	$upc = str_pad($id,13,'0',STR_PAD_LEFT);

	self::addItem($upc, $desc, "I", "CP", "C", 0, 1, $val, $val, $val, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0);
}

/**
  Add transaction discount record
*/
static public function addTransDiscount() 
{
	global $CORE_LOCAL;
	self::addItem("DISCOUNT", "Discount", "I", "", "", 0, 1, MiscLib::truncate2(-1 * $CORE_LOCAL->get("transDiscount")), MiscLib::truncate2(-1 * $CORE_LOCAL->get("transDiscount")), 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0);
}

/**
  Add cash drop record
*/
static public function addCashDrop($amt) 
{
	self::addItem("DROP", "Cash Drop", "I", "", "X", 0, 1, MiscLib::truncate2(-1 * $amt), MiscLib::truncate2(-1 * $amt), 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0.00, 0, 'CD');
}

/**
  Add an activity record to activitytemplog
  @param $activity identifier

  @deprecated
  No one really uses activity logging currently.
  Use TransRecord::addLogRecord instead.
*/
static public function addactivity($activity) 
{
    /*
	global $CORE_LOCAL;

	$timeNow = time();

	if ($CORE_LOCAL->get("CashierNo") > 0 && $CORE_LOCAL->get("CashierNo") < 256) {
		$intcashier = $CORE_LOCAL->get("CashierNo");
	} else {
		$intcashier = 0;
	}

	$db = Database::tDataConnect();
	$strqtime = "select max(datetime) as maxDateTime, ".$db->now()." as rightNow from activitytemplog";
	$result = $db->query($strqtime);

	$row = $db->fetch_array($result);

	if (!$row || !$row[0]) {
		$interval = 0;
	} else {
		$interval = strtotime($row["rightNow"]) - strtotime($row["maxDateTime"]);
	}

	$datetimestamp = strftime("%Y-%m-%d %H:%M:%S", $timeNow);

	$values = array(
		'datetime'	=> MiscLib::nullwrap($datetimestamp),
		'LaneNo'	=> MiscLib::nullwrap($CORE_LOCAL->get("laneno")),
		'CashierNo'	=> MiscLib::nullwrap($intcashier),
		'TransNo'	=> MiscLib::nullwrap($CORE_LOCAL->get("transno")),
		'Activity'	=> MiscLib::nullwrap($activity),
		'Interval'	=> MiscLib::nullwrap($interval)
		);
	$result = $db->smart_insert("activitytemplog",$values);
    */
}

/**
  Add a log entry to the transaction table.
  Log records do not appear onscreen on on receipts.

  @param $opts keyed array. Currently valid keys are:
   - upc
   - description
   - department
   - numflag
   - charflag
   - amount1
   - amount2

  All keys are optional and will be left blank or zero if
  omitted. Log records have trans_status 'X', trans_type 'L',
  and trans_subtype 'OG'. Amount1 and Amount2 are reflected in
  total and regPrice (respectively). The other values go in the
  correspondingly named columns.
*/
static public function addLogRecord($opts)
{
	if (!is_array($opts)) {
        $opts = array();
    }

	$upc = isset($opts['upc']) ? $opts['upc'] : '';
	$desc = isset($opts['description']) ? $opts['description'] : '';
	$dept = isset($opts['department']) ? $opts['department'] : 0;
	$nflag = isset($opts['numflag']) ? $opts['numflag'] : 0;
	$cflag = isset($opts['charflag']) ? $opts['charflag'] : '';
	$total = isset($opts['amount1']) ? $opts['amount1'] : 0;
	$regPrice = isset($opts['amount2']) ? $opts['amount2'] : 0;
	
	self::addItem($upc, $desc, 'L', 'OG', 'D', $dept, 
		0, // quantity
		0, // unitPrice 
		$total, 
		$regPrice, 
		0, // scale 
		0, // tax 
		0, //foodstamp
		0, //discount
		0, //memDiscount 
		0, //discountable
		0, //discounttype
		0, //ItemQtty
		0, //volDiscType
		0, //volume
		0, //VolSpecial
		'', //mixMatch
		0, //matched
		0, //voided
		0, //cost 
		$nflag, $cflag);
}

static public function add_log_record($opts)
{
    self::addLogRecord($opts);
}

}

