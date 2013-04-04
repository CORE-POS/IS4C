<?php
/*******************************************************************************

    Copyright 2007 Whole Foods Co-op 

    This file is part of IT CORE.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/**
 @class SQLManager
 Custom abstraction layer for SQL

 Please see Fannie. It's the same class
 and tons of documentation to reproduce here.
*/

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

define('DEBUG_MYSQL_QUERIES',realpath(dirname(__FILE__).'/../log/queries.log'));
define('DEBUG_SMART_INSERTS',realpath(dirname(__FILE__).'/../log/smart_insert_errors.log'));

$TYPE_MYSQL = 'MYSQL';
$TYPE_MSSQL = 'MSSQL'; 
$TYPE_PGSQL = 'PGSQL';

$TYPE_PDOMY = 'PDOMYSQL';
$TYPE_PDOMS = 'PDOMSSQL';
$TYPE_PDOPG = 'PDOPGSQL';

class SQLManager {

	var $connections;
	var $db_types;
	var $default_db;

	var $TYPE_MYSQL = 'MYSQL';
	var $TYPE_MSSQL = 'MSSQL'; 
	var $TYPE_PGSQL = 'PGSQL';

	var $TYPE_PDOMY = 'PDOMYSQL';
	var $TYPE_PDOMS = 'PDOMSSQL';
	var $TYPE_PDOPG = 'PDOPGSQL';

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
		case $this->TYPE_PDOMY:
			$dsn = 'mysql:host='.$server;
			return new PDO($dsn, $username, $password);
		case $this->TYPE_PDOMS:
			$dsn = 'mssql:host='.$server;
			return new PDO($dsn, $username, $password);
		case $this->TYPE_PDOPG:
			$dsn = 'pgsql:host='.$server;
			return new PDO($dsn, $username, $password);
		}
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
		case $this->TYPE_PDOMY:
		case $this->TYPE_PDOMS:
		case $this->TYPE_PDOPG:
			return $this->query('use '.$db_name,$which_connection);
		}
		return -1;
	}

	function query($query_text,$which_connection=''){
		if ($which_connection == '')
			$which_connection=$this->default_db;
		switch($this->db_types[$which_connection]){
		case $this->TYPE_MYSQL:
			$result = mysql_query($query_text,$this->connections[$which_connection]);
			if (!$result && DEBUG_MYSQL_QUERIES != "" && is_writable(DEBUG_MYSQL_QUERIES)){
				$fp = fopen(DEBUG_MYSQL_QUERIES,"a");
				fwrite($fp,date('r').": ".$query_text."\n\n");
				fclose($fp);
			}
			else if (!$result) echo $query_text."<hr />";
			return $result;
		case $this->TYPE_MSSQL:
			$result = mssql_query($query_text,$this->connections[$which_connection]);
			if (!$result && DEBUG_MYSQL_QUERIES != "" && is_writable(DEBUG_MYSQL_QUERIES)){
				$fp = fopen(DEBUG_MYSQL_QUERIES,"a");
				fwrite($fp,date('r').": ".$query_text."\n\n");
				fclose($fp);
			}
			return $result;
		case $this->TYPE_PGSQL:
			return pg_query($this->connections[$which_connection],$query_text);
		case $this->TYPE_PDOMY:
		case $this->TYPE_PDOMS:
		case $this->TYPE_PDOPG:
			$obj = $this->connections[$which_connection];
			$result = $obj->query($query_text);
			if (!$result && DEBUG_MYSQL_QUERIES != "" && is_writable(DEBUG_MYSQL_QUERIES)){
				$fp = fopen(DEBUG_MYSQL_QUERIES,"a");
				fwrite($fp,date('r').": ".$query_text."\n\n");
				fclose($fp);
			}
			return $result;
		}	
		return -1;
	}

	/**
	  Prepared statement: non-PDO types just return the query_text
	  without modification
	*/
	function prepare_statement($query_text,$which_connection=''){
		if ($which_connection == '')
			$which_connection=$this->default_db;
		switch($this->db_types[$which_connection]){
		case $this->TYPE_MYSQL:
		case $this->TYPE_MSSQL:
			return $query_text;
		case $this->TYPE_PDOMY:
		case $this->TYPE_PDOMS:
		case $this->TYPE_PDOPG:
			$obj = $this->connections[$which_connection];
			return $obj->prepare($query_text);
		}
		return False;
	}

	/**
	  execute statement: exec is emulated for non-PDO types
	*/
	function exec_statement($stmt,$args=array(),$which_connection=''){
		if ($which_connection == '')
			$which_connection=$this->default_db;
		switch($this->db_types[$which_connection]){
		case $this->TYPE_MYSQL:
		case $this->TYPE_MSSQL:
			$query = "";
			$parts = explode('?',$stmt);
			foreach($parts as $p){
				$query .= $p;
				if (count($args)>0){
					$val = array_shift($args);
					$query .= is_numeric($val) ? $val : $this->escape($val,$which_connection);
				}
			}
			return $this->query($query,$which_connection);
		case $this->TYPE_PDOMY:
		case $this->TYPE_PDOMS:
		case $this->TYPE_PDOPG:
			$success = False;
			if (is_object($stmt)){
				$success = $stmt->execute($args);
			}
			return $success ? $stmt : False;
		}
		return False;
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
		case $this->TYPE_PDOMY:
		case $this->TYPE_PDOMS:
		case $this->TYPE_PDOPG:
			return $result_object->rowCount();
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
		case $this->TYPE_PDOMY:
		case $this->TYPE_PDOMS:
		case $this->TYPE_PDOPG:
			return $result_object->columnCount();
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
		case $this->TYPE_PDOMY:
		case $this->TYPE_PDOMS:
		case $this->TYPE_PDOPG:
			return $result_object->fetch();
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
		case $this->TYPE_PDOMY:
		case $this->TYPE_PDOMS:
		case $this->TYPE_PDOPG:
			return $result_object->fetchColumn($index);
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
		case $this->TYPE_PDOMY:
		case $this->TYPE_PDOMS:
		case $this->TYPE_PDOPG:
			$info = $result_object->getColumnMeta($index);
			if (!isset($info['native_type'])) return 'bit';	
			else return strtolower($info['native_type']);
		}
		return -1;
	}

	/**
	  This is effectively disabled. Singleton behavior
	  means it isn't really necessary
	*/
	function close($which_connection='',$force=False){
		if (!$force) return True;
		if ($which_connection == '')
			$which_connection = $this->default_db;
		switch($this->db_types[$which_connection]){
		case $this->TYPE_MYSQL:
			return mysql_close($this->connections[$which_connection]);
		case $this->TYPE_MSSQL:
			return mssql_close($this->connections[$which_connection]);
		case $this->TYPE_PGSQL:
			return pg_close($this->connections[$which_connection]);
		case $this->TYPE_PDOMY:
		case $this->TYPE_PDOMS:
		case $this->TYPE_PDOPG:
			return True;
		}
		return -1;
	}

	/**
	  Temporary compatibility solution. Will go away once
	  db_close() calls are gone in all branches
	*/
	function db_close($which_connection='',$force=False){
		return $this->close($which_connection,$force);
	}

	/**
	  Start a SQL transaction
	  Nexted transactions not supported on MSSQL
	*/
	function start_transaction($which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		switch($this->db_types[$which_connection]){
		case $this->TYPE_MYSQL:
			return $this->query("START TRANSACTION",$which_connection);
		case $this->TYPE_MSSQL:
			return $this->query("BEGIN TRANSACTION tr1",$which_connection);
		case $this->TYPE_PGSQL:
			return $this->query("START TRANSACTION",$which_connection);
		case $this->TYPE_PDOMY:
		case $this->TYPE_PDOMS:
		case $this->TYPE_PDOPG:
			$obj = $this->connections[$which_connection];	
			return $obj->beginTransaction();
		}
		return -1;
	}

	/**
	  Commit an SQL transaction
	*/
	function commit_transaction($which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		switch($this->db_types[$which_connection]){
		case $this->TYPE_MYSQL:
			return $this->query("COMMIT",$which_connection);
		case $this->TYPE_MSSQL:
			return $this->query("COMMIT TRANSACTION tr1",$which_connection);
		case $this->TYPE_PGSQL:
			return $this->query("COMMIT",$which_connection);
		case $this->TYPE_PDOMY:
		case $this->TYPE_PDOMS:
		case $this->TYPE_PDOPG:
			$obj = $this->connections[$which_connection];	
			return $obj->commit();
		}
		return -1;
	}

	/**
	  Rollback an SQL transaction
	*/
	function rollback_transaction($which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		switch($this->db_types[$which_connection]){
		case $this->TYPE_MYSQL:
			return $this->query("ROLLBACK",$which_connection);
		case $this->TYPE_MSSQL:
			return $this->query("ROLLBACK TRANSACTION tr1",$which_connection);
		case $this->TYPE_PGSQL:
			return $this->query("ROLLBACK",$which_connection);
		case $this->TYPE_PDOMY:
		case $this->TYPE_PDOMS:
		case $this->TYPE_PDOPG:
			$obj = $this->connections[$which_connection];	
			return $obj->rollBack();
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
			"unknown"=>1,'double'=>1);
		$strings = array("varchar"=>1,"nvarchar"=>1,"string"=>1,
				"char"=>1,'var_string');
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

		$this->start_transaction($dest_db);

		foreach ($queries as $q){
			if(!$this->query($q,$dest_db)){
				$ret = False;
				if (is_writable(DEBUG_MYSQL_QUERIES)){
					$fp = fopen(DEBUG_MYSQL_QUERIES,"a");
					fwrite($fp,$q."\n\n");
					fclose($fp);
				}
			}
		}

		if ($ret === True)
			$this->commit_transaction($dest_db);
		else
			$this->rollback_transaction($dest_db);

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
		case $this->TYPE_PDOMY:
                case $this->TYPE_MYSQL:
			$result = $this->query("SHOW TABLES FROM $which_connection 
						LIKE '$table_name'",$which_connection);
                        if ($this->num_rows($result) > 0) return True;
                        else return False;
                case $this->TYPE_MSSQL:
		case $this->TYPE_PDOMS:
			$result = $this->query("SELECT name FROM sysobjects 
						WHERE name LIKE '$table_name'",
						$which_connection);
                        if ($this->num_rows($result) > 0) return True;
                        else return False;
		case $this->TYPE_PGSQL:
		case $this->TYPE_PDOPG:
			$result = $this->query("SELECT relname FROM pg_class
					WHERE relname LIKE '$tablename'",
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
		case $this->TYPE_PDOMY:
                case $this->TYPE_MYSQL:
                        $return = array();
                        $result = $this->query("SHOW COLUMNS FROM $table_name",$which_connection);
                        while($row = $this->fetch_row($result,$which_connection)){
				$auto = False;
				if (strstr($row[5],"auto_increment"))
					$auto = True;
                                $return[$row[0]] = array($row[1],$auto,$row[0]);
			}
                        if (count($return) == 0) return False;
                        else return $return;
                case $this->TYPE_MSSQL:
		case $this->TYPE_PDOMS:
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
                        while($row = $this->fetch_row($result,$which_connection)){
				$auto = False;
				if ($row[3] == 1) $auto = True;
				$return[$row[0]] = array($row[1]."(".$row[2].")",$auto,$row[0]);
			}
                        if (count($return) == 0) return False;
                        else return $return;
		case $this->TYPE_PDOPG:
                case $this->TYPE_PGSQL:
			$return = array();
			$result = $this->query("SELECT a.attname,t.typname FROM pg_class AS c
					LEFT JOIN pg_attribute AS a ON a.attrelid = c.oid
					LEFT JOIN pg_type AS t ON a.atttypid = t.oid	
					WHERE c.relname='$table_name'", $which_connection);
			while($row = $this->fetch_row($result,$which_connection)){
				$return[$row[0]] = array($row[1],False);
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
		if ($OUTFILE != "" && is_writable($OUTFILE))
			$fp = fopen($OUTFILE,"a");

		$cols = "(";
		$vals = "(";
		foreach($values as $k=>$v){
			//$k = strtoupper($k);
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
		if ($OUTFILE != "" && is_writable($OUTFILE)) fclose($fp);

		return $ret;
	}

	function datediff($date1,$date2,$which_connection=''){
                if ($which_connection == '')
                        $which_connection = $this->default_db;
                switch($this->db_types[$which_connection]){
		case $this->TYPE_PDOMY:
                case $this->TYPE_MYSQL:
                        return "datediff($date1,$date2)";
                case $this->TYPE_MSSQL:
		case $this->TYPE_PDOMS:
                        return "datediff(dd,$date2,$date1)";
		case $this->TYPE_PGSQL:
		case $this->TYPE_PDOPG:
			return "extract(day from ($date2 - $date1))";
                }
        }

	function yeardiff($date1,$date2,$which_connection=''){
                if ($which_connection == '')
                        $which_connection = $this->default_db;
                switch($this->db_types[$which_connection]){
		case $this->TYPE_PDOMY:
                case $this->TYPE_MYSQL:
                        return "DATE_FORMAT(FROM_DAYS(DATEDIFF($date1,$date2)), '%Y')+0";
                case $this->TYPE_MSSQL:
		case $this->TYPE_PDOMS:
                        return "datediff(yy,$date2,$date1)";
		case $this->TYPE_PGSQL:
		case $this->TYPE_PDOPG:
			return "extract(year from age($date1,$date))";
                }
	}

	function now($which_connection=''){
                if ($which_connection == '')
                        $which_connection = $this->default_db;
                switch($this->db_types[$which_connection]){
		case $this->TYPE_PDOMY:
                case $this->TYPE_MYSQL:
                        return "now()";
                case $this->TYPE_MSSQL:
		case $this->TYPE_PDOMS:
                        return "getdate()";
                case $this->TYPE_PGSQL:
		case $this->TYPE_PDOPG:
                        return "now()";
                }
        }

	function dayofweek($col,$which_connection=''){
                if ($which_connection == '')
                        $which_connection = $this->default_db;
                switch($this->db_types[$which_connection]){
		case $this->TYPE_PDOMY:
                case $this->TYPE_MYSQL:
                        return "dayofweek($col)";
                case $this->TYPE_MSSQL:
		case $this->TYPE_PDOMS:
                        return "datepart(dw,$col)";
                case $this->TYPE_PGSQL:
		case $this->TYPE_PDOPG:
			return "extract(dow from $col";
                }
	}

	function curtime($which_connection=''){
                if ($which_connection == '')
                        $which_connection = $this->default_db;
                switch($this->db_types[$which_connection]){
		case $this->TYPE_PDOMY:
                case $this->TYPE_MYSQL:
                        return "curtime()";
                case $this->TYPE_MSSQL:
		case $this->TYPE_PDOMS:
                        return "getdate()";
                case $this->TYPE_PGSQL:
		case $this->TYPE_PDOPG:
			return "current_time";
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
		case $this->TYPE_PDOMY:
		case $this->TYPE_PDOMS:
		case $this->TYPE_PDOPG:
			$obj = $this->connections[$which_connection];
			$quoted = $obj->quote($str);
			return ($quoted == "''" ? '' : substr($quoted, 1, strlen($quoted)-2));
		}
		return $str;
	}

	function identifier_escape($str,$which_connection=''){
                if ($which_connection == '')
                        $which_connection = $this->default_db;
                switch($this->db_types[$which_connection]){
		case $this->TYPE_MYSQL:
		case $this->TYPE_PDOMY:
			return '`'.$str.'`';
		case $this->TYPE_PDOMS:
		case $this->TYPE_MSSQL:
			return '['.$str.']';
                case $this->TYPE_PGSQL:
		case $this->TYPE_PDOPG:
			return '"'.$str.'"';
		}
		return $str;
	}

	function sep($str,$which_connection=''){
                if ($which_connection == '')
                        $which_connection = $this->default_db;
                switch($this->db_types[$which_connection]){
		case $this->TYPE_MYSQL:
		case $this->TYPE_PDOMY:
                case $this->TYPE_PGSQL:
		case $this->TYPE_PDOPG:
			return '.';
		case $this->TYPE_PDOMS:
		case $this->TYPE_MSSQL:
			return '.dbo.';
		}
		return '.';
	}


	function error($which_connection=''){
                if ($which_connection == '')
                        $which_connection = $this->default_db;
                switch($this->db_types[$which_connection]){
		case $this->TYPE_MYSQL:
			return mysql_error();
		case $this->TYPE_MSSQL:
			return mssql_get_last_message();
		case $this->TYPE_PDOMY:
		case $this->TYPE_PDOMS:
		case $this->TYPE_PDOPG:
			$obj = $this->connections[$which_connection];
			$info = $obj->errorInfo();
			return $info[2];
		}
		return 'unknown error';
	}

	/**
	  Concatenate strings
	  @param Arbitrary; see below
	  @return The SQL expression

	  This function takes an arbitrary number of arguments
	  and concatenates them. The last argument is the
	  standard $which_connection but in this case it is
	  not optional. You may pass the empty string to use
	  the default database though.

	  This method currently only supports MySQL and MSSQL
	*/
	function concat(){
		$args = func_get_args();
		$ret = "";
		$which_connection = $args[count($args)-1];
		if ($which_connection == '')
			$which_connection = $this->default_db;
		switch($this->db_types[$which_connection]){
		case $this->TYPE_PDOMY:
		case $this->TYPE_MYSQL:
			$ret .= "CONCAT(";
			for($i=0;$i<count($args)-1;$i++)
				$ret .= $args[$i].",";	
			$ret = rtrim($ret,",").")";
			break;
		case $this->TYPE_MSSQL:
		case $this->TYPE_PDOMS:
			for($i=0;$i<count($args)-1;$i++)
				$ret .= $args[$i]."+";	
			$ret = rtrim($ret,"+");
			break;
		case $this->TYPE_PGSQL:
		case $this->TYPE_PDOPG:
			for($i=0;$i<count($args)-1;$i++)
				$ret .= $args[$i]."||";	
			$ret = rtrim($ret,"||");
			break;
		}
		return $ret;
	}

	/**
	  Get a SQL convert function
	  @param $expr An SQL expression
	  @param $type Convert to this SQL type
	  @param $which_connection see method close()
	  @return The SQL expression

	  This method currently only supports MySQL and MSSQL

	*/
	function convert($expr,$type,$which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		switch($this->db_types[$which_connection]){
		case $this->TYPE_PDOMY:
		case $this->TYPE_MYSQL:
			if(strtoupper($type)=='INT')
				$type='SIGNED';
			return "CONVERT($expr,$type)";
		case $this->TYPE_MSSQL:
		case $this->TYPE_PDOMS:
			return "CONVERT($type,$expr)";
		case $this->TYPE_PGSQL:
		case $this->TYPE_PDOPG:
			return "CAST($expr AS $type)";
		}
		return "";
	}
}

?>
