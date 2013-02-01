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

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	*  6Dec2012 Eric Lee Add FANNIE_MEMBER_UPC_PREFIX

*/

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
<a href="plugins.php">Plugins</a>
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
<b>Equity/Store Charge</b>
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
<b>Membership Information Modules</b> <br />
The Member editing interface displayed after you select a member at:
<br /><a href="/IS4C/fannie/mem/search.php" target="_mem">/IS4C/fannie/mem/search.php</a>
<br />consists of fields grouped in several sections, called modules, listed below.
<br />The enabled (active) ones are selected/highlighted.
<br />
<br /><b>Available Modules</b> <br />
<?php
if (!isset($FANNIE_MEMBER_MODULES)) $FANNIE_MEMBER_MODULES = array('ContactInfo','MemType');
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
Click or ctrl-Click or shift-Click to select/deselect modules for enablement.
<br /><br />
<a href="memModDisplay.php">Adjust Module Display Order</a>

<hr />
<b>Member Cards</b>
<br />Member Card UPC Prefix: 
<?php
if (!isset($FANNIE_MEMBER_UPC_PREFIX)) $FANNIE_MEMBER_UPC_PREFIX = '';
if (isset($_REQUEST['FANNIE_MEMBER_UPC_PREFIX'])) $FANNIE_MEMBER_UPC_PREFIX=$_REQUEST['FANNIE_MEMBER_UPC_PREFIX'];
confset('FANNIE_MEMBER_UPC_PREFIX',"'$FANNIE_MEMBER_UPC_PREFIX'");
printf("<input type=\"text\" name=\"FANNIE_MEMBER_UPC_PREFIX\" value=\"%s\" />",$FANNIE_MEMBER_UPC_PREFIX);
?>

<hr />
<b>Locale</b> <br />
Set the Country and Language where Fannie will run.
<br />If these are not set in Fannie configuration but are set in the Linux environment the environment values will be used as
defaults that can be overridden by settings here.

<br /><b>Country</b> <br />
<?php
// If the var doesn't exist in config.php assign a default value.
if (!isset($FANNIE_COUNTRY)) $FANNIE_COUNTRY = "";
// If the form var is set assign it to the local copy of the config var.
if (isset($_REQUEST['FANNIE_COUNTRY'])) $FANNIE_COUNTRY = $_REQUEST['FANNIE_COUNTRY'];
// Change or add the local copy to the config file.
confset('FANNIE_COUNTRY',"'$FANNIE_COUNTRY'");
if ( !isset($FANNIE_COUNTRY) && isset($_ENV['LANG']) ) {
	$FANNIE_COUNTRY = substr($_ENV['LANG'],3,2);
}
?>
<select name="FANNIE_COUNTRY" size='1'>
<?php
//Use I18N country codes.
$countries = array("US"=>"USA", "CA"=>"Canada");
foreach (array_keys($countries) as $key) {
	printf("<option value='%s' %s>%s</option>", $key, (($FANNIE_COUNTRY == $key)?'selected':''), $countries["$key"]);
}
?>
</select>

<br /><b>Language</b> <br />
<?php
// If the var doesn't exist in config.php assign a default value.
if (!isset($FANNIE_LANGUAGE)) $FANNIE_LANGUAGE = "";
// If the form var is set assign it to the local copy of the config var.
if (isset($_REQUEST['FANNIE_LANGUAGE'])) $FANNIE_LANGUAGE = $_REQUEST['FANNIE_LANGUAGE'];
// Change or add the local copy to the config file.
confset('FANNIE_LANGUAGE',"'$FANNIE_LANGUAGE'");
if ( !isset($FANNIE_LANGUAGE) && isset($_ENV['LANG']) ) {
	$FANNIE_LANGUAGE = substr($_ENV['LANG'],0,2);
}
?>
<select name="FANNIE_LANGUAGE" size='1'>
<?php
//Use I18N language codes.
$langs = array("en"=>"English", "fr"=>"French", "sp"=>"Spanish");
foreach (array_keys($langs) as $key) {
	printf("<option value='%s' %s>%s</option>", $key, (($FANNIE_LANGUAGE == $key)?'selected':''), $langs["$key"]);
}
?>
</select><br />

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

	$con->query("DROP VIEW ar_history_today_sum",$FANNIE_TRANS_DB);
	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'ar_history_today_sum','trans');

	$con->query("DROP VIEW ar_live_balance",$FANNIE_TRANS_DB);
	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'ar_live_balance','trans');

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
