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
  @file
  @brief Functions related to the database
  @deprecated see Database
*/

$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }

if (!class_exists("SQLManager")) include($CORE_PATH."lib/SQLManager.php");

if (!function_exists("setglobalflags")) include($CORE_PATH."lib/loadconfig.php");
if (!function_exists("pingport")) include($CORE_PATH."lib/lib.php");
if (!function_exists("wmdiscount")) include($CORE_PATH."lib/prehkeys.php");
if (!isset($CORE_LOCAL)) include($CORE_PATH."lib/LocalStorage/conf.php");


/***********************************************************************************************

 Functions transcribed from connect.asp on 07.13.03 by Brandon.

***********************************************************************************************/

/**
  Connect to the transaction database (local)
  @return a SQLManager object
*/
function tDataConnect()
{
	global $CORE_LOCAL;

	$sql = new SQLManager($CORE_LOCAL->get("localhost"),$CORE_LOCAL->get("DBMS"),$CORE_LOCAL->get("tDatabase"),
			      $CORE_LOCAL->get("localUser"),$CORE_LOCAL->get("localPass"),False);
	return $sql;
}

/**
  Connect to the operational database (local)
  @return a SQLManager object
*/
function pDataConnect()
{
	global $CORE_LOCAL;

	$sql = new SQLManager($CORE_LOCAL->get("localhost"),$CORE_LOCAL->get("DBMS"),$CORE_LOCAL->get("pDatabase"),
			      $CORE_LOCAL->get("localUser"),$CORE_LOCAL->get("localPass"),False);
	return $sql;
}

/**
  Connect to the remote server database
  @return a SQLManager object
*/
function mDataConnect()
{
	global $CORE_LOCAL;

	$sql = new SQLManager($CORE_LOCAL->get("mServer"),$CORE_LOCAL->get("mDBMS"),$CORE_LOCAL->get("mDatabase"),
			      $CORE_LOCAL->get("mUser"),$CORE_LOCAL->get("mPass"),False);
	return $sql;
}

// ----------getsubtotals()----------

// getsubtotals() updates the values held in our session variables.

