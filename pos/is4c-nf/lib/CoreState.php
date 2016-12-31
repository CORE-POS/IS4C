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
use COREPOS\pos\lib\FormLib;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\models\op\TendersModel;
use \CoreLocal;

/**
 @class CoreState
 Setup session variables
*/
class CoreState 
{

/**
  Populates session with default values.
  Short-hand for calling every other function
  in this file. Normally called once on
  startup.
*/
static public function initiateSession() 
{
    self::systemInit();
    self::memberReset();
    self::transReset();
    self::printReset();

    Database::getsubtotals();
    Database::loadglobalvalues();
    self::loadData();
    self::customReceipt();
    self::loadParams();
}

/**
  Initialize system default values in
  session. Variables defined here
  should always exist but won't be reset
  to these values on a regular basis.
*/
static public function systemInit() 
{
    /**
      @var standalone 
      indicates whether the server
      database is available.
      - 0 => server is available 
      - 1 => server is not available 
    */
    CoreLocal::set("standalone",0);

    /**
      @var currentid
      localtemptrans.trans_id for current
      cursor position
    */    
    CoreLocal::set("currentid",1);

    /**
      @var currenttopid
      localtemptrans.trans_id for the first
      item currently shown on screen
    */
    CoreLocal::set("currenttopid",1);

    /**
      @var training
      Lane is in training mode
      - 0 => not in training mode
      - 1 => in training mode
    */
    CoreLocal::set("training",0);

    /**
      @var SNR
      Scale Not Ready. Set a non-zero value
      (normally a UPC) to be entered when
      the scale settles on a weight
    */
    CoreLocal::set("SNR",0);

    /**
      @var weight
      Currently scale weight (as float)
    */
    CoreLocal::set("weight",0);

    /**
      @var scale
      Scale has a valid weight
      - 0 => scale error or settling
      - 1 => scale settled on weight
    */
    CoreLocal::set("scale",1);

    /**
      @var plainmsg
      Lines of text to display on
      main POS screen (pos2.php) that
      are not part of a transaction. Used
      for things like messages after signing
      on or finishing/canceling/suspending a
      transaction
    */
    CoreLocal::set("plainmsg","");

    /**
      Load lane and store numbers from LaneMap array
      if present
    */
    if (is_array(CoreLocal::get('LaneMap'))) {
        $myIPs = MiscLib::getAllIPs();
        foreach ($myIPs as $ip) {
            if (!isset($map[$ip])) {
                continue;
            }
            if (isset($map[$ip]['register_id']) && isset($map[$ip]['store_id'])) {
                CoreLocal::set('laneno', $map[$ip]['register_id']);
                CoreLocal::set('store_id', $map[$ip]['store_id']);
            }
            // use first matching IP
            break;
        }

    }
}

/**
  Initialize transaction variable in session.
  This function is called after the end of every
  transaction so these values will be the
  the defaults every time.
*/
static public function transReset() 
{
    /**
      @var End
      Indicates transaction has ended
      0 => transaction in progress
      1 => transaction is complete
    */
    CoreLocal::set("End",0);

    /**
      @var memberID
      Current member number
    */
    CoreLocal::set("memberID","0");

    /**
      @var TaxExempt
      Tax exempt status flag
      0 => transaction is taxable
      1 => transaction is tax exempt
    */
    CoreLocal::set("TaxExempt",0);

    /**
      @var yousaved
      Total savings on the transaction (as float).
      Includes any if applicable:
      - transaction level percent discount
      - sale prices (localtemptrans.discount)
      - member prices (localtemptrans.memDiscount)
    */
    CoreLocal::set("yousaved",0);

    /**
      @var couldhavesaved
      Total member savings that were not applied.
      Consists of localtemptrans.memDiscount on
      non-member purchases
    */ 
    CoreLocal::set("couldhavesaved",0);

    /**
      @var specials
      Total saving via sale prices. Consists
      of localtemptrans.discount and when applicable
      localtemptrans.memDiscount
    */
    CoreLocal::set("specials",0);

    /**
      @var tare
      Current tare setting (as float)
    */
    CoreLocal::set("tare",0);

    /**
      @var change
      Amount of change due (as float)
    */
    CoreLocal::set("change",0);

    /**
      @var toggletax
      Alter the next item's tax status
      - 0 => do nothing
      - 1 => change next tax status    
    */
    CoreLocal::set("toggletax",0);

    /**
      @var togglefoodstamp
      Alter the next item's foodstamp status
      - 0 => do nothing
      - 1 => change next foodstamp status    
    */
    CoreLocal::set("togglefoodstamp",0);

    /**
      @var toggleDiscountable
      Alter the next item's discount status
      - 0 => do nothing
      - 1 => change next discount status    
    */
    CoreLocal::set("toggleDiscountable",0);

    /**
      @var refund
      Indicates current ring is a refund. This
      is set as a session variable as it could
      apply to items, open rings, or potentially
      other kinds of input.
      - 0 => not a refund
      - 1 => refund
    */
    CoreLocal::set("refund",0);

    /**
      @var multiple
      Cashier used the "*" key to enter
      a multiplier. This currently makes the
      products.qttyEnforced flag work. This may
      be redundant and the quantity setting below
      is likely sufficient to determine whether
      a multiplier was used.
    */
    CoreLocal::set("multiple",0);

    /**
      @var quantity
      Quantity for the current ring. A non-zero
      value usually means the cashier used "*" 
      to enter a multiplier. A value of zero
      gets converted to one unless the item requires
      a quantity via products.scale or
      products.qttyEnforced.
    */
    CoreLocal::set("quantity",0);

    /**
      @var strEntered
      Stores the last user input from the main
      POS screen. Used in conjunction with the
      msgrepeat option.
    */
    CoreLocal::set("strEntered","");

    /**
      @var strRemembered
      Value to use as input the next time
      the main POS screen loads. Used in
      conjunction with the msgrepeat
      option.
    */
    CoreLocal::set("strRemembered","");

    /**
      @var msgrepeat
      Controls repeat input behavior
      - 0 => do nothing
      - 1 => set POS input to the value
         in strRemembered

      strEntered, strRemembered, and msgrepeat
      are strongly interrelated.

      When parsing user input on the main POS screen,
      the entered value is always stored as strEntered.

      msgrepeat gets used in two slightly different
      ways. If you're on a page other than the main
      screen, set msgrepeat to 1 and strRemembered to
      the desired input, then redirect to pos2.php. This
      will run the chosen value through standard input
      processing.

      The other way msgrepeat is used is with boxMsg2.php.
      This page is a generic enter to continue, clear to
      cancel prompt. If you redirect to boxMsg2.php and the
      user presses enter, POS will set msgrepeat to 1 and
      copy strEntered into strRemembered effectively repeating
      the last input. Code using this feature will interpret
      a msgrepeat value of 1 to indicate the user has given
      confirmation.

      msgrepeat is always cleared back to zero when input
      processing finishes.
    
    */
    CoreLocal::set("msgrepeat",0);

    /**
      @var lastRepeat
      [Optional] Reason for the last repeated message
      Useful to set & check in situations where multiple
      confirmations may be required.
    */
    CoreLocal::set('lastRepeat', '');

    /**
      @var boxMsg
      Message string to display on the boxMsg2.php page
    */
    CoreLocal::set("boxMsg","");        

    /**
      @var itemPD
      Line item percent discount (as integer; 5 = 5%).
      Applies a percent discount to the current ring.
    */
    CoreLocal::set("itemPD",0);

    /**
      @var cashierAgeOverride
      This flag indicates a manager has given approval
      for the cashier to sell age-restricted items. This
      setting only comes into effect if the cashier is
      too young. The value persists for the remainder of
      the transaction so the manager does not have to give
      approval for each individual item.
      - 0 => no manager approval
      - 1 => manager has given approval
    */
    CoreLocal::set("cashierAgeOverride",0);

    /**
      @var voidOverride
      This flag indicates a manager has given approval
      for the cashier to void items beyond the per
      transaction limit.
      The value persists for the remainder of
      the transaction so the manager does not have to give
      approval for each individual item.
      - 0 => no manager approval
      - 1 => manager has given approval
    */
    CoreLocal::set("voidOverride",0);
    
    /**
      @var lastWeight
      The weight of the last by-weight item entered into
      the transaction. It's used to monitor for scale 
      problems. Consecutive items with the exact same
      weight often indicate the scale is stuck or not
      responding properly.
    */
    CoreLocal::set("lastWeight",0.00);

    if (!is_array(CoreLocal::get('PluginList'))) {
        CoreLocal::set('PluginList', array());
    }

    if (is_array(CoreLocal::get('PluginList'))) {
        foreach(CoreLocal::get('PluginList') as $p) {
            if (!class_exists($p)) continue;
            $obj = new $p();
            $obj->plugin_transaction_reset();
        }
    }

    if (is_array(CoreLocal::get('Notifiers'))) {
        foreach(CoreLocal::get('Notifiers') as $n) {
            if (!class_exists($n)) continue;
            $obj = new $n();
            $obj->transactionReset();
        }
    }

    FormLib::clearTokens();
    DiscountModule::transReset();
}

/**
  Initialize print related variables in session.
  This function is called after the end of
  every transaction.
*/
static public function printReset() 
{
    /**
      @var receiptToggle
      Control whether a receipt prints
      - 0 => do not print receipt
      - 1 => print receipt normally

      Note that some kinds of receipts
      such as credit card or store charge
      signature slips cannot be suppressed
      and will always print.
    */
    CoreLocal::set("receiptToggle",1);

    /**
      @var autoReprint
      Print two receipts.
      - 0 => do nothing
      - 1 => print a copy of the receipt
    */
    CoreLocal::set("autoReprint",0);
}

/**
  Initialize member related variables in session.
  This function is called after the end of
  every transaction.
*/
static public function memberReset() 
{
    /**
      @var memberID
      The current member number
    */
    CoreLocal::set("memberID","0");

    /**
      @var isMember
      Indicates whether the current customer
      is considered a member or just someone
      who happens to have a number
      0 - not considered a member
      1 - is a member
      
      This is controlled by custdata.Type. That
      field must be 'PC' for the account to be
      considered a member.
    */
    CoreLocal::set("isMember",0);

    /**
      @var isStaff
      Indicates whether the current customer is
      an employee. Corresponds to custdata.staff.
    */
    CoreLocal::set("isStaff",0);

    /**
      @var SSI
      Corresponds to custdata.SSI for current
      customer.
    */
    CoreLocal::set("SSI",0);

    /**
      @var memMsg
      Text string shown in the upper left of the
      POS screen near the word MEMBER.
    */
    CoreLocal::set("memMsg","");

    /**
      @var memType
      Corresponds to custdata.memType for current
      customer.
    */
    CoreLocal::set("memType",0);
    
    /**
      @var balance
      Current customer's charge account balance
      owed.
    */
    CoreLocal::set("balance",0);

    /**
      @var availBal
      Current customer's available charge account
      balance. This is equivalent to 
      custdata.ChargeLimit minus the balance
      setting above.
    */
    CoreLocal::set("availBal",0);

    /**
      @var percentDiscount
      The current customer's transaction-level 
      percent discount as an integer (i.e., 5 = 5%).
      Corresponds to custdata.Discount.
    */
    CoreLocal::set("percentDiscount",0);

    /**
      @var memAge
      Actually current customer's birthday
      as YYYYMMDD but used to calculate age.
      This is stored if the customer purchases
      an age-restricted item.
    */
    CoreLocal::set("memAge",date('Ymd'));
}

/**
  If there are records in localtemptrans, get the 
  member number and initialize session member
  variables.

  The only time this function does anything is
  in crash recovery - if a browser is closed and
  re-opened or the computer is rebooted in the
  middle of a transaction.
*/
static public function loadData() 
{
    $queryLocal = "select card_no from localtemptrans";
    
    $dbLocal = Database::tDataConnect();
    $resultLocal = $dbLocal->query($queryLocal);
    $numRowsLocal = $dbLocal->numRows($resultLocal);

    if ($numRowsLocal > 0) {
        $rowLocal = $dbLocal->fetchRow($resultLocal);
        
        if ($rowLocal["card_no"] && strlen($rowLocal["card_no"]) > 0) {
            \COREPOS\pos\lib\MemberLib::setMember($rowLocal['card_no'], 1);
        }
    }
}

/** 
   Fetch text fields from the customReceipt table
   These fields are used for various messages that
   invariably must be customized at every store.
 */
static public function customReceipt()
{
    $dbc = Database::pDataConnect(); 
    $headerQ = "select text,type,seq from customReceipt order by seq";
    $headerR = $dbc->query($headerQ);
    $counts = array();
    while($headerW = $dbc->fetch_row($headerR)) {
        $typeStr = $headerW['type'];
        $numeral = $headerW['seq']+1;
        $text = $headerW['text'];
        
        // translation for really old data
        if (strtolower($typeStr)=="header") {
            $typeStr = "receiptHeader";
        } elseif(strtolower($typeStr)=="footer") {
            $typeStr = "receiptFooter";
        }

        CoreLocal::set($typeStr.$numeral,$text);

        if (!isset($counts[$typeStr])) {
            $counts[$typeStr] = 0;
        }
        $counts[$typeStr]++;
    }
    
    foreach($counts as $key => $num) {
        CoreLocal::set($key."Count",$num);
    }
}

static public function getCustomerPref($key)
{
    if (CoreLocal::get('memberID') == 0) {
        return '';
    }

    $dbc = Database::pDataConnect();
    $prep = $dbc->prepare('SELECT pref_value FROM custPreferences WHERE
        card_no=? AND pref_key=?');
    $args = array(CoreLocal::get('memberID'),$key);
    $val = $dbc->getValue($prep, $args);

    return $val === false ? '' : $val;
}

static public function cashierLogin($transno=False, $age=0)
{
    if (CoreLocal::get('CashierNo')==9999) {
        CoreLocal::set('training', 1);
    }
    if (!is_numeric($age)) {
        $age = 0;
    }
    CoreLocal::set('cashierAge', $age);
    if($transno && is_numeric($transno)) {
        CoreLocal::set('transno', $transno);
    }
}

static private function setParams($parameters)
{
    foreach ($parameters->find() as $global) {
        $key = $global->param_key();
        $value = $global->materializeValue();
        CoreLocal::set($key, $value, true);
    }
}

static public function loadParams()
{
    $dbc = Database::pDataConnect();

    // newer & optional table. should not fail
    // if it's missing
    if (CoreLocal::get('NoCompat') != 1 && !$dbc->table_exists('parameters')) {
        return;
    }
    
    // load global settings first
    $parameters = new \COREPOS\pos\lib\models\op\ParametersModel($dbc);
    $parameters->lane_id(0);
    $parameters->store_id(0);
    self::setParams($parameters);

    // apply store-specific settings next
    // with any overrides that occur
    $parameters->reset();
    $parameters->store_id(CoreLocal::get('store_id'));
    $parameters->lane_id(0);
    self::setParams($parameters);

    // apply lane-specific settings last
    // with any overrides that occur
    $parameters->reset();
    $parameters->lane_id(CoreLocal::get('laneno'));
    $parameters->store_id(0);
    self::setParams($parameters);

    // load tender map from tenders instead of parameters
    $map = array();
    if (CoreLocal::get('NoCompat') == 1) {
        $model = new TendersModel($dbc);
        $map = $model->getMap();
    } else {
        $table = $dbc->tableDefinition('tenders');
        if (isset($table['TenderModule'])) {
            $model = new TendersModel($dbc);
            $map = $model->getMap();
        }
    }
    if (count($map) > 0 || !is_array(CoreLocal::get('TenderMap'))) {
        CoreLocal::set('TenderMap', $map);
    }
}

}

