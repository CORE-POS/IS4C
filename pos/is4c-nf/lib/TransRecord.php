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

    if (strlen($strdescription) > 30) {
        $strdescription = substr($strdescription, 0, 30);
    }

	$values = array(
		'datetime'	=> $datetimestamp,
		'register_no'	=> $intregisterno,
		'emp_no'	=> $intempno,
		'trans_no'	=> MiscLib::nullwrap($inttransno),
		'upc'		=> MiscLib::nullwrap($strupc),
		'description'	=> $db->escape($strdescription),
		'trans_type'	=> MiscLib::nullwrap($strtransType),
		'trans_subtype'	=> MiscLib::nullwrap($strtranssubType, true),
		'trans_status'	=> MiscLib::nullwrap($strtransstatus, true),
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

	$CORE_LOCAL->set("toggletax",0);
	$CORE_LOCAL->set("togglefoodstamp",0);
	$CORE_LOCAL->set("SNR",0);

	if ($intscale == 1) {
		$CORE_LOCAL->set("lastWeight",$dblquantity);
    }
}

/**
  Wrapper for addItem that accepted a keyed array instead
  of many, MANY arguments. All keys are optional and will have
  the default values listed below if ommitted (read the actual method) 
  @param $named_params [keyed array]
  @return [none]
*/
static public function addRecord($named_params)
{
    // start with default values
    $new_record = array(
        'upc'           => '',
        'description'   => '',
        'trans_type'    => 'I',
        'trans_subtype' => '',
        'trans_status'  => '',
        'department'    => 0,
        'quantity'      => 0.0,
        'unitPrice'     => 0.0,
        'total'         => 0.0,
        'regPrice'      => 0.0,
        'scale'         => 0,
        'tax'           => 0,
        'foodstamp'     => 0,
        'discount'      => 0.0,
        'memDiscount'   => 0.0,
        'discountable'  => 0,
        'discounttype'  => 0,
        'ItemQtty'      => 0.0,
        'volDiscType'   => 0,
        'volume'        => 0,
        'VolSpecial'    => 0,
        'mixMatch'      => '',
        'matched'       => 0,
        'voided'        => 0,
        'cost'          => 0.0,
        'numflag'       => 0,
        'charflag'      => '',
    );

    // override defaults with any values specified
    // in $named_params
    foreach(array_keys($new_record) as $key) {
        if (isset($named_params[$key])) {
            $new_record[$key] = $named_params[$key];
        }
    }

    // call addItem()
    self::addItem(
        $new_record['upc'],
        $new_record['description'],
        $new_record['trans_type'],
        $new_record['trans_subtype'],
        $new_record['trans_status'],
        $new_record['department'],
        $new_record['quantity'],
        $new_record['unitPrice'],
        $new_record['total'],
        $new_record['regPrice'],
        $new_record['scale'],
        $new_record['tax'],
        $new_record['foodstamp'],
        $new_record['discount'],
        $new_record['memDiscount'],
        $new_record['discountable'],
        $new_record['discounttype'],
        $new_record['ItemQtty'],
        $new_record['volDiscType'],
        $new_record['volume'],
        $new_record['VolSpecial'],
        $new_record['mixMatch'],
        $new_record['matched'],
        $new_record['voided'],
        $new_record['cost'],
        $new_record['numflag'],
        $new_record['charflag']
    );
}

/**
  Add a item, but not until the end of the transaction
  Use this for records that shouldn't be displayed

  Note: TransRecord::addLogRecord() can be used to add
  non-display records to the transaction instantly
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
        self::addRecord(array(
            'upc' => $record['upc'],
            'description' => $record['description'],
            'trans_type' => 'C',
            'trans_status' => 'D',
            'regPrice' => $record['regPrice'],
            'numflag' => $record['numflag'],
            'charflag' => $record['charflag'],
        ));
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
        self::addRecord(array(
            'upc' => 'TAX',
            'description' => 'Tax',
            'trans_type' => 'A',
            'total' => $CORE_LOCAL->get('taxTotal'),
        ));
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

        self::addRecord(array(
            'upc' => 'TAX',
            'description' => substr($w['description'] . ' Tax', 0, 30),
            'trans_type' => 'A',
            'total' => MiscLib::truncate2($w['taxTotal']),
            'tax' => $w['id'],
        ));
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
    self::addRecord(array(
        'description' => $strtenderdesc,
        'trans_type' => 'T',
        'trans_subtype' => $strtendercode,
        'total' => $dbltendered,
    ));
}

/**
  Add a tender record to the transaction with numflag & charflag values
  @param $strtenderdesc is a description, such as "Credit Card"
  @param $strtendercode is a 1-2 character code, such as "CC"
  @param $dbltendered is the amount. Remember that payments are
      <i>negative</i> amounts. 
  @param $numflag [int]
  @param $charflag [1-2 character string]

  Flags are meant to store generic additional data as needed. For example,
  numflag might contain a record ID corresponding to table(s) of additional
  processor data on an integrated transaction.
*/
static public function addFlaggedTender($strtenderdesc, $strtendercode, $dbltendered, $numflag, $charflag) 
{
    self::addRecord(array(
        'description' => $strtenderdesc,
        'trans_type' => 'T',
        'trans_subtype' => $strtendercode,
        'total' => $dbltendered,
        'numflag' => $numflag,
        'charflag' => $charflag,
    ));
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
    self::addRecord(array(
        'description' => $comment,
        'trans_type' => 'C',
        'trans_subtype' => 'CM',
        'trans_status' => 'D',
    ));
}

