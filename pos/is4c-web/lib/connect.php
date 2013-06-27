<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

    This file is part of IS4C.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

if (!class_exists("SQLManager")) include($IS4C_PATH."lib/SQLManager.php");

if (!function_exists("setglobalflags")) include($IS4C_PATH."lib/loadconfig.php");
if (!function_exists("pinghost")) include($IS4C_PATH."lib/lib.php");
if (!function_exists("wmdiscount")) include($IS4C_PATH."lib/prehkeys.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");


/***********************************************************************************************

 Functions transcribed from connect.asp on 07.13.03 by Brandon.

***********************************************************************************************/



function tDataConnect()
{
	global $IS4C_LOCAL;

	$sql = new SQLManager($IS4C_LOCAL->get("localhost"),$IS4C_LOCAL->get("DBMS"),$IS4C_LOCAL->get("tDatabase"),
			      $IS4C_LOCAL->get("localUser"),$IS4C_LOCAL->get("localPass"),False);
	$sql->query("SET time_zone='America/Chicago'");
	return $sql;
}

function pDataConnect()
{
	global $IS4C_LOCAL;

	$sql = new SQLManager($IS4C_LOCAL->get("localhost"),$IS4C_LOCAL->get("DBMS"),$IS4C_LOCAL->get("pDatabase"),
			      $IS4C_LOCAL->get("localUser"),$IS4C_LOCAL->get("localPass"),False);
	$sql->query("SET time_zone='America/Chicago'");
	return $sql;
}

function mDataConnect()
{
	global $IS4C_LOCAL;

	$sql = new SQLManager($IS4C_LOCAL->get("mServer"),$IS4C_LOCAL->get("mDBMS"),$IS4C_LOCAL->get("mDatabase"),
			      $IS4C_LOCAL->get("mUser"),$IS4C_LOCAL->get("mPass"),False);
	return $sql;
}

// ----------getsubtotals()----------

// getsubtotals() updates the values held in our session variables.

function getsubtotals() {
	global $IS4C_LOCAL;

	$query = "SELECT * FROM subtotals";
	$connection = tDataConnect();
	$result = $connection->query($query);
	$row = $connection->fetch_array($result);

	// reset a few variables
	if (False && (!$row || $row["LastID"] == 0)) {
		$IS4C_LOCAL->set("ttlflag",0);
		$IS4C_LOCAL->set("fntlflag",0);
		setglobalflags(0);
	}

	$IS4C_LOCAL->set("LastID", (!$row || !isset($row['LastID'])) ? 0 : (double)$row["LastID"] );
	$IS4C_LOCAL->set("memberID", (!$row || !isset($row['card_no'])) ? "0" : trim($row["card_no"]) );
	$IS4C_LOCAL->set("runningTotal", (!$row || !isset($row['runningTotal'])) ? 0 : (double)$row["runningTotal"] );
	$IS4C_LOCAL->set("taxTotal", (!$row || !isset($row['taxTotal'])) ? 0 : (double)$row["taxTotal"] );
	$IS4C_LOCAL->set("discounttotal", (!$row || !isset($row['discounttotal'])) ? 0 : (double)$row["discountTTL"] );
	$IS4C_LOCAL->set("tenderTotal", (!$row || !isset($row['tenderTotal'])) ? 0 : (double)$row["tenderTotal"] );
	$IS4C_LOCAL->set("memSpecial", (!$row || !isset($row['memSpecial'])) ? 0 : (double)$row["memSpecial"] );
	$IS4C_LOCAL->set("staffSpecial", (!$row || !isset($row['staffSpecial'])) ? 0 : (double)$row["staffSpecial"] );
	$IS4C_LOCAL->set("percentDiscount", (!$row || !isset($row['percentDiscount'])) ? 0 : (double)$row["percentDiscount"] );
	$IS4C_LOCAL->set("transDiscount", (!$row || !isset($row['transDiscount'])) ? 0 : (double)$row["transDiscount"] );
	$IS4C_LOCAL->set("fsTaxExempt", (!$row || !isset($row['fsTaxExempt'])) ? 0 : (double)$row["fsTaxExempt"] );
	$IS4C_LOCAL->set("fsEligible", (!$row || !isset($row['fsEligible'])) ? 0 : (double)$row["fsEligible"] );
	$IS4C_LOCAL->set("memCouponTTL", (!$row || !isset($row['couponTotal']) || !isset($row['memCoupon'])) ? 0 : -1 * ((double) $row["couponTotal"]) + ((double) $row["memCoupon"]));
	$IS4C_LOCAL->set("refundTotal", (!$row || !isset($row['refundTotal'])) ? 0 : (double)$row["refundTotal"] );
	$IS4C_LOCAL->set("chargeTotal", (!$row || !isset($row['chargeTotal'])) ? 0 : (double)$row["chargeTotal"] );
	$IS4C_LOCAL->set("ccTotal", (!$row || !isset($row['ccTotal'])) ? 0 : (double)$row["ccTotal"] );
	$IS4C_LOCAL->set("memChargeTotal", (!$row || !isset($row['memChargeTotal'])) ? 0 : (double)$row["memChargeTotal"] );
	$IS4C_LOCAL->set("madCoup", (!$row || !isset($row['madCoup'])) ? 0 : (double)$row["madCoup"] );
	$IS4C_LOCAL->set("scTaxTotal", (!$row || !isset($row['scTaxTotal'])) ? 0 : (double)$row["scTaxTotal"] );
	$IS4C_LOCAL->set("scDiscount", (!$row || !isset($row['scDiscount'])) ? 0 : (double)$row["scDiscount"] );
	$IS4C_LOCAL->set("paymentTotal", (!$row || !isset($row['paymentTotal'])) ? 0 : (double)$row["paymentTotal"] );
	$IS4C_LOCAL->set("discountableTotal", (!$row || !isset($row['discountableTotal'])) ? 0 : (double)$row["discountableTotal"] );
	$IS4C_LOCAL->set("localTotal", (!$row || !isset($row['localTotal'])) ? 0 : (double)$row["localTotal"] );

	if ($IS4C_LOCAL->get("memberID") == "0" && $IS4C_LOCAL->get("runningTotal") > 0) {
		if ($IS4C_LOCAL->get("SSI") != 0 && ($IS4C_LOCAL->get("isStaff") == 3 || $IS4C_LOCAL->get("isStaff") == 6)) wmdiscount();
	}

	if ($IS4C_LOCAL->get("sc") == 1) {
		$IS4C_LOCAL->set("taxTotal",$IS4C_LOCAL->get("scTaxTotal"));
	}

	if ( $IS4C_LOCAL->get("TaxExempt") == 1 ) {
		$IS4C_LOCAL->set("taxable",0);
		$IS4C_LOCAL->set("taxTotal",0);
		$IS4C_LOCAL->set("fsTaxable",0);
		$IS4C_LOCAL->set("fsTaxExempt",0);
	}

	$IS4C_LOCAL->set("subtotal",number_format($IS4C_LOCAL->get("runningTotal") - $IS4C_LOCAL->get("transDiscount"), 2));
	/* using a string for amtdue behaves strangely for
	 * values > 1000, so use floating point */
	$IS4C_LOCAL->set("amtdue",(double)round($IS4C_LOCAL->get("runningTotal") - $IS4C_LOCAL->get("transDiscount") + $IS4C_LOCAL->get("taxTotal"), 2));

	if ( $IS4C_LOCAL->get("fsEligible") > $IS4C_LOCAL->get("subtotal") ) {
		$IS4C_LOCAL->set("fsEligible",$IS4C_LOCAL->get("subtotal"));
	}


	$connection->close();

}

