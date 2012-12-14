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

<div class="alert"><?php check_writeable('../ini.php'); ?></div>
<div class="alert"><?php check_writeable('../ini-local.php'); ?></div>

<?php
if (function_exists('posix_getpwuid')){
	$chk = posix_getpwuid(posix_getuid());
	echo "PHP is running as: ".$chk['name']."<br />";
}
else
	echo "PHP is (probably) running as: ".get_current_user()."<br />";

if (!function_exists("socket_create")){
	echo '<b>Warning</b>: PHP socket extension is not enabled. NewMagellan will not work quite right';
}
?>
<br />
<table id="install" border=0 cellspacing=0 cellpadding=4>
<tr>
<td style="width: 30%;">OS: </td><td><select name=OS>
<?php
if (isset($_REQUEST['OS'])) $CORE_LOCAL->set('OS',$_REQUEST['OS']);
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
if (isset($_REQUEST['LANE_NO']) && is_numeric($_REQUEST['LANE_NO'])) $CORE_LOCAL->set('laneno',$_REQUEST['LANE_NO']);
printf("<input type=text name=LANE_NO value=\"%d\" />",
	$CORE_LOCAL->get('laneno'));
confsave('laneno',$CORE_LOCAL->get('laneno'));
?>
</td></tr><tr><td colspan=2 class="tblheader">
<h3>Database set up</h3></td></tr>
<tr><td>
Lane database host: </td><td>
<?php
if (isset($_REQUEST['LANE_HOST'])) $CORE_LOCAL->set('localhost',$_REQUEST['LANE_HOST']);
printf("<input type=text name=LANE_HOST value=\"%s\" />",
	$CORE_LOCAL->get('localhost'));
confsave('localhost',"'".$CORE_LOCAL->get('localhost')."'");
?>
</td></tr><tr><td>
Lane database type:</td>
<td><select name=LANE_DBMS>
<?php
if(isset($_REQUEST['LANE_DBMS'])) $CORE_LOCAL->set('DBMS',$_REQUEST['LANE_DBMS']);
if ($CORE_LOCAL->get('DBMS') == 'mssql'){
	echo "<option value=mysql>MySQL</option>";
	echo "<option value=mssql selected>SQL Server</option>";
}
else {
	echo "<option value=mysql selected>MySQL</option>";
	echo "<option value=mssql>SQL Server</option>";
}
confsave('DBMS',"'".$CORE_LOCAL->get('DBMS')."'");
?>
</select></td></tr>
<tr><td>Lane user name:</td><td>
<?php
if (isset($_REQUEST['LANE_USER'])) $CORE_LOCAL->set('localUser',$_REQUEST['LANE_USER']);
printf("<input type=text name=LANE_USER value=\"%s\" />",
	$CORE_LOCAL->get('localUser'));
confsave('localUser',"'".$CORE_LOCAL->get('localUser')."'");
?>
</td></tr><tr><td>
Lane password:</td><td>
<?php
if (isset($_REQUEST['LANE_PASS'])) $CORE_LOCAL->set('localPass',$_REQUEST['LANE_PASS']);
printf("<input type=password name=LANE_PASS value=\"%s\" />",
	$CORE_LOCAL->get('localPass'));
confsave('localPass',"'".$CORE_LOCAL->get('localPass')."'");
?>
</td></tr><tr><td>
Lane operational DB:</td><td>
<?php
if (isset($_REQUEST['LANE_OP_DB'])) $CORE_LOCAL->set('pDatabase',$_REQUEST['LANE_OP_DB']);
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
if (isset($_REQUEST['LANE_TRANS_DB'])) $CORE_LOCAL->set('tDatabase',$_REQUEST['LANE_TRANS_DB']);
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
if (isset($_REQUEST['SERVER_HOST'])) $CORE_LOCAL->set('mServer',$_REQUEST['SERVER_HOST']);
printf("<input type=text name=SERVER_HOST value=\"%s\" />",
	$CORE_LOCAL->get('mServer'));
confsave('mServer',"'".$CORE_LOCAL->get('mServer')."'");
?>
</td></tr><tr><td>
Server database type:</td><td>
<select name=SERVER_TYPE>
<?php
if (isset($_REQUEST['SERVER_TYPE'])) $CORE_LOCAL->set('mDBMS',$_REQUEST['SERVER_TYPE']);
if ($CORE_LOCAL->get('mDBMS') == 'mssql'){
	echo "<option value=mysql>MySQL</option>";
	echo "<option value=mssql selected>SQL Server</option>";
}
else {
	echo "<option value=mysql selected>MySQL</option>";
	echo "<option value=mssql>SQL Server</option>";
}
confsave('mDBMS',"'".$CORE_LOCAL->get('mDBMS')."'");
?>
</select></td></tr><tr><td>
Server user name:</td><td>
<?php
if (isset($_REQUEST['SERVER_USER'])) $CORE_LOCAL->set('mUser',$_REQUEST['SERVER_USER']);
printf("<input type=text name=SERVER_USER value=\"%s\" />",
	$CORE_LOCAL->get('mUser'));
confsave('mUser',"'".$CORE_LOCAL->get('mUser')."'");
?>
</td></tr><tr><td>
Server password:</td><td>
<?php
if (isset($_REQUEST['SERVER_PASS'])) $CORE_LOCAL->set('mPass',$_REQUEST['SERVER_PASS']);
printf("<input type=password name=SERVER_PASS value=\"%s\" />",
	$CORE_LOCAL->get('mPass'));
