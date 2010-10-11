<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
ini_set('display_errors','1');
date_default_timezone_set('America/Chicago');
?>
<?php include('config.php'); ?>
Necessities
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="sample_data/extra_data.php">Sample Data</a>
<form action=install.php method=post>
<h1>Fannie install checks</h1>
<?php
$FILEPATH = rtrim($_SERVER['SCRIPT_FILENAME'],'install.php');
$URL = rtrim($_SERVER['SCRIPT_NAME'],'install.php');

if (function_exists('posix_getpwuid')){
	$chk = posix_getpwuid(posix_getuid());
	echo "PHP is running as: ".$chk['name']."<br />";
}
else
	echo "PHP is (probably) running as: ".get_current_user()."<br />";

if (is_writable($FILEPATH.'config.php')){
	confset('FANNIE_ROOT',"'$FILEPATH'");
	confset('FANNIE_URL',"'$URL'");
	echo "<span style=\"color:green;\"><i>config.php</i> is writeable</span>";
}
else {
	echo "<span style=\"color:red;\"><b>Error</b>: config.php is not writeable</span>";
}
?>
<br />
Authentication enabled
<select name=FANNIE_AUTH_ENABLED>
<?php
if (!isset($FANNIE_AUTH_ENABLED)) $FANNIE_AUTH_ENABLED = False;
if (isset($_REQUEST['FANNIE_AUTH_ENABLED'])) $FANNIE_AUTH_ENABLED = $_REQUEST['FANNIE_AUTH_ENABLED'];
if ($FANNIE_AUTH_ENABLED === True || $FANNIE_AUTH_ENABLED == 'Yes'){
	include('auth/utilities.php');
	table_check();
	confset('FANNIE_AUTH_ENABLED','True');
	echo "<option selected>Yes</option><option>No</option>";
}
else{
	confset('FANNIE_AUTH_ENABLED','False');
	echo "<option>Yes</option><option selected>No</option>";
}
?>
</select>
<br />
Names per membership
<?php
if (!isset($FANNIE_NAMES_PER_MEM)) $FANNIE_NAMES_PER_MEM = 1;
if (isset($_REQUEST['FANNIE_NAMES_PER_MEM'])) $FANNIE_NAMES_PER_MEM = $_REQUEST['FANNIE_NAMES_PER_MEM'];
confset('FANNIE_NAMES_PER_MEM',$FANNIE_NAMES_PER_MEM);
echo "<input type=text size=3 name=FANNIE_NAMES_PER_MEM value=\"$FANNIE_NAMES_PER_MEM\" />";
?>
<br />
Default Shelf Tag Layout
<select name=FANNIE_DEFAULT_PDF>
<?php
if (!isset($FANNIE_DEFAULT_PDF)) $FANNIE_DEFAULT_PDF = 'Fannie Standard';
if (isset($_REQUEST['FANNIE_DEFAULT_PDF'])) $FANNIE_DEFAULT_PDF = $_REQUEST['FANNIE_DEFAULT_PDF'];
if (file_exists($FANNIE_ROOT.'admin/labels/scan_layouts.php')){
	include($FANNIE_ROOT.'admin/labels/scan_layouts.php');
	foreach(scan_layouts() as $l){
		if ($l == $FANNIE_DEFAULT_PDF)
			echo "<option selected>$l</option>";
		else
			echo "<option>$l</option>";
	}
}
else {
	echo "<option>No layouts found!</option>";
}
confset('FANNIE_DEFAULT_PDF',"'$FANNIE_DEFAULT_PDF'");
?>
</select>
<hr />
<b>Main Server</b><br />
Server Database Host
<?php
if(!isset($FANNIE_SERVER)) $FANNIE_SERVER = '127.0.0.1';
if (isset($_REQUEST['FANNIE_SERVER'])){
	$FANNIE_SERVER = $_REQUEST['FANNIE_SERVER'];
}
confset('FANNIE_SERVER',"'$FANNIE_SERVER'");
echo "<input type=text name=FANNIE_SERVER value=\"$FANNIE_SERVER\" />";
?>
<br />Server Database Type
<select name=FANNIE_SERVER_DBMS>
<?php
if(!isset($FANNIE_SERVER_DBMS)) $FANNIE_SERVER_DBMS = 'MYSQL';
if (isset($_REQUEST['FANNIE_SERVER_DBMS'])){
	$FANNIE_SERVER_DBMS = $_REQUEST['FANNIE_SERVER_DBMS'];
}
confset('FANNIE_SERVER_DBMS',"'$FANNIE_SERVER_DBMS'");
if ($FANNIE_SERVER_DBMS == 'MYSQL'){
	echo "<option value=MYSQL selected>MySQL</option>";
	echo "<option value=MSSQL>SQL Server</option>";
}
else {
	echo "<option value=MYSQL>MySQL</option>";
	echo "<option value=MSSQL selected>SQL Server</option>";
}
?>
</select>
<br />Server Database Username
<?php
if (!isset($FANNIE_SERVER_USER)) $FANNIE_SERVER_USER = 'root';
if (isset($_REQUEST['FANNIE_SERVER_USER']))
	$FANNIE_SERVER_USER = $_REQUEST['FANNIE_SERVER_USER'];
confset('FANNIE_SERVER_USER',"'$FANNIE_SERVER_USER'");
echo "<input type=text name=FANNIE_SERVER_USER value=\"$FANNIE_SERVER_USER\" />";
?>
<br />Server Database Password
<?php
if (!isset($FANNIE_SERVER_PW)) $FANNIE_SERVER_PW = '';
if (isset($_REQUEST['FANNIE_SERVER_PW']))
	$FANNIE_SERVER_PW = $_REQUEST['FANNIE_SERVER_PW'];
confset('FANNIE_SERVER_PW',"'$FANNIE_SERVER_PW'");
echo "<input type=password name=FANNIE_SERVER_PW value=\"$FANNIE_SERVER_PW\" />";
?>
<br />Server Operational DB name
<?php
if (!isset($FANNIE_OP_DB)) $FANNIE_OP_DB = 'is4c_op';
if (isset($_REQUEST['FANNIE_OP_DB']))
	$FANNIE_OP_DB = $_REQUEST['FANNIE_OP_DB'];
confset('FANNIE_OP_DB',"'$FANNIE_OP_DB'");
echo "<input type=text name=FANNIE_OP_DB value=\"$FANNIE_OP_DB\" />";
?>
<br />Server Transaction DB name
<?php
if (!isset($FANNIE_TRANS_DB)) $FANNIE_TRANS_DB = 'is4c_trans';
if (isset($_REQUEST['FANNIE_TRANS_DB']))
	$FANNIE_TRANS_DB = $_REQUEST['FANNIE_TRANS_DB'];
confset('FANNIE_TRANS_DB',"'$FANNIE_TRANS_DB'");
echo "<input type=text name=FANNIE_TRANS_DB value=\"$FANNIE_TRANS_DB\" />";
?>
<br />Testing Operational DB connection:
<?php
if (!class_exists('SQLManager'))
	include('src/SQLManager.php');
$sql = False;
try {
	$sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,
			$FANNIE_OP_DB,$FANNIE_SERVER_USER,
			$FANNIE_SERVER_PW);
}
catch(Exception $ex) {}
if ($sql && $sql->connections[$FANNIE_OP_DB] == False)
	echo "<span style=\"color:red;\">Failed</span>";
else {
	echo "<span style=\"color:green;\">Succeeded</span>";
	create_op_dbs($sql);
}
?>
<br />Testing Transaction DB connection:
<?php
$sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,
		$FANNIE_TRANS_DB,$FANNIE_SERVER_USER,
		$FANNIE_SERVER_PW);
if ($sql->connections[$FANNIE_TRANS_DB] == False)
	echo "<span style=\"color:red;\">Failed</span>";
