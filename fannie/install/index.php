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
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/


/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	*  5Dec2012 Eric Lee Added table memContactPrefs

*/

ini_set('display_errors','1');
?>
<?php 
include('../config.php'); 
include('util.php');
include('db.php');
?>
Necessities
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="auth.php">Authentication</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="mem.php">Members</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="stores.php">Stores</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="update.php">Updates</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="plugins.php">Plugins</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="sample_data/extra_data.php">Sample Data</a>
<form action=index.php method=post>
<h1>Fannie install checks</h1>
<?php
// path detection
$FILEPATH = rtrim($_SERVER['SCRIPT_FILENAME'],'index.php');
$URL = rtrim($_SERVER['SCRIPT_NAME'],'index.php');
$FILEPATH = rtrim($FILEPATH,'/');
$URL = rtrim($URL,'/');
$FILEPATH = rtrim($FILEPATH,'install');
$URL = rtrim($URL,'install');
$FANNIE_ROOT = $FILEPATH;
$FANNIE_URL = $URL;

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
	echo "<blockquote>";
	echo "config.php ({$FILEPATH}config.php) is Fannie's main configuration file.";
	echo "<ul>";
	echo "<li>If this file exists, ensure it is writable by the user running PHP (see above)";
	echo "<li>If the file does not exist, copy config.dist.php ({$FILEPATH}config.dist.php) to config.php";
	echo "<li>If neither file exists, create a new config.php ({$FILEPATH}config.php) containing:";
	echo "</ul>";
	echo "<pre style=\"font:fixed;background:#ccc;\">
