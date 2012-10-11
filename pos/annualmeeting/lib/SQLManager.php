<?php
/*******************************************************************************

    Copyright 2007 Whole Foods Co-op 

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

/**************************************************
CLASS INTERFACE

Properties:
rows - contains the number of rows from the last
       query
TYPE_MYSQL - type for MySQL (static)
TYPE_MSSQL - type for Microsoft SQL Server (static)

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

define('DEBUG_MYSQL_QUERIES',$_SERVER['DOCUMENT_ROOT'].'/queries.log');
define('DEBUG_SMART_INSERTS',$_SERVER['DOCUMENT_ROOT'].'/smart_insert_errors.log');

$TYPE_MYSQL = 'MYSQL';
$TYPE_MSSQL = 'MSSQL'; 
$TYPE_PGSQL = 'PGSQL';

class SQLManager {

	var $connections;
	var $db_types;
	var $default_db;

	var $TYPE_MYSQL = 'MYSQL';
	var $TYPE_MSSQL = 'MSSQL'; 
	var $TYPE_PGSQL = 'PGSQL';

	function SQLManager($server,$type,$database,$username,$password='',$persistent=False){
		$this->connections=array();
		$this->db_types=array();
		$this->default_db = $database;
		$this->add_connection($server,strtoupper($type),
				      $database,$username,$password,
				      $persistent);
	}

	function add_connection($server,$type,$database,$username,$password='',$persistent=False){
		if (isset($this->connections[$database])){
			$this->connections[$database] = $this->connect($server,
				strtoupper($type),$username,$password,
				$persistent,False);		
		}
		else {
			$this->connections[$database] = $this->connect($server,
				strtoupper($type),$username,$password,
				$persistent,True);		

		}
		$this->db_types[$database] = strtoupper($type);
		$gotdb = $this->select_db($database,$database);
		if (!$gotdb){
                        if ($this->query("CREATE DATABASE $database")){
                                $this->select_db($database,$database);
                        }
                        else {
                                unset($this->db_types[$database]);
                                $this->connections[$database] = False;
                        }
                }

	}

	function connect($server,$type,$username,$password,$persistent=False,$newlink=False){
		switch($type){
		case $this->TYPE_MYSQL:
			if ($persistent)
				return mysql_pconnect($server,$username,$password,$newlink);
			else
				return mysql_connect($server,$username,$password,$newlink);
		case $this->TYPE_MSSQL:
			if ($persistent)
				return mssql_pconnect($server,$username,$password);
			else
				return mssql_connect($server,$username,$password);
		case $this->TYPE_PGSQL:
			$conStr = "host=".$server." user=".$username." password=".$password;
			if ($persistent)
				return pg_pconnect($conStr);
			else
				return pg_connect($conStr);
		}	
		return -1;
	}

	function db_close($which_connection=''){
		if ($which_connection == '')
			$which_connection=$this->default_db;
		switch($this->db_types[$which_connection]){	
		case $this->TYPE_MYSQL:
			return mysql_close($this->connections[$which_connection]);	
		case $this->TYPE_MSSQL:
			return mssql_close($this->connections[$which_connection]);
		case $this->TYPE_PGSQL:
			return pg_close($this->connections[$which_connection]);
		}
		unset($this->connections[$which_connection]);
		unset($this->db_types[$which_connection]);
		return -1;
	}
	function select_db($db_name,$which_connection=''){
		if ($which_connection == '')
			$which_connection=$this->default_db;
		switch($this->db_types[$which_connection]){	
		case $this->TYPE_MYSQL:
			return mysql_select_db($db_name,$this->connections[$which_connection]);	
		case $this->TYPE_MSSQL:
			return mssql_select_db($db_name,$this->connections[$which_connection]);
		case $this->TYPE_PGSQL:
			return True;
		}
		return -1;
	}

	function query($query_text,$which_connection=''){
		if ($which_connection == '')
			$which_connection=$this->default_db;
		switch($this->db_types[$which_connection]){
		case $this->TYPE_MYSQL:
			$result = mysql_query($query_text,$this->connections[$which_connection]);
			if (!$result && DEBUG_MYSQL_QUERIES != ""){
				$fp = fopen(DEBUG_MYSQL_QUERIES,"a");
				fwrite($fp,date('r').": ".$query_text."\n\n");
				fclose($fp);
			}
			return $result;
		case $this->TYPE_MSSQL:
			$result = mssql_query($query_text,$this->connections[$which_connection]);
			if (!$result && DEBUG_MYSQL_QUERIES != ""){
				$fp = fopen(DEBUG_MYSQL_QUERIES,"a");
				fwrite($fp,date('r').": ".$query_text."\n\n");
				fclose($fp);
			}
			return $result;
		case $this->TYPE_PGSQL:
			return pg_query($this->connections[$which_connection],$query_text);
		}	
		return -1;
	}
	
	function num_rows($result_object,$which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		switch($this->db_types[$which_connection]){
		case $this->TYPE_MYSQL:
			return mysql_num_rows($result_object);
		case $this->TYPE_MSSQL:
			return mssql_num_rows($result_object);
		case $this->TYPE_PGSQL:
			return pg_num_rows($result_object);
		}
		return -1;
	}
	
	function num_fields($result_object,$which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		switch($this->db_types[$which_connection]){
		case $this->TYPE_MYSQL:
			return mysql_num_fields($result_object);
		case $this->TYPE_MSSQL:
			return mssql_num_fields($result_object);
		case $this->TYPE_PGSQL:
			return pg_num_fields($result_object);
		}
		return -1;
	}

	function fetch_array($result_object,$which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		switch($this->db_types[$which_connection]){
		case $this->TYPE_MYSQL:
			return mysql_fetch_array($result_object);
		case $this->TYPE_MSSQL:
			return mssql_fetch_array($result_object);
		case $this->TYPE_PGSQL:
			return pg_fetch_array($result_object);
		}
		return -1;
	}
	
	/* compatibility */
	function fetch_row($result_object,$which_connection=''){
		return $this->fetch_array($result_object,$which_connection);
	}

	function fetch_field($result_object,$index,$which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		switch($this->db_types[$which_connection]){
		case $this->TYPE_MYSQL:
			return mysql_fetch_field($result_object,$index);
		case $this->TYPE_MSSQL:
			return mssql_fetch_field($result_object,$index);
		}
		return -1;
	}

	function field_type($result_object,$index,$which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		switch($this->db_types[$which_connection]){
		case $this->TYPE_MYSQL:
			return mysql_field_type($result_object,$index);
		case $this->TYPE_MSSQL:
			return mssql_field_type($result_object,$index);
		case $this->TYPE_PGSQL:
			return pg_field_type($result_object,$index);
		}
		return -1;
	}

	function close($which_connection=''){
		return;
		if ($which_connection == '')
			$which_connection = $this->default_db;
		switch($this->db_types[$which_connection]){
		case $this->TYPE_MYSQL:
			return mysql_close($this->connections[$which_connection]);
		case $this->TYPE_MSSQL:
			return mssql_close($this->connections[$which_connection]);
		case $this->TYPE_PGSQL:
			return pg_close($this->connections[$which_connection]);
		}
		return -1;
	}

	function test($which_connection=''){
		if ($which_connection=='')
			$which_connection=$this->default_db;

		if ($this->connections[$which_connection]) return True;
		else return False;
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
			"float4"=>1,"float8"=>1,"bit"=>1,"decimal"=>1,
			"unknown"=>1);
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
                                if (isset($dates[$type])){
					$clean = $this->cleanDateTime($row[$i]);
                                        $row[$i] = ($clean!="")?$clean:$row[$i];
				}
                                elseif (isset($strings[$type]))
                                        $row[$i] = str_replace("'","''",$row[$i]);

				if (isset($unquoted[$type]))
					$full_query .= $row[$i].",";
				else
					$full_query .= "'".$row[$i]."',";
			}
			$full_query = substr($full_query,0,strlen($full_query)-1).")";
			$queries[] = $full_query;
		}

		$ret = True;

		foreach ($queries as $q){
			if(!$this->query($q,$dest_db)){
				$ret = False;
				$fp = fopen(DEBUG_MYSQL_QUERIES,"a");
				fwrite($fp,$q."\n\n");
				fclose($fp);
			}
		}

		return $ret;
	}

	function cleanDateTime($str){
		$stdFmt = "/(\d\d\d\d)-(\d\d)-(\d\d) (\d+?):(\d\d):(\d\d)/";
                if (preg_match($stdFmt,$str,$group))
                        return $str;

                $msqlFmt = "/(\w\w\w) (\d+) (\d\d\d\d) +(\d+?):(\d\d)(\w)M/";

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
			if ($group[6] == "P" && $info["hour"] != "12")
                                $info["hour"] = ($info["hour"] + 12) % 24;
                        elseif($group[6] == "A" && $info["hour"] == "12")
                                $info["hour"] = 0;
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
                switch($this->db_types[$which_connection]){
                case $this->TYPE_MYSQL:
			$result = $this->query("SHOW TABLES FROM $which_connection 
						LIKE '$table_name'",$which_connection);
                        if ($this->num_rows($result) > 0) return True;
                        else return False;
                case $this->TYPE_MSSQL:
			$result = $this->query("SELECT name FROM sysobjects 
						WHERE name LIKE '$table_name'",
						$which_connection);
                        if ($this->num_rows($result) > 0) return True;
                        else return False;
                }
                return -1;
        }

	/* return the table's definition
           Return values:
	   	array of values => table found
			array format: $return['column_name'] =
					array('column_type', is_auto_increment)
                False => no such table
                -1 => Operation not supported for this database type
        */
        function table_definition($table_name,$which_connection=''){
                if ($which_connection == '')
                        $which_connection=$this->default_db;
                switch($this->db_types[$which_connection]){
                case $this->TYPE_MYSQL:
                        $return = array();
                        $result = $this->query("SHOW COLUMNS FROM $table_name",$which_connection);
                        while($row = $this->fetch_row($result)){
				$auto = False;
				if (strstr($row[5],"auto_increment"))
					$auto = True;
                                $return[strtoupper($row[0])] = array($row[1],$auto,$row[0]);
			}
                        if (count($return) == 0) return False;
                        else return $return;
                case $this->TYPE_MSSQL:
                        $return = array();
                        $result = $this->query("SELECT c.name,t.name,c.length,
						CASE WHEN c.autoval IS NULL
						THEN 0 ELSE 1 END AS auto
                                                FROM syscolumns AS c
                                                LEFT JOIN sysobjects AS o
                                                ON c.id=o.id
                                                LEFT JOIN systypes AS t
                                                ON c.xtype=t.xtype
                                                WHERE o.name='$table_name'",$which_connection);
                        while($row = $this->fetch_row($result)){
				$auto = False;
				if ($row[3] == 1) $auto = True;
				$return[strtoupper($row[0])] = array($row[1]."(".$row[2].")",$auto,$row[0]);
			}
                        if (count($return) == 0) return False;
                        else return $return;
                }
                return -1;
        }

	/* attempt to load an array of values
	 * into the specified table
	 * 	array format: $values['column_name'] = 'column_value'
	 * If debugging is enabled, columns that couldn't be
	 * written are noted
	 */
	function smart_insert($table_name,$values,$which_connection=''){
		$OUTFILE = DEBUG_SMART_INSERTS;

                if ($which_connection == '')
                        $which_connection=$this->default_db;
		$exists = $this->table_exists($table_name,$which_connection);
		if (!$exists) return False;
		if ($exists === -1) return -1;

		$t_def = $this->table_definition($table_name,$which_connection);

		$fp = -1;
		$tstamp = date("r");
		if ($OUTFILE != "")
			$fp = fopen($OUTFILE,"a");

		$cols = "(";
		$vals = "(";
		foreach($values as $k=>$v){
			$k = strtoupper($k);
			if (isset($t_def[$k]) && is_array($t_def[$k])){
				if (!$t_def[$k][1]){
					if (stristr($t_def[$k][0],"money") ||
					    stristr($t_def[$k][0],'decimal') ||
					    stristr($t_def[$k][0],'float') ||
					    stristr($t_def[$k][0],'double') )
						$vals .= $v.",";
					else
						$vals .= "'".$v."',";
					$col_name = $t_def[$k][2];
					if ($this->db_types[$which_connection] == $this->TYPE_MYSQL)
						$cols .= "`".$col_name."`,";
					else
						$cols .= $col_name.",";
				}
				else {
					if ($OUTFILE != "")
						fwrite($fp,"$tstamp: Column $k in table $table_name
							is auto_increment so your value
							was omitted\n");
				}
			}
			else {
				if ($OUTFILE != '')
					fwrite($fp,"$tstamp: Column $k not in table $table_name\n");
			}
		}
		$cols = substr($cols,0,strlen($cols)-1).")";
		$vals = substr($vals,0,strlen($vals)-1).")";
		$insertQ = "INSERT INTO $table_name $cols VALUES $vals";

		$ret = $this->query($insertQ,$which_connection);
		if (!$ret && $OUTFILE != ""){
			fwrite($fp,"$tstamp: $insertQ\n");
		}
		if ($OUTFILE != "") fclose($fp);

		return $ret;
	}

	function datediff($date1,$date2,$which_connection=''){
                if ($which_connection == '')
                        $which_connection = $this->default_db;
                switch($this->db_types[$which_connection]){
                case $this->TYPE_MYSQL:
                        return "datediff($date1,$date2)";
                case $this->TYPE_MSSQL:
                        return "datediff(dd,$date2,$date1)";
                }
        }

	function now($which_connection=''){
                if ($which_connection == '')
                        $which_connection = $this->default_db;
                switch($this->db_types[$which_connection]){
                case $this->TYPE_MYSQL:
                        return "now()";
                case $this->TYPE_MSSQL:
                        return "getdate()";
                case $this->TYPE_PGSQL:
                        return "now()";
                }
        }

	function escape($str,$which_connection=''){
                if ($which_connection == '')
                        $which_connection = $this->default_db;
                switch($this->db_types[$which_connection]){
		case $this->TYPE_MYSQL:
			return mysql_real_escape_string($str);
		case $this->TYPE_MSSQL:
			return str_replace("'","''",$str);
		}
		return $str;
	}
}

?>
