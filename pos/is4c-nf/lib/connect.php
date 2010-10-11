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
	return $sql;
}

function pDataConnect()
{
	global $IS4C_LOCAL;

	$sql = new SQLManager($IS4C_LOCAL->get("localhost"),$IS4C_LOCAL->get("DBMS"),$IS4C_LOCAL->get("pDatabase"),
			      $IS4C_LOCAL->get("localUser"),$IS4C_LOCAL->get("localPass"),False);
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

	if (!$row || $row["LastID"] == 0) {
		$IS4C_LOCAL->set("LastID",0);
		$IS4C_LOCAL->set("runningTotal",0);
		$IS4C_LOCAL->set("subtotal",0);
		$IS4C_LOCAL->set("taxTotal",0);
		$IS4C_LOCAL->set("discounttotal",0);
		$IS4C_LOCAL->set("tenderTotal",0);
		$IS4C_LOCAL->set("memSpecial",0);
		$IS4C_LOCAL->set("staffSpecial",0);
		$IS4C_LOCAL->set("percentDiscount",0);
		$IS4C_LOCAL->set("transDiscount",0);
		$IS4C_LOCAL->set("fsTaxExempt",0);
		$IS4C_LOCAL->set("fsEligible",0);
		$IS4C_LOCAL->set("ttlflag",0);
		$IS4C_LOCAL->set("fntlflag",0);
		$IS4C_LOCAL->set("memCouponTTL",0);
		$IS4C_LOCAL->set("refundTotal",0);
		$IS4C_LOCAL->set("chargeTotal",0);
		$IS4C_LOCAL->set("ccTotal",0);
		$IS4C_LOCAL->set("memChargeTotal",0);
		$IS4C_LOCAL->set("madCoup",0);
		$IS4C_LOCAL->set("scTaxTotal",0);
		$IS4C_LOCAL->set("scDiscount",0);
		$IS4C_LOCAL->set("paymentTotal",0);
		$IS4C_LOCAL->set("discountableTotal",0);
		$IS4C_LOCAL->set("localTotal",0.00);

		setglobalflags(0);
	}
	else {
		$IS4C_LOCAL->set("LastID",(double) $row["LastID"]);
		$IS4C_LOCAL->set("memberID",trim($row["card_no"]));
		$IS4C_LOCAL->set("runningTotal",(double) $row["runningTotal"]);
		$IS4C_LOCAL->set("taxTotal",(double) $row["taxTotal"]);
		$IS4C_LOCAL->set("discounttotal",(double) $row["discountTTL"]);
		$IS4C_LOCAL->set("madCoup",(double) $row["madCoupon"]);
		$IS4C_LOCAL->set("memSpecial",(double) $row["memSpecial"]);
		$IS4C_LOCAL->set("staffSpecial",(double) $row["staffSpecial"]);
		$IS4C_LOCAL->set("tenderTotal",(double) $row["tenderTotal"]);
		$IS4C_LOCAL->set("percentDiscount",(int) $row["percentDiscount"]);
		$IS4C_LOCAL->set("transDiscount",(double) $row["transDiscount"]);

		$IS4C_LOCAL->set("scDiscount",(double) $row["scDiscount"]);
		$IS4C_LOCAL->set("scTaxTotal",(double) $row["scTaxTotal"]);
		$IS4C_LOCAL->set("fsTaxExempt",(double) $row["fsTaxExempt"]);
		$IS4C_LOCAL->set("fsEligible",(double) $row["fsEligible"]);
		$IS4C_LOCAL->set("memCouponTTL",-1 * ((double) $row["couponTotal"]) + ((double) $row["memCoupon"]));
		$IS4C_LOCAL->set("refundTotal",(double) $row["refundTotal"]);
		$IS4C_LOCAL->set("chargeTotal",(double) $row["chargeTotal"]);
		$IS4C_LOCAL->set("ccTotal",(double) $row["ccTotal"]);
		$IS4C_LOCAL->set("paymentTotal",(double) $row["paymentTotal"]);
		$IS4C_LOCAL->set("memChargeTotal",(double) $row["memChargeTotal"]);
		$IS4C_LOCAL->set("discountableTotal",(double) $row["discountableTotal"]);
		$IS4C_LOCAL->set("localTotal",(double) $row["localTotal"]);
	}

	if ($IS4C_LOCAL->get("memberID") == "" && $IS4C_LOCAL->get("runningTotal") > 0) {
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

function gettransno($CashierNo) {
	global $IS4C_LOCAL;

	$database = $IS4C_LOCAL->get("tDatabase");
	$register_no = $IS4C_LOCAL->get("laneno");
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
		$al_success = $connect->transfer($IS4C_LOCAL->get("tDatabase"),
			"select {$al_matches} from alog",
			$IS4C_LOCAL->get("mDatabase"),
			"insert into alog ({$al_matches})");


		$su_matches = getMatchingColumns($connect,"suspended");
		$su_sucess = $connect->transfer($IS4C_LOCAL->get("tDatabase"),
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

function uploadCCdata(){
	global $IS4C_LOCAL;

	$sql = tDataConnect();
	$sql->add_connection($IS4C_LOCAL->get("mServer"),
				$IS4C_LOCAL->get("mDBMS"),
				$IS4C_LOCAL->get("mDatabase"),
				$IS4C_LOCAL->get("mUser"),
				$IS4C_LOCAL->get("mPass"),
				False);

	$req_cols = getMatchingColumns($sql,"efsnetrequest");
	if ($sql->transfer($IS4C_LOCAL->get("tDatabase"),
		"select {$req_cols} from efsnetrequest",
		$IS4C_LOCAL->get("mDatabase"),"insert into efsnetrequest ({$req_cols})")){

		$sql->query("truncate table efsnetrequest",
			$IS4C_LOCAL->get("tDatabase"));

		$res_cols = getMatchingColumns($sql,"efsnetresponse");
		$res_success = $sql->transfer($IS4C_LOCAL->get("tDatabase"),
			"select {$res_cols} from efsnetresponse",
			$IS4C_LOCAL->get("mDatabase"),
			"insert into efsnetresponse ({$res_cols})");
		if ($res_success){
			$sql->query("truncate table efsnetresponse",
				$IS4C_LOCAL->get("tDatabase"));
		}

		$mod_cols = getMatchingColumns($sql,"efsnetrequestmod");
		$mod_success = $sql->transfer($IS4C_LOCAL->get("tDatabase"),
			"select {$mod_cols} from efsnetrequestmod",
			$IS4C_LOCAL->get("mDatabase"),
			"insert into efsnetrequestmod ({$mod_cols})");
		if ($mod_success){
			$sql->query("truncate table efsnetrequestmod",
				$IS4C_LOCAL->get("tDatabase"));
		}
	}
}

?>
