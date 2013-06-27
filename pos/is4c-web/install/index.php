<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

return;
$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

ini_set('display_errors','1');

include($IS4C_PATH.'ini.php');
include($IS4C_PATH.'lib/lib.php');
include('util.php');
?>
<html>
<head>
<title>IS4C Web Installation</title>
<style type="text/css">
body {
	line-height: 1.5em;
}
</style>
</head>
<body>
Necessities
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="extra_data.php">Sample Data</a>
<form action=index.php method=post>
<h1>IS4C Install checks</h1>
<h3>Basics</h3>
<?php
if (function_exists('posix_getpwuid')){
	$chk = posix_getpwuid(posix_getuid());
	echo "PHP is running as: ".$chk['name']."<br />";
}
else
	echo "PHP is (probably) running as: ".get_current_user()."<br />";
if (is_writable('../ini.php'))
	echo '<i>ini.php</i> is writeable';
else
	echo '<b>Error</b>: ini.php is not writeable';
?>
<br />
<?php
if (!function_exists("socket_create")){
	echo '<b>Warning</b>: PHP socket extension is not enabled. NewMagellan will not work quite right';
}
?>
<br />
OS: <select name=OS>
<?php
if (isset($_REQUEST['OS'])) $IS4C_LOCAL->set('OS',$_REQUEST['OS']);
if ($IS4C_LOCAL->get('OS') == 'win32'){
	echo "<option value=win32 selected>Windows</option>";
	echo "<option value=other>*nix</option>";
}
else {
	echo "<option value=win32>Windows</option>";
	echo "<option value=other selected>*nix</option>";
}
confsave('OS',"'".$IS4C_LOCAL->get('OS')."'");
?>
</select><br />
Lane number:
<?php
if (isset($_REQUEST['LANE_NO']) && is_numeric($_REQUEST['LANE_NO'])) $IS4C_LOCAL->set('laneno',$_REQUEST['LANE_NO']);
printf("<input type=text name=LANE_NO value=\"%d\" />",
	$IS4C_LOCAL->get('laneno'));
confsave('laneno',$IS4C_LOCAL->get('laneno'));
?>
<br />
<hr />
<h3>Database set up</h3>
Lane database host: 
<?php
if (isset($_REQUEST['LANE_HOST'])) $IS4C_LOCAL->set('localhost',$_REQUEST['LANE_HOST']);
printf("<input type=text name=LANE_HOST value=\"%s\" />",
	$IS4C_LOCAL->get('localhost'));
confsave('localhost',"'".$IS4C_LOCAL->get('localhost')."'");
?>
<br />
Lane database type:
<select name=LANE_DBMS>
<?php
if(isset($_REQUEST['LANE_DBMS'])) $IS4C_LOCAL->set('DBMS',$_REQUEST['LANE_DBMS']);
if ($IS4C_LOCAL->get('DBMS') == 'mssql'){
	echo "<option value=mysql>MySQL</option>";
	echo "<option value=mssql selected>SQL Server</option>";
}
else {
	echo "<option value=mysql selected>MySQL</option>";
	echo "<option value=mssql>SQL Server</option>";
}
confsave('DBMS',"'".$IS4C_LOCAL->get('DBMS')."'");
?>
</select><br />
Lane user name:
<?php
if (isset($_REQUEST['LANE_USER'])) $IS4C_LOCAL->set('localUser',$_REQUEST['LANE_USER']);
printf("<input type=text name=LANE_USER value=\"%s\" />",
	$IS4C_LOCAL->get('localUser'));
confsave('localUser',"'".$IS4C_LOCAL->get('localUser')."'");
?>
<br />
Lane password:
<?php
if (isset($_REQUEST['LANE_PASS'])) $IS4C_LOCAL->set('localPass',$_REQUEST['LANE_PASS']);
printf("<input type=password name=LANE_PASS value=\"%s\" />",
	$IS4C_LOCAL->get('localPass'));