/**
  Add a change record (a special type of tender record)
  @param $dblcashreturn the change amount
*/
static public function addchange($dblcashreturn,$strtendercode='CA') 
{
	global $CORE_LOCAL;
    self::addRecord(array(
        'description' => 'Change',
        'trans_type' => 'T',
        'trans_subtype' => $strtendercode,
        'total' => $dblcashreturn,
        'voided' => 8,
    ));
}

/**
  Add a foodstamp change record
  @param $intfsones the change amount

  Please do verify cashback is permitted with EBT transactions
  in your area before using this.
*/
static public function addfsones($intfsones) 
{
    self::addRecord(array(
        'description' => 'FS Change',
        'trans_type' => 'T',
        'trans_subtype' => 'FS',
        'total' => $intfsones,
        'voided' => 8,
    ));
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
    self::addRecord(array(
        'description' => $strsaved,
        'trans_type' => 'I',
        'trans_status' => 'D',
        'department' => $department,
        'voided' => 2,
    ));
}

/**
  Add tax exemption for foodstamps
*/
static public function addfsTaxExempt() 
{
	global $CORE_LOCAL;

	Database::getsubtotals();
    self::addRecord(array(
        'upc' => 'FS Tax Exempt',
        'description' => ' Fs Tax Exempt ',
        'trans_type' => 'C',
        'trans_status' => 'D',
        'unitPrice' => $CORE_LOCAL->get('fsTaxExempt'),
        'voided' => 17,
    ));
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
    self::addRecord(array(
        'description' => '** ' . $strl . '% Discount Applied **',
        'trans_type' => '0',
        'trans_status' => 'D',
        'voided' => 4,
    ));
}

/**
  Add tax exemption record to transaction
*/
static public function addTaxExempt() 
{
	global $CORE_LOCAL;

    self::addRecord(array(
        'description' => '** Order is Tax Exempt **',
        'trans_type' => '0',
        'trans_status' => 'D',
        'voided' => 10,
        'tax' => 9,
    ));
	$CORE_LOCAL->set("TaxExempt",1);
	Database::setglobalvalue("TaxExempt", 1);
}

/**
  Add record to undo tax exemption
*/
static public function reverseTaxExempt() 
{
	global $CORE_LOCAL;
    self::addRecord(array(
        'description' => '** Tax Exemption Reversed **',
        'trans_type' => '0',
        'trans_status' => 'D',
        'voided' => 10,
        'tax' => 9,
    ));
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

    self::addRecord(array(
        'description' => '** ' . $CORE_LOCAL->get('casediscount') . '% Case Discount Applied',
        'trans_type' => '0',
        'trans_status' => 'D',
        'voided' => 6,
    ));
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

    self::addRecord(array(
        'upc' => $strupc,
        'description' => ' * Manufacturers Coupon',
        'trans_type' => 'I',
        'trans_subtype' => 'CP',
        'trans_status' => 'C',
        'department' => $intdepartment,
        'quantity' => 1,
        'ItemQtty' => 1,
        'unitPrice' => $dbltotal,
        'total' => $dbltotal,
        'regPrice' => $dbltotal,
        'tax' => $tax,
        'foodstamp' => $foodstamp,
    ));
}

/**
  Add an in-store coupon
  @param $strupc coupon UPC
  @param $intdepartment associated POS department
  @param $dbltotal coupon amount (should be negative)
*/
static public function addhousecoupon($strupc, $intdepartment, $dbltotal, $description='') 
{
	global $CORE_LOCAL;
    if (empty($description)) {
        $sql = Database::pDataConnect();
        $fetchQ = "select card_no, coupID, description from houseVirtualCoupons WHERE card_no=" . $CORE_LOCAL->get('memberID');
        $fetchR = $sql->query($fetchQ);
        $coupW = $sql->fetch_row($fetchR);
        $description = ($coupW) ? substr($coupW["description"],0,35) : " * Store Coupon";
    }

    self::addRecord(array(
        'upc' => $strupc,
        'description' => $description,
        'trans_type' => 'I',
        'trans_subtype' => 'IC',
        'trans_status' => 'C',
        'department' => $intdepartment,
        'quantity' => 1,
        'ItemQtty' => 1,
        'unitPrice' => $dbltotal,
        'total' => $dbltotal,
        'regPrice' => $dbltotal,
    ));
}