confsave('mPass',"'".$CORE_LOCAL->get('mPass')."'");
?>
</td></tr><tr><td>
Server database name:</td><td>
<?php
if (isset($_REQUEST['SERVER_DB'])) $CORE_LOCAL->set('mDatabase',$_REQUEST['SERVER_DB']);
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
echo "<table><tr><th>ID</th><th>Rate (%)</th><th>Description</th></tr>";
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

	$chargeCodeQ = "CREATE TABLE chargecode (
		staffID varchar(4),
		chargecode varchar(6))";
	if (!$db->table_exists('chargecode',$name)){
		$db->query($chargeCodeQ,$name);
		db_structure_modify($db,'chargecode',$chargeCodeQ,$errors);
	}

	$couponCodeQ = "CREATE TABLE couponcodes (
		Code varchar(4),
		Qty int,
		Value real)";
	if (!$db->table_exists('couponcodes',$name)){
		db_structure_modify($db,'couponcodes',$couponCodeQ,$errors);
		load_sample_data($db,'couponcodes');
	}

	$custDataQ = "CREATE TABLE `custdata` (
	  `CardNo` int(8) default NULL,
	  `personNum` tinyint(4) NOT NULL default '1',
	  `LastName` varchar(30) default NULL,
	  `FirstName` varchar(30) default NULL,
	  `CashBack` real NOT NULL default '60',
	  `Balance` real NOT NULL default '0',
	  `Discount` smallint(6) default NULL,
	  `MemDiscountLimit` real NOT NULL default '0',
	  `ChargeOk` tinyint(4) NOT NULL default '1',
	  `WriteChecks` tinyint(4) NOT NULL default '1',
	  `StoreCoupons` tinyint(4) NOT NULL default '1',
	  `Type` varchar(10) NOT NULL default 'pc',
	  `memType` tinyint(4) default NULL,
	  `staff` tinyint(4) NOT NULL default '0',
	  `SSI` tinyint(4) NOT NULL default '0',
	  `Purchases` real NOT NULL default '0',
	  `NumberOfChecks` smallint(6) NOT NULL default '0',
	  `memCoupons` int(11) NOT NULL default '1',
	  `blueLine` varchar(50) default NULL,
	  `Shown` tinyint(4) NOT NULL default '1',
	  `id` int(11) NOT NULL auto_increment,
	  PRIMARY KEY  (`id`),
	  KEY `CardNo` (`CardNo`),
	  KEY `LastName` (`LastName`)
	) ENGINE=MyISAM AUTO_INCREMENT=926 DEFAULT CHARSET=latin1;";
	if ($type == 'mssql'){
		$custDataQ = "CREATE TABLE [custdata] (
		[CardNo] [varchar] (25) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
		[personNum] [varchar] (3) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
		[LastName] [varchar] (30) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
		[FirstName] [varchar] (30) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
		[CashBack] [money] NULL ,
		[Balance] [money] NULL ,
		[Discount] [smallint] NULL ,
		[MemDiscountLimit] [money] NULL ,
		[ChargeOk] [bit] NULL ,
		[WriteChecks] [bit] NULL ,
		[StoreCoupons] [bit] NULL ,
		[Type] [varchar] (10) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
		[memType] [smallint] NULL ,
		[staff] [tinyint] NULL ,
		[SSI] [tinyint] NULL ,
		[Purchases] [money] NULL ,
		[NumberOfChecks] [smallint] NULL ,
		[memCoupons] [int] NULL ,
		[blueLine] [varchar] (50) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
		[Shown] [tinyint] NULL ,
		[id] [int] IDENTITY (1, 1) NOT NULL 
		) ON [PRIMARY]";
	}
	if (!$db->table_exists('custdata',$name)){
		db_structure_modify($db,'custdata',$custDataQ,$errors);
	}

	$cardsQ = "CREATE TABLE memberCards (upc VARCHAR(13),card_no INT,
			PRIMARY KEY(upc))";
	if (!$db->table_exists('memberCards',$name)){
		db_structure_modify($db,'memberCards',$cardsQ,$errors);
	}

	$cardsViewQ = "CREATE VIEW memberCardsView AS 
		SELECT CONCAT(" . $CORE_LOCAL->get('memberUpcPrefix') . ",c.CardNo) as upc, c.CardNo as card_no FROM custdata c";
	if (!$db->table_exists('memberCardsView',$name)){
		db_structure_modify($db,'memberCardsView',$cardsViewQ,$errors);
	}
	
	$deptQ = "CREATE TABLE departments (
		dept_no smallint,
		dept_name varchar(30),
		dept_tax tinyint,
		dept_fs tinyint,
		dept_limit real,
		dept_minimum real,
		dept_discount tinyint,
		modified datetime,
		modifiedby int,
		PRIMARY KEY (dept_no))";
	if (!$db->table_exists('departments',$name)){
		db_structure_modify($db,'departments',$deptQ,$errors);
	}

	$empQ = "CREATE TABLE employees (
		emp_no smallint,
		CashierPassword int,
		AdminPassword int,
		FirstName varchar(255),
		LastName varchar(255),
		JobTitle varchar(255),
		EmpActive tinyint,
		frontendsecurity smallint,
		backendsecurity smallint,
		birthdate datetime,
		PRIMARY KEY (emp_no))";
	if (!$db->table_exists('employees',$name)){
		db_structure_modify($db,'employees',$empQ,$errors);
	}

	$globalQ = "CREATE TABLE globalvalues (
		CashierNo int,
		Cashier varchar(30),
		LoggedIn tinyint,
		TransNo int,
		TTLFlag tinyint,
		FntlFlag tinyint,
		TaxExempt tinyint)";
	if (!$db->table_exists('globalvalues',$name)){
		db_structure_modify($db,'globalvalues',$globalQ,$errors);
		load_sample_data($db,'globalvalues');
	}

	$prodQ = "CREATE TABLE `products` (
	  `upc` varchar(13) default NULL,
	  `description` varchar(30) default NULL,
	  `normal_price` real default NULL,
	  `pricemethod` smallint(6) default NULL,
	  `groupprice` real default NULL,
	  `quantity` smallint(6) default NULL,
	  `special_price` real default NULL,
	  `specialpricemethod` smallint(6) default NULL,
	  `specialgroupprice` real default NULL,
	  `specialquantity` smallint(6) default NULL,
	  `start_date` datetime default NULL,
	  `end_date` datetime default NULL,
	  `department` smallint(6) default NULL,
	  `size` varchar(9) default NULL,
	  `tax` smallint(6) default NULL,
	  `foodstamp` tinyint(4) default NULL,
	  `scale` tinyint(4) default NULL,
	  `scaleprice` tinyint(4) default 0 NULL,
	  `mixmatchcode` varchar(13) default NULL,
	  `modified` datetime default NULL,
	  `advertised` tinyint(4) default NULL,
	  `tareweight` real default NULL,
	  `discount` smallint(6) default NULL,
	  `discounttype` tinyint(4) default NULL,
	  `unitofmeasure` varchar(15) default NULL,
	  `wicable` smallint(6) default NULL,
	  `qttyEnforced` tinyint(4) default NULL,
	  `idEnforced` tinyint(4) default 0 NULL,
	  `cost` real default 0 NULL,
	  `inUse` tinyint(4) default NULL,
	  `numflag` int(11) default 0 NULL,
	  `subdept` smallint(4) default NULL,
	  `deposit` real default NULL,
	  `local` int(11) default 0 NULL,
	  `store_id` smallint default 0,
	  `id` int(11) NOT NULL,
	  KEY `upc` (`upc`),
	  KEY `description` (`description`),
	  KEY `normal_price` (`normal_price`)
	) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
	if ($type == 'mssql'){
		$prodQ = "CREATE TABLE [products] (
		[upc] [varchar] (13) COLLATE SQL_Latin1_General_CP1_CI_AS NOT NULL ,
		[description] [varchar] (30) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
		[normal_price] [money] NULL ,
		[pricemethod] [smallint] NULL ,
		[groupprice] [money] NULL ,
		[quantity] [smallint] NULL ,
		[special_price] [money] NULL ,
		[specialpricemethod] [smallint] NULL ,
		[specialgroupprice] [money] NULL ,
		[specialquantity] [smallint] NULL ,
		[start_date] [datetime] NULL ,
		[end_date] [datetime] NULL ,
		[department] [smallint] NOT NULL ,
		[size] [varchar] (9) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
		[tax] [smallint] NOT NULL ,
		[foodstamp] [bit] NOT NULL ,
		[scale] [bit] NOT NULL ,
		[scaleprice] [tinyint] NULL ,
		[mixmatchcode] [varchar] (13) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
		[modified] [datetime] NULL ,
		[advertised] [bit] NOT NULL ,
		[tareweight] [float] NULL ,
		[discount] [smallint] NULL ,
		[discounttype] [tinyint] NULL ,
		[unitofmeasure] [varchar] (15) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
		[wicable] [smallint] NULL ,
		[qttyEnforced] [tinyint] NULL ,
		[idEnforced] [tinyint] NULL ,
		[cost] [money] NULL ,
		[inUse] [tinyint] NOT NULL ,
		[numflag] [int] NULL ,
		[subdept] [int] NULL ,
		[deposit] [money] NULL ,
		[local] [int] NULL ,
		[store_id] [smallint] NULL,
		[id] [int] NOT NULL ,
		CONSTRAINT [PK_Products] PRIMARY KEY  CLUSTERED 
		(
			[upc]
		) WITH  FILLFACTOR = 90  ON [PRIMARY] 
		) ON [PRIMARY]";
	}
	if (!$db->table_exists('products',$name)){
		db_structure_modify($db,'products',$prodQ,$errors);
	}

	$promoQ = "CREATE TABLE promomsgs (
		startDate datetime,
		endDate datetime,
		promoMsg varchar(50),
		sequence tinyint)";
	if (!$db->table_exists('promomsgs',$name)){
		db_structure_modify($db,'promomsgs',$promoQ,$errors);
	}

	$drQ = "CREATE TABLE dateRestrict (
                upc varchar(13),
                dept_ID int,
                restrict_date date default null,
                restrict_dow smallint default null,
                restrict_start time default null,
                restrict_end time default null,
                INDEX (upc),
                INDEX (dept_ID)
        )";
	if (!$db->table_exists('dateRestrict',$name)){
		db_structure_modify($db,'dateRestrict',$drQ,$errors);
	}

	$tenderQ = "CREATE TABLE tenders (
		TenderID smallint,
		TenderCode varchar(255),
		TenderName varchar(255),
		TenderType varchar(255),
		ChangeMessage varchar(255),
		MinAmount real,
		MaxAmount real,
		MaxRefund real)";
	if(!$db->table_exists('tenders',$name)){
		db_structure_modify($db,'tenders',$tenderQ,$errors);
		load_sample_data($db,'tenders');
	}

	$ccView = "CREATE VIEW chargecodeview AS
		SELECT c.staffID, c.chargecode, d.blueLine
		FROM chargecode AS c, custdata AS d
		WHERE c.staffID = d.CardNo";
	if (!$db->table_exists('chargecodeview',$name)){
		db_structure_modify($db,'chargecodeview',$ccView,$errors);
	}

	$subQ = "CREATE TABLE subdepts (
		subdept_no smallint,
		subdept_name varchar(30),
		dept_ID smallint)";
	if(!$db->table_exists('subdepts',$name)){
		db_structure_modify($db,'subdepts',$subQ,$errors);
	}

	$pmV = "CREATE view promoMsgsView as
		select 
		 * from promomsgs
		where ".
		$db->datediff('startDate',$db->now())." >= 0
		and ".
		$db->datediff($db->now(),'endDate')." >= 0
		order by sequence";
	if(!$db->table_exists('promoMsgsView',$name)){
		db_structure_modify($db,'promoMsgsView',$pmV,$errors);
	}

	$custRpt = "CREATE TABLE customReceipt (
		text varchar(20),
		seq int,
		type varchar(20)
		)";
	if(!$db->table_exists('customReceipt',$name)){
		db_structure_modify($db,'customReceipt',$custRpt,$errors);
	}

	$dCoup = "CREATE TABLE disableCoupon (
		upc varchar(13),
		reason text,
		PRIMARY KEY (upc)
		)";
	if(!$db->table_exists('disableCoupon',$name)){
		db_structure_modify($db,'disableCoupon',$dCoup,$errors);
	}

	$houseCoup = "CREATE TABLE houseCoupons (
		coupID int,
		endDate datetime,
		`limit` smallint,
		memberOnly smallint,
		discountType varchar(2),
		discountValue real,
		minType varchar(2),
		minValue real,
		department int)";
	if ($type == 'mssql')
		$houseCoup = str_replace("`","",$houseCoup);
	if(!$db->table_exists('houseCoupons',$name)){
		db_structure_modify($db,'houseCoupons',$houseCoup,$errors);
	}

	$hvQ = "CREATE TABLE houseVirtualCoupons (
		card_no int,
		coupID int,
		description varchar(100),
		start_date datetime,
		end_date datetime,
		PRIMARY KEY (card_no, coupID)
		)";
	if(!$db->table_exists('houseVirtualCoupons',$name)){
		db_structure_modify($db,'houseVirtualCoupons',$hvQ,$errors);
	}

	$hciQ = "CREATE TABLE houseCouponItems (
		coupID int,
		upc varchar(13),
		type varchar(15))";
	if(!$db->table_exists('houseCouponItems',$name)){
		db_structure_modify($db,'houseCouponItems',$hciQ,$errors);
	}

	$mcV = "CREATE view memchargebalance as
		SELECT 
		c.CardNo,
		c.memDiscountLimit - c.Balance AS availBal,	
		c.Balance as balance
		FROM custdata AS c WHERE personNum = 1";
	if (!$db->table_exists('memchargebalance',$name)){
		db_structure_modify($db,'memchargebalance',$mcV,$errors);
	}

	$uaQ = "CREATE TABLE unpaid_ar_today (
		card_no int,
		old_balance real,
		recent_payments real,
		primary key (card_no)
		)";
	if (!$db->table_exists('unpaid_ar_today',$name)){
		db_structure_modify($db,'unpaid_ar_today',$uaQ,$errors);
	}

	$lcQ = "CREATE TABLE lane_config (
		modified datetime
		)";
	if (!$db->table_exists('lane_config',$name)){
		db_structure_modify($db,'lane_config',$lcQ,$errors);
		$db->query("INSERT INTO lane_config VALUES ('1900-01-01 00:00:00')");
	}
	return $errors;
}

