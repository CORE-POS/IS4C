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

namespace COREPOS\pos\lib;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\DiscountModule;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\ReceiptLib;
use \AutoLoader;
use \CoreLocal;

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    * 10Apr2013 Andy Theuninck Filter backslash out of comments
    * 19Jan2013 Eric Lee Fix typo "Datbase" in reverseTaxExempt

*/

/**
  @class TransRecord
  Defines functions for adding records to the transaction
*/
class TransRecord 
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

  Use the other methods in this file that do not require
  nearly as many arguments
*/
static private function addItem($strupc, $strdescription, $strtransType, $strtranssubType, $strtransstatus, $intdepartment, $dblquantity, $dblunitPrice, $dbltotal, $dblregPrice, $intscale, $inttax, $intfoodstamp, $dbldiscount, $dblmemDiscount, $intdiscountable, $intdiscounttype, $dblItemQtty, $intvolDiscType, $intvolume, $dblVolSpecial, $intmixMatch, $intmatched, $intvoided, $cost=0, $numflag=0, $charflag='') 
{
    //$dbltotal = MiscLib::truncate2(str_replace(",", "", $dbltotal)); replaced by apbw 7/27/05 with the next 4 lines -- to fix thousands place errors
    $dbltotal = self::formatDouble($dbltotal);
    $dblunitPrice = self::formatDouble($dblunitPrice);

    // do not clear refund flag when adding an informational log record
    if ($strtransType != 'L' && CoreLocal::get("refund") == 1) {
        $dblquantity = (-1 * $dblquantity);
        $dbltotal = (-1 * $dbltotal);
        $dbldiscount = (-1 * $dbldiscount);
        $dblmemDiscount = (-1 * $dblmemDiscount);
        $cost = (-1 * $cost);

        if ($strtransstatus != "V" && $strtransstatus != "D") {
            $strtransstatus = "R" ;    // edited by apbw 6/04/05 to correct voiding of refunded items
        }

        CoreLocal::set("refund",0);
        CoreLocal::set("refundComment","");
        CoreLocal::set("autoReprint",1);

        if (CoreLocal::get("refundDiscountable")==0) {
            $intdiscountable = 0;
        }
    }

    $dbc = Database::tDataConnect();

    $datetimestamp = strftime("%Y-%m-%d %H:%M:%S", time());
    if (CoreLocal::get("DBMS") == "mssql") {
        $datetimestamp = strftime("%m/%d/%y %H:%M:%S %p", time());
    }

    CoreLocal::set("LastID",CoreLocal::get("LastID") + 1);

    $values = array(
        'datetime'    => $datetimestamp,
        'register_no'    => CoreLocal::get('laneno'),
        'emp_no'    => CoreLocal::get('CashierNo'),
        'trans_no'    => MiscLib::nullwrap(CoreLocal::get('transno')),
        'upc'        => MiscLib::nullwrap($strupc),
        'description'    => substr($strdescription, 0, 30),
        'trans_type'    => MiscLib::nullwrap($strtransType),
        'trans_subtype'    => MiscLib::nullwrap($strtranssubType, true),
        'trans_status'    => MiscLib::nullwrap($strtransstatus, true),
        'department'    => MiscLib::nullwrap($intdepartment),
        'quantity'    => MiscLib::nullwrap($dblquantity),
        'cost'        => MiscLib::nullwrap($cost),
        'unitPrice'    => MiscLib::nullwrap($dblunitPrice),
        'total'        => MiscLib::nullwrap($dbltotal),
        'regPrice'    => MiscLib::nullwrap($dblregPrice),
        'scale'        => MiscLib::nullwrap($intscale),
        'tax'        => MiscLib::nullwrap($inttax),
        'foodstamp'    => MiscLib::nullwrap($intfoodstamp),
        'discount'    => MiscLib::nullwrap($dbldiscount),
        'memDiscount'    => MiscLib::nullwrap($dblmemDiscount),
        'discountable'    => MiscLib::nullwrap($intdiscountable),
        'discounttype'    => MiscLib::nullwrap($intdiscounttype),
        'ItemQtty'    => MiscLib::nullwrap($dblItemQtty),
        'volDiscType'    => MiscLib::nullwrap($intvolDiscType),
        'volume'    => MiscLib::nullwrap($intvolume),
        'VolSpecial'    => MiscLib::nullwrap($dblVolSpecial),
        'mixMatch'    => MiscLib::nullwrap($intmixMatch),
        'matched'    => MiscLib::nullwrap($intmatched),
        'voided'    => MiscLib::nullwrap($intvoided),
        'memType'    => MiscLib::nullwrap(CoreLocal::get('memType')),
        'staff'        => MiscLib::nullwrap(CoreLocal::get('isStaff')),
        'percentDiscount'=> MiscLib::nullwrap(CoreLocal::get('percentDiscount')),
        'numflag'    => MiscLib::nullwrap($numflag),
        'charflag'    => $charflag,
        'card_no'    => (string)CoreLocal::get('memberID'),
        );

    $dbc->smartInsert("localtemptrans",$values);

    if ($strtransType == "I" || $strtransType == "D") {
        CoreLocal::set("repeatable",1);
    }

    CoreLocal::set("toggletax",0);
    CoreLocal::set("togglefoodstamp",0);
    CoreLocal::set("SNR",0);

    if ($intscale == 1) {
        CoreLocal::set("lastWeight",$dblquantity);
    }
}

