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

/*
 * NOTE: SQLManager's transfer method is not the fastest way of pulling
 * this off. I'm using it so I can mix & match MySQL and SQL Server
 * without errors.
 *
 * Rewriting the loop to use mysql commandline programs would be good
 * if everything's on the same dbms. Using the global settings in
 * $FANNIE_LANES is the important part. Rough sketch of this
 * is in comments below
 *
 */
include('../config.php');

$table = (isset($_REQUEST['tablename']))?$_REQUEST['tablename']:'';
if (isset($_REQUEST['othertable']) && !empty($_REQUEST['othertable'])) $table = $_REQUEST['othertable'];

$page_title = "Fannie : Sync Data";
$header = "Syncing data";
include($FANNIE_ROOT.'src/header.html');

if (empty($table)){
	echo "<i>Error: no table was specified</i>";
}
elseif (ereg("[^A-Za-z0-9_]",$table)){
	echo "<i>Error: \"$table\" contains illegal characters</i>";
}
else {
	include($FANNIE_ROOT.'src/SQLManager.php');
	$dbc = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,
		$FANNIE_OP_DB,$FANNIE_SERVER_USER,$FANNIE_SERVER_PW);

	echo "<p>Syncing table $table";
	echo "<ul>";
	flush();

	if (file_exists("special/$table.php")){
		include("special/$table.php");
	}
	else {
		for($i=0; $i<$FANNIE_NUM_LANES; $i++){
			$lane = $FANNIE_LANES[$i];
			$dbc->add_connection($lane['host'],$lane['type'],
				$lane['op'],$lane['user'],$lane['pw']);

			if ($dbc->connections[$lane['op']]){
				$dbc->query("TRUNCATE TABLE $table",$lane['op']);
				$success = $dbc->transfer($FANNIE_OP_DB,
					       "SELECT * FROM $table",
					       $lane['op'],
					       "INSERT INTO $table");
				$dbc->close($lane['op']);
				if ($success){
					echo "<li>Lane ".($i+1)." completed successfully</li>";
				}
				else {
					echo "<li>Lane ".($i+1)." completed but with some errors</li>";
				}
			}
			else {
				echo "<li>Couldn't connect to lane ".($i+1)."</li>";
			}
		}
	}

	echo "</ul>";
}

include($FANNIE_ROOT.'src/footer.html');

?>
