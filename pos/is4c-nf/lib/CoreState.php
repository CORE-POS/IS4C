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

include_once(realpath(dirname(__FILE__)."/../ini.php"));

/**
 @class CoreState
 Setup session variables
*/
class CoreState extends LibraryClass 
{

/**
  Populates $CORE_LOCAL with default values.
  Short-hand for calling every other function
  in this file. Normally called once on
  startup.
*/
static public function initiate_session() 
{
	self::systemInit();
	self::memberReset();
	self::transReset();
	self::printReset();
	PaycardLib::paycard_reset();

	Database::getsubtotals();
	Database::loadglobalvalues();
	self::loadData();
	self::customReceipt();
    self::loadParams();
}

/**
  Initialize system default values in
  $CORE_LOCAL. Variables defined here
  should always exist but won't be reset
  to these values on a regular basis.
*/
static public function systemInit() 
{
	global $CORE_LOCAL;

	/**
	  @var standalone 
	  indicates whether the server
	  database is available.
	  - 0 => server is available 
	  - 1 => server is not available 
	*/
	$CORE_LOCAL->set("standalone",0);

	/**
	  @var currentid
	  localtemptrans.trans_id for current
	  cursor position
	*/	
	$CORE_LOCAL->set("currentid",1);

	/**
	  @var currenttopid
	  localtemptrans.trans_id for the first
	  item currently shown on screen
	*/
	$CORE_LOCAL->set("currenttopid",1);

	/**
	  @var training
	  Lane is in training mode
	  - 0 => not in training mode
	  - 1 => in training mode
	*/
	$CORE_LOCAL->set("training",0);

	/**
	  @var SNR
	  Scale Not Ready. Set a non-zero value
	  (normally a UPC) to be entered when
	  the scale settles on a weight
	*/
	$CORE_LOCAL->set("SNR",0);

	/**
	  @var weight
	  Currently scale weight (as float)
	*/
	$CORE_LOCAL->set("weight",0);

	/**
	  @var scale
	  Scale has a valid weight
	  - 0 => scale error or settling
	  - 1 => scale settled on weight
	*/
	$CORE_LOCAL->set("scale",1);

	/**
	  @var plainmsg
	  Lines of text to display on
	  main POS screen (pos2.php) that
	  are not part of a transaction. Used
	  for things like messages after signing
	  on or finishing/canceling/suspending a
	  transaction
	*/
	$CORE_LOCAL->set("plainmsg","");

	/**
	  @var ccTermOut
	  Used for sending messages to Ingenico
	  device. Very alpha.
	*/
	$CORE_LOCAL->set("ccTermOut","idle");

    /**
      Load lane and store numbers from LaneMap array
      if present
    */
    if (is_array($CORE_LOCAL->get('LaneMap'))) {
        $my_ips = MiscLib::getAllIPs();
        foreach ($my_ips as $ip) {
            if (!isset($map[$ip])) {
                continue;
            }
            if (isset($map[$ip]['register_id']) && isset($map[$ip]['store_id'])) {
                $CORE_LOCAL->set('laneno', $map[$ip]['register_id']);
                $CORE_LOCAL->set('store_id', $map[$ip]['store_id']);
            }
            // use first matching IP
            break;
        }

    }
}

/**
  Initialize transaction variable in $CORE_LOCAL.
  This function is called after the end of every
  transaction so these values will be the
  the defaults every time.
*/
static public function transReset() 
{
	global $CORE_LOCAL;

	/**
	  @var End
	  Indicates transaction has ended
	  0 => transaction in progress
	  1 => transaction is complete
	*/
	$CORE_LOCAL->set("End",0);

	/**
	  @var memberID
	  Current member number
	*/
	$CORE_LOCAL->set("memberID","0");

	/**
	  @var TaxExempt
	  Tax exempt status flag
	  0 => transaction is taxable
	  1 => transaction is tax exempt
	*/
	$CORE_LOCAL->set("TaxExempt",0);

	/**
	  @var yousaved
	  Total savings on the transaction (as float).
	  Includes any if applicable:
	  - transaction level percent discount
	  - sale prices (localtemptrans.discount)
	  - member prices (localtemptrans.memDiscount)
	*/
	$CORE_LOCAL->set("yousaved",0);

	/**
	  @var couldhavesaved
	  Total member savings that were not applied.
	  Consists of localtemptrans.memDiscount on
	  non-member purchases
	*/ 
	$CORE_LOCAL->set("couldhavesaved",0);

	/**
	  @var specials
	  Total saving via sale prices. Consists
	  of localtemptrans.discount and when applicable
	  localtemptrans.memDiscount
	*/
	$CORE_LOCAL->set("specials",0);

	/**
	  @var tare
	  Current tare setting (as float)
	*/
	$CORE_LOCAL->set("tare",0);

	/**
	  @var change
	  Amount of change due (as float)
	*/
	$CORE_LOCAL->set("change",0);

	/**
	  @var toggletax
	  Alter the next item's tax status
	  - 0 => do nothing
	  - 1 => change next tax status	
	*/
	$CORE_LOCAL->set("toggletax",0);

	/**
	  @var togglefoodstamp
	  Alter the next item's foodstamp status
	  - 0 => do nothing
	  - 1 => change next foodstamp status	
	*/
	$CORE_LOCAL->set("togglefoodstamp",0);

	/**
	  @var toggleDiscountable
	  Alter the next item's discount status
	  - 0 => do nothing
	  - 1 => change next discount status	
	*/
	$CORE_LOCAL->set("toggleDiscountable",0);

	/**
	  @var refund
	  Indicates current ring is a refund. This
	  is set as a session variable as it could
	  apply to items, open rings, or potentially
	  other kinds of input.
	  - 0 => not a refund
	  - 1 => refund
	*/
	$CORE_LOCAL->set("refund",0);

	/**
	  @var casediscount
	  Line item case discount percentage (as
	  integer; 5 = 5%). This feature may be redundant
	  in that it could be handled with the generic
	  line-item discount. It more or less just differs
	  in that the messages say "Case".
	*/
	$CORE_LOCAL->set("casediscount",0);

	/**
	  @var multiple
	  Cashier used the "*" key to enter
	  a multiplier. This currently makes the
	  products.qttyEnforced flag work. This may
	  be redundant and the quantity setting below
	  is likely sufficient to determine whether
	  a multiplier was used.
	*/
	$CORE_LOCAL->set("multiple",0);

	/**
	  @var quantity
	  Quantity for the current ring. A non-zero
	  value usually means the cashier used "*" 
	  to enter a multiplier. A value of zero
	  gets converted to one unless the item requires
	  a quantity via products.scale or
	  products.qttyEnforced.
	*/
	$CORE_LOCAL->set("quantity",0);

	/**
	  @var strEntered
	  Stores the last user input from the main
	  POS screen. Used in conjunction with the
	  msgrepeat option.
	*/
	$CORE_LOCAL->set("strEntered","");

	/**
	  @var strRemembered
	  Value to use as input the next time
	  the main POS screen loads. Used in
	  conjunction with the msgrepeat
	  option.
	*/
	$CORE_LOCAL->set("strRemembered","");

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
	$CORE_LOCAL->set("msgrepeat",0);

    /**
      @var lastRepeat
      [Optional] Reason for the last repeated message
      Useful to set & check in situations where multiple
      confirmations may be required.
    */
    $CORE_LOCAL->set('lastRepeat', '');

	/**
	  @var boxMsg
	  Message string to display on the boxMsg2.php page
	*/
	$CORE_LOCAL->set("boxMsg","");		

	/**
	  @var itemPD
	  Line item percent discount (as integer; 5 = 5%).
	  Applies a percent discount to the current ring.
	*/
	$CORE_LOCAL->set("itemPD",0);

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
	$CORE_LOCAL->set("cashierAgeOverride",0);

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
	$CORE_LOCAL->set("voidOverride",0);
	
	/**
	  @var lastWeight
	  The weight of the last by-weight item entered into
	  the transaction. It's used to monitor for scale 
	  problems. Consecutive items with the exact same
	  weight often indicate the scale is stuck or not
	  responding properly.
	*/
	$CORE_LOCAL->set("lastWeight",0.00);

	/**
	  @var CachePanEncBlcok
	  Stores the encrypted string of card information
	  provided by the CC terminal. If the terminal is
	  facing the customer, the customer may swipe their
	  card before the cashier is done ringing in items
	  so the value is stored in session until the
	  cashier is ready to process payment
	*/
	$CORE_LOCAL->set("CachePanEncBlock","");

	/**
	  @var CachePinEncBlock
	  Stores the encrypted string of PIN data.
	  Similar to CachePanEncBlock.
	*/
	$CORE_LOCAL->set("CachePinEncBlock","");

	/**
	  @var CacheCardType
	  Stores the selected card type.
	  Similar to CachePanEncBlock.
	  Known values are:
	  - CREDIT
	  - DEBIT
	  - EBTFOOD
	  - EBTCASH
	*/
	$CORE_LOCAL->set("CacheCardType","");

	/**
	  @var CacheCardCashBack
	  Stores the select cashback amount.
	  Similar to CachePanEncBlock.
	*/
	$CORE_LOCAL->set("CacheCardCashBack",0);

	/**
	  @var ccTermState
	  Stores a string representing the CC 
	  terminals current display. This drives
	  an optional on-screen icon to let the 
	  cashier know what the CC terminal is
	  doing if they cannot see its screen.
	*/
	$CORE_LOCAL->set('ccTermState','swipe');

	/**
	  @var paycard_voiceauthcode
	  Stores a voice authorization code for use
	  with a paycard transaction. Not normally used
	  but required to pass Mercury's certification
	  script.
	*/
	$CORE_LOCAL->set("paycard_voiceauthcode","");

	/**
	  @var ebt_authcode
	  Stores a foodstamp authorization code.
	  Similar to paycard_voiceauthcode.
	*/
	$CORE_LOCAL->set("ebt_authcode","");

	/**
	  @var ebt_vnum
	  Stores a foodstamp voucher number.
	  Similar to paycard_voiceauthcode.
	*/
	$CORE_LOCAL->set("ebt_vnum","");

	/**
	  @var paycard_keyed
	  - True => card number was hand keyed
	  - False => card was swiped

	  Normally POS figures this out automatically
	  but it has to be overriden to pass Mercury's
	  certification script. They require some
	  keyed transactions even though the CC terminal
	  is only capable of producing swipe-style data.
	*/
	$CORE_LOCAL->set("paycard_keyed",False);
    
    if (!is_array($CORE_LOCAL->get('PluginList'))) {
        $CORE_LOCAL->set('PluginList', array());
    }

    if (is_array($CORE_LOCAL->get('PluginList'))) {
        foreach($CORE_LOCAL->get('PluginList') as $p) {
            if (!class_exists($p)) continue;
            $obj = new $p();
            $obj->plugin_transaction_reset();
        }
    }

    if (is_array($CORE_LOCAL->get('Notifiers'))) {
        foreach($CORE_LOCAL->get('Notifiers') as $n) {
            if (!class_exists($n)) continue;
            $obj = new $n();
            $obj->transactionReset();
        }
    }

    FormLib::clearTokens();
}

/**
  Initialize print related variables in $CORE_LOCAL.
  This function is called after the end of
  every transaction.
*/
static public function printReset() 
{
	global $CORE_LOCAL;

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
	$CORE_LOCAL->set("receiptToggle",1);

	/**
	  @var autoReprint
	  Print two receipts.
	  - 0 => do nothing
	  - 1 => print a copy of the receipt
	*/
	$CORE_LOCAL->set("autoReprint",0);
}

/**
  Initialize member related variables in $CORE_LOCAL.
  This function is called after the end of
  every transaction.
*/
static public function memberReset() 
{
	global $CORE_LOCAL;

	/**
	  @var memberID
	  The current member number
	*/
	$CORE_LOCAL->set("memberID","0");

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
	$CORE_LOCAL->set("isMember",0);

	/**
	  @var isStaff
	  Indicates whether the current customer is
	  an employee. Corresponds to custdata.staff.
	*/
	$CORE_LOCAL->set("isStaff",0);

	/**
	  @var SSI
	  Corresponds to custdata.SSI for current
	  customer.
	*/
	$CORE_LOCAL->set("SSI",0);

	/**
	  @var memMsg
	  Text string shown in the upper left of the
	  POS screen near the word MEMBER.
	*/
	$CORE_LOCAL->set("memMsg","");

	/**
	  @var memType
	  Corresponds to custdata.memType for current
	  customer.
	*/
	$CORE_LOCAL->set("memType",0);
	
	/**
	  @var balance
	  Current customer's charge account balance
	  owed.
	*/
	$CORE_LOCAL->set("balance",0);

	/**
	  @var availBal
	  Current customer's available charge account
	  balance. This is equivalent to 
	  custdata.ChargeLimit minus the balance
	  setting above.
	*/
	$CORE_LOCAL->set("availBal",0);

	/**
	  @var percentDiscount
	  The current customer's transaction-level 
	  percent discount as an integer (i.e., 5 = 5%).
	  Corresponds to custdata.Discount.
	*/
	$CORE_LOCAL->set("percentDiscount",0);

	/**
	  @var memAge
	  Actually current customer's birthday
	  as YYYYMMDD but used to calculate age.
	  This is stored if the customer purchases
	  an age-restricted item.
	*/
	$CORE_LOCAL->set("memAge",date('Ymd'));
}

/**
  If there are records in localtemptrans, get the 
  member number and initialize $CORE_LOCAL member
  variables.

  The only time this function does anything is
  in crash recovery - if a browser is closed and
  re-opened or the computer is rebooted in the
  middle of a transaction.
*/
static public function loadData() 
{
	global $CORE_LOCAL;
	
	$query_local = "select card_no from localtemptrans";
	
	$db_local = Database::tDataConnect();
	$result_local = $db_local->query($query_local);
	$num_rows_local = $db_local->num_rows($result_local);

	if ($num_rows_local > 0) {
		$row_local = $db_local->fetch_array($result_local);
		
		if ($row_local["card_no"] && strlen($row_local["card_no"]) > 0) {
			$CORE_LOCAL->set("memberID",$row_local["card_no"]);
		}
	}

	if ($CORE_LOCAL->get("memberID") == "0") {
		// not used - andy 4/12/07
		$CORE_LOCAL->set("percentDiscount",0);
		$CORE_LOCAL->set("memType",0);
	} else {
		$query_member = "select CardNo,memType,Type,Discount,staff,SSI,
				blueLine,FirstName,LastName
				from custdata where CardNo = '".$CORE_LOCAL->get("memberID")."'";
		$db_product = Database::pDataConnect();
		$result = $db_product->query($query_member);
		if ($db_product->num_rows($result) > 0) {
			$row = $db_product->fetch_array($result);
			$CORE_LOCAL->set("memMsg",$row['blueLine']);
			$CORE_LOCAL->set("memType",$row["memType"]);
			$CORE_LOCAL->set("percentDiscount",$row["Discount"]);

			if ($row["Type"] == "PC") {
                $CORE_LOCAL->set("isMember",1);
			} else {
                $CORE_LOCAL->set("isMember",0);
            }

			$CORE_LOCAL->set("isStaff",$row["staff"]);
			$CORE_LOCAL->set("SSI",$row["SSI"]);

			if ($CORE_LOCAL->get("SSI") == 1) {
				$CORE_LOCAL->set("memMsg",$CORE_LOCAL->get("memMsg")." #");
            }
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
	global $CORE_LOCAL;

	$db = Database::pDataConnect(); 
	$headerQ = "select text,type,seq from customReceipt order by seq";
	$headerR = $db->query($headerQ);
	$counts = array();
	while($headerW = $db->fetch_row($headerR)) {
		$typeStr = $headerW['type'];
		$numeral = $headerW['seq']+1;
		$text = $headerW['text'];
		
		// translation for really old data
		if (strtolower($typeStr)=="header") {
			$typeStr = "receiptHeader";
		} elseif(strtolower($typeStr)=="footer") {
            $typeStr = "receiptFooter";
        }

		$CORE_LOCAL->set($typeStr.$numeral,$text);

		if (!isset($counts[$typeStr])) {
			$counts[$typeStr] = 1;
		} else {
			$counts[$typeStr]++;
        }
	}
	
	foreach($counts as $key => $num) {
		$CORE_LOCAL->set($key."Count",$num);
	}
}

static public function getCustomerPref($key)
{
	global $CORE_LOCAL;
	if ($CORE_LOCAL->get('memberID') == 0) {
        return '';
    }

	$db = Database::pDataConnect();
	$q = sprintf('SELECT pref_value FROM custPreferences WHERE
		card_no=%d AND pref_key=\'%s\'',
		$CORE_LOCAL->get('memberID'),$key);
	$r = $db->query($q);
	if ($r === False) {
        return '';
    }
	if ($db->num_rows($r) == 0) {
        return '';
    }

	$row = $db->fetch_row($r);
    return $row['pref_value'];
}

static public function cashierLogin($transno=False, $age=0)
{
	global $CORE_LOCAL;
	if ($CORE_LOCAL->get('CashierNo')==9999) {
		$CORE_LOCAL->set('training', 1);
	}
	if (!is_numeric($age)) {
        $age = 0;
    }
	$CORE_LOCAL->set('cashierAge', $age);
	if($transno && is_numeric($transno)) {
		$CORE_LOCAL->set('transno', $transno);
	}
}

static public function loadParams(){
    global $CORE_LOCAL;

    $db = Database::pDataConnect();

    // newer & optional table. should not fail
    // if it's missing
    if (!$db->table_exists('parameters')) {
        return;
    }
    
    // load global settings first
    $prep = $db->prepare_statement('SELECT param_key, param_value, is_array FROM parameters
                            WHERE (lane_id=0 OR lane_id IS NULL) AND
                            (store_id=0 OR store_id IS NULL)');
    $globals = $db->exec_statement($prep);
    while($row = $db->fetch_row($globals)) {
        $key = $row['param_key'];
        $value = $row['param_value'];
        if ($row['is_array'] == 1) {
            $value = explode(',', $value);
        }
        $CORE_LOCAL->set($key, $value);
    }

    // apply local settings next
    // with any overrides that occur
    $prep = $db->prepare_statement('SELECT param_key, param_value, is_array FROM parameters
                            WHERE lane_id=?');
    $locals = $db->exec_statement($prep, array($CORE_LOCAL->get('laneno')));
    while($row = $db->fetch_row($locals)) {
        $key = $row['param_key'];
        $value = $row['param_value'];
        if ($row['is_array'] == 1) {
            $value = explode(',', $value);
            if (isset($value[0]) && strstr($value[0], '=>')) {
                // keyed array
                $tmp = array();
                foreach($value as $entry) {
                    list($k, $v) = explode('=>', $entry, 2);
                    $tmp[$k] = $v;
                }
                $value = $tmp;
            }
        } else if (strtoupper($value) === 'TRUE') {
            $value = true;
        } else if (strtoupper($value) === 'FALSE') {
            $value = false;
        }

        $CORE_LOCAL->set($key, $value);
    }
}

}