/**
  Add a line-item discount
  @param $intdepartment POS department
  @param $dbltotal discount amount (should be <b>positive</b>)
*/
static public function additemdiscount($intdepartment, $dbltotal) 
{
	$dbltotal *= -1;
    self::addRecord(array(
        'upc' => 'ITEMDISCOUNT',
        'description' => ' * Item Discount',
        'trans_type' => 'I',
        'department' => $intdepartment,
        'quantity' => 1,
        'unitPrice' => $dbltotal,
        'total' => $dbltotal,
        'regPrice' => $dbltotal,
        'ItemQtty' => 1,
    ));
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
    self::addRecord(array(
        'description' => '** Tare Weight ' . $CORE_LOCAL->get('tare') . ' **',
        'trans_type' => '0',
        'trans_status' => 'D',
        'voided' => 6,
    ));
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

    self::addRecord(array(
        'upc' => $upc,
        'description' => $desc,
        'trans_type' => 'I',
        'trans_subtype' => 'CP',
        'trans_status' => 'C',
        'quantity' => 1,
        'unitPrice' => $val,
        'total' => $val,
        'regPrice' => $val,
        'ItemQtty' => 1,
    ));
}

/**
  Add transaction discount record
*/
static public function addTransDiscount() 
{
	global $CORE_LOCAL;
    self::addRecord(array(
        'upc' => 'DISCOUNT',
        'description' => 'Discount',
        'trans_type' => 'I',
        'quantity' => 1,
        'unitPrice' => MiscLib::truncate2(-1 * $CORE_LOCAL->get('transDiscount')),
        'total' => MiscLib::truncate2(-1 * $CORE_LOCAL->get('transDiscount')),
        'ItemQtty' => 1,
    ));
}

/**
  Add cash drop record
*/
static public function addCashDrop($amt) 
{
    self::addRecord(array(
        'upc' => 'DROP',
        'description' => 'Cash Drop',
        'trans_type' => 'I',
        'trans_status' => 'X',
        'quantity' => 1,
        'unitPrice' => MiscLib::truncate2(-1 * $amt),
        'total' => MiscLib::truncate2(-1 * $amt),
        'ItemQtty' => 1,
        'charflag' => 'CD',
    ));
}

/**
  Add a log entry to the transaction table.
  Log records do not appear onscreen or on receipts.

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

    self::addRecord(array(
        'upc' => $upc,
        'description' => $desc,
        'trans_type' => 'L',
        'trans_subtype' => 'OG',
        'trans_status' => 'X',
        'department' => $dept,
        'total' => $total,
        'regPrice' => $regPrice,
        'numflag' => $nflag,
        'charflag' => $cflag,
    ));
}

static public function add_log_record($opts)
{
    self::addLogRecord($opts);
}

/**
  Finish the current transaction
  @param $incomplete [boolean] optional, default false

  This method:
  1) Adds tax and discount lines if transaction is complete
     (i.e., $incomplete == false)
  2) Rotates data out of localtemptrans
  3) Advances trans_no variable to next available value

  This method replaces older ajax-end.php / end.php operations
  where the receipt was printed first and then steps 1-3
  above happened. This method should be called BEFORE printing
  a receipt. Receipts are now always printed via localtranstoday.
*/
static public function finalizeTransaction($incomplete=false)
{
    global $CORE_LOCAL;
    if (!$incomplete) {
        self::addtransDiscount();
        self::addTax();
        $taxes = Database::LineItemTaxes();
        foreach($taxes as $tax) {
            if ($CORE_LOCAL->get('TaxExempt') == 1) {
                $tax['amount'] = 0.00;
            }
            self::addLogRecord(array(
                'upc' => 'TAXLINEITEM',
                'description' => $tax['description'],
                'numflag' => $tax['rate_id'],
                'amount2' => $tax['amount'],
            ));
        }
    }

    if (Database::rotateTempData()) { // rotate data
        Database::clearTempTables();
    }

    // advance trans_no value
    $nextTransNo = Database::gettransno($CORE_LOCAL->get('CashierNo'));
    $CORE_LOCAL->set('transno', $nextTransNo);
    Database::setglobalvalue('TransNo', $nextTransNo);
}

static public function debugLog($val)
{
    global $CORE_LOCAL;

	$tdate = "";
	if ($CORE_LOCAL->get("DBMS") == "mssql") {
		$tdate = strftime("%m/%d/%y %H:%M:%S %p", time());
	} else {
		$tdate = strftime("%Y-%m-%d %H:%M:%S", time());
	}
    $trans_num = ReceiptLib::receiptNumber();
    $lastID = $CORE_LOCAL->get('LastID');

    $db = Database::tDataConnect();
    if ($db->table_exists('DebugLog')) {
        $prep = $db->prepare('INSERT INTO DebugLog 
                              (tdate, transNum, transID, entry)
                              VALUES
                              (?, ?, ?, ?)');
        $res = $db->execute($prep, array($tdate, $trans_num, $lastID, $val));

        return $res ? true : false;
    } else {
        return false;
    }
}

}