else {
	echo "<span style=\"color:green;\">Succeeded</span>";
	create_trans_dbs($sql);
	create_dlogs($sql);
}
?>
<hr />
<b>Transaction archiving</b><br />
Archive DB name:
<?php
if (!isset($FANNIE_ARCHIVE_DB)) $FANNIE_ARCHIVE_DB = 'trans_archive';
if (isset($_REQUEST['FANNIE_ARCHIVE_DB'])) $FANNIE_ARCHIVE_DB = $_REQUEST['FANNIE_ARCHIVE_DB'];
confset('FANNIE_ARCHIVE_DB',"'$FANNIE_ARCHIVE_DB'");
echo "<input type=text name=FANNIE_ARCHIVE_DB value=\"$FANNIE_ARCHIVE_DB\" />";
?>
<br />Use a different DB server for archives
<select name=FANNIE_ARCHIVE_REMOTE>
<?php
if (!isset($FANNIE_ARCHIVE_REMOTE)) $FANNIE_ARCHIVE_REMOTE = 'No';
if (isset($_REQUEST['FANNIE_ARCHIVE_REMOTE'])) $FANNIE_ARCHIVE_REMOTE = $_REQUEST['FANNIE_ARCHIVE_REMOTE'];
if ($FANNIE_ARCHIVE_REMOTE == 'Yes' || $FANNIE_ARCHIVE_REMOTE === True){
	confset('FANNIE_ARCHIVE_REMOTE','True');
	echo "<option selected>Yes</option><option>No</option>";
}
else{
	confset('FANNIE_ARCHIVE_REMOTE','False');
	echo "<option>Yes</option><option selected>No</option>";
}
echo "</select><br />";
if ($FANNIE_ARCHIVE_REMOTE === True || $FANNIE_ARCHIVE_REMOTE == 'Yes'){
if ($FANNIE_ARCHIVE_DB == $FANNIE_TRANS_DB){
	echo "<blockquote><i>Warning: using the same name for the archive database
	and the main transaction database will probably cause problems</blockquote>";
}
?>
<br />Archive DB Server
<?php
if (!isset($FANNIE_ARCHIVE_SERVER)) $FANNIE_ARCHIVE_SERVER = '127.0.0.1';
if (isset($_REQUEST['FANNIE_ARCHIVE_SERVER'])) $FANNIE_ARCHIVE_SERVER = $_REQUEST['FANNIE_ARCHIVE_SERVER'];
confset('FANNIE_ARCHIVE_SERVER',"'$FANNIE_ARCHIVE_SERVER'");
echo "<input type=text name=FANNIE_ARCHIVE_SERVER value=\"$FANNIE_ARCHIVE_SERVER\" />";
?>
<br />Archive DB type
<select name=FANNIE_ARCHIVE_DBMS>
<?php
if (!isset($FANNIE_ARCHIVE_DBMS)) $FANNIE_ARCHIVE_DBMS = 'MYSQL';
if (isset($_REQUEST['FANNIE_ARCHIVE_DBMS'])) $FANNIE_ARCHIVE_DBMS = $_REQUEST['FANNIE_ARCHIVE_DBMS'];
confset('FANNIE_ARCHIVE_DBMS',"'$FANNIE_ARCHIVE_DBMS'");
if ($FANNIE_ARCHIVE_DBMS == 'MYSQL')
	echo "<option value=MYSQL selected>MySQL</option><option value=MSSQL>SQL Server</option>";
else
	echo "<option value=MYSQL>MySQL</option><option value=MSSQL selected>SQL Server</option>";
?>
</select>
<br />Archive DB username
<?php
if (!isset($FANNIE_ARCHIVE_USER)) $FANNIE_ARCHIVE_USER = 'root';
if (isset($_REQUEST['FANNIE_ARCHIVE_USER'])) $FANNIE_ARCHIVE_USER = $_REQUEST['FANNIE_ARCHIVE_USER'];
confset('FANNIE_ARCHIVE_USER',"'$FANNIE_ARCHIVE_USER'");
echo "<input type=text name=FANNIE_ARCHIVE_USER value=\"$FANNIE_ARCHIVE_USER\" />";
?>
<br />Archive DB password
<?php
if (!isset($FANNIE_ARCHIVE_PW)) $FANNIE_ARCHIVE_PW = '';
if (isset($_REQUEST['FANNIE_ARCHIVE_PW'])) $FANNIE_ARCHIVE_PW = $_REQUEST['FANNIE_ARCHIVE_PW'];
confset('FANNIE_ARCHIVE_PW',"'$FANNIE_ARCHIVE_PW'");
echo "<input type=password name=FANNIE_ARCHIVE_PW value=\"$FANNIE_ARCHIVE_PW\" />";
}
else {
	//local archiving - set up now
	echo "<br />Testing Transaction DB connection:";
	$sql = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,
			$FANNIE_ARCHIVE_DB,$FANNIE_SERVER_USER,
			$FANNIE_SERVER_PW);
	if ($sql->connections[$FANNIE_ARCHIVE_DB] == False)
		echo "<span style=\"color:red;\">Failed</span>";
	else {
		echo "<span style=\"color:green;\">Succeeded</span>";
		create_archive_dbs($sql);
	}
}
?>
<hr />
<b>Lanes</b>: 
<?php
if (!isset($FANNIE_NUM_LANES)) $FANNIE_NUM_LANES = 0;
if (isset($_REQUEST['FANNIE_NUM_LANES'])) $FANNIE_NUM_LANES = $_REQUEST['FANNIE_NUM_LANES'];
confset('FANNIE_NUM_LANES',"$FANNIE_NUM_LANES");
echo "<input type=text name=FANNIE_NUM_LANES value=\"$FANNIE_NUM_LANES\" size=3 />";
?>
<br />
<?php
if ($FANNIE_NUM_LANES == 0) confset('FANNIE_LANES','array()');
else {
?>
<script type=text/javascript>
function showhide(i,num){
	for (var j=0; j<num; j++){
		if (j == i)
			document.getElementById('lanedef'+j).style.display='block';
		else
			document.getElementById('lanedef'+j).style.display='none';
	}
}
</script>
<?php
echo "<select onchange=\"showhide(this.value,$FANNIE_NUM_LANES);\">";
for($i=0; $i<$FANNIE_NUM_LANES;$i++){
	echo "<option value=$i>Lane ".($i+1)."</option>";
}
echo "</select><br />";

$conf = 'array(';
for($i=0; $i<$FANNIE_NUM_LANES; $i++){
	$style = ($i == 0)?'block':'none';
	echo "<div id=\"lanedef$i\" style=\"display:$style;\">";
	if (!isset($FANNIE_LANES[$i])) $FANNIE_LANES[$i] = array();
	$conf .= 'array(';

	if (!isset($FANNIE_LANES[$i]['host'])) $FANNIE_LANES[$i]['host'] = '127.0.0.1';
	if (isset($_REQUEST["LANE_HOST_$i"])){ $FANNIE_LANES[$i]['host'] = $_REQUEST["LANE_HOST_$i"]; }
	$conf .= "'host'=>'{$FANNIE_LANES[$i]['host']}',";
	echo "Lane ".($i+1)." Database Host: <input type=text name=LANE_HOST_$i value=\"{$FANNIE_LANES[$i]['host']}\" /><br />";
	
	if (!isset($FANNIE_LANES[$i]['type'])) $FANNIE_LANES[$i]['type'] = 'MYSQL';
	if (isset($_REQUEST["LANE_TYPE_$i"])) $FANNIE_LANES[$i]['type'] = $_REQUEST["LANE_TYPE_$i"];
	$conf .= "'type'=>'{$FANNIE_LANES[$i]['type']}',";
	echo "Lane ".($i+1)." Database Type: <select name=LANE_TYPE_$i>";
	if ($FANNIE_LANES[$i]['type'] == 'MYSQL'){
		echo "<option value=MYSQL selected>MySQL</option><option value=MSSQL>SQL Server</option>";
	}
	else {
		echo "<option value=MYSQL>MySQL</option><option selected value=MSSQL>SQL Server</option>";
	}
	echo "</select><br />";

	if (!isset($FANNIE_LANES[$i]['user'])) $FANNIE_LANES[$i]['user'] = 'root';
	if (isset($_REQUEST["LANE_USER_$i"])) $FANNIE_LANES[$i]['user'] = $_REQUEST["LANE_USER_$i"];
	$conf .= "'user'=>'{$FANNIE_LANES[$i]['user']}',";
	echo "Lane ".($i+1)." Database Username: <input type=text name=LANE_USER_$i value=\"{$FANNIE_LANES[$i]['user']}\" /><br />";

	if (!isset($FANNIE_LANES[$i]['pw'])) $FANNIE_LANES[$i]['pw'] = '';
	if (isset($_REQUEST["LANE_PW_$i"])) $FANNIE_LANES[$i]['pw'] = $_REQUEST["LANE_PW_$i"];
	$conf .= "'pw'=>'{$FANNIE_LANES[$i]['pw']}',";
	echo "Lane ".($i+1)." Database Password: <input type=password name=LANE_PW_$i value=\"{$FANNIE_LANES[$i]['pw']}\" /><br />";

	if (!isset($FANNIE_LANES[$i]['op'])) $FANNIE_LANES[$i]['op'] = 'opdata';
	if (isset($_REQUEST["LANE_OP_$i"])) $FANNIE_LANES[$i]['op'] = $_REQUEST["LANE_OP_$i"];
	$conf .= "'op'=>'{$FANNIE_LANES[$i]['op']}',";
	echo "Lane ".($i+1)." Operational DB: <input type=text name=LANE_OP_$i value=\"{$FANNIE_LANES[$i]['op']}\" /><br />";

	if (!isset($FANNIE_LANES[$i]['trans'])) $FANNIE_LANES[$i]['trans'] = 'translog';
	if (isset($_REQUEST["LANE_TRANS_$i"])) $FANNIE_LANES[$i]['trans'] = $_REQUEST["LANE_TRANS_$i"];
	$conf .= "'trans'=>'{$FANNIE_LANES[$i]['trans']}'";
	echo "Lane ".($i+1)." Transaction DB: <input type=text name=LANE_TRANS_$i value=\"{$FANNIE_LANES[$i]['trans']}\" /><br />";

	$conf .= ")";
	echo "</div>";	

	if ($i == $FANNIE_NUM_LANES - 1)
		$conf .= ")";
	else
		$conf .= ",";
}
confset('FANNIE_LANES',$conf);

}
?>
<hr />
<b>Scales</b>:
<?php
if (!isset($FANNIE_NUM_SCALES)) $FANNIE_NUM_SCALES = 0;
if (isset($_REQUEST['FANNIE_NUM_SCALES'])) $FANNIE_NUM_SCALES = $_REQUEST['FANNIE_NUM_SCALES'];
confset('FANNIE_NUM_SCALES',"$FANNIE_NUM_SCALES");
echo "<input type=text name=FANNIE_NUM_SCALES value=\"$FANNIE_NUM_SCALES\" size=3 />";
echo "<br />";
if (is_writable($FANNIE_ROOT.'item/hobartcsv/csvfiles'))
	echo "<span style=\"color:green;\">item/hobartcsv/csvfiles is writeable</span>";
else 
	echo "<span style=\"color:red;\">item/hobartcsv/csvfiles is not writeable</span>";
echo "<br />";
if (is_writable($FANNIE_ROOT.'item/hobartcsv/csv_output'))
	echo "<span style=\"color:green;\">item/hobartcsv/csv_output is writeable</span>";
else 
	echo "<span style=\"color:red;\">item/hobartcsv/csv_output is not writeable</span>";
?>
<br />
<?php
if($FANNIE_NUM_SCALES == 0) confset('FANNIE_SCALES','array()');
else {
?>
<script type=text/javascript>
function showhidescale(i,num){
	for (var j=0; j<num; j++){
		if (j == i)
			document.getElementById('scaledef'+j).style.display='block';
		else
			document.getElementById('scaledef'+j).style.display='none';
	}
}
</script>
<?php
echo "<select onchange=\"showhidescale(this.value,$FANNIE_NUM_SCALES);\">";
for($i=0; $i<$FANNIE_NUM_SCALES;$i++)
	echo "<option value=$i>Scale ".($i+1)."</option>";
echo "</select><br />";

$conf = 'array(';
for($i=0; $i<$FANNIE_NUM_SCALES; $i++){
	$style = ($i == 0)?'block':'none';
	echo "<div id=\"scaledef$i\" style=\"display:$style;\">";
	if (!isset($FANNIE_SCALES[$i])) $FANNIE_SCALES[$i] = array();
	$conf .= 'array(';

	if (!isset($FANNIE_SCALES[$i]['host'])) $FANNIE_SCALES[$i]['host'] = '';
	if (isset($_REQUEST["SCALE_HOST_$i"])){ $FANNIE_SCALES[$i]['host'] = $_REQUEST["SCALE_HOST_$i"]; }
	$conf .= "'host'=>'{$FANNIE_SCALES[$i]['host']}',";
	echo "Scale ".($i+1)." IP: <input type=text name=SCALE_HOST_$i value=\"{$FANNIE_SCALES[$i]['host']}\" /><br />";

	if (!isset($FANNIE_SCALES[$i]['type'])) $FANNIE_SCALES[$i]['type'] = 'QUANTUMTCP';
	if (isset($_REQUEST["SCALE_TYPE_$i"])){ $FANNIE_SCALES[$i]['type'] = $_REQUEST["SCALE_TYPE_$i"]; }
	$conf .= "'type'=>'{$FANNIE_SCALES[$i]['type']}',";
	echo "Scale ".($i+1)." Type: <input type=text name=SCALE_TYPE_$i value=\"{$FANNIE_SCALES[$i]['type']}\" /><br />";

	if (!isset($FANNIE_SCALES[$i]['dept'])) $FANNIE_SCALES[$i]['dept'] = '';
	if (isset($_REQUEST["SCALE_DEPT_$i"])){ $FANNIE_SCALES[$i]['dept'] = $_REQUEST["SCALE_DEPT_$i"]; }
	$conf .= "'dept'=>'{$FANNIE_SCALES[$i]['dept']}'";
	echo "Scale ".($i+1)." Department: <input type=text name=SCALE_DEPT_$i value=\"{$FANNIE_SCALES[$i]['dept']}\" /><br />";

	$conf .= ")";
	echo "</div>";	

	if ($i == $FANNIE_NUM_SCALES - 1)
		$conf .= ")";
	else
		$conf .= ",";
}
confset('FANNIE_SCALES',$conf);
}
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
<input type=submit value="Re-run" />
</form>

<?php
// set a variable in the config file
function confset($key, $value){
	global $FILEPATH;
	$lines = array();
	$found = False;
	$fp = fopen($FILEPATH.'config.php','r');
	while($line = fgets($fp)){
		if (strpos($line,"\$$key ") === 0){
			$lines[] = "\$$key = $value;\n";
			$found = True;
		}
		elseif (strpos($line,"?>") === 0 && $found == False){
			$lines[] = "\$$key = $value;\n";
			$lines[] = "?>\n";
		}
		else
			$lines[] = $line;
	}
	fclose($fp);

	$fp = fopen($FILEPATH.'config.php','w');
	foreach($lines as $line)
		fwrite($fp,$line);
	fclose($fp);
}

