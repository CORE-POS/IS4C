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
use COREPOS\common\sql\CharSets;

if (!function_exists("ADONewConnection")) {
    include(dirname(__FILE__).'/adodb5/adodb.inc.php');
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
    /**
     Logging object (PSR-3)
    */
    private $QUERY_LOG; 

    /**
     In debug mode all queries are logged
     even if they succeed
    */
    private $debug_mode = false;

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
        $this->addConnection($server,$type,$database,$username,$password,$persistent,$new);
        if ($this->isConnected($database)) {
            $this->default_db = $database;
            $adapter = $this->getAdapter(strtolower($type));
            $this->query($adapter->useNamedDB($database));
        }
    }

    private function isPDO($type)
    {
        return (substr(strtolower($type), 0, 4) === 'pdo_');
    }

    private function setConnectTimeout($conn, $type)
    {
        if (strtolower($type) === 'mysqli') {
            $conn->optionFlags[] = array(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
        } elseif (strtolower($type) === 'pdo_mysql') {
            $this->save_socket_timeout = ini_get('default_socket_timeout');
            ini_set('default_socket_timeout', 5);
        }

        return $conn;
    }

    private function clearConnectTimeout($conn, $type)
    {
        if (strtolower($type) === 'pdo_mysql') {
            ini_set('default_socket_timeout', $this->save_socket_timeout);
        }

        return $conn;
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
        } elseif (strtolower($type) == 'postgres9') {
            // value here is really schema. Database name must match user name.
            $savedDB = $database;
            $database = $username;
        } elseif (strtolower($type) == 'mysql' && version_compare(PHP_VERSION, '7.0.0') >= 0) {
            $type = function_exists('mysqli_connect') ? 'mysqli' : 'pdo_mysql';
        }

        $conn = ADONewConnection($this->isPDO($type) ? 'pdo' : $type);
        $conn->SetFetchMode(ADODB_FETCH_BOTH);
        $conn = $this->setConnectTimeout($conn, $type);
        $connected = false;
        if (isset($this->connections[$database]) || $new) {
            $connected = $conn->NConnect($this->getDSN($server,$type,$database),$username,$password, $database);
        } elseif ($persistent) {
            $connected = $conn->PConnect($this->getDSN($server,$type,$database),$username,$password,$database);
        } else {
            $connected = $conn->Connect($this->getDSN($server,$type,$database),$username,$password,$database);
        }
        $conn = $this->clearConnectTimeout($conn, $type);

        if (strtolower($type) == 'postgres9') {
            $database = $savedDB;
        }
        $this->connections[$database] = $conn;

        $this->last_connect_error = false;
        if (!$connected) {
            $this->last_connect_error = $conn->ErrorMsg();
            return $this->connectAndCreate($server, $type, $username, $password, $database);
        }

        return true;
    }

    /**
      PDO drivers expect a dsn string rather than just a hostname
      This returns a dsn string for those drivers or just
      the $server value unchanged for other drivers.
    */
    private function getDSN($server, $type, $database)
    {
        if ($this->isPDO($type)) {
            $dsn = substr(strtolower($type), 4) . ':';
            if (strstr($server, ':')) {
                list($host, $port) = explode(':', $server, 2);
                $dsn .= 'host=' . $host . ';port=' . $port; 
            } else {
                $dsn .= 'host=' . $server;
            }
            if ($database) {
                $dsn .= ';dbname=' . $database;
            }

            return $dsn;
        } else {
            return $server;
        }
    }

    /**
      Try connecting without specifying a database
      and then creating the requested database
    */
    private function connectAndCreate($server, $type, $username, $password, $database)
    {
        $conn = ADONewConnection($this->isPDO($type) ? 'pdo' : $type);
        $conn->SetFetchMode(ADODB_FETCH_BOTH);
        $conn = $this->setConnectTimeout($conn, $type);
        $connected = $conn->Connect($this->getDSN($server,$type,false),$username,$password,$database);
        $conn = $this->clearConnectTimeout($conn, $type);
        if ($connected) {
            $this->last_connect_error = false;
            $adapter = $this->getAdapter(strtolower($type));
            $stillok = $conn->Execute($adapter->createNamedDB($database));
            if (!$stillok) {
                $this->last_connect_error = $conn->ErrorMsg();
                $this->connections[$database] = false;
                return false;
            }
            $conn->Execute($adapter->useNamedDB($database));
            $conn->SelectDB($database);
            $this->connections[$database] = $conn;
            return true;
        } else {
            $this->last_connect_error = $conn->ErrorMsg();
            $this->connections[$database] = false;
            return false;
        }
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

    /**
      Abstraction leaks here. Changing the connection's default DB
      or SCHEMA via query works but calling SelectDB on the underying
      ADOdb object is sometimes necessary to update the object's
      internal state appropriately. This makes postgres a special case
      where the SCHEMA should change but DB should not.
    */
    private function setDBorSchema($db_name)
    {
        if (strtolower($this->connectionType($db_name)) === 'postgres9') {
            $adapter = $this->getAdapter($this->connectionType($db_name));
            $selectDbQuery = $adapter->useNamedDB($db_name);
            return $this->connections[$db_name]->Execute($selectDbQuery);
        }

        return $this->connections[$db_name]->SelectDB($db_name);
    }

    public function setDefaultDB($db_name)
    {
        /** verify connection **/
        if (!is_string($db_name) || !isset($this->connections[$db_name])) {
            return false;
        }

        $this->default_db = $db_name;
        if ($this->isConnected()) {
            $selected = $this->setDBorSchema($db_name);
            if (!$selected) {
                $selected = $this->setDBorSchema($db_name);
            }
            if ($selected) {
                $this->connections[$db_name]->database = $db_name;
                return true;
            }
        }

        return false;
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

        $current_db = $this->defaultDatabase($which_connection);
        if ($current_db === false) {
            // no connection; cannot switch database
            return false;
        }

        $this->connections[$db_name] = $this->connections[$which_connection];

        return $this->setDefaultDB($db_name);
    }

    private function getNamedConnection($which_connection)
    {
        $which_connection = ($which_connection === '') ? $this->default_db : $which_connection;
        return isset($this->connections[$which_connection]) ? $this->connections[$which_connection] : null;
    }

    /**
      Execute a query
      @param $query_text The query
      @param which_connection see method close
      @return A result object on success, False on failure
    */
    public function query($query_text,$which_connection='',$params=false)
    {
        $con = $this->getNamedConnection($which_connection);

        $result = (!is_object($con)) ? false : $con->Execute($query_text,$params);
        if (!$result) {
            $errorMsg = $this->failedQueryMsg($query_text, $params, $which_connection);
            $this->logger($errorMsg);

            if ($this->throw_on_fail) {
                throw new \Exception($errorMsg);
            }
        } elseif ($this->debug_mode) {
            $logMsg = 'Successful query on ' . filter_input(INPUT_SERVER, 'PHP_SELF') . "\n"
                . $query_text . "\n"
                . (is_array($params) ? 'Parameters: ' . implode("\n", $params) : '');
            $this->logger($logMsg);
        }

        return $result;
    }

    protected function failedQueryMsg($query_text, $params, $which_connection)
    {
        if (is_array($query_text)) {
            $query_text = $query_text[0];
        }

        $errorMsg = $this->error($which_connection);
        $logMsg = 'Failed Query on ' . filter_input(INPUT_SERVER, 'PHP_SELF') . "\n"
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
    public function endQuery($result_object, $which_connection='')
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
        $ret = true;
        foreach ($this->connections as $db_name => $con) {
            $ret = $this->query($query_text,$db_name);
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
    public function escape($query_text,$which_connection='')
    {
        if ($which_connection == '') {
            $which_connection = $this->default_db;
        }

        return $this->connections[$which_connection]->qstr($query_text);
    }

    public function identifierEscape($str,$which_connection='')
    {
        $which_connection = $which_connection === '' ? $this->default_db : $which_connection;
        $adapter = $this->getAdapter($this->connectionType($which_connection));
        return $adapter->identifierEscape($str);
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

    /**
      Get next record from a result set
      @param $result_object A result set
      @param $which_connection see method close()
      @return An array of values
    */
    public function fetchArray($result_object,$which_connection='')
    {
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

    /**
      An alias for the method fetchArray()
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
        $which_connection = $which_connection === '' ? $this->default_db : $which_connection;
        $adapter = $this->getAdapter($this->connectionType($which_connection));
        return $adapter->curtime();
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
        $which_connection = $which_connection === '' ? $this->default_db : $which_connection;
        $adapter = $this->getAdapter($this->connectionType($which_connection));
        return $adapter->datediff($date1, $date2);
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
        $which_connection = $which_connection === '' ? $this->default_db : $which_connection;
        $adapter = $this->getAdapter($this->connectionType($which_connection));
        return $adapter->monthdiff($date1, $date2);
    }

    public function yeardiff($date1, $date2, $which_connection='')
    {
        $which_connection = $which_connection === '' ? $this->default_db : $which_connection;
        $adapter = $this->getAdapter($this->connectionType($which_connection));
        return $adapter->yeardiff($date1, $date2);
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
        $which_connection = $which_connection === '' ? $this->default_db : $which_connection;
        $adapter = $this->getAdapter($this->connectionType($which_connection));
        return $adapter->seconddiff($date1, $date2);
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
        $which_connection = $which_connection === '' ? $this->default_db : $which_connection;
        $adapter = $this->getAdapter($this->connectionType($which_connection));
        return $adapter->dateymd($date1);
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
        $which_connection = $which_connection === '' ? $this->default_db : $which_connection;
        $adapter = $this->getAdapter($this->connectionType($which_connection));
        return $adapter->convert($expr, $type);
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
        $which_connection = $which_connection === '' ? $this->default_db : $which_connection;
        $adapter = $this->getAdapter($this->connectionType($which_connection));
        return $adapter->locate($substr, $str);
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
        $which_connection = array_pop($args);
        $adapter = $this->getAdapter($this->connectionType($which_connection));
        return $adapter->concat($args);
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
        $which_connection = $which_connection === '' ? $this->default_db : $which_connection;
        $adapter = $this->getAdapter($this->connectionType($which_connection));
        return $adapter->weekdiff($date1, $date2);
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

        $numFields = $this->numFields($result,$source_db);

        $prep = $insert_query . ' VALUES(';
        $arg_sets = array();
        $big_query = $insert_query . ' VALUES ';
        $big_values = '';
        $big_args = array();

        while ($row = $this->fetchArray($result,$source_db)) {
            $big_values .= '(';
            $args = array();
            for ($i=0; $i<$numFields; $i++) {
                $type = strtolower($this->fieldType($result,$i,$source_db));
                $row[$i] = $this->sanitizeValue($row[$i], $type);
                $args[] = $row[$i];
                $big_args[] = $row[$i];
                $big_values .= '?';
                  /**
                  Since we can be dealing with very large strings here, it
                  could be more memory efficient to avoid adding the last
                  comma just to remove it again with a substr() call.
                */
                if ($i < $numFields-1) {
                    $big_values .= ',';
                }
            }
            $arg_sets[] = $args;
            /**
              If the limit's exceeded and the data won't be
              sent as one giant query there's no need to continue
              building components of that query.
            */
            if (count($arg_sets) < 500) {
                $big_values .= '),';
            } else {
                $big_values = '';
                $big_args = array();
            }
        }
        $big_values = substr($big_values, 0, strlen($big_values)-1);
        $prep .= str_repeat('?,', count($arg_sets[0]));
        $prep = substr($prep, 0, strlen($prep)-1) . ')';

        /**
          Sending all records as a single query for large
          record sets may present problems depending on
          underlying DBMS and/or configuration limits.
          MySQL max_allowed_packet is probably the most
          common one.
        */
        if (count($arg_sets) < 500) {
            $this->lockTimeout(5, $dest_db);
            $big_prep = $this->prepare($big_query . $big_values, $dest_db);
            $bigR = $this->execute($big_prep, $big_args, $dest_db);
            return ($bigR) ? true : false;
        } else {
            return $this->executeAsTransaction($prep, $arg_sets, $dest_db);
        }
    }

    private function lockTimeout($seconds, $which_connection)
    {
        $which_connection = $which_connection === '' ? $this->default_db : $which_connection;
        $adapter = $this->getAdapter($this->connectionType($which_connection));

        return $this->query($adapter->setLockTimeout($seconds), $which_connection);
    }


    /**
      Execute a statement repeatedly as transaction.
      Commit if all statements succeed.
      Otherwise roll back.
    */
    public function executeAsTransaction($query, $arg_sets, $which_connection='')
    {
        $which_connection = $which_connection === '' ? $this->default_db : $which_connection;
        $ret = true;
        $statement = $this->prepare($query, $which_connection);
        $this->startTransaction($which_connection);
        $this->lockTimeout(5, $which_connection);
        foreach ($arg_sets as $args) {
            if (!$this->execute($statement, $args, $which_connection)) {
                $ret = false;
                break;
            }
        }
        if ($ret === true) {
            $this->commitTransaction($which_connection);
        } else {
            $this->rollbackTransaction($which_connection);
        }

        return $ret;
    }

    private function isIntegerType($type)
    {
        foreach (array('INT', 'LONG', 'SHORT') as $str) {
            if (strstr(strtoupper($type), $str)) {
                return true;
            }
        }

        return false;
    }

    private function sanitizeValue($val, $type)
    {
        $unquoted = array("money"=>1,"real"=>1,"numeric"=>1,
            "float4"=>1,"float8"=>1,"bit"=>1,"double"=>1,"newdecimal"=>1);
        $dates = array("datetime"=>1);

        if ($val == "" && $this->isIntegerType($type)) {
            $val = 0;    
        } elseif ($val == "" && isset($unquoted[$type])) {
            $val = 0;    
        }
        if (isset($dates[$type])) {
            $val = $this->cleanDateTime($val);
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
        // mysqli puts a integer constant in the type property
        // ADOdb provides MetaType to convert to relative type
        $dbtype = $this->connectionType($which_connection);
        if (strtolower($dbtype) === 'mysqli') {
            $meta = $this->connections[$which_connection]->MetaType($fld->type);
            switch ($meta) {
                case 'C':
                case 'X':
                    $fld->type = 'varchar';
                    break;
                case 'B':
                case 'X':
                    $fld->type= 'blob';
                    break;
                case 'D':
                case 'T':
                    $fld->type= 'datetime';
                    break;
                case 'R':
                case 'I':
                    $fld->type= 'int';
                    break;
                case 'N':
                    $fld->type= 'numeric';
                    break;
            }
        }

        return $fld->type;
    }

    /**
      Alias of method fetchField()
    */
    public function fieldName($result_object,$index,$which_connection='')
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
        $which_connection = $which_connection === '' ? $this->default_db : $which_connection;
        $adapter = $this->getAdapter($this->connectionType($which_connection));
        return $adapter->dayofweek($field);
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
        /**
          Check whether the definition is in cache
        */
        if (isset($this->structure_cache[$which_connection]) && isset($this->structure_cache[$which_connection][$table_name])) {
            return true;
        }

        $conn = $this->getNamedConnection($which_connection);
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
        $lc_name = strtolower($table_name);
        $lc_views = array_map(function($view) { return strtolower($view); }, $views);

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

        $adapter = $this->getAdapter($this->connectionType($which_connection));
        return $adapter->getViewDefinition($view_name, $this, $which_connection);
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

        if (is_array($cols)) {
            $return = array_reduce($cols,
                function ($carry, $c) {
                    if (is_object($c)) {
                        $carry[$c->name] = $c->type;
                    }
                    return $carry;
                },
                array()
            );
            return $return;
        }

        return false;
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

    private function columnBooleanProperty($col, $prop)
    {
        if (property_exists($col, $prop) && $col->$prop) {
            return true;
        } else if (property_exists($col, $prop) && !$col->$prop) {
            return false;
        } else {
            return null;
        }
    }

    private function columnToArray($col)
    {
        $info = array();
        $type = strtoupper($col->type);
        if (property_exists($col, 'max_length') && $col->max_length != -1 && substr($type, -3) != 'INT') {
            if ($this->columnBooleanProperty($col, 'scale')) {
                $type .= '(' . $col->max_length . ',' . $col->scale . ')';
            } else {
                $type .= '(' . $col->max_length . ')';
            }
        }
        if ($this->columnBooleanProperty($col, 'unsigned')) {
            $type .= ' UNSIGNED';
        }
        $info['type'] = $type;
        $info['increment'] = $this->columnBooleanProperty($col, 'auto_increment');
        $info['primary_key'] = $this->columnBooleanProperty($col, 'primary_key');

        if (property_exists($col, 'default_value') && $col->default_value !== 'NULL' && $col->default_value !== null && !$info['increment']) {
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

    /**
      Get current default database
      for a given connection
      @param which_connection see method close
      @return [string] database name
        or [boolean] false on failure
    */
    public function defaultDatabase($which_connection='')
    {
        $which_connection = $which_connection === '' ? $this->default_db : $which_connection;
        $adapter = $this->getAdapter($this->connectionType($which_connection));
        if ($which_connection == '') {
            $which_connection=$this->default_db;
        }

        if (count($this->connections) == 0) {
            return false;
        }

        $query = $adapter->defaultDatabase();

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
        $which_connection = $which_connection === '' ? $this->default_db : $which_connection;
        $adapter = $this->getAdapter($this->connectionType($which_connection));
        return $adapter->currency();
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
        $which_connection = $which_connection === '' ? $this->default_db : $which_connection;
        $adapter = $this->getAdapter($this->connectionType($which_connection));
        return $adapter->addSelectLimit($query, $int_limit);
    }

    /**
      Get database scope separator
      @param which_connection see method close
      @return String separator
    */
    public function sep($which_connection='')
    {
        $which_connection = $which_connection === '' ? $this->default_db : $which_connection;
        $adapter = $this->getAdapter($this->connectionType($which_connection));
        if ($adapter == null) {
            return '.';
        }
        return $adapter->sep();
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

    /**
      Get last error message
      @param which_connection see method close
      @return The message
    */
    public function error($which_connection='')
    {
        $con = $this->getNamedConnection($which_connection);

        if (!is_object($con)) {
            if ($this->last_connect_error) {
                return $this->last_connect_error;
            }

            return 'No database connection';
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
      to SQLManager::execute for execution
    */
    public function prepare($sql,$which_connection="")
    {
        if ($which_connection == '') {
            $which_connection=$this->default_db;
        }
        $con = $this->connections[$which_connection];

        return is_object($con) ? $con->Prepare($sql) : false;
    }

   /**
      Execute a prepared statement with the given
      set of parameters
      @param $sql a value from SQLManager::prepare
      @param $input_array an array of values
      @param which_connection see method close
      @return same as SQLManager::query

      This is essentially a helper function to flip the 
      parameter order on SQLManager::query so existing code
      works as expected
    */
    public function execute($sql, $input_array=array(), $which_connection='')
    {
        if ($which_connection == '') {
            $which_connection=$this->default_db;
        }
        if (!is_array($input_array)) {
            $input_array = array($input_array);
        }

        return $this->query($sql,$which_connection,$input_array);
    }

    /**
      Get a value directly from a query without verifying
      rows exist and fetching one
      @param $sql a value from SQLManager::prepare
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
        } elseif ($res && $this->numRows($res) == 0) {
            return false;
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
      @param $sql a value from SQLManager::prepare
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
        } elseif ($res && $this->numRows($res) == 0) {
            return false;
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

    /**
      Assign a query log
      @param [mixed] [object] implementing the PSR3 log interface
    */
    public function setQueryLog($log)
    {
        $this->QUERY_LOG = $log;
    }

    /**
      Enable or disable debug mode
      @param [boolean] true means enabled, false means disabled
    */
    public function setDebugMode($debug)
    {
        $this->debug_mode = $debug;
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
        
        $definition1 = $this->tableDefinition($table1, $which_connection);
        $definition2 = $this->tableDefinition($table2, $which_connection);
        if (!is_array($definition1) || ! is_array($definition2)) {
            return array();
        }

        $matches = array_filter(array_keys($definition1),
            function ($col_name) use ($definition2) {
                return isset($definition2[$col_name]) ? true : false;
            }
        );

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
        $def1 = $this->tableDefinition($table1, $which_connection1);
        $def2 = $this->tableDefinition($table2, $which_connection2);
        $ret = array_reduce(array_keys($def1),
            function ($carry, $column_name) use ($def2) {
                if (isset($def2[$column_name])) {
                    $carry .= $column_name . ',';
                }
                return $carry;
            },
            ''
        );
                
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
        $which_connection = $which_connection === '' ? $this->default_db : $which_connection;
        $adapter = $this->getAdapter($this->connectionType($which_connection));
        $query = $adapter->temporaryTable($name, $source_table);
        $created = $this->query($query, $which_connection);
        return $created ? $name : false;
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

    /**
      Adapters provide bits of rDBMS-specific SQL
      that aren't covered by ADOdb. This method 
      caches adapters on creation.
    */
    protected $adapters = array();
    protected $adapter_map = array(
        'mysql'     => 'COREPOS\common\sql\MysqlAdapter',
        'mysqli'    => 'COREPOS\common\sql\MysqlAdapter',
        'pdo_mysql' => 'COREPOS\common\sql\MysqlAdapter',
        'pdo'       => 'COREPOS\common\sql\MysqlAdapter',
        'mssql'     => 'COREPOS\common\sql\MssqlAdapter',
        'pgsql'     => 'COREPOS\common\sql\PgsqlAdapter',
        'postgres9' => 'COREPOS\common\sql\PgsqlAdapter',
        'pdo_pgsql'     => 'COREPOS\common\sql\PgsqlAdapter',
        'sqlite3'   => 'COREPOS\common\sql\SqliteAdapter',
    );

    protected function getAdapter($type)
    {
        if (isset($this->adapters[$type])) {
            return $this->adapters[$type];
        }
        if (isset($this->adapter_map[$type])) {
            $class = $this->adapter_map[$type];
            $this->adapters[$type] = new $class();
        }

        return $this->getAdapter('mysqli');
    }

    /**
      Build an SQL IN clause from an array
      @param $arr [array] of values
      @param $args [array, optional] existing query parameter list
      @param $dummy_value [optional] plug value when the array of values is empty
      @return [tuple] SQL string of placeholders, array of query parameters
    */
    public function safeInClause($arr, $args=array(), $dummy_value=-999999)
    {
        if (!is_array($arr)) {
            $arr = array($arr);
        }
        if (count($arr) == 0) { 
            $arr = array($dummy_value);
        }
        $args = array_merge($args, $arr);
        $inStr = str_repeat('?,', count($arr));
        $inStr = substr($inStr, 0, strlen($inStr)-1);

        return array($inStr, $args);
    }

    public function setCharSet($charset, $which_connection='')
    {
        // validate connection
        $con = $this->getNamedConnection($which_connection);
        $type = $this->connectionType($which_connection);
        if ($type == 'unknown' || !is_object($con)) {
            return false;
        }

        // validate character set
        $db_charset = CharSets::get($type, $charset);
        if ($db_charset === false) {
            return false;
        }

        $adapter = $this->getAdapter($type);
        $query = $adapter->setCharSet($db_charset);

        return $con->query($query);
    }
}