/**
  Load values from subtotals view into $CORE_LOCAL.
  Essentially refreshes totals in the session.
*/
function getsubtotals() {
	global $CORE_LOCAL;

	$query = "SELECT * FROM subtotals";
	$connection = tDataConnect();
	$result = $connection->query($query);
	$row = $connection->fetch_array($result);

	// reset a few variables
	if (!$row || $row["LastID"] == 0) {
		$CORE_LOCAL->set("ttlflag",0);
		$CORE_LOCAL->set("fntlflag",0);
		setglobalflags(0);
	}

	$CORE_LOCAL->set("LastID", (!$row || !isset($row['LastID'])) ? 0 : (double)$row["LastID"] );
	$cn = (!$row || !isset($row['card_no'])) ? "0" : trim($row["card_no"]);
	if ($cn != "0" || $CORE_LOCAL->get("memberID") == "") 
		$CORE_LOCAL->set("memberID",$cn);
	$CORE_LOCAL->set("runningTotal", (!$row || !isset($row['runningTotal'])) ? 0 : (double)$row["runningTotal"] );
	$CORE_LOCAL->set("taxTotal", (!$row || !isset($row['taxTotal'])) ? 0 : (double)$row["taxTotal"] );
	$CORE_LOCAL->set("discounttotal", (!$row || !isset($row['discountTTL'])) ? 0 : (double)$row["discountTTL"] );
	$CORE_LOCAL->set("tenderTotal", (!$row || !isset($row['tenderTotal'])) ? 0 : (double)$row["tenderTotal"] );
	$CORE_LOCAL->set("memSpecial", (!$row || !isset($row['memSpecial'])) ? 0 : (double)$row["memSpecial"] );
	$CORE_LOCAL->set("staffSpecial", (!$row || !isset($row['staffSpecial'])) ? 0 : (double)$row["staffSpecial"] );
	$CORE_LOCAL->set("percentDiscount", (!$row || !isset($row['percentDiscount'])) ? 0 : (double)$row["percentDiscount"] );
	$CORE_LOCAL->set("transDiscount", (!$row || !isset($row['transDiscount'])) ? 0 : (double)$row["transDiscount"] );
	$CORE_LOCAL->set("fsTaxExempt", (!$row || !isset($row['fsTaxExempt'])) ? 0 : (double)$row["fsTaxExempt"] );
	$CORE_LOCAL->set("fsEligible", (!$row || !isset($row['fsEligible'])) ? 0 : (double)$row["fsEligible"] );
	$CORE_LOCAL->set("memCouponTTL", (!$row || !isset($row['couponTotal']) || !isset($row['memCoupon'])) ? 0 : -1 * ((double) $row["couponTotal"]) + ((double) $row["memCoupon"]));
	$CORE_LOCAL->set("refundTotal", (!$row || !isset($row['refundTotal'])) ? 0 : (double)$row["refundTotal"] );
	$CORE_LOCAL->set("chargeTotal", (!$row || !isset($row['chargeTotal'])) ? 0 : (double)$row["chargeTotal"] );
	$CORE_LOCAL->set("ccTotal", (!$row || !isset($row['ccTotal'])) ? 0 : (double)$row["ccTotal"] );
	$CORE_LOCAL->set("memChargeTotal", (!$row || !isset($row['memChargeTotal'])) ? 0 : (double)$row["memChargeTotal"] );
	$CORE_LOCAL->set("madCoup", (!$row || !isset($row['madCoupon'])) ? 0 : (double)$row["madCoupon"] );
	$CORE_LOCAL->set("scTaxTotal", (!$row || !isset($row['scTaxTotal'])) ? 0 : (double)$row["scTaxTotal"] );
	$CORE_LOCAL->set("scDiscount", (!$row || !isset($row['scDiscount'])) ? 0 : (double)$row["scDiscount"] );
	$CORE_LOCAL->set("paymentTotal", (!$row || !isset($row['paymentTotal'])) ? 0 : (double)$row["paymentTotal"] );
	$CORE_LOCAL->set("discountableTotal", (!$row || !isset($row['discountableTotal'])) ? 0 : (double)$row["discountableTotal"] );
	$CORE_LOCAL->set("localTotal", (!$row || !isset($row['localTotal'])) ? 0 : (double)$row["localTotal"] );
	$CORE_LOCAL->set("voidTotal", (!$row || !isset($row['voidTotal'])) ? 0 : (double)$row["voidTotal"] );

	if ($CORE_LOCAL->get("memberID") == "0" && $CORE_LOCAL->get("runningTotal") > 0) {
		if ($CORE_LOCAL->get("SSI") != 0 && ($CORE_LOCAL->get("isStaff") == 3 || $CORE_LOCAL->get("isStaff") == 6)) wmdiscount();
	}

	if ($CORE_LOCAL->get("sc") == 1) {
		$CORE_LOCAL->set("taxTotal",$CORE_LOCAL->get("scTaxTotal"));
	}

	if ( $CORE_LOCAL->get("TaxExempt") == 1 ) {
		$CORE_LOCAL->set("taxable",0);
		$CORE_LOCAL->set("taxTotal",0);
		$CORE_LOCAL->set("fsTaxable",0);
		$CORE_LOCAL->set("fsTaxExempt",0);
	}

	$CORE_LOCAL->set("subtotal",number_format($CORE_LOCAL->get("runningTotal") - $CORE_LOCAL->get("transDiscount"), 2));
	/* using a string for amtdue behaves strangely for
	 * values > 1000, so use floating point */
	$CORE_LOCAL->set("amtdue",(double)round($CORE_LOCAL->get("runningTotal") - $CORE_LOCAL->get("transDiscount") + $CORE_LOCAL->get("taxTotal"), 2));

	if ( $CORE_LOCAL->get("fsEligible") > $CORE_LOCAL->get("subtotal") ) {
		$CORE_LOCAL->set("fsEligible",$CORE_LOCAL->get("subtotal"));
	}


	$connection->close();

}

