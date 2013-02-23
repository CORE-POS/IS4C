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

/**
 @class SQLManager
 @brief A SQL abstraction layer

 Custom SQL abstraction based on ADOdb.
 Provides some limited functionality for queries
 across two servers that are useful for lane-server
 communication
*/
$QUERY_LOG = $FANNIE_ROOT."logs/queries.log";

if (!function_exists("ADONewConnection")) include($FANNIE_ROOT.'adodb5/adodb.inc.php');

class SQLManager {

	/** Array of connections **/
	var $connections;
	/** Default database connection */
	var $default_db;

	/** Constructor
	    @param $server Database server host
	    @param $type Database type. Most supported are
	    'mysql' and 'mssql' but anything ADOdb supports
	    will kind of work
	    @param $database Database name
	    @param $username Database username
	    @param $password Database password
	    @param $persistent Make persistent connection.
	*/
	function SQLManager($server,$type,$database,$username,$password='',$persistent=False){
		$this->connections=array();
		$this->default_db = $database;
		$this->add_connection($server,$type,$database,$username,$password,$persistent);
	}

	/** Add another connection
	    @param $server Database server host
	    @param $type Database type. Most supported are
	    'mysql' and 'mssql' but anything ADOdb supports
	    will kind of work
	    @param $database Database name
	    @param $username Database username
	    @param $password Database password
	    @param $persistent Make persistent connection.

	    When dealing with multiple connections, user the
	    database name to distinguish which is to be used
	*/
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
				$stillok = $conn->Execute("CREATE DATABASE $database");
				if (!$stillok){
					$this->connections[$database] = False;
					return False;
				}
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

	/**
	  Close a database connection
	  @param $which_connection
	  If there are multiple connections, this is
	  the database name for the connection you want to close
	*/
	function close($which_connection=''){
		if ($which_connection == '')
			$which_connection=$this->default_db;
		$con = $this->connections[$which_connection];
		unset($this->connections[$which_connection]);

		return $con->Close();
	}

