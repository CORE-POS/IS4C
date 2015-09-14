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

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    *  3Feb2015 Eric Lee New function logger(), anticipate change to uploadCC().
    * 27Feb2013 Andy Theuninck singleton connection for local databases
    * 13Jan2013 Eric Lee Added changeLttTaxCode(From, To);

*/

/**
  @class Database
  Functions related to the database
*/
class Database extends LibraryClass {


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
    if (self::$SQL_CONNECTION === null){
        /**
          Add both local databases to the connection object
        */
        self::$SQL_CONNECTION = new SQLManager(
            CoreLocal::get("localhost"),
            CoreLocal::get("DBMS"),
            CoreLocal::get("tDatabase"),
            CoreLocal::get("localUser"),
            CoreLocal::get("localPass"),
            false);
        self::$SQL_CONNECTION->connections[CoreLocal::get('pDatabase')] = self::$SQL_CONNECTION->connections[CoreLocal::get('tDatabase')];
        /**
          19Mar2015
          Temporary measure to support failback
          using old, non-adodb SQLManager
        */
        if (property_exists(self::$SQL_CONNECTION, 'db_types')) {
            self::$SQL_CONNECTION->db_types[CoreLocal::get('pDatabase')] = strtoupper(CoreLocal::get('DBMS'));
        }
    } else {
        /**
          Switch connection object to the requested database
        */
        self::$SQL_CONNECTION->selectDB(CoreLocal::get('tDatabase'));
    }    

    return self::$SQL_CONNECTION;
}

/**
  Connect to the operational database (local)
  @return a SQLManager object
*/
static public function pDataConnect()
{
    if (self::$SQL_CONNECTION === null){
        /**
          Add both local databases to the connection object
        */
        self::$SQL_CONNECTION = new SQLManager(
            CoreLocal::get("localhost"),
            CoreLocal::get("DBMS"),
            CoreLocal::get("pDatabase"),
            CoreLocal::get("localUser"),
            CoreLocal::get("localPass"),
            false);
        self::$SQL_CONNECTION->connections[CoreLocal::get('tDatabase')] = self::$SQL_CONNECTION->connections[CoreLocal::get('pDatabase')];
        /**
          19Mar2015
          Temporary measure to support failback
          using old, non-adodb SQLManager
        */
        if (property_exists(self::$SQL_CONNECTION, 'db_types')) {
            self::$SQL_CONNECTION->db_types[CoreLocal::get('tDatabase')] = strtoupper(CoreLocal::get('DBMS'));
        }
    } else {
        /**
          Switch connection object to the requested database
        */
        self::$SQL_CONNECTION->selectDB(CoreLocal::get('pDatabase'));
    }

    return self::$SQL_CONNECTION;
}

