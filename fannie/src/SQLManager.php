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

/**************************************************
CLASS INTERFACE

Methods:
SQLManager(server, type, database, username, password[default: ''], persistent[default: False])
	Constructor. Creates the object and adds an initial connection to use as the
	default. Future references to this connection can be made using the $database string.
	Type should be one of the static database types, e.g. TYPE_MYSQL

add_connection(server, type, database, username, password[d: ''], persistent[d: False])
	Same as above, but this is not the default connection.

select_db(database_name, connection_identifier)
	Selects the given database, using the default connection if no identifier is provided.

query(query_string, connection_identifier)
	Issues the query and returns the result, using the default connection is no identifier 
	is provided.

fetch_array(result_object, connection_identifer)
	Returns the row array, using the default connection if no identifier is provided.

**************************************************/
$QUERY_LOG = $FANNIE_ROOT."logs/queries.log";

if (!function_exists("ADONewConnection")) include($FANNIE_ROOT.'adodb5/adodb.inc.php');

class SQLManager {

	var $connections;
	var $default_db;

	function SQLManager($server,$type,$database,$username,$password='',$persistent=False){
		$this->connections=array();
		$this->default_db = $database;
		$this->add_connection($server,$type,$database,$username,$password,$persistent);
	}

	function add_connection($server,$type,$database,$username,$password='',$persistent=False){
		$conn = ADONewConnection($type);
		$conn->SetFetchMode(ADODB_FETCH_BOTH);
		$ok = False;
		if (isset($this->connections[$database])){
			$ok = $conn->NConnect($server,$username,$password,$database);
		}
		else {
			if ($persistent)
				$ok = $conn->PConnect($server,$username,$password,$database);
			else
				$ok = $conn->Connect($server,$username,$password,$database);
		}
		$this->connections[$database] = $conn;

		if (!$ok){
			$conn = ADONewConnection($type);
			$conn->SetFetchMode(ADODB_FETCH_BOTH);
			$ok = $conn->Connect($server,$username,$password);
			if ($ok){
				$conn->Execute("CREATE DATABASE $database");
				$conn->Execute("USE $database");
				$this->connections[$database] = $conn;
			}
			else {
				$this->connections[$database] = False;
				return False;
			}
		}
		return True;
	}

	function close($which_connection=''){
		if ($which_connection == '')
			$which_connection=$this->default_db;
		$con = $this->connections[$which_connection];
		unset($this->connections[$which_connection]);

		return $con->Close();
	}

	function query($query_text,$which_connection=''){
		global $QUERY_LOG;
		if ($which_connection == '')
			$which_connection=$this->default_db;
		$con = $this->connections[$which_connection];

		$ok = $con->Execute($query_text);
		if (!$ok && is_writable($QUERY_LOG)){
			$fp = fopen($QUERY_LOG,'a');
			fputs($fp,$_SERVER['PHP_SELF'].": ".date('r').': '.$query_text."\n");
			fclose($fp);
		}
		else if (!$ok){
			echo "Bad query: {$_SERVER['PHP_SELF']}: $query_text<br />";
			echo $this->error($which_connection)."<br />";
		}
		return $ok;
	}

	function escape($query_text,$which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		return $this->connections[$which_connection]->qstr($query_text);
	}
	
	function num_rows($result_object,$which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		return $result_object->RecordCount();
	}
	function data_seek($result_object,$rownum,$which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		return $result_object->Move((int)$rownum);
	}
	
	function num_fields($result_object,$which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		return $result_object->FieldCount();
	}

	function fetch_array($result_object,$which_connection=''){
		if (is_null($result_object)) return false;
		if ($result_object === false) return false;

		if ($which_connection == '')
			$which_connection = $this->default_db;
		$ret = $result_object->fields;
		if ($result_object)
			$result_object->MoveNext();
		return $ret;
	}

	function fetch_object($result_object,$which_connection=''){
		return $result_object->FetchNextObject(False);
	}
	
	/* compatibility */
	function fetch_row($result_object,$which_connection=''){
		return $this->fetch_array($result_object,$which_connection);
	}