	/**
	  Execute a query
	  @param $query_text The query
	  @param which_connection see method close
	  @return A result object on success, False on failure
	*/
	function query($query_text,$which_connection='',$params=False){
		global $QUERY_LOG;
		if ($which_connection == '')
			$which_connection=$this->default_db;
		$con = $this->connections[$which_connection];

		$ok = (!is_object($con)) ? False : $con->Execute($query_text,$params);
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

	/**
	  Execute a query on all connected databases
	  @param $query_text The query
	  @return An array keyed by database name. Entries
	  will be result objects where queries succeeded
	  and False where they failed
	*/
	function query_all($query_text){
		$ret = array();
		foreach($this->connections as $db_name => $con){
			$ret[$db_name] = $this->query($query_text,$db_name);
		}
		return $ret;
	}

	/**
	  Escape a string for SQL-safety
	  @param $query_text The string to escape
	  @param $which_connection see method close()
	  @return The escaped string

	  Note that the return value will include start and
	  end single quotes
	*/
	function escape($query_text,$which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		return $this->connections[$which_connection]->qstr($query_text);
	}
	
	/**
	  Get number of rows in a result set
	  @param $result_object A result set
	  @param $which_connection see method close()
	  @return Integer number or False if there's an error
	*/
	function num_rows($result_object,$which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		return $result_object->RecordCount();
	}

	/**
	  Move result cursor to specified record
	  @param $result_object A result set
	  @param $rownum The record index
	  @param $which_connection see method close()
	  @return True on success, False on failure
	*/
	function data_seek($result_object,$rownum,$which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		return $result_object->Move((int)$rownum);
	}
	
	/**
	  Get number of fields in a result set
	  @param $result_object A result set
	  @param $which_connection see method close()
	  @return Integer number or False if there's an error
	*/
	function num_fields($result_object,$which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		return $result_object->FieldCount();
	}

	/**
	  Get next record from a result set
	  @param $result_object A result set
	  @param $which_connection see method close()
	  @return An array of values
	*/
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

	/**
	  Get next record from a result set but as an object
	  @param $result_object A result set
	  @param $which_connection see method close()
	  @return An object with member containing values
	*/
	function fetch_object($result_object,$which_connection=''){
		return $result_object->FetchNextObject(False);
	}
	
	/**
	  An alias for the method fetch_array()
	*/
	function fetch_row($result_object,$which_connection=''){
		return $this->fetch_array($result_object,$which_connection);
	}

	/**
	  Get the database's function for present time
	  @param $which_connection see method close()
	  @return The appropriate function

	  For example, with MySQL this will return the
	  string 'NOW()'.
	*/
	function now($which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		return $this->connections[$which_connection]->sysTimeStamp;
	}

	/**
	  Get the database's date difference function
	  @param $date1 First date
	  @param $date2 Second date
	  @param $which_connection see method close()
	  @return The appropriate function

	  Arguments are inverted for some databases to
	  ensure consistent results. If $date1 is today
	  and $date2 is yesterday, this method returns
	  a SQL function that evaluates to 1.
	*/
	function datediff($date1,$date2,$which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		switch($this->connections[$which_connection]->databaseType){
		case 'mysql':
		case 'mysqli':
		case 'pdo_mysql':
			return "datediff($date1,$date2)";
		case 'mssql':
			return "datediff(dd,$date2,$date1)";
		}
	}

	/**
	  Get the databases' month difference function
	  @param $date1 First date
	  @param $date2 Second date
	  @param $which_connection see method close()
	  @return The SQL expression

	  Arguments are inverted for some databases to
	  ensure consistent results. If $date1 is this month
	  and $date2 is last month, this method returns
	  a SQL expression that evaluates to 1.
	*/

	function monthdiff($date1,$date2,$which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		switch($this->connections[$which_connection]->databaseType){
		case 'mysql':
		case 'mysqli':
		case 'pdo_mysql':
			return "period_diff(date_format($date1, '%Y%m'), date_format($date2, '%Y%m'))";
		case 'mssql':
			return "datediff(mm,$date2,$date1)";
		}	
	}

	/**
	  Get the difference between two dates in seconds
	  @param $date1 First date (or datetime)
	  @param $date2 Second date (or datetime)
	  @param $which_connection see method close()
	  @return The SQL expression

	  This method currently only suport MySQL and MSSQL
	*/
	function seconddiff($date1,$date2,$which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		switch($this->connections[$which_connection]->databaseType){
		case 'mysql':
		case 'mysqli':
		case 'pdo_mysql':
			return "TIMESTAMPDIFF(SECOND,$date1,$date2)";
		case 'mssql':
			return "datediff(ss,$date2,$date1)";
		}	
	}

	/**
	  Get a date formatted YYYYMMDD
	  @param $date1 The date (or datetime)
	  @param $which_connection see method close()
	  @return The SQL expression

	  This method currently only supports MySQL and MSSQL
	*/
	function dateymd($date1,$which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		switch($this->connections[$which_connection]->databaseType){
		case 'mysql':
		case 'mysqli':
		case 'pdo_mysql':
			return "DATE_FORMAT($date1,'%Y%m%d')";
		case 'mssql':
			return "CONVERT(CHAR(11),$date1,112)";
		}
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
		switch($this->connections[$which_connection]->databaseType){
		case 'mysql':
		case 'mysqli':
		case 'pdo_mysql':
			if(strtoupper($type)=='INT')
				$type='SIGNED';
			return "CONVERT($expr,$type)";
		case 'mssql':
			return "CONVERT($type,$expr)";
		}
		return "";
	}

	/**
	  Find index of a substring within a larger string
	  @param $substr Search string (needle)
	  @param $str Target string (haystack)
	  @param $which_connection see method close()
	  @return The SQL expression

	  This method currently only supports MySQL and MSSQL
	*/
	function locate($substr,$str,$which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		switch($this->connections[$which_connection]->databaseType){
		case 'mysql':
		case 'mysqli':
		case 'pdo_mysql':
			return "LOCATE($substr,$str)";
		case 'mssql':
			return "CHARINDEX($substr,$str)";
		}
		return "";
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
		switch($this->connections[$which_connection]->databaseType){
		case 'mysql':
		case 'mysqli':
		case 'pdo_mysql':
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

	/**
	  Get the differnces between two dates in weeks
	  @param $date1 First date
	  @param $date2 Second date
	  @param $which_connection see method close()
	  @return The SQL expression

	  This method currently only supports MySQL and MSSQL
	*/
	function weekdiff($date1,$date2,$which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		switch($this->connections[$which_connection]->databaseType){
		case 'mysql':
		case 'mysqli':
		case 'pdo_mysql':
			return "week($date1) - week($date2)";
		case 'mssql':
			return "datediff(wk,$date2,$date1)";
		}	
	}

	/**
	  Get a column name by index
	  @param $result_object A result set
	  @param $index Integer index
	  @param $which_connection see method close()
	  @return The column name
	*/
	function fetch_field($result_object,$index,$which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		return $result_object->FetchField($index);
	}

	/** 
	   Copy a table from one database to another, not necessarily on
	   the same server or format.
	
	   @param $source_db The database name of the source
	   @param $select_query The query that will get the data
	   @param $dest_db The database name of the destination
	   @param $insert_query The beginning of the query that will add the
		data to the destination (specify everything before VALUES)
	   @return False if any record cannot be transfered, True otherwise
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

	/**
	  Get column type
	  @param $result_object A result set
	  @param $index Integer index
	  @param $which_connection see method close()
	  @return The column type
	*/
	function field_type($result_object,$index,$which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		$fld = $result_object->FetchField($index);
		return $fld->type;
	}

	/**
	  Alias of method fetch_field()
	*/
	function field_name($result_object,$index,$which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		$fld = $result_object->FetchField($index);
		return $fld->name;
	}

	/**
	  Get day of week number
	  @param $field A date expression
	  @param $which_connection see method close()
	  @return The SQL expression

	  This method currently only suport MySQL and MSSQL
	*/
	function dayofweek($field,$which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		// ado is inconsistent
		//$conn = $this->connections[$which_connection];
		//return $conn->SQLDate("w",$field);
		switch($this->connections[$which_connection]->databaseType){
		case 'mysql':
		case 'mysqli':
		case 'pdo_mysql':
			return "DATE_FORMAT($field,'%w')+1";
		case 'mssql':
			return "DATEPART(dw,$field)";
		}
		return false;
	}

	/**
	  Get the hour from a datetime
	  @param $field A datetime expression
	  @param $which_connection see method close()
	  @return The SQL expression
	*/
	function hour($field,$which_connection=''){
		if ($which_connection == '')
			$which_connection = $this->default_db;
		$conn = $this->connections[$which_connection];
		return $conn->SQLDate("H",$field);
	}


	/**
	  Reformat a datetime to YYYY-MM-DD HH:MM:SS
	  @param $str A datetime string
	  @return The reformatted string

	  This is a utility method to support transfer()
	*/
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

	/** 
	   Check whether the given table exists
	   @param $table_name The table's name
	   @param which_connection see method close
	   @return
	    - True The table exists
	    - False The table doesn't exist
	    - -1 Operation not supported for this database type
	*/
	function table_exists($table_name,$which_connection=''){
		if ($which_connection == '')
			$which_connection=$this->default_db;
		$conn = $this->connections[$which_connection];
		$cols = $conn->MetaColumns($table_name);
		if ($cols === False) return False;
		return True;
	}

	/**
	   Get the table's definition
	   @param $table_name The table's name
	   @param which_connection see method close
	   @return
	    - Array of (column name, column type) table found
	    - False No such table
	    - -1 Operation not supported for this database type
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

	/**
	  Get database's currency type
	  @param which_connection see method close
	  @return The SQL type
	*/
	function currency($which_connection=''){
		if ($which_connection == '')
			$which_connection=$this->default_db;
		switch($this->connections[$which_connection]->databaseType){
		case 'mysql':
		case 'mysqli':
		case 'pdo_mysql':
			return 'decimal(10,2)';
		case 'mssql':
			return 'money';
		}
		return 'decimal(10,2)';
	}

	/**
	  Add row limit to a select query
	  @param $query The select query
	  @param $int_limit Max rows
	  @param which_connection see method close

	  This method currently only suport MySQL and MSSQL
	*/
	function add_select_limit($query,$int_limit,$which_connection=''){
		if ($which_connection == '')
			$which_connection=$this->default_db;
		switch($this->connections[$which_connection]->databaseType){
		case 'mysql':
		case 'mysqli':
		case 'pdo_mysql':
			return sprintf("%s LIMIT %d",$query,$int_limit);
		case 'mssql':
			return str_ireplace("SELECT ","SELECT TOP $int_limit ",$query);
		}
	}

	/**
	  Get database scope separator
	  @param which_connection see method close
	  @return String separator
	*/
	function sep($which_connection=''){
		if ($which_connection == '')
			$which_connection=$this->default_db;
		switch($this->connections[$which_connection]->databaseType){
		case 'mysql':
		case 'mysqli':
		case 'pdo_mysql':
			return ".";
		case 'mssql':
			return ".dbo.";
		}
		return ".";
	}

	/**
	  Get last error message
	  @param which_connection see method close
	  @return The message
	*/
	function error($which_connection=''){
		if ($which_connection == '')
			$which_connection=$this->default_db;
		$con = $this->connections[$which_connection];
		return $con->ErrorMsg();
	}

	/**
	  Get auto incremented ID from last insert
	  @param which_connection see method close
	  @return The new ID value
	*/
	function insert_id($which_connection=''){
		if ($which_connection == '')
			$which_connection=$this->default_db;
		$con = $this->connections[$which_connection];
		return $con->Insert_ID();
	}

	/**
	  Check how many rows the last query affected
	  @param which_connection see method close
	  @returns Number of rows
	*/
	function affected_rows($which_connection=''){
		if ($which_connection == '')
			$which_connection=$this->default_db;
		$con = $this->connections[$which_connection];
		return $con->Affected_Rows();
	}

	/** 
	  Insert as much data as possible
	  @param $table_name Table to insert into
	  @param $values An array of column name => column value
	  @param which_connection see method close
	  @return Same as INSERT via query() method

	  This method polls the table to see which columns actually
	  exist then inserts those values
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

	/** 
	  Update as much data as possible
	  @param $table_name The table to update
	  @param $values An array of column name => column value
	  @param $where_clause The query WHERE clause
	  @param which_connection see method close
	  @return Same as an UPDATE via query() method
	  
	  This method checks which columns actually exist then
	  updates those values

	  Caveat: There are a couple places this could break down
	   - If your WHERE clause requires a column that doesn't exist,
	     the query will fail. No way around it. Auto-modifying 
	     WHERE clauses seems like a terrible idea
	   - This only works with a single table. Updates involving joins
	     are rare in the code base though.
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

	/**
	  Create a prepared statement
	  @param $sql SQL expression	
	  @param which_connection see method close
	  @return
	    - If ADOdb supports prepared statements, an
	      array of (input string $sql, statement object)
	    - If ADOdb does not supported prepared statements,
	      then just the input string $sql

	  The return value of this function should be handed
	  to SQLManager::exec_statement for execution
	*/
	function prepare_statement($sql,$which_connection=""){
		if ($which_connection == '')
			$which_connection=$this->default_db;
		$con = $this->connections[$which_connection];
		return $con->Prepare($sql);
	}

	/**
	  Execute a prepared statement with the given
	  set of parameters
	  @param $sql a value from SQLManager::prepare_statement
	  @param $input_array an array of values
	  @param which_connection see method close
	  @return same as SQLManager::query

	  This is essentially a helper function to flip the 
	  parameter order on SQLManager::query so existing code
	  works as expected
	*/
	function exec_statement($sql, $input_array, $which_connection=''){
		if ($which_connection == '')
			$which_connection=$this->default_db;
		if (!is_array($input_array)) $input_array = array($input_array);
		return $this->query($sql,$which_connection,$input_array);
	}

	/** 
	  See if a datetime is on a given date using BETWEEN	
	  @param $col datetime expression
	  @param $dateStr String date
	  @return SQL BETWEEN comparision

	  Which MySQL partitioning by date this is MUCH
	  faster than using datediff($col,$dateStr)==0
	*/
	function date_equals($col,$dateStr){
		$dateStr = trim($dateStr,"'");
		$seconds = strtotime($dateStr);
		if ($seconds === False) $seconds = time();
		$base = date("Y-m-d",$seconds);
	
		return sprintf("(%s BETWEEN '%s 00:00:00' AND '%s 23:59:59')",
			$col,$base,$base);
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
