<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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
use COREPOS\pos\lib\LocalStorage\LaneCache;
use COREPOS\pos\lib\MiscLib;
use \CoreLocal;
use \Exception;

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    *  3Feb2015 Eric Lee New function logger(), anticipate change to uploadCC().
    * 27Feb2013 Andy Theuninck singleton connection for local databases
    * 13Jan2013 Eric Lee Added changeLttTaxCode(From, To);

*/

/**
  @class Database
  Functions related to the database
*/
class Database 
{

/***********************************************************************************************

 Functions transcribed from connect.asp on 07.13.03 by Brandon.

***********************************************************************************************/

/**
  Singleton connection
*/
static private $SQL_CONNECTION = null;

/**
  Connect to the transaction database (local)
  @return a SQLManager object
*/
static public function tDataConnect()
{
    return self::getLocalConnection(CoreLocal::get('tDatabase'), CoreLocal::get('pDatabase'));
}

/**
  Connect to the operational database (local)
  @return a SQLManager object
*/
static public function pDataConnect()
{
    return self::getLocalConnection(CoreLocal::get('pDatabase'), CoreLocal::get('tDatabase'));
}

static private function getLocalConnection($database1, $database2)
{
    if (self::$SQL_CONNECTION === null){
        /**
          Add both local databases to the connection object
        */
        self::$SQL_CONNECTION = new \COREPOS\pos\lib\SQLManager(
            CoreLocal::get("localhost"),
            CoreLocal::get("DBMS"),
            $database1,
            CoreLocal::get("localUser"),
            CoreLocal::get("localPass"),
            false);
        if (isset(self::$SQL_CONNECTION->connections[$database1])) {
            self::$SQL_CONNECTION->connections[$database2] = self::$SQL_CONNECTION->connections[$database1];
            if (CoreLocal::get('CoreCharSet') != '') {
                self::$SQL_CONNECTION->setCharSet(CoreLocal::get('CoreCharSet'), $database1);
            }
        }
    } else {
        /**
          Switch connection object to the requested database
        */
        self::$SQL_CONNECTION->selectDB($database1);
    }

    return self::$SQL_CONNECTION;
}

/**
  Connect to the remote server database
  @return a SQLManager object
*/
static public function mDataConnect()
{
    $sql = new \COREPOS\pos\lib\SQLManager(CoreLocal::get("mServer"),CoreLocal::get("mDBMS"),CoreLocal::get("mDatabase"),
                  CoreLocal::get("mUser"),CoreLocal::get("mPass"),false,true);
    if ($sql->isConnected(CoreLocal::get('mDatabase')) && CoreLocal::get('CoreCharSet') != '') {
        $sql->setCharSet(CoreLocal::get('CoreCharSet'), CoreLocal::get('mDatabase'));
    }

    return $sql;
}

/**
  Get the name of the primary server database
  This is only relevant in multi-store setups where the lane
  is shipping data to a temporary holding database to be
  relayed to the master HQ server later. Most operations can
  work off this holding database but a few may need to reference
  the some overarching, all-location data.

  @return database name w/ . separator or an empty string if
    no alternate is defined.
*/
static public function mAltName()
{
    $ret = CoreLocal::get('mAlternative');
    if ($ret) {
        return $ret . '.';
    }

    return '';
}

// ----------getsubtotals()----------

// getsubtotals() updates the values held in our session variables.

/**
  Load values from subtotals view into session.
  Essentially refreshes totals in the session.
*/
static public function getsubtotals() 
{
    $query = "SELECT * FROM subtotals";
    $connection = self::tDataConnect();
    $result = $connection->query($query);
    $row = $connection->fetchRow($result);

    // reset a few variables
    if (!$row || $row["LastID"] == 0) {
        CoreLocal::set("ttlflag",0);
        CoreLocal::set("fntlflag",0);
        self::setglobalflags(0);
    }

    // LastID => MAX(localtemptrans.trans_id) or zero table is empty
    CoreLocal::set("LastID", (!$row || !isset($row['LastID'])) ? 0 : (double)$row["LastID"] );
    // card_no => MAX(localtemptrans.card_no)
    $cardno = (!$row || !isset($row['card_no'])) ? "0" : trim($row["card_no"]);
    if ($cardno != "0" || CoreLocal::get("memberID") == "") {
        CoreLocal::set("memberID",$cardno);
    }
    // runningTotal => SUM(localtemptrans.total)
    CoreLocal::set("runningTotal", (!$row || !isset($row['runningTotal'])) ? 0 : (double)$row["runningTotal"] );
    // discountTTL => SUM(localtemptrans.total) where discounttype=1
    // probably not necessary
    CoreLocal::set("discounttotal", (!$row || !isset($row['discountTTL'])) ? 0 : (double)$row["discountTTL"] );
    // tenderTotal => SUM(localtemptrans.total) where trans_type=T
    CoreLocal::set("tenderTotal", (!$row || !isset($row['tenderTotal'])) ? 0 : (double)$row["tenderTotal"] );
    // memSpecial => SUM(localtemptrans.total) where discounttype=2,3
    CoreLocal::set("memSpecial", (!$row || !isset($row['memSpecial'])) ? 0 : (double)$row["memSpecial"] );
    // staffSpecial => SUM(localtemptrans.total) where discounttype=4
    CoreLocal::set("staffSpecial", (!$row || !isset($row['staffSpecial'])) ? 0 : (double)$row["staffSpecial"] );
    if (CoreLocal::get('member_subtotal') !== 0 && CoreLocal::get('member_subtotal') !== '0') {
        // percentDiscount => MAX(localtemptrans.percentDiscount)
        CoreLocal::set("percentDiscount", (!$row || !isset($row['percentDiscount'])) ? 0 : (double)$row["percentDiscount"] );
    }
    // transDiscount => lttsummary.discountableTTL * lttsummary.percentDiscount
    CoreLocal::set("transDiscount", (!$row || !isset($row['transDiscount'])) ? 0 : (double)$row["transDiscount"] );
    // foodstamp total net percentdiscount minus previous foodstamp tenders
    CoreLocal::set("fsEligible", (!$row || !isset($row['fsEligible'])) ? 0 : (double)$row["fsEligible"] );
    // chargeTotal => hardcoded to localtemptrans.trans_subtype MI or CX
    CoreLocal::set("chargeTotal", (!$row || !isset($row['chargeTotal'])) ? 0 : (double)$row["chargeTotal"] );
    // paymentTotal => hardcoded to localtemptrans.department = 990
    CoreLocal::set("paymentTotal", (!$row || !isset($row['paymentTotal'])) ? 0 : (double)$row["paymentTotal"] );
    CoreLocal::set("memChargeTotal", CoreLocal::get("chargeTotal") + CoreLocal::get("paymentTotal") );
    // discountableTotal => SUM(localtemptrans.total) where discountable > 0
    CoreLocal::set("discountableTotal", (!$row || !isset($row['discountableTotal'])) ? 0 : (double)$row["discountableTotal"] );
    // voidTotal => SUM(localtemptrans.total) where trans_status=V
    CoreLocal::set("voidTotal", (!$row || !isset($row['voidTotal'])) ? 0 : (double)$row["voidTotal"] );

    /**
      9May14 Andy
      I belive this query is equivalent to the
      old subtotals => lttsubtotals => lttsummary
      I've omitted tax since those are already calculated
      separately. A few conditions here should obviously
      be more configurable, but first I want to get
      rid of or simply the old nested views

      fsEligible is the complicated one. That's:
      1. Total foodstampable items
      2. Minus transaction-level discount on those items
      3. Minus any foodstamp tenders already applied.
         localtemptrans.total is negative on tenders
         so the query uses an addition sign but in 
         effect it's subracting.
    $replacementQ = "
        SELECT
            CASE WHEN MAX(trans_id) IS NULL THEN 0 ELSE MAX(trans_id) END AS LastID,
            MAX(card_no) AS card_no,
            SUM(total) AS runningTotal,
            SUM(CASE WHEN discounttype=1 THEN total ELSE 0 END) AS discountTTL,
            SUM(CASE WHEN discounttype IN (2,3) THEN total ELSE 0 END) AS staffSpecial,
            SUM(CASE WHEN discounttype=4 THEN total ELSE 0 END) AS discountTTL,
            SUM(CASE WHEN trans_type='T' THEN total ELSE 0 END) AS tenderTotal,
            MAX(percentDiscount) AS percentDiscount,
            SUM(CASE WHEN discountable=0 THEN 0 ELSE total END) as discountableTotal,
            SUM(CASE WHEN discountable=0 THEN 0 ELSE total END) * (MAX(percentDiscount)/100.00) AS transDiscount,
            SUM(CASE WHEN trans_subtype IN ('MI', 'CX') THEN total ELSE 0 END) AS chargeTotal,
            SUM(CASE WHEN department=990 THEN total ELSE 0 END) as paymentTotal,
            SUM(CASE WHEN trans_status='V' THEN total ELSE 0 END) as voidTotal,
            (
                SUM(CASE WHEN foodstamp=1 THEN total ELSE 0 END) 
                -
                ((MAX(percentDiscount)/100.00)
                * SUM(CASE WHEN foodstamp=1 AND discountable=1 THEN total ELSE 0 END))
                +
                SUM(CASE WHEN trans_subtype IN ('EF','FS') THEN total ELSE 0 END)
            ) AS fsEligble
        FROM localtemptrans AS l
        WHERE trans_type <> 'L'
    ";
    */

    /* ENABLED LIVE 15Aug2013
       Calculate taxes & exemptions separately from
       the subtotals view.

       Adding the exemption amount back on is a bit
       silly but the goal for the moment is to keep
       this function behaving the same. Once the subtotals
       view is deprecated we can revisit how these two
       session variables should behave.
    */
    $taxes = self::lineItemTaxes();
    $taxTTL = 0.00;
    $exemptTTL = 0.00;
    foreach($taxes as $tax) {
        $taxTTL += $tax['amount'];
        $exemptTTL += $tax['exempt'];
    }
    CoreLocal::set('taxTotal', number_format($taxTTL,2));
    CoreLocal::set('fsTaxExempt', number_format(-1*$exemptTTL,2));

    if (CoreLocal::get("TaxExempt") == 1) {
        CoreLocal::set("taxable",0);
        CoreLocal::set("taxTotal",0);
        CoreLocal::set("fsTaxable",0);
        CoreLocal::set("fsTaxExempt",0);
    }

    CoreLocal::set("subtotal",number_format(CoreLocal::get("runningTotal") - CoreLocal::get("transDiscount"), 2));
    /* using a string for amtdue behaves strangely for
     * values > 1000, so use floating point */
    CoreLocal::set("amtdue",(double)round(CoreLocal::get("runningTotal") - CoreLocal::get("transDiscount") + CoreLocal::get("taxTotal"), 2));

    /**
      If FS eligible amount is greater than the current transaction total
      and total is positive, limit the eligible amount to the current total.
      This may not be technically correct but the resulting change causes a lot
      of headaches depending what kind of change is allowed for earlier tenders,
      if change is allowed for those tenders at all.

      The other case is a refund to FS. Over-tendering on a refund doesn't make
      any sense.
    */
    if (CoreLocal::get("fsEligible") > CoreLocal::get("subtotal") && CoreLocal::get('subtotal') >= -0.005) {
        CoreLocal::set("fsEligible",CoreLocal::get("subtotal"));
    } elseif (CoreLocal::get("fsEligible") < CoreLocal::get("subtotal") && CoreLocal::get('subtotal') < 0) {
        CoreLocal::set("fsEligible",CoreLocal::get("subtotal"));
    }
}

/**
  Calculate taxes using new-style taxView.
  @return an array of records each containing:
    - id
    - description
    - amount (taxes actually due)
    - exempt (taxes exempted because of foodstamps) 
  There will always be one record for each existing tax rate.
*/
static public function lineItemTaxes()
{
    $dbc = self::tDataConnect();
    $taxQ = "SELECT id, description, taxTotal, fsTaxable, fsTaxTotal, foodstampTender, taxrate
        FROM taxView ORDER BY taxrate DESC";
    $taxR = $dbc->query($taxQ);
    $taxRows = array();
    $fsTenderTTL = 0.00;
    while ($row = $dbc->fetch_row($taxR)) {
        $row['fsExempt'] = 0.00;
        $taxRows[] = $row;
        $fsTenderTTL = $row['foodstampTender'];
    }

    // loop through line items and deal with
    // foodstamp tax exemptions
    for($i=0;$i<count($taxRows);$i++) {
        if (abs($fsTenderTTL) <= 0.005) {
            continue;
        }
        
        if (abs($fsTenderTTL - $taxRows[$i]['fsTaxable']) < 0.005) {
            // CASE 1:
            //    Available foodstamp tender matches foodstamp taxable total
            //    Decrement line item tax by foodstamp tax total
            //    No FS tender left, so exemption ends
            $taxRows[$i]['taxTotal'] = MiscLib::truncate2($taxRows[$i]['taxTotal'] - $taxRows[$i]['fsTaxTotal']);
            $taxRows[$i]['fsExempt'] = $taxRows[$i]['fsTaxTotal'];
            $fsTenderTTL = 0;
        } elseif ($fsTenderTTL > $taxRows[$i]['fsTaxable']){
            // CASE 2:
            //    Available foodstamp tender exeeds foodstamp taxable total
            //    Decrement line item tax by foodstamp tax total
            //    Decrement foodstamp tender total to reflect amount not yet applied
            $taxRows[$i]['taxTotal'] = MiscLib::truncate2($taxRows[$i]['taxTotal'] - $taxRows[$i]['fsTaxTotal']);
            $taxRows[$i]['fsExempt'] = $taxRows[$i]['fsTaxTotal'];
            $fsTenderTTL = MiscLib::truncate2($fsTenderTTL - $taxRows[$i]['fsTaxable']);;
        } else {
            // CASE 3:
            //    Available foodstamp tender is less than foodstamp taxable total
            //    Decrement line item tax proprotionally to foodstamp tender available
            //    No FS tender left, so exemption ends
            $percentageApplied = $fsTenderTTL / $taxRows[$i]['fsTaxable'];
            $exemption = MiscLib::truncate2($taxRows[$i]['fsTaxTotal'] * $percentageApplied);
            $taxRows[$i]['taxTotal'] = MiscLib::truncate2($taxRows[$i]['taxTotal'] - $exemption);
            $taxRows[$i]['fsExempt'] = $exemption;
            $fsTenderTTL = 0;
        }
    }
    
    $ret = array();
    foreach ($taxRows as $tr) {
        $ret[] = array(
            'rate_id' => $tr['id'],
            'description' => $tr['description'],
            'amount' => $tr['taxTotal'],
            'exempt' => $tr['fsExempt']
        );
    }
    return $ret;
}

/**
 Get the next transaction number for a given cashier
 @param $cashierNo cashier number (emp_no in tables) 
 @return integer transaction number
*/
static public function gettransno($cashierNo) 
{
    $connection = self::tDataConnect();
    $registerNo = CoreLocal::get("laneno");
    $query = "SELECT max(trans_no) as maxtransno from localtranstoday where emp_no = "
        .((int)$cashierNo)." and register_no = "
        .((int)$registerNo).' AND datetime >= ' . $connection->curdate();
    $result = $connection->query($query);
    $row = $connection->fetchRow($result);
    if (!$row || !$row["maxtransno"]) {
        $transNo = 1;
        // automatically trim the relevant table
        // on some installs localtranstoday might be
        // a view pointed at localtrans_today
        $cleanQ = 'DELETE FROM localtranstoday WHERE datetime < ' . $connection->curdate();
        if (CoreLocal::get('NoCompat') != 1 && $connection->isView('localtranstoday')) {
            $cleanQ = str_replace('localtranstoday', 'localtrans_today', $cleanQ);
        }
        $connection->query($cleanQ);
    } else {
        $transNo = $row["maxtransno"] + 1;
    }

    return $transNo;
}

/**
  See if the remote database is available
  This function calls uploadtoServer() if
  the initial test works.
  @return integer 
   - 1 server available
   - 0 server down
*/
static public function testremote() 
{
    $intConnected = MiscLib::pingport(CoreLocal::get("mServer"), CoreLocal::get("mDBMS"));
    if ($intConnected == 1) {

        self::uploadtoServer(); 

    } else {
        CoreLocal::set("standalone",1);
    }

    return (CoreLocal::get("standalone") + 1) % 2;
}

/**
  Copy tables from the lane to the remote server
  The following tables are copied:
   - dtransactions
   - suspended
   - PaycardTransactions
   - CapturedSignature

  On success the local tables are truncated. The Paycards tables
  are copied in the uploadCCdata() function but that gets called 
  automatically.

  @return
   - 1 upload succeeded
   - 0 upload failed
*/
static private function uploadtoServer()
{
    $uploaded = 0;
    CoreLocal::set("standalone",1);

    // new upload method makes use of SQLManager's transfer method
    // to simulate cross-server queries
    $connect = self::tDataConnect();
    $connect->addConnection(CoreLocal::get("mServer"),
                CoreLocal::get("mDBMS"),
                CoreLocal::get("mDatabase"),
                CoreLocal::get("mUser"),
                CoreLocal::get("mPass"),
                False);
    if (!isset($connect->connections[CoreLocal::get("mDatabase")]) ||
        $connect->connections[CoreLocal::get("mDatabase")] === False){
        CoreLocal::set("standalone",1);
        return 0;    
    } elseif (CoreLocal::get('CoreCharSet') != '') {
        $connect->setCharSet(CoreLocal::get('CoreCharSet'), CoreLocal::get('mDatabase'));
    }

    $dtMatches = self::getMatchingColumns($connect,"dtransactions");

    if ($connect->transfer(CoreLocal::get("tDatabase"),
        "select {$dtMatches} from dtransactions",
        CoreLocal::get("mDatabase"),"insert into dtransactions ({$dtMatches})")) {
    
        // Moved up
        // DO NOT TRUNCATE; that resets AUTO_INCREMENT
        $connect->query("DELETE FROM dtransactions",
            CoreLocal::get("tDatabase"));

        $suMatches = self::getMatchingColumns($connect,"suspended");
        $suSuccess = $connect->transfer(CoreLocal::get("tDatabase"),
            "select {$suMatches} from suspended",
            CoreLocal::get("mDatabase"),
            "insert into suspended ({$suMatches})");

        if ($suSuccess) {
            $connect->query("truncate table suspended",
                CoreLocal::get("tDatabase"));
            $uploaded = 1;
            CoreLocal::set("standalone",0);
        }
    }

    if (!self::uploadCCdata()) {
        $uploaded = 0;
        CoreLocal::set("standalone",1);
    }

    return $uploaded;
}

/** 
   Get a list of columns that exist on the local db
   and the server db for the given table.
   @param $connection a SQLManager object that's
    already connected
   @param $tableName the table
   @param $table2 is provided, it match columns from
    local.tableName against remote.table2
   @return [string] comma separated list of column names
*/
    // @hintable
static public function getMatchingColumns($connection,$tableName,$table2="")
{
    /**
      Cache column information by table in the session
      In standalone mode, a transfer query likely failed
      and the cache may be wrong so always requery in
      that case.
    */
    $cacheItem = LaneCache::get('MatchingColumnCache');
    $cache = $cacheItem->get();
    if (!is_array($cache)) {
        $cache = array();
    }
    if (isset($cache[$tableName]) && CoreLocal::get('standalone') == 0) {
        return $cache[$tableName];
    }

    $localPoll = $connection->tableDefinition($tableName,CoreLocal::get("tDatabase"));
    if ($localPoll === false) {
        return '';
    }
    $localCols = array();
    foreach($localPoll as $name=>$v) {
        $localCols[$name] = true;
    }
    $remotePoll = $connection->tableDefinition((!empty($table2)?$table2:$tableName),
                CoreLocal::get("mDatabase"));
    if ($remotePoll === false) {
        return '';
    }
    $matchingCols = array();
    foreach($remotePoll as $name=>$v) {
        if (isset($localCols[$name])) {
            $matchingCols[] = $name;
        }
    }

    $ret = "";
    foreach($matchingCols as $col) {
        $ret .= $col.",";
    }
    $ret = rtrim($ret,",");

    $cache[$tableName] = $ret;
    $cacheItem->set($cache);
    LaneCache::set($cacheItem);

    return $ret;
}

/** Get a list of columns in both tables.
   @param $connection a SQLManager object that's
    already connected
   @param $table1 a database table
   @param $table2 a database table
   @return [string] comma separated list of column names
 */
    // @hintable
static public function localMatchingColumns($connection,$table1,$table2)
{
    $poll1 = $connection->tableDefinition($table1);
    $cols1 = array();
    foreach($poll1 as $name=>$v) {
        $cols1[$name] = true;
    }
    $poll2 = $connection->tableDefinition($table2);
    $matchingCols = array();
    foreach($poll2 as $name=>$v) {
        if (isset($cols1[$name])) {
            $matchingCols[] = $name;
        }
    }

    $ret = "";
    foreach($matchingCols as $col) {
        $ret .= $col.",";
    }

    return rtrim($ret,",");
}

/**
  Transfer credit card tables to the server.
  See uploadtoServer().

  @return boolean success / failure
*/
static private function uploadCCdata()
{
    if (!in_array("Paycards",CoreLocal::get("PluginList"))) {
        // plugin not enabled; nothing to upload
        return true;
    }

    $sql = self::tDataConnect();
    $sql->addConnection(CoreLocal::get("mServer"),
                CoreLocal::get("mDBMS"),
                CoreLocal::get("mDatabase"),
                CoreLocal::get("mUser"),
                CoreLocal::get("mPass"),
                False);
    if (CoreLocal::get('CoreCharSet') != '') {
        $sql->setCharSet(CoreLocal::get('CoreCharSet'), CoreLocal::get('mDatabase'));
    }

    // test for success
    $ret = true;

    $tables = array('PaycardTransactions', 'CapturedSignature');
    foreach ($tables as $table) {
        if (CoreLocal::get('NoCompat') == 1 || $sql->tableExists($table)) {
            $cols = self::getMatchingColumns($sql, $table);
            $success = $sql->transfer(CoreLocal::get('tDatabase'),
                "SELECT {$cols} FROM {$table}",
                CoreLocal::get('mDatabase'),
                "INSERT INTO {$table} ({$cols})"
            );
            if ($success) {
                $sql->query('DELETE FROM ' . $table, CoreLocal::get('tDatabase'));
            }
            $ret = $ret & $success;
        }
    }

    return $ret;
}

/**
  Read globalvalues settings into session.
*/
static public function loadglobalvalues() 
{
    $query = "select CashierNo,Cashier,LoggedIn,TransNo,TTLFlag,
        FntlFlag,TaxExempt from globalvalues";
    $dbc = self::pDataConnect();
    $result = $dbc->query($query);
    $row = $dbc->fetchRow($result);

    CoreLocal::set("CashierNo",$row["CashierNo"]);
    CoreLocal::set("cashier",$row["Cashier"]);
    CoreLocal::set("LoggedIn",$row["LoggedIn"]);
    CoreLocal::set("transno",$row["TransNo"]);
    CoreLocal::set("ttlflag",$row["TTLFlag"]);
    CoreLocal::set("fntlflag",$row["FntlFlag"]);
    CoreLocal::set("TaxExempt",$row["TaxExempt"]);
}

/**
  Set new value in session.
  @param $param keycode
  @param $val new value
*/
static private function loadglobalvalue($param,$val)
{
    switch (strtoupper($param)) {
        case 'CASHIERNO':
            CoreLocal::set("CashierNo",$val);    
            break;
        case 'CASHIER':
            CoreLocal::set("cashier",$val);
            break;
        case 'LOGGEDIN':
            CoreLocal::set("LoggedIn",$val);
            break;
        case 'TRANSNO':
            CoreLocal::set("transno",$val);
            break;
        case 'TTLFLAG':
            CoreLocal::set("ttlflag",$val);
            break;
        case 'FNTLFLAG':
            CoreLocal::set("fntlflag",$val);
            break;
        case 'TAXEXEMPT':
            CoreLocal::set("TaxExempt",$val);
            break;
    }
}

/**
  Update setting in globalvalues table.
  @param $param keycode
  @param $value new value
*/
static public function setglobalvalue($param, $value) 
{
    $dbc = self::pDataConnect();
    
    if (!is_numeric($value)) {
        $value = "'".$value."'";
    }
    
    $strUpdate = "update globalvalues set ".$param." = ".$value;

    $dbc->query($strUpdate);
}

/**
  Update many settings in globalvalues table
  and in session
  @param $arr An array of keys and values
*/
static public function setglobalvalues(array $arr)
{
    $setStr = "";
    foreach($arr as $param => $value) {
        $setStr .= $param." = ";
        $setStr .= !is_numeric($value) ? "'{$value}'," : $value . ',';
        self::loadglobalvalue($param,$value);
    }
    $setStr = rtrim($setStr,",");

    $dbc = self::pDataConnect();
    $upQ = "UPDATE globalvalues SET ".$setStr;
    $dbc->query($upQ);
}

/**
  Sets TTLFlag and FntlFlag in globalvalues table
  @param $value value for both fields.
*/
static public function setglobalflags($value) 
{
    $dbc = self::pDataConnect();

    $dbc->query("update globalvalues set TTLFlag = ".$value.", FntlFlag = ".$value);
}

static private function getTaxByName($name)
{
    $dbc = self::tDataConnect();

    // Get the codes for the names provided.
    $query = "SELECT id FROM taxrates WHERE description = '$name'";
    $result = $dbc->query($query);
    $row = $dbc->fetch_row($result);
    if ($row) {
        return $row['id'];
    }

    throw new Exception('name: >' . $name . '< not known.');
}

/**
  Change one tax code in all items of localtemptrans to a different one.
  Parameters are the names of the taxes, as in taxrates.description
  @param $fromName The name of the tax changed from.
  @param $fromName The name of the tax changed to.
*/
static public function changeLttTaxCode($fromName, $toName) 
{
    $pfx = "changeLttTaxCode ";
    $pfx = "";
    if ( $fromName == "" ) {
        return "{$pfx}fromName is empty";
    } elseif ( $toName == "" ) {
        return "{$pfx}toName is empty";
    }

    $dbc = self::tDataConnect();

    // Get the codes for the names provided.
    try {
        $fromId = self::getTaxByName($fromName);
        $toId = self::getTaxByName($toName);
    } catch (Exception $ex) {
        return $pfx . $ex->getMessage();
    }

    // Change the values.
    $query = "UPDATE localtemptrans set tax = $toId WHERE tax = $fromId";
    $result = $dbc->query($query);
    if ( !$result ) {
        return "UPDATE false";
    }

    return true;

// changeLttTaxCode
}

/**
  Rotate current transaction data
  Current data in translog.localtemptrans is inserted into:
  - translog.dtransactions
  - translog.localtrans
  - translog.localtranstoday (if not a view)
  - translog.localtrans_today (if present)

  @return [boolean] success or failure

  Success or failure is based on whether or not
  the insert into translog.dtransactions succeeds. That's
  the most important query in terms of ensuring data
  flows properly to the server.
*/
static public function rotateTempData()
{
    $connection = Database::tDataConnect();

    // LEGACY.
    // these records should be written correctly from the start
    // could go away with verification of above.
    $connection->query("update localtemptrans set trans_type = 'T' where trans_subtype IN ('CP','IC')");

    $connection->query("insert into localtrans select * from localtemptrans");
    // localtranstoday converted from view to table
    if (CoreLocal::get('NoCompat') == 1 || !$connection->isView('localtranstoday')) {
        $connection->query("insert into localtranstoday select * from localtemptrans");
    }
    // legacy table when localtranstoday is still a view
    if (CoreLocal::get('NoCompat') != 1 && $connection->table_exists('localtrans_today')) {
        $connection->query("insert into localtrans_today select * from localtemptrans");
    }

    $cols = self::localMatchingColumns($connection, 'dtransactions', 'localtemptrans');
    $ret = $connection->query("insert into dtransactions ($cols) select $cols from localtemptrans");

    /**
      If store_id column is present in lane dtransactions table
      and the lane's store_id has been configured, assign that
      value to the column. Otherwise it may be handled but some
      other mechanism such as triggers or column default values.
    */
    $tableDef = $connection->tableDefinition('dtransactions');
    if (isset($tableDef['store_id']) && CoreLocal::get('store_id') !== '') {
        $assignQ = sprintf('
            UPDATE dtransactions
            SET store_id = %d',
            CoreLocal::get('store_id')
        );
        $connection->query($assignQ);
    }

    return ($ret) ? true : false;
}

/**
  Truncate current transaction tables.
  Clears data from:
  - translog.localtemptrans
  - translog.couponApplied
  
  @return [boolean] success or failure 

  Success or failure is based on whether 
  translog.localtemptrans is cleared correctly.
*/
static public function clearTempTables()
{
    $connection = Database::tDataConnect();

    $query1 = "truncate table localtemptrans";
    $ret = $connection->query($query1);

    $query2 = "truncate table couponApplied";
    $connection->query($query2);

    return ($ret) ? true : false;
}

/**
  Log a message to the lane log
  @param $msg A string containing the message to log.
  @return True on success, False on failure 
 */
static public function logger($msg="")
{
    $connection = self::tDataConnect();

    $ret = false;
    if (method_exists($connection, 'logger')) {
        $ret = $connection->logger($msg);
    }

    return $ret;
}

} // end Database class