function create_op_dbs($con){
	global $FANNIE_OP_DB, $FANNIE_SERVER_DBMS;

	$prodQ = "CREATE TABLE `products` (
		  `upc` bigint(13) unsigned zerofill default NULL,
		  `description` varchar(30) default NULL,
		  `normal_price` double default NULL,
		  `pricemethod` smallint(6) default NULL,
		  `groupprice` double default NULL,
		  `quantity` smallint(6) default NULL,
		  `special_price` double default NULL,
		  `specialpricemethod` smallint(6) default NULL,
		  `specialgroupprice` double default NULL,
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
		  `tareweight` double default NULL,
		  `discount` smallint(6) default NULL,
		  `discounttype` tinyint(4) default NULL,
		  `unitofmeasure` varchar(15) default NULL,
		  `wicable` smallint(6) default NULL,
		  `qttyEnforced` tinyint(4) default NULL,
		  `idEnforced` tinyint(4) default NULL,
		  `cost` double default 0 NULL,
		  `inUse` tinyint(4) default NULL,
		  `numflag` int(11) default '0',
		  `subdept` smallint(4) default NULL,
		  `deposit` double default NULL,
		  `local` int(11) default '0',
		  `id` int(11) NOT NULL auto_increment,
		  PRIMARY KEY  (`id`),
		  KEY `upc` (`upc`),
		  KEY `description` (`description`),
		  KEY `normal_price` (`normal_price`),
		  KEY `subdept` (`subdept`),
		  KEY `department` (`department`)
		)";
	if ($FANNIE_SERVER_DBMS == "MSSQL"){
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
			[Scale] [bit] NOT NULL ,
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
			[subdept] [smallint] NULL ,
			[deposit] [money] NULL ,
			[local] [int] NULL ,
			[id] [int] IDENTITY (1, 1) NOT NULL ,
			PRIMARY KEY ([id]) )";
	}
	if (!$con->table_exists("products",$FANNIE_OP_DB)){
		$con->query($prodQ,$FANNIE_OP_DB);
	}

	$deptQ = "CREATE TABLE `departments` (
		  `dept_no` smallint(6) default NULL,
		  `dept_name` varchar(30) default NULL,
		  `dept_tax` tinyint(4) default NULL,
		  `dept_fs` tinyint(4) default NULL,  
		  `dept_limit` double default NULL,
		  `dept_minimum` double default NULL,
		  `dept_discount` tinyint(4) default NULL,
		  `modified` datetime default NULL,
		  `modifiedby` int(11) default NULL,
		  KEY `dept_no` (`dept_no`),
		  KEY `dept_name` (`dept_name`)
		)";
	if ($FANNIE_SERVER_DBMS == "MSSQL"){
		$deptQ = "CREATE TABLE [departments] (
			[dept_no] [smallint] NULL ,
			[dept_name] [nvarchar] (30) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
			[dept_tax] [tinyint] NULL ,
			[dept_fs] [bit] NOT NULL ,
			[dept_limit] [money] NULL ,
			[dept_minimum] [money] NULL ,
			[dept_discount] [smallint] NULL ,
			[modified] [smalldatetime] NULL ,
			[modifiedby] [int] NULL 
			)";
	}
	if (!$con->table_exists('departments',$FANNIE_OP_DB)){
		$con->query($deptQ,$FANNIE_OP_DB);
	}

	$subdeptQ = "CREATE TABLE `subdepts` (
		  `subdept_no` smallint(4) NOT NULL, 
		  `subdept_name` varchar(30) default NULL,
		  `dept_ID` smallint(4) default NULL,
		  KEY `subdept_no` (`subdept_no`),
		  KEY `subdept_name` (`subdept_name`)
		) ";
	if ($FANNIE_SERVER_DBMS == "MSSQL"){
		$subdeptQ = "CREATE TABLE subdepts (
		subdept_no smallint,
		subdept_name varchar(30),
		dept_ID smallint
		)";
	}
	if (!$con->table_exists('subdepts',$FANNIE_OP_DB)){
		$con->query($subdeptQ,$FANNIE_OP_DB);
	}

	$lcQ = "CREATE TABLE `likeCodes` (
		`likeCode` int NOT NULL,
		`likeCodeDesc` varchar(50) default NULL,
		PRIMARY KEY (`likeCode`)
		)";
	if ($FANNIE_SERVER_DBMS == "MSSQL"){
		$lcQ = "CREATE TABLE likeCodes (
			likeCode int,
			likeCodeDesc varchar(50),
			PRIMARY KEY (likeCode)
			)";
	}
	if (!$con->table_exists('likeCodes',$FANNIE_OP_DB)){
		$con->query($lcQ,$FANNIE_OP_DB);
	}

	$upcLikeQ = "CREATE TABLE `upcLike` (
			`upc` bigint(13) unsigned zerofill NOT NULL default '0000000000000',
			`likeCode` int default NULL
			)"; 
	if ($FANNIE_SERVER_DBMS == "MSSQL"){
		$upcLikeQ = "CREATE TABLE upcLike (
			upc varchar(13),
			likeCode int
			)";
	}
	if (!$con->table_exists('upcLike',$FANNIE_OP_DB)){
		$con->query($upcLikeQ,$FANNIE_OP_DB);
	}

	$taxQ = "CREATE TABLE `taxrates` (
		`id` int NOT NULL,
		`rate` float default NULL,
		`description` varchar(30) default NULL,
		PRIMARY KEY (`id`)
		) ";
	if ($FANNIE_SERVER_DBMS == "MSSQL"){
		$taxQ = "CREATE TABLE taxrates (
			id int,
			rate float,
			description varchar(30),
			PRIMARY KEY (id)
			)";
	}
	if (!$con->table_exists('taxrates',$FANNIE_OP_DB)){
		$con->query($taxQ,$FANNIE_OP_DB);
	}

	$xtraQ = "CREATE TABLE `prodExtra` (
		`upc` bigint(13) unsigned zerofill NOT NULL default '0000000000000',
		`distributor` varchar(100) default NULL,
		`manufacturer` varchar(100) default NULL,
		`cost` numeric(10,2) default NULL,
		`margin` numeric(10,2) default NULL,
		`variable_pricing` tinyint default NULL,
		`location` varchar(30) default NULL,
		`case_quantity` varchar(15) default NULL,
		`case_cost` numeric(10,2) default NULL,
		`case_info` varchar(100) default NULL,
		PRIMARY KEY (`upc`)
		)"; 
	if ($FANNIE_SERVER_DBMS == "MSSQL"){
		$xtraQ = "CREATE TABLE prodExtra (
			upc varchar(13),
			distributor varchar(100),
			manufacturer varchar(100),
			cost money,
			margin money,
			variable_pricing tinyint,
			location varchar(30),
			case_quantity varchar(15),
			case_cost money,
			case_info varchar(100),
			PRIMARY KEY (upc)
		)";
	}
	if (!$con->table_exists('prodExtra',$FANNIE_OP_DB)){
		$con->query($xtraQ,$FANNIE_OP_DB);
	}

	$prodUpQ = "CREATE TABLE `prodUpdate` (
	  `upc` varchar(13) default NULL,
	  `description` varchar(50) default NULL,
	  `price` decimal(10,2) default NULL,
	  `dept` int(6) default NULL,
	  `tax` bit(1) default NULL,
	  `fs` bit(1) default NULL,
	  `scale` bit(1) default NULL,
	  `likeCode` int(6) default NULL,
	  `modified` date default NULL,
	  `user` int(8) default NULL,
	  `forceQty` bit(1) default NULL,
	  `noDisc` bit(1) default NULL,
	  `inUse` bit(1) default NULL
	)";
	if ($FANNIE_SERVER_DBMS == "MSSQL"){
		$prodUpQ = "CREATE TABLE prodUpdate (
			upc varchar(13),
			description varchar(50),
			price money,
			dept int,
			tax smallint,
			fs smallint,
			scale smallint,
			likeCode int,
			modified datetime,
			[user] int,
			forceQty smallint,
			noDisc smallint,
			inUse smallint
			)";
	}
	if (!$con->table_exists('prodUpdate',$FANNIE_OP_DB)){
		$con->query($prodUpQ,$FANNIE_OP_DB);
	}

	if (!$con->table_exists('prodUpdateArchive',$FANNIE_OP_DB)){
		$prodUpQ = str_replace('prodUpdate','prodUpdateArchive',$prodUpQ);
		$con->query($prodUpQ,$FANNIE_OP_DB);
	}

	$priceHistoryQ = "CREATE TABLE ProdPriceHistory (
		upc varchar(13),
		modified datetime,
		price decimal(10,2),
		uid int
	)";
	if (!$con->table_exists('ProdPriceHistory',$FANNIE_OP_DB)){
		$con->query($priceHistoryQ,$FANNIE_OP_DB);
	}

	$deptHistoryQ = "CREATE TABLE ProdDepartmentHistory (
		upc varchar(13),
		modified datetime,
		dept_ID int,
		uid int
	)";
	if (!$con->table_exists('ProdDepartmentHistory',$FANNIE_OP_DB)){
		$con->query($deptHistoryQ,$FANNIE_OP_DB);
	}

	$batchQ = "CREATE TABLE `batches` (
	  `batchID` int(5) NOT NULL auto_increment,
	  `startDate` datetime default NULL,
	  `endDate` datetime default NULL,
	  `batchName` varchar(80) default NULL,
	  `batchType` int(3) default NULL,
	  `discountType` int(2) default NULL,
	  PRIMARY KEY  (`batchID`)
	)";
	if ($FANNIE_SERVER_DBMS == "MSSQL"){
		$batchQ = "CREATE TABLE [batches] (
		[batchID] [int] IDENTITY (1, 1) NOT NULL ,
		[startDate] [datetime] NULL ,
		[endDate] [datetime] NULL ,
		[batchName] [varchar] (50) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
		[batchType] [int] NULL ,
		[discountType] [int] NULL 
		) ON [PRIMARY]";
	}
	if (!$con->table_exists('batches',$FANNIE_OP_DB)){
		$con->query($batchQ,$FANNIE_OP_DB);
	}

	$batchListQ = "CREATE TABLE `batchList` (
	  `listID` int(6) NOT NULL auto_increment,
	  `upc` varchar(13) default NULL,
	  `batchID` int(5) default NULL,
	  `salePrice` decimal(10,2) default NULL,
	  `active` bit(1) default NULL,
	  `pricemethod` int(4) default NULL,
	  `quantity` int(4) default NULL, 
	  PRIMARY KEY  (`listID`)
	)";
	if ($FANNIE_SERVER_DBMS == "MSSQL"){
		$batchListQ = "CREATE TABLE [batchList] (
		[listID] [int] IDENTITY (1, 1) NOT NULL ,
		[upc] [char] (13) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
		[batchID] [int] NULL ,
		[salePrice] [money] NULL ,
		[active] [bit] NULL ,
		[pricemethod] [int] NULL ,
		[quantity] [int] NULL 
		) ON [PRIMARY]";
	}
	if (!$con->table_exists('batchList',$FANNIE_OP_DB)){
		$con->query($batchListQ,$FANNIE_OP_DB);
	}

	$unfiQ = "CREATE TABLE `UNFI` (
	  `brand` varchar(30) default NULL,
	  `sku` int(6) default NULL,
	  `size` varchar(25) default NULL,
	  `upc` bigint(13) unsigned zerofill NOT NULL default '0000000000000',
	  `units` int(3) default NULL,
	  `cost` decimal(9,2) default NULL,
	  `description` varchar(35) default NULL,
	  `depart` varchar(15) default NULL,
	  PRIMARY KEY  (`upc`),
	  KEY `newindex` (`upc`)
	)";
	if ($FANNIE_SERVER_DBMS == "MSSQL"){
		$unfiQ = "CREATE TABLE [UNFI] (
		[brand] [varchar] (30) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
		[sku] [int] NULL ,
		[size] [varchar] (25) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
		[upc] [varchar] (13) COLLATE SQL_Latin1_General_CP1_CI_AS NOT NULL ,
		[units] [smallint] NULL ,
		[cost] [money] NULL ,
		[description] [varchar] (35) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
		[depart] [varchar] (15) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
		 PRIMARY KEY ([upc]) )";
	}
	if (!$con->table_exists('UNFI',$FANNIE_OP_DB)){
		$con->query($unfiQ,$FANNIE_OP_DB);
	}

	$uoQ = "CREATE TABLE UNFI_order (
		unfi_sku varchar(12),
		brand varchar(50),
		item_desc varchar(50),
		pack varchar(10),
		pack_size varchar(20),
		upcc varchar(13),
		cat int,
		wholesale double,
		vd_cost double,
		wfc_srp double)";
	if (!$con->table_exists("UNFI_order",$FANNIE_OP_DB)){
		$con->query($uoQ,$FANNIE_OP_DB);
	}

	$uDiff = "CREATE view unfi_diff as 
		select p.upc,u.upcc, 
		p.description,
		u.item_desc,
		u.wholesale,
		u.vd_cost,
		p.normal_price,
		u.unfi_sku,
		u.wfc_srp,
		u.cat,
		p.department,
		CASE WHEN p.normal_price = 0 THEN 0 ELSE
		CONVERT((p.normal_price - (u.vd_cost/u.pack))/p.normal_price,decimal(10,2)) 
		END as our_margin,
		CONVERT((u.wfc_srp - (u.vd_cost/u.pack))/ u.wfc_srp,decimal(10,2))
		as unfi_margin,
		case when u.wfc_srp > p.normal_price then 1 else 0 END as diff
		from products as p 
		right join UNFI_order as u 
		on u.upcc=p.upc
		where 
		p.normal_price <> u.wfc_srp and
		p.upc is not NULL";
	if ($FANNIE_SERVER_DBMS == 'MSSQL'){
		$uDiff = "CREATE view unfi_diff as 
			select p.upc,u.upcc, 
			p.description,
			u.item_desc,
			u.wholesale,
			u.vd_cost,
			p.normal_price,
			u.unfi_sku,
			u.wfc_srp,
			u.cat,
			p.department,
			CASE WHEN p.normal_price = 0 THEN 0 ELSE
			CONVERT(decimal(10,5),(p.normal_price - (u.vd_cost/u.pack))/p.normal_price) 
			END as our_margin,
			CONVERT(decimal(10,5),(u.wfc_srp - (u.vd_cost/u.pack))/ u.wfc_srp)
			as UNFI_margin,
			case when u.wfc_srp > p.normal_price then 1 else 0 END as diff
			from products as p 
			right join UNFI_order as u 
			on left(u.upcc,13)=p.upc
			where 
			p.normal_price <> u.wfc_srp and
			p.upc is not NULL";
	}
	if (!$con->table_exists("unfi_diff",$FANNIE_OP_DB)){
		$con->query($uDiff,$FANNIE_OP_DB);
	}

	$uAll = "CREATE view unfi_all as 
		select p.upc,u.upcc, 
		p.description,
		u.item_desc,
		u.wholesale,
		u.vd_cost,
		p.normal_price,
		u.unfi_sku,
		u.wfc_srp,
		u.cat,
		p.department,
		CASE WHEN p.normal_price = 0 THEN 0 ELSE
		CONVERT((p.normal_price - (u.vd_cost/u.pack))/p.normal_price,decimal(10,2)) 
		END as our_margin,
		CONVERT((u.wfc_srp - (u.vd_cost/u.pack))/ u.wfc_srp,decimal(10,2))
		as unfi_margin,
		case when u.wfc_srp > p.normal_price then 1 else 0 END as diff
		from products as p 
		right join UNFI_order as u 
		on left(u.upcc,13)=p.upc
		where 
		p.upc is not NULL";
	if ($FANNIE_SERVER_DBMS == 'MSSQL'){
		$uAll = "CREATE view unfi_all as 
			select p.upc,u.upcc, 
			p.description,
			u.item_desc,
			u.wholesale,
			u.vd_cost,
			p.normal_price,
			u.unfi_sku,
			u.wfc_srp,
			u.cat,
			p.department,
			CASE WHEN p.normal_price = 0 THEN 0 ELSE
			CONVERT(decimal(10,5),(p.normal_price - (u.vd_cost/u.pack))/p.normal_price) 
			END as our_margin,
			CONVERT(decimal(10,5),(u.wfc_srp - (u.vd_cost/u.pack))/ u.wfc_srp)
			as UNFI_margin,
			case when u.wfc_srp > p.normal_price then 1 else 0 END as diff
			from products as p 
			right join UNFI_order as u 
			on left(u.upcc,13)=p.upc
			where 
			p.upc is not NULL";
	}
	if (!$con->table_exists("unfi_all",$FANNIE_OP_DB)){
		$con->query($uAll,$FANNIE_OP_DB);
	}

	$unfiCat = "CREATE TABLE unfiCategories (
		categoryID int,
		name varchar(50),
		margin double,
		testing double)";
	if (!$con->table_exists("unfiCategories",$FANNIE_OP_DB)){
		$con->query($unfiCat,$FANNIE_OP_DB);
	}
	

	$shelfQ = "CREATE TABLE `shelftags` (
		`id` int(6) default NULL ,
		`upc` bigint(13) unsigned zerofill NOT NULL default '0000000000000',
		`description` varchar(30) default NULL,
		`normal_price` decimal(9,2) default NULL,
		`brand` varchar(100) default NULL,
		`sku` varchar(12) default NULL,
		`size` varchar(50) default NULL,
		`units` int(4) default NULL ,
		`vendor` varchar(50) default NULL,
		`pricePerUnit` varchar(50) default NULL
	)";
	if ($FANNIE_SERVER_DBMS == "MSSQL"){
		$shelfQ = "CREATE TABLE [shelftags] (
			[id] [int] NULL ,
			[upc] [varchar] (13) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
			[description] [varchar] (30) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
			[normal_price] [money] NULL ,
			[brand] [varchar] (100) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
			[sku] [varchar] (12) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
			[size] [varchar] (50) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
			[units] [int] NULL ,
			[vendor] [varchar] (50) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
			[pricePerUnit] [varchar] (50) COLLATE SQL_Latin1_General_CP1_CI_AS NULL 
		)";
	}
	if (!$con->table_exists('shelftags',$FANNIE_OP_DB)){
		$con->query($shelfQ,$FANNIE_OP_DB);
	}

	$batchTypeQ = "CREATE TABLE batchType (
		batchTypeID int,
		typeDesc varchar(50),
		discType int
		)";
	if (!$con->table_exists('batchType',$FANNIE_OP_DB)){
		$con->query($batchTypeQ,$FANNIE_OP_DB);
	}

	$batchOwnerQ = "CREATE TABLE batchowner (
		batchID int,
		owner varchar(50)
		)";
	if (!$con->table_exists('batchowner',$FANNIE_OP_DB)){
		$con->query($batchOwnerQ,$FANNIE_OP_DB);
	}

	$batchCPQ = "CREATE TABLE batchCutPaste (
			batchID int,
			upc varchar(13),
			uid int
			)";
	if (!$con->table_exists("batchCutPaste",$FANNIE_OP_DB)){
		$con->query($batchCPQ,$FANNIE_OP_DB);
	}

	$batchBarcodeQ = "CREATE TABLE batchBarcodes (
		`upc` bigint(13) unsigned zerofill NOT NULL default '0000000000000',
		`description` varchar(30) default NULL,
		`normal_price` decimal(10,2) default NULL,
		`brand` varchar(50) default NULL,
		`sku` varchar(14) default NULL,
		`size` varchar(50) default NULL,
		`units` varchar(15) default NULL,
		`vendor` varchar(50) default NULL,
		`batchID` int
		)";
	if ($FANNIE_SERVER_DBMS == "MSSQL"){
		$batchBarcodeQ = "CREATE TABLE [batchBarcodes] (
			[upc] [varchar] (13) COLLATE SQL_Latin1_General_CP1_CI_AS NOT NULL ,
			[description] [varchar] (30) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
			[normal_price] [money] NULL ,
			[brand] [varchar] (255) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
			[sku] [varchar] (50) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
			[size] [varchar] (255) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
			[units] [varchar] (15) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
			[vendor] [varchar] (50) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
			[batchID] [int] NOT NULL)";
	}
	if (!$con->table_exists("batchBarcodes", $FANNIE_OP_DB)){
		$con->query($batchBarcodeQ,$FANNIE_OP_DB);
	}

	$lcViewQ = "CREATE VIEW likeCodeView AS
		SELECT l.likeCode,l.likeCodeDesc,u.upc,p.normal_price
		FROM likeCodes AS l LEFT JOIN upcLike AS u
		ON l.likeCode=u.likeCode LEFT JOIN
		products AS p ON u.upc=p.upc";
	if (!$con->table_exists("likeCodeView",$FANNIE_OP_DB)){
		$con->query($lcViewQ,$FANNIE_OP_DB);
	}

	$batchMergeTableQ = "CREATE TABLE batchMergeTable (
		startDate datetime,
		endDate datetime,
		upc bigint(13) unsigned zerofill,
		description varchar(30),
		batchID int
		)";
	if (!$con->table_exists("batchMergeTable")){
		$batchMergeTableQ = "CREATE TABLE batchMergeTable (
			startDate datetime,
			endDate datetime,
			upc varchar(13),
			description varchar(30),
			batchID int
			)";
	}
	if (!$con->table_exists('batchMergeTable',$FANNIE_OP_DB)){
		$con->query($batchMergeTableQ,$FANNIE_OP_DB);
	}

	$batchMergeProdQ = "CREATE VIEW batchMergeProd AS
		SELECT b.startDate,b.endDate,p.upc,p.description,b.batchID
		FROM batches AS b LEFT JOIN batchList AS l
		ON b.batchID=l.batchID LEFT JOIN products AS p
		ON p.upc = l.upc";
	if (!$con->table_exists('batchMergeProd',$FANNIE_OP_DB)){
		$con->query($batchMergeProdQ,$FANNIE_OP_DB);
	}

	$batchMergeLCQ = "CREATE VIEW batchMergeLC AS
		SELECT b.startDate, b.endDate, p.upc, p.description, b.batchID
		FROM batchList AS l LEFT JOIN batches AS b
		ON b.batchID=l.batchID LEFT JOIN upcLike AS u
		ON l.upc = concat('LC',convert(u.likeCode,char))
		LEFT JOIN products AS p ON u.upc=p.upc
		WHERE p.upc IS NOT NULL";
	if ($FANNIE_SERVER_DBMS == "MSSQL"){
		$batchMergeLCQ = "CREATE VIEW batchMergeLC AS
			SELECT b.startDate, b.endDate, p.upc, p.description, b.batchID
			FROM batchList AS l LEFT JOIN batches AS b
			ON b.batchID=l.batchID LEFT JOIN upcLike AS u
			ON l.upc = 'LC'+convert(varchar,u.likeCode)
			LEFT JOIN products AS p ON u.upc=p.upc
			WHERE p.upc IS NOT NULL";
	}
	if (!$con->table_exists('batchMergeLC',$FANNIE_OP_DB)){
		$con->query($batchMergeLCQ,$FANNIE_OP_DB);
	}

	$memTypeQ = "CREATE TABLE memtype (
		memtype tinyint,
		memDesc varchar(20))";
	if (!$con->table_exists('memtype',$FANNIE_OP_DB)){
		$con->query($memTypeQ,$FANNIE_OP_DB);
	}

	$custdataQ = "CREATE TABLE `custdata` (
	  `CardNo` int(8) default NULL,
	  `personNum` tinyint(4) NOT NULL default '1',
	  `LastName` varchar(30) default NULL,
	  `FirstName` varchar(30) default NULL,
	  `CashBack` double NOT NULL default '60',
	  `Balance` double NOT NULL default '0',
	  `Discount` smallint(6) default NULL,
	  `MemDiscountLimit` double NOT NULL default '0',
	  `ChargeOk` tinyint(4) NOT NULL default '1',
	  `WriteChecks` tinyint(4) NOT NULL default '1',
	  `StoreCoupons` tinyint(4) NOT NULL default '1',
	  `Type` varchar(10) NOT NULL default 'pc',
	  `memType` tinyint(4) default NULL,
	  `staff` tinyint(4) NOT NULL default '0',
	  `SSI` tinyint(4) NOT NULL default '0',
	  `Purchases` double NOT NULL default '0',
	  `NumberOfChecks` smallint(6) NOT NULL default '0',
	  `memCoupons` int(11) NOT NULL default '1',
	  `blueLine` varchar(50) default NULL,
	  `Shown` tinyint(4) NOT NULL default '1',
	  `id` int(11) NOT NULL auto_increment,
	  PRIMARY KEY  (`id`),
	  KEY `CardNo` (`CardNo`),
	  KEY `LastName` (`LastName`)
	)";
	if ($FANNIE_SERVER_DBMS == "MSSQL"){
		$custdataQ = "CREATE TABLE [custdata] (
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
	if (!$con->table_exists('custdata',$FANNIE_OP_DB)){
		$con->query($custdataQ,$FANNIE_OP_DB);
	}

	$meminfoQ = "CREATE TABLE `meminfo` (
	  `card_no` smallint(5) default NULL,
	  `last_name` varchar(30) default NULL,
	  `first_name` varchar(30) default NULL,
	  `othlast_name` varchar(30) default NULL,
	  `othfirst_name` varchar(30) default NULL,
	  `street` varchar(255) default NULL,
	  `city` varchar(20) default NULL,
	  `state` varchar(2) default NULL,
	  `zip` varchar(10) default NULL,
	  `phone` varchar(30) default NULL,
	  `email_1` varchar(50) default NULL,
	  `email_2` varchar(50) default NULL,
	  `ads_OK` tinyint(1) default '1'
	)";
	if ($FANNIE_SERVER_DBMS == "MSSQL"){
		$meminfoQ = "CREATE TABLE meminfo (
		  card_no smallint ,
		  last_name varchar(30) ,
		  first_name varchar(30) ,
		  othlast_name varchar(30) ,
		  othfirst_name varchar(30) ,
		  street varchar(255) ,
		  city varchar(20) ,
		  state varchar(2) ,
		  zip varchar(10) ,
		  phone varchar(30) ,
		  email_1 varchar(50) ,
		  email_2 varchar(50) ,
		  ads_OK tinyint 
		)";
	}
	if (!$con->table_exists('meminfo',$FANNIE_OP_DB)){
		$con->query($meminfoQ,$FANNIE_OP_DB);
	}

	$cardsQ = "CREATE TABLE memberCards (card_no int, upc varchar(13))";
	if (!$con->table_exists('memberCards',$FANNIE_OP_DB)){
		$con->query($cardsQ,$FANNIE_OP_DB);
	}

	$tenderQ = "CREATE TABLE tenders (
		TenderID smallint,
		TenderCode varchar(255),
		TenderName varchar(255),
		TenderType varchar(255),
		ChangeMessage varchar(255),
		MinAmount double,
		MaxAmount double,
		MaxRefund double)";
	if(!$con->table_exists('tenders',$FANNIE_OP_DB)){
		$con->query($tenderQ,$FANNIE_OP_DB);
	}

	$custRpt = "CREATE TABLE customReceipt (
		text varchar(20),
		seq int,
		type varchar(20)
		)";
	if(!$con->table_exists('customReceipt',$FANNIE_OP_DB)){
		$con->query($custRpt,$FANNIE_OP_DB);
	}

	$houseCoup = "CREATE TABLE houseCoupons (
		coupID int,
		endDate datetime,
		`limit` smallint,
		memberOnly smallint,
		discoutType varchar(2),
		discountValue double,
		minType varchar(2),
		minValue double,
		department int)";
	if ($FANNIE_SERVER_DBMS == 'MSSQL')
		$houseCoup = str_replace("`","",$houseCoup);
	if(!$con->table_exists('houseCoupons',$FANNIE_OP_DB)){
		$con->query($houseCoup,$FANNIE_OP_DB);
	}

	$hciQ = "CREATE TABLE houseCouponItems (
		coupID int,
		upc varchar(13),
		type varchar(15))";
	if(!$con->table_exists('houseCouponItems',$FANNIE_OP_DB)){
		$con->query($hciQ,$FANNIE_OP_DB);
	}	

	$superQ = "CREATE TABLE superdepts (
		superID int,
		dept_ID int)";
	if (!$con->table_exists("superdepts",$FANNIE_OP_DB)){
		$con->query($superQ,$FANNIE_OP_DB);
	}

	$superQ = "CREATE TABLE superDeptNames (
		superID int,
		super_name varchar(50)
	)";
	if (!$con->table_exists("superDeptNames",$FANNIE_OP_DB)){
		$con->query($superQ,$FANNIE_OP_DB);
	}

	// mysql doesn't permit subqueries in the
	// FROM clause of a VIEW. Lame.
	$minQ = "CREATE VIEW SuperMinIdView AS
		SELECT MIN(superID) as superID,dept_ID
		FROM superdepts
		GROUP BY dept_ID";
	if (!$con->table_exists("SuperMinIdView",$FANNIE_OP_DB)){
		$con->query($minQ,$FANNIE_OP_DB);
	}

	$masterQ = "CREATE VIEW MasterSuperDepts AS
		SELECT n.superID,n.super_name,s.dept_ID
		FROM SuperMinIdView AS s
		LEFT JOIN superDeptNames AS n
		on s.superID=n.superID";
	if (!$con->table_exists("MasterSuperDepts",$FANNIE_OP_DB)){
		$con->query($masterQ,$FANNIE_OP_DB);
	}

	$dmQ = "CREATE TABLE deptMargin (
		dept_ID int,
		margin decimal(10,5)
	)";
	if (!$con->table_exists("deptMargin",$FANNIE_OP_DB)){
		$con->query($dmQ,$FANNIE_OP_DB);
	}

	$pcodeQ = "CREATE TABLE deptSalesCodes (
		dept_ID int,
		salesCode int
	)";
	if (!$con->table_exists("deptSalesCodes",$FANNIE_OP_DB)){
		$con->query($pcodeQ,$FANNIE_OP_DB);
	}

	$prodMargin= "create view productMargin as
		select upc,
		cost,normal_price,
		(normal_price-cost) / normal_price as actualMargin,
		d.margin as desiredMargin,
		convert(cost/(1-d.margin),decimal(10,2)) as srp
		from products as p left join deptMargin as d
		on p.department=d.dept_ID
		where normal_price > 0
		and d.margin <> 1";
	if ($FANNIE_SERVER_DBMS == 'mssql'){
		$prodMargin= "create view productMargin as
			select upc,
			cost,normal_price,
			(normal_price-cost) / normal_price as actualMargin,
			d.margin as desiredMargin,
			convert(numeric(10,2),cost/(1-d.margin)) as srp
			from products as p left join deptMargin as d
			on p.department=d.dept_ID
			where normal_price > 0
			and d.margin <> 1";
	}
	if (!$con->table_exists("productMargin",$FANNIE_OP_DB)){
		$con->query($prodMargin,$FANNIE_OP_DB);
	}

	$vendorQ = "create table vendors (vendorID int,
		vendorName varchar(50),
		primary key (vendorID))";
	if (!$con->table_exists("vendors",$FANNIE_OP_DB)){
		$con->query($vendorQ,$FANNIE_OP_DB);
	}

	$viQ = "CREATE TABLE vendorItems (
		upc varchar(13),
		sku varchar(10),
		brand varchar(50),
		description varchar(50),
		size varchar(25),
		units int,
		cost decimal(10,2),
		vendorDept int,
		vendorID int)";
	if ($FANNIE_SERVER_DBMS == 'mssql'){
		$viQ = "CREATE TABLE vendorItems (
			upc varchar(13),
			sku varchar(10),
			brand varchar(50),
			description varchar(50),
			size varchar(25),
			units int,
			cost money,
			vendorDept int,
			vendorID int)";
	}
	if (!$con->table_exists('vendorItems',$FANNIE_OP_DB)){
		$con->query($viQ);
	}

	$vdQ = "create table vendorDepartments (
		vendorID int,
		deptID int,
		name varchar(125),
		margin float,
		testing float
		)";
	if(!$con->table_exists('vendorDepartments',$FANNIE_OP_DB)){
		$con->query($vdQ,$FANNIE_OP_DB);
	}

	$vsrpQ = "create table vendorSRPs (
		vendorID int,
		upc varchar(13),
		srp decimal(10,2)
		)";
	if (!$con->table_exists('vendorSRPs',$FANNIE_OP_DB)){
		$con->query($vsrpQ,$FANNIE_OP_DB);
	}

	$mdQ = "CREATE TABLE memDates (
		card_no int,
		start_date datetime,
		end_date datetime,
		PRIMARY KEY (card_no))";
	if (!$con->table_exists('memDates',$FANNIE_OP_DB)){
		$con->query($mdQ,$FANNIE_OP_DB);
	}

	$siQ = "CREATE TABLE scaleItems (
		plu varchar(13),
		price float,
		itemdesc varchar(100),
		exceptionprice float,
		weight tinyint,
		bycount tinyint,
		tare float,
		shelflife int,
		text text,
		class varchar(6),
		label int,
		graphics int)";
	if (!$con->table_exists('scaleItems',$FANNIE_OP_DB)){
		$con->query($siQ,$FANNIE_OP_DB);
	}

	$poQ = "CREATE TABLE PurchaseOrder (
		name varchar(100),
		stamp datetime,";
	if ($FANNIE_SERVER_DBMS == "MSSQL")
		$poQ .= "id int IDENTITY (1,1) NOT NULL, primary key(id))";
	else
		$poQ .= "id int auto_increment NOT NULL, primary key(id))";
	if (!$con->table_exists('PurchaseOrder',$FANNIE_OP_DB)){
		$con->query($poQ,$FANNIE_OP_DB);
	}

	$poiQ = "CREATE TABLE PurchaseOrderItems (
		upc varchar(13),
		vendor_id int,
		order_id int,
		quantity int)";
	if (!$con->table_exists('PurchaseOrderItems',$FANNIE_OP_DB)){
		$con->query($poiQ,$FANNIE_OP_DB);
	}

	$so = "CREATE TABLE SpecialOrder (
		order_id int not null auto_increment,
		order_date datetime,
		uid int,
		status smallint,
		fullname varchar(200),
		order_info text,
		special_instructions text,
		superDept int,
		primary key(order_id)
		)";
	if ($FANNIE_SERVER_DBMS == "MSSQL"){
		$so = "CREATE TABLE SpecialOrder (
			order_id int identity(1,1) not null,
			order_date datetime,
			uid int,
			status smallint,
			fullname varchar(200),
			order_info text,
			special_instructions text,
			superDept int,
			primary key(order_id)
			)";
	}
	if (!$con->table_exists('SpecialOrder',$FANNIE_OP_DB)){
		$con->query($so,$FANNIE_OP_DB);
	}

	$soU = "CREATE TABLE SpecialOrderUser (
		id int not null auto_increment,
		card_no int,
		lastname varchar(100),
		firstname varchar(50),
		street1 varchar(100),
		street2 varchar(100),
		city varchar(20),
		state char(2),
		zip varchar(10),
		phone varchar(30),
		alt_phone varchar(30),
		email varchar(50),
		primary key(id)
		)";
	if ($FANNIE_SERVER_DBMS == "MSSQL"){
		$soU = "CREATE TABLE SpecialOrderUser (
			id int identity(1,1) not null,
			card_no int,
			lastname varchar(100),
			firstname varchar(50),
			street1 varchar(100),
			street2 varchar(100),
			city varchar(20),
			state char(2),
			zip varchar(10),
			phone varchar(30),
			alt_phone varchar(30),
			email varchar(50),
			primary key(id)
			)";
	}
	if (!$con->table_exists('SpecialOrderUser',$FANNIE_OP_DB)){
		$con->query($soU,$FANNIE_OP_DB);
	}

	/* listing of email invoices that have been
	   sent to members */
	$emailLog = "CREATE TABLE emailLog (
		tdate datetime,
		card_no int,
		send_addr varchar(150),
		message_type varchar(100)
	)";
	if (!$con->table_exists('emailLog',$FANNIE_OP_DB)){
		$con->query($emailLog,$FANNIE_OP_DB);
	}

	/* generic notes attached to a member */
	$memNotes = "CREATE TABLE memberNotes (
		cardno int,
		note text,
		stamp datetime,
		username varchar(50)
	)";
	if (!$con->table_exists("memberNotes",$FANNIE_OP_DB)){
		$con->query($memNotes,$FANNIE_OP_DB);
	}

	/* mechanism for suspending memberships
	   temporarily but retaining account-type
	   settings */
	$susp = "CREATE TABLE suspensions (
		cardno int,
		type char(1),
		memtype1 int,
		memtype2 varchar(6),
		reason text,
		mailflag int,
		discount int,
		chargelimit decimal(10,2),
		reasoncode int
	)";	
	if ($FANNIE_SERVER_DBMS == "MSSQL")
		$susp = str_replace("decimal(10,2)","money",$susp);
	if (!$con->table_exists("suspensions",$FANNIE_OP_DB)){
		$con->query($susp,$FANNIE_OP_DB);
	}

	/* defined suspension reasons
	   "mask" is a bitmask corresponding to
	   suspensions.reasoncode so multiple
	   reasons can be set in one suspension
	   record */
	$rcs = "CREATE TABLE reasoncodes (
		textStr varchar(100),
		mask int
	)";
	if (!$con->table_exists("reasoncodes",$FANNIE_OP_DB)){
		$con->query($rcs,$FANNIE_OP_DB);
	}

	/* historical record of account suspensions */
	$suspHist = "CREATE TABLE suspension_history (
		username varchar(50),
		postdate datetime,
		post text,
		cardno int,
		reasoncode int
	)";
	if (!$con->table_exists("suspension_history",$FANNIE_OP_DB)){
		$con->query($suspHist,$FANNIE_OP_DB);
	}
}

