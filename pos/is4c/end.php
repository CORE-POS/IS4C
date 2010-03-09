<body bgcolor='#ffffff'>
<?
include_once("ini/ini.php");
include_once("session.php");
include_once("printLib.php");
include_once("printReceipt.php");
include_once("connect.php");
include_once("additem.php");
include_once("ccLib.php");
include_once("maindisplay.php");



if ($_SESSION["End"] == 1) {

	addtransDiscount();
	addTax();
}
/* acg 
if ($_SESSION["End"] == 1 && $_SESSION["isStaff"] == 3 && $_SESSION["volunteerDiscount"] != 0) {
	
	wmupdate($_SESSION["memID"], $_SESSION["volunteerDiscount"]);
}
*/
$receiptType = $_SESSION["receiptType"];
$_SESSION["receiptType"] = "";


if (strlen($receiptType) > 0) {
	
	printReceipt($receiptType);

	if ($_SESSION["End"] == 1 || $_SESSION["msg"] == 2) {

		if ($_SESSION["msg"] == 2) {
			$returnHome = 1;
		} else {
			$returnHome = 0;
		}

		$_SESSION["End"] = 0;

		if (cleartemptrans() == 1) {

			// force cleartemptrans to finish before returning home.
			// Because returnHome() depends on javascript which
			// can be triggered independently of php

			if ($returnHome == 1) {
				$returnHome = 0;
				returnHome();
			}
		}

	}
}

function cleartemptrans() {


	$db = tDataConnect();

	if($_SESSION["msg"] == 2) {
		$_SESSION["msg"] = 99;
		sql_query("update localtemptrans set trans_status = 'X'", $db);
	}

	if ($_SESSION["DBMS"] == "mssql") {
		sql_query("exec clearTempTables", $db);
	} else {
		moveTempData();
		truncateTempTables();
	}

	sql_close($db);

	testremote();

	loadglobalvalues();	
	$_SESSION["transno"] = $_SESSION["transno"] + 1;
	setglobalvalue("TransNo", $_SESSION["transno"]);

	if ($_SESSION["TaxExempt"] != 0) {
		$_SESSION["TaxExempt"] = 0;
		setglobalvalue("TaxExempt", 0);
	}

	memberReset();
	transReset();
	printReset();

	getsubtotals();

	delete_file(remote_oux());
	delete_file(local_inx());

	return 1;
}


function truncateTempTables() {
	$connection = tDataConnect();
	$query1 = "truncate table localtemptrans";
	$query2 = "truncate table activitytemplog";

	sql_query($query1, $connection);
	sql_query($query2, $connection);

	sql_close($connection);
}

function moveTempData() {
	$connection = tDataConnect();
//	sql_query("call movetempdata()", $connection);

	sql_query("update localtemptrans set trans_type = 'T' where trans_subtype = 'CP'", $connection);
//	sql_query("update localtemptrans set trans_type = 'T', trans_subtype = 'IC' where upc in ('0000000008019', '0000000003031', '0000000001014')", $connection);
	sql_query("update localtemptrans set upc = 'DISCOUNT', description = upc, department = 0 where trans_status = 'S'", $connection);

	sql_query("insert into localtrans select * from localtemptrans", $connection);
	sql_query("insert into dtransactions select * from localtemptrans", $connection);

	sql_query("insert into activitylog select * from activitytemplog", $connection);
	sql_query("insert into alog select * from activitytemplog", $connection);


	sql_close($connection);
}
?>
</body>
