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
<a href="sample_data/extra_data.php">Sample Data</a>
<form action=food_net.php method=post>
<h1>Fannie install checks</h1>
<?php
// path detection
$FILEPATH = rtrim($_SERVER['SCRIPT_FILENAME'],'food_net.php');
$URL = rtrim($_SERVER['SCRIPT_NAME'],'food_net.php');
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
}
?>
<hr />
<b>Foodnet DB Server</b><br />
Server Database Host
<?php
if(!isset($FANNIE_FN_SERVER)) $FANNIE_FN_SERVER = '127.0.0.1';
if (isset($_REQUEST['FANNIE_FN_SERVER'])){
    $FANNIE_FN_SERVER = $_REQUEST['FANNIE_FN_SERVER'];
}
confset('FANNIE_FN_SERVER',"'$FANNIE_FN_SERVER'");
echo "<input type=text name=FANNIE_FN_SERVER value=\"$FANNIE_FN_SERVER\" />";
?>
<br />Server Database Type
<select name=FANNIE_FN_DBMS>
<?php
if(!isset($FANNIE_FN_DBMS)) $FANNIE_FN_DBMS = 'MYSQL';
if (isset($_REQUEST['FANNIE_FN_DBMS'])){
    $FANNIE_FN_DBMS = $_REQUEST['FANNIE_FN_DBMS'];
}
confset('FANNIE_FN_DBMS',"'$FANNIE_FN_DBMS'");
if ($FANNIE_FN_DBMS == 'MYSQL'){
    echo "<option value=MYSQL selected>MySQL</option>";
    echo "<option value=MSSQL>SQL Server</option>";
    echo "<option value=MYSQLI>MySQLi</option>";
}
else if ($FANNIE_FN_DBMS == 'MSSQL'){
    echo "<option value=MYSQL>MySQL</option>";
    echo "<option value=MSSQL selected>SQL Server</option>";
    echo "<option value=MYSQLI>MySQLi</option>";
}
else {
    echo "<option value=MYSQL>MySQL</option>";
    echo "<option value=MSSQL>SQL Server</option>";
    echo "<option value=MYSQLI selected>MySQLi</option>";
}
?>
</select>
<br />Server Database Username
<?php
if (!isset($FANNIE_FN_USER)) $FANNIE_FN_USER = 'root';
if (isset($_REQUEST['FANNIE_FN_USER']))
    $FANNIE_FN_USER = $_REQUEST['FANNIE_FN_USER'];
confset('FANNIE_FN_USER',"'$FANNIE_FN_USER'");
echo "<input type=text name=FANNIE_FN_USER value=\"$FANNIE_FN_USER\" />";
?>
<br />Server Database Password
<?php
if (!isset($FANNIE_FN_PW)) $FANNIE_FN_PW = '';
if (isset($_REQUEST['FANNIE_FN_PW']))
    $FANNIE_FN_PW = $_REQUEST['FANNIE_FN_PW'];
confset('FANNIE_FN_PW',"'$FANNIE_FN_PW'");
echo "<input type=password name=FANNIE_FN_PW value=\"$FANNIE_FN_PW\" />";
?>
<br />Server DB name
<?php
if (!isset($FANNIE_FN_DB)) $FANNIE_FN_DB = 'pi_food_net';
if (isset($_REQUEST['FANNIE_FN_DB']))
    $FANNIE_FN_DB = $_REQUEST['FANNIE_FN_DB'];
confset('FANNIE_FN_DB',"'$FANNIE_FN_DB'");
echo "<input type=text name=FANNIE_FN_DB value=\"$FANNIE_FN_DB\" />";
?>
<br />Testing DB connection:
<?php
$sql = db_test_connect($FANNIE_FN_SERVER,$FANNIE_FN_DBMS,
        $FANNIE_FN_DB,$FANNIE_FN_USER,
        $FANNIE_FN_PW);
if ($sql === False)
    echo "<span style=\"color:red;\">Failed</span>";
else {
    echo "<span style=\"color:green;\">Succeeded</span>";
}
?>
<hr />
<input type=submit value="Re-run" />
</form>