function create_trans_dbs($con){
	global $FANNIE_TRANS_DB, $FANNIE_SERVER_DBMS, $FANNIE_OP_DB;

	$opstr = $FANNIE_OP_DB;
	if ($FANNIE_SERVER_DBMS=="mssql") $opstr .= ".dbo";

	$alogQ = "CREATE TABLE alog (
	`datetime` datetime,
	LaneNo tinyint,
	CashierNo tinyint,
	TransNo int,
	Activity tinyint,
	`Interval` double)";
	if ($FANNIE_SERVER_DBMS == 'MSSQL'){
		$alogQ = str_replace("`datetime`","[datetime]",$alogQ);
		$alogQ = str_replace("`","",$alogQ);
	}
	if(!$con->table_exists("alog",$FANNIE_TRANS_DB)){
		$con->query($alogQ,$FANNIE_TRANS_DB);
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
		amount double ,
		PAN varchar (19) ,
		issuer varchar (16) ,
		name varchar (50) ,
		manual tinyint ,
		sentPAN tinyint ,
		sentExp tinyint ,
		sentTr1 tinyint ,
		sentTr2 tinyint 
		)";
	if(!$con->table_exists('efsnetRequest',$FANNIE_TRANS_DB)){
		$con->query($efsrq,$FANNIE_TRANS_DB);
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
	if(!$con->table_exists('efsnetResponse',$FANNIE_TRANS_DB)){
		$con->query($efsrp,$FANNIE_TRANS_DB);
	}

	$efsrqm = "CREATE TABLE efsnetRequestMod (
		date int ,
		cashierNo int ,
		laneNo int ,
		transNo int ,
		transID int ,
		datetime datetime ,
		origRefNum varchar (50),
		origAmount double ,
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
	if(!$con->table_exists('efsnetRequestMod',$FANNIE_TRANS_DB)){
		$con->query($efsrqm,$FANNIE_TRANS_DB);
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
		amount double,
		PAN varchar(19),
		manual tinyint
		)";
	if(!$con->table_exists('valutecRequest',$FANNIE_TRANS_DB)){
		$con->query($vrq,$FANNIE_TRANS_DB);
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
	if(!$con->table_exists('valutecResponse',$FANNIE_TRANS_DB)){
		$con->query($vrp,$FANNIE_TRANS_DB);
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
	if(!$con->table_exists('valutecRequestMod',$FANNIE_TRANS_DB)){
		$con->query($vrqm,$FANNIE_TRANS_DB);
	}

	$invCur = "CREATE TABLE InvDelivery (
		inv_date datetime,
		upc varchar(13),
		vendor_id int,
		quantity int,
		price float)";
	if (!$con->table_exists('InvDelivery',$FANNIE_TRANS_DB)){
		$con->query($invCur,$FANNIE_TRANS_DB);
	}

	$invCur = "CREATE TABLE InvDeliveryLM (
		inv_date datetime,
		upc varchar(13),
		vendor_id int,
		quantity int,
		price float)";
	if (!$con->table_exists('InvDeliveryLM',$FANNIE_TRANS_DB)){
		$con->query($invCur,$FANNIE_TRANS_DB);
	}

	$invArc = "CREATE TABLE InvDeliveryArchive (
		inv_date datetime,
		upc varchar(13),
		vendor_id int,
		quantity int,
		price float)";
	if (!$con->table_exists('InvDeliveryArchive',$FANNIE_TRANS_DB)){
		$con->query($invArc,$FANNIE_TRANS_DB);
	}

	$invRecent = "CREATE VIEW InvRecentOrders AS
		SELECT inv_date,upc,sum(quantity) as quantity,
		sum(price) as price
		FROM InvDelivery GROUP BY inv_date,upc
		UNION ALL
		SELECT inv_date,upc,sum(quantity) as quantity,
		sum(price) as price
		FROM InvDeliveryLM GROUP BY inv_date,upc";
	if (!$con->table_exists('InvRecentOrders',$FANNIE_TRANS_DB)){
		$con->query($invRecent,$FANNIE_TRANS_DB);
	}

	$union = "CREATE VIEW InvDeliveryUnion AS
		select upc,vendor_id,sum(quantity) as quantity,
		sum(price) as price,max(inv_date) as inv_date
		FROM InvDelivery
		GROUP BY upc,vendor_id
		UNION ALL
		select upc,vendor_id,sum(quantity) as quantity,
		sum(price) as price,max(inv_date) as inv_date
		FROM InvDeliveryLM
		GROUP BY upc,vendor_id
		UNION ALL
		select upc,vendor_id,sum(quantity) as quantity,
		sum(price) as price,max(inv_date) as inv_date
		FROM InvDeliveryArchive
		GROUP BY upc,vendor_id";
	if (!$con->table_exists("InvDeliveryUnion",$FANNIE_TRANS_DB)){
		$con->query($union,$FANNIE_TRANS_DB);
	}

	$total = "CREATE VIEW InvDeliveryTotals AS
		select upc,sum(quantity) as quantity,
		sum(price) as price,max(inv_date) as inv_date
		FROM InvDeliveryUnion
		GROUP BY upc";
	if (!$con->table_exists("InvDeliveryTotals",$FANNIE_TRANS_DB)){
		$con->query($total,$FANNIE_TRANS_DB);
	}

	$invSalesView = "CREATE VIEW InvSales AS
		select datetime as inv_date,upc,quantity,total as price
		FROM transArchive WHERE ".$con->monthdiff($con->now(),'datetime')." <= 1
		AND scale=0 AND trans_status NOT IN ('X','R') 
		AND trans_type = 'I' AND trans_subtype <> '0'
		AND register_no <> 99 AND emp_no <> 9999";
	if (!$con->table_exists("InvSales",$FANNIE_TRANS_DB)){
		$con->query($invSalesView,$FANNIE_TRANS_DB);
	}

	$invRecentSales = "CREATE VIEW InvRecentSales AS
		select t.upc, 
		max(t.inv_date) as mostRecentOrder,
		sum(CASE WHEN s.quantity IS NULL THEN 0 ELSE s.quantity END) as quantity,
		sum(CASE WHEN s.price IS NULL THEN 0 ELSE s.price END) as price
		from InvDeliveryTotals as t
		left join InvSales as s
		on t.upc=s.upc and
		".$con->datediff('s.inv_date','t.inv_date')." >= 0
		group by t.upc";
	if (!$con->table_exists("InvRecentSales",$FANNIE_TRANS_DB)){
		$con->query($invRecentSales,$FANNIE_TRANS_DB);
	}

	$invSales = "CREATE TABLE InvSalesArchive (
		inv_date datetime,
		upc varchar(13),
		quantity int,
		price float)";
	if (!$con->table_exists('InvSalesArchive',$FANNIE_TRANS_DB)){
		$con->query($invSales,$FANNIE_TRANS_DB);
	}

	$union = "CREATE VIEW InvSalesUnion AS
		select upc,sum(quantity) as quantity,
		sum(price) as price
		FROM InvSales
		WHERE ".$con->monthdiff($con->now(),'inv_date')." = 0
		GROUP BY upc
		UNION ALL
		select upc,sum(quantity) as quantity,
		sum(price) as price
		FROM InvSalesArchive
		GROUP BY upc";
	if (!$con->table_exists("InvSalesUnion",$FANNIE_TRANS_DB)){
		$con->query($union,$FANNIE_TRANS_DB);
	}

	$total = "CREATE VIEW InvSalesTotals AS
		select upc,sum(quantity) as quantity,
		sum(price) as price
		FROM InvSalesUnion
		GROUP BY upc";
	if (!$con->table_exists("InvSalesTotals",$FANNIE_TRANS_DB)){
		$con->query($total,$FANNIE_TRANS_DB);
	}
		
	$adj = "CREATE TABLE InvAdjustments (
		inv_date datetime,
		upc varchar(13),
		diff int)";
	if (!$con->table_exists("InvAdjustments",$FANNIE_TRANS_DB)){
		$con->query($adj,$FANNIE_TRANS_DB);
	}

	$adjTotal = "CREATE VIEW InvAdjustTotals AS
		SELECT upc,sum(diff) as diff,max(inv_date) as inv_date
		FROM InvAdjustments
		GROUP BY upc";
	if (!$con->table_exists("InvAdjustTotals",$FANNIE_TRANS_DB)){
		$con->query($adjTotal,$FANNIE_TRANS_DB);
	}

	$inv = "CREATE VIEW Inventory AS
		SELECT d.upc,
		d.quantity AS OrderedQty,
		CASE WHEN s.quantity IS NULL THEN 0
			ELSE s.quantity END AS SoldQty,
		CASE WHEN a.diff IS NULL THEN 0
			ELSE a.diff END AS Adjustments,
		CASE WHEN a.inv_date IS NULL THEN '1900-01-01'
			ELSE a.inv_date END AS LastAdjustDate,
		d.quantity - CASE WHEN s.quantity IS NULL
			THEN 0 ELSE s.quantity END + CASE WHEN
			a.diff IS NULL THEN 0 ELSE a.diff END
			AS CurrentStock
		FROM InvDeliveryTotals AS d
		INNER JOIN $opstr.VendorItems AS v 
		ON d.upc = v.upc
		LEFT JOIN InvSalesTotals AS s
		ON d.upc = s.upc LEFT JOIN
		InvAdjustTotals AS a ON d.upc=a.upc";
	if (!$con->table_exists("Inventory",$FANNIE_TRANS_DB)){
		$con->query($inv,$FANNIE_TRANS_DB);
	}

	$cache = "CREATE TABLE InvCache (
		upc varchar(13),
		OrderedQty int,
		SoldQty int,
		Adjustments int,
		LastAdjustDate datetime,
		CurrentStock int)";
	if (!$con->table_exists("InvCache",$FANNIE_TRANS_DB)){
		$con->query($cache,$FANNIE_TRANS_DB);
	}

}

