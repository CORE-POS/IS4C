<?php
/*******************************************************************************

    Copyright 2001, 2004, 2007 Wedge Community Co-op

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

include_once("../define.conf");

function reloadtable($table) {

$aLane = array(

	LANE01,
	LANE02,
	LANE03,
	LANE04,
	LANE05,
	LANE06,
	LANE07,
	LANE08,
	LANE09,
	LANE10,
	LANE11,
	LANE12


);

$_SESSION["mUser"] = "root";
$_SESSION["laneUser"] = "root";

$server = "localhost";
$opDB = "is4c_op";
$serveruser = $_SESSION["mUser"];
$serverpass = $_SESSION["mPass"];

$laneuser = $_SESSION["laneUser"];
$lanepass = $_SESSION["lanePass"];

$file = "/pos/is4c/download/".$table.".out";
$dump = "select * into outfile '".$file."' from ".$table;
$load = "load data infile '".$file."' into table is4c_op.".$table;

$is4c_op_truncate = "truncate is4c_op.".$table;
$opdata_truncate = "truncate opdata.".$table;
$opdata_insert = "insert into opdata. ".$table." select * from is4c_op.".$table;

echo "<font color='#004080' face=helvetica><b>".$table."</b></font>";
echo "<p>";


$continue = 0;

/* establish connection to server */

if ($s_conn = mysql_connect($server, $serveruser, $serverpass)) {
	$continue = 1;
}
else {
	echo "<p><font color='#800000' face=helvetica size=-1>Failed to connect to server</font>";
}

if ($continue == 1) {

	$continue = 0;
	if (mysql_select_db("is4c_op", $s_conn)) $continue = 1; 
	else echo "<p><font color='#800000' face=helvetica size=-1>Failed to connect to server database</font>";
}

if ($continue == 1) {

	$continue = 0;
	$result = mysql_query("select count(*) from ".$table, $s_conn);
	$row = mysql_fetch_array($result);
	$server_num_rows = $row[0];
	// echo "<p><font color='#004080' face=helvetica size=-1>There are ".$server_num_rows." record(s) on server database</font>";
	if ($server_num_rows > 10) $continue = 1;
	else echo "<p><font color='#800000' face=helvetica size=-1>There are only ".$server_num_rows." records on the server.<br>No way</font>";

}
/*
if ($continue == 1) {

	$continue = 0;
	if (file_exists($file)) exec("rm ".$file);
	if (mysql_query($dump, $s_conn)) $continue = 1;
	else echo "<p><font color='#800000' face=helvetica size=-1>Failed to download new data from server</font>";

}


if ($continue == 1) {

	$continue = 0;
	if (file_exists($file)) $continue = 1;
	else echo "<p><font color='#800000' face=helvetica size=-1>Failed to retrieve new data from server</font>";

} 
*/
// synchronize lanes

if ($continue == 1) {

//	$continue = 0;
//	echo "<p><font color='#004080' face=helvetica size=-1>".$server_num_rows." records downloaded from server</font>";
//	echo "<p>";
	
	$i = 1;
	foreach ($aLane as $lane) {

		if ($lane && strlen($lane) > 0) {
		$lane_num = "lane ".$i;
		$i++;
		$lane_continue = 0;
		if ($lane_conn = mysql_connect($lane, $laneuser, $lanepass)) $lane_continue = 1;
		else echo "<br><font color='#800000' face=helvetica size=-1>Unable to connect to ".$lane_num."</font>";

		if ($lane_continue == 1) {
			$lane_continue = 0;
			if (mysql_select_db("opdata", $lane_conn)) $lane_continue = 1;
			else echo "<br><font color='#800000' face=helvetica size=-1>Unable to select database on ".$lane_num."</font>";
		}

		if ($lane_continue == 1) {
			$lane_continue = 0;
			mysql_query($is4c_op_truncate, $lane_conn);

			if (synctable($table,$serveruser,$opDB,$lane) == 1) {

				$result = mysql_query("select count(*) from is4c_op.".$table, $lane_conn);
				$row = mysql_fetch_array($result);
				$lane_num_rows = $row[0];
				if ($lane_num_rows == $server_num_rows) $lane_continue = 1;
				else echo "<br><font color='#800000' face=helvetica size=-1>".$lane_num.": Number of records do not match. Synchronization refused</font>";

			}
			else echo "<br><font color='#800000' face=helvetica size=-1>Unable to load new data onto ".$lane_num."</font>";

		}

		if ($lane_continue == 1) {
			$lane_continue = 0;
			if (mysql_query($opdata_truncate, $lane_conn)) {
				if (mysql_query($opdata_insert, $lane_conn)) {
					$qresult = mysql_query("select * from ".$table, $lane_conn);
					$lane_num_rows = mysql_num_rows($qresult);
					echo "<br><font color='#004080' face=helvetica size=-1>".$lane_num.": ".$lane_num_rows." records successfully synchronized";
				}
				else {
					echo "<br><font color='#800000' face=helvetica size=-1>Unable to synchronize ".$lane_num."</font>";
				}
			}
		}

	}
	}

}
$time = strftime("%m-%d-%y %I:%M %p", time());
echo "<p> <p><font color='#004080' face=helvetica size=-1>last run: ".$time."</font>";



}

function synctable($table,$serveruser,$opDB,$lane) {
//	openlog("is4c_connect", LOG_PID | LOG_PERROR, LOG_LOCAL0);

	if ($_SESSION["lanePass"] == "") {
		$lanepass = "";
	}
	else {
		$lanepass = "-p".$_SESSION["lanePass"];
	}

	if ($_SESSION["mPass"] == "") {
		$serverpass = "";
	}
	else {
		$serverpass = "-p".$_SESSION["mPass"];
	}

	$sync =  "mysqldump -u root ".$serverpass." -t ".$opDB." ".$table
		  ." | mysql -u root ".$lanepass." -h ".$lane." ".$opDB." 2>&1";
//	echo $sync;
	$error = 0;
	$output = "";
	exec($sync, $aResult);
	foreach ($aResult as $errormsg) {
		if ($errormsg && strlen($errormsg) > 0) {
			$output = $output."\n".$errormsg;
			$error = 1;
		}
	}

	if ($error == 1) {

              	syslog(LOG_WARNING, "synctable($table) failed; rc: errormsg: '$output'");
              	return 0;

	} else {
		return 1;
	}

}

/*

function synctable_old($table,$serveruser,$opDB,$lane) {
	openlog("is4c_connect", LOG_PID | LOG_PERROR, LOG_LOCAL0);
	exec('mysqldump -u '.$serveruser.' -t '.$opDB.' '.$table.' | mysql -h '.$lane.' '.$opDB.".$table." 2>&1", $result, $return_code);
	foreach ($result as $v) {$output .= "$v\n";}
	if ($return_code == 0) {

		return 1;
	} else {
		syslog(LOG_WARNING, "synctable($table) failed; rc: '$return_code', output: '$output'");
		return 0;
	}
}
*/
?>