confsave('localPass',"'".$IS4C_LOCAL->get('localPass')."'");
?>
<br />
Lane operational DB:
<?php
if (isset($_REQUEST['LANE_OP_DB'])) $IS4C_LOCAL->set('pDatabase',$_REQUEST['LANE_OP_DB']);
printf("<input type=text name=LANE_OP_DB value=\"%s\" />",
	$IS4C_LOCAL->get('pDatabase'));
confsave('pDatabase',"'".$IS4C_LOCAL->get('pDatabase')."'");
?>
<br />
Testing Operation DB Connection:
<?php
$gotDBs = 0;
if (!class_exists('SQLManager'))
	include('../lib/SQLManager.php');
if ($IS4C_LOCAL->get("DBMS") == "mysql")
	$val = ini_set('mysql.connect_timeout',5);

$sql = db_test_connect($IS4C_LOCAL->get('localhost'),
		$IS4C_LOCAL->get('DBMS'),
		$IS4C_LOCAL->get('pDatabase'),
		$IS4C_LOCAL->get('localUser'),
		$IS4C_LOCAL->get('localPass'));
if ($sql === False){
	echo "<span style=\"color:red;\">Failed</span>";
}
else {
	echo "<span style=\"color:green;\">Succeeded</span>";
	create_op_dbs($sql,$IS4C_LOCAL->get('DBMS'));
	$gotDBs++;
	include('../lib/connect.php');
	//include('../auth/utilities.php');
	table_check();
}
?>
<br />
Lane transaction DB:
<?php
if (isset($_REQUEST['LANE_TRANS_DB'])) $IS4C_LOCAL->set('tDatabase',$_REQUEST['LANE_TRANS_DB']);
printf("<input type=text name=LANE_TRANS_DB value=\"%s\" />",
	$IS4C_LOCAL->get('tDatabase'));
confsave('tDatabase',"'".$IS4C_LOCAL->get('tDatabase')."'");
?>
<br />
Testing transational DB connection:
<?php
$sql = db_test_connect($IS4C_LOCAL->get('localhost'),
		$IS4C_LOCAL->get('DBMS'),
		$IS4C_LOCAL->get('tDatabase'),
		$IS4C_LOCAL->get('localUser'),
		$IS4C_LOCAL->get('localPass'));
if ($sql === False){
	echo "<span style=\"color:red;\">Failed</span>";
}
else {
	echo "<span style=\"color:green;\">Succeeded</span>";

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

	create_trans_dbs($sql,$IS4C_LOCAL->get('DBMS'));
	$gotDBs++;
}

?>
<br /><br />
Server database host: 
<?php
if (isset($_REQUEST['SERVER_HOST'])) $IS4C_LOCAL->set('mServer',$_REQUEST['SERVER_HOST']);
printf("<input type=text name=SERVER_HOST value=\"%s\" />",
	$IS4C_LOCAL->get('mServer'));
confsave('mServer',"'".$IS4C_LOCAL->get('mServer')."'");
?>
<br />
Server database type:
<select name=SERVER_TYPE>
<?php
if (isset($_REQUEST['SERVER_TYPE'])) $IS4C_LOCAL->set('mDBMS',$_REQUEST['SERVER_TYPE']);
if ($IS4C_LOCAL->get('mDBMS') == 'mssql'){
	echo "<option value=mysql>MySQL</option>";
	echo "<option value=mssql selected>SQL Server</option>";
}
else {
	echo "<option value=mysql selected>MySQL</option>";
	echo "<option value=mssql>SQL Server</option>";
}
confsave('mDBMS',"'".$IS4C_LOCAL->get('mDBMS')."'");
?>
</select><br />
Server user name:
<?php
if (isset($_REQUEST['SERVER_USER'])) $IS4C_LOCAL->set('mUser',$_REQUEST['SERVER_USER']);
printf("<input type=text name=SERVER_USER value=\"%s\" />",
	$IS4C_LOCAL->get('mUser'));
confsave('mUser',"'".$IS4C_LOCAL->get('mUser')."'");
?>
<br />
Server password:
<?php
if (isset($_REQUEST['SERVER_PASS'])) $IS4C_LOCAL->set('mPass',$_REQUEST['SERVER_PASS']);
printf("<input type=password name=SERVER_PASS value=\"%s\" />",
	$IS4C_LOCAL->get('mPass'));
