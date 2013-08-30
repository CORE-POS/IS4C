<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

	DHermann test
*********************************************************************************/

ini_set('display_errors','1');

include(realpath(dirname(__FILE__).'/../lib/AutoLoader.php'));
AutoLoader::LoadMap();
if(file_exists((dirname(__FILE__).'/../ini.php')))
	include(realpath(dirname(__FILE__).'/../ini.php'));
include('util.php');
?>
<html>
<head>
<title>IT CORE Lane Installation: Necessities</title>
<style type="text/css">
body {
	line-height: 1.5em;
}
</style>
<script type="text/javascript" src="../js/jquery.js"></script>
</head>
<body>
<?php include('tabs.php'); ?>
<div id="wrapper">
<h2>IT CORE Lane Installation: Necessities</h2>

<form action=index.php method=post>

<div class="alert"><?php check_writeable('../ini.php', False, 'PHP'); ?></div>
<div class="alert"><?php check_writeable('../ini-local.php', True, 'PHP'); ?></div>

PHP is running as: <?php echo whoami(); ?><br />
<?php
if (!function_exists("socket_create")){
	echo '<b>Warning</b>: PHP socket extension is not enabled. NewMagellan will not work quite right';
}
?>
<br />
<table id="install" border=0 cellspacing=0 cellpadding=4>
<tr>
<td style="width: 30%;">OS: </td><td><select name=OS>
<?php
if (isset($_REQUEST['OS'])) $CORE_LOCAL->set('OS',$_REQUEST['OS'],True);
if ($CORE_LOCAL->get('OS') == 'win32'){
	echo "<option value=win32 selected>Windows</option>";
	echo "<option value=other>*nix</option>";
}
else {
	echo "<option value=win32>Windows</option>";
	echo "<option value=other selected>*nix</option>";
}
confsave('OS',"'".$CORE_LOCAL->get('OS')."'");
?>
</select></td></tr>
<tr><td>Lane number:</td><td>
<?php
if (isset($_REQUEST['LANE_NO']) && is_numeric($_REQUEST['LANE_NO'])) $CORE_LOCAL->set('laneno',$_REQUEST['LANE_NO'],True);
printf("<input type=text name=LANE_NO value=\"%d\" />",
	$CORE_LOCAL->get('laneno'));
confsave('laneno',$CORE_LOCAL->get('laneno'));
?>
</td></tr><tr><td colspan=2 class="tblheader">
<h3>Database set up</h3></td></tr>
<tr><td>
Lane database host: </td><td>
<?php
if (isset($_REQUEST['LANE_HOST'])) $CORE_LOCAL->set('localhost',$_REQUEST['LANE_HOST'],True);
printf("<input type=text name=LANE_HOST value=\"%s\" />",
	$CORE_LOCAL->get('localhost'));
confsave('localhost',"'".$CORE_LOCAL->get('localhost')."'");
?>
</td></tr><tr><td>
Lane database type:</td>
<td><select name=LANE_DBMS>
<?php
$db_opts = array('mysql'=>'MySQL','mssql'=>'SQL Server',
	'pdomysql'=>'MySQL (PDO)','pdomssql'=>'SQL Server (PDO)');
if(isset($_REQUEST['LANE_DBMS'])) $CORE_LOCAL->set('DBMS',$_REQUEST['LANE_DBMS'],True);
foreach($db_opts as $name=>$label){
	printf('<option %s value="%s">%s</option>',
		($CORE_LOCAL->get('DBMS')==$name?'selected':''),
		$name,$label);
}
confsave('DBMS',"'".$CORE_LOCAL->get('DBMS')."'");
?>
</select></td></tr>
<tr><td>Lane user name:</td><td>
<?php
if (isset($_REQUEST['LANE_USER'])) $CORE_LOCAL->set('localUser',$_REQUEST['LANE_USER'],True);
printf("<input type=text name=LANE_USER value=\"%s\" />",
	$CORE_LOCAL->get('localUser'));
confsave('localUser',"'".$CORE_LOCAL->get('localUser')."'");
?>
</td></tr><tr><td>
Lane password:</td><td>
<?php
if (isset($_REQUEST['LANE_PASS'])) $CORE_LOCAL->set('localPass',$_REQUEST['LANE_PASS'],True);
printf("<input type=password name=LANE_PASS value=\"%s\" />",
	$CORE_LOCAL->get('localPass'));
confsave('localPass',"'".$CORE_LOCAL->get('localPass')."'");
?>
</td></tr><tr><td>
Lane operational DB:</td><td>
<?php
if (isset($_REQUEST['LANE_OP_DB'])) $CORE_LOCAL->set('pDatabase',$_REQUEST['LANE_OP_DB'],True);
printf("<input type=text name=LANE_OP_DB value=\"%s\" />",
	$CORE_LOCAL->get('pDatabase'));
confsave('pDatabase',"'".$CORE_LOCAL->get('pDatabase')."'");
?>
</td></tr><tr><td colspan=2>
<div class="noteTxt">
Testing operational DB Connection:
<?php
$gotDBs = 0;
if ($CORE_LOCAL->get("DBMS") == "mysql")
	$val = ini_set('mysql.connect_timeout',5);

$sql = db_test_connect($CORE_LOCAL->get('localhost'),
		$CORE_LOCAL->get('DBMS'),
		$CORE_LOCAL->get('pDatabase'),
		$CORE_LOCAL->get('localUser'),
		$CORE_LOCAL->get('localPass'));
if ($sql === False){
	echo "<span class='fail'>Failed</span>";
	echo '<div class="db_hints" style="margin-left:25px;">';
	if (!function_exists('socket_create')){
		echo '<i>Try enabling PHP\'s socket extension in php.ini for better diagnostics</i>';
	}
	elseif (@MiscLib::pingport($CORE_LOCAL->get('localhost'),$CORE_LOCAL->get('DBMS'))){
		echo '<i>Database found at '.$CORE_LOCAL->get('localhost').'. Verify username and password
			and/or database account permissions.</i>';
	}
	else {
		echo '<i>Database does not appear to be listening for connections on '
			.$CORE_LOCAL->get('localhost').'. Verify host is correct, database is running and
			firewall is allowing connections.</i>';
	}
	echo '</div>';
}
else {
	echo "<span class='success'>Succeeded</span><br />";
	//echo "<textarea rows=3 cols=80>";
	$opErrors = create_op_dbs($sql,$CORE_LOCAL->get('DBMS'));
	$gotDBs++;
	if (!empty($opErrors)){
		echo '<div class="db_create_errors" style="border: solid 1px red;padding:5px;">';
		echo 'There were some errors creating operational DB structure';
		echo '<ul style="margin-top:2px;">';
		foreach($opErrors as $error){
			echo '<li>';	
			echo 'Error on structure <b>'.$error['struct'].'</b>. ';
			printf('<a href="" onclick="$(\'#eDetails%s\').toggle();return false;">Details</a>',
				$error['struct']);
			printf('<ul style="display:none;" id="eDetails%s">',$error['struct']);
			echo '<li>Query: <pre>'.$error['query'].'</pre></li>';
			echo '<li>Error Message: '.$error['details'].'</li>';
			echo '</ul>';
			echo '</li>';
		}
		echo '</div>';
	}
	//echo "</textarea>";
}
?>
</div> <!-- noteTxt -->
</td></tr><tr><td>
Lane transaction DB:</td><td>
<?php
if (isset($_REQUEST['LANE_TRANS_DB'])) $CORE_LOCAL->set('tDatabase',$_REQUEST['LANE_TRANS_DB'],True);
printf("<input type=text name=LANE_TRANS_DB value=\"%s\" />",
	$CORE_LOCAL->get('tDatabase'));
confsave('tDatabase',"'".$CORE_LOCAL->get('tDatabase')."'");
?>
</td></tr><tr><td colspan=2>
<div class="noteTxt">
Testing transactional DB connection:
<?php
$sql = db_test_connect($CORE_LOCAL->get('localhost'),
		$CORE_LOCAL->get('DBMS'),
		$CORE_LOCAL->get('tDatabase'),
		$CORE_LOCAL->get('localUser'),
		$CORE_LOCAL->get('localPass'));