function create_dlogs($con){
	global $FANNIE_TRANS_DB, $FANNIE_SERVER_DBMS, $FANNIE_AR_DEPARTMENTS, $FANNIE_EQUITY_DEPARTMENTS, $FANNIE_OP_DB;

	$trans_columns = "(
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
	  `quantity` double default NULL,
	  `scale` tinyint(4) default NULL,
	  `cost` double default 0.00 NULL,
	  `unitPrice` double default NULL,
	  `total` double default NULL,
	  `regPrice` double default NULL,
	  `tax` smallint(6) default NULL,
	  `foodstamp` tinyint(4) default NULL,
	  `discount` double default NULL,
	  `memDiscount` double default NULL,
	  `discountable` tinyint(4) default NULL,
	  `discounttype` tinyint(4) default NULL,
	  `voided` tinyint(4) default NULL,
	  `percentDiscount` tinyint(4) default NULL,
	  `ItemQtty` double default NULL,
	  `volDiscType` tinyint(4) default NULL,
	  `volume` tinyint(4) default NULL,
	  `VolSpecial` double default NULL,
	  `mixMatch` varchar(13) default NULL,
	  `matched` smallint(6) default NULL,
	  `memType` tinyint(2) default NULL,
	  `staff` tinyint(4) default NULL,
	  `numflag` smallint(6) default 0 NULL,
	  `charflag` varchar(2) default '' NULL,
	  `card_no` varchar(255) default NULL,
	  `trans_id` int(11) default NULL
	)";

	if ($FANNIE_SERVER_DBMS == 'MSSQL'){
		$trans_columns = "([datetime] [datetime] NOT NULL ,
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
			[cost] [money] NULL ,
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
			[mixMatch] [nvarchar] (13) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
			[matched] [smallint] NOT NULL ,
			[memType] [smallint] NULL ,
			[isStaff] [tinyint] NULL ,
			[numflag] [smallint] NULL ,
			[charflag] [nvarchar] (2) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
			[card_no] [nvarchar] (6) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
			[trans_id] [int] NOT NULL )";
	}

	if (!$con->table_exists('dtransactions',$FANNIE_TRANS_DB)){
		$con->query('CREATE TABLE dtransactions '.$trans_columns,$FANNIE_TRANS_DB);
	}
	if (!$con->table_exists('transarchive',$FANNIE_TRANS_DB)){
		$con->query('CREATE TABLE transarchive '.$trans_columns,$FANNIE_TRANS_DB);
	}

	if (!$con->table_exists('suspended',$FANNIE_TRANS_DB)){
		$con->query('CREATE TABLE suspended '.$trans_columns,$FANNIE_TRANS_DB);
	}

	$dlogView = "select 
		`dtransactions`.`datetime` AS `tdate`,
		`dtransactions`.`register_no` AS `register_no`,
		`dtransactions`.`emp_no` AS `emp_no`,
		`dtransactions`.`trans_no` AS `trans_no`,
		`dtransactions`.`upc` AS `upc`,
		`dtransactions`.`trans_type` AS `trans_type`,
		`dtransactions`.`trans_subtype` AS `trans_subtype`,
		`dtransactions`.`trans_status` AS `trans_status`,
		`dtransactions`.`department` AS `department`,
		`dtransactions`.`quantity` AS `quantity`,
		`dtransactions`.`unitPrice` AS `unitPrice`,
		`dtransactions`.`total` AS `total`,
		`dtransactions`.`tax` AS `tax`,
		`dtransactions`.`foodstamp` AS `foodstamp`,
		`dtransactions`.`ItemQtty` AS `itemQtty`,
		`dtransactions`.`card_no` AS `card_no`,
		`dtransactions`.`trans_id` AS `trans_id` 
		from `dtransactions` 
		where 
		((`dtransactions`.`trans_status` <> 'D') 
		and 
		(`dtransactions`.`trans_status` <> 'X'))";
	if ($FANNIE_SERVER_DBMS == 'MSSQL'){
		$dlogView = "SELECT
			datetime AS tdate,
			register_no,
			emp_no,
			trans_no,
			upc,
			trans_type,
			trans_subtype,
			trans_status,
			department,
			quantity,
			unitPrice,
			total,
			tax,
			foodstamp,
			ItemQtty,
			card_no,
			trans_id
			FROM dtransactions
			WHERE trans_status NOT IN ('D','X')";
	}
	if (!$con->table_exists('dlog',$FANNIE_TRANS_DB)){
		$con->query('CREATE VIEW dlog AS '.$dlogView,$FANNIE_TRANS_DB);
	}
	if (!$con->table_exists('dlog_90_view',$FANNIE_TRANS_DB)){
		$dlogView90 = str_replace('dtransactions','transarchive',$dlogView);
		$con->query('CREATE VIEW dlog_90_view AS '.$dlogView90,$FANIE_TRANS_DB);
	}

	$log_columns = "(`tdate` datetime default NULL,
          `register_no` smallint(6) default NULL,
          `emp_no` smallint(6) default NULL,
          `trans_no` int(11) default NULL,
          `upc` varchar(255) default NULL,
          `trans_type` varchar(255) default NULL,
          `trans_subtype` varchar(255) default NULL,
          `trans_status` varchar(255) default NULL,
          `department` smallint(6) default NULL,
          `quantity` double default NULL,
          `unitPrice` double default NULL,
          `total` double default NULL,
          `tax` smallint(6) default NULL,
          `foodstamp` tinyint(4) default NULL,
          `ItemQtty` double default NULL,
          `card_no` varchar(255) default NULL,
          `trans_id` int(11) default NULL)";
	if ($FANNIE_SERVER_DBMS == 'MSSQL'){
		$log_columns = "([tdate] [datetime] NOT NULL ,
                        [register_no] [smallint] NOT NULL ,
                        [emp_no] [smallint] NOT NULL ,
                        [trans_no] [int] NOT NULL ,
                        [upc] [nvarchar] (13) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
                        [trans_type] [nvarchar] (1) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
                        [trans_subtype] [nvarchar] (2) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
                        [trans_status] [nvarchar] (1) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
                        [department] [smallint] NULL ,
                        [quantity] [float] NULL ,
                        [total] [money] NOT NULL ,
                        [regPrice] [money] NULL ,
                        [tax] [smallint] NULL ,
                        [foodstamp] [tinyint] NOT NULL ,
                        [ItemQtty] [float] NULL ,
                        [card_no] [nvarchar] (6) COLLATE SQL_Latin1_General_CP1_CI_AS NULL ,
                        [trans_id] [int] NOT NULL )";
	}

	if (!$con->table_exists('dlog_15',$FANNIE_TRANS_DB)){
		$con->query('CREATE TABLE dlog_15 '.$log_columns,$FANIE_TRANS_DB);
	}

	$susToday = "CREATE VIEW suspendedtoday AS
		SELECT * FROM suspended WHERE
		".$con->datediff($con->now(),'datetime')." = 0";
	if (!$con->table_exists('suspendedtoday',$FANNIE_TRANS_DB)){
		$con->query($susToday,$FANNIE_TRANS_DB);
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
		where ".$con->datediff($con->now(),'tdate')."= 0
		and trans_subtype not in ('0','')";
	if (!$con->table_exists("TenderTapeGeneric",$FANNIE_TRANS_DB)){
		$con->query($ttG,$FANNIE_TRANS_DB);
	}

	$rp1Q = "CREATE  view rp_dt_receipt_90 as 
		select 
		datetime,
		register_no,
		emp_no,
		trans_no,
		description,
		case 
			when voided = 5 
				then 'Discount'
			when trans_status = 'M'
				then 'Mbr special'
			when scale <> 0 and quantity <> 0 
				then concat(convert(quantity,char), ' @ ', convert(unitPrice,char))
			when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity = 1
				then concat(convert(volume,char), ' /', convert(unitPrice,char))
			when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity <> 1
				then concat(convert(Quantity,char), ' @ ', convert(Volume,char), ' /', convert(unitPrice,char))
			when abs(itemQtty) > 1 and discounttype = 3
				then concat(convert(ItemQtty,char), ' /', convert(UnitPrice,char))
			when abs(itemQtty) > 1
				then concat(convert(quantity,char), ' @ ', convert(unitPrice,char))	
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
		card_no as memberID,
		unitPrice,
		voided,
		trans_id,
		concat(convert(emp_no,char), '-', convert(register_no,char), '-', convert(trans_no,char)) as trans_num

		from transArchive
		where voided <> 5 and UPC <> 'TAX' and UPC <> 'DISCOUNT'";
	if ($FANNIE_SERVER_DBMS == 'MSSQL'){
		$rp1Q = "CREATE  view rp_dt_receipt_90 as 
			select 
			datetime,
			register_no,
			emp_no,
			trans_no,
			description,
			case 
				when voided = 5 
					then 'Discount'
				when trans_status = 'M'
					then 'Mbr special'
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
			card_no as memberID,
			unitPrice,
			voided,
			trans_id,
			(convert(varchar,emp_no) +  '-' + convert(varchar,register_no) + '-' + convert(varchar,trans_no)) as trans_num

			from transArchive
			where voided <> 5 and UPC <> 'TAX' and UPC <> 'DISCOUNT'";
	}
	if (!$con->table_exists("rp_dt_receipt_90",$FANNIE_TRANS_DB)){
		$con->query($rp1Q,$FANNIE_TRANS_DB);
	}

	$rp2Q = "create  view rp_receipt_header_90 as
		select
		datetime as dateTimeStamp,
		card_no as memberID,
		concat(convert(emp_no,char), '-', convert(register_no,char), '-', convert(trans_no,char)) as trans_num,
		register_no,
		emp_no,
		trans_no,
		convert(sum(case when discounttype = 1 then discount else 0 end),decimal(10,2)) as discountTTL,
		convert(sum(case when discounttype = 2 then memDiscount else 0 end),decimal(10,2)) as memSpecial,
		convert(sum(case when upc = '0000000008005' then total else 0 end),decimal(10,2)) as couponTotal,
		convert(sum(case when upc = 'MEMCOUPON' then unitPrice else 0 end),decimal(10,2)) as memCoupon,
		abs(sum(case when trans_subtype = 'MI' or trans_subtype = 'CX' then total else 0 end)) as chargeTotal,
		sum(case when upc = 'Discount' then total else 0 end) as transDiscount,
		sum(case when trans_type = 'T' then -1 * total else 0 end) as tenderTotal

		from transArchive
		group by register_no, emp_no, trans_no, card_no, datetime";
	if ($FANNIE_SERVER_DBMS == 'MSSQL'){
		$rp2Q = "create  view rp_receipt_header_90 as
			select
			datetime as dateTimeStamp,
			card_no as memberID,
			(convert(varchar,emp_no) +  '-' + convert(varchar,register_no) + '-' + convert(varchar,trans_no)) as trans_num,
			register_no,
			emp_no,
			trans_no,
			convert(numeric(10,2), sum(case when discounttype = 1 then discount else 0 end)) as discountTTL,
			convert(numeric(10,2), sum(case when discounttype = 2 then memDiscount else 0 end)) as memSpecial,
			convert(numeric(10,2), sum(case when upc = '0000000008005' then total else 0 end)) as couponTotal,
			convert(numeric(10,2), sum(case when upc = 'MEMCOUPON' then unitPrice else 0 end)) as memCoupon,
			abs(sum(case when trans_subtype = 'MI' or trans_subtype = 'CX' then total else 0 end)) as chargeTotal,
			sum(case when upc = 'Discount' then total else 0 end) as transDiscount,
			sum(case when trans_type = 'T' then -1 * total else 0 end) as tenderTotal

			from transArchive
			group by register_no, emp_no, trans_no, card_no, datetime";
	}
	if (!$con->table_exists("rp_receipt_header_90",$FANNIE_TRANS_DB)){
		$con->query($rp2Q,$FANNIE_TRANS_DB);
	}

	$ret = preg_match_all("/[0-9]+/",$FANNIE_AR_DEPARTMENTS,$depts);
	if ($ret != 0){
		/* AR departments exist */
		$depts = array_pop($depts);
		$dlist = "(";
		foreach ($depts as $d){
			$dlist .= $d.",";	
		}
		$dlist = substr($dlist,0,strlen($dlist)-1).")";

		/* view for today's charges & payments */
		$iouView = "CREATE VIEW memIouToday AS
			SELECT card_no,
			SUM(CASE WHEN trans_subtype='MI' THEN total ELSE 0 END) as charges,
			SUM(CASE WHEN department IN $dlist THEN total ELSE 0 END) as payments
			FROM dlog WHERE ".$con->datediff($con->now(),'tdate')." = 0
			AND (trans_subtype='MI' OR department IN $dlist)
			GROUP BY card_no";
		if (!$con->table_exists("memIouToday",$FANNIE_TRANS_DB)){
			$con->query($iouView,$FANNIE_TRANS_DB);
		}

		
		/* view for real-time account balances */
		$cdata = ($FANNIE_SERVER_DBMS=='MSSQL')?$FANNIE_OP_DB.".dbo.custdata":$FANNIE_OP_DB.".custdata";
		$newBal = "CREATE VIEW newBalanceToday_cust AS
			SELECT   c.cardno as memnum, c.discount as discounttype,c.balance as ARCurrBalance,
			(case when a.charges is NULL then 0 ELSE a.charges END) as totcharges,
			(CASE WHEN a.payments IS NULL THEN 0 ELSE a.payments END) as totpayments,
			(CASE when a.card_no is NULL then c.Balance ELSE (c.Balance -a.charges - a.payments)END) as balance
			FROM $cdata as c left outer join memIouToday as a ON c.cardno = a.card_no
			where c.personnum = 1";
		if (!$con->table_exists("newBalanceToday_cust",$FANNIE_TRANS_DB)){
			$con->query($newBal,$FANNIE_TRANS_DB);
		}

		/* table for storing charge/payment history */
		$arHist = "CREATE TABLE ar_history (
			card_no int,
			Charges decimal(10,2),
			Payments decimal(10,2),
			tdate datetime,
			trans_num varchar(90)
		)";
		if ($FANNIE_TRANS_DB == "MSSQL")
			$arHist = str_replace("decimal(10,2)","money",$arHist);
		if (!$con->table_exists("ar_history",$FANNIE_TRANS_DB)){
			$con->query($arHist);
		}
	}

	$ret = preg_match_all("/[0-9]+/",$FANNIE_EQUITY_DEPARTMENTS,$depts);
	if ($ret != 0){
		/* equity departments exist */
		$depts = array_pop($depts);
		$dlist = "(";
		foreach ($depts as $d){
			$dlist .= $d.",";	
		}
		$dlist = substr($dlist,0,strlen($dlist)-1).")";

		/* table for equity purchase history */
		$eqHist = "CREATE TABLE stockpurchases (
			card_no int,
			stockPurchase decimal(10,2),
			tdate datetime,
			trans_num varchar(90),
			dept int
		)";
		if ($FANNIE_TRANS_DB == "MSSQL")
			$eqHist = str_replace("decimal(10,2)","money",$eqHist);
		if (!$con->table_exists("stockpurchases",$FANNIE_TRANS_DB)){
			$con->query($eqHist,$FANNIE_TRANS_DB);
		}

		/* per-member equity totals */
		$sumHist = "CREATE VIEW stockSum_purch AS
			SELECT card_no,
			SUM(stockPurchase) AS totPayments,
			MIN(tdate) AS startdate
			FROM stockpurchases
			GROUP BY card_no";
		if (!$con->table_exists("stockSum_purch",$FANNIE_TRANS_DB)){
			$con->query($sumHist,$FANNIE_TRANS_DB);
		}

		/* equity purchases today */
		$eqToday = "CREATE VIEW stockSumToday AS
			SELECT card_no,
			CASE WHEN department IN $dlist THEN total ELSE 0 END AS totPayments,
			MIN(tdate) AS startdate
			FROM dlog WHERE ".$con->datediff($con->now(),'tdate')." = 0
			AND department IN $dlist
			GROUP BY card_no";
		if (!$con->table_exists("stockSumToday",$FANNIE_TRANS_DB)){
			$con->query($eqToday,$FANNIE_TRANS_DB);
		}

		$minfo = ($FANNIE_SERVER_DBMS=='MSSQL')?$FANNIE_OP_DB.".dbo.meminfo":$FANNIE_OP_DB.".meminfo";
		/* real-time equity totals */
		$newBalEq = "CREATE VIEW newBalanceStockToday_test 
			SELECT
			m.card_no as card_no,
			case
				when a.card_no is not null and b.card_no is not null
				then a.totPayments + b.totPayments
				when a.card_no is not null
				then a.totPayments
				when b.card_no is not null
				then b.totPayments
			end
			as payments,
			case when a.startdate is null then
			b.startdate else a.startdate end
			as startdate

			FROM $minfo as m LEFT JOIN
			stockSum_purch as a on a.card_no=m.card_no
			LEFT JOIN stockSumToday as b
			ON m.card_no=b.card_no
			WHERE a.card_no is not null OR b.card_no is not null";
		if (!$con->table_exists("newBalanceStockToday_test",$FANNIE_TRANS_DB)){
			$con->query($newBalEq,$FANNIE_TRANS_DB);
		}
	}
}