	function now($which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		return $this->connections[$which_connection]->sysTimeStamp;
	}

	function datediff($date1,$date2,$which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		switch($this->connections[$which_connection]->databaseType){
		case 'mysql':
		case 'mysqli':
			return "datediff($date1,$date2)";
		case 'mssql':
			return "datediff(dd,$date2,$date1)";
		}
	}

	function monthdiff($date1,$date2,$which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		switch($this->connections[$which_connection]->databaseType){
		case 'mysql':
		case 'mysqli':
			return "period_diff(date_format($date1, '%Y%m'), date_format($date2, '%Y%m'))";
		case 'mssql':
			return "datediff(mm,$date2,$date1)";
		}	
	}

	function seconddiff($date1,$date2,$which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		switch($this->connections[$which_connection]->databaseType){
		case 'mysql':
		case 'mysqli':
			return "TIMESTAMPDIFF(SECOND,$date1,$date2)";
		case 'mssql':
			return "datediff(ss,$date2,$date1)";
		}	
	}

	// flip argument order by mysql vs mssql
	function convert($expr,$type,$which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		switch($this->connections[$which_connection]->databaseType){
		case 'mysql':
		case 'mysqli':
			return "CONVERT($expr,$type)";
		case 'mssql':
			return "CONVERT($type,$expr)";
		}
		return "";
	}

	// note: to swing variable number of args,
	// connection is manadatory. use empty string
	// for default connection
	function concat(){
		$args = func_get_args();
		$ret = "";
		$which_connection = $args[count($args)-1];
		if ($which_connection == '')
			$which_connection = $this->default_db;
		switch($this->connections[$which_connection]->databaseType){
		case 'mysql':
		case 'mysqli':
			$ret .= "CONCAT(";
			for($i=0;$i<count($args)-1;$i++)
				$ret .= $args[$i].",";	
			$ret = rtrim($ret,",").")";
			break;
		case 'mssql':
			for($i=0;$i<count($args)-1;$i++)
				$ret .= $args[$i]."+";	
			$ret = rtrim($ret,"+");
			break;
		}
		return $ret;
	}

	function weekdiff($date1,$date2,$which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		switch($this->connections[$which_connection]->databaseType){
		case 'mysql':
		case 'mysqli':
			return "week($date1) - week($date2)";
		case 'mssql':
			return "datediff(wk,$date2,$date1)";
		}	
	}

	function fetch_field($result_object,$index,$which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		return $result_object->FetchField($index);
	}

	/* copy a table from one database to another, not necessarily on
	   the same server or format
	
	   $source_db is the database name of the source
	   $select_query is the query that will get the data
	   $dest_db is the database name of the destination
	   $insert_query is the beginning of the query that will add the
		data to the destination (specify everything up to VALUES)
	*/
	function transfer($source_db,$select_query,$dest_db,$insert_query){
		$result = $this->query($select_query,$source_db);
		if (!$result) return False;

		$num_fields = $this->num_fields($result,$source_db);

		$unquoted = array("money"=>1,"real"=>1,"numeric"=>1,
			"float4"=>1,"float8"=>1,"bit"=>1);
		$strings = array("varchar"=>1,"nvarchar"=>1,"string"=>1,
			"char"=>1);
		$dates = array("datetime"=>1);
		$queries = array();

		while($row = $this->fetch_array($result,$source_db)){
			$full_query = $insert_query." VALUES (";
			for ($i=0; $i<$num_fields; $i++){
				$type = $this->field_type($result,$i,$source_db);
				if ($row[$i] == "" && strstr(strtoupper($type),"INT"))
					$row[$i] = 0;	
				elseif ($row[$i] == "" && isset($unquoted[$type]))
					$row[$i] = 0;
				if (isset($dates[$type]))
					$row[$i] = $this->cleanDateTime($row[$i]);
				elseif (isset($strings[$type]))
					$row[$i] = str_replace("'","''",$row[$i]);
				if (isset($unquoted[$type]))
					$full_query .= $row[$i].",";
				else
					$full_query .= "'".$row[$i]."',";
			}
			$full_query = substr($full_query,0,strlen($full_query)-1).")";
			array_push($queries,$full_query);
		}

		$ret = True;
		foreach ($queries as $q){
			if(!$this->query($q,$dest_db)) $ret = False;
		}

		return $ret;
	}

