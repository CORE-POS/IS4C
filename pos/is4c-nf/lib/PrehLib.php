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
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\TransRecord;
use COREPOS\pos\lib\TotalActions\TotalAction;
use \CoreLocal;
use \DateTime;
use \DateInterval;
use \Exception;

/**
  @class PrehLib
  A horrible, horrible catch-all clutter of functions
*/
class PrehLib 
{

static private function getTenderMods($right)
{
    $ret = array('COREPOS\\pos\\lib\\Tenders\\TenderModule');

    /**
      Get a tender-specific module if
      one has been configured
    */
    $map = CoreLocal::get("TenderMap");
    $dbc = Database::pDataConnect();
    /**
      Fetch module mapping from the database
      if the schema supports it
      16Mar2015
    */
    if (CoreLocal::get('NoCompat') == 1) {
        $tenderModel = new \COREPOS\pos\lib\models\op\TendersModel($dbc);
        $map = $tenderModel->getMap();
    } else {
        $tenderTable = $dbc->tableDefinition('tenders');
        if (isset($tenderTable['TenderModule'])) {
            $tenderModel = new \COREPOS\pos\lib\models\op\TendersModel($dbc);
            $map = $tenderModel->getMap();
        }
    }
    if (is_array($map) && isset($map[$right])) {
        $class = $map[$right];
        if ($class != 'COREPOS\\pos\\lib\\Tenders\\TenderModule') {
            $ret[] = $class;
        }
    }

    return $ret;
}

/**
  Add a tender to the transaction

  @right tender amount in cents (100 = $1)
  @strl tender code from tenders table
  @return An array see Parser::default_json()
   for format explanation.

  This function will automatically end a transaction
  if the amount due becomes <= zero.
*/
static public function tender($right, $strl)
{
    $ret = array('main_frame'=>false,
        'redraw_footer'=>false,
        'target'=>'.baseHeight',
        'output'=>"");

    $strl = MiscLib::centStrToDouble($strl);

    if (CoreLocal::get('RepeatAgain')) {
        // the default tender prompt utilizes boxMsg2.php to
        // repeat the previous input, plus amount, on confirmation
        // the tender's preReqCheck methods will need to pretend
        // this is the first input rather than a repeat
        CoreLocal::set('msgrepeat', 0);
        CoreLocal::set('RepeatAgain', false);
    }

    $tenderMods = self::getTenderMods($right);
    $tenderObject = null;
    foreach ($tenderMods as $class) {
        if (!class_exists($class)) { // try namespaced version
            $class = 'COREPOS\\pos\\lib\\Tenders\\' . $class;
        }
        if (!class_exists($class)) {
            $ret['output'] = DisplayLib::boxMsg(
                _('tender is misconfigured'),
                _('Notify Administrator'),
                false,
                DisplayLib::standardClearButton()
            );
            return $ret;
        } 
        $tenderObject = new $class($right, $strl);
        /**
          Do tender-specific error checking and prereqs
        */
        $error = $tenderObject->ErrorCheck();
        if ($error !== true) {
            $ret['output'] = $error;
            return $ret;
        }
        $prereq = $tenderObject->PreReqCheck();
        if ($prereq !== true) {
            $ret['main_frame'] = $prereq;
            return $ret;
        }
    }

    // add the tender record
    $tenderObject->Add();
    Database::getsubtotals();

    // see if transaction has ended
    if (CoreLocal::get("amtdue") <= 0.005 && $tenderObject->endsTransaction()) {
        $ret = self::tenderEndsTransaction($tenderObject, $ret);
    } else {
        $ret = self::tenderContinuesTransaction($ret);
    }
    $ret['redraw_footer'] = true;

    return $ret;
}

private static function tenderEndsTransaction($tenderObject, $ret)
{
    CoreLocal::set("change",-1 * CoreLocal::get("amtdue"));
    CoreLocal::set('strEntered', '');
    CoreLocal::set('msgrepeat', 0);
    $cashReturn = CoreLocal::get("change");
    TransRecord::addchange($cashReturn, $tenderObject->ChangeType(), $tenderObject->ChangeMsg());
                
    CoreLocal::set("End",1);
    $ret['receipt'] = 'full';
    $ret['output'] = DisplayLib::printReceiptFooter();
    TransRecord::finalizeTransaction();

    return $ret;
}

private static function tenderContinuesTransaction($ret)
{
    CoreLocal::set("change",0);
    CoreLocal::set("fntlflag",0);
    Database::setglobalvalue("FntlFlag", 0);
    $chk = self::ttl();
    if ($chk === true) {
        $ret['output'] = DisplayLib::lastpage();
    } else {
        $ret['main_frame'] = $chk;
    }

    return $ret;
}

static private function addRemoveDiscountViews()
{
    $dbc = Database::tDataConnect();
    if (CoreLocal::get("isMember") == 1 || CoreLocal::get("memberID") == CoreLocal::get("visitingMem")) {
        $cols = Database::localMatchingColumns($dbc,"localtemptrans","memdiscountadd");
        $dbc->query("INSERT INTO localtemptrans ({$cols}) SELECT {$cols} FROM memdiscountadd");
    } else {
        $cols = Database::localMatchingColumns($dbc,"localtemptrans","memdiscountremove");
        $dbc->query("INSERT INTO localtemptrans ({$cols}) SELECT {$cols} FROM memdiscountremove");
    }

    if (CoreLocal::get("isStaff") != 0) {
        $cols = Database::localMatchingColumns($dbc,"localtemptrans","staffdiscountadd");
        $dbc->query("INSERT INTO localtemptrans ({$cols}) SELECT {$cols} FROM staffdiscountadd");
    } else {
        $cols = Database::localMatchingColumns($dbc,"localtemptrans","staffdiscountremove");
        $dbc->query("INSERT INTO localtemptrans ({$cols}) SELECT {$cols} FROM staffdiscountremove");
    }
}

static private function runTotalActions()
{
    $ttlHooks = CoreLocal::get('TotalActions');
    if (is_array($ttlHooks)) {
        foreach($ttlHooks as $ttlClass) {
            if ("$ttlClass" == "") {
                continue;
            }
            $mod = TotalAction::factory($ttlClass);
            $result = $mod->apply();
            if ($result !== true && is_string($result)) {
                return $result; // redirect URL
            }
        }
    }

    return true;
}

/**
  Total the transaction
  @return
   True - total successfully
   String - URL

  If ttl() returns a string, go to that URL for
  more information on the error or to resolve the
  problem. 

  The most common error, by far, is no 
  member number in which case the return value
  is the member-entry page.
*/
static public function ttl() 
{
    if (CoreLocal::get("memberID") == "0") {
        return MiscLib::baseURL()."gui-modules/memlist.php";
    } 

    self::addRemoveDiscountViews();

    CoreLocal::set("ttlflag",1);
    Database::setglobalvalue("TTLFlag", 1);

    // if total is called before records have been added to the transaction,
    // Database::getsubtotals will zero out the discount
    $savePD = CoreLocal::get('percentDiscount');

    // Refresh totals after staff and member discounts.
    Database::getsubtotals();

    $ttlHooks = self::runTotalActions();
    if ($ttlHooks !== true) {
        // follow redirect
        return $ttlHooks;
    }

    // Refresh totals after total actions
    Database::getsubtotals();

    CoreLocal::set('percentDiscount', $savePD);

    if (CoreLocal::get("percentDiscount") > 0) {
        if (CoreLocal::get('member_subtotal') === 0 || CoreLocal::get('member_subtotal') === '0') {
            // 5May14 Andy
            // Why is this different trans_type & voided from
            // the other Subtotal record generated farther down?
            TransRecord::addRecord(array(
                'description' => 'Subtotal',
                'trans_type' => '0',
                'trans_status' => 'D',
                'unitPrice' => MiscLib::truncate2(CoreLocal::get('transDiscount') + CoreLocal::get('subtotal')),
                'voided' => 7,
            ));
        }
        TransRecord::discountnotify(CoreLocal::get("percentDiscount"));
        TransRecord::addRecord(array(
            'description' => CoreLocal::get('percentDiscount') . '% Discount',
            'trans_type' => 'C',
            'trans_status' => 'D',
            'unitPrice' => MiscLib::truncate2(-1 * CoreLocal::get('transDiscount')),
            'voided' => 5,
        ));
    }

    $amtDue = str_replace(",", "", CoreLocal::get("amtdue"));

    $memline = "";
    if(CoreLocal::get("memberID") != CoreLocal::get("defaultNonMem")) {
        $memline = " #" . CoreLocal::get("memberID");
    } 
    // temporary fix Andy 13Feb13
    // my cashiers don't like the behavior; not configurable yet
    if (CoreLocal::get("store") == "wfc") $memline="";
    TransRecord::addRecord(array(
        'description' => 'Subtotal ' 
                         . MiscLib::truncate2(CoreLocal::get('subtotal')) 
                         . ', Tax' 
                         . MiscLib::truncate2(CoreLocal::get('taxTotal')) 
                         . $memline,
        'trans_type' => 'C',
        'trans_status' => 'D',
        'unitPrice' => $amtDue,
        'voided' => 3,
    ));

    if (CoreLocal::get("fntlflag") == 1) {
        TransRecord::addRecord(array(
            'description' => 'Foodstamps Eligible',
            'trans_type' => '0',
            'trans_status' => 'D',
            'unitPrice' => MiscLib::truncate2(CoreLocal::get('fsEligible')),
            'voided' => 7,
        ));
    }

    return true;
}

/**
  Total the transaction, which the cashier thinks may be eligible for the
     Ontario Meal Tax Rebate.
  @return
   True - total successfully
   String - URL

  If ttl() returns a string, go to that URL for
  more information on the error or to resolve the
  problem. 

  The most common error, by far, is no 
  member number in which case the return value
  is the member-entry page.

  The Ontario Meal Tax Rebate refunds the provincial part of the
  Harmonized Sales Tax if the total of the transaction is not more
  than a certain amount.

  If the transaction qualifies,
   change the tax status for each item at the higher rate to the lower rate.
   Display a message that a change was made.
  Otherwise display a message about that.
  Total the transaction as usual.

*/
static public function omtr_ttl() 
{
    // Must have gotten member number before totaling.
    if (CoreLocal::get("memberID") == "0") {
        return MiscLib::baseURL()."gui-modules/memlist.php";
    }

    self::addRemoveDiscountViews();

    CoreLocal::set("ttlflag",1);
    Database::setglobalvalue("TTLFlag", 1);

    // Refresh totals after staff and member discounts.
    Database::getsubtotals();

    // Is the before-tax total within range?
    if (CoreLocal::get("runningTotal") <= 4.00 ) {
        $totalBefore = CoreLocal::get("amtdue");
        $ret = Database::changeLttTaxCode("HST","GST");
        if ( $ret !== True ) {
            TransRecord::addcomment("$ret");
        } else {
            Database::getsubtotals();
            $saved = ($totalBefore - CoreLocal::get("amtdue"));
            $comment = sprintf("OMTR OK. You saved: $%.2f", $saved);
            TransRecord::addcomment("$comment");
        }
    }
    else {
        TransRecord::addcomment("Does NOT qualify for OMTR");
    }

    /* If member can do Store Charge, warn on certain conditions.
     * Important preliminary is to refresh totals.
    */
    \COREPOS\pos\lib\MemberLib::chargeOk();
    if (CoreLocal::get("balance") < CoreLocal::get("memChargeTotal") && CoreLocal::get("memChargeTotal") > 0){
        if (CoreLocal::get('msgrepeat') == 0){
            CoreLocal::set("boxMsg",sprintf("<b>A/R Imbalance</b><br />
                Total AR payments $%.2f exceeds AR balance %.2f<br />",
                CoreLocal::get("memChargeTotal"),
                CoreLocal::get("balance")));
            CoreLocal::set('boxMsgButtons', array(
                'Confirm [enter]' => '$(\'#reginput\').val(\'\');submitWrapper();',
                'Cancel [clear]' => '$(\'#reginput\').val(\'CL\');submitWrapper();',
            ));
            CoreLocal::set("strEntered","TL");
            return MiscLib::baseURL()."gui-modules/boxMsg2.php?quiet=1";
        }
    }

    // Display discount.
    if (CoreLocal::get("percentDiscount") > 0) {
        TransRecord::addRecord(array(
            'description' => CoreLocal::get('percentDiscount') . '% Discount',
            'trans_type' => 'C',
            'trans_status' => 'D',
            'unitPrice' => MiscLib::truncate2(-1 * CoreLocal::get('transDiscount')),
            'voided' => 5,
        ));
    }

    $amtDue = str_replace(",", "", CoreLocal::get("amtdue"));

    // Compose the member ID string for the description.
    $memline = "";
    if(CoreLocal::get("memberID") != CoreLocal::get("defaultNonMem")) {
        $memline = " #" . CoreLocal::get("memberID");
    }

    // Put out the Subtotal line.
    $peek = self::peekItem();
    if (True || substr($peek,0,9) != "Subtotal "){
        TransRecord::addRecord(array(
            'description' => 'Subtotal ' 
                             . MiscLib::truncate2(CoreLocal::get('subtotal')) 
                             . ', Tax' 
                             . MiscLib::truncate2(CoreLocal::get('taxTotal')) 
                             . $memline,
            'trans_type' => 'C',
            'trans_status' => 'D',
            'unitPrice' => $amtDue,
            'voided' => 3,
        ));
    }

    if (CoreLocal::get("fntlflag") == 1) {
        TransRecord::addRecord(array(
            'description' => 'Foodstamps Eligible',
            'trans_type' => '0',
            'trans_status' => 'D',
            'unitPrice' => MiscLib::truncate2(CoreLocal::get('fsEligible')),
            'voided' => 7,
        ));
    }

    return true;
// omtr_ttl
}

/**
  See what the last item in the transaction is currently
  @param $fullRecord [boolean] return full database record.
    Default is false. Just returns description.
  @return localtemptrans.description for the last item
    or localtemptrans record for the last item

    If no record exists, returns false
*/
static public function peekItem($fullRecord=false, $transID=false)
{
    $dbc = Database::tDataConnect();
    $query = "SELECT * FROM localtemptrans ";
    if ($transID) {
        $query .= ' WHERE trans_id=' . ((int)$transID);
    }
    $query .= " ORDER BY trans_id DESC";
    $res = $dbc->query($query);
    $row = $dbc->fetchRow($res);

    if ($fullRecord) {
        return is_array($row) ? $row : false;
    }
    return isset($row['description']) ? $row['description'] : false;
}

/**
  Add foodstamp elgibile total record
*/
static public function fsEligible() 
{
    Database::getsubtotals();
    CoreLocal::set("fntlflag",1);
    Database::setglobalvalue("FntlFlag", 1);
    if (CoreLocal::get("ttlflag") != 1) {
        return self::ttl();
    }
    TransRecord::addRecord(array(
        'description' => 'Foodstamps Eligible',
        'trans_type' => '0',
        'trans_status' => 'D',
        'unitPrice' => MiscLib::truncate2(CoreLocal::get('fsEligible')),
        'voided' => 7,
    ));

    return true;
}

/**
  Add a percent discount notification
  @param $strl discount percentage
  @param $json keyed array
  @return An array see Parser::default_json()
  @deprecated
  Use discountnotify() instead. This just adds
  hard-coded percentages and PLUs that likely
  aren't applicable anywhere but the Wedge.
*/
static public function percentDiscount($strl,$json=array()) 
{
    if ($strl == 10.01) {
        $strl = 10;
    }

    if (!is_numeric($strl) || $strl > 100 || $strl < 0) {
        $json['output'] = DisplayLib::boxMsg(
            _("discount invalid"),
            '',
            false,
            DisplayLib::standardClearButton()
        );
    } else {
        $dbc = Database::tDataConnect();
        if ($strl != 0) {
            TransRecord::discountnotify($strl);
        }
        $dbc->query("update localtemptrans set percentDiscount = ".$strl);
        CoreLocal::set('percentDiscount', $strl);
        DiscountModule::transReset();
        $chk = self::ttl();
        if ($chk !== true) {
            $json['main_frame'] = $chk;
        }
        $json['output'] = DisplayLib::lastpage();
    }

    return $json;
}

/**
  Enforce age-based restrictions
  @param $requiredAge [int] age in years
  @param $ret [array] Parser-formatted return value
  @return [array]
   0 - boolean age-related approval required
   1 - array Parser-formatted return value
*/
public static function ageCheck($requiredAge, $ret)
{
    $myUrl = MiscLib::baseURL();
    if (CoreLocal::get("cashierAge") < 18 && CoreLocal::get("cashierAgeOverride") != 1){
        $ret['main_frame'] = $myUrl."gui-modules/adminlogin.php?class=COREPOS-pos-lib-adminlogin-AgeApproveAdminLogin";
        return array(true, $ret);
    }

    if (CoreLocal::get("memAge")=="") {
        CoreLocal::set("memAge",date('Ymd'));
    }
    try {
        $ofAgeOnDay = new DateTime(CoreLocal::get('memAge'));
        $ofAgeOnDay->add(new DateInterval("P{$requiredAge}Y"));
    } catch (Exception $ex) {
        $ofAgeOnDay = new DateTime(date('Y-m-d', strtotime('tomorrow')));
    }
    $today = new DateTime(date('Y-m-d'));
    if ($ofAgeOnDay > $today) {
        $ret['udpmsg'] = 'twoPairs';
        $ret['main_frame'] = $myUrl.'gui-modules/requestInfo.php?class=COREPOS-pos-parser-parse-UPC';
        return array(true, $ret);
    }

    return array(false, $ret);
}

public static function applyToggles($tax, $foodstamp, $discount)
{
    if (CoreLocal::get("toggletax") != 0) {
        $tax = ($tax==0) ? 1 : 0;
        CoreLocal::set("toggletax",0);
    }

    if (CoreLocal::get("togglefoodstamp") != 0){
        CoreLocal::set("togglefoodstamp",0);
        $foodstamp = ($foodstamp==0) ? 1 : 0;
    }

    if (CoreLocal::get("toggleDiscountable") == 1) {
        CoreLocal::set("toggleDiscountable",0);
        $discount = ($discount == 0) ? 1 : 0;
    }

    return array($tax, $foodstamp, $discount);
}

}