if ($sql === False ){
	echo "<span class='fail'>Failed</span>";
	echo '<div class="db_hints" style="margin-left:25px;">';
	echo '<i>If both connections failed, see above. If just this one
		is failing, it\'s probably an issue of database user 
		permissions.</i>';
	echo '</div>';
}
else {
	echo "<span class='success'>Succeeded</span><br />";
	//echo "<textarea rows=3 cols=80>";
	

	/* Re-do tax rates here so changes affect the subsequent
	 * ltt* view builds. 
	 */
	if (isset($_REQUEST['TAX_RATE']) && $sql->table_exists('taxrates')){
		$queries = array();
		for($i=0; $i<count($_REQUEST['TAX_RATE']); $i++){
			$rate = $_REQUEST['TAX_RATE'][$i];
			$desc = $_REQUEST['TAX_DESC'][$i];
			if(is_numeric($rate)){
				$desc = str_replace(" ","",$desc);
				$queries[] = sprintf("INSERT INTO taxrates VALUES 
					(%d,%f,'%s')",$i+1,$rate,$desc);
			}
			else if ($rate != ""){
				echo "<br /><b>Error</b>: the given
					tax rate, $rate, doesn't seem to
					be a number.";
			}
			$sql->query("TRUNCATE TABLE taxrates");
			foreach($queries as $q)
				$sql->query($q);
		}
	}

	$transErrors = create_trans_dbs($sql,$CORE_LOCAL->get('DBMS'));
	$gotDBs++;
	if (!empty($transErrors)){
		echo '<div class="db_create_errors" style="border: solid 1px red;padding:5px;">';
		echo 'There were some errors creating transactional DB structure';
		echo '<ul style="margin-top:2px;">';
		foreach($transErrors as $error){
			echo '<li>';	
			echo 'Error on structure <b>'.$error['struct'].'</b>. ';
			printf('<a href="" onclick="$(\'#eDetails%s\').toggle();return false;">Details</a>',
				$error['struct']);
			printf('<ul style="display:none;" id="eDetails%s">',$error['struct']);
			echo '<li>Query: <pre>'.$error['query'].'</pre></li>';
			echo '<li>Error Message: '.$error['details'].'</li>';
			echo '</ul>';
			echo '</li>';
		}
		echo '</div>';
	}
	//echo "</textarea>";
}
?>
</div> <!-- noteTxt -->
</td></tr><tr><td>
Server database host: </td><td>
<?php
if (isset($_REQUEST['SERVER_HOST'])) $CORE_LOCAL->set('mServer',$_REQUEST['SERVER_HOST'],True);
printf("<input type=text name=SERVER_HOST value=\"%s\" />",
	$CORE_LOCAL->get('mServer'));
confsave('mServer',"'".$CORE_LOCAL->get('mServer')."'");
?>
</td></tr><tr><td>
Server database type:</td><td>
<select name=SERVER_TYPE>
<?php
$db_opts = array('mysql'=>'MySQL','mssql'=>'SQL Server',
	'pdomysql'=>'MySQL (PDO)','pdomssql'=>'SQL Server (PDO)');
if (isset($_REQUEST['SERVER_TYPE'])) $CORE_LOCAL->set('mDBMS',$_REQUEST['SERVER_TYPE'],True);
foreach($db_opts as $name=>$label){
	printf('<option %s value="%s">%s</option>',
		($CORE_LOCAL->get('mDBMS')==$name?'selected':''),
		$name,$label);
}
confsave('mDBMS',"'".$CORE_LOCAL->get('mDBMS')."'");
?>
</select></td></tr><tr><td>
Server user name:</td><td>
<?php
if (isset($_REQUEST['SERVER_USER'])) $CORE_LOCAL->set('mUser',$_REQUEST['SERVER_USER'],True);
printf("<input type=text name=SERVER_USER value=\"%s\" />",
	$CORE_LOCAL->get('mUser'));
confsave('mUser',"'".$CORE_LOCAL->get('mUser')."'");
?>
</td></tr><tr><td>
Server password:</td><td>
<?php
if (isset($_REQUEST['SERVER_PASS'])) $CORE_LOCAL->set('mPass',$_REQUEST['SERVER_PASS'],True);
printf("<input type=password name=SERVER_PASS value=\"%s\" />",
	$CORE_LOCAL->get('mPass'));
confsave('mPass',"'".$CORE_LOCAL->get('mPass')."'");
?>
</td></tr><tr><td>
Server database name:</td><td>
<?php
if (isset($_REQUEST['SERVER_DB'])) $CORE_LOCAL->set('mDatabase',$_REQUEST['SERVER_DB'],True);
printf("<input type=text name=SERVER_DB value=\"%s\" />",
	$CORE_LOCAL->get('mDatabase'));
confsave('mDatabase',"'".$CORE_LOCAL->get('mDatabase')."'");
?>
</td></tr><tr><td colspan=2>
<div class="noteTxt">
Testing server connection:
<?php
$sql = db_test_connect($CORE_LOCAL->get('mServer'),
		$CORE_LOCAL->get('mDBMS'),
		$CORE_LOCAL->get('mDatabase'),
		$CORE_LOCAL->get('mUser'),
		$CORE_LOCAL->get('mPass'));
if ($sql === False){
	echo "<span class='fail'>Failed</span>";
	echo '<div class="db_hints" style="margin-left:25px;width:350px;">';
	if (!function_exists('socket_create')){
		echo '<i>Try enabling PHP\'s socket extension in php.ini for better diagnostics</i>';
	}
	elseif (@MiscLib::pingport($CORE_LOCAL->get('mServer'),$CORE_LOCAL->get('DBMS'))){
		echo '<i>Database found at '.$CORE_LOCAL->get('mServer').'. Verify username and password
			and/or database account permissions.</i>';
	}
	else {
		echo '<i>Database does not appear to be listening for connections on '
			.$CORE_LOCAL->get('mServer').'. Verify host is correct, database is running and
			firewall is allowing connections.</i>';
	}
	echo '</div>';
}
else {
	echo "<span class='success'>Succeeded</span><br />";
	//echo "<textarea rows=3 cols=80>";
	$sErrors = create_min_server($sql,$CORE_LOCAL->get('mDBMS'));
	if (!empty($sErrors)){
		echo '<div class="db_create_errors" style="border: solid 1px red;padding:5px;">';
		echo 'There were some errors creating transactional DB structure';
		echo '<ul style="margin-top:2px;">';
		foreach($sErrors as $error){
			echo '<li>';	
			echo 'Error on structure <b>'.$error['struct'].'</b>. ';
			printf('<a href="" onclick="$(\'#eDetails%s\').toggle();return false;">Details</a>',
				$error['struct']);
			printf('<ul style="display:none;" id="eDetails%s">',$error['struct']);
			echo '<li>Query: <pre>'.$error['query'].'</pre></li>';
			echo '<li>Error Message: '.$error['details'].'</li>';
			echo '</ul>';
			echo '</li>';
		}
		echo '</div>';
	}
	//echo "</textarea>";
}
?>
</div>  <!-- noteTxt -->
</td></tr><tr><td colspan=2 class="tblHeader">
<h3>Tax</h3></td></tr>
<tr><td colspan=2>
<p><i>Provided tax rates are used to create database views. As such,
descriptions should be DB-legal syntax (e.g., no spaces). A rate of
0% with ID 0 is automatically included. Enter exact values - e.g.,
0.05 to represent 5%.</i></p></td></tr>
<tr><td colspan=2>
<?php
$rates = array();
if($gotDBs == 2){
	$sql = new SQLManager($CORE_LOCAL->get('localhost'),
			$CORE_LOCAL->get('DBMS'),
			$CORE_LOCAL->get('tDatabase'),
			$CORE_LOCAL->get('localUser'),
			$CORE_LOCAL->get('localPass'));
	$ratesR = $sql->query("SELECT id,rate,description FROM taxrates ORDER BY id");
	while($row=$sql->fetch_row($ratesR))
		$rates[] = array($row[0],$row[1],$row[2]);
}
echo "<table><tr><th>ID</th><th>Rate</th><th>Description</th></tr>";
foreach($rates as $rate){
	printf("<tr><td>%d</td><td><input type=text name=TAX_RATE[] value=\"%f\" /></td>
		<td><input type=text name=TAX_DESC[] value=\"%s\" /></td></tr>",
		$rate[0],$rate[1],$rate[2]);
}
printf("<tr><td>(Add)</td><td><input type=text name=TAX_RATE[] value=\"\" /></td>
	<td><input type=text name=TAX_DESC[] value=\"\" /></td></tr></table>");
?>
</td></tr><tr><td colspan=2 class="submitBtn">
<input type=submit value="Save &amp; Re-run installation checks" />
</form>
</td></tr>
</table>
</div> <!--	wrapper -->
<?php

function create_op_dbs($db,$type){
	global $CORE_LOCAL;
	$name = $CORE_LOCAL->get('pDatabase');
	$errors = array();

	create_if_needed($db, $type, $name, 'couponcodes', 'op', $errors);
	$chk = $db->query('SELECT Code FROM couponcodes', $name);
	if ($db->num_rows($chk) == 0){
		load_sample_data($db,'couponcodes');
	}

	create_if_needed($db, $type, $name, 'custdata', 'op', $errors);

	create_if_needed($db, $type, $name, 'memberCards', 'op', $errors);

	create_if_needed($db, $type, $name, 'custPreferences', 'op', $errors);

	$cardsViewQ = "CREATE VIEW memberCardsView AS 
		SELECT CONCAT('" . $CORE_LOCAL->get('memberUpcPrefix') . "',c.CardNo) as upc, c.CardNo as card_no FROM custdata c";
	if (!$db->table_exists('memberCardsView',$name)){
		db_structure_modify($db,'memberCardsView',$cardsViewQ,$errors);
	}
	
	create_if_needed($db, $type, $name, 'departments', 'op', $errors);

	create_if_needed($db, $type, $name, 'employees', 'op', $errors);

	create_if_needed($db, $type, $name, 'globalvalues', 'op', $errors);
	$chk = $db->query('SELECT CashierNo FROM globalvalues', $name);
	if ($db->num_rows($chk) != 1){
		$db->query('TRUNCATE TABLE globalvalues');
		load_sample_data($db,'globalvalues');
	}

	create_if_needed($db, $type, $name, 'drawerowner', 'op', $errors);
	$chk = $db->query('SELECT drawer_no FROM drawerowner', $name);
	if ($db->num_rows($chk) == 0){
		$db->query('INSERT INTO drawerowner (drawer_no) VALUES (1)', $name);
		$db->query('INSERT INTO drawerowner (drawer_no) VALUES (2)', $name);
	}

	create_if_needed($db, $type, $name, 'products', 'op', $errors);

	create_if_needed($db, $type, $name, 'dateRestrict', 'op', $errors);

	create_if_needed($db, $type, $name, 'tenders', 'op', $errors);
	$chk = $db->query('SELECT TenderID FROM tenders', $name);
	if ($db->num_rows($chk) == 0){
		load_sample_data($db,'tenders');
	}

	create_if_needed($db, $type, $name, 'subdepts', 'op', $errors);

	create_if_needed($db, $type, $name, 'customReceipt', 'op', $errors);

	create_if_needed($db, $type, $name, 'custReceiptMessage', 'op', $errors);

	create_if_needed($db, $type, $name, 'disableCoupon', 'op', $errors);

	create_if_needed($db, $type, $name, 'houseCoupons', 'op', $errors);

	create_if_needed($db, $type, $name, 'houseVirtualCoupons', 'op', $errors);

	create_if_needed($db, $type, $name, 'houseCouponItems', 'op', $errors);

	create_if_needed($db, $type, $name, 'memchargebalance', 'op', $errors);

	create_if_needed($db, $type, $name, 'unpaid_ar_today', 'op', $errors);

	// Update lane_config structure if needed
	if ($db->table_exists('lane_config', $name)){
		$def = $db->table_definition('lane_config', $name);
		if (!isset($def['keycode']) || !isset($def['value']))
			$db->query('DROP TABLE lane_config', $name);
	}
	create_if_needed($db, $type, $name, 'lane_config', 'op', $errors);
	
	return $errors;
}

function create_trans_dbs($db,$type){
	global $CORE_LOCAL;
	$name = $CORE_LOCAL->get('tDatabase');
	$errors = array();

	create_if_needed($db, $type, $name, 'activities', 'trans', $errors);

	create_if_needed($db, $type, $name, 'alog', 'trans', $errors);

	create_if_needed($db, $type, $name, 'activitylog', 'trans', $errors);

	create_if_needed($db, $type, $name, 'activitytemplog', 'trans', $errors);

	create_if_needed($db, $type, $name, 'dtransactions', 'trans', $errors);

	create_if_needed($db, $type, $name, 'localtrans', 'trans', $errors);

	create_if_needed($db, $type, $name, 'localtransarchive', 'trans', $errors);

	create_if_needed($db, $type, $name, 'localtrans_today', 'trans', $errors);

	create_if_needed($db, $type, $name, 'suspended', 'trans', $errors);

	create_if_needed($db, $type, $name, 'localtemptrans', 'trans', $errors);

	create_if_needed($db, $type, $name, 'taxrates', 'trans', $errors);

	create_if_needed($db, $type, $name, 'localtranstoday', 'trans', $errors);

	create_if_needed($db, $type, $name, 'memdiscountadd', 'trans', $errors);

	create_if_needed($db, $type, $name, 'memdiscountremove', 'trans', $errors);

	create_if_needed($db, $type, $name, 'screendisplay', 'trans', $errors);

	create_if_needed($db, $type, $name, 'staffdiscountadd', 'trans', $errors);

	create_if_needed($db, $type, $name, 'staffdiscountremove', 'trans', $errors);

	create_if_needed($db, $type, $name, 'suspendedtoday', 'trans', $errors);

	create_if_needed($db, $type, $name, 'couponApplied', 'trans', $errors);

	/* lttsummary, lttsubtotals, and subtotals
	 * always get rebuilt to account for tax rate
	 * changes */
	include('buildLTTViews.php');
	$errors = buildLTTViews($db,$type,$errors);

	create_if_needed($db, $type, $name, 'taxView', 'trans', $errors);

	$lttR = "CREATE view ltt_receipt as 
		select
		l.description,
		case 
			when voided = 5 
				then 'Discount'
			when trans_status = 'M'
				then 'Mbr special'
			when trans_status = 'S'
				then 'Staff special'
			when unitPrice = 0.01
				then ''
			when scale <> 0 and quantity <> 0 
				then concat(quantity, ' @ ', unitPrice)
			when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity = 1
				then concat(volume, ' /', unitPrice)
			when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity <> 1
				then concat(Quantity, ' @ ', Volume, ' /', unitPrice)
			when abs(itemQtty) > 1 and discounttype = 3
				then concat(ItemQtty, ' /', UnitPrice)
			when abs(itemQtty) > 1
				then concat(quantity, ' @ ', unitPrice)	
			when matched > 0
				then '1 w/ vol adj'
			else ''
		end
		as comment,
		total,
		case 
			when trans_status = 'V' 
				then 'VD'
			when trans_status = 'R'
				then 'RF'
			when tax = 1 and foodstamp <> 0
				then 'TF'
			when tax = 1 and foodstamp = 0
				then 'T' 
			when tax = 0 and foodstamp <> 0
				then 'F'
			WHEN (tax > 1 and foodstamp <> 0)
				THEN CONCAT(LEFT(t.description,1),'F')
			WHEN (tax > 1 and foodstamp = 0)
				THEN LEFT(t.description,1)
			when tax = 0 and foodstamp = 0
				then '' 
		end
		as Status,
		trans_type,
		unitPrice,
		voided,
		CASE 
			WHEN upc = 'DISCOUNT' THEN (
			SELECT MAX(trans_id) FROM localtemptrans WHERE voided=3
			)-1
			WHEN trans_type = 'T' THEN trans_id+99999	
			ELSE trans_id
		END AS trans_id
		from localtemptrans as l
		left join taxrates as t
		on l.tax = t.id
		where voided <> 5 and UPC <> 'TAX'
		AND trans_type <> 'L'";
	if($type == 'mssql'){
		$lttR = "CREATE view ltt_receipt as 
			select
			l.description,
			case 
				when voided = 5 
					then 'Discount'
				when trans_status = 'M'
					then 'Mbr special'
				when trans_status = 'S'
					then 'Staff special'
				when unitPrice = 0.01
					then ''
				when scale <> 0 and quantity <> 0 
					then quantity+ ' @ '+ unitPrice
				when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity = 1
					then volume+ ' /'+ unitPrice
				when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity <> 1
					then Quantity+ ' @ '+Volume+ ' /'+ unitPrice
				when abs(itemQtty) > 1 and discounttype = 3
					then ItemQtty+ ' /'+ UnitPrice
				when abs(itemQtty) > 1
					then quantity+' @ '+unitPrice
				when matched > 0
					then '1 w/ vol adj'
				else ''
			end
			as comment,
			total,
			case 
				when trans_status = 'V' 
					then 'VD'
				when trans_status = 'R'
					then 'RF'
				when tax = 1 and foodstamp <> 0
					then 'TF'
				when tax = 1 and foodstamp = 0
					then 'T' 
				WHEN (tax > 1 and foodstamp <> 0)
					THEN LEFT(t.description,1)+'F'
				WHEN (tax > 1 and foodstamp = 0)
					THEN LEFT(t.description,1)
				when tax = 0 and foodstamp <> 0
					then 'F'
				when tax = 0 and foodstamp = 0
					then '' 
			end
			as Status,
			trans_type,
			unitPrice,
			trans_id
			CASE 
				WHEN upc = 'DISCOUNT' THEN (
				SELECT MAX(trans_id) FROM localtemptrans WHERE voided=3
				)-1
				WHEN trans_type = 'T' THEN trans_id+99999	
				ELSE trans_id
			END AS trans_id
			from localtemptrans as l
			left join taxrates as t
			on l.tax = t.id
			where voided <> 5 and UPC <> 'TAX'
			AND trans_type <> 'L'
			order by trans_id";
	}
	db_structure_modify($db,'ltt_receipt','DROP VIEW ltt_receipt',$errors);
	if(!$db->table_exists('ltt_receipt',$name)){
		db_structure_modify($db,'ltt_receipt',$lttR,$errors);
	}

	$rV = "CREATE view receipt as
		select
		case 
			when trans_type = 'T'
				then 	concat(right( concat(space(44), upper(rtrim(Description)) ), 44) 
					, right(concat( space(8), format(-1 * Total, 2)), 8) 
					, right(concat(space(4), status), 4))
			when voided = 3 
				then 	concat(left(concat(Description, space(30)), 30) 
					, space(9) 
					, 'TOTAL' 
					, right(concat(space(8), format(UnitPrice, 2)), 8))
			when voided = 2
				then 	description
			when voided = 4
				then 	description
			when voided = 6
				then 	description
			when voided = 7 or voided = 17
				then 	concat(left(concat(Description, space(30)), 30) 
					, space(14) 
					, right(concat(space(8), format(unitPrice, 2)), 8) 
					, right(concat(space(4), status), 4))
			else
				concat(left(concat(Description, space(30)), 30)
				, ' ' 
				, left(concat(Comment, space(13)), 13) 
				, right(concat(space(8), format(Total, 2)), 8) 
				, right(concat(space(4), status), 4))
		end
		as linetoprint
		from ltt_receipt
		order by trans_id";
	if($type == 'mssql'){
		$rV = "CREATE  view receipt as
		select top 100 percent
		case 
			when trans_type = 'T'
				then 	right((space(44) + upper(rtrim(Description))), 44) 
					+ right((space(8) + convert(varchar, (-1 * Total))), 8) 
					+ right((space(4) + status), 4)
			when voided = 3 
				then 	left(Description + space(30), 30) 
					+ space(9) 
					+ 'TOTAL' 
					+ right(space(8) + convert(varchar, UnitPrice), 8)
			when voided = 2
				then 	description
			when voided = 4
				then 	description
			when voided = 6
				then 	description
			when voided = 7 or voided = 17
				then 	left(Description + space(30), 30) 
					+ space(14) 
					+ right(space(8) + convert(varchar, UnitPrice), 8) 
					+ right(space(4) + status, 4)
			when sequence < 1000
				then 	description
			else
				left(Description + space(30), 30)
				+ ' ' 
				+ left(Comment + space(13), 13) 
				+ right(space(8) + convert(varchar, Total), 8) 
				+ right(space(4) + status, 4)
		end
		as linetoprint,
		sequence
		from ltt_receipt
		order by sequence";
	}
	if(!$db->table_exists('receipt',$name)){
		db_structure_modify($db,'receipt',$rV,$errors);
	}

	$rpheader = "CREATE VIEW rp_receipt_header AS
		select
		min(datetime) as dateTimeStamp,
		card_no as memberID,
		register_no,
		emp_no,
		trans_no,
		convert(sum(case when discounttype = 1 then discount else 0 end),decimal(10,2)) as discountTTL,
		convert(sum(case when discounttype = 2 then memDiscount else 0 end),decimal(10,2)) as memSpecial,
		case when (min(datetime) is null) then 0 else
			sum(CASE WHEN discounttype = 4 THEN memDiscount ELSE 0 END)
		end as staffSpecial,
		convert(sum(case when upc = '0000000008005' then total else 0 end),decimal(10,2)) as couponTotal,
		convert(sum(case when upc = 'MEMCOUPON' then unitPrice else 0 end),decimal(10,2)) as memCoupon,
		abs(sum(case when trans_subtype = 'MI' or trans_subtype = 'CX' then total else 0 end)) as chargeTotal,
		sum(case when upc = 'Discount' then total else 0 end) as transDiscount,
		sum(case when trans_type = 'T' then -1 * total else 0 end) as tenderTotal
		from localtranstoday
		WHERE trans_type <> 'L'
		group by register_no, emp_no, trans_no, card_no";
	if($type == 'mssql'){
		$rpheader = "CREATE view rp_receipt_header as
		select
		min(datetime) as dateTimeStamp,
		card_no as memberID,
		register_no,
		emp_no,
		trans_no,
		convert(numeric(10,2), sum(case when discounttype = 1 then discount else 0 end)) as discountTTL,
		convert(numeric(10,2), sum(case when discounttype = 2 then memDiscount else 0 end)) as memSpecial,
		case when (min(datetime) is null) then 0 else
			sum(CASE WHEN discounttype = 4 THEN memDiscount ELSE 0 END)
		end as staffSpecial,
		convert(numeric(10,2), sum(case when upc = '0000000008005' then total else 0 end)) as couponTotal,
		convert(numeric(10,2), sum(case when upc = 'MEMCOUPON' then unitPrice else 0 end)) as memCoupon,
		abs(sum(case when trans_subtype = 'MI' or trans_subtype = 'CX' then total else 0 end)) as chargeTotal,
		sum(case when upc = 'Discount' then total else 0 end) as transDiscount,
		sum(case when trans_type = 'T' then -1 * total else 0 end) as tenderTotal
		from localtranstoday
		WHERE trans_type <> 'L'
		group by register_no, emp_no, trans_no, card_no";
	}
	if(!$db->table_exists('rp_receipt_header',$name)){
		db_structure_modify($db,'rp_receipt_header',$rpheader,$errors);
	}

	$rplttR = "CREATE view rp_ltt_receipt as 
		select
		register_no,
		emp_no,
		trans_no,
		l.description,
		case 
			when voided = 5 
				then 'Discount'
			when trans_status = 'M'
				then 'Mbr special'
			when trans_status = 'S'
				then 'Staff special'
			when unitPrice = 0.01
				then ''
			when scale <> 0 and quantity <> 0 
				then concat(quantity, ' @ ', unitPrice)
			when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity = 1
				then concat(volume, ' /', unitPrice)
			when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity <> 1
				then concat(Quantity, ' @ ', Volume, ' /', unitPrice)
			when abs(itemQtty) > 1 and discounttype = 3
				then concat(ItemQtty, ' /', UnitPrice)
			when abs(itemQtty) > 1
				then concat(quantity, ' @ ', unitPrice)	
			when matched > 0
				then '1 w/ vol adj'
			else ''
		end
		as comment,
		total,
		case 
			when trans_status = 'V' 
				then 'VD'
			when trans_status = 'R'
				then 'RF'
			WHEN (tax = 1 and foodstamp <> 0)
				THEN 'TF'
			WHEN (tax = 1 and foodstamp = 0)
				THEN 'T' 
			WHEN (tax > 1 and foodstamp <> 0)
				THEN CONCAT(LEFT(t.description,1),'F')
			WHEN (tax > 1 and foodstamp = 0)
				THEN LEFT(t.description,1)
			when tax = 0 and foodstamp <> 0
				then 'F'
			when tax = 0 and foodstamp = 0
				then '' 
		end
		as Status,
		trans_type,
		unitPrice,
		voided,
		trans_id
		from localtranstoday as l
		left join taxrates as t
		on l.tax = t.id
		where voided <> 5 and UPC <> 'TAX' and UPC <> 'DISCOUNT'
		AND trans_type <> 'L'
		order by emp_no, trans_no, trans_id";
	if($type == 'mssql'){
		$rplttR = "CREATE view rp_ltt_receipt as 
			select
			register_no,
			emp_no,
			trans_no,
			description,
			case 
				when voided = 5 
					then 'Discount'
				when trans_status = 'M'
					then 'Mbr special'
				when trans_status = 'S'
					then 'Staff special'
				when unitPrice = 0.01
					then ''
				when scale <> 0 and quantity <> 0 
					then quantity+ ' @ '+ unitPrice
				when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity = 1
					then volume+ ' /'+ unitPrice
				when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity <> 1
					then Quantity+ ' @ '+ Volume+ ' /'+ unitPrice
				when abs(itemQtty) > 1 and discounttype = 3
					then ItemQtty+' /'+ UnitPrice
				when abs(itemQtty) > 1
					then quantity+ ' @ '+ unitPrice
				when matched > 0
					then '1 w/ vol adj'
				else ''
			end
			as comment,
			total,
			case 
				when trans_status = 'V' 
					then 'VD'
				when trans_status = 'R'
					then 'RF'
				WHEN (tax = 1 and foodstamp <> 0)
					THEN 'TF'
				WHEN (tax = 1 and foodstamp = 0)
					THEN 'T' 
				WHEN (tax > 1 and foodstamp <> 0)
					THEN LEFT(t.description,1)+'F'
				WHEN (tax > 1 and foodstamp = 0)
					THEN LEFT(t.description,1)
				when tax = 0 and foodstamp <> 0
					then 'F'
				when tax = 0 and foodstamp = 0
					then '' 
			end
			as Status,
			trans_type,
			unitPrice,
			voided,
			trans_id
			from localtranstoday as l
			left join taxrates as t
			on l.tax = t.id
			where voided <> 5 and UPC <> 'TAX' and UPC <> 'DISCOUNT'
			AND trans_type <> 'L'
			order by emp_no, trans_no, trans_id";
	}
	db_structure_modify($db,'rp_ltt_receipt','DROP VIEW rp_ltt_receipt',$errors);
	if(!$db->table_exists('rp_ltt_receipt',$name)){
		db_structure_modify($db,'rp_ltt_receipt',$rplttR,$errors);
	}

	$rprV = "CREATE view rp_receipt  as
		select
		register_no,
		emp_no,
		trans_no,
		case 
			when trans_type = 'T'
				then 	concat(right( concat(space(44), upper(rtrim(Description)) ), 44) 
					, right(concat( space(8), format(-1 * Total, 2)), 8) 
					, right(concat(space(4), status), 4))
			when voided = 3 
				then 	concat(left(concat(Description, space(30)), 30) 
					, space(9) 
					, 'TOTAL' 
					, right(concat(space(8), format(UnitPrice, 2)), 8))
			when voided = 2
				then 	description
			when voided = 4
				then 	description
			when voided = 6
				then 	description
			when voided = 7 or voided = 17
				then 	concat(left(concat(Description, space(30)), 30) 
					, space(14) 
					, right(concat(space(8), format(UnitPrice, 2)), 8) 
					, right(concat(space(4), status), 4))
			else
				concat(left(concat(Description, space(30)), 30)
				, ' ' 
				, left(concat(Comment, space(13)), 13) 
				, right(concat(space(8), format(Total, 2)), 8) 
				, right(concat(space(4), status), 4))
		end
		as linetoprint,
		trans_id
		from rp_ltt_receipt";
	if($type == 'mssql'){
		$rprV = "CREATE view rp_receipt  as
		select
		register_no,
		emp_no,
		trans_no,
		case 
			when trans_type = 'T'
				then 	right((space(44) + upper(rtrim(Description))), 44) 
					+ right((space(8) + convert(varchar, (-1 * Total))), 8) 
					+ right((space(4) + status), 4)
			when voided = 3 
				then 	left(Description + space(30), 30) 
					+ space(9) 
					+ 'TOTAL' 
					+ right(space(8) + convert(varchar, UnitPrice), 8)
			when voided = 2
				then 	description
			when voided = 4
				then 	description
			when voided = 6
				then 	description
			when voided = 7 or voided = 17
				then 	left(Description + space(30), 30) 
					+ space(14) 
					+ right(space(8) + convert(varchar, UnitPrice), 8) 
					+ right(space(4) + status, 4)
			else
				left(Description + space(30), 30)
				+ ' ' 
				+ left(Comment + space(13), 13) 
				+ right(space(8) + convert(varchar, Total), 8) 
				+ right(space(4) + status, 4)
		end
		as linetoprint,
		trans_id
		from rp_ltt_receipt";
	}
	if(!$db->table_exists('rp_receipt',$name)){
		db_structure_modify($db,'rp_receipt',$rprV,$errors);
	}

	create_if_needed($db, $type, $name, 'efsnetRequest', 'trans', $errors);

	create_if_needed($db, $type, $name, 'efsnetRequestMod', 'trans', $errors);

	create_if_needed($db, $type, $name, 'efsnetResponse', 'trans', $errors);

	create_if_needed($db, $type, $name, 'efsnetTokens', 'trans', $errors);

	create_if_needed($db, $type, $name, 'valutecRequest', 'trans', $errors);

	create_if_needed($db, $type, $name, 'valutecRequestMod', 'trans', $errors);

	create_if_needed($db, $type, $name, 'valutecResponse', 'trans', $errors);

	$ccV = "CREATE view ccReceiptView 
		AS 
		select
		  (case r.mode
		    when 'tender' then 'Credit Card Purchase'
		    when 'retail_sale' then 'Credit Card Purchase'
		    when 'Credit_Sale' then 'Credit Card Purchase'
		    when 'retail_alone_credit' then 'Credit Card Refund'
		    when 'Credit_Return' then 'Credit Card Refund'
		    when 'refund' then 'Credit Card Refund'
		    else ''
		  end) as tranType,
		  (case r.mode 
		    when 'refund' then -1*r.amount
		    else r.amount
		  end) as amount,
		  r.PAN,
		  (case r.manual when 1 then 'Manual' else 'Swiped' end) as entryMethod,
		  r.issuer,
		  r.name,
		  s.xResultMessage,
		  s.xApprovalNumber, 
		  s.xTransactionID, 
		  r.date, r.cashierNo, r.laneNo, r.transNo, r.transID, r.datetime,
		  0 as sortorder
		from efsnetRequest r
		join efsnetResponse s
		  on s.date=r.date
		  and s.cashierNo=r.cashierNo
		  and s.laneNo=r.laneNo
		  and s.transNo=r.transNo
		  and s.transID=r.transID
		where s.validResponse=1 and 
		(s.xResultMessage like '%APPROVE%' or s.xResultMessage like '%PENDING%')

		union all

		select
		  (case r.mode
		    when 'tender' then 'Credit Card Purchase CANCELED'
		    when 'retail_sale' then 'Credit Card Purchase CANCELLED'
		    when 'Credit_Sale' then 'Credit Card Purchase CANCELLED'
		    when 'retail_alone_credit' then 'Credit Card Refund CANCELLED'
		    when 'Credit_Return' then 'Credit Card Refund CANCELLED'
		    when 'refund' then 'Credit Card Refund CANCELED'
		    else ''
		  end) as tranType,
		  (case r.mode when 'refund' then r.amount else -1*r.amount end) as amount,  
		  r.PAN,
		  (case r.manual when 1 then 'Manual' else 'Swiped' end) as entryMethod,
		  r.issuer,
		  r.name,
		  s.xResultMessage,
		  s.xApprovalNumber,
		  s.xTransactionID,
		  r.date, r.cashierNo, r.laneNo, r.transNo, r.transID, r.datetime,
		  1 as sortorder
		from efsnetRequestMod m
		join efsnetRequest r
		  on r.date=m.date
		  and r.cashierNo=m.cashierNo
		  and r.laneNo=m.laneNo
		  and r.transNo=m.transNo
		  and r.transID=m.transID
		join efsnetResponse s
		  on s.date=r.date
		  and s.cashierNo=r.cashierNo
		  and s.laneNo=r.laneNo
		  and s.transNo=r.transNo
		  and s.transID=r.transID
		where s.validResponse=1 and (s.xResultMessage like '%APPROVE%')
		  and m.validResponse=1 and 
		  (m.xResponseCode=0 or m.xResultMessage like '%APPROVE%')
		  and m.mode='void'";
	if(!$db->table_exists('ccReceiptView',$name)){
		db_structure_modify($db,'ccReceiptView',$ccV,$errors);
	}

	$gcV = "CREATE VIEW gcReceiptView
		AS
		select
		  (case mode
		    when 'tender' then 'Gift Card Purchase'
		    when 'refund' then 'Gift Card Refund'
		    when 'addvalue' then 'Gift Card Add Value'
		    when 'activate' then 'Gift Card Activation'
		    else 'Gift Card Transaction'
		  end) as tranType,
		  (case mode when 'refund' then -1*r.amount else r.amount end) as amount, 
		  terminalID,
		  PAN,
		  (case manual when 1 then 'Manual' else 'Swiped' end) as entryMethod,
		  xAuthorizationCode,
		  xBalance,
		  '' as xVoidCode,
		  r.date, r.cashierNo, r.laneNo, r.transNo, r.transID, r.datetime,
		  0 as sortorder
		from valutecRequest r
		join valutecResponse s
		  on s.date=r.date
		  and s.cashierNo=r.cashierNo
		  and s.laneNo=r.laneNo
		  and s.transNo=r.transNo
		  and s.transID=r.transID
		where s.validResponse=1 and (s.xAuthorized='true' or s.xAuthorized='Appro')

		union all

		select
		  (case r.mode
		    when 'tender' then 'Gift Card Purchase CANCELED'
		    when 'refund' then 'Gift Card Refund CANCELED'
		    when 'addvalue' then 'Gift Card Add Value CANCELED'
		    when 'activate' then 'Gift Card Activation CANCELED'
		    else 'Gift Card Transaction CANCELED'
		  end) as tranType,
		  (case r.mode when 'refund' then r.amount else -1*r.amount end) as amount,  
		  terminalID,
		  PAN,
		  (case manual when 1 then 'Manual' else 'Swiped' end) as entryMethod,
		  origAuthCode as xAuthorizationCode,
		  xBalance,
		  xAuthorizationCode as xVoidCode,
		  r.date, r.cashierNo, r.laneNo, r.transNo, r.transID, m.datetime,
		  1 as sortorder
		from valutecRequestMod as m
		join valutecRequest as r
		  on r.date=m.date
		  and r.cashierNo=m.cashierNo
		  and r.laneNo=m.laneNo
		  and r.transNo=m.transNo
		  and r.transID=m.transID
		where m.validResponse=1 and (m.xAuthorized='true' 
		or m.xAuthorized='Appro') and m.mode='void'";
	if(!$db->table_exists('gcReceiptView',$name)){
		db_structure_modify($db,'gcReceiptView',$gcV,$errors);
	}

	$sigCaptureTable = "CREATE TABLE CapturedSignature (
		tdate datetime,
		emp_no int,
		register_no int,
		trans_no int,
		trans_id int,
		filetype char(3),
		filecontents blob)";
	if($type == "mssql"){
		$sigCaptureTable = str_replace("blob","image",$sigCaptureTable);
	}
	if (!$db->table_exists("CapturedSignature")){
		db_structure_modify($db,'CapturedSignature',$sigCaptureTable,$errors);
	}

	$lttG = "CREATE  view ltt_grouped as
	select 	upc,description,trans_type,trans_subtype,sum(itemQtty)as itemqtty,
		discounttype,volume,
		trans_status,
		case when voided=1 then 0 else voided end as voided,
		department,sum(quantity) as quantity,matched,min(trans_id) as trans_id,
		scale,
		sum(unitprice) as unitprice, 
		convert(sum(total),decimal(10,2)) as total,
		sum(regPrice) as regPrice,tax,foodstamp,charflag,
		case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end as grouper
	from localtemptrans
	where description not like '** YOU SAVED %' and trans_status = 'M'
	group by upc,description,trans_type,trans_subtype,discounttype,volume,
		trans_status,
		department,scale,case when voided=1 then 0 else voided end,
		matched,tax,foodstamp,charflag,
		case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end

	union all

	select 	upc,case when numflag=1 then concat(description,'*') else description end as description,
		trans_type,trans_subtype,sum(itemQtty)as itemqtty,discounttype,volume,
		trans_status,
		case when voided=1 then 0 else voided end as voided,
		department,sum(quantity) as quantity,matched,min(trans_id) as trans_id,
		scale,unitprice,convert(sum(total),decimal(10,2)) as total,regPrice,tax,foodstamp,charflag,
		case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end as grouper
	from localtemptrans
	where description not like '** YOU SAVED %' and trans_status !='M'
	AND trans_type <> 'L'
	group by upc,description,trans_type,trans_subtype,discounttype,volume,
		trans_status,
		department,scale,case when voided=1 then 0 else voided end,
		unitprice,regPrice,matched,tax,foodstamp,charflag,
		case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end

	union all

	select 	upc,
		case when discounttype=1 then
		concat(' > you saved $',convert(convert(sum(quantity*regprice-quantity*unitprice),decimal(10,2)),char(20)),'  <')
		when discounttype=2 then
		concat(' > you saved $',convert(convert(sum(quantity*regprice-quantity*unitprice),decimal(10,2)),char(20)),'  Member Special <')
		end as description,
		trans_type,'0' as trans_subtype,0 as itemQtty,discounttype,volume,
		'D' as trans_status,
		2 as voided,
		department,0 as quantity,matched,min(trans_id)+1 as trans_id,
		scale,0 as unitprice,
		0 as total,
		0 as regPrice,0 as tax,0 as foodstamp,charflag,
		case when trans_status='d' or scale=1 then trans_id else scale end as grouper
	from localtemptrans
	where description not like '** YOU SAVED %' and (discounttype=1 or discounttype=2)
	AND trans_type <> 'L'
	group by upc,description,trans_type,trans_subtype,discounttype,volume,
		department,scale,matched,
		case when trans_status='d' or scale=1 then trans_id else scale end
	having convert(sum(quantity*regprice-quantity*unitprice),decimal(10,2))<>0";
	if($type == 'mssql'){
		$lttG = "CREATE   view ltt_grouped as
		select 	upc,description,trans_type,trans_subtype,sum(itemQtty)as itemqtty,
			discounttype,volume,
			trans_status,
			case when voided=1 then 0 else voided end as voided,
			department,sum(quantity) as quantity,matched,min(trans_id) as trans_id,
			scale,
			sum(unitprice) as unitprice, 
			sum(total) as total,
			sum(regPrice) as regPrice,tax,foodstamp,charflag,
			case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end as grouper
		from localtemptrans
		where description not like '** YOU SAVED %' and trans_status = 'M'
		group by upc,description,trans_type,trans_subtype,discounttype,volume,
			trans_status,
			department,scale,case when voided=1 then 0 else voided end,
			matched,tax,foodstamp,charflag,
			case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end

		union all

		select 	upc,case when numflag=1 then description+'*' else description end as description,
			trans_type,trans_subtype,sum(itemQtty)as itemqtty,discounttype,volume,
			trans_status,
			case when voided=1 then 0 else voided end as voided,
			department,sum(quantity) as quantity,matched,min(trans_id) as trans_id,
			scale,unitprice,sum(total) as total,regPrice,tax,foodstamp,charflag,
			case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end as grouper
		from localtemptrans
		where description not like '** YOU SAVED %' and trans_status !='M'
		AND trans_type <> 'L'
		group by upc,description,trans_type,trans_subtype,discounttype,volume,
			trans_status,
			department,scale,case when voided=1 then 0 else voided end,
			unitprice,regPrice,matched,tax,foodstamp,charflag,
			case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end

		union all

		select 	upc,
			case when discounttype=1 then
			' > you saved $'+convert(varchar(20),convert(money,sum(quantity*regprice-quantity*unitprice)))+'  <'
			when discounttype=2 then
			' > you saved $'+convert(varchar(20),convert(money,sum(quantity*regprice-quantity*unitprice)))+'  Member Special <'
			end as description,
			trans_type,'0' as trans_subtype,0 as itemQtty,discounttype,volume,
			'D' as trans_status,
			2 as voided,
			department,0 as quantity,matched,min(trans_id)+1 as trans_id,
			scale,0 as unitprice,
			0 as total,
			0 as regPrice,0 as tax,0 as foodstamp,charflag,
			case when trans_status='d' or scale=1 then trans_id else scale end as grouper
		from localtemptrans
		where description not like '** YOU SAVED %' and (discounttype=1 or discounttype=2)
		AND trans_type <> 'L'
		group by upc,description,trans_type,trans_subtype,discounttype,volume,
			department,scale,matched,
			case when trans_status='d' or scale=1 then trans_id else scale end
		having convert(money,sum(quantity*regprice-quantity*unitprice))<>0";
	}
	db_structure_modify($db,'ltt_grouped','DROP VIEW ltt_grouped',$errors);
	if(!$db->table_exists('ltt_grouped',$name)){
		db_structure_modify($db,'ltt_grouped',$lttG,$errors);
	}


	$lttreorderG = "CREATE   view ltt_receipt_reorder_g as
	select 
	l.description,
	case 
		when voided = 5 
			then 'Discount'
		when trans_status = 'M'
			then 'Mbr special'
		when trans_status = 'S'
			then 'Staff special'
		when unitPrice = 0.01
			then ''
		when charflag = 'SO'
			then ''
		when scale <> 0 and quantity <> 0 
			then concat(convert(quantity,char),' @ ',convert(unitPrice,char))
		when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity = 1
			then concat(convert(volume,char),' /',convert(unitPrice,char))
		when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity <> 1
			then concat(convert(Quantity,char),' @ ',convert(Volume,char),' /',convert(unitPrice,char))
		when abs(itemQtty) > 1 and discounttype = 3
			then concat(convert(ItemQtty,char),' /',convert(UnitPrice,char))
		when abs(itemQtty) > 1
			then concat(convert(quantity,char),' @ ',convert(unitPrice,char))
		when matched > 0
			then '1 w/ vol adj'
		else ''
	end
	as comment,
	total,
	case 
		when trans_status = 'V' 
			then 'VD'
		when trans_status = 'R'
			then 'RF'
		when tax = 1 and foodstamp <> 0
			then 'TF'
		when tax = 1 and foodstamp = 0
			then 'T' 
		WHEN (tax > 1 and foodstamp <> 0)
			THEN CONCAT(LEFT(t.description,1),'F')
		WHEN (tax > 1 and foodstamp = 0)
			THEN LEFT(t.description,1)
		when tax = 0 and foodstamp <> 0
			then 'F'
		when tax = 0 and foodstamp = 0
			then '' 
	end
	as Status,
	case when trans_subtype='CM' or voided in (10,17)
		then 'CM' else trans_type
	end
	as trans_type,
	unitPrice,
	voided,
	trans_id + 1000 as sequence,
	department,
	upc,
	trans_subtype
	from ltt_grouped as l
	left join taxrates as t
	on l.tax = t.id
	where voided <> 5 and UPC <> 'TAX' and UPC <> 'DISCOUNT'
	AND trans_type <> 'L'
	and not (trans_status='M' and total=convert('0.00',decimal(10,2)))

	union

	select
	'  ' as description,
	' ' as comment,
	0 as total,
	' ' as Status,
	' ' as trans_type,
	0 as unitPrice,
	0 as voided,
	999 as sequence,
	'' as department,
	'' as upc,
	'' as trans_subtype";

	if($type == 'mssql'){
		$lttreorderG = "CREATE view ltt_receipt_reorder_g as
		select top 100 percent
		l.description,
		case 
			when voided = 5 
				then 'Discount'
			when trans_status = 'M'
				then 'Mbr special'
			when trans_status = 'S'
				then 'Staff special'
			when unitPrice = 0.01
				then ''
			when scale <> 0 and quantity <> 0 
				then convert(varchar, quantity) + ' @ ' + convert(varchar, unitPrice)
			when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity = 1
				then convert(varchar, volume) + ' /' + convert(varchar, unitPrice)
			when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity <> 1
				then convert(varchar, Quantity) + ' @ ' + convert(varchar, Volume) + ' /' + convert(varchar, unitPrice)
			when abs(itemQtty) > 1 and discounttype = 3
				then convert(varchar,ItemQtty) + ' /' + convert(varchar, UnitPrice)
			when abs(itemQtty) > 1
				then convert(varchar, quantity) + ' @ ' + convert(varchar, unitPrice)	
			when matched > 0
				then '1 w/ vol adj'
			else ''
		end
		as comment,
		total,
		case 
			when trans_status = 'V' 
				then 'VD'
			when trans_status = 'R'
				then 'RF'
			WHEN (tax = 1 and foodstamp <> 0)
				THEN 'TF'
			WHEN (tax = 1 and foodstamp = 0)
				THEN 'T' 
			WHEN (tax > 1 and foodstamp <> 0)
				THEN LEFT(t.description,1)+'F'
			WHEN (tax > 1 and foodstamp = 0)
				THEN LEFT(t.description,1)
			when tax = 0 and foodstamp <> 0
				then 'F'
			when tax = 0 and foodstamp = 0
				then '' 
		end
		as Status,
		case when trans_subtype='CM' or voided in (10,17)
			then 'CM' else trans_type
		end
		as trans_type,
		unitPrice,
		voided,
		trans_id + 1000 as sequence,
		department,
		upc,
		trans_subtype
		from ltt_grouped as l
		left join taxrates as t
		on l.tax = t.id
		where voided <> 5 and UPC <> 'TAX' and UPC <> 'DISCOUNT'
		AND trans_type <> 'L'
		and not (trans_status='M' and total=convert(money,'0.00'))

		union

		select
		'  ' as description,
		' ' as comment,
		0 as total,
		' ' as Status,
		' ' as trans_type,
		0 as unitPrice,
		0 as voided,
		999 as sequence,
		'' as department,
		'' as upc,
		'' as trans_subtype";
	}
	db_structure_modify($db,'ltt_receipt_reorder_g','DROP VIEW ltt_receipt_reorder_g',$errors);
	if(!$db->table_exists('ltt_receipt_reorder_g',$name)){
		db_structure_modify($db,'ltt_receipt_reorder_g',$lttreorderG,$errors);
	}

	$reorderG = "CREATE   view receipt_reorder_g as
		select 
		case 
			when trans_type = 'T' 
				then 	
					case when trans_subtype = 'CP' and upc<>'0'
					then	concat(	
						rpad(Description,30,' '),
						' ',
						rpad(Comment,12,' '),
						lpad(convert(Total,char),8,' '),
						lpad(status,4,' ') ) 
					else 	concat( lpad(upper(Description),44,' '), 
						lpad(convert((-1 * Total),char),8,' '), 
						lpad(status,4,' ') ) 
					end 
			when voided = 3 
				then 	concat( rpad(Description,30,' '),
					space(9), 
					'TOTAL', 
					lpad(convert(UnitPrice,char),8,' ') )
			when voided = 2
				then 	description
			when voided = 4
				then 	description
			when voided = 6
				then 	description
			when voided = 7 or voided = 17
				then 	concat( rpad(Description,30,' '), 
					space(14), 
					lpad(convert(UnitPrice,char),8,' '), 
					lpad(status,4,' ') )
			when sequence < 1000
				then 	description
			else
				concat( rpad(Description,30,' '),
					' ',
					rpad(Comment,12,' '),
					lpad(convert(Total,char),8,' '),
					lpad(status,4,' ') )
			end as linetoprint,
		sequence,
		department,
		subdept_name as dept_name,
		trans_type,
		upc
		from ltt_receipt_reorder_g r
		left outer join ".$CORE_LOCAL->get('pDatabase').".subdepts d on r.department=d.dept_ID
		where r.total<>0 or r.unitprice=0
		order by sequence";
	if($type == 'mssql'){
		$reorderG = "CREATE view receipt_reorder_g as
		select top 100 percent
		case 
			when trans_type = 'T' 
				then 	
					case when trans_subtype = 'CP' and upc<>'0'
					then	left(Description + space(30), 30)
						+ ' ' 
						+ left(Comment + space(12), 12) 
						+ right(space(8) + convert(varchar, Total), 8) 
						+ right(space(4) + status, 4) 
					else 	right((space(44) + upper(rtrim(Description))), 44) 
						+ right((space(8) + convert(varchar, (-1 * Total))), 8) 
						+ right((space(4) + status), 4) 
					end 
			when voided = 3 
				then 	left(Description + space(30), 30) 
					+ space(9) 
					+ 'TOTAL' 
					+ right(space(8) + convert(varchar, UnitPrice), 8)
			when voided = 2
				then 	description
			when voided = 4
				then 	description
			when voided = 6
				then 	description
			when voided = 7 or voided = 17
				then 	left(Description + space(30), 30) 
					+ space(14) 
					+ right(space(8) + convert(varchar, UnitPrice), 8) 
					+ right(space(4) + status, 4)
			when sequence < 1000
				then 	description
			else
				left(Description + space(30), 30)
				+ ' ' 
				+ left(Comment + space(12), 12) 
				+ right(space(8) + convert(varchar, Total), 8) 
				+ right(space(4) + status, 4)
			end
			as linetoprint,
			sequence,
			department,
			dept_name,
			trans_type,
			upc
			from ltt_receipt_reorder_g r
			left outer join ".$CORE_LOCAL->get('pDatabase')."dbo.subdepts
		       	d on r.department=d.dept_ID
			where r.total<>0 or r.unitprice=0
			order by sequence";
	}
	if(!$db->table_exists('receipt_reorder_g',$name)){
		db_structure_modify($db,'receipt_reorder_g',$reorderG,$errors);
	}


	$unionsG = "CREATE view receipt_reorder_unions_g as
	select linetoprint,
	sequence,dept_name,1 as ordered,upc
	from receipt_reorder_g
	where (department<>0 or trans_type IN ('CM','I'))
	and linetoprint not like 'member discount%'

	union all

	select replace(replace(replace(r1.linetoprint,'** T',' = t'),' **',' = '),'W','w') as linetoprint,
	r1.sequence,r2.dept_name,1 as ordered,r2.upc
	from receipt_reorder_g as r1 join receipt_reorder_g as r2 on r1.sequence+1=r2.sequence
	where r1.linetoprint like '** T%' and r2.dept_name is not null and r1.linetoprint<>'** Tare Weight 0 **'

	union all

	select
	concat(
	rpad(concat('** ',rtrim(convert(percentdiscount,char)),'% Discount Applied **'),30,' '),
	' ', 
	space(13),
	lpad(convert((-1*transDiscount),char),8,' '),
	space(4) ) as linetoprint,
	0 as sequence,null as dept_name,2 as ordered,
	'' as upc
	from subtotals
	where percentdiscount<>0

	union all

	select linetoprint,sequence,null as dept_name,2 as ordered,upc
	from receipt_reorder_g
	where linetoprint like 'member discount%'

	union all

	select 
	concat(
	lpad('SUBTOTAL',44,' '),
	lpad(convert(round(l.runningTotal-s.taxTotal-l.tenderTotal,2),char),8,' '),
	space(4) ) as linetoprint,1 as sequence,null as dept_name,3 as ordered,'' as upc
	from lttsummary as l, subtotals as s

	union all

	select 
	concat(
	lpad('TAX',44,' '),
	lpad(convert(round(taxtotal,2),char),8,' '), 
	space(4) ) as linetoprint,
	2 as sequence,null as dept_name,3 as ordered,'' as upc
	from subtotals

	union all

	select 
	concat(
	lpad('TOTAL',44,' '),
	lpad(convert(runningtotal-tendertotal,char),8,' '),
	space(4) ) as linetoprint,3 as sequence,null as dept_name,3 as ordered,'' as upc
	from lttsummary

	union all

	select linetoprint,sequence,dept_name,4 as ordered,upc
	from receipt_reorder_g
	where (trans_type='T' and department = 0)
	or (department = 0 and trans_type NOT IN ('CM','I')
	and linetoprint NOT LIKE '** %'
	and linetoprint NOT LIKE 'Subtotal%') 

	union all

	select 
	concat(
	lpad('CURRENT AMOUNT DUE',44,' '),
	lpad(convert(runningTotal-transDiscount,char),8,' '),
	space(4) ) as linetoprint,
	5 as sequence,
	null as dept_name,
	5 as ordered,'' as upc
	from subtotals where runningTotal <> 0 ";

	if($type == 'mssql'){
		$unionsG = "CREATE view receipt_reorder_unions_g as
		select linetoprint,
		sequence,dept_name,1 as ordered,upc
		from receipt_reorder_g
		where (department<>0 or trans_type IN ('CM','I'))
		and linetoprint not like 'member discount%'

		union all

		select replace(replace(replace(r1.linetoprint,'** T',' = T'),' **',' = '),'W','w') as linetoprint,
		r1.[sequence],r2.dept_name,1 as ordered,r2.upc
		from receipt_reorder_g r1 join receipt_reorder_g r2 on r1.[sequence]+1=r2.[sequence]
		where r1.linetoprint like '** T%' and r2.dept_name is not null and r1.linetoprint<>'** Tare Weight 0 **'

		union all

		select
		left('** '+rtrim(convert(char,percentdiscount))+'% Discount Applied **' + space(30), 30)
		+ ' ' 
		+ left('' + space(13), 13) 
		+ right(space(8) + convert(varchar, (-1*transDiscount)), 8) 
		+ right(space(4) + '', 4),
		0 as sequence,null as dept_name,2 as ordered,
		'' as upc
		from subtotals
		where percentdiscount<>0

		union all

		select linetoprint,sequence,null as dept_name,2 as ordered,upc
		from receipt_reorder_g
		where linetoprint like 'member discount%'

		union all

		select 
		right((space(44) + upper(rtrim('SUBTOTAL'))), 44) 
		+ right((space(8) + convert(varchar,round(l.runningTotal-s.taxTotal-l.tenderTotal,2))),8)
		+ right((space(4) + ''), 4) as linetoprint,1 as sequence,null as dept_name,3 as ordered,'' as upc
		from lttsummary as l, subtotals as s

		union all

		select 
		right((space(44) + upper(rtrim('TAX'))), 44) 
		+ right((space(8) + convert(varchar,round(taxtotal,2))), 8) 
		+ right((space(4) + ''), 4) as linetoprint,
		2 as sequence,null as dept_name,3 as ordered,'' as upc
		from subtotals

		union all

		select 
		right((space(44) + upper(rtrim('TOTAL'))), 44) 
		+ right((space(8) +convert(varchar,runningtotal-tendertotal)),8)
		+ right((space(4) + ''), 4) as linetoprint,3 as sequence,null as dept_name,3 as ordered,'' as upc
		from lttsummary

		union all

		select linetoprint,sequence,dept_name,4 as ordered,upc
		from receipt_reorder_g
		where (trans_type='T' and department = 0)
		or (department = 0 and trans_type NOT IN ('CM','I') and linetoprint like '%Coupon%')

		union all

		select 
		right((space(44) + upper(rtrim('Current Amount Due'))), 44) 
		+right((space(8) + convert(varchar,subtotal)),8)
		+ right((space(4) + ''), 4) as linetoprint,
		5 as sequence,
		null as dept_name,
		5 as ordered,'' as upc
		from subtotals where runningtotal <> 0 ";
	}
	if(!$db->table_exists('receipt_reorder_unions_g',$name)){
		db_structure_modify($db,'receipt_reorder_unions_g',$unionsG,$errors);
	}

	$rplttG = "CREATE     view rp_ltt_grouped as
		select 	register_no,emp_no,trans_no,card_no,
			upc,description,trans_type,trans_subtype,sum(itemQtty)as itemqtty,
			discounttype,volume,
			trans_status,
			case when voided=1 then 0 else voided end as voided,
			department,sum(quantity) as quantity,matched,min(trans_id) as trans_id,
			scale,
			sum(unitprice) as unitprice, 
			convert(sum(total),decimal(10,2)) as total,
			sum(regPrice) as regPrice,tax,foodstamp,
			case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end as grouper
		from localtranstoday
		where description not like '** YOU SAVED %' and trans_status = 'M'
		group by register_no,emp_no,trans_no,card_no,
			upc,description,trans_type,trans_subtype,discounttype,volume,
			trans_status,
			department,scale,case when voided=1 then 0 else voided end,
			matched,tax,foodstamp,
			case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end

		union all

		select 	register_no,emp_no,trans_no,card_no,
			upc,case when numflag=1 then concat(description,'*') else description end as description,
			trans_type,trans_subtype,sum(itemQtty)as itemqtty,discounttype,volume,
			trans_status,
			case when voided=1 then 0 else voided end as voided,
			department,sum(quantity) as quantity,matched,min(trans_id) as trans_id,
			scale,unitprice,convert(sum(total),decimal(10,2)) as total,regPrice,tax,foodstamp,
			case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end as grouper
		from localtranstoday
		where description not like '** YOU SAVED %' and trans_status !='M'
		AND trans_type <> 'L'
		group by register_no,emp_no,trans_no,card_no,
			upc,description,trans_type,trans_subtype,discounttype,volume,
			trans_status,
			department,scale,case when voided=1 then 0 else voided end,
			unitprice,regPrice,matched,tax,foodstamp,
			case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end

		union all

		select 	register_no,emp_no,trans_no,card_no,
			upc,
			case when discounttype=1 then
			concat(' > YOU SAVED $',convert(convert(sum(quantity*regprice-quantity*unitprice),decimal(10,2)),char(20)),'  <')
			when discounttype=2 then
			concat(' > YOU SAVED $',convert(convert(sum(quantity*regprice-quantity*unitprice),decimal(10,2)),char(20)),'  Member Special <')
			end as description,
			trans_type,'0' as trans_subtype,0 as itemQtty,discounttype,volume,
			'D' as trans_status,
			2 as voided,
			department,0 as quantity,matched,min(trans_id)+1 as trans_id,
			scale,0 as unitprice,
			0 as total,
			0 as regPrice,0 as tax,0 as foodstamp,
			case when trans_status='d' or scale=1 then trans_id else scale end as grouper
		from localtranstoday
		where description not like '** YOU SAVED %' and (discounttype=1 or discounttype=2)
		AND trans_type <> 'L'
		group by register_no,emp_no,trans_no,card_no,
			upc,description,trans_type,trans_subtype,discounttype,volume,
			department,scale,matched,
			case when trans_status='d' or scale=1 then trans_id else scale end
		having convert(sum(quantity*regprice-quantity*unitprice),decimal(10,2))<>0";
	if($type == 'mssql'){
		$rplttG = "CREATE      view rp_ltt_grouped as
		select 	register_no,emp_no,trans_no,card_no,
			upc,description,trans_type,trans_subtype,sum(itemQtty)as itemqtty,
			discounttype,volume,
			trans_status,
			case when voided=1 then 0 else voided end as voided,
			department,sum(quantity) as quantity,matched,min(trans_id) as trans_id,
			scale,
			sum(unitprice) as unitprice, 
			sum(total) as total,
			sum(regPrice) as regPrice,tax,foodstamp,
			case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end as grouper
		from localtranstoday
		where description not like '** YOU SAVED %' and trans_status = 'M'
		group by register_no,emp_no,trans_no,card_no,
			upc,description,trans_type,trans_subtype,discounttype,volume,
			trans_status,
			department,scale,case when voided=1 then 0 else voided end,
			matched,tax,foodstamp,
			case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end

		union all

		select 	register_no,emp_no,trans_no,card_no,
			upc,case when numflag=1 then description+'*' else description end as description,
			trans_type,trans_subtype,sum(itemQtty)as itemqtty,discounttype,volume,
			trans_status,
			case when voided=1 then 0 else voided end as voided,
			department,sum(quantity) as quantity,matched,min(trans_id) as trans_id,
			scale,unitprice,sum(total) as total,regPrice,tax,foodstamp,
			case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end as grouper
		from localtranstoday
		where description not like '** YOU SAVED %' and trans_status !='M'
		AND trans_type <> 'L'
		group by register_no,emp_no,trans_no,card_no,
			upc,description,trans_type,trans_subtype,discounttype,volume,
			trans_status,
			department,scale,case when voided=1 then 0 else voided end,
			unitprice,regPrice,matched,tax,foodstamp,
			case when trans_status='d' or scale=1 or trans_type='T' then trans_id else scale end

		union all

		select 	register_no,emp_no,trans_no,card_no,
			upc,
			case when discounttype=1 then
			' > YOU SAVED $'+convert(varchar(20),convert(money,sum(quantity*regprice-quantity*unitprice)))+'  <'
			when discounttype=2 then
			' > YOU SAVED $'+convert(varchar(20),convert(money,sum(quantity*regprice-quantity*unitprice)))+'  Member Special <'
			end as description,
			trans_type,'0' as trans_subtype,0 as itemQtty,discounttype,volume,
			'D' as trans_status,
			2 as voided,
			department,0 as quantity,matched,min(trans_id)+1 as trans_id,
			scale,0 as unitprice,
			0 as total,
			0 as regPrice,0 as tax,0 as foodstamp,
			case when trans_status='d' or scale=1 then trans_id else scale end as grouper
		from localtranstoday
		where description not like '** YOU SAVED %' and (discounttype=1 or discounttype=2)
		AND trans_type <> 'L'
		group by register_no,emp_no,trans_no,card_no,
			upc,description,trans_type,trans_subtype,discounttype,volume,
			department,scale,matched,
			case when trans_status='d' or scale=1 then trans_id else scale end
		having convert(money,sum(quantity*regprice-quantity*unitprice))<>0";
	}	
	db_structure_modify($db,'rp_ltt_grouped','DROP VIEW rp_ltt_grouped',$errors);
	if(!$db->table_exists('rp_ltt_grouped',$name)){
		db_structure_modify($db,'rp_ltt_grouped',$rplttG,$errors);
	}

	$rpreorderG = "CREATE    view rp_ltt_receipt_reorder_g as
		select 
		register_no,emp_no,trans_no,card_no,
		l.description,
		case 
			when voided = 5 
				then 'Discount'
			when trans_status = 'M'
				then 'Mbr special'
			when trans_status = 'S'
				then 'Staff special'
			when unitPrice = 0.01
				then ''
			when scale <> 0 and quantity <> 0 
				then concat(convert(quantity,char),' @ ',convert(unitPrice,char))
			when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity = 1
				then concat(convert(volume,char),' /',convert(unitPrice,char))
			when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity <> 1
				then concat(convert(Quantity,char),' @ ',convert(Volume,char),' /',convert(unitPrice,char))
			when abs(itemQtty) > 1 and discounttype = 3
				then concat(convert(ItemQtty,char),' /',convert(UnitPrice,char))
			when abs(itemQtty) > 1
				then concat(convert(quantity,char),' @ ',convert(unitPrice,char))
			when matched > 0
				then '1 w/ vol adj'
			else ''
		end
		as comment,
		total,
		case 
			when trans_status = 'V' 
				then 'VD'
			when trans_status = 'R'
				then 'RF'
			WHEN (tax = 1 and foodstamp <> 0)
				THEN 'TF'
			WHEN (tax = 1 and foodstamp = 0)
				THEN 'T' 
			WHEN (tax > 1 and foodstamp <> 0)
				THEN CONCAT(LEFT(t.description,1),'F')
			WHEN (tax > 1 and foodstamp = 0)
				THEN LEFT(t.description,1)
			when tax = 0 and foodstamp <> 0
				then 'F'
			when tax = 0 and foodstamp = 0
				then '' 
		end
		as Status,
		trans_type,
		unitPrice,
		voided,
		trans_id + 1000 as sequence,
		department,
		upc,
		trans_subtype
		from rp_ltt_grouped as l
		left join taxrates as t
		on l.tax=t.id
		where voided <> 5 and UPC <> 'TAX' and UPC <> 'DISCOUNT'
		AND trans_type <> 'L'
		and not (trans_status='M' and total=convert('0.00',decimal))

		union

		select
		0 as register_no, 0 as emp_no,0 as trans_no,0 as card_no,
		'  ' as description,
		' ' as comment,
		0 as total,
		' ' as Status,
		' ' as trans_type,
		0 as unitPrice,
		0 as voided,
		999 as sequence,
		'' as department,
		'' as upc,
		'' as trans_subtype";
	if($type == 'mssql'){
		$rpreorderG = "CREATE     view rp_ltt_receipt_reorder_g as
		select top 100 percent
		register_no,emp_no,trans_no,card_no,
		l.description,
		case 
			when voided = 5 
				then 'Discount'
			when trans_status = 'M'
				then 'Mbr special'
			when trans_status = 'S'
				then 'Staff special'
			when unitPrice = 0.01
				then ''
			when scale <> 0 and quantity <> 0 
				then convert(varchar, quantity) + ' @ ' + convert(varchar, unitPrice)
			when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity = 1
				then convert(varchar, volume) + ' /' + convert(varchar, unitPrice)
			when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity <> 1
				then convert(varchar, Quantity) + ' @ ' + convert(varchar, Volume) + ' /' + convert(varchar, unitPrice)
			when abs(itemQtty) > 1 and discounttype = 3
				then convert(varchar,ItemQtty) + ' /' + convert(varchar, UnitPrice)
			when abs(itemQtty) > 1
				then convert(varchar, quantity) + ' @ ' + convert(varchar, unitPrice)	
			when matched > 0
				then '1 w/ vol adj'
			else ''
		end
		as comment,
		total,
		case 
			when trans_status = 'V' 
				then 'VD'
			when trans_status = 'R'
				then 'RF'
			WHEN (tax = 1 and foodstamp <> 0)
				THEN 'TF'
			WHEN (tax = 1 and foodstamp = 0)
				THEN 'T' 
			WHEN (tax > 1 and foodstamp <> 0)
				THEN LEFT(t.description,1)+'F'
			WHEN (tax > 1 and foodstamp = 0)
				THEN LEFT(t.description,1)
			when tax = 0 and foodstamp <> 0
				then 'F'
			when tax = 0 and foodstamp = 0
				then '' 
		end
		as Status,
		trans_type,
		unitPrice,
		voided,
		trans_id + 1000 as sequence,
		department,
		upc,
		trans_subtype
		from rp_ltt_grouped as l
		left join taxrates as t
		on l.tax=t.id
		where voided <> 5 and UPC <> 'TAX' and UPC <> 'DISCOUNT'
		AND trans_type <> 'L'
		and not (trans_status='M' and total=convert(money,'0.00'))

		union

		select
		0 as register_no, 0 as emp_no,0 as trans_no,0 as card_no,
		'  ' as description,
		' ' as comment,
		0 as total,
		' ' as Status,
		' ' as trans_type,
		0 as unitPrice,
		0 as voided,
		999 as sequence,
		'' as department,
		'' as upc,
		'' as trans_subtype";
	}	
	db_structure_modify($db,'rp_ltt_receipt_reorder_g','DROP VIEW rp_ltt_receipt_reorder_g',$errors);
	if(!$db->table_exists("rp_ltt_receipt_reorder_g",$name)){
		db_structure_modify($db,'rp_ltt_receipt_reorder_g',$rpreorderG,$errors);
	}
	
	$rpG = "CREATE    view rp_receipt_reorder_g as
		select 
		register_no,emp_no,trans_no,card_no,
		case 
			when trans_type = 'T' 
				then 	
					case when trans_subtype = 'CP' and upc<>'0'
					then	concat(	
						rpad(Description,30,' '),
						' ',
						rpad(Comment,12,' '),
						lpad(convert(Total,char),8,' '), 
						lpad(status,4,' ')) 
					else 	concat(	
						lpad(upper(rtrim(Description)),44,' '),
						lpad(convert((-1 * Total),char),8,' '), 
						lpad(status,4,' ')) 
					end 
			when voided = 3 
				then 	concat(rpad(Description,30,' '),
					space(9), 
					'TOTAL',
					lpad(convert(UnitPrice,char),8,' '))
			when voided = 2
				then 	description
			when voided = 4
				then 	description
			when voided = 6
				then 	description
			when voided = 7 or voided = 17
				then 	concat(rpad(Description,30,' '),
					space(14),
					lpad(convert(UnitPrice,char),8,' '), 
					lpad(status,4,' '))
			when sequence < 1000
				then 	description
			else
				concat(rpad(Description,30,' '),
				' ',
				rpad(Comment,12, ' '),
				lpad(convert(Total,char),8,' '), 
				lpad(status,4,' '))
		end
		as linetoprint,
		sequence,
		department,
		subdept_name as dept_name,
		case when trans_subtype='CM' or voided in (10,17)
			then 'CM' else trans_type
		end
		as trans_type,
		upc

		from rp_ltt_receipt_reorder_g r
		left outer join ".$CORE_LOCAL->get('pDatabase').".subdepts d 
		on r.department=d.dept_ID
		where r.total<>0 or r.unitprice=0
		order by register_no,emp_no,trans_no,card_no,sequence";
	if($type == 'mssql'){
		$rpG = "CREATE     view rp_receipt_reorder_g as
		select top 100 percent
		register_no,emp_no,trans_no,card_no,
		case 
			when trans_type = 'T' 
				then 	
					case when trans_subtype = 'CP' and upc<>'0'
					then	left(Description + space(30), 30)
						+ ' ' 
						+ left(Comment + space(12), 12) 
						+ right(space(8) + convert(varchar, Total), 8) 
						+ right(space(4) + status, 4) 
					else 	right((space(44) + upper(rtrim(Description))), 44) 
						+ right((space(8) + convert(varchar, (-1 * Total))), 8) 
						+ right((space(4) + status), 4) 
					end 
			when voided = 3 
				then 	left(Description + space(30), 30) 
					+ space(9) 
					+ 'TOTAL' 
					+ right(space(8) + convert(varchar, UnitPrice), 8)
			when voided = 2
				then 	description
			when voided = 4
				then 	description
			when voided = 6
				then 	description
			when voided = 7 or voided = 17
				then 	left(Description + space(30), 30) 
					+ space(14) 
					+ right(space(8) + convert(varchar, UnitPrice), 8) 
					+ right(space(4) + status, 4)
			when sequence < 1000
				then 	description
			else
				left(Description + space(30), 30)
				+ ' ' 
				+ left(Comment + space(12), 12) 
				+ right(space(8) + convert(varchar, Total), 8) 
				+ right(space(4) + status, 4)
		end
		as linetoprint,
		sequence,
		department,
		dept_name,
		case when trans_subtype='CM' or voided in (10,17)
			then 'CM' else trans_type
		end
		as trans_type,
		upc

		from rp_ltt_receipt_reorder_g r
		left outer join ".$CORE_LOCAL->get('pDatabase').".dbo.subdepts d 
		on r.department=d.dept_ID
		where r.total<>0 or r.unitprice=0
		order by register_no,emp_no,trans_no,card_no,sequence";
	}
	if(!$db->table_exists('rp_receipt_reorder_g',$name)){
		db_structure_modify($db,'rp_receipt_reorder_g',$rpG,$errors);
	}

	$rpunionsG = "CREATE     view rp_receipt_reorder_unions_g as
		select linetoprint,
		emp_no,register_no,trans_no,
		sequence,dept_name,1 as ordered,upc
		from rp_receipt_reorder_g
		where (department<>0 or trans_type='CM')
		and linetoprint not like 'member discount%'

		union all

		select replace(replace(r1.linetoprint,'** T',' = T'),' **',' = ') as linetoprint,
		r1.emp_no,r1.register_no,r1.trans_no,
		r1.sequence,r2.dept_name,1 as ordered,r2.upc
		from rp_receipt_reorder_g r1 join rp_receipt_reorder_g r2 on r1.sequence+1=r2.sequence
		and r1.register_no=r2.register_no and r1.emp_no=r2.emp_no and r1.trans_no=r2.trans_no
		where r1.linetoprint like '** T%' and r2.dept_name is not null and r1.linetoprint<>'** Tare Weight 0 **'

		union all

		select
		concat(
		rpad(concat('** ',rtrim(convert(percentdiscount,char)),'% Discount Applied **'),30,' '),
		space(14),
		lpad(convert((-1*transDiscount),char),8,' '), 
		space(4) ),
		emp_no,register_no,trans_no,
		0 as sequence,null as dept_name,2 as ordered,
		'' as upc
		from rp_subtotals
		where percentdiscount<>0

		union all

		select linetoprint,
		emp_no,register_no,trans_no,
		sequence,null as dept_name,2 as ordered,upc
		from rp_receipt_reorder_g
		where linetoprint like 'member discount%'

		union all

		select 
		concat(
		lpad('SUBTOTAL',44,' '), 
		lpad(convert(l.runningTotal-s.taxTotal-l.tenderTotal,char),8,' '),
		space(4)) as linetoprint,
		l.emp_no,l.register_no,l.trans_no,
		1 as sequence,null as dept_name,3 as ordered,'' as upc
		from rp_lttsummary as l, rp_subtotals as s
		WHERE l.emp_no = s.emp_no and
		l.register_no = s.register_no and
		l.trans_no = s.trans_no

		union all

		select 
		concat(
		lpad('TAX',44,' '),
		lpad(convert(taxtotal,char),8,' '), 
		space(4)) as linetoprint,
		emp_no,register_no,trans_no,
		2 as sequence,null as dept_name,3 as ordered,'' as upc
		from rp_subtotals

		union all

		select 
		concat(
		lpad('TOTAL',44,' '),
		lpad(convert(runningtotal-tendertotal,char),8,' '),
		space(4)) as linetoprint,
		emp_no,register_no,trans_no,
		3 as sequence,null as dept_name,3 as ordered,'' as upc
		from rp_lttsummary

		union all

		select linetoprint,
		emp_no,register_no,trans_no,
		sequence,dept_name,4 as ordered,upc
		from rp_receipt_reorder_g
		where (trans_type='T' and department = 0)
		or (department = 0 and linetoprint like '%Coupon%')

		union all

		select 
		concat(
		lpad('CURRENT AMOUNT DUE',44,' '),
		lpad(convert(runningTotal-transDiscount,char),8,' '),
		space(4)) as linetoprint,
		emp_no,register_no,trans_no,
		5 as sequence,
		null as dept_name,
		5 as ordered,'' as upc
		from rp_subtotals where runningTotal <> 0 ";
	if($type == 'mssql'){
		$rpunionsG = "CREATE view rp_receipt_reorder_unions_g as
		select linetoprint,
		emp_no,register_no,trans_no,
		sequence,dept_name,1 as ordered,upc
		from rp_receipt_reorder_g
		where (department<>0 or trans_type='CM')
		and linetoprint not like 'member discount%'

		union all

		select replace(replace(r1.linetoprint,'** T',' = T'),' **',' = ') as linetoprint,
		r1.emp_no,r1.register_no,r1.trans_no,
		r1.[sequence],r2.dept_name,1 as ordered,r2.upc
		from rp_receipt_reorder_g r1 join rp_receipt_reorder_g r2 on r1.[sequence]+1=r2.[sequence]
		and r1.emp_no=r2.emp_no and r1.register_no=r2.register_no and r1.trans_no=r2.trans_no
		where r1.linetoprint like '** T%' and r2.dept_name is not null and r1.linetoprint<>'** Tare Weight 0 **'

		union all

		select
		left('** '+rtrim(convert(char,percentdiscount))+'% Discount Applied **' + space(30), 30)
		+ ' ' 
		+ left('' + space(13), 13) 
		+ right(space(8) + convert(varchar, (-1*transDiscount)), 8) 
		+ right(space(4) + '', 4),
		emp_no,register_no,trans_no,
		0 as sequence,null as dept_name,2 as ordered,
		'' as upc
		from rp_subtotals
		where percentdiscount<>0

		union all

		select linetoprint,
		emp_no,register_no,trans_no,
		sequence,null as dept_name,2 as ordered,upc
		from rp_receipt_reorder_g
		where linetoprint like 'member discount%'

		union all

		select 
		right((space(44) + upper(rtrim('SUBTOTAL'))), 44) 
		+ right((space(8) + convert(varchar,l.runningTotal-s.taxTotal-l.tenderTotal)),8)
		+ right((space(4) + ''), 4) as linetoprint,
		l.emp_no,l.register_no,l.trans_no,
		1 as sequence,null as dept_name,3 as ordered,'' as upc
		from rp_lttsummary as l, rp_subtotals as s
		WHERE l.emp_no = s.emp_no and
		l.register_no = s.register_no and
		l.trans_no = s.trans_no

		union all

		select 
		right((space(44) + upper(rtrim('TAX'))), 44) 
		+ right((space(8) + convert(varchar,taxtotal)), 8) 
		+ right((space(4) + ''), 4) as linetoprint,
		emp_no,register_no,trans_no,
		2 as sequence,null as dept_name,3 as ordered,'' as upc
		from rp_subtotals

		union all

		select 
		right((space(44) + upper(rtrim('TOTAL'))), 44) 
		+ right((space(8) +convert(varchar,runningtotal-tendertotal)),8)
		+ right((space(4) + ''), 4) as linetoprint,
		emp_no,register_no,trans_no,
		3 as sequence,null as dept_name,3 as ordered,'' as upc
		from rp_lttsummary

		union all

		select linetoprint,
		emp_no,register_no,trans_no,
		sequence,dept_name,4 as ordered,upc
		from rp_receipt_reorder_g
		where (trans_type='T' and department = 0)
		or (department = 0 and linetoprint like '%Coupon%')

		union all

		select 
		right((space(44) + upper(rtrim('Current Amount Due'))), 44) 
		+right((space(8) + convert(varchar,subtotal)),8)
		+ right((space(4) + ''), 4) as linetoprint,
		emp_no,register_no,trans_no,
		5 as sequence,
		null as dept_name,
		5 as ordered,'' as upc
		from rp_subtotals where runningtotal <> 0"; 
	}
	if(!$db->table_exists('rp_receipt_reorder_unions_g',$name)){
		db_structure_modify($db,'rp_receipt_reorder_unions_g',$rpunionsG,$errors);
	}

	return $errors;
}