private static function formatDouble($dbl)
{
    $dbl = str_replace(",", "", $dbl);
    $dbl = number_format($dbl, 2, '.', '');
    return $dbl;
}

private static $defaultRecord = array(
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

/**
  Wrapper for addItem that accepted a keyed array instead
  of many, MANY arguments. All keys are optional and will have
  the default values listed below if ommitted (read the actual method) 
  @param $namedParams [keyed array]
  @return [none]
*/
    // @hintable
static public function addRecord($namedParams)
{
    // start with default values
    $newRecord = self::$defaultRecord;

    // override defaults with any values specified
    // in $namedParams
    foreach(array_keys($newRecord) as $key) {
        if (isset($namedParams[$key])) {
            $newRecord[$key] = $namedParams[$key];
        }
    }

    // call addItem()
    self::addItem(
        $newRecord['upc'],
        $newRecord['description'],
        $newRecord['trans_type'],
        $newRecord['trans_subtype'],
        $newRecord['trans_status'],
        $newRecord['department'],
        $newRecord['quantity'],
        $newRecord['unitPrice'],
        $newRecord['total'],
        $newRecord['regPrice'],
        $newRecord['scale'],
        $newRecord['tax'],
        $newRecord['foodstamp'],
        $newRecord['discount'],
        $newRecord['memDiscount'],
        $newRecord['discountable'],
        $newRecord['discounttype'],
        $newRecord['ItemQtty'],
        $newRecord['volDiscType'],
        $newRecord['volume'],
        $newRecord['VolSpecial'],
        $newRecord['mixMatch'],
        $newRecord['matched'],
        $newRecord['voided'],
        $newRecord['cost'],
        $newRecord['numflag'],
        $newRecord['charflag']
    );

    $actions = CoreLocal::get('ItemActions');
    if (!is_array($actions)) {
        $actions = AutoLoader::listModules('COREPOS\\pos\\lib\\ItemAction');
        CoreLocal::set('ItemActions', $actions);
    }
    foreach ($actions as $class) {
        $obj = new $class();
        $obj->callback($newRecord);
    }
}

/**
  Add a item, but not until the end of the transaction
  Use this for records that shouldn't be displayed

  Note: TransRecord::addLogRecord() can be used to add
  non-display records to the transaction instantly
*/
static public function addQueued($upc, $description, $numflag=0, $charflag='',$regPrice=0)
{
    $queue = CoreLocal::get("infoRecordQueue");    
    if (!is_array($queue)) {
        $queue = array();
    }
    $queue[] = array('upc'=>$upc,'description'=>$description,
            'numflag'=>$numflag,'charflag'=>$charflag,
            'regPrice'=>$regPrice);
    CoreLocal::set("infoRecordQueue", $queue);
}

/**
   Add records queued by TransRecord::addQueued
   to the current transaction then clear the queue.
   Records get trans_type C, trans_status D 
*/
static public function emptyQueue()
{
    $queue = CoreLocal::get("infoRecordQueue");    
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
    CoreLocal::set("infoRecordQueue",array());
}

/**
   Add a tax record to the transaction. Amount is
   pulled from session info automatically.
*/
static public function addtax() 
{
    self::addRecord(array(
        'upc' => 'TAX',
        'description' => 'Tax',
        'trans_type' => 'A',
        'total' => CoreLocal::get('taxTotal'),
    ));

    /* line-item taxes in transaction
       intentionally disabled for now

    $dbc = Database::tDataConnect();
    $q = "SELECT id, description, taxTotal, fsTaxable, fsTaxTotal, foodstampTender, taxrate
        FROM taxView ORDER BY taxrate DESC";
    $r = $dbc->query($q);

    $fsTenderAvailable = null;
    while($w = $dbc->fetch_row($r)) {
        if ($fsTenderAvailable === null) $fsTenderAvailable = (double)$w['foodstampTender'];
        
        if ($fsTenderAvailable >= $w['fsTaxable']) {
            // whole amount purchased w/ foodstamps; exempt all fsTax
            $w['taxTotal'] -= $w['fsTaxTotal'];
            $fsTenderAvailable -= $w['fsTaxable'];
        } elseif ($fsTenderAvailable > 0 && $fsTenderAvailable < $w['fsTaxable']) {
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
    */
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
  @param $strtendercode [default 'CA']
  @param $strchangemsg [default 'Change']
*/
static public function addchange($dblcashreturn, $strtendercode='CA', $strchangemsg='Change') 
{
    /**
      Avoiding writing blank records if opdata.tenders.ChangeMsg happens to be blank or null
    */
    if (empty($strchangemsg)) {
        $strchangemsg = 'Change';
    }
    if (empty($strtendercode)) {
        $strtendercode = 'CA';
    }
    self::addRecord(array(
        'description' => $strchangemsg,
        'trans_type' => 'T',
        'trans_subtype' => $strtendercode,
        'total' => $dblcashreturn,
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
    $strsaved = "** YOU SAVED $".MiscLib::truncate2($dbldiscount)." **";
    if (CoreLocal::get("itemPD") > 0) {
        $strsaved = sprintf("** YOU SAVED \$%.2f (%d%%) **",
            $dbldiscount,CoreLocal::get("itemPD"));
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
    Database::getsubtotals();
    self::addRecord(array(
        'upc' => 'FS Tax Exempt',
        'description' => ' Fs Tax Exempt ',
        'trans_type' => 'C',
        'trans_status' => 'D',
        'unitPrice' => CoreLocal::get('fsTaxExempt'),
        'voided' => 17,
    ));
}

/**
  Add a information record showing transaction percent discount
  @param $strl the percentage
*/
static public function discountnotify($strl) 
{
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
    self::addRecord(array(
        'description' => '** Order is Tax Exempt **',
        'trans_type' => '0',
        'trans_status' => 'D',
        'voided' => 10,
        'tax' => 9,
    ));
    CoreLocal::set("TaxExempt",1);
    Database::setglobalvalue("TaxExempt", 1);
}

/**
  Add record to undo tax exemption
*/
static public function reverseTaxExempt() 
{
    self::addRecord(array(
        'description' => '** Tax Exemption Reversed **',
        'trans_type' => '0',
        'trans_status' => 'D',
        'voided' => 10,
        'tax' => 9,
    ));
    CoreLocal::set("TaxExempt",0);
    Database::setglobalvalue("TaxExempt", 0);
}

/**
  Add a manufacturer coupon record
  @param $strupc coupon UPC
  @param $intdepartment associated POS department
  @param $dbltotal coupon amount (should be negative)
  @param $statusFlags array of optional status flags. Supported keys:
    - tax
    - foodstamp
    - discountable

  Marking a coupon as taxable will *reduce* the taxable
  total by the coupon amount. This is not desirable in 
  all tax jurisdictions. The ini setting 'CouponsAreTaxable'
  controls whether the tax parameter is used.
*/
    // @hintable
static public function addCoupon($strupc, $intdepartment, $dbltotal, $statusFlags=array())
{
    if (CoreLocal::get('CouponsAreTaxable') !== 0) {
        $statusFlags['tax'] = 0;
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
        'tax' => isset($statusFlags['tax']) ? $statusFlags['tax'] : 0,
        'foodstamp' => isset($statusFlags['foodstamp']) ? $statusFlags['foodstamp'] : 0,
        'discountable' => isset($statusFlags['discountable']) ? $statusFlags['discountable'] : 0,
    ));
}

/**
  Add an in-store coupon
  @param $strupc coupon UPC
  @param $intdepartment associated POS department
  @param $dbltotal coupon amount (should be negative)
*/
static public function addhousecoupon($strupc, $intdepartment, $dbltotal, $description='', $discountable=1)
{
    if (empty($description)) {
        $sql = Database::pDataConnect();
        $fetchQ = "select card_no, coupID, description from houseVirtualCoupons WHERE card_no=" . CoreLocal::get('memberID');
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
        'discountable' => $discountable,
    ));
}

/**
  Add a line-item discount
  @param $intdepartment POS department
  @param $dbltotal discount amount (should be <b>positive</b>)
  @param $tax amount is taxable (default 0)
  @param $fs amount is foodstampable (default 0)
*/
static public function additemdiscount($intdepartment, $dbltotal, $tax=0, $fs=0) 
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
        'tax' => $tax,
        'foodstamp' => $fs,
    ));
}