confsave('mPass',"'".$IS4C_LOCAL->get('mPass')."'");
?>
<br />
Server database name:
<?php
if (isset($_REQUEST['SERVER_DB'])) $IS4C_LOCAL->set('mDatabase',$_REQUEST['SERVER_DB']);
printf("<input type=text name=SERVER_DB value=\"%s\" />",
	$IS4C_LOCAL->get('mDatabase'));
confsave('mDatabase',"'".$IS4C_LOCAL->get('mDatabase')."'");
?>
<br />
Testing server connection:
<?php
$sql = db_test_connect($IS4C_LOCAL->get('mServer'),
		$IS4C_LOCAL->get('mDBMS'),
		$IS4C_LOCAL->get('mDatabase'),
		$IS4C_LOCAL->get('mUser'),
		$IS4C_LOCAL->get('mPass'));
if ($sql === False){
	echo "<span style=\"color:red;\">Failed</span>";
}
else {
	echo "<span style=\"color:green;\">Succeeded</span>";
}
?>
<hr />
<h3>Tax</h3>
<i>Provided tax rates are used to create database views. As such,
descriptions should be DB-legal syntax (e.g., no spaces). A rate of
0% with ID 0 is automatically included</i>
<?php
$rates = array();
if($gotDBs == 2){
	$sql = new SQLManager($IS4C_LOCAL->get('localhost'),
			$IS4C_LOCAL->get('DBMS'),
			$IS4C_LOCAL->get('tDatabase'),
			$IS4C_LOCAL->get('localUser'),
			$IS4C_LOCAL->get('localPass'));
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
<input type=submit value="Save &amp; Re-run installation checks" />
</form>

<?php

function create_op_dbs($db,$type){
	global $IS4C_LOCAL;
	$name = $IS4C_LOCAL->get('pDatabase');

	$chargeCodeQ = "CREATE TABLE chargecode (
		staffID varchar(4),
		chargecode varchar(6))";
	if (!$db->table_exists('chargecode',$name)){
		$db->query($chargeCodeQ,$name);
	}

	$couponCodeQ = "CREATE TABLE couponcodes (
		Code varchar(4),
		Qty int,
		Value real)";
	if (!$db->table_exists('couponcodes',$name)){
		$db->query($couponCodeQ,$name);
		loaddata($db,'couponcodes');
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
		$db->query($custDataQ,$name);
	}

	$deptQ = "CREATE TABLE departments (
		dept_no smallint,
		dept_name varchar(100),
		dept_tax tinyint,
		dept_fs tinyint,
		dept_limit real,
		dept_minimum real,
		dept_discount tinyint,
		modified datetime,
		modifiedby int,
		PRIMARY KEY (dept_no))";
	if (!$db->table_exists('departments',$name)){
		$db->query($deptQ,$name);
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
		PRIMARY KEY (emp_no))";
	if (!$db->table_exists('employees',$name)){
		$db->query($empQ,$name);
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
		$db->query($globalQ,$name);
		loaddata($db,'globalvalues');
	}

	$prodQ = "CREATE TABLE `products` (
	  `upc` bigint(13) unsigned zerofill default NULL,
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
	  `id` int(11) NOT NULL auto_increment,
	  PRIMARY KEY  (`id`),
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
		[id] [int] IDENTITY (1, 1) NOT NULL ,
		CONSTRAINT [PK_Products] PRIMARY KEY  CLUSTERED 
		(
			[upc]
		) WITH  FILLFACTOR = 90  ON [PRIMARY] 
		) ON [PRIMARY]";
	}
	if (!$db->table_exists('products',$name)){
		$db->query($prodQ,$name);
	}

	$promoQ = "CREATE TABLE promomsgs (
		startDate datetime,
		endDate datetime,
		promoMsg varchar(50),
		sequence tinyint)";
	if (!$db->table_exists('promomsgs',$name)){
		$db->query($promoQ,$name);
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
		$db->query($tenderQ,$name);
		loaddata($db,'tenders');
	}

	$ccView = "CREATE VIEW chargecodeview AS
		SELECT c.staffID, c.chargecode, d.blueLine
		FROM chargecode AS c, custdata AS d
		WHERE c.staffID = d.CardNo";
	if (!$db->table_exists('chargecodeview',$name)){
		$db->query($ccView,$name);
	}

	$subQ = "CREATE TABLE subdepts (
		subdept_no smallint,
		subdept_name varchar(10),
		dept_ID smallint)";
	if(!$db->table_exists('subdepts',$name)){
		$db->query($subQ,$name);
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
		$db->query($pmV,$name);
	}

	$custRpt = "CREATE TABLE customReceipt (
		text varchar(20),
		seq int,
		type varchar(20)
		)";
	if(!$db->table_exists('customReceipt',$name)){
		$db->query($custRpt,$name);
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
		$db->query($houseCoup,$name);
	}

	$hciQ = "CREATE TABLE houseCouponItems (
		coupID int,
		upc varchar(13),
		type varchar(15))";
	if(!$db->table_exists('houseCouponItems',$name)){
		$db->query($hciQ);
	}

	$sdQ = "CREATE TABLE superdepts (
		superID int,
		dept_ID int
		)";
	if (!$db->table_exists('superdepts',$name)){
		$db->query($sdQ);
	}

	$sdnQ = "CREATE TABLE superDeptNames (
		superID int,
		super_name varchar(100),
		primary key (superID)
		)";
	if (!$db->table_exists('superDeptNames',$name)){
		$db->query($sdnQ);
	}

	$seQ = "CREATE TABLE superDeptEmails (
		superID int,
		email_address varchar(100)	
		)";
	if (!$db->table_exists('superDeptEmails',$name)){
		$db->query($seQ);
	}

	$puQ = "CREATE TABLE productUser (
		upc varchar(13),
		description varchar(255),
		brand varchar(255),
		sizing varchar(255),
		photo varchar(255),
		long_text text,
		primary key (upc)
		)";
	if (!$db->table_exists('productUser',$name)){
		$db->query($puQ);
	}	

	$blQ = "CREATE TABLE IPBlacklist (
		ip int,
		primary key(ip)
		)";
	if (!$db->table_exists('IPBlacklist',$name)){
		$db->query($blQ);
	}
	
	$bl2Q = "CREATE TABLE emailBlacklist (
		addr varchar(255),
		primary key(addr)
		)";
	if (!$db->table_exists('emailBlacklist',$name)){
		$db->query($bl2Q);
	}

	$regQ = "CREATE TABLE registrations (
		tdate datetime,
		card_no int,
		name varchar(150),
		email varchar(150),
		phone varchar(30),
		guest_count int,
		child_count int,
		paid int
		)";
	if (!$db->table_exists("registrations",$name)){
		$db->query($regQ);
	}

	$mealQ = "CREATE TABLE regMeals (
		card_no int,
		type varchar(5),
		subtype smallint
		)";
	if (!$db->table_exists("regMeals",$name)){
		$db->query($mealQ);
	}

	$tkQ = "CREATE TABLE tokenCache (
		card_no int,
		token varchar(25),
		tdate datetime
		)";
	if (!$db->table_exists("tokenCache",$name)){
		$db->query($tkQ);
	}

	$expQ = "CREATE TABLE productExpires (
		upc bigint(13) unsigned zerofill NOT NULL,
		expires datetime,
		PRIMARY KEY (upc)
		)";
	if (!$db->table_exists("productExpires",$name)){
		$db->query($expQ);
	}

	$limitQ = "CREATE TABLE productOrderLimits (
		upc bigint(13) unsigned zerofill NOT NULL,
		available int,
		PRIMARY KEY (upc)
		)";
	if (!$db->table_exists("productOrderLimits",$name)){
		$db->query($limitQ);
	}
	
}