// ----------gettransno($CashierNo /int)----------
//
// Given $CashierNo, gettransno() will look up the number of the most recent transaction.
// modified for web use. Check localtemptrans for an ongoing transaction first, then
// check for any other transactions that day
function gettransno($CashierNo) {
	global $IS4C_LOCAL;

	$register_no = $IS4C_LOCAL->get("laneno");
	$query1 = "SELECT max(trans_no) as maxtransno from localtemptrans where emp_no = '"
		.$CashierNo."' and register_no = '"
		.$register_no."' GROUP by register_no, emp_no";
	$query2 = "SELECT max(trans_no) as maxtransno from localtranstoday where emp_no = '"
		.$CashierNo."' and register_no = '"
		.$register_no."' GROUP by register_no, emp_no";
	$connection = tDataConnect();
	$result = $connection->query($query1);
	$row = $connection->fetch_array($result);
	if ($row) return $row['maxtransno'];

	$result = $connection->query($query2);
	$row = $connection->fetch_array($result);
	if (!$row || !$row["maxtransno"]) {
		$trans_no = 1;
	}
	else {
		$trans_no = $row["maxtransno"] + 1;
	}
	return $trans_no;
}

// ------------------------------------------------------------------

function testremote() {
	global $IS4C_LOCAL;


	$intConnected = pinghost($IS4C_LOCAL->get("mServer"));
	if ($intConnected == 1) {

		uploadtoServer(); 

	} else {
		$IS4C_LOCAL->set("standalone",1);
	}

	return ($IS4C_LOCAL->get("standalone") + 1) % 2;
}