&lt;?php
?&gt;
	</pre>";
	echo "</blockquote>";
	echo '<input type="submit" value="Refresh this page" />';
	echo "</form>";
	exit;
}
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
$supportedTypes = array(
	'MYSQL' => 'MySQL',
	'MSSQL' => 'MSSQL',
	'MYSQLI' => 'MySQLi',
	'PDO_MYSQL' => 'PDO MySQL'
);
foreach ($supportedTypes as $val=>$label){
	printf('<option value="%s" %s>%s</option>',
		$val,
		($FANNIE_SERVER_DBMS == $val)?'selected':'',
		$label);
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
if (!isset($FANNIE_OP_DB)) $FANNIE_OP_DB = 'core_op';
if (isset($_REQUEST['FANNIE_OP_DB']))
	$FANNIE_OP_DB = $_REQUEST['FANNIE_OP_DB'];
confset('FANNIE_OP_DB',"'$FANNIE_OP_DB'");
echo "<input type=text name=FANNIE_OP_DB value=\"$FANNIE_OP_DB\" />";
?>
<br />Server Transaction DB name
<?php
if (!isset($FANNIE_TRANS_DB)) $FANNIE_TRANS_DB = 'core_trans';
if (isset($_REQUEST['FANNIE_TRANS_DB']))
	$FANNIE_TRANS_DB = $_REQUEST['FANNIE_TRANS_DB'];
confset('FANNIE_TRANS_DB',"'$FANNIE_TRANS_DB'");
echo "<input type=text name=FANNIE_TRANS_DB value=\"$FANNIE_TRANS_DB\" />";
?>
<br />Testing Operational DB connection:
<?php
$sql = db_test_connect($FANNIE_SERVER,$FANNIE_SERVER_DBMS,
		$FANNIE_OP_DB,$FANNIE_SERVER_USER,
		$FANNIE_SERVER_PW);
$createdOps = False;
if ($sql === False)
	echo "<span style=\"color:red;\">Failed</span>";
else {
	echo "<span style=\"color:green;\">Succeeded</span>";
	create_op_dbs($sql);
	$createdOps = True;

	// create auth tables later than the original
	// setting in case db settings were wrong
	if (isset($FANNIE_AUTH_ENABLED) && $FANNIE_AUTH_ENABLED === True){ 
		include($FILEPATH.'auth/utilities.php');
		table_check();
	}
}
?>
<br />Testing Transaction DB connection:
<?php
$sql = db_test_connect($FANNIE_SERVER,$FANNIE_SERVER_DBMS,
		$FANNIE_TRANS_DB,$FANNIE_SERVER_USER,
		$FANNIE_SERVER_PW);
$createdTrans = False;
if ($sql === False)
	echo "<span style=\"color:red;\">Failed</span>";
else {
	echo "<span style=\"color:green;\">Succeeded</span>";
	create_trans_dbs($sql);
	create_dlogs($sql);
	$createdTrans = True;
}
if ($createdOps && $createdTrans){
	create_delayed_dbs();
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
<br />Archive Method:
<select name=FANNIE_ARCHIVE_METHOD>
<?php
if (!isset($FANNIE_ARCHIVE_METHOD)) $FANNIE_ARCHIVE_METHOD = 'tables';
if (isset($_REQUEST['FANNIE_ARCHIVE_METHOD'])) $FANNIE_ARCHIVE_METHOD = $_REQUEST['FANNIE_ARCHIVE_METHOD'];
if ($FANNIE_ARCHIVE_METHOD == 'tables'){
	confset('FANNIE_ARCHIVE_METHOD',"'tables'");
	echo "<option selected>tables</option><option>partitions</option>";
}
else{
	confset('FANNIE_ARCHIVE_METHOD',"'partitions'");
	echo "<option>tables</option><option selected>partitions</option>";
}
echo "</select><br />";
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
if ($FANNIE_ARCHIVE_DBMS == 'MYSQL'){
	echo "<option value=MYSQL selected>MySQL</option><option value=MSSQL>SQL Server</option><option value=MYSQLI>MySQLi</option>";
}
else if ($FANNIE_ARCHIVE_DBMS == 'MSSQL'){
	echo "<option value=MYSQL>MySQL</option><option selected value=MSSQL>SQL Server</option><option value=MYSQLI>MySQLi</option>";
}
else {
	echo "<option value=MYSQL>MySQL</option><option value=MSSQL>SQL Server</option><option selected value=MYSQLI>MySQLi</option>";
}
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
	$sql = db_test_connect($FANNIE_SERVER,$FANNIE_SERVER_DBMS,
			$FANNIE_ARCHIVE_DB,$FANNIE_SERVER_USER,
			$FANNIE_SERVER_PW);
	if ($sql === False)
		echo "<span style=\"color:red;\">Failed</span>";
	else {
		echo "<span style=\"color:green;\">Succeeded</span>";
		create_archive_dbs($sql);
	}
}
?>
<hr />
<b>Database Backups</b><br />
Backup Directory
<?php
if(!isset($FANNIE_BACKUP_PATH)) $FANNIE_BACKUP_PATH = '/tmp/';
if (isset($_REQUEST['FANNIE_BACKUP_PATH'])){
	$FANNIE_BACKUP_PATH = $_REQUEST['FANNIE_BACKUP_PATH'];
}
confset('FANNIE_BACKUP_PATH',"'$FANNIE_BACKUP_PATH'");
echo "<input type=text name=FANNIE_BACKUP_PATH value=\"$FANNIE_BACKUP_PATH\" /><br />";
if (is_writable($FANNIE_BACKUP_PATH)){
	echo "<span style=\"color:green;\">Backup directory is writeable</span>";
}
else {
	echo "<span style=\"color:red;\"><b>Error</b>: backup directory is not writeable</span>";
}
?>
<br />
Path to mysqldump
<?php
if(!isset($FANNIE_BACKUP_BIN)) $FANNIE_BACKUP_BIN = '/usr/bin/';
if (isset($_REQUEST['FANNIE_BACKUP_BIN'])){
	$FANNIE_BACKUP_BIN = $_REQUEST['FANNIE_BACKUP_BIN'];
}
confset('FANNIE_BACKUP_BIN',"'$FANNIE_BACKUP_BIN'");
echo "<input type=text name=FANNIE_BACKUP_BIN value=\"$FANNIE_BACKUP_BIN\" /><br />";
if (is_executable(realpath($FANNIE_BACKUP_BIN."/mysqldump"))){
	echo "<span style=\"color:green;\">Found mysqldump program</span>";
}
else {
	echo "<span style=\"color:red;\"><b>Error</b>: mysqldump not found</span>";
}
?>
<br />
Number of backups
<?php
if(!isset($FANNIE_BACKUP_NUM)) $FANNIE_BACKUP_NUM = 1;
if (isset($_REQUEST['FANNIE_BACKUP_NUM'])){
	$FANNIE_BACKUP_NUM = $_REQUEST['FANNIE_BACKUP_NUM'];
}
confset('FANNIE_BACKUP_NUM',"'$FANNIE_BACKUP_NUM'");
echo "<input type=text name=FANNIE_BACKUP_NUM value=\"$FANNIE_BACKUP_NUM\" /><br />";
?>
<br />
Compress backups
<select name=FANNIE_BACKUP_GZIP>
<?php
if (!isset($FANNIE_BACKUP_GZIP)) $FANNIE_BACKUP_GZIP = False;
if (isset($_REQUEST['FANNIE_BACKUP_GZIP'])) $FANNIE_BACKUP_GZIP = $_REQUEST['FANNIE_BACKUP_GZIP'];
if ($FANNIE_BACKUP_GZIP === True || $FANNIE_BACKUP_GZIP == 'Yes'){
	confset('FANNIE_BACKUP_GZIP','True');
	echo "<option selected>Yes</option><option>No</option>";
}
else{
	confset('FANNIE_BACKUP_GZIP','False');
	echo "<option>Yes</option><option selected>No</option>";
}
?>
</select>
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
	foreach ($supportedTypes as $val=>$label){
		printf('<option value="%s" %s>%s</option>',
			$val,
			($FANNIE_LANES[$i]['type'] == $val)?'selected':'',
			$label);
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
<a href="lane_config/">Edit Global Lane Configuration</a>
<hr />
<b>Color-Highlighted Logs</b>:
<?php
if (!isset($FANNIE_PRETTY_LOGS)) $FANNIE_PRETTY_LOGS = 0;
if (isset($_REQUEST['FANNIE_PRETTY_LOGS'])) $FANNIE_PRETTY_LOGS = $_REQUEST['FANNIE_PRETTY_LOGS'];
confset('FANNIE_PRETTY_LOGS',"$FANNIE_PRETTY_LOGS");
echo '<select name="FANNIE_PRETTY_LOGS">';
if ($FANNIE_PRETTY_LOGS == 0){
	echo '<option value="1">Yes</option>';
	echo '<option value="0" selected>No</option>';
}
else {
	echo '<option value="1" selected>Yes</option>';
	echo '<option value="0">No</option>';
}
echo '</select>';
?>
<br />
<b>Log Rotation Count</b>:
<?php
if (!isset($FANNIE_LOG_COUNT)) $FANNIE_LOG_COUNT = 5;
if (isset($_REQUEST['FANNIE_LOG_COUNT'])) $FANNIE_LOG_COUNT = $_REQUEST['FANNIE_LOG_COUNT'];
confset('FANNIE_LOG_COUNT',"$FANNIE_LOG_COUNT");
echo "<input type=text name=FANNIE_LOG_COUNT value=\"$FANNIE_LOG_COUNT\" size=3 />";
echo "<br />";
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
<input type=submit value="Re-run" />
</form>

<?php

function create_op_dbs($con){
	global $FANNIE_OP_DB, $FANNIE_SERVER_DBMS;

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'employees','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'departments','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'deptMargin','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'deptSalesCodes','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'dateRestrict','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'subdepts','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'superdepts','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'superDeptNames','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'superMinIdView','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'MasterSuperDepts','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'products','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'productBackup','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'likeCodes','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'upcLike','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'taxrates','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'prodExtra','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'prodFlags','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'prodPhysicalLocation','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'productUser','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'prodUpdate','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'prodUpdateArchive','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'prodPriceHistory','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'prodDepartmentHistory','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'batches','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'batchList','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'batchType','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'batchowner','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'batchCutPaste','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'batchBarcodes','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'batchMergeTable','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'batchMergeProd','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'likeCodeView','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'batchMergeLC','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'batchPriority30','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'batchPriority20','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'batchPriority10','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'batchPriority0','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'batchPriority','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'unfi','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'unfi_order','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'unfi_diff','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'unfi_all','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'unfiCategories','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'shelftags','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'custdata','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'custdataBackup','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'custAvailablePrefs','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'custPreferences','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'meminfo','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'memtype','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'memdefaults','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'memberCards','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'memDates','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'memContact','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'memContactPrefs','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'tenders','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'customReceipt','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'houseCoupons','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'houseVirtualCoupons','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'houseCouponItems','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'disableCoupon','op');
	
	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'productMargin','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'vendors','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'vendorItems','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'vendorSKUtoPLU','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'vendorSRPs','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'vendorDepartments','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'vendorLoadScripts','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'scaleItems','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'PurchaseOrder','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'PurchaseOrderItems','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'emailLog','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'UpdateLog','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'memberNotes','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'suspensions','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'reasoncodes','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'suspension_history','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'cronBackup','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'customReports','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'AdSaleDates','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'custReceiptMessage','op');
}

function create_trans_dbs($con){
	global $FANNIE_TRANS_DB, $FANNIE_SERVER_DBMS, $FANNIE_OP_DB;


	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'alog','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'efsnetRequest','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'efsnetResponse','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'efsnetRequestMod','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'ccReceiptView','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'valutecRequest','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'valutecResponse','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'valutecRequestMod','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'voidTransHistory','trans');

	/* invoice stuff is very beta; not documented yet */
	$invCur = "CREATE TABLE InvDelivery (
		inv_date datetime,
		upc varchar(13),
		vendor_id int,
		quantity double,
		price float,
		INDEX (upc))";
	if (!$con->table_exists('InvDelivery',$FANNIE_TRANS_DB)){
		$con->query($invCur,$FANNIE_TRANS_DB);
	}

	$invCur = "CREATE TABLE InvDeliveryLM (
		inv_date datetime,
		upc varchar(13),
		vendor_id int,
		quantity double,
		price float)";
	if (!$con->table_exists('InvDeliveryLM',$FANNIE_TRANS_DB)){
		$con->query($invCur,$FANNIE_TRANS_DB);
	}

	$invArc = "CREATE TABLE InvDeliveryArchive (
		inv_date datetime,
		upc varchar(13),
		vendor_id int,
		quantity double,
		price float,
		INDEX(upc))";
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


	
	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'ar_history_backup','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'AR_EOM_Summary','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'lane_config','trans');

}