/**
  Add a tare record
  @param $dbltare the tare weight. The weight
  gets divided by 100, so an argument of 5 gives tare 0.05
*/
static public function addTare($dbltare) 
{
    CoreLocal::set("tare",$dbltare/100);
    $refund = CoreLocal::get("refund");
    $rComment = CoreLocal::get("refundComment");
    self::addRecord(array(
        'description' => '** Tare Weight ' . CoreLocal::get('tare') . ' **',
        'trans_type' => '0',
        'trans_status' => 'D',
        'voided' => 6,
    ));
    CoreLocal::set("refund",$refund);
    CoreLocal::set("refundComment",$rComment);
}

/**
  Add transaction discount record
*/
static public function addTransDiscount() 
{
    self::addRecord(array(
        'upc' => 'DISCOUNT',
        'description' => 'Discount',
        'trans_type' => 'S',
        'quantity' => 1,
        'unitPrice' => MiscLib::truncate2(-1 * CoreLocal::get('transDiscount')),
        'total' => MiscLib::truncate2(-1 * CoreLocal::get('transDiscount')),
        'ItemQtty' => 1,
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
    // @hintable
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
        'trans_status' => 'D',
        'department' => $dept,
        'total' => $total,
        'regPrice' => $regPrice,
        'numflag' => $nflag,
        'charflag' => $cflag,
    ));
}