function create_trans_dbs($db,$type){
	global $CORE_LOCAL;
	$name = $CORE_LOCAL->get('tDatabase');
	$errors = array();

	$actQ = "CREATE TABLE activities (
		Activity tinyint,
		Description varchar(15))";
	if (!$db->table_exists('activities',$name)){
		db_structure_modify($db,'activities',$actQ,$errors);
	}

	$alogQ = "CREATE TABLE activitylog (
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
	if (!$db->table_exists('activitylog',$name)){
		db_structure_modify($db,'activitylog',$alogQ,$errors);
	}

	$atempQ = str_replace("activitylog","activitytemplog",$alogQ);
	if (!$db->table_exists('activitytemplog',$name)){
		db_structure_modify($db,'activitytemplog',$atempQ,$errors);
	}

	$alogQ = str_replace("activitylog","alog",$alogQ);
	if (!$db->table_exists('alog',$name)){
		db_structure_modify($db,'alog',$alogQ,$errors);
	}

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
	  `mixMatch` varchar(12) default NULL,
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
		[mixMatch] [nvarchar] (12) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
		[matched] [smallint] NOT NULL ,
		[memType] [smallint] NULL ,
		[staff] [tinyint] NULL ,
		[numflag] [smallint] NULL ,
		[charflag] [nvarchar] (2) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
		[card_no] [nvarchar] (6) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
		[trans_id] [int] NOT NULL 
		) ON [PRIMARY]";
	}
	if (!$db->table_exists('dtransactions',$name)){
		db_structure_modify($db,'dtransactions',$dtransQ,$errors);
	}

	$ltQ = str_replace("dtransactions","localtrans",$dtransQ);
	if (!$db->table_exists('localtrans',$name)){
		db_structure_modify($db,'localtrans',$ltQ,$errors);
	}

	$ltaQ = str_replace("dtransactions","localtransarchive",$dtransQ);
	if (!$db->table_exists('localtransarchive',$name)){
		db_structure_modify($db,'localtransarchive',$ltaQ,$errors);
	}

	$susQ = str_replace("dtransactions","suspended",$dtransQ);
	if (!$db->table_exists('suspended',$name)){
		db_structure_modify($db,'suspended',$susQ,$errors);
	}

	$ltodayQ = str_replace("dtransactions","localtrans_today",$dtransQ);
	if(!$db->table_exists('localtrans_today',$name)){
		db_structure_modify($db,'localtrans_today',$ltodayQ,$errors);
	}

	$lttQ = "CREATE TABLE `localtemptrans` (
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
	  `cost` real default 0.00,
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
	  `mixMatch` varchar(12) default NULL,
	  `matched` smallint(6) default NULL,
	  `memType` varchar(10) default NULL,
	  `staff` tinyint(4) default 0,
	  `numflag` smallint(6) default 0,
	  `charflag` varchar(2) default '',
	  `card_no` varchar(255) default NULL,
	  `trans_id` int(11) NOT NULL auto_increment,
	  PRIMARY KEY  (`trans_id`)
	)";
	if ($type == 'mssql'){
		$lttQ = "CREATE TABLE [localtemptrans] (
		[datetime] [smalldatetime] NOT NULL ,
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
		[mixMatch] [nvarchar] (12) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
		[matched] [smallint] NOT NULL ,
		[memType] [smallint] NOT NULL ,
		[staff] [tinyint] NOT NULL ,
		[numflag] [smallint] NULL ,
		[charflag] [nvarchar] (2) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
		[card_no] [nvarchar] (6) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
		[trans_id] [int] IDENTITY (1, 1) NOT NULL 
		) ON [PRIMARY]";
	}
	if (!$db->table_exists('localtemptrans',$name)){
		db_structure_modify($db,'localtemptrans',$lttQ,$errors);
	}

	$failQ = "CREATE TABLE failedscans (
		upc varchar(15),
		lane_no tinyint,
		emp_no tinyint,
		trans_no int,
		fdate datetime)";
	if(!$db->table_exists('failedscans',$name)){
		db_structure_modify($db,'failedscans',$failQ,$errors);
	}

	$lttoday = "CREATE VIEW localtranstoday AS
		SELECT * FROM localtrans_today WHERE "
		.$db->datediff($db->now(),'datetime')
		." = 0";
	if (!$db->table_exists('localtranstoday',$name)){
		db_structure_modify($db,'localtranstoday',$lttoday,$errors);
	}

	$mAdd = "CREATE VIEW memdiscountadd AS
		select 
		max(datetime) as datetime, 
		register_no, 
		emp_no, 
		trans_no, 
		upc, 
		CASE WHEN volDiscType IN (3,4) THEN 'Set Discount' ELSE description END as description, 
		'I' as trans_type, 
		'' as trans_subtype,
		'M' as trans_status, 
		max(department) as department, 
		1 as quantity, 
		0 as scale, 
		0 as cost,
		-1 * sum(memDiscount) as unitPrice, 
		-1 * sum(memDiscount) as total, 
		-1 * sum(memDiscount )as regPrice, 
		max(tax) as tax, 
		max(foodstamp) as foodstamp, 
		0 as discount, 
		-1 * sum(memDiscount) as memDiscount, 
		3 as discountable, 
		20 as discounttype, 
		8 as voided,
		0 as percentDiscount,
		0 as ItemQtty, 
		0 as volDiscType, 
		0 as volume, 
		0 as VolSpecial, 
		0 as mixMatch, 
		0 as matched, 
		0 as memType,
		0 as staff,
		0 as numflag,
		'' as charflag,
		 card_no as card_no
		from localtemptrans 
		where ((discounttype = 2 and unitPrice = regPrice) or trans_status = 'M') 
		group by register_no, emp_no, trans_no, upc, description, card_no 
		having 
		sum(memDiscount)<> 0";
	if(!$db->table_exists('memdiscountadd',$name)){
		db_structure_modify($db,'memdiscountadd',$mAdd,$errors);
	}

	$mRem = "CREATE view memdiscountremove as
		Select 
		max(datetime) as datetime, 
		register_no, 
		emp_no, 
		trans_no, 
		upc, 
		CASE WHEN volDiscType IN (3,4) THEN 'Set Discount' ELSE description END as description, 
		'I' as trans_type, 
		'' as trans_subtype, 
		'M' as trans_status, 
		max(department) as department, 
		1 as quantity, 
		0 as scale, 
		0 as cost,
		-1 * (sum(case when (discounttype = 2 and unitPrice <> regPrice) then -1 * memDiscount 
		else memDiscount end)) as unitPrice, 
		-1 * (sum(case when (discounttype = 2 and unitPrice <> regPrice) then -1 * memDiscount 
		else memDiscount end)) as total, 
		-1 * (sum(case when (discounttype = 2 and unitPrice <> regPrice) then -1 * memDiscount 
		else memDiscount end))as regPrice, 
		max(tax) as tax, 
		max(foodstamp) as foodstamp, 
		0 as discount, 
		-1 * (sum(case when (discounttype = 2 and unitPrice <> regPrice) then -1 * memDiscount 
		else memDiscount end)) as memDiscount, 
		3 as discountable, 
		20 as discounttype, 
		8 as voided, 
		0 as percentDiscount,
		0 as ItemQtty, 
		0 as volDiscType, 
		0 as volume, 
		0 as VolSpecial, 
		0 as mixMatch, 
		0 as matched, 
		0 as memType,
		0 as staff,
		0 as numflag,
		'' as charflag,
		card_no as card_no
		from localtemptrans 
		where ((discounttype = 2 and unitPrice <> regPrice) or trans_status = 'M') 
		group by register_no, emp_no, trans_no, upc, description, card_no 
		having 
		sum(case when (discounttype = 2 and unitPrice <> regPrice) then -1 * memDiscount 
		else memDiscount end)<> 0";
	if(!$db->table_exists('memdiscountremove',$name)){
		db_structure_modify($db,'memdiscountremove',$mRem,$errors);
	}

	$rplist = "CREATE VIEW rp_list AS
		SELECT min(datetime) as time,
		register_no,
		emp_no,
		trans_no,
		sum(CASE WHEN trans_type = 'T' THEN -1*total ELSE 0 END) as total
		from localtranstoday
		GROUP BY register_no,emp_no,trans_no";
	if (!$db->table_exists('rp_list',$name)){
		db_structure_modify($db,'rp_list',$rplist,$errors);
	}

	$taxQ = "CREATE TABLE taxrates (
		id		int,
		rate		float,
		`description`	varchar(50))";
	if ($type == 'mssql')
		$taxQ = str_replace("`","",$taxQ);
	if(!$db->table_exists('taxrates',$name)){
		db_structure_modify($db,'taxrates',$taxQ,$errors);
	}

	$screen = "CREATE view screendisplay as 
		select 
		CASE
		WHEN (voided = 5 or voided = 11 or voided = 17 or trans_type = 'T')
			THEN ''
		ELSE
			l.description
		END
		as description,
		CASE
		WHEN(discounttype = 3 and trans_status = 'V')
			THEN CONCAT(ItemQtty,' /',UnitPrice)
		WHEN (voided = 5)
			THEN 'Discount'
		WHEN (trans_status = 'M')
			THEN 'Mbr special'
		WHEN (trans_status = 'S')
			THEN 'Staff special'
		WHEN (scale <> 0 and quantity <> 0 and unitPrice <> 0.01)
			THEN CONCAT( quantity,' @ ',unitPrice)
		WHEN (SUBSTRING(upc, 1, 3) = '002')
			THEN CONCAT( itemQtty,' @ ',regPrice)
		WHEN (abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity = 1)
			THEN CONCAT(volume,' for ',unitPrice)
		WHEN (abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity <> 1)
			THEN CONCAT(Quantity,' @ ',Volume,' for ',unitPrice)
		WHEN (abs(itemQtty) > 1 and discounttype = 3)
			THEN CONCAT(ItemQtty,' /',UnitPrice)
		WHEN (abs(itemQtty) > 1)
			THEN CONCAT(quantity,' @ ',unitPrice)	
		WHEN (voided = 3)
			THEN 'Total '
		WHEN (voided = 5)
			THEN 'Discount '
		WHEN (voided = 7)
			THEN ''
		WHEN (voided = 11 or voided = 17)
			THEN upc
		WHEN (matched > 0)
			THEN '1 w/ vol adj'
		WHEN (trans_type = 'T')
			THEN l.description
		ELSE
			''
		END
		as comment,
		CASE
		WHEN (voided = 3 or voided = 5 or voided = 7 or voided = 11 or voided = 17)
			THEN unitPrice
		WHEN (trans_status = 'D')
			THEN ''
		ELSE
			total
		END
		as total,
		CASE
		WHEN (trans_status = 'V')
			THEN 'VD'
		WHEN (trans_status = 'R')
			THEN 'RF'
		WHEN (trans_status = 'C')
			THEN 'MC'
		WHEN (tax = 1 and foodstamp <> 0)
			THEN 'TF'
		WHEN (tax = 1 and foodstamp = 0)
			THEN 'T' 
		WHEN (tax > 1 and foodstamp <> 0)
			THEN CONCAT(LEFT(t.description,1),'F')
		WHEN (tax > 1 and foodstamp = 0)
			THEN LEFT(t.description,1)
		WHEN (tax = 0 and foodstamp <> 0)
			THEN 'F'
		WHEN (tax = 0 and foodstamp = 0)
			THEN ''
		ELSE
			''
		END
		as status,
		CASE
		WHEN (trans_status = 'V' or trans_type = 'T' or trans_status = 'R' or trans_status = 'C' or trans_status = 'M' or voided = 17 or trans_status = 'J')
			THEN '800000'
		WHEN ((discounttype <> 0 and (matched > 0 or volDiscType=0)) or voided = 2 or voided = 6 or voided = 4 or voided = 5 or voided = 10 or voided = 22)
			THEN '408080'
		WHEN (voided = 3 or voided = 11)
			THEN '000000'
		WHEN (voided = 7)
			THEN '800080'
		ELSE
			'004080'
		END
		as lineColor,
		discounttype,
		trans_type,
		trans_status,
		voided,
		trans_id
		from localtemptrans as l
		left join taxrates as t
		on l.tax = t.id
		WHERE trans_type <> 'L'
		order by trans_id";
	if ($type == 'mssql'){
		$screen = "CREATE view screendisplay as 
			select 
			CASE
			WHEN (voided = 5 or voided = 11 or voided = 17 or trans_type = 'T')
				THEN ''
			ELSE
				l.description
			END
			as description,
			CASE
			WHEN(discounttype = 3 and trans_status = 'V')
				THEN ItemQtty+' /'+UnitPrice
			WHEN (voided = 5)
				THEN 'Discount'
			WHEN (trans_status = 'M')
				THEN 'Mbr special'
			WHEN (trans_status = 'S')
				THEN 'Staff special'
			WHEN (scale <> 0 and quantity <> 0)
				THEN quantity+' @ '+unitPrice
			WHEN (SUBSTRING(upc, 1, 3) = '002')
				THEN itemQtty+' @ '+regPrice
			WHEN (abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity = 1)
				THEN volume+' for '+unitPrice
			WHEN (abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity <> 1)
				THEN Quantity+' @ '+Volume+' for '+unitPrice
			WHEN (abs(itemQtty) > 1 and discounttype = 3)
				THEN ItemQtty+' /'+UnitPrice
			WHEN (abs(itemQtty) > 1)
				THEN quantity+' @ '+unitPrice
			WHEN (voided = 3)
				THEN 'Total '
			WHEN (voided = 5)
				THEN 'Discount '
			WHEN (voided = 7)
				THEN ''
			WHEN (voided = 11 or voided = 17)
				THEN upc
			WHEN (matched > 0)
				THEN '1 w/ vol adj'
			WHEN (trans_type = 'T')
				THEN l.description
			ELSE
				''
			END
			as comment,
			CASE
			WHEN (voided = 3 or voided = 5 or voided = 7 or voided = 11 or voided = 17)
				THEN unitPrice
			WHEN (trans_status = 'D')
				THEN ''
			ELSE
				total
			END
			as total,
			CASE
			WHEN (trans_status = 'V')
				THEN 'VD'
			WHEN (trans_status = 'R')
				THEN 'RF'
			WHEN (trans_status = 'C')
				THEN 'MC'
			WHEN (tax <> 0 and foodstamp <> 0)
				THEN 'TF'
			WHEN (tax <> 0 and foodstamp = 0)
				THEN 'T' 
			WHEN (tax = 0 and foodstamp <> 0)
				THEN 'F'
			WHEN (tax = 0 and foodstamp = 0)
				THEN ''
			ELSE
				''
			END
			as status,
			CASE
			WHEN (trans_status = 'V' or trans_type = 'T' or trans_status = 'R' or trans_status = 'C' or trans_status = 'M' or voided = 17 or trans_status = 'J')
				THEN '800000'
			WHEN ((discounttype <> 0 and (volDiscType=0 or matched>0)) or voided = 2 or voided = 6 or voided = 4 or voided = 5 or voided = 10 or voided = 22)
				THEN '408080'
			WHEN (voided = 3 or voided = 11)
				THEN '000000'
			WHEN (voided = 7)
				THEN '800080'
			ELSE
				'004080'
			END
			as lineColor,
			discounttype,
			trans_type,
			trans_status,
			voided,
			trans_id
			from localtemptrans
			WHERE trans_type <> 'L'
			order by trans_id";
	}
	db_structure_modify($db,'screendisplay','DROP VIEW screendisplay',$errors);
	if (!$db->table_exists('screendisplay',$name)){
		db_structure_modify($db,'screendisplay',$screen,$errors);
	}

	$sAdd = "CREATE VIEW staffdiscountadd AS
		select max(datetime) AS datetime,
		register_no AS register_no,
		emp_no AS emp_no,
		trans_no AS trans_no,
		upc AS upc,
		description AS description,
		'I' AS trans_type,
		'' AS trans_subtype,
		'S' AS trans_status,
		max(department) AS department,
		1 AS quantity,
		0 AS scale,
		0 AS cost,
		(-(1) * sum(memDiscount)) AS unitPrice,
		(-(1) * sum(memDiscount)) AS total,
		(-(1) * sum(memDiscount)) AS regPrice,
		max(tax) AS tax,
		max(foodstamp) AS foodstamp,
		0 AS discount,
		(-(1) * sum(memDiscount)) AS memDiscount,
		3 AS discountable,40 AS discounttype,8 AS voided,0 AS percentDiscount,
		0 AS ItemQtty,0 AS volDiscType,
		0 AS volume,0 AS VolSpecial,
		0 AS mixMatch,0 AS matched,
		0 as memType,
		0 as staff,
		0 as numflag,
		'' as charflag,
		card_no AS card_no 
		from localtemptrans 
		where (((discounttype = 4) and (unitPrice = regPrice)) or (trans_status = 'S')) 
		group by register_no,emp_no,trans_no,upc,description,card_no having (sum(memDiscount) <> 0)";
	if (!$db->table_exists('staffdiscountadd',$name)){
		db_structure_modify($db,'staffdiscountadd',$sAdd,$errors);
	}

	$sRem = "CREATE view staffdiscountremove as
		Select 
		max(datetime) as datetime, 
		register_no, 
		emp_no, 
		trans_no, 
		upc, 
		description, 
		'I' as trans_type, 
		'' as trans_subtype, 
		'S' as trans_status, 
		max(department) as department, 
		1 as quantity, 
		0 as scale, 
		0 as cost,
		-1 * (sum(case when (discounttype = 4 and unitPrice <> regPrice) then -1 * memDiscount 
		else memDiscount end)) as unitPrice, 
		-1 * (sum(case when (discounttype = 4 and unitPrice <> regPrice) then -1 * memDiscount 
		else memDiscount end)) as total, 
		-1 * (sum(case when (discounttype = 4 and unitPrice <> regPrice) then -1 * memDiscount 
		else memDiscount end))as regPrice, 
		max(tax) as tax, 
		max(foodstamp) as foodstamp, 
		0 as discount, 
		-1 * (sum(case when (discounttype = 4 and unitPrice <> regPrice) then -1 * memDiscount 
		else memDiscount end)) as memDiscount, 
		3 as discountable, 
		40 as discounttype, 
		8 as voided, 
		0 as percentDiscount,
		0 as ItemQtty, 
		0 as volDiscType, 
		0 as volume, 
		0 as VolSpecial, 
		0 as mixMatch, 
		0 as matched, 
		0 as memType,
		0 as staff,
		0 as numflag,
		'' as charflag,
		card_no as card_no
		from localtemptrans 
		where ((discounttype = 4 and unitPrice <> regPrice) or trans_status = 'S') 
		group by register_no, emp_no, trans_no, upc, description, card_no 
		having 
		sum(case when (discounttype = 4 and unitPrice <> regPrice) then -1 * memDiscount 
		else memDiscount end)<> 0";

	if(!$db->table_exists('staffdiscountremove',$name)){
		db_structure_modify($db,'staffdiscountremove',$sRem,$errors);
	}

	$slist = "CREATE VIEW suspendedlist AS
		SELECT register_no,
		emp_no,
		trans_no,
		sum(total) as total
		FROM suspended
		WHERE ".$db->datediff('datetime',$db->now())." = 0
		GROUP BY register_no,emp_no,trans_no";	
	if(!$db->table_exists('suspendedlist',$name)){
		db_structure_modify($db,'suspendedlist',$slist,$errors);
	}

	$stoday = "CREATE VIEW suspendedtoday AS
		SELECT * FROM suspended
		WHERE ".$db->datediff('datetime',$db->now())." = 0";
	if(!$db->table_exists('suspendedtoday',$name)){
		db_structure_modify($db,'suspendedtoday',$stoday,$errors);
	}

	$caQ = "CREATE TABLE couponApplied (
			emp_no		int,
			trans_no	int,
			quantity	float,
			trans_id	int)";
	if(!$db->table_exists('couponApplied',$name)){
		db_structure_modify($db,'couponApplied',$caQ,$errors);
	}


	/* lttsummary, lttsubtotals, and subtotals
	 * always get rebuilt to account for tax rate
	 * changes */
	include('buildLTTViews.php');
	$errors = buildLTTViews($db,$type,$errors);

	$tvQ = "CREATE VIEW taxView AS
		SELECT 
		r.id,
		r.description,
		CONVERT(SUM(CASE 
			WHEN l.trans_type IN ('I','D') AND discountable=0 THEN total 
			WHEN l.trans_type IN ('I','D') AND discountable<>0 THEN total * ((100-s.percentDiscount)/100)
			ELSE 0 END
		) * r.rate, DECIMAL(10,2)) as taxTotal,
		CONVERT(SUM(CASE 
			WHEN l.trans_type IN ('I','D') AND discountable=0 AND foodstamp=1 THEN total 
			WHEN l.trans_type IN ('I','D') AND discountable<>0 AND foodstamp=1 THEN total * ((100-s.percentDiscount)/100)
			ELSE 0 END
		), DECIMAL(10,2)) as fsTaxable,
		CONVERT(SUM(CASE 
			WHEN l.trans_type IN ('I','D') AND discountable=0 AND foodstamp=1 THEN total 
			WHEN l.trans_type IN ('I','D') AND discountable<>0 AND foodstamp=1 THEN total * ((100-s.percentDiscount)/100)
			ELSE 0 END
		) * r.rate, DECIMAL(10,2)) as fsTaxTotal,
		-1*MAX(fsTendered) as foodstampTender,
		MAX(r.rate) as taxrate
		FROM
		taxrates AS r 
		LEFT JOIN localtemptrans AS l
		ON r.id=l.tax
		JOIN lttsummary AS s
		WHERE trans_type <> 'L'
		GROUP BY r.id,r.description";
	if(!$db->table_exists('taxView',$name)){
		db_structure_modify($db,'taxView','DROP VIEW taxView',$errors);
		db_structure_modify($db,'taxView',$tvQ,$errors);
	}

	$lttR = "CREATE view ltt_receipt as 
		select
		description,
		case 
			when voided = 5 
				then 'Discount'
			when trans_status = 'M'
				then 'Mbr special'
			when trans_status = 'S'
				then 'Staff special'
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
			when tax <> 0 and foodstamp <> 0
				then 'TF'
			when tax <> 0 and foodstamp = 0
				then 'T' 
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
		from localtemptrans
		where voided <> 5 and UPC <> 'TAX' and UPC <> 'DISCOUNT'
		AND trans_type <> 'L'
		order by trans_id";
	if($type == 'mssql'){
		$lttR = "CREATE view ltt_receipt as 
			select
			description,
			case 
				when voided = 5 
					then 'Discount'
				when trans_status = 'M'
					then 'Mbr special'
				when trans_status = 'S'
					then 'Staff special'
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
				when tax <> 0 and foodstamp <> 0
					then 'TF'
				when tax <> 0 and foodstamp = 0
					then 'T' 
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
			from localtemptrans
			where voided <> 5 and UPC <> 'TAX' and UPC <> 'DISCOUNT'
			AND trans_type <> 'L'
			order by trans_id";
	}
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
					, right(concat(space(8), format(UnitPrice, 2)), 8) 
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
		description,
		case 
			when voided = 5 
				then 'Discount'
			when trans_status = 'M'
				then 'Mbr special'
			when trans_status = 'S'
				then 'Staff special'
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
			when tax <> 0 and foodstamp <> 0
				then 'TF'
			when tax <> 0 and foodstamp = 0
				then 'T' 
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
		from localtranstoday
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
				when tax <> 0 and foodstamp <> 0
					then 'TF'
				when tax <> 0 and foodstamp = 0
					then 'T' 
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
			from localtranstoday
			where voided <> 5 and UPC <> 'TAX' and UPC <> 'DISCOUNT'
			AND trans_type <> 'L'
			order by emp_no, trans_no, trans_id";
	}
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

	$etq = "CREATE TABLE efsnetTokens (
			expireDay datetime, 
			refNum varchar(50),
			token varchar(100),
			PRIMARY KEY (refNum)
		)";
	if(!$db->table_exists('efsnetTokens',$name)){
		db_structure_modify($db,'efsnetTokens',$etq,$errors);
	}

	$vrq = "CREATE TABLE valutecRequest (
		date int,
		cashierNo int,
		laneNo int,
		transNo int,
		transID int,
		datetime datetime,
		identifier varchar(10),
		terminalID varchar(20),
		live tinyint,
		mode varchar(32),
		amount real,
		PAN varchar(19),
		manual tinyint
		)";
	if(!$db->table_exists('valutecRequest',$name)){
		db_structure_modify($db,'valutecRequest',$vrq,$errors);
	}

	$vrp = "CREATE TABLE valutecResponse (
		date int,
		cashierNo int,
		laneNo int,
		transNo int,
		transID int,
		datetime datetime,
		identifier varchar(10),
		seconds float,
		commErr int,
		httpCode int,
		validResponse smallint,
		xAuthorized varchar(5),
		xAuthorizationCode varchar(9),
		xBalance varchar(8),
		xErrorMsg varchar(100)
		)";
	if(!$db->table_exists('valutecResponse',$name)){
		db_structure_modify($db,'valutecResponse',$vrp,$errors);
	}

	$vrqm = "CREATE TABLE valutecRequestMod (
		date int,
		cashierNo int,
		laneNo int,
		transNo int,
		transID int,
		datetime datetime,
		origAuthCode varchar(9),
		mode varchar(32),
		seconds float,
		commErr int,
		httpCode int,
		validResponse smallint,
		xAuthorized varchar(5),
		xAuthorizationCode varchar(9),
		xBalance varchar(8),
		xErrorMsg varchar(100)
		)";
	if(!$db->table_exists('valutecRequestMod',$name)){
		db_structure_modify($db,'valutecRequestMod',$vrqm,$errors);
	}

	$ccV = "CREATE view ccReceiptView 
		AS 
		select
		  (case r.mode
		    when 'tender' then 'Credit Card Purchase'
		    when 'retail_sale' then 'Credit Card Purchase'
		    when 'retail_alone_credit' then 'Credit Card Refund'
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
		    when 'retail_alone_credit' then 'Credit Card Refund CANCELLED'
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
	if(!$db->table_exists('ltt_grouped',$name)){
		db_structure_modify($db,'ltt_grouped',$lttG,$errors);
	}


	$lttreorderG = "CREATE   view ltt_receipt_reorder_g as
	select 
	description,
	case 
		when voided = 5 
			then 'Discount'
		when trans_status = 'M'
			then 'Mbr special'
		when trans_status = 'S'
			then 'Staff special'
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
		when tax = 2 and foodstamp <> 0
			then 'DF'
		when tax = 2 and foodstamp = 0
			then 'D' 
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
	from ltt_grouped
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
	'' as trans_subtype

	union

	select 
	concat('  ',promoMsg) as description,
	' ' as comment,
	0 as total,
	' ' as Status,
	' ' as trans_type,
	0 as unitPrice,
	0 as voided,
	sequence,
	'' as department,
	'' as upc,
	'' as trans_subtype
	from ".$CORE_LOCAL->get('pDatabase').".promoMsgsView";
	if($type == 'mssql'){
		$lttreorderG = "CREATE view ltt_receipt_reorder_g as
		select top 100 percent
		description,
		case 
			when voided = 5 
				then 'Discount'
			when trans_status = 'M'
				then 'Mbr special'
			when trans_status = 'S'
				then 'Staff special'
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
			when tax <> 0 and foodstamp <> 0
				then 'TF'
			when tax <> 0 and foodstamp = 0
				then 'T' 
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
		from ltt_grouped
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
		'' as trans_subtype

		union

		select top 100 percent
		'  ' + promoMsg as description,
		' ' as comment,
		0 as total,
		' ' as Status,
		' ' as trans_type,
		0 as unitPrice,
		0 as voided,
		sequence,
		'' as department,
		'' as upc,
		'' as trans_subtype
		from ".$CORE_LOCAL->get('pDatabase').".dbo.promoMsgsView";
	}
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
	if(!$db->table_exists('rp_ltt_grouped',$name)){
		db_structure_modify($db,'rp_ltt_grouped',$rplttG,$errors);
	}

	$rpreorderG = "CREATE    view rp_ltt_receipt_reorder_g as
		select 
		register_no,emp_no,trans_no,card_no,
		description,
		case 
			when voided = 5 
				then 'Discount'
			when trans_status = 'M'
				then 'Mbr special'
			when trans_status = 'S'
				then 'Staff special'
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
			when tax <> 0 and foodstamp <> 0
				then 'TF'
			when tax <> 0 and foodstamp = 0
				then 'T' 
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
		from rp_ltt_grouped
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
		'' as trans_subtype

		union

		select 
		0 as register_no, 0 as emp_no,0 as trans_no,0 as card_no,
		concat('  ',promoMsg) as description,
		' ' as comment,
		0 as total,
		' ' as Status,
		' ' as trans_type,
		0 as unitPrice,
		0 as voided,
		sequence,
		'' as department,
		'' as upc,
		'' as trans_subtype
		from ".$CORE_LOCAL->get('pDatabase').".promoMsgsView";
	if($type == 'mssql'){
		$rpreorderG = "CREATE     view rp_ltt_receipt_reorder_g as
		select top 100 percent
		register_no,emp_no,trans_no,card_no,
		description,
		case 
			when voided = 5 
				then 'Discount'
			when trans_status = 'M'
				then 'Mbr special'
			when trans_status = 'S'
				then 'Staff special'
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
			when tax <> 0 and foodstamp <> 0
				then 'TF'
			when tax <> 0 and foodstamp = 0
				then 'T' 
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
		from rp_ltt_grouped
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
		'' as trans_subtype

		union

		select top 100 percent
		0 as register_no, 0 as emp_no,0 as trans_no,0 as card_no,
		'  ' + promoMsg as description,
		' ' as comment,
		0 as total,
		' ' as Status,
		' ' as trans_type,
		0 as unitPrice,
		0 as voided,
		sequence,
		'' as department,
		'' as upc,
		'' as trans_subtype
		from ".$CORE_LOCAL->get('pDatabase').".dbo.promoMsgsView";
	}	
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

	return $errors;
}

?>
