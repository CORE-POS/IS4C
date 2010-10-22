<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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
	con		=> SQLManager object
	dbms		=> type: mysql or mssql probably
	db_name		=> user-defined database name
	table_name	=> table/view to create
	stddb		=> standardized database name for
			   the sake of file paths
			   'op', 'trans', or 'arch'
*/
function create_if_needed($con,$dbms,$db_name,$table_name,$stddb){
	if ($con->table_exists($table_name,$db_name)) return;
	
	$fn = "sql/$stddb/$table_name.php";
	if (!file_exists($fn)){
		echo "<i>Error: no create file for $stddb.$table_name.
			File should be: $fn</i><br />";
		return;
	}

	include($fn);
	if (!isset($CREATE["$stddb.$table_name"])){
		echo "<i>Error: file $fn doesn't have a valid \$CREATE</i><br />";
		return;
	}

	$con->query($CREATE["$stddb.$table_name"],$db_name);
}

/* create another table with the same
	columns
*/
function duplicate_structure($con,$dbms,$db_name,$table1,$table2){
	if ($dbms == "MYSQL"){
		$con->query("CREATE TABLE `$table2` LIKE `$table1`",$db_name);
	}
	elseif ($dbms == "MSSQL"){
		$con->query("SELECT * INTO [$table2] FROM [$table1] WHERE 1=0",$db_name);
	}
}

?>