/**
  Finish the current transaction
  @param $incomplete [boolean] optional, default false

  This method:
  1) Adds tax and discount lines if transaction is complete
     (i.e., $incomplete == false)
  2) Rotates data out of localtemptrans
  3) Advances trans_no variable to next available value

  This method replaces older AjaxEnd.php / end.php operations
  where the receipt was printed first and then steps 1-3
  above happened. This method should be called BEFORE printing
  a receipt. Receipts are now always printed via localtranstoday.
*/
static public function finalizeTransaction($incomplete=false)
{
    if (!$incomplete) {
        self::addtransDiscount();
        self::addTax();
        $taxes = Database::lineItemTaxes();
        foreach($taxes as $tax) {
            if (CoreLocal::get('TaxExempt') == 1) {
                $tax['amount'] = 0.00;
            }
            self::addLogRecord(array(
                'upc' => 'TAXLINEITEM',
                'description' => $tax['description'],
                'numflag' => $tax['rate_id'],
                'amount2' => $tax['amount'],
            ));
        }
        DiscountModule::lineItems();
    }

    if (Database::rotateTempData()) { // rotate data
        Database::clearTempTables();
    }

    // advance trans_no value
    Database::loadglobalvalues();
    $nextTransNo = Database::gettransno(CoreLocal::get('CashierNo'));
    CoreLocal::set('transno', $nextTransNo);
    Database::setglobalvalue('TransNo', $nextTransNo);
}

static public function debugLog($val)
{
    $tdate = strftime("%Y-%m-%d %H:%M:%S", time());
    if (CoreLocal::get("DBMS") == "mssql") {
        $tdate = strftime("%m/%d/%y %H:%M:%S %p", time());
    }
    $transNum = ReceiptLib::receiptNumber();
    $lastID = CoreLocal::get('LastID');

    $dbc = Database::tDataConnect();
    if ($dbc->table_exists('DebugLog')) {
        $prep = $dbc->prepare('INSERT INTO DebugLog 
                              (tdate, transNum, transID, entry)
                              VALUES
                              (?, ?, ?, ?)');
        $res = $dbc->execute($prep, array($tdate, $transNum, $lastID, $val));

        return $res ? true : false;
    }

    return false;
}

}