function create_dlogs($con){
	global $FANNIE_TRANS_DB, $FANNIE_SERVER_DBMS, $FANNIE_AR_DEPARTMENTS, $FANNIE_EQUITY_DEPARTMENTS, $FANNIE_OP_DB;

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'dtransactions','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'transarchive','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'suspended','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'SpecialOrderID','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'SpecialOrderDeptMap','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'SpecialOrderContact','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'SpecialOrderNotes','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'SpecialOrderHistory','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'SpecialOrderStatus','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'PendingSpecialOrder','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'CompleteSpecialOrder','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'dlog','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'dlog_90_view','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'dlog_15','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'suspendedtoday','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'TenderTapeGeneric','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'rp_dt_receipt_90','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'rp_receipt_header_90','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'ar_live_balance','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'ar_history','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'ar_history_sum','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'stockpurchases','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'stockSum_purch','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'stockSumToday','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'newBalanceStockToday_test','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'memChargeBalance','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'unpaid_ar_balances','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'unpaid_ar_today','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'dheader','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'dddItems','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'CashPerformDay','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'CashPerformDay_cache','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'houseCouponThisMonth','trans');
}

function create_delayed_dbs(){
	global $FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_SERVER_USER,$FANNIE_SERVER_PW,$FANNIE_OP_DB,$FANNIE_TRANS_DB;

	$con = db_test_connect($FANNIE_SERVER,$FANNIE_SERVER_DBMS,
		$FANNIE_OP_DB,$FANNIE_SERVER_USER,
		$FANNIE_SERVER_PW);

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'expingMems','op');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
			'expingMems_thisMonth','op');

	$con = db_test_connect($FANNIE_SERVER,$FANNIE_SERVER_DBMS,
		$FANNIE_TRANS_DB,$FANNIE_SERVER_USER,
		$FANNIE_SERVER_PW);

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'ar_history_today','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'ar_history_today_sum','trans');

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
			'AR_statementHistory','trans');

	$invSalesView = "CREATE VIEW InvSales AS
		select datetime as inv_date,upc,quantity,total as price
		FROM transarchive WHERE ".$con->monthdiff($con->now(),'datetime')." <= 1
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
		quantity double,
		price float,
		INDEX(upc))";
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
		diff double,
		INDEX(upc))";
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

	$opstr = $FANNIE_OP_DB;
	if ($FANNIE_SERVER_DBMS=="mssql") $opstr .= ".dbo";
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
		INNER JOIN $opstr.vendorItems AS v 
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

