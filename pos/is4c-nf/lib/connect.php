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

if (!class_exists("SQLManager")) include($_SERVER["DOCUMENT_ROOT"]."/lib/SQLManager.php");

if (!function_exists("setglobalflags")) include($_SERVER["DOCUMENT_ROOT"]."/lib/loadconfig.php");
if (!function_exists("pinghost")) include($_SERVER["DOCUMENT_ROOT"]."/lib/lib.php");
if (!function_exists("wmdiscount")) include($_SERVER["DOCUMENT_ROOT"]."/lib/prehkeys.php");
if (!isset($IS4C_LOCAL)) include($_SERVER["DOCUMENT_ROOT"]."/lib/LocalStorage/conf.php");


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

	// set_error_handler("dataError");


	$intConnected = pinghost($IS4C_LOCAL->get("mServer"));
	if ($intConnected == 1) {

		uploadtoServer(); 

	} else {
		$IS4C_LOCAL->set("standalone",1);
	}

	return ($IS4C_LOCAL->get("standalone") + 1) % 2;
}

// ------------------------------------------------------------------

function testcc() {
	
}

// ------------------------------------------------------------------
function uploadtoServer()
{
	global $IS4C_LOCAL;

	$uploaded = 0;

	if ($IS4C_LOCAL->get("DBMS") == "mssql") {

		$strUploadDTrans = "insert ".trim($IS4C_LOCAL->get("mServer")).".".trim($IS4C_LOCAL->get("mDatabase")).".dbo.dtransactions select * from dtransactions";
		$strUploadAlog = "insert ".trim($IS4C_LOCAL->get("mServer")).".".trim($IS4C_LOCAL->get("mDatabase")).".dbo.alog select * from alog";
		$strUploadsuspended = "insert ".trim($IS4C_LOCAL->get("mServer")).".".trim($IS4C_LOCAL->get("mDatabase")).".dbo.suspended select * from suspended";		
		
		$strUploadToday = "insert ".trim($IS4C_LOCAL->get("mServer")).".".trim($IS4C_LOCAL->get("mDatabase")).".dbo.dtranstoday select * from dtransactions";
		
		$connect = tDataConnect();

		if ( $connect->query($strUploadDTrans) ) {

			$connect->query("truncate table dtransactions");
			$connect->query($strUploadAlog);
			$connect->query("truncate table alog");
			$connect->query($strUploadsuspended);
			$connect->query("truncate table suspended");
			$uploaded = 1;
			$IS4C_LOCAL->set("standalone",0);

		} else {

			$uploaded = 0;
			$IS4C_LOCAL->set("standalone",1);
		}
	}
	else {
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

		$dtcols = "datetime,register_no,emp_no,trans_no,upc,description,
			trans_type,trans_subtype,trans_status,department,quantity,
			Scale,cost,unitPrice,total,regPrice,tax,foodstamp,discount,
			memDiscount,discountable,discounttype,voided,percentDiscount,
			ItemQtty,volDiscType,volume,VolSpecial,mixMatch,matched,
			memType,isStaff,numflag,charflag,card_no,trans_id";

		if ($connect->transfer($IS4C_LOCAL->get("tDatabase"),
			"select * from dtransactions",
			$IS4C_LOCAL->get("mDatabase"),"insert into dtransactions ($dtcols)")){

			$connect->transfer($IS4C_LOCAL->get("tDatabase"),
				"select * from alog",
				$IS4C_LOCAL->get("mDatabase"),
				"insert into alog");
			$connect->transfer($IS4C_LOCAL->get("tDatabase"),
				"select * from suspended",
				$IS4C_LOCAL->get("mDatabase"),
				"insert into suspended ($dtcols)");

			$connect->query("truncate table dtransactions",
				$IS4C_LOCAL->get("tDatabase"));
			$connect->query("truncate table alog",
				$IS4C_LOCAL->get("tDatabase"));
			$connect->query("truncate table suspended",
				$IS4C_LOCAL->get("tDatabase"));

			$uploaded = 1;
			$IS4C_LOCAL->set("standalone",0);
		}
		else {
			$uploaded = 0;
			$IS4C_LOCAL->set("standalone",1);
		}

		$connect->close($IS4C_LOCAL->get("mDatabase"));
		$connect->close($IS4C_LOCAL->get("tDatabase"));
	}

	uploadCCdata();

	return $uploaded;
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
	if ($sql->transfer($IS4C_LOCAL->get("tDatabase"),
		"select * from efsnetrequest",
		$IS4C_LOCAL->get("mDatabase"),"insert into efsnetrequest")){

		$sql->query("truncate table efsnetrequest",
			$IS4C_LOCAL->get("tDatabase"));

		$sql->transfer($IS4C_LOCAL->get("tDatabase"),
			"select * from efsnetresponse",
			$IS4C_LOCAL->get("mDatabase"),
			"insert into efsnetresponse");
		$sql->query("truncate table efsnetresponse",
			$IS4C_LOCAL->get("tDatabase"));

		$sql->transfer($IS4C_LOCAL->get("tDatabase"),
			"select * from efsnetrequestmod",
			$IS4C_LOCAL->get("mDatabase"),
			"insert into efsnetrequestmod");
		$sql->query("truncate table efsnetrequestmod",
			$IS4C_LOCAL->get("tDatabase"));
	}
}

?>