// ----------gettransno($CashierNo /int)----------
//
// Given $CashierNo, gettransno() will look up the number of the most recent transaction.

/**
 Get the next transaction number for a given cashier
 @param $CashierNo cashier number (emp_no in tables) 
 @return integer transaction number
*/
function gettransno($CashierNo) {
	global $CORE_LOCAL;

	$database = $CORE_LOCAL->get("tDatabase");
	$register_no = $CORE_LOCAL->get("laneno");
	$query = "SELECT max(trans_no) as maxtransno from localtranstoday where emp_no = '"
		.$CashierNo."' and register_no = '"
		.$register_no."' GROUP by register_no, emp_no";
	$connection = tDataConnect();
	$result = $connection->query($query);
	$row = $connection->fetch_array($result);
	if (!$row || !$row["maxtransno"]) {
		$trans_no = 1;
	}
	else {
		$trans_no = $row["maxtransno"] + 1;
	}
	$connection->close();
	return $trans_no;
}

// ------------------------------------------------------------------

/**
  See if the remote database is available
  This function calls uploadtoServer() if
  the initial test works.
  @return integer 
   - 1 server available
   - 0 server down
*/
function testremote() {
	global $CORE_LOCAL;


	$intConnected = pingport($CORE_LOCAL->get("mServer"), $CORE_LOCAL->get("mDBMS"));
	if ($intConnected == 1) {

		uploadtoServer(); 

	} else {
		$CORE_LOCAL->set("standalone",1);
	}

	return ($CORE_LOCAL->get("standalone") + 1) % 2;
}