function create_archive_dbs($con) {
	global $FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,$FANNIE_ARCHIVE_DB,$FANNIE_ARCHIVE_METHOD;

	$dstr = date("Ym");
	$archive = "transArchive".$dstr;
	$dbconn = ".";
	if ($FANNIE_SERVER_DBMS == "MSSQL")
		$dbconn = ".dbo.";

	if ($FANNIE_ARCHIVE_METHOD == "partitions")
		$archive = "bigArchive";

	$query = "CREATE TABLE $archive LIKE 
		{$FANNIE_TRANS_DB}{$dbconn}dtransactions";
	if ($FANNIE_SERVER_DBMS == "MSSQL"){
		$query = "SELECT TOP 1 * INTO $archive FROM 
			{$FANNIE_TRANS_DB}{$dbconn}dtransactions";
	}
	if (!$con->table_exists($archive,$FANNIE_ARCHIVE_DB)){
		$con->query($query,$FANNIE_ARCHIVE_DB);
		// create the first partition if needed
		if ($FANNIE_ARCHIVE_METHOD == "partitions"){
			$p = "p".date("Ym");
			$limit = date("Y-m-d",mktime(0,0,0,date("n")+1,1,date("Y")));
			$partQ = sprintf("ALTER TABLE `bigArchive` 
				PARTITION BY RANGE(TO_DAYS(`datetime`)) 
				(PARTITION %s 
					VALUES LESS THAN (TO_DAYS('%s'))
				)",$p,$limit);
			$con->query($partQ);
		}
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

	$dlog_view = ($FANNIE_ARCHIVE_METHOD != "partitions") ? "dlog".$dstr : "dlogBig";
	if (!$con->table_exists($dlog_view,$FANNIE_ARCHIVE_DB)){
		$con->query("CREATE VIEW $dlog_view AS $dlogView",
			$FANNIE_ARCHIVE_DB);
	}

	$rp_dt_view = ($FANNIE_ARCHIVE_METHOD != "partitions") ? "rp_dt_receipt_".$dstr : "rp_dt_receipt_big";
	$rp1Q = "CREATE  view $rp_dt_view as 
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

		from $archive
		where voided <> 5 and UPC <> 'TAX' and UPC <> 'DISCOUNT'";
	if ($FANNIE_SERVER_DBMS == 'MSSQL'){
		$rp1Q = "CREATE  view $rp_dt_view as 
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

			from $archive
			where voided <> 5 and UPC <> 'TAX' and UPC <> 'DISCOUNT'";
	}
	if (!$con->table_exists($rp_dt_view,$FANNIE_ARCHIVE_DB)){
		$con->query($rp1Q,$FANNIE_ARCHIVE_DB);
	}

	$rp_view = ($FANNIE_ARCHIVE_METHOD != "partitions") ? "rp_receipt_header_".$dstr : "rp_receipt_header_big";
	$rp2Q = "create  view $rp_view as
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

		from $archive
		group by register_no, emp_no, trans_no, card_no, datetime";
	if ($FANNIE_SERVER_DBMS == 'MSSQL'){
		$rp2Q = "create  view $rp_view as
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

			from $archive
			group by register_no, emp_no, trans_no, card_no, datetime";
	}
	if (!$con->table_exists($rp_view,$FANNIE_ARCHIVE_DB)){
		$con->query($rp2Q,$FANNIE_ARCHIVE_DB);
	}

	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_ARCHIVE_DB,
			'sumUpcSalesByDay','arch');
	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_ARCHIVE_DB,
			'sumRingSalesByDay','arch');
	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_ARCHIVE_DB,
			'vRingSalesToday','arch');
	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_ARCHIVE_DB,
			'sumDeptSalesByDay','arch');
	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_ARCHIVE_DB,
			'vDeptSalesToday','arch');
	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_ARCHIVE_DB,
			'sumFlaggedSalesByDay','arch');
	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_ARCHIVE_DB,
			'sumMemSalesByDay','arch');
	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_ARCHIVE_DB,
			'sumMemTypeSalesByDay','arch');
	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_ARCHIVE_DB,
			'sumTendersByDay','arch');
	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_ARCHIVE_DB,
			'sumDiscountsByDay','arch');
	create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_ARCHIVE_DB,
			'reportDataCache','arch');
}

?>
