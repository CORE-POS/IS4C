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

/*
 * This works the same way as server => lane syncing, 
 * just for master => store
 *
 */
include('../../config.php');

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
	/*
		Connecting to the transactional database here is
		intentional. SQLManager identifies connections by
		database name; if both stores have the same name
		for their operational database (which is likely)
		connection to op on both will cause problems.
	*/
	$dbc = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,
		$FANNIE_TRANS_DB,$FANNIE_SERVER_USER,$FANNIE_SERVER_PW);

	echo "<p>Syncing table $table";
	echo "<ul>";
	flush();

	if (file_exists("special/$table.php")){
		include("special/$table.php");
	}
	else {
		echo "Whoa! bail-out! multi-store not safe right now!";exit;
		if (isset($_REQUEST['storeNum']) && $_REQUEST['storeNum']==="") unset($_REQUEST['storeNum']);

		$start = isset($_REQUEST['storeNum']) ? $_REQUEST['storeNum'] : 0;
		$end = isset($_REQUEST['storeNum']) ? $_REQUEST['storeNum']+1 : $FANNIE_NUM_STORES;

		$selectCols = "";
		$cols = $dbc->table_definition($table,$FANNIE_TRANS_DB);
		foreach($cols as $name=>$type){ 
			if ($name != "id") // ignore autoinc columns
				$selectCols .= $name.",";
		}
		$selectCols = rtrim($selectCols,",");

		for($i=$start; $i<$FANNIE_NUM_STORES; $i++){
			$store = $FANNIE_STORES[$i];
			$dbc->add_connection($store['host'],$store['type'],
				$store['op'],$store['user'],$store['pw']);

			if ($dbc->connections[$store['op']]){
				$insCols = "(";
				$cols = $dbc->table_definition($table,$store['op']);
				foreach($cols as $name=>$type){ 
					if ($name != "id") // ignore autoinc columns
						$insCols .= $name.",";
				}
				$insCols = rtrim($insCols,",").")";

				$dbc->query("TRUNCATE TABLE $table",$store['op']);

				$fqn = ($FANNIE_SERVER_DBMS == "MSSQL") ? $FANNIE_TRANS_DB.".dbo.".$table : $FANNIE_TRANS_DB.".".$table;
				$select = "SELECT $selectCols FROM $fqn";
				$ins = "INSERT INTO $table $insCols";
				//echo $select."<br />";
				//echo $ins."<br />";
				$success = $dbc->transfer($FANNIE_TRANS_DB,
					       $select,
					       $store['op'],
					       $ins);
				$dbc->close($store['op']);

				if ($success){
					echo "<li>Store ".($i+1)." completed successfully</li>";
				}
				else {
					echo "<li>Store ".($i+1)." completed but with some errors</li>";
				}
			}
			else {
				echo "<li>Couldn't connect to store ".($i+1)."</li>";
			}
		}
	}

	echo "</ul>";
}

echo "<a href=\"index.php\">Store Sync Menu</a>";

include($FANNIE_ROOT.'src/footer.html');

?>
