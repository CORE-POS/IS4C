<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
ini_set('display_errors','1');
include('../config.php'); 
include('util.php');
include('db.php');
$FILEPATH = $FANNIE_ROOT;
?>
<a href="index.php">Necessities</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="auth.php">Authentication</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
Members 
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="stores.php">Stores</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="update.php">Updates</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="sample_data/extra_data.php">Sample Data</a>
<form action=mem.php method=post>
<h1>Fannie Membership Settings</h1>
<?php
if (is_writable('../config.php')){
	echo "<span style=\"color:green;\"><i>config.php</i> is writeable</span>";
}
else {
	echo "<span style=\"color:red;\"><b>Error</b>: config.php is not writeable</span>";
}
?>
<br />
Names per membership
<?php
if (!isset($FANNIE_NAMES_PER_MEM)) $FANNIE_NAMES_PER_MEM = 1;
if (isset($_REQUEST['FANNIE_NAMES_PER_MEM'])) $FANNIE_NAMES_PER_MEM = $_REQUEST['FANNIE_NAMES_PER_MEM'];
confset('FANNIE_NAMES_PER_MEM',$FANNIE_NAMES_PER_MEM);
echo "<input type=text size=3 name=FANNIE_NAMES_PER_MEM value=\"$FANNIE_NAMES_PER_MEM\" />";
?>
<hr />
<b>Equity/Store Charge</b>:
<br />Equity Department(s): 
<?php
if (!isset($FANNIE_EQUITY_DEPARTMENTS)) $FANNIE_EQUITY_DEPARTMENTS = '';
if (isset($_REQUEST['FANNIE_EQUITY_DEPARTMENTS'])) $FANNIE_EQUITY_DEPARTMENTS=$_REQUEST['FANNIE_EQUITY_DEPARTMENTS'];
confset('FANNIE_EQUITY_DEPARTMENTS',"'$FANNIE_EQUITY_DEPARTMENTS'");
printf("<input type=\"text\" name=\"FANNIE_EQUITY_DEPARTMENTS\" value=\"%s\" />",$FANNIE_EQUITY_DEPARTMENTS);
?>
<br />Store Charge Department(s): 
<?php
if (!isset($FANNIE_AR_DEPARTMENTS)) $FANNIE_AR_DEPARTMENTS = '';
if (isset($_REQUEST['FANNIE_AR_DEPARTMENTS'])) $FANNIE_AR_DEPARTMENTS=$_REQUEST['FANNIE_AR_DEPARTMENTS'];
confset('FANNIE_AR_DEPARTMENTS',"'$FANNIE_AR_DEPARTMENTS'");
printf("<input type=\"text\" name=\"FANNIE_AR_DEPARTMENTS\" value=\"%s\" />",$FANNIE_AR_DEPARTMENTS);
?>
<hr />
<b>Enabled modules</b><br />
<?php
if (!isset($FANNIE_MEMBER_MODULES)) $FANNIE_MEMBER_MODULES = array();
if (isset($_REQUEST['FANNIE_MEMBER_MODULES'])){
	$FANNIE_MEMBER_MODULES = array();
	foreach($_REQUEST['FANNIE_MEMBER_MODULES'] as $m)
		$FANNIE_MEMBER_MODULES[] = $m;
}
$saveStr = 'array(';
foreach($FANNIE_MEMBER_MODULES as $m)
	$saveStr .= '"'.$m.'",';
$saveStr = rtrim($saveStr,",").")";
confset('FANNIE_MEMBER_MODULES',$saveStr);
?>
<select multiple name="FANNIE_MEMBER_MODULES[]" size="10">
<?php
$dh = opendir("../mem/modules");
$tmp = array();
while(($file = readdir($dh)) !== False){
	if (substr($file,-4) == ".php")
		$tmp[] = substr($file,0,strlen($file)-4);	
}
sort($tmp);
foreach($tmp as $module){
	printf("<option %s>%s</option>",(in_array($module,$FANNIE_MEMBER_MODULES)?'selected':''),$module);
}
?>
</select><br />
<a href="memModDisplay.php">Adjust Module Display Order</a>
<hr />
<input type=submit value="Re-run" />
</form>
<?php
$sql = db_test_connect($FANNIE_SERVER,$FANNIE_SERVER_DBMS,
		$FANNIE_TRANS_DB,$FANNIE_SERVER_USER,
		$FANNIE_SERVER_PW);
recreate_views($sql);

// rebuild views that depend on ar & equity
// department definitions
function recreate_views($con){
	global $FANNIE_TRANS_DB,$FANNIE_OP_DB,$FANNIE_SERVER_DBMS;

	$con->query("DROP VIEW memIouToday",$FANNIE_TRANS_DB);
	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'memIouToday','trans');

	$con->query("DROP VIEW newBalanceToday_cust",$FANNIE_TRANS_DB);
	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'newBalanceToday_cust','trans');

	$con->query("DROP VIEW stockSumToday",$FANNIE_TRANS_DB);
	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'stockSumToday','trans');

	$con->query("DROP VIEW newBalanceStockToday_test",$FANNIE_TRANS_DB);
	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'newBalanceStockToday_test','trans');

	$con->query("DROP VIEW dheader",$FANNIE_TRANS_DB);
	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'dheader','trans');

	$con->query("DROP VIEW ar_history_today",$FANNIE_TRANS_DB);
	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'ar_history_today','trans');
}
?>
