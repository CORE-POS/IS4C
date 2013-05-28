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
    in the file license.txt along with IT CORE; if not, write to the Free Software
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
	$ret = array('db'=>$db_name,'struct'=>$table_name,'error'=>0,'error_msg'=>'');
	if ($con->table_exists($table_name,$db_name)) return $ret;
	
	$fn = dirname(__FILE__)."/sql/$stddb/$table_name.php";
	if (!file_exists($fn)){
		$ret['error_msg'] = "<i>Error: no create file for $stddb.$table_name.
			File should be: $fn</i><br />";
		$ret['error'] = 1;
		return $ret;
	}

	include($fn);
	if (!isset($CREATE["$stddb.$table_name"])){
		$ret['error_msg'] = "<i>Error: file $fn doesn't have a valid \$CREATE</i><br />";
		$ret['error'] = 2;
		return $ret;
	}

	$prep = $con->prepare_statement($CREATE["$stddb.$table_name"],$db_name);
	$result = $con->exec_statement($prep,array(),$db_name);
	if ($result === False){
		$ret['error_msg'] = $con->error($db_name);
		$ret['error'] = 3;
	}
	return $ret;
}

/* query to create another table with the same
	columns
*/
function duplicate_structure($dbms,$table1,$table2){
	if (strstr($dbms,"MYSQL")){
		return "CREATE TABLE `$table2` LIKE `$table1`";
	}
	elseif ($dbms == "MSSQL"){
		return "SELECT * INTO [$table2] FROM [$table1] WHERE 1=0";
	}
}

function ar_departments(){
	global $FANNIE_AR_DEPARTMENTS;
	$ret = preg_match_all("/[0-9]+/",$FANNIE_AR_DEPARTMENTS,$depts);
	if ($ret != 0){
		/* AR departments exist */
		$depts = array_pop($depts);
		$dlist = "(";
		foreach ($depts as $d){
			$dlist .= $d.",";	
		}
		$dlist = substr($dlist,0,strlen($dlist)-1).")";
		return $dlist;
	}
	return "";
}

function equity_departments(){
	global $FANNIE_EQUITY_DEPARTMENTS;
	$ret = preg_match_all("/[0-9]+/",$FANNIE_EQUITY_DEPARTMENTS,$depts);
	if ($ret != 0){
		/* equity departments exist */
		$depts = array_pop($depts);
		$dlist = "(";
		foreach ($depts as $d){
			$dlist .= $d.",";	
		}
		$dlist = substr($dlist,0,strlen($dlist)-1).")";
		return $dlist;
	}
	return "";
}

function qualified_names(){
	global $FANNIE_SERVER_DBMS,$FANNIE_OP_DB,$FANNIE_TRANS_DB;

	$ret = array("op"=>$FANNIE_OP_DB,"trans"=>$FANNIE_TRANS_DB);
	if ($FANNIE_SERVER_DBMS == "MSSQL"){
		$ret["op"] .= ".dbo";
		$ret["trans"] .= ".dbo";
	}
	return $ret;
}

function loaddata($sql, $table){
	global $FANNIE_ROOT;
	if (file_exists("{$FANNIE_ROOT}install/sample_data/$table.sql")){
		$fp = fopen("{$FANNIE_ROOT}install/sample_data/$table.sql","r");
		while($line = fgets($fp)){
			$prep = $sql->prepare_statement("INSERT INTO $table VALUES $line");
			$sql->exec_statement($prep);
		}
		fclose($fp);
	}
	else if (file_exists("{$FANNIE_ROOT}install/sample_data/$table.csv")){
		$prep = $sql->prepare_statement("LOAD DATA LOCAL INFILE
			'{$FANNIE_ROOT}install/sample_data/$table.csv'
			INTO TABLE $table
			FIELDS TERMINATED BY ','
			ESCAPED BY '\\\\'
			OPTIONALLY ENCLOSED BY '\"'
			LINES TERMINATED BY '\\r\\n'");
		$sql->exec_statement($prep);
	}
}

?>