function create_min_server($db,$type){
	global $CORE_LOCAL;
	$name = $CORE_LOCAL->get('mDatabase');
	$errors = array();

	$dtransQ = "CREATE TABLE `dtransactions` (
	  `datetime` datetime default NULL,
	  `register_no` smallint(6) default NULL,
	  `emp_no` smallint(6) default NULL,
	  `trans_no` int(11) default NULL,
	  `upc` varchar(255) default NULL,
	  `description` varchar(255) default NULL,
	  `trans_type` varchar(255) default NULL,
	  `trans_subtype` varchar(255) default NULL,
	  `trans_status` varchar(255) default NULL,
	  `department` smallint(6) default NULL,
	  `quantity` real default NULL,
	  `scale` tinyint(4) default NULL,
	  `cost` real default 0.00 NULL,
	  `unitPrice` real default NULL,
	  `total` real default NULL,
	  `regPrice` real default NULL,
	  `tax` smallint(6) default NULL,
	  `foodstamp` tinyint(4) default NULL,
	  `discount` real default NULL,
	  `memDiscount` real default NULL,
	  `discountable` tinyint(4) default NULL,
	  `discounttype` tinyint(4) default NULL,
	  `voided` tinyint(4) default NULL,
	  `percentDiscount` tinyint(4) default NULL,
	  `ItemQtty` real default NULL,
	  `volDiscType` tinyint(4) default NULL,
	  `volume` tinyint(4) default NULL,
	  `VolSpecial` real default NULL,
	  `mixMatch` smallint(6) default NULL,
	  `matched` smallint(6) default NULL,
	  `memType` tinyint(2) default NULL,
	  `staff` tinyint(4) default NULL,
	  `numflag` smallint(6) default 0 NULL,
	  `charflag` varchar(2) default '' NULL,
	  `card_no` varchar(255) default NULL,
	  `trans_id` int(11) default NULL
	)";
	if ($type == 'mssql'){
		$dtransQ = "CREATE TABLE [dtransactions] (
		[datetime] [datetime] NOT NULL ,
		[register_no] [smallint] NOT NULL ,
		[emp_no] [smallint] NOT NULL ,
		[trans_no] [int] NOT NULL ,
		[upc] [nvarchar] (13) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
		[description] [nvarchar] (30) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
		[trans_type] [nvarchar] (1) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
		[trans_subtype] [nvarchar] (2) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
		[trans_status] [nvarchar] (1) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
		[department] [smallint] NULL ,
		[quantity] [float] NULL ,
		[scale] [tinyint] NULL ,
		[unitPrice] [money] NULL ,
		[total] [money] NOT NULL ,
		[regPrice] [money] NULL ,
		[tax] [smallint] NULL ,
		[foodstamp] [tinyint] NOT NULL ,
		[discount] [money] NOT NULL ,
		[memDiscount] [money] NULL ,
		[discountable] [tinyint] NULL ,
		[discounttype] [tinyint] NULL ,
		[voided] [tinyint] NULL ,
		[percentDiscount] [tinyint] NULL ,
		[ItemQtty] [float] NULL ,
		[volDiscType] [tinyint] NOT NULL ,
		[volume] [tinyint] NOT NULL ,
		[VolSpecial] [money] NOT NULL ,
		[mixMatch] [smallint] NULL ,
		[matched] [smallint] NOT NULL ,
		[memType] [smallint] NULL ,
		[staff] [tinyint] NULL ,
		[numflag] [smallint] NULL ,
		[charflag] [nvarchar] (2) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
		[card_no] [nvarchar] (6) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
		[trans_id] [int] NOT NULL 
		) ON [PRIMARY]";
	}
	if (!$db->table_exists("dtransactions",$name)){
		$db->query($dtransQ,$name);
		db_structure_modify($db,'dtransactions',$dtransQ,$errors);
	}

	$susQ = str_replace("dtransactions","suspended",$dtransQ);
	if(!$db->table_exists("suspended",$name)){
		db_structure_modify($db,'suspended',$susQ,$errors);
	}

	$dlogQ = "CREATE VIEW dlog AS
		SELECT
		datetime AS tdate,
		register_no,
		emp_no,
		trans_no,
		upc,
		CASE WHEN (trans_subtype IN ('CP','IC') OR upc like('%000000052')) then 'T' WHEN upc = 'DISCOUNT' then 'S' else trans_type end as trans_type,
		CASE WHEN upc = 'MAD Coupon' THEN 'MA' 
		   WHEN upc like('%00000000052') THEN 'RR' ELSE trans_subtype END as trans_subtype,
		trans_status,
		department,
		quantity,
		unitPrice,
		total,
		tax,
		foodstamp,
		ItemQtty,
		memType,
		staff,
		numflag,
		charflag,
		card_no,
		trans_id,
		".$db->concat(
			$db->convert('emp_no','char'),"'-'",
			$db->convert('register_no','char'),"'-'",
			$db->convert('trans_no','char'),'')
		." as trans_num
		FROM dtransactions
		WHERE trans_status NOT IN ('D','X','Z')
		AND emp_no <> 9999 and register_no <> 99";
	if(!$db->table_exists("dlog",$name)){
		$errors = db_structure_modify($db,'dlog',$dlogQ,$errors);
	}

	

	$alogQ = "CREATE TABLE alog (
		`datetime` datetime,
		LaneNo smallint,
		CashierNo smallint,
		TransNo int,
		Activity tinyint,
		`Interval` real)";
	if ($type == 'mssql'){
		$alogQ = str_replace("`datetime`","[datetime]",$alogQ);
		$alogQ = str_replace("`","",$alogQ);
	}
	if(!$db->table_exists("alog",$name)){
		db_structure_modify($db,'alog',$alogQ,$errors);
	}

	$susToday = "CREATE VIEW suspendedtoday AS
		SELECT * FROM suspended WHERE "
		.$db->datediff($db->now(),'datetime')." = 0";
	if (!$db->table_exists("suspendedtoday",$name)){
		db_structure_modify($db,'suspendedtoday',$susToday,$errors);
	}

	$efsrq = "CREATE TABLE efsnetRequest (
		date int ,
		cashierNo int ,
		laneNo int ,
		transNo int ,
		transID int ,
		datetime datetime ,
		refNum varchar (50) ,
		live tinyint ,
		mode varchar (32) ,
		amount real ,
		PAN varchar (19) ,
		issuer varchar (16) ,
		name varchar (50) ,
		manual tinyint ,
		sentPAN tinyint ,
		sentExp tinyint ,
		sentTr1 tinyint ,
		sentTr2 tinyint 
		)";
	if(!$db->table_exists('efsnetRequest',$name)){
		db_structure_modify($db,'efsnetRequest',$efsrq,$errors);
	}

	$efsrp = "CREATE TABLE efsnetResponse (
		date int ,
		cashierNo int ,
		laneNo int ,
		transNo int ,
		transID int ,
		datetime datetime ,
		refNum varchar (50),
		seconds float ,
		commErr int ,
		httpCode int ,
		validResponse smallint ,
		xResponseCode varchar (4),
		xResultCode varchar (4), 
		xResultMessage varchar (100),
		xTransactionID varchar (12),
		xApprovalNumber varchar (20)
		)";
	if(!$db->table_exists('efsnetResponse',$name)){
		db_structure_modify($db,'efsnetResponse',$efsrp,$errors);
	}

	$efsrqm = "CREATE TABLE efsnetRequestMod (
		date int ,
		cashierNo int ,
		laneNo int ,
		transNo int ,
		transID int ,
		datetime datetime ,
		origRefNum varchar (50),
		origAmount real ,
		origTransactionID varchar(12) ,
		mode varchar (32),
		altRoute tinyint ,
		seconds float ,
		commErr int ,
		httpCode int ,
		validResponse smallint ,
		xResponseCode varchar(4),
		xResultCode varchar(4),
		xResultMessage varchar(100)
		)";
	if(!$db->table_exists('efsnetRequestMod',$name)){
		db_structure_modify($db,'efsnetRequestMod',$efsrqm,$errors);
	}

	$ttG = "CREATE view TenderTapeGeneric
		as
		select 
		tdate, 
		emp_no, 
		register_no,
		trans_no,
		CASE WHEN trans_subtype = 'CP' AND upc LIKE '%MAD%' THEN ''
		     WHEN trans_subtype IN ('EF','EC','TA') THEN 'EF'
		     ELSE trans_subtype
		END AS trans_subtype,
		CASE WHEN trans_subtype = 'ca' THEN
			CASE WHEN total >= 0 THEN total ELSE 0 END
		     ELSE
			-1 * total
		END AS tender
		from dlog
		where datediff(tdate, curdate()) = 0
		and trans_subtype not in ('0','')";
	if (!$db->table_exists("TenderTapeGeneric",$name)){
		db_structure_modify($db,'TenderTapeGeneric',$ttG,$errors);
	}

	// re-use definition to create lane_config on server
	create_if_needed($db, $type, $name, 'lane_config', 'op', $errors);

	return $errors;
}

?>