	function field_type($result_object,$index,$which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		$fld = $result_object->FetchField($index);
		return $fld->type;
	}

	function field_name($result_object,$index,$which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		$fld = $result_object->FetchField($index);
		return $fld->name;
	}

	function dayofweek($field,$which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		$conn = $this->connections[$which_connection];
		return $conn->SQLDate("w",$field);
	}

	function hour($field,$which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		$conn = $this->connections[$which_connection];
		return $conn->SQLDate("H",$field);
	}


	function cleanDateTime($str){
		$stdFmt = "/(\d\d\d\d)-(\d\d)-(\d\d) (\d+?):(\d\d):(\d\d)/";
		if (preg_match($stdFmt,$str,$group))
			return $str;	

		$msqlFmt = "/(\w\w\w) (\d\d) (\d\d\d\d) (\d+?):(\d\d)(\w)M/";

		$months = array(
			"jan"=>"01",
			"feb"=>"02",
			"mar"=>"03",
			"apr"=>"04",
			"may"=>"05",
			"jun"=>"06",
			"jul"=>"07",
			"aug"=>"08",
			"sep"=>"09",
			"oct"=>"10",
			"nov"=>"11",
			"dec"=>"12"
		);

		$info = array(
			"month" => 1,
			"day" => 1,
			"year" => 1900,
			"hour" => 0,
			"min" => 0
		);
		
		if (preg_match($msqlFmt,$str,$group)){
			$info["month"] = $months[strtolower($group[1])];
			$info["day"] = $group[2];
			$info["year"] = $group[3];
			$info["hour"] = $group[4];
			$info["min"] = $group[5];
			if ($group[6] == "P")
				$info["hour"] = ($info["hour"] + 12) % 24;
		}

		$ret = $info["year"]."-";
		$ret .= str_pad($info["month"],2,"0",STR_PAD_LEFT)."-";
		$ret .= str_pad($info["day"],2,"0",STR_PAD_LEFT)." ";
		$ret .= str_pad($info["hour"],2,"0",STR_PAD_LEFT).":";
		$ret .= str_pad($info["min"],2,"0",STR_PAD_LEFT);
		return $ret;
	}

	/* check whether the given table exists
	   Return values:
		True => table exists
		False => table doesn't exist
		-1 => Operation not supported for this database type
	*/
	function table_exists($table_name,$which_connection=''){
		if ($which_connection == '')
			$which_connection=$this->default_db;
		$conn = $this->connections[$which_connection];
		$cols = $conn->MetaColumns($table_name);
		if ($cols === False) return False;
		return True;
	}

	/* return the table's definition
	   Return values:
		array of (column name, column type) => table found
		False => no such table
		-1 => Operation not supported for this database type
	*/
	function table_definition($table_name,$which_connection=''){
		if ($which_connection == '')
			$which_connection=$this->default_db;
		$conn = $this->connections[$which_connection];
		$cols = $conn->MetaColumns($table_name);

		$return = array();
		if (is_array($cols)){
			foreach($cols as $c)
				$return[$c->name] = $c->type;
			return $return;
		}
		return False;
	}

	function currency($which_connection=''){
		if ($which_connection == '')
			$which_connection=$this->default_db;
		switch($this->connections[$which_connection]->databaseType){
		case 'mysql':
		case 'mysqli':
			return 'decimal(10,2)';
		case 'mssql':
			return 'money';
		}
		return 'decimal(10,2)';
	}

	function add_select_limit($query,$int_limit,$which_connection=''){
		if ($which_connection == '')
			$which_connection=$this->default_db;
		switch($this->connections[$which_connection]->databaseType){
		case 'mysql':
		case 'mysqli':
			return sprintf("%s LIMIT %d",$query,$int_limit);
		case 'mssql':
			return str_ireplace("SELECT ","SELECT TOP $int_limit ",$query);
		}
	}