// ------------------------------------------------------------------
function uploadtoServer()
{
	global $IS4C_LOCAL;

	$uploaded = 0;

	// new upload method makes use of SQLManager's transfer method
	// to simulate cross-server queries
	$connect = tDataConnect();
	$connect->add_connection($IS4C_LOCAL->get("mServer"),
				$IS4C_LOCAL->get("mDBMS"),
				$IS4C_LOCAL->get("mDatabase"),
				$IS4C_LOCAL->get("mUser"),
				$IS4C_LOCAL->get("mPass"),
				False);
	if (!isset($connect->connections[$IS4C_LOCAL->get("mDatabase")]) ||
		$connect->connections[$IS4C_LOCAL->get("mDatabase")] === False){
		$IS4C_LOCAL->set("standalone",1);
		return 0;	
	}

	$dt_matches = getMatchingColumns($connect,"dtransactions");

	if ($connect->transfer($IS4C_LOCAL->get("tDatabase"),
		"select {$dt_matches} from dtransactions",
		$IS4C_LOCAL->get("mDatabase"),"insert into dtransactions ({$dt_matches})")){

		$al_matches = getMatchingColumns($connect,"alog");
		$al_my = ""; // interval is a mysql reserved word
		if ($IS4C_LOCAL->get("DBMS") == "mysql")
			$al_my = str_replace("Interval","`Interval`",$al_matches);
		$al_success = $connect->transfer($IS4C_LOCAL->get("tDatabase"),
			"select ".(empty($al_my)?$al_matches:$al_my)." from alog",
			$IS4C_LOCAL->get("mDatabase"),
			"insert into alog ({$al_matches})");


		$su_matches = getMatchingColumns($connect,"suspended");
		$su_success = $connect->transfer($IS4C_LOCAL->get("tDatabase"),
			"select {$su_matches} from suspended",
			$IS4C_LOCAL->get("mDatabase"),
			"insert into suspended ({$su_matches})");

		$connect->query("truncate table dtransactions",
			$IS4C_LOCAL->get("tDatabase"));
		if ($al_success){
			$connect->query("truncate table alog",
				$IS4C_LOCAL->get("tDatabase"));
		}
		if ($su_success){
			$connect->query("truncate table suspended",
				$IS4C_LOCAL->get("tDatabase"));
		}

		$uploaded = 1;
		$IS4C_LOCAL->set("standalone",0);
	}
	else {
		$uploaded = 0;
		$IS4C_LOCAL->set("standalone",1);
	}

	$connect->close($IS4C_LOCAL->get("mDatabase"));
	$connect->close($IS4C_LOCAL->get("tDatabase"));

	uploadCCdata();

	return $uploaded;
}

/* get a list of columns that exist on the local db
   and the server db for the given table
   $connection should be a SQLManager object that's
   already connected
   if $table2 is provided, it match columns from
   local.table_name against remote.table2
*/
function getMatchingColumns($connection,$table_name,$table2=""){
	global $IS4C_LOCAL;

	$local_poll = $connection->table_definition($table_name,$IS4C_LOCAL->get("tDatabase"));
	$local_cols = array();
	foreach($local_poll as $name=>$v)
		$local_cols[$name] = True;
	$remote_poll = $connection->table_definition((!empty($table2)?$table2:$table_name),
				$IS4C_LOCAL->get("mDatabase"));
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

/* get a list of columns in both tables
 * unlike getMatchingColumns, this compares tables
 * on the same database & server
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

function uploadCCdata(){
	global $IS4C_LOCAL;

	$sql = tDataConnect();
	$sql->add_connection($IS4C_LOCAL->get("mServer"),
				$IS4C_LOCAL->get("mDBMS"),
				$IS4C_LOCAL->get("mDatabase"),
				$IS4C_LOCAL->get("mUser"),
				$IS4C_LOCAL->get("mPass"),
				False);

	$req_cols = getMatchingColumns($sql,"efsnetRequest");
	if ($sql->transfer($IS4C_LOCAL->get("tDatabase"),
		"select {$req_cols} from efsnetRequest",
		$IS4C_LOCAL->get("mDatabase"),"insert into efsnetRequest ({$req_cols})")){

		$sql->query("truncate table efsnetRequest",
			$IS4C_LOCAL->get("tDatabase"));

		$res_cols = getMatchingColumns($sql,"efsnetResponse");
		$res_success = $sql->transfer($IS4C_LOCAL->get("tDatabase"),
			"select {$res_cols} from efsnetResponse",
			$IS4C_LOCAL->get("mDatabase"),
			"insert into efsnetResponse ({$res_cols})");
		if ($res_success){
			$sql->query("truncate table efsnetResponse",
				$IS4C_LOCAL->get("tDatabase"));
		}

		$mod_cols = getMatchingColumns($sql,"efsnetRequestMod");
		$mod_success = $sql->transfer($IS4C_LOCAL->get("tDatabase"),
			"select {$mod_cols} from efsnetRequestMod",
			$IS4C_LOCAL->get("mDatabase"),
			"insert into efsnetRequestMod ({$mod_cols})");
		if ($mod_success){
			$sql->query("truncate table efsnetRequestMod",
				$IS4C_LOCAL->get("tDatabase"));
		}
	}
}

?>
