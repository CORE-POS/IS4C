<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op.

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

ini_set('display_errors','1');

include_once($IS4C_PATH."ini.php");
include_once($IS4C_PATH."lib/session.php");
include_once($IS4C_PATH."lib/printLib.php");
include_once($IS4C_PATH."lib/printReceipt.php");
include_once($IS4C_PATH."lib/connect.php");
include_once($IS4C_PATH."lib/additem.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

if ($IS4C_LOCAL->get("End") == 1) {
	addtransDiscount();
	addTax();
}

$receiptType = isset($_REQUEST['receiptType'])?$_REQUEST['receiptType']:'';

if (strlen($receiptType) > 0) {
	
	if ($receiptType != "none")
		printReceipt($receiptType);

	if ($IS4C_LOCAL->get("ccCustCopy") == 1){
		$IS4C_LOCAL->set("ccCustCopy",0);
		printReceipt($receiptType);
	}
	elseif ($receiptType == "ccSlip"){
		// don't mess with reprints
	}
	elseif ($IS4C_LOCAL->get("autoReprint") == 1){
		$IS4C_LOCAL->set("autoReprint",0);
		printReceipt($receiptType,True);
	}

	if ($IS4C_LOCAL->get("End") >= 1 || $receiptType == "cancelled"
		|| $receiptType == "suspended"){
		$IS4C_LOCAL->set("End",0);
		cleartemptrans($receiptType);
	}
}

echo "Done";

function cleartemptrans($type) {
	global $IS4C_LOCAL;

	// make sure transno advances even if something
	// wacky happens with the db shuffling
	loadglobalvalues();	
	$IS4C_LOCAL->set("transno",$IS4C_LOCAL->get("transno") + 1);
	setglobalvalue("TransNo", $IS4C_LOCAL->get("transno"));

	$db = tDataConnect();

	if($type == "cancelled") {
		$IS4C_LOCAL->set("msg",99);
		$db->query("update localtemptrans set trans_status = 'X'");
	}

	moveTempData();
	truncateTempTables();

	$db->close();

	if ($IS4C_LOCAL->get("testremote")==0)
		testremote(); 

	if ($IS4C_LOCAL->get("TaxExempt") != 0) {
		$IS4C_LOCAL->set("TaxExempt",0);
		setglobalvalue("TaxExempt", 0);
	}

	memberReset();
	transReset();
	printReset();

	getsubtotals();

	return 1;
}


function truncateTempTables() {
	$connection = tDataConnect();
	$query1 = "truncate table localtemptrans";
	$query2 = "truncate table activitytemplog";
	$query3 = "truncate table couponApplied";

	$connection->query($query1);
	$connection->query($query2);
	$connection->query($query3);

	$connection->close();
}

function moveTempData() {
	$connection = tDataConnect();

	$connection->query("update localtemptrans set trans_type = 'T' where trans_subtype = 'CP'");
	//$connection->query("update localtemptrans set trans_type = 'T', trans_subtype = 'IC' where upc in ('0000000008019', '0000000003031', '0000000001014')");
	$connection->query("update localtemptrans set upc = 'DISCOUNT', description = upc, department = 0 where trans_status = 'S'");

	$connection->query("insert into localtrans select * from localtemptrans");
	$connection->query("insert into localtrans_today select * from localtemptrans");
	$connection->query("insert into dtransactions select * from localtemptrans");

	$connection->query("insert into activitylog select * from activitytemplog");
	$connection->query("insert into alog select * from activitytemplog");

	$connection->close();
}
?>