	function sep($which_connection=''){
		if ($which_connection == '')
			$which_connection=$this->default_db;
		switch($this->connections[$which_connection]->databaseType){
		case 'mysql':
		case 'mysqli':
			return ".";
		case 'mssql':
			return ".dbo.";
		}
	}

	function error($which_connection=''){
		if ($which_connection == '')
			$which_connection=$this->default_db;
		$con = $this->connections[$which_connection];
		return $con->ErrorMsg();
	}

	function insert_id($which_connection=''){
		if ($which_connection == '')
			$which_connection=$this->default_db;
		$con = $this->connections[$which_connection];
		return $con->Insert_ID();
	}

	function affected_rows($which_connection=''){
		if ($which_connection == '')
			$which_connection=$this->default_db;
		$con = $this->connections[$which_connection];
		return $con->Affected_Rows();
	}

	/* insert as much data as possible
	 * $values is an associative array of column_name => column_value
	 * Values are taken as is (i.e., you have to quote your strings)
	 */
	function smart_insert($table_name,$values,$which_connection=''){
                if ($which_connection == '')
                        $which_connection=$this->default_db;

		$exists = $this->table_exists($table_name,$which_connection);

		if (!$exists) return False;
		if ($exists === -1) return -1;

		$t_def = $this->table_definition($table_name,$which_connection);

		$cols = "(";
		$vals = "(";
		foreach($values as $k=>$v){
			if (isset($t_def[$k])){
				$vals .= $v.",";
				$col_name = $k;
				if($this->connections[$which_connection]->databaseType == 'mssql')
					$cols .= $col_name.",";
				else
					$cols .= "`".$col_name."`,";
			}
			else {
				echo "No column - $k";
				// implication: column isn't in the table
			}
		}
		$cols = substr($cols,0,strlen($cols)-1).")";
		$vals = substr($vals,0,strlen($vals)-1).")";
		$insertQ = "INSERT INTO $table_name $cols VALUES $vals";

		//echo $insertQ;
		$ret = $this->query($insertQ,$which_connection);

		return $ret;
	}

	/* update as much data as possible
	 * $values is an associative array of column_name => column_value
	 * Values are taken as is (i.e., you have to quote your strings)
	 * 
	 * Caveat: There are a couple places this could break down
	 * 1) If your WHERE clause requires a column that doesn't exist,
	 *    the query will fail. No way around it. Auto-modifying 
	 *    WHERE clauses seems like a terrible idea
	 * 2) This only works with a single table. Updates involving joins
	 *    are rare in the code base though.
	 */
	function smart_update($table_name,$values,$where_clause,$which_connection=''){
                if ($which_connection == '')
                        $which_connection=$this->default_db;

		$exists = $this->table_exists($table_name,$which_connection);

		if (!$exists) return False;
		if ($exists === -1) return -1;

		$t_def = $this->table_definition($table_name,$which_connection);

		$sets = "";
		foreach($values as $k=>$v){
			if (isset($t_def[$k])){
				$col_name = $k;
				if($this->connections[$which_connection]->databaseType == 'mssql')
					$sets .= $col_name;
				else
					$sets .= "`".$col_name."`";
				$sets .= "=".$v.",";
			}
			else {
				echo "No column - $k";
				// implication: column isn't in the table
			}
		}
		$sets = rtrim($sets,",");
		$upQ = "UPDATE $table_name SET $sets WHERE $where_clause";

		$ret = $this->query($upQ,$which_connection);

		return $ret;
	}

	/* compat layer; mimic functions of Brad's mysql class */
	function get_result($host,$user,$pass,$data_base,$query){
		return $this->query($query);
	}

	function aff_rows($result){
		return $this->affected_rows($result);
	}

	// skipping fetch_cell on purpose; generic-db way would be slow as heck

	/* end compat Brad's class */
}

?>