function create_trans_dbs($db,$type){
	global $IS4C_LOCAL;
	$name = $IS4C_LOCAL->get('tDatabase');

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
		$db->query($dtransQ,$name);
	}

	$ltQ = str_replace("dtransactions","localtrans",$dtransQ);
	if (!$db->table_exists('localtrans',$name)){
		$db->query($ltQ,$name);
	}

	$lttQ = str_replace("dtransactions","localtrans_today",$dtransQ);
	if (!$db->table_exists('localtrans_today',$name)){
		$db->query($lttQ,$name);
	}

	$lttV = "CREATE VIEW localtranstoday AS SELECT * FROM localtrans_today
		WHERE ".$db->datediff($db->now(),"datetime")." = 0";
	if (!$db->table_exists('localtranstoday',$name)){
		$db->query($lttV,$name);
	}

	$ltaQ = str_replace("dtransactions","localtransarchive",$dtransQ);
	if (!$db->table_exists('localtransarchive',$name)){
		$db->query($ltaQ,$name);
	}

	$ltaQ = str_replace("dtransactions","pendingtrans",$dtransQ);
	if (!$db->table_exists('pendingtrans',$name)){
		$db->query($ltaQ,$name);
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

	/* don't make this table yet */
	if (!$db->table_exists('localtemptrans',$name)){
		$db->query($lttQ,$name);
	}

	$caQ = "CREATE TABLE couponApplied (
			emp_no		int,
			trans_no	int,
			quantity	float,
			trans_id	int)";
	if(!$db->table_exists('couponApplied',$name)){
		$db->query($caQ,$name);
	}

	$taxQ = "CREATE TABLE taxrates (
		id		int,
		rate		float,
		`description`	varchar(50))";
	if ($type == 'mssql')
		$taxQ = str_replace("`","",$taxQ);
	if(!$db->table_exists('taxrates',$name)){
		$db->query($taxQ,$name);
	}

	$taxTtlQ = "CREATE VIEW taxTTL AS 
		SELECT l.emp_no,
			SUM(
			CASE WHEN t.id IS NULL THEN 0 ELSE l.total*t.rate END
			) as taxes	
		FROM localtemptrans AS l LEFT JOIN
		taxrates AS t ON l.tax=t.id
		GROUP BY l.emp_no";
	if (!$db->table_exists("taxTTL",$name)){
		$db->query($taxTtlQ,$name);	
	}

	/* lttsummary, lttsubtotals, and subtotals
	 * always get rebuilt to account for tax rate
	 * changes */
	include('buildLTTViews.php');
	//buildLTTViews($db,$type);

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
	/* might use for CC processing
	if(!$db->table_exists('efsnetRequest',$name)){
		$db->query($efsrq,$name);
	}
	*/

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
	/*
	if(!$db->table_exists('efsnetResponse',$name)){
		$db->query($efsrp,$name);
	}
	*/

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
	/*
	if(!$db->table_exists('efsnetRequestMod',$name)){
		$db->query($efsrqm,$name);
	}
	*/

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
	/* might use for gift cards
	if(!$db->table_exists('valutecRequest',$name)){
		$db->query($vrq,$name);
	}
	*/

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
	/*
	if(!$db->table_exists('valutecResponse',$name)){
		$db->query($vrp,$name);
	}
	*/

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
	/*
	if(!$db->table_exists('valutecRequestMod',$name)){
		$db->query($vrqm,$name);
	}
	*/

	$cart = "CREATE VIEW cart AS
		SELECT l.upc,l.emp_no,
		u.brand,u.description,
		l.scale,l.quantity,
		l.unitPrice,l.total,
		CASE WHEN l.discounttype=1 THEN 'On Sale'
		WHEN l.discounttype=2 AND l.memType=1 THEN 'Owner Special'
		ELSE '' END as saleMsg
		FROM localtemptrans AS l INNER JOIN "
		.$IS4C_LOCAL->get("pDatabase").".productUser AS u
		ON l.upc=u.upc";
	if (!$db->table_exists('cart',$name)){
		$db->query($cart,$name);
	}
}

?>