// ------------------------------------------------------------------
/**
  Copy tables from the lane to the remote server
  The following tables are copied:
   - dtransactions
   - alog
   - suspended
   - efsnetRequest
   - efsnetResponse
   - efsnetRequestMod

  On success the local tables are truncated. The efsnet tables
  are copied in the uploadCCdata() function but that gets called 
  automatically.

  @return
   - 1 upload succeeded
   - 0 upload failed
*/
function uploadtoServer()
{
	global $CORE_LOCAL;

	$uploaded = 0;

	// new upload method makes use of SQLManager's transfer method
	// to simulate cross-server queries
	$connect = tDataConnect();
	$connect->add_connection($CORE_LOCAL->get("mServer"),
				$CORE_LOCAL->get("mDBMS"),
				$CORE_LOCAL->get("mDatabase"),
				$CORE_LOCAL->get("mUser"),
				$CORE_LOCAL->get("mPass"),
				False);
	if (!isset($connect->connections[$CORE_LOCAL->get("mDatabase")]) ||
		$connect->connections[$CORE_LOCAL->get("mDatabase")] === False){
		$CORE_LOCAL->set("standalone",1);
		return 0;	
	}

	$dt_matches = getMatchingColumns($connect,"dtransactions");

	if ($connect->transfer($CORE_LOCAL->get("tDatabase"),
		"select {$dt_matches} from dtransactions",
		$CORE_LOCAL->get("mDatabase"),"insert into dtransactions ({$dt_matches})")){

		$al_matches = getMatchingColumns($connect,"alog");
		// interval is a mysql reserved word
		// so it needs to be escaped
		$local_columns = $al_matches;
		$server_columns = $al_matches;
		if ($CORE_LOCAL->get("DBMS") == "mysql")
			$local_columns = str_replace("Interval","`Interval`",$al_matches);
		if ($CORE_LOCAL->get("mDBMS") == "mysql")
			$server_columns = str_replace("Interval","`Interval`",$al_matches);
		$al_success = $connect->transfer($CORE_LOCAL->get("tDatabase"),
			"select $local_columns FROM alog",
			$CORE_LOCAL->get("mDatabase"),
			"insert into alog ($server_columns)");

		$su_matches = getMatchingColumns($connect,"suspended");
		$su_success = $connect->transfer($CORE_LOCAL->get("tDatabase"),
			"select {$su_matches} from suspended",
			$CORE_LOCAL->get("mDatabase"),
			"insert into suspended ({$su_matches})");

		$connect->query("truncate table dtransactions",
			$CORE_LOCAL->get("tDatabase"));
		if ($al_success){
			$connect->query("truncate table alog",
				$CORE_LOCAL->get("tDatabase"));
		}
		if ($su_success){
			$connect->query("truncate table suspended",
				$CORE_LOCAL->get("tDatabase"));
		}

		$uploaded = 1;
		$CORE_LOCAL->set("standalone",0);
	}
	else {
		$uploaded = 0;
		$CORE_LOCAL->set("standalone",1);
	}

	$connect->close($CORE_LOCAL->get("mDatabase"));
	$connect->close($CORE_LOCAL->get("tDatabase"));

	uploadCCdata();

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
   @return an array of column names
*/
function getMatchingColumns($connection,$table_name,$table2=""){
	global $CORE_LOCAL;

	$local_poll = $connection->table_definition($table_name,$CORE_LOCAL->get("tDatabase"));
	$local_cols = array();
	foreach($local_poll as $name=>$v)
		$local_cols[$name] = True;
	$remote_poll = $connection->table_definition((!empty($table2)?$table2:$table_name),
				$CORE_LOCAL->get("mDatabase"));
	$matching_cols = array();
	foreach($remote_poll as $name=>$v){
		if (isset($local_cols[$name]))
			$matching_cols[] = $name;
	}

	$ret = "";
	foreach($matching_cols as $col)
		$ret .= $col.",";
	return rtrim($ret,",");
}

/** Get a list of columns in both tables.
   @param $connection a SQLManager object that's
    already connected
   @param $table1 a database table
   @param $table2 a database table
   @return an array of column names common to both tables
 */
function localMatchingColumns($connection,$table1,$table2){
	$poll1 = $connection->table_definition($table1);
	$cols1 = array();
	foreach($poll1 as $name=>$v)
		$cols1[$name] = True;
	$poll2 = $connection->table_definition($table2);
	$matching_cols = array();
	foreach($poll2 as $name=>$v){
		if (isset($cols1[$name]))
			$matching_cols[] = $name;
	}

	$ret = "";
	foreach($matching_cols as $col)
		$ret .= $col.",";
	return rtrim($ret,",");
}

/**
  Transfer credit card tables to the server.
  See uploadtoServer().
*/
function uploadCCdata(){
	global $CORE_LOCAL;

	$sql = tDataConnect();
	$sql->add_connection($CORE_LOCAL->get("mServer"),
				$CORE_LOCAL->get("mDBMS"),
				$CORE_LOCAL->get("mDatabase"),
				$CORE_LOCAL->get("mUser"),
				$CORE_LOCAL->get("mPass"),
				False);

	$req_cols = getMatchingColumns($sql,"efsnetRequest");
	if ($sql->transfer($CORE_LOCAL->get("tDatabase"),
		"select {$req_cols} from efsnetRequest",
		$CORE_LOCAL->get("mDatabase"),"insert into efsnetRequest ({$req_cols})")){

		$sql->query("truncate table efsnetRequest",
			$CORE_LOCAL->get("tDatabase"));

		$res_cols = getMatchingColumns($sql,"efsnetResponse");
		$res_success = $sql->transfer($CORE_LOCAL->get("tDatabase"),
			"select {$res_cols} from efsnetResponse",
			$CORE_LOCAL->get("mDatabase"),
			"insert into efsnetResponse ({$res_cols})");
		if ($res_success){
			$sql->query("truncate table efsnetResponse",
				$CORE_LOCAL->get("tDatabase"));
		}

		$mod_cols = getMatchingColumns($sql,"efsnetRequestMod");
		$mod_success = $sql->transfer($CORE_LOCAL->get("tDatabase"),
			"select {$mod_cols} from efsnetRequestMod",
			$CORE_LOCAL->get("mDatabase"),
			"insert into efsnetRequestMod ({$mod_cols})");
		if ($mod_success){
			$sql->query("truncate table efsnetRequestMod",
				$CORE_LOCAL->get("tDatabase"));
		}
	}
}

?>