function create_archive_dbs($con) {
	global $FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,$FANNIE_ARCHIVE_DB;

	$dstr = date("Ym");
	$archive = "transArchive".$dstr;
	$dbconn = ".";
	if ($FANNIE_SERVER_DBMS == "MSSQL")
		$dbconn = ".dbo.";

	$query = "CREATE TABLE $archive LIKE 
		{$FANNIE_TRANS_DB}{$dbconn}dtransactions";
	if ($FANNIE_SERVER_DBMS == "MSSQL"){
		$query = "SELECT TOP 1 * INTO $archive FROM 
			{$FANNIE_TRANS_DB}{$dbconn}dtransactions";
	}
	if (!$con->table_exists($archive,$FANNIE_ARCHIVE_DB)){
		$con->query($query,$FANNIE_ARCHIVE_DB);
	}

	$dlogView = "select 
		`$archive`.`datetime` AS `tdate`,
		`$archive`.`register_no` AS `register_no`,
		`$archive`.`emp_no` AS `emp_no`,
		`$archive`.`trans_no` AS `trans_no`,
		`$archive`.`upc` AS `upc`,
		`$archive`.`trans_type` AS `trans_type`,
		`$archive`.`trans_subtype` AS `trans_subtype`,
		`$archive`.`trans_status` AS `trans_status`,
		`$archive`.`department` AS `department`,
		`$archive`.`quantity` AS `quantity`,
		`$archive`.`unitPrice` AS `unitPrice`,
		`$archive`.`total` AS `total`,
		`$archive`.`tax` AS `tax`,
		`$archive`.`foodstamp` AS `foodstamp`,
		`$archive`.`ItemQtty` AS `itemQtty`,
		`$archive`.`card_no` AS `card_no`,
		`$archive`.`trans_id` AS `trans_id` 
		from `$archive` 
		where 
		((`$archive`.`trans_status` <> 'D') 
		and 
		(`$archive`.`trans_status` <> 'X'))";
	if ($FANNIE_SERVER_DBMS == 'MSSQL'){
		$dlogView = "SELECT
			datetime AS tdate,
			register_no,
			emp_no,
			trans_no,
			upc,
			trans_type,
			trans_subtype,
			trans_status,
			department,
			quantity,
			unitPrice,
			total,
			tax,
			foodstamp,
			ItemQtty,
			card_no,
			trans_id
			FROM $archive
			WHERE trans_status NOT IN ('D','X')";
	}

	if (!$con->table_exists("dlog".$dstr,$FANNIE_ARCHIVE_DB)){
		$con->query("CREATE VIEW dlog$dstr AS $dlogView",
			$FANNIE_ARCHIVE_DB);
	}

	$rp1Q = "CREATE  view rp_dt_receipt_$dstr as 
		select 
		datetime,
		register_no,
		emp_no,
		trans_no,
		description,
		case 
			when voided = 5 
				then 'Discount'
			when trans_status = 'M'
				then 'Mbr special'
			when scale <> 0 and quantity <> 0 
				then concat(convert(quantity,char), ' @ ', convert(unitPrice,char))
			when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity = 1
				then concat(convert(volume,char), ' /', convert(unitPrice,char))
			when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity <> 1
				then concat(convert(Quantity,char), ' @ ', convert(Volume,char), ' /', convert(unitPrice,char))
			when abs(itemQtty) > 1 and discounttype = 3
				then concat(convert(ItemQtty,char), ' /', convert(UnitPrice,char))
			when abs(itemQtty) > 1
				then concat(convert(quantity,char), ' @ ', convert(unitPrice,char))	
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
		card_no as memberID,
		unitPrice,
		voided,
		trans_id,
		concat(convert(emp_no,char), '-', convert(register_no,char), '-', convert(trans_no,char)) as trans_num

		from transArchive$dstr
		where voided <> 5 and UPC <> 'TAX' and UPC <> 'DISCOUNT'";
	if ($FANNIE_SERVER_DBMS == 'MSSQL'){
		$rp1Q = "CREATE  view rp_dt_receipt_$dstr as 
			select 
			datetime,
			register_no,
			emp_no,
			trans_no,
			description,
			case 
				when voided = 5 
					then 'Discount'
				when trans_status = 'M'
					then 'Mbr special'
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
			card_no as memberID,
			unitPrice,
			voided,
			trans_id,
			(convert(varchar,emp_no) +  '-' + convert(varchar,register_no) + '-' + convert(varchar,trans_no)) as trans_num

			from transArchive$dstr
			where voided <> 5 and UPC <> 'TAX' and UPC <> 'DISCOUNT'";
	}
	if (!$con->table_exists("rp_dt_receipt_$dstr",$FANNIE_ARCHIVE_DB)){
		$con->query($rp1Q,$FANNIE_ARCHIVE_DB);
	}

	$rp2Q = "create  view rp_receipt_header_$dstr as
		select
		datetime as dateTimeStamp,
		card_no as memberID,
		concat(convert(emp_no,char), '-', convert(register_no,char), '-', convert(trans_no,char)) as trans_num,
		register_no,
		emp_no,
		trans_no,
		convert(sum(case when discounttype = 1 then discount else 0 end),decimal(10,2)) as discountTTL,
		convert(sum(case when discounttype = 2 then memDiscount else 0 end),decimal(10,2)) as memSpecial,
		convert(sum(case when upc = '0000000008005' then total else 0 end),decimal(10,2)) as couponTotal,
		convert(sum(case when upc = 'MEMCOUPON' then unitPrice else 0 end),decimal(10,2)) as memCoupon,
		abs(sum(case when trans_subtype = 'MI' or trans_subtype = 'CX' then total else 0 end)) as chargeTotal,
		sum(case when upc = 'Discount' then total else 0 end) as transDiscount,
		sum(case when trans_type = 'T' then -1 * total else 0 end) as tenderTotal

		from transArchive$dstr
		group by register_no, emp_no, trans_no, card_no, datetime";
	if ($FANNIE_SERVER_DBMS == 'MSSQL'){
		$rp2Q = "create  view rp_receipt_header_$dstr as
			select
			datetime as dateTimeStamp,
			card_no as memberID,
			(convert(varchar,emp_no) +  '-' + convert(varchar,register_no) + '-' + convert(varchar,trans_no)) as trans_num,
			register_no,
			emp_no,
			trans_no,
			convert(numeric(10,2), sum(case when discounttype = 1 then discount else 0 end)) as discountTTL,
			convert(numeric(10,2), sum(case when discounttype = 2 then memDiscount else 0 end)) as memSpecial,
			convert(numeric(10,2), sum(case when upc = '0000000008005' then total else 0 end)) as couponTotal,
			convert(numeric(10,2), sum(case when upc = 'MEMCOUPON' then unitPrice else 0 end)) as memCoupon,
			abs(sum(case when trans_subtype = 'MI' or trans_subtype = 'CX' then total else 0 end)) as chargeTotal,
			sum(case when upc = 'Discount' then total else 0 end) as transDiscount,
			sum(case when trans_type = 'T' then -1 * total else 0 end) as tenderTotal

			from transArchive$dstr
			group by register_no, emp_no, trans_no, card_no, datetime";
	}
	if (!$con->table_exists("rp_receipt_header_$dstr",$FANNIE_ARCHIVE_DB)){
		$con->query($rp2Q,$FANNIE_ARCHIVE_DBMS);
	}
}

?>
