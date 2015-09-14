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

namespace COREPOS\common;

if (!function_exists("ADONewConnection")) {
    if (file_exists(dirname(__FILE__) . '/../vendor/adodb/adodb-php/adodb.inc.php')) {
        include(dirname(__FILE__) . '/../vendor/adodb/adodb-php/adodb.inc.php');
    } else {
        include(dirname(__FILE__).'/adodb5/adodb.inc.php');
    }
}


/**
 @class SQLManager
 @brief A SQL abstraction layer

 Custom SQL abstraction based on ADOdb.
 Provides some limited functionality for queries
 across two servers that are useful for lane-server
 communication
*/
class SQLManager 
{
    private $QUERY_LOG; 

    /** Array of connections **/
    public $connections;
    /** Default database connection */
    public $default_db;

    /** throw exception on failed query **/
    protected $throw_on_fail = false;

    /** cache information about table existence & definition **/
    protected $structure_cache = array();

    protected $last_connect_error = false;

    /** Constructor
        @param $server Database server host
        @param $type Database type. Most supported are
        'mysql' and 'mssql' but anything ADOdb supports
        will kind of work
        @param $database Database name
        @param $username Database username
        @param $password Database password
        @param $persistent Make persistent connection.
        @param $new Force new connection
    */
    public function __construct($server,$type,$database,$username,$password='',$persistent=false, $new=false)
    {
        $this->connections=array();
        $this->default_db = $database;
        $this->addConnection($server,$type,$database,$username,$password,$persistent,$new);
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
        @param $new Force new connection

        When dealing with multiple connections, user the
        database name to distinguish which is to be used
    */
    public function addConnection($server,$type,$database,$username,$password='',$persistent=false,$new=false)
    {
        if (empty($type)) {
            return false;
        }

        $conn = ADONewConnection($type);
        $conn->SetFetchMode(ADODB_FETCH_BOTH);
        $connected = false;
        if (isset($this->connections[$database]) || $new) {
            $connected = $conn->NConnect($server,$username,$password,$database);
        } else {
            if ($persistent) {
                $connected = $conn->PConnect($server,$username,$password,$database);
            } else {
                $connected = $conn->Connect($server,$username,$password,$database);
            }
        }
        $this->connections[$database] = $conn;

        $this->last_connection_error = false;
        if (!$connected) {
            $this->last_connect_error = $conn->ErrorMsg();
            $conn = ADONewConnection($type);
            $conn->SetFetchMode(ADODB_FETCH_BOTH);
            $connected = $conn->Connect($server,$username,$password);
            if ($connected) {
                $this->last_connection_error = false;
                $stillok = $conn->Execute("CREATE DATABASE $database");
                if (!$stillok) {
                    $this->last_connect_error = $conn->ErrorMsg();
                    $this->connections[$database] = false;
                    return false;
                }
                $conn->Execute("USE $database");
                $this->connections[$database] = $conn;
            } else {
                $this->last_connect_error = $conn->ErrorMsg();
                $this->connections[$database] = false;
                return false;
            }
        }

        return true;
    }

    public function add_connection($server,$type,$database,$username,$password='',$persistent=false,$new=false)
    {
        return $this->addConnection($server, $type, $database, $username, $password, $persistent,$new);
    }

    /**
      Verify object is connected to the database
      @param $which_connection [string] database name (optional)
      @return [boolean] 
    */
    public function isConnected($which_connection='')
    {
        if ($which_connection == '') {
            $which_connection=$this->default_db;
        }
        if (isset($this->connections[$which_connection]) && 
            is_object($this->connections[$which_connection])) {
            return true;
        } else {
            return false;
        }
    }

    /**
      Get connection type (i.e., mysql, pdo_mysql, etc)
      @param $which_connection
      @return [string] connection type
    */
    public function connectionType($which_connection='')
    {
        if ($which_connection == '') {
            $which_connection=$this->default_db;
        }
        if ($this->isConnected($which_connection)) {
            $reflect = new \ReflectionClass($this->connections[$which_connection]);
            if (substr($reflect->name, 0, 6) == 'ADODB_') {
                return substr($reflect->name, 6);
            }
        }

        return 'unknown';
    }

    /**
      Close a database connection
      @param $which_connection
      If there are multiple connections, this is
      the database name for the connection you want to close

      This is effectively disabled. Singleton behavior
      means it isn't really necessary and the lane database
      connection should rarely (never) be disconnencted
      during a request
    */
    public function close($which_connection='', $force=false)
    {
        if (!$force) {
            return true;
        }
        if ($which_connection == '') {
            $which_connection=$this->default_db;
        }
        $con = $this->connections[$which_connection];
        unset($this->connections[$which_connection]);

        return $con->Close();
    }

    public function setDefaultDB($db_name)
    {
        /** verify connection **/
        if (!isset($this->connections[$db_name])) {
            return false;
        }

        $this->default_db = $db_name;
        if ($this->isConnected()) {
            $selected = $this->query('USE ' . $this->identifierEscape($db_name), $db_name);
            if (!$selected) {
                $this->query('CREATE DATABASE ' . $this->identifierEscape($db_name), $db_name);
                $selected = $this->query('USE ' . $this->identifierEscape($db_name), $db_name);
            }
            if ($selected) {
                $this->connections[$db_name]->database = $db_name;
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
      Change the default database on a given connection
      (i.e., mysql_select_db equivalent)
      @param $db_name [string] database name
      @param $which_connection [optional]
      @return boolean

      Using this method will recycle an existing connection
      object where as calling addConnection will create a
      new connection object.
    */
    public function selectDB($db_name, $which_connection='')
    {
        if ($which_connection == '') {
            $which_connection=$this->default_db;
        }

        /** toggle test_mode off while checking database connection **/
        $current_tm = $this->test_mode;
        $this->test_mode = false;
        $current_db = $this->defaultDatabase($which_connection);
        $this->test_mode = $current_tm;
        if ($current_db === false) {
            // no connection; cannot switch database
            return false;
        }

        $this->connections[$db_name] = $this->connections[$which_connection];

        return $this->setDefaultDB($db_name);
    }

    /**
      Execute a query
      @param $query_text The query
      @param which_connection see method close
      @return A result object on success, False on failure
    */
    public function query($query_text,$which_connection='',$params=false)
    {
        if ($this->test_mode && substr($query_text, 0, 4) != 'USE ') {
            // called when 
            $this->test_mode = false;
        }

        $which_connection = ($which_connection === '') ? $this->default_db : $which_connection;
        $con = $this->connections[$which_connection];

        $result = (!is_object($con)) ? false : $con->Execute($query_text,$params);
        if (!$result) {
            $this->logger($this->failedQueryMsg($query_text, $params, $which_connection));

            if ($this->throw_on_fail) {
                throw new \Exception($errorMsg);
            }
        }

        return $result;
    }

    protected function failedQueryMsg($query_text, $params, $which_connection)
    {
        if (is_array($query_text)) {
            $query_text = $query_text[0];
        }

        $errorMsg = $this->error($which_connection);
        $logMsg = 'Failed Query on ' . $_SERVER['PHP_SELF'] . "\n"
                . $query_text . "\n";
        if (is_array($params)) {
            $logMsg .= 'Parameters: ' . implode("\n", $params);
        }
        $logMsg .= $errorMsg . "\n";

        return $logMsg;
    }

    /**
      Potentially required for SQLite
    */
    public function end_query($result_object, $which_connection='')
    {
        if (is_object($result_object) && method_exists($result_object, 'closeCursor')) {
            $result_object->closeCursor();
        }
    }

    /**
      Execute a query on all connected databases
      @param $query_text The query
      @return An array keyed by database name. Entries
      will be result objects where queries succeeded
      and False where they failed
    */
    public function queryAll($query_text)
    {
        $ret = array();
        foreach($this->connections as $db_name => $con) {
            $ret[$db_name] = $this->query($query_text,$db_name);
        }

        return $ret;
    }

    public function query_all($query_text)
    {
        return $this->queryAll($query_text);
    }

    /**
      Escape a string for SQL-safety
      @param $query_text The string to escape
      @param $which_connection see method close()
      @return The escaped string

      Note that the return value will include start and
      end single quotes
    */
    public function escape($query_text,$which_connection='')
    {
        if ($which_connection == '') {
            $which_connection = $this->default_db;
        }

        return $this->connections[$which_connection]->qstr($query_text);
    }

    public function identifierEscape($str,$which_connection='')
    {
        if ($which_connection == '') {
            $which_connection = $this->default_db;
        }
        switch ($this->connectionType($which_connection)) {
            case 'mysql':
            case 'mysqli':
            case 'pdo_mysql':
            case 'pdo':
                return '`'.$str.'`';
            case 'mssql':
                return '['.$str.']';
            case 'pgsql':
            case 'sqlite3':
                return '"'.$str.'"';
        }

        return $str;
    }

    public function identifier_escape($str,$which_connection='')
    {
        return $this->identifierEscape($str, $which_connection);
    }

    
    /**
      Get number of rows in a result set
      @param $result_object A result set
      @param $which_connection see method close()
      @return Integer number or False if there's an error
    */
    public function numRows($result_object,$which_connection='')
    {
        if (!is_object($result_object)) {
            return false;
        }

        if ($which_connection == '') {
            $which_connection = $this->default_db;
        }

        return $result_object->RecordCount();
    }

    public function num_rows($result_object,$which_connection='')
    {
        return $this->numRows($result_object, $which_connection);
    }

    /**
      Move result cursor to specified record
      @param $result_object A result set
      @param $rownum The record index
      @param $which_connection see method close()
      @return True on success, False on failure
    */
    public function dataSeek($result_object,$rownum,$which_connection='')
    {
        if ($which_connection == '') {
            $which_connection = $this->default_db;
        }

        return $result_object->Move((int)$rownum);
    }

    public function data_seek($result_object,$rownum,$which_connection='')
    {
        return $this->dataSeek($result_object, $rownum, $which_connection);
    }
    /**
      Get number of fields in a result set
      @param $result_object A result set
      @param $which_connection see method close()
      @return Integer number or False if there's an error
    */
    public function numFields($result_object,$which_connection='')
    {
        if ($which_connection == '') {
            $which_connection = $this->default_db;
        }

        return $result_object->FieldCount();
    }

    public function num_fields($result_object,$which_connection='')
    {
        return $this->numFields($result_object, $which_connection);
    }

    /**
      Get next record from a result set
      @param $result_object A result set
      @param $which_connection see method close()
      @return An array of values
    */
    public function fetchArray($result_object,$which_connection='')
    {
        if ($this->test_mode) {
            return $this->getTestDataRow();
        }

        if (is_null($result_object)) return false;
        if ($result_object === false) return false;

        if ($which_connection == '') {
            $which_connection = $this->default_db;
        }
        $ret = $result_object->fields;
        if ($result_object) {
            $result_object->MoveNext();
        }

        return $ret;
    }

    public function fetch_array($result_object,$which_connection='')
    {
        return $this->fetchArray($result_object, $which_connection);
    }

    /**
      Get next record from a result set but as an object
      @param $result_object A result set
      @param $which_connection see method close()
      @return An object with member containing values
    */
    public function fetchObject($result_object,$which_connection='')
    {
        return $result_object->FetchNextObject(False);
    }

    public function fetch_object($result_object,$which_connection='')
    {
        return $this->fetchObject($result_object, $which_connection);
    }
    
    /**
      An alias for the method fetch_array()
    */
    public function fetchRow($result_object,$which_connection='')
    {
        return $this->fetchArray($result_object,$which_connection);
    }

    public function fetch_row($result_object,$which_connection='')
    {
        return $this->fetchArray($result_object,$which_connection);
    }

    /**
      Get the database's function for present time
      @param $which_connection see method close()
      @return The appropriate function

      For example, with MySQL this will return the
      string 'NOW()'.
    */
    public function now($which_connection='')
    {
        if ($which_connection == '') {
            $which_connection = $this->default_db;
        }

        return $this->connections[$which_connection]->sysTimeStamp;
    }

    /**
      Get the database's function for current day
      @param $which_connection see method close()
      @return The appropriate function

      For example, with MySQL this will return the
      string 'CURDATE()'.
    */
    public function curdate($which_connection='')
    {
        if ($which_connection == '') {
            $which_connection = $this->default_db;
        }

        return $this->connections[$which_connection]->sysDate;
    }

    /**
      Get the current time w/o date
      @return [string] SQL 
    */
    public function curtime($which_connection='')
    {
        if ($which_connection == '') {
            $which_connection = $this->default_db;
        }
        switch ($this->connectionType($which_connection)) {
            case 'mysql':
            case 'mysqli':
            case 'pdo_mysql':
            case 'pdo':
                return "CURTIME()";
            case 'mssql':
                return 'GETDATE()';
            case 'pgsql':
                return 'CURRENT_TIME';
            case 'sqlite3':
                return "TIME('NOW')";
        }
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
    public function datediff($date1,$date2,$which_connection='')
    {
        if ($which_connection == '') {
            $which_connection = $this->default_db;
        }
        switch ($this->connectionType($which_connection)) {
            case 'mysql':
            case 'mysqli':
            case 'pdo_mysql':
            case 'pdo':
                return "datediff($date1,$date2)";
            case 'mssql':
                return "datediff(dd,$date2,$date1)";
            case 'pgsql':
                return "extract(day from ($date2 - $date1))";
            case 'sqlite3':
                return "CAST( (JULIANDAY($date1) - JULIANDAY($date2)) AS INT)";
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

    public function monthdiff($date1,$date2,$which_connection='')
    {
        if ($which_connection == '') {
            $which_connection = $this->default_db;
        }
        switch ($this->connectionType($which_connection)) {
            case 'mysql':
            case 'mysqli':
            case 'pdo_mysql':
            case 'pdo':
                return "period_diff(date_format($date1, '%Y%m'), date_format($date2, '%Y%m'))";
            case 'mssql':
                return "datediff(mm,$date2,$date1)";
            case 'pgsql':
                return "EXTRACT(year FROM age($date2,$date1))*12 + EXTRACT(month FROM age($date2,$date1))";
        }
    }

    public function yeardiff($date1, $date2, $which_connection='')
    {
        if ($which_connection == '') {
            $which_connection = $this->default_db;
        }
        switch ($this->connectionType($which_connection)) {
            case 'mysql':
            case 'mysqli':
            case 'pdo_mysql':
            case 'pdo':
                return "DATE_FORMAT(FROM_DAYS(DATEDIFF($date1,$date2)), '%Y')+0";
            case 'mssql':
                return "extract(year from age($date1,$date))";
            case 'pgsql':
                return "extract(year from age($date1,$date))";
            case 'sqlite3':
                return "CAST( ((JULIANDAY($date1) - JULIANDAY($date2)) / 365) AS INT)";
        }

        return '0';
    }

    /**
      Get the difference between two dates in seconds
      @param $date1 First date (or datetime)
      @param $date2 Second date (or datetime)
      @param $which_connection see method close()
      @return The SQL expression

      This method currently only suport MySQL and MSSQL
    */
    public function seconddiff($date1,$date2,$which_connection='')
    {
        if ($which_connection == '') {
            $which_connection = $this->default_db;
        }
        switch ($this->connectionType($which_connection)) {
            case 'mysql':
            case 'mysqli':
            case 'pdo_mysql':
            case 'pdo':
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
    public function dateymd($date1,$which_connection='')
    {
        if ($which_connection == '') {
            $which_connection = $this->default_db;
        }
        switch ($this->connectionType($which_connection)) {
            case 'mysql':
            case 'mysqli':
            case 'pdo_mysql':
            case 'pdo':
                return "DATE_FORMAT($date1,'%Y%m%d')";
            case 'mssql':
                return "CONVERT(CHAR(11),$date1,112)";
            case 'pgsql':
                return "TO_CHAR($date1, 'YYYYMMDD')";
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
    public function convert($expr,$type,$which_connection='')
    {
        if ($which_connection == '') {
            $which_connection = $this->default_db;
        }
        switch ($this->connectionType($which_connection)) {
            case 'mysql':
            case 'mysqli':
            case 'pdo_mysql':
            case 'pdo':
                if(strtoupper($type)=='INT') {
                    $type='SIGNED';
                }
                return "CONVERT($expr,$type)";
            case 'mssql':
                return "CONVERT($type,$expr)";
            case 'pgsql':
            case 'sqlite3':
                return "CAST($expr AS $type)";
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
    public function locate($substr,$str,$which_connection='')
    {
        if ($which_connection == '') {
            $which_connection = $this->default_db;
        }
        switch ($this->connectionType($which_connection)) {
            case 'mysql':
            case 'mysqli':
            case 'pdo_mysql':
            case 'pdo':
                return "LOCATE($substr,$str)";
            case 'mssql':
                return "CHARINDEX($substr,$str)";
            case 'pgsql':
                return "POSITION($substr IN $str)";
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
    public function concat()
    {
        $args = func_get_args();
        $ret = "";
        $which_connection = $args[count($args)-1];
        if ($which_connection == '') {
            $which_connection = $this->default_db;
        }
        switch ($this->connectionType($which_connection)) {
            case 'mysql':
            case 'mysqli':
            case 'pdo_mysql':
            case 'pdo':
                $ret .= "CONCAT(";
                for($i=0;$i<count($args)-1;$i++) {
                    $ret .= $args[$i].",";
                }
                $ret = rtrim($ret,",").")";
                break;
            case 'mssql':
                for($i=0;$i<count($args)-1;$i++) {
                    $ret .= $args[$i]."+";
                }
                $ret = rtrim($ret,"+");
                break;
            case 'pgsql':
            case 'sqlite3':
                for ($i=0;$i<count($args)-1;$i++) {
                    $ret .= $args[$i] . "||";
                }
                $ret = rtrim($ret,"||");
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
    public function weekdiff($date1,$date2,$which_connection='')
    {
        if ($which_connection == '') {
            $which_connection = $this->default_db;
        }
        switch ($this->connectionType($which_connection)) {
            case 'mysql':
            case 'mysqli':
            case 'pdo_mysql':
            case 'pdo':
                return "week($date1) - week($date2)";
            case 'mssql':
                return "datediff(wk,$date2,$date1)";
            case 'pgsql':
                return "EXTRACT(WEEK FROM $date1) - EXTRACT(WEEK FROM $date2)";
        }
    }

    /**
      Get a column name by index
      @param $result_object A result set
      @param $index Integer index
      @param $which_connection see method close()
      @return The column name
    */
    public function fetchField($result_object,$index,$which_connection='')
    {
        if ($which_connection == '') {
            $which_connection = $this->default_db;
        }

        return $result_object->FetchField($index);
    }

    public function fetch_field($result_object,$index,$which_connection='')
    {
        return $this->fetchField($result_object, $index, $which_connection);
    }

    /**
      Start a transaction
      @param $which_connection see method close()
    */
    public function startTransaction($which_connection='')
    {
        if ($which_connection == '') {
            $which_connection = $this->default_db;
        }

        return $this->connections[$which_connection]->BeginTrans();
    }

    /**
      Finish a transaction
      @param $which_connection see method close()
    */
    public function commitTransaction($which_connection='')
    {
        if ($which_connection == '') {
            $which_connection = $this->default_db;
        }

        return $this->connections[$which_connection]->CommitTrans();
    }

    /**
      Abort a transaction
      @param $which_connection see method close()
    */
    public function rollbackTransaction($which_connection='')
    {
        if ($which_connection == '') {
            $which_connection = $this->default_db;
        }

        return $this->connections[$which_connection]->RollbackTrans();
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
    public function transfer($source_db,$select_query,$dest_db,$insert_query)
    {
        $result = $this->query($select_query,$source_db);
        if (!$result) {
            return false;
        }
        if ($this->numRows($result) == 0) {
            return true;
        }

        $num_fields = $this->num_fields($result,$source_db);

        $prep = $insert_query . ' VALUES(';
        $arg_sets = array();
        $big_query = $insert_query . ' VALUES ';
        $big_values = '';
        $big_args = array();

        while ($row = $this->fetch_array($result,$source_db)) {
            $big_values .= '(';
            $args = array();
            for ($i=0; $i<$num_fields; $i++) {
                $type = strtolower($this->fieldType($result,$i,$source_db));
                $row[$i] = $this->sanitizeValue($row[$i], $type);
                $args[] = $row[$i];
                if (count($arg_sets) == 0) {
                    $prep .= '?,';
                }
                $big_args[] = $row[$i];
                $big_values .= '?,';
            }
            if (count($arg_sets) == 0) {
                $prep = substr($prep, 0, strlen($prep)-1) . ')';
            }
            $arg_sets[] = $args;
            $big_values = substr($big_values, 0, strlen($big_values)-1) . '),';
        }
        $big_values = substr($big_values, 0, strlen($big_values)-1);

        /**
          Sending all records as a single query for large
          record sets may present problems depending on
          underlying DBMS and/or configuration limits.
          MySQL max_allowed_packet is probably the most
          common one.
        */
        if (count($arg_sets) < 500) {
            $big_prep = $this->prepare($big_query . $big_values, $dest_db);
            $bigR = $this->execute($big_prep, $big_args, $dest_db);
            return ($bigR) ? true : false;
        }

        $ret = true;
        $this->startTransaction($dest_db);
        $statement = $this->prepare($prep, $dest_db);
        foreach ($arg_sets as $args) {
            if (!$this->execute($statement, $args, $dest_db)) {
                $ret = false;
                break;
            }
        }
        if ($ret === true) {
            $this->commitTransaction($dest_db);
        } else {
            $this->rollbackTransaction($dest_db);
        }

        return $ret;
    }

    private function sanitizeValue($val, $type)
    {
        $unquoted = array("money"=>1,"real"=>1,"numeric"=>1,
            "float4"=>1,"float8"=>1,"bit"=>1);
        $strings = array("varchar"=>1,"nvarchar"=>1,"string"=>1,
            "char"=>1, 'var_string'=>1);
        $dates = array("datetime"=>1);

        if ($val == "" && strstr(strtoupper($type),"INT")) {
            $val = 0;    
        } elseif ($val == "" && isset($unquoted[$type])) {
            $val = 0;    
        }
        if (isset($dates[$type])) {
            $val = $this->cleanDateTime($val);
        } elseif (isset($strings[$type])) {
            $val = str_replace("'","''",$val);
        }

        return $val;
    }

    /**
      Get column type
      @param $result_object A result set
      @param $index Integer index
      @param $which_connection see method close()
      @return The column type
    */
    public function fieldType($result_object,$index,$which_connection='')
    {
        if ($which_connection == '') {
            $which_connection = $this->default_db;
        }
        $fld = $result_object->FetchField($index);

        return $fld->type;
    }

    public function field_type($result_object,$index,$which_connection='')
    {
        return $this->fieldType($result_object, $index, $which_connection);
    }

    /**
      Alias of method fetchField()
    */
    public function field_name($result_object,$index,$which_connection='')
    {
        $field = $this->fetchField($result_object, $index, $which_connection);

        if (is_object($field) && property_exists($field, 'name')) {
            return $field->name;
        } else {
            return '';
        }
    }

    /**
      Get day of week number
      @param $field A date expression
      @param $which_connection see method close()
      @return The SQL expression

      This method currently only suport MySQL and MSSQL
    */
    public function dayofweek($field,$which_connection='')
    {
        if ($which_connection == '') {
            $which_connection = $this->default_db;
        }
        // ado is inconsistent
        //$conn = $this->connections[$which_connection];
        //return $conn->SQLDate("w",$field);
        switch ($this->connectionType($which_connection)) {
            case 'mysql':
            case 'mysqli':
            case 'pdo_mysql':
            case 'pdo':
                return "DATE_FORMAT($field,'%w')+1";
            case 'mssql':
                return "DATEPART(dw,$field)";
            case 'pgsql':
                return "EXTRACT(dow from $col";
            case 'sqlite3':
                return "(7 - ROUND(JULIANDAY(DATETIME('now','weekday 0')) - JULIANDAY($col))) % 7";
        }

        return false;
    }

    /**
      Get the hour from a datetime
      @param $field A datetime expression
      @param $which_connection see method close()
      @return The SQL expression
    */
    public function hour($field,$which_connection='')
    {
        if ($which_connection == '') {
            $which_connection = $this->default_db;
        }
        $conn = $this->connections[$which_connection];

        return $conn->SQLDate("H",$field);
    }

    /**
      Get the week number from a datetime
      @param $field A datetime expression
      @param $which_connection see method close()
      @return The SQL expression
    */
    public function week($field,$which_connection='')
    {
        if ($which_connection == '') {
            $which_connection = $this->default_db;
        }
        $conn = $this->connections[$which_connection];

        return $conn->SQLDate("W",$field);
    }

    /**
      Reformat a datetime to YYYY-MM-DD HH:MM:SS
      @param $str A datetime string
      @return The reformatted string

      This is a utility method to support transfer()
    */
    protected function cleanDateTime($str)
    {
        $stdFmt = "/(\d\d\d\d)-(\d\d)-(\d\d) (\d+?):(\d\d):(\d\d)/";
        if (preg_match($stdFmt,$str,$group)) {
            return $str;    
        }

        $timestamp = strtotime($str);
        if ($timestamp === false) {
            return $str;
        } else {
            return date('Y-m-d H:i:s', $timestamp);
        }
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
    public function tableExists($table_name,$which_connection='')
    {
        if ($which_connection == '') {
            $which_connection=$this->default_db;
        }

        /**
          Check whether the definition is in cache
        */
        if (isset($this->structure_cache[$which_connection]) && isset($this->structure_cache[$which_connection][$table_name])) {
            return true;
        }

        $conn = $this->connections[$which_connection];
        if (!is_object($conn)) {
            return false;
        }
        $cols = $conn->MetaColumns($table_name);
        if ($cols === false) {
            return false;
        }

        return true;
    }

    public function table_exists($table_name,$which_connection='')
    {
        return $this->tableExists($table_name, $which_connection);
    }

    public function isView($table_name, $which_connection='')
    {
        if ($which_connection == '') {
            $which_connection=$this->default_db;
        }

        if (!$this->tableExists($table_name, $which_connection)) {
            return false;
        }

        $conn = $this->connections[$which_connection];
        $views = $conn->MetaTables('VIEW');
        $lc_views = array();
        $lc_name = strtolower($table_name);
        foreach ($views as $view) {
            $lc_views[] = strtolower($view);
        }

        if (in_array($table_name, $views) || in_array($lc_name, $lc_views)) {
            return true;
        } else {
            return false;
        }
    }

    /**
      Get SQL definition of a view
      @param $view_name string name
      @param $which_connection [optional]
      @return [string] SQL statement or [boolean] false
    */
    public function getViewDefinition($view_name, $which_connection='')
    {
        $which_connection = ($which_connection === '') ? $this->default_db : $which_connection;

        if (!$this->isView($view_name, $which_connection)) {
            return false;
        }

        switch ($this->connectionType($which_connection)) {
            case 'mysql':
            case 'mysqli':
            case 'pdo_mysql':
            case 'pdo':
                $result = $this->query("SHOW CREATE VIEW " . $this->identifierEscape($view_name, $which_connection), $which_connection);
                if ($this->numRows($result) > 0) {
                    $row = $this->fetchRow($result);
                    return $row[1];
                } else {
                    return false;
                }
                break;
            case 'mssql':
                $result = $this->query("SELECT OBJECT_DEFINITION(OBJECT_ID('$view_name'))", $which_connection);
                if ($this->numRows($result) > 0) {
                    $row = $this->fetchRow($result);
                    return $row[0];
                } else {
                    return false;
                }
                break;
            case 'pgsql':
                $result = $this->query("SELECT oid FROM pg_class
                        WHERE relname LIKE '$view_name'",
                        $which_connection);
                if ($this->numRows($result) > 0) {
                    $row = $this->fetchRow($result);
                    $defQ = sprintf('SELECT pg_get_viewdef(%d)', $row['oid']);
                    $defR = $this->query($defQ, $which_connection);
                    if ($this->numRows($defR) > 0) {
                        $def = $this->fetchRow($defR);
                        return $def[0];
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
                break;
            case 'sqlite3':
                $result = $this->query("SELECT sql FROM sqlite_master
                        WHERE type IN ('view') AND name='$table_name'",
                        $which_connection);
                $ret = false;
                if ($this->numRows($result) > 0) {
                    $row = $this->fetchRow($result);
                    $ret = $row['sql'];
                }
                $this->end_query($result, $which_connection);
                return $ret;
                break;
        }

        return false;
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
    public function tableDefinition($table_name,$which_connection='')
    {
        if ($which_connection == '') {
            $which_connection=$this->default_db;
        }

        /**
          Check whether the definition is in cache
        */
        if (isset($this->structure_cache[$which_connection]) && isset($this->structure_cache[$which_connection][$table_name])) {
            return $this->structure_cache[$which_connection][$table_name];
        }

        $conn = $this->connections[$which_connection];
        $cols = $conn->MetaColumns($table_name);

        $return = array();
        if (is_array($cols)) {
            foreach($cols as $c) {
                $return[$c->name] = $c->type;
            }
            return $return;
        }

        return false;
    }

    public function table_definition($table_name,$which_connection='')
    {
        return $this->tableDefinition($table_name, $which_connection);
    }

    /**
      More detailed table definition
       @param $table_name The table's name
       @param which_connection see method close
       @return
        - array of column name => info array
        - the info array has keys: 
            * type (string)
            * increment (boolean OR null if unknown)
            * primary_key (boolean OR null if unknown)
            * default (value OR null)
    */
    public function detailedDefinition($table_name,$which_connection='')
    {
        $which_connection = ($which_connection === '') ? $this->default_db : $which_connection;
        $conn = $this->connections[$which_connection];
        $cols = $conn->MetaColumns($table_name);

        $return = array();
        if (is_array($cols)) {
            foreach($cols as $c) {
                $return[$c->name] = $this->columnToArray($c);
            }

            return $return;
        }

        return false;
    }

    private function columnToArray($col)
    {
        $info = array();
        $type = strtoupper($col->type);
        if (property_exists($col, 'max_length') && $col->max_length != -1 && substr($type, -3) != 'INT') {
            if (property_exists($col, 'scale') && $col->scale) {
                $type .= '(' . $col->max_length . ',' . $col->scale . ')';
            } else {
                $type .= '(' . $col->max_length . ')';
            }
        }
        if (property_exists($col, 'unsigned') && $col->unsigned) {
            $type .= ' UNSIGNED';
        }
        $info['type'] = $type;
        if (property_exists($col, 'auto_increment') && $col->auto_increment) {
            $info['increment'] = true;
        } else if (property_exists($col, 'auto_increment') && !$col->auto_increment) {
            $info['increment'] = false;
        } else {
            $info['increment'] = null;
        }
        if (property_exists($col, 'primary_key') && $col->primary_key) {
            $info['primary_key'] = true;
        } else if (property_exists($col, 'primary_key') && !$col->primary_key) {
            $info['primary_key'] = false;
        } else {
            $info['primary_key'] = null;
        }

        if (property_exists($col, 'default_value') && $col->default_value !== 'NULL' && $col->default_value !== null) {
            $info['default'] = $col->default_value;
        } else {
            $info['default'] = null;
        }

        return $info;
    }

    /**
       Get list of tables/views
       @param which_connection see method close
    */
    public function getTables($which_connection='')
    {
        if ($which_connection == '') {
            $which_connection=$this->default_db;
        }
        $conn = $this->connections[$which_connection];

        return $conn->MetaTables();
    }

    public function get_tables($which_connection='')
    {
        return $this->getTables($which_connection);
    }

    /**
      Get current default database
      for a given connection
      @param which_connection see method close
      @return [string] database name
        or [boolean] false on failure
    */
    public function defaultDatabase($which_connection='')
    {
        if ($which_connection == '') {
            $which_connection=$this->default_db;
        }

        if (count($this->connections) == 0) {
            return false;
        }

        $query ='';
        switch ($this->connectionType($which_connection)) {
            case 'mysql':
            case 'mysqli':
            case 'pdo_mysql':
            case 'pdo':
                $query = 'SELECT DATABASE() as dbname';
                break;
            case 'mssql':
                $query = 'SELECT DB_NAME() as dbname';
                break;
            case 'pgsql':
                $query = 'SELECT CURRENT_DATABASE() AS dbname';
                break;
        }

        $ret = false;
        $try = $this->query($query, $which_connection);
        if ($try && $this->num_rows($try) > 0) {
            $row = $this->fetch_row($try);
            $ret = $row['dbname'];
        }

        return $ret;
    }

    /**
      Get database's currency type
      @param which_connection see method close
      @return The SQL type
    */
    public function currency($which_connection='')
    {
        if ($which_connection == '') {
            $which_connection=$this->default_db;
        }
        switch ($this->connectionType($which_connection)) {
            case 'mysql':
            case 'mysqli':
            case 'pdo_mysql':
            case 'pdo':
                return 'decimal(10,2)';
            case 'mssql':
            case 'pgsql':
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
    public function addSelectLimit($query,$int_limit,$which_connection='')
    {
        if ($which_connection == '') {
            $which_connection=$this->default_db;
        }
        switch ($this->connectionType($which_connection)) {
            case 'mysql':
            case 'mysqli':
            case 'pdo_mysql':
            case 'pdo':
            case 'pgsql':
                return sprintf("%s LIMIT %d",$query,$int_limit);
            case 'mssql':
                return str_ireplace("SELECT ","SELECT TOP $int_limit ",$query);
        }

        return $query;
    }

    public function add_select_limit($query,$int_limit,$which_connection='')
    {
        return $this->addSelectLimit($query, $int_limit, $which_connection);
    }

    /**
      Get database scope separator
      @param which_connection see method close
      @return String separator
    */
    public function sep($which_connection='')
    {
        if ($which_connection == '') {
            $which_connection=$this->default_db;
        }
        switch ($this->connectionType($which_connection)) {
            case 'mysql':
            case 'mysqli':
            case 'pdo_mysql':
            case 'pdo':
            case 'pgsql':
            case 'sqlite3':
                return ".";
            case 'mssql':
                return ".dbo.";
        }

        return ".";
    }

    /**
      Get name of database driver 
      @param which_connection see method close
      @return String name
    */
    public function dbmsName($which_connection='')
    {
        if ($which_connection == '') {
            $which_connection=$this->default_db;
        }

        return $this->connectionType($which_connection);
    }

    public function dbms_name($which_connection='')
    {
        return $this->dbmsName($which_connection);
    }
    /**
      Get last error message
      @param which_connection see method close
      @return The message
    */
    public function error($which_connection='')
    {
        if ($which_connection == '') {
            $which_connection=$this->default_db;
        }
        $con = $this->connections[$which_connection];

        if (!is_object($con)) {
            if ($this->last_connect_error) {
                return $this->last_connect_error;
            } else {
                return 'No database connection';
            }
        }

        return $con->ErrorMsg();
    }

    /**
      Get auto incremented ID from last insert
      @param which_connection see method close
      @return The new ID value
    */
    public function insertID($which_connection='')
    {
        if ($which_connection == '') {
            $which_connection=$this->default_db;
        }
        $con = $this->connections[$which_connection];

        return $con->Insert_ID();
    }

    public function insert_id($which_connection='')
    {
        return $this->insertID($which_connection);
    }

    /**
      Check how many rows the last query affected
      @param which_connection see method close
      @returns Number of rows
    */
    public function affectedRows($which_connection='')
    {
        if ($which_connection == '') {
            $which_connection=$this->default_db;
        }
        $con = $this->connections[$which_connection];

        return $con->Affected_Rows();
    }

    public function affected_rows($which_connection='')
    {
        return $this->affectedRows($which_connection);
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
    public function smartInsert($table_name,$values,$which_connection='')
    {
        $which_connection = ($which_connection === '') ? $this->default_db : $which_connection;

        $t_def = $this->tableDefinition($table_name, $which_connection);
        if ($t_def === false) {
            return false;
        }

        $cols = "(";
        $vals = "(";
        $args = array();
        foreach ($values as $k=>$v) {
            if (isset($t_def[$k])) {
                $vals .= '?,';
                $args[] = $v;
                $cols .= $this->identifierEscape($k, $which_connection) . ',';
            } else {
                // implication: column isn't in the table
            }
        }
        $cols = substr($cols,0,strlen($cols)-1).")";
        $vals = substr($vals,0,strlen($vals)-1).")";
        $insertQ = "INSERT INTO $table_name $cols VALUES $vals";
        $insertP = $this->prepare($insertQ, $which_connection);
        $ret = $this->execute($insertP, $args, $which_connection);

        return $ret;
    }

    public function smart_insert($table_name,$values,$which_connection='')
    {
        return $this->smartInsert($table_name, $values, $which_connection);
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
    public function smartUpdate($table_name,$values,$where_clause,$which_connection='')
    {
        $which_connection = ($which_connection === '') ? $this->default_db : $which_connection;

        $t_def = $this->tableDefinition($table_name, $which_connection);
        if ($t_def === false) {
            return false;
        }

        $sets = "";
        $args = array();
        foreach($values as $k=>$v) {
            if (isset($t_def[$k])) {
                $sets .= $this->identifierEscape($k) . ' = ?,';
                $args[] = $v;
            } else {
                // implication: column isn't in the table
            }
        }
        $sets = rtrim($sets,",");
        $upQ = "UPDATE $table_name SET $sets WHERE $where_clause";
        $upP = $this->prepare($upQ, $which_connection);

        $ret = $this->execute($upP, $args, $which_connection);

        return $ret;
    }

    public function smart_update($table_name,$values,$where_clause,$which_connection='')
    {
        return $this->smartUpdate($table_name, $values, $where_clause, $which_connection);
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
    public function prepare($sql,$which_connection="")
    {
        if ($which_connection == '') {
            $which_connection=$this->default_db;
        }
        $con = $this->connections[$which_connection];

        return $con->Prepare($sql);
    }

    public function prepare_statement($sql,$which_connection="")
    {
        return $this->prepare($sql, $which_connection);
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
    public function execute($sql, $input_array=array(), $which_connection='')
    {
        $this->test_mode = false;

        if ($which_connection == '') {
            $which_connection=$this->default_db;
        }
        if (!is_array($input_array)) {
            $input_array = array($input_array);
        }

        return $this->query($sql,$which_connection,$input_array);
    }

    public function exec_statement($sql, $input_array=array(), $which_connection='')
    {
        return $this->execute($sql, $input_array, $which_connection);
    }

    /**
      Get a value directly from a query without verifying
      rows exist and fetching one
      @param $sql a value from SQLManager::prepare_statement
      @param $input_array an array of values
      @param which_connection see method close
      @return [mixed] value or [boolean] false
        - throws an exception if exceptions are enabled
    */
    public function getValue($sql, $input_array=array(), $which_connection='')
    {
        $res = $this->execute($sql, $input_array, $which_connection);
        if ($res && $this->numRows($res) > 0) {
            $row = $this->fetchRow($res);
            return $row[0];
        } else {
            if ($this->throw_on_fail) {
                throw new \Exception('Record not found');
            } else {
                return false;
            }
        }
    }

    /**
      Get a row directly from a query without verifying
      rows exist and fetching one
      @param $sql a value from SQLManager::prepare_statement
      @param $input_array an array of values
      @param which_connection see method close
      @return [mixed] value or [boolean] false
        - throws an exception if exceptions are enabled
    */
    public function getRow($sql, $input_array=array(), $which_connection='')
    {
        $res = $this->execute($sql, $input_array, $which_connection);
        if ($res && $this->numRows($res) > 0) {
            $row = $this->fetchRow($res);
            return $row;
        } else {
            if ($this->throw_on_fail) {
                throw new \Exception('Record not found');
            } else {
                return false;
            }
        }
    }

    /** 
      See if a datetime is on a given date using BETWEEN    
      @param $col datetime expression
      @param $dateStr String date
      @return SQL BETWEEN comparision

      Which MySQL partitioning by date this is MUCH
      faster than using datediff($col,$dateStr)==0
    */
    public function dateEquals($col,$dateStr)
    {
        $dateStr = trim($dateStr,"'");
        $seconds = strtotime($dateStr);
        if ($seconds === false) {
            $seconds = time();
        }
        $base = date("Y-m-d",$seconds);
    
        return sprintf("(%s BETWEEN '%s 00:00:00' AND '%s 23:59:59')",
            $col,$base,$base);
    }

    public function date_equals($col,$dateStr)
    {
        return $this->dateEquals($col, $dateStr);
    }

    /* compat layer; mimic functions of Brad's mysql class */
    public function get_result($host,$user,$pass,$data_base,$query)
    {
        return $this->query($query);
    }

    public function aff_rows($result)
    {
        return $this->affected_rows($result);
    }


    /**
      Assign a query log
      @param [mixed] $log
        - an [object] implementing the PSR3 log interface
        - a [string] filename
    */
    public function setQueryLog($log)
    {
        $this->QUERY_LOG = $log;
    }

    /**
       Log a string to the query log.
       @param $str The string
       @return [boolean] success
    */  
    public function logger($str)
    {
        if (is_object($this->QUERY_LOG) && method_exists($this->QUERY_LOG, 'debug')) {
            $this->QUERY_LOG->debug($str);

            return true;
        } elseif (is_string($this->QUERY_LOG)) {
            $fptr = @fopen($this->QUERY_LOG, 'a');
            if ($fptr) {
                fwrite($fptr, date('r') . ': ' . $str);
                fclose($fptr);

                return true;
            } else {
                return false;
            }
        }

        return false;
    }

    /**
      Get column names common to both tables
      @param $table1 [string] table name
      @param $table2 [string] table name
      $which_connection [optiona] see close()
      @return [array] of [string] column names
    */
    public function matchingColumns($table1, $table2, $which_connection='')
    {
        if ($which_connection == '') {
            $which_connection=$this->default_db;
        }
        
        $definition1 = $this->table_definition($table1, $which_connection);
        $definition2 = $this->table_definition($table2, $which_connection);
        if (!is_array($definition1) || ! is_array($definition2)) {
            return array();
        }

        $matches = array();
        foreach($definition1 as $col_name => $info) {
            if (isset($definition2[$col_name])) {
                $matches[] = $col_name;
            }
        }

        return $matches;
    }

    /**
      Get list of columns that exist in both tables
      @param $table1 [string] name of first table
      @param $which_connection1 [string] name of first database connection
      @param $table2 [string] name of second table
      @param $which_connection2 [string] name of second database connection
      @return [string] list of column names or [boolean] false
    */
    public function getMatchingColumns($table1, $which_connection1, $table2, $which_connection2)
    {
        $ret = '';
        $def1 = $this->tableDefinition($table1, $which_connection1);
        $def2 = $this->tableDefinition($table2, $which_connection2);
        foreach ($def1 as $column_name => $info) {
            if (isset($def2[$column_name])) {
                $ret .= $column_name . ',';
            }
        }
        if ($ret === '') {
            return false;
        } else {
            return substr($ret, 0, strlen($ret)-1);
        }
    }

    /**
      Enable or disable exceptions on failed queries
      @param $mode boolean
    */
    public function throwOnFailure($mode)
    {
        $this->throw_on_fail = $mode;
    }

    /**
      Create temporary table
      @param name string temporary table name
      @param source_table string source table name
      @param which_connection see method close
      @return String separator
    */
    public function temporaryTable($name, $source_table, $which_connection='')
    {
        if ($which_connection == '') {
            $which_connection=$this->default_db;
        }
        switch ($this->connectionType($which_connection)) {
            case 'mysql':
            case 'mysqli':
            case 'pdo_mysql':
            case 'pdo':
            case 'pgsql':
                $created = $this->query('
                    CREATE TEMPORARY TABLE ' . $name . '
                    LIKE ' . $source_table
                );
                return $created ? $name : false;
            case 'mssql':
                if (strstr($name, '.dbo.')) {
                    list($schema, $table) = explode('.dbo.', $name, 2);
                    $name = $schema . '.dbo.#' . $name;
                } else {
                    $name = '#' . $name;
                }
                $created = $this->query('
                    CREATE TABLE ' . $name . '
                    LIKE ' . $source_table
                );
                return $created ? $name : false;
        }

        return false;
    }

    /**
      Test data is for faking queries.
      Setting the test data then running
      a unit test means the test will get
      predictable results.
    */

    private $test_data = array();
    private $test_counter = 0;
    private $test_mode = false;
    public function setTestData($records)
    {
        $this->test_data = $records;
        $this->test_counter = 0;
        $this->test_mode = true;
    }

    public function getTestDataRow()
    {
        if (isset($this->test_data[$this->test_counter])) {
            $next = $this->test_data[$this->test_counter];
            $this->test_counter++;
            return $next;
        } else {
            $this->test_mode = false; // no more test data
            return false;
        }
    }

    /**
      Cache a table definition to avoid future lookups
    */
    public function cacheTableDefinition($table, $definition, $which_connection='')
    {
        if ($which_connection == '') {
            $which_connection=$this->default_db;
        }
        if (!isset($this->structure_cache[$which_connection])) {
            $this->structure_cache[$which_connection] = array();
        }
        $this->structure_cache[$which_connection][$table] = $definition;

        return true;
    }

    /**
      Clear cached table definitions
    */
    public function clearTableCache($which_connection='')
    {
        if ($which_connection == '') {
            $which_connection=$this->default_db;
        }
        $this->structure_cache[$which_connection] = array();

        return true;
    }
}