/**
  Connect to the remote server database
  @return a SQLManager object
*/
static public function mDataConnect()
{
    $sql = new SQLManager(CoreLocal::get("mServer"),CoreLocal::get("mDBMS"),CoreLocal::get("mDatabase"),
                  CoreLocal::get("mUser"),CoreLocal::get("mPass"),false,true);

    return $sql;
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
    $row = $connection->fetch_array($result);

    // reset a few variables
    if (!$row || $row["LastID"] == 0) {
        CoreLocal::set("ttlflag",0);
        CoreLocal::set("fntlflag",0);
        self::setglobalflags(0);
    }

    // LastID => MAX(localtemptrans.trans_id) or zero table is empty
    CoreLocal::set("LastID", (!$row || !isset($row['LastID'])) ? 0 : (double)$row["LastID"] );
    // card_no => MAX(localtemptrans.card_no)
    $cn = (!$row || !isset($row['card_no'])) ? "0" : trim($row["card_no"]);
    if ($cn != "0" || CoreLocal::get("memberID") == "") {
        CoreLocal::set("memberID",$cn);
    }
    // runningTotal => SUM(localtemptrans.total)
    CoreLocal::set("runningTotal", (!$row || !isset($row['runningTotal'])) ? 0 : (double)$row["runningTotal"] );
    // complicated, but replaced by taxView & LineItemTaxes() method
    CoreLocal::set("taxTotal", (!$row || !isset($row['taxTotal'])) ? 0 : (double)$row["taxTotal"] );
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
    // complicated, but replaced by taxView & LineItemTaxes() method
    CoreLocal::set("fsTaxExempt", (!$row || !isset($row['fsTaxExempt'])) ? 0 : (double)$row["fsTaxExempt"] );
    // foodstamp total net percentdiscount minus previous foodstamp tenders
    CoreLocal::set("fsEligible", (!$row || !isset($row['fsEligible'])) ? 0 : (double)$row["fsEligible"] );
    // chargeTotal => hardcoded to localtemptrans.trans_subtype MI or CX
    CoreLocal::set("chargeTotal", (!$row || !isset($row['chargeTotal'])) ? 0 : (double)$row["chargeTotal"] );
    // paymentTotal => hardcoded to localtemptrans.department = 990
    CoreLocal::set("paymentTotal", (!$row || !isset($row['paymentTotal'])) ? 0 : (double)$row["paymentTotal"] );
    CoreLocal::set("memChargeTotal", CoreLocal::get("chargeTotal") + CoreLocal::get("paymentTotal") );
    // discountableTotal => SUM(localtemptrans.total) where discountable > 0
    CoreLocal::set("discountableTotal", (!$row || !isset($row['discountableTotal'])) ? 0 : (double)$row["discountableTotal"] );
    // localTotal => SUM(localtemptrans.total) where numflag=1
    CoreLocal::set("localTotal", (!$row || !isset($row['localTotal'])) ? 0 : (double)$row["localTotal"] );
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
    */
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
            SUM(CASE WHEN numflag=1 THEN total ELSE 0 END) as localTotal,
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

    /* ENABLED LIVE 15Aug2013
       Calculate taxes & exemptions separately from
       the subtotals view.

       Adding the exemption amount back on is a bit
       silly but the goal for the moment is to keep
       this function behaving the same. Once the subtotals
       view is deprecated we can revisit how these two
       session variables should behave.
    */
    $taxes = Database::LineItemTaxes();
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
static public function LineItemTaxes()
{
    $db = Database::tDataConnect();
    $q = "SELECT id, description, taxTotal, fsTaxable, fsTaxTotal, foodstampTender, taxrate
        FROM taxView ORDER BY taxrate DESC";
    $r = $db->query($q);
    $taxRows = array();
    $fsTenderTTL = 0.00;
    while ($w = $db->fetch_row($r)) {
        $w['fsExempt'] = 0.00;
        $taxRows[] = $w;
        $fsTenderTTL = $w['foodstampTender'];
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
        } else if ($fsTenderTTL > $taxRows[$i]['fsTaxable']){
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
    foreach($taxRows as $tr) {
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
 @param $CashierNo cashier number (emp_no in tables) 
 @return integer transaction number
*/
static public function gettransno($CashierNo) 
{
    $connection = self::tDataConnect();
    $database = CoreLocal::get("tDatabase");
    $register_no = CoreLocal::get("laneno");
    $query = "SELECT max(trans_no) as maxtransno from localtranstoday where emp_no = "
        .((int)$CashierNo)." and register_no = "
        .((int)$register_no).' AND datetime >= ' . $connection->curdate();
    $result = $connection->query($query);
    $row = $connection->fetch_array($result);
    if (!$row || !$row["maxtransno"]) {
        $trans_no = 1;
        // automatically trim the relevant table
        // on some installs localtranstoday might be
        // a view pointed at localtrans_today
        $cleanQ = 'DELETE FROM localtranstoday WHERE datetime < ' . $connection->curdate();
        if ($connection->isView('localtranstoday')) {
            $cleanQ = str_replace('localtranstoday', 'localtrans_today', $cleanQ);
        }
        $connection->query($cleanQ);
    } else {
        $trans_no = $row["maxtransno"] + 1;
    }

    return $trans_no;
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
   - efsnetRequest
   - efsnetResponse
   - efsnetRequestMod
   - efsnetTokens
   - CapturedSignature

  On success the local tables are truncated. The efsnet tables
  are copied in the uploadCCdata() function but that gets called 
  automatically.

  @return
   - 1 upload succeeded
   - 0 upload failed
*/
static public function uploadtoServer()
{
    $uploaded = 0;

    // new upload method makes use of SQLManager's transfer method
    // to simulate cross-server queries
    $connect = self::tDataConnect();
    $connect->add_connection(CoreLocal::get("mServer"),
                CoreLocal::get("mDBMS"),
                CoreLocal::get("mDatabase"),
                CoreLocal::get("mUser"),
                CoreLocal::get("mPass"),
                False);
    if (!isset($connect->connections[CoreLocal::get("mDatabase")]) ||
        $connect->connections[CoreLocal::get("mDatabase")] === False){
        CoreLocal::set("standalone",1);
        return 0;    
    }

    $dt_matches = self::getMatchingColumns($connect,"dtransactions");

    if ($connect->transfer(CoreLocal::get("tDatabase"),
        "select {$dt_matches} from dtransactions",
        CoreLocal::get("mDatabase"),"insert into dtransactions ({$dt_matches})")) {
    
        // Moved up
        // DO NOT TRUNCATE; that resets AUTO_INCREMENT
        $connect->query("DELETE FROM dtransactions",
            CoreLocal::get("tDatabase"));

        $su_matches = self::getMatchingColumns($connect,"suspended");
        $su_success = $connect->transfer(CoreLocal::get("tDatabase"),
            "select {$su_matches} from suspended",
            CoreLocal::get("mDatabase"),
            "insert into suspended ({$su_matches})");

        if ($su_success) {
            $connect->query("truncate table suspended",
                CoreLocal::get("tDatabase"));
            $uploaded = 1;
            CoreLocal::set("standalone",0);
        } else {
            $uploaded = 0;
            CoreLocal::set("standalone",1);
        }

    } else {
        $uploaded = 0;
        CoreLocal::set("standalone",1);
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
   @param $table_name the table
   @param $table2 is provided, it match columns from
    local.table_name against remote.table2
   @return [string] comma separated list of column names
*/
static public function getMatchingColumns($connection,$table_name,$table2="")
{
    /**
      Cache column information by table in the session
      In standalone mode, a transfer query likely failed
      and the cache may be wrong so always requery in
      that case.
    */
    $cache = CoreLocal::get('MatchingColumnCache');
    if (!is_array($cache)) {
        $cache = array();
    }
    if (isset($cache[$table_name]) && CoreLocal::get('standalone') == 0) {
        return $cache[$table_name];
    }

    $local_poll = $connection->table_definition($table_name,CoreLocal::get("tDatabase"));
    if ($local_poll === false) {
        return '';
    }
    $local_cols = array();
    foreach($local_poll as $name=>$v) {
        $local_cols[$name] = true;
    }
    $remote_poll = $connection->table_definition((!empty($table2)?$table2:$table_name),
                CoreLocal::get("mDatabase"));
    if ($remote_poll === false) {
        return '';
    }
    $matching_cols = array();
    foreach($remote_poll as $name=>$v) {
        if (isset($local_cols[$name])) {
            $matching_cols[] = $name;
        }
    }

    $ret = "";
    foreach($matching_cols as $col) {
        $ret .= $col.",";
    }
    $ret = rtrim($ret,",");
    $cache[$table_name] = $ret;
    CoreLocal::set('MatchingColumnCache', $cache);

    return $ret;
}

/** Get a list of columns in both tables.
   @param $connection a SQLManager object that's
    already connected
   @param $table1 a database table
   @param $table2 a database table
   @return [string] comma separated list of column names
 */
static public function localMatchingColumns($connection,$table1,$table2)
{
    $poll1 = $connection->table_definition($table1);
    $cols1 = array();
    foreach($poll1 as $name=>$v) {
        $cols1[$name] = true;
    }
    $poll2 = $connection->table_definition($table2);
    $matching_cols = array();
    foreach($poll2 as $name=>$v) {
        if (isset($cols1[$name])) {
            $matching_cols[] = $name;
        }
    }

    $ret = "";
    foreach($matching_cols as $col) {
        $ret .= $col.",";
    }

    return rtrim($ret,",");
}

/**
  Transfer credit card tables to the server.
  See uploadtoServer().

  @return boolean success / failure
*/
static public function uploadCCdata()
{
    if (!in_array("Paycards",CoreLocal::get("PluginList"))) {
        // plugin not enabled; nothing to upload
        return true;
    }

    $sql = self::tDataConnect();
    $sql->add_connection(CoreLocal::get("mServer"),
                CoreLocal::get("mDBMS"),
                CoreLocal::get("mDatabase"),
                CoreLocal::get("mUser"),
                CoreLocal::get("mPass"),
                False);

    // test for success
    $ret = true;

    $req_cols = self::getMatchingColumns($sql,"efsnetRequest");
    if ($sql->transfer(CoreLocal::get("tDatabase"),
        "select {$req_cols} from efsnetRequest",
        CoreLocal::get("mDatabase"),"insert into efsnetRequest ({$req_cols})")) {

        // table contains an autoincrementing column
        // do not TRUNCATE; that would reset the counter
        $sql->query("DELETE FROM efsnetRequest",
            CoreLocal::get("tDatabase"));

        $res_cols = self::getMatchingColumns($sql,"efsnetResponse");
        $res_success = $sql->transfer(CoreLocal::get("tDatabase"),
            "select {$res_cols} from efsnetResponse",
            CoreLocal::get("mDatabase"),
            "insert into efsnetResponse ({$res_cols})");
        if ($res_success) {
            $sql->query("truncate table efsnetResponse",
                CoreLocal::get("tDatabase"));
        } else {
            // transfer failure
            $ret = false;
        }

        $mod_cols = self::getMatchingColumns($sql,"efsnetRequestMod");
        $mod_success = $sql->transfer(CoreLocal::get("tDatabase"),
            "select {$mod_cols} from efsnetRequestMod",
            CoreLocal::get("mDatabase"),
            "insert into efsnetRequestMod ({$mod_cols})");
        if ($mod_success) {
            $sql->query("truncate table efsnetRequestMod",
                CoreLocal::get("tDatabase"));
        } else {
            // transfer failure
            $ret = false;
        }

        $mod_cols = self::getMatchingColumns($sql,"efsnetTokens");
        $mod_success = $sql->transfer(CoreLocal::get("tDatabase"),
            "select {$mod_cols} from efsnetTokens",
            CoreLocal::get("mDatabase"),
            "insert into efsnetTokens ({$mod_cols})");
        if ($mod_success) {
            $sql->query("truncate table efsnetTokens",
                CoreLocal::get("tDatabase"));
        } else {
            // transfer failure
            $ret = false;
        }

    } else if (!$sql->table_exists('efsnetRequest')) {
        // if for whatever reason the table does not exist,
        // it's not necessary to treat this as a failure.
        // if integrated card processing is not in use,
        // this is not an important enough error to go
        // to standalone. 
        $ret = true;
    }

    if ($sql->table_exists('CapturedSignature')) {
        $sig_cols = self::getMatchingColumns($sql, 'CapturedSignature');
        $sig_success = $sql->transfer(CoreLocal::get("tDatabase"),
            "select {$sig_cols} from CapturedSignature",
            CoreLocal::get("mDatabase"),
            "insert into CapturedSignature ({$sig_cols})");
        if ($sig_success) {
            $sql->query("truncate table CapturedSignature",
                CoreLocal::get("tDatabase"));
        } else {
            // transfer failure
            $ret = false;
        }
    }

    // newer paycard transactions table
    if ($sql->table_exists('PaycardTransactions')) {
        $ptrans_cols = self::getMatchingColumns($sql, 'PaycardTransactions');
        $ptrans_success = $sql->transfer(CoreLocal::get('tDatabase'),
                                         "SELECT {$ptrans_cols} FROM PaycardTransactions",
                                         CoreLocal::get('mDatabase'),
                                         "INSERT INTO PaycardTransactions ($ptrans_cols)"
        );
        if ($ptrans_success) {
            $sql->query('DELETE FROM PaycardTransactions', CoreLocal::get('tDatabase'));
        } else {
            // transfer failure
            $ret = false;
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
    $db = self::pDataConnect();
    $result = $db->query($query);
    $row = $db->fetch_array($result);

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
static public function loadglobalvalue($param,$val)
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
    $db = self::pDataConnect();
    
    if (!is_numeric($value)) {
        $value = "'".$value."'";
    }
    
    $strUpdate = "update globalvalues set ".$param." = ".$value;

    $db->query($strUpdate);
}

/**
  Update many settings in globalvalues table
  and in session
  @param $arr An array of keys and values
*/
static public function setglobalvalues($arr)
{
    $setStr = "";
    foreach($arr as $param => $value) {
        $setStr .= $param." = ";
        if (!is_numeric($value)) {
            $setStr .= "'".$value."',";
        } else {
            $setStr .= $value.",";
        }
        self::loadglobalvalue($param,$value);
    }
    $setStr = rtrim($setStr,",");

    $db = self::pDataConnect();
    $upQ = "UPDATE globalvalues SET ".$setStr;
    $db->query($upQ);
}

/**
  Sets TTLFlag and FntlFlag in globalvalues table
  @param $value value for both fields.
*/
static public function setglobalflags($value) 
{
    $db = self::pDataConnect();

    $db->query("update globalvalues set TTLFlag = ".$value.", FntlFlag = ".$value);
}

/**
  Change one tax code in all items of localtemptrans to a different one.
  Parameters are the names of the taxes, as in taxrates.description
  @param $fromName The name of the tax changed from.
  @param $fromName The name of the tax changed to.
*/
static public function changeLttTaxCode($fromName, $toName) 
{

    $msg = "";
    $pfx = "changeLttTaxCode ";
    $pfx = "";
    if ( $fromName == "" ) {
        $msg = "{$pfx}fromName is empty";
        return $msg;
    } else {
        if ( $toName == "" ) {
            $msg = "{$pfx}toName is empty";
            return $msg;
        }
    }

    $db = self::tDataConnect();

    // Get the codes for the names provided.
    $query = "SELECT id FROM taxrates WHERE description = '$fromName'";
    $result = $db->query($query);
    $row = $db->fetch_row($result);
    if ( $row ) {
        $fromId = $row['id'];
    } else {
        $msg = "{$pfx}fromName: >{$fromName}< not known.";
        return $msg;
    }
    $query = "SELECT id FROM taxrates WHERE description = '$toName'";
    $result = $db->query($query);
    $row = $db->fetch_row($result);
    if ( $row ) {
        $toId = $row['id'];
    } else {
        $msg = "{$pfx}toName: >{$toName}< not known.";
        return $msg;
    }

    // Change the values.
    $query = "UPDATE localtemptrans set tax = $toId WHERE tax = $fromId";
    $result = $db->query($query);
    /* Complains that errno is undefined in SQLManager.
    if ( $db->errno ) {
        return "{$pfx}UPDATE error: " . $db->error;
    }
    */
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
    if (!$connection->isView('localtranstoday')) {
        $connection->query("insert into localtranstoday select * from localtemptrans");
    }
    // legacy table when localtranstoday is still a view
    if ($connection->table_exists('localtrans_today')) {
        $connection->query("insert into localtrans_today select * from localtemptrans");
    }

    $cols = Database::localMatchingColumns($connection, 'dtransactions', 'localtemptrans');
    $ret = $connection->query("insert into dtransactions ($cols) select $cols from localtemptrans");

    /**
      If store_id column is present in lane dtransactions table
      and the lane's store_id has been configured, assign that
      value to the column. Otherwise it may be handled but some
      other mechanism such as triggers or column default values.
    */
    $table_def = $connection->table_definition('dtransactions');
    if (isset($table_def['store_id']) && CoreLocal::get('store_id') !== '') {
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
    $connection = Database::tDataConnect();

    if (method_exists($connection, 'logger')) {
        $ret = $connection->logger($msg);
    } else {
        $ret = False;
    }
    return $ret;
}

} // end Database class

