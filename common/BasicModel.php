<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of Fannie.

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

namespace COREPOS\common;

/**
  @class BasicModel
*/
class BasicModel 
{

    /**
      Name of the table
    */
    protected $name;

    /**
      Fully qualified table name
      Typically database.table
      Provided only if it can be detected
      If found, this name is more reliable
      since the connection is shared and outside
      code may change its state including the
      currently selected database.

      If the database cannot be detected, 
      $fq_name and $name will be identical.
    */
    protected $fq_name;

    /**
      Definition of columns. Keyed by column name.
      Values should be arrays with keys for:
      - type (required)
      - default (null if omitted)
      - primary_key (optional, boolean)
      - index (optional, boolean)
      - not_null (optional, boolean)
      - increment (optional, boolean)
      - ignore_updates (optional, boolean)
      - replaces (optional, string previous column name)
    */
    protected $columns = array();

    /**
      List of columns that should be unique
      per record. Only necessary if the
      table has no primary key.
    */
    protected $unique = array();

    /**
      Type transalations for different DB
      backends.
    */
    protected $meta_types = array(
        'MONEY' => array('default'=>'DECIMAL(10,2)','mssql'=>'MONEY'),
        'BIGINT UNSIGNED' => array('default'=>'BIGINT UNSIGNED', 'mssql'=>'BIGINT'),
    );

    /**
      Database connection
    */
    protected $connection = false;
    public function db()
    { 
        return $this->connection;
    }

    /**
      boolean flag indicating at least one column
      record has been updated and the instances
      currently differs from the underlying database.
      Columns flagged as ignore_upates will not
      be considered a record change when their value
      is altered.
    */
    protected $record_changed = false;

    /**
      List of column names => values
    */
    protected $instance = array();

    /**
      List of WHERE clauses
    */
    protected $filters = array();

    /**
      Cache table definition internally so that repeated
      calls to find(), save(), etc don't involve multiple
      extra queries checking table existence and 
      structure every single time
    */
    protected $cached_definition = false;

    /**
      Configuration object
    */
    protected $config;

    protected $find_limit = 0;

    /**
      Name of preferred database
    */
    protected $preferred_db = '';
    public function preferredDB()
    {
        return $this->preferred_db;
    }

    public function setConfig($c)
    {
        $this->config = $c;
    }

    public function setConnection($c)
    {
        $this->connection = $c;
    }

    /** check for potential changes **/
    const NORMALIZE_MODE_CHECK = 1;
    /** apply changes **/
    const NORMALIZE_MODE_APPLY = 2;

    /**
      Constructor
      @param $con a SQLManager object
    */
    public function __construct($con)
    {
        $this->connection = $con;
        if (empty($this->unique)) {
            foreach($this->columns as $name=>$definition) {
                if (isset($definition['primary_key']) && $definition['primary_key']) {
                    $this->unique[] = $name;
                }
            }
        }

        // fully-qualified name detectetion not working right now...
        $this->fq_name = $this->name;
    }

    /**
      Manually set which database contains this table. Normally
      this is autodetected by the constructor.
      @param $db_name [string] database name
      @return [boolean] success/failure
    */
    public function whichDB($db_name)
    {
        if ($this->connection->tableExists($db_name . $this->connection->sep() . $this->name)) {
            $this->fq_name = $db_name . $this->connection->sep() . $this->name;
            return true;
        } else {
            return false;
        }
    }

    public function getDefinition()
    {
        if ($this->cached_definition === false) {
            $this->cached_definition = $this->connection->tableDefinition($this->fq_name);
        }

        return $this->cached_definition;
    }

    /**
      Create the table
      @return boolean
    */
    public function create()
    {
        if ($this->connection->tableExists($this->fq_name)) {
            return true;
        }

        $dbms = $this->connection->dbms_name();
        $pkey = array();
        $indexes = array();
        $sql = 'CREATE TABLE '.$this->fq_name.' (';
        foreach($this->columns as $cname => $definition) {
            if (!isset($definition['type'])) {
                return false;
            }

            $sql .= $this->connection->identifier_escape($cname);    
            $sql .= ' ' . $this->arrayToSQL($definition, $dbms);
            $sql .= ',';

            if ($this->isPrimaryKey($definition)) {
                $pkey[] = $cname;
            } elseif ($this->isIndexed($definition)) {
                $indexes[] = $cname;
            }
        }

        if (!empty($pkey)) {
            $sql .= ' PRIMARY KEY (';
            foreach($pkey as $col) {
                $sql .= $this->connection->identifier_escape($col).',';
            }
            $sql = substr($sql,0,strlen($sql)-1).'),';
        }
        if (!empty($indexes)) {
            foreach($indexes as $index) {
                $sql .= ' INDEX (';
                $sql .= $this->connection->identifier_escape($index);
                $sql .= '),';
            }
        }

        $sql = rtrim($sql,',');
        $sql .= ')';
        if ($this->hasIncrement() && $dbms == 'mssql')
            $sql .= ' ON [PRIMARY]';

        $result = $this->connection->exec_statement($sql);

        /**
          Clear out any cached definition
        */
        if ($result) {
            $this->cached_definition = false;
        }

        return ($result === false) ? false : true;

    // create()
    }

    protected function arrayToSQL($definition, $dbms)
    {
        $sql = '';
        $type = $definition['type'];
        if (isset($this->meta_types[strtoupper($type)])) {
            $type = $this->getMeta($type, $dbms);
        }
        $sql .= $type;

        if (isset($definition['not_null']) && $definition['not_null']) {
            $sql .= ' NOT NULL';
        }
        if (isset($definition['increment']) && $definition['increment']) {
            if ($dbms == 'mssql') {
                $sql .= ' IDENTITY (1, 1) NOT NULL';
            } else {
                $sql .= ' NOT NULL AUTO_INCREMENT';
            }
        } elseif (isset($definition['default']) && (
            is_string($definition['default']) || is_numeric($definition['default'])
        )) {
            if ($dbms == 'mssql') {
                $sql .= ' '.$definition['default'];
            } else {
                $sql .= ' DEFAULT '.$definition['default'];
            }
        }

        return $sql;
    }

    protected function isPrimaryKey($column)
    {
        if (isset($column['primary_key']) && $column['primary_key'] === true) {
            return true;
        } else {
            return false;
        }
    }

    protected function isIndexed($column)
    {
        if (isset($column['index']) && $column['index'] === true) {
            return true;
        } elseif (isset($column['increment']) && $column['increment'] === true && (!isset($column['primary_key']) || $column['primary_key'] !== true)) {
            return true;
        } else {
            return false;
        }
    }

    protected function hasIncrement()
    {
        foreach ($this->columns as $name => $def) {
            if (isset($def['increment']) && $def['increment'] === true) {
                return true;
            }
        }

        return false;
    }

    /**
      Create structure only if it does not exist
      @param $db_name [string] database name
      @return [keyed array]
        db => database name
        struct => table/view name
        error => [int] error code
        error_msg => error details
    */
    public function createIfNeeded($db_name)
    {
        $this->fq_name = $db_name . $this->connection->sep() . $this->name;
        $ret = array('db'=>$db_name,'struct'=>$this->name,'error'=>0,'error_msg'=>'');
        if (!$this->create()) {
            $ret['error'] = 3;
            $ret['error_msg'] = $this->connection->error($db_name);
        }

        return $ret;
    }

    /**
      Populate instance with database values
      Requires a uniqueness constraint. Assign
      those columns before calling load().
      @return boolean
    */
    public function load()
    {
        if (empty($this->unique)) {
            return false;
        }
        foreach($this->unique as $column) {
            if (!isset($this->instance[$column])) {
                return false;
            }
        }

        $table_def = $this->getDefinition();

        $sql = 'SELECT ';
        foreach($this->columns as $name => $definition) {
            if (!isset($table_def[$name])) {
                // underlying table is missing the column
                // constraint only used for select columns
                // if a uniqueness-constraint column is missing
                // this method will and should fail
                continue; 
            }
            $sql .= $this->connection->identifier_escape($name).',';
        }
        $sql = substr($sql,0,strlen($sql)-1);
        
        $sql .= ' FROM '.$this->fq_name.' WHERE 1=1';
        $args = array();
        foreach($this->unique as $name) {
            $sql .= ' AND '.$this->connection->identifier_escape($name).' = ?';
            $args[] = $this->instance[$name];
        }

        $prep = $this->connection->prepare_statement($sql);
        $result = $this->connection->exec_statement($prep, $args);

        if ($this->connection->num_rows($result) > 0) {
            $row = $this->connection->fetch_row($result);
            foreach($this->columns as $name => $definition) {
                if (!isset($row[$name])) continue;
                $this->instance[$name] = $row[$name];
            }
            $this->record_changed = false;

            return true;
        } else {
            return false;
        }
    }

    /**
      Clear object values.
    */
    public function reset()
    {
        $this->instance = array();
        $this->filters = array();
    }

    public function getColumns()
    {
        return $this->columns;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getFullQualifiedName()
    {
        return $this->fq_name;
    }

    public function setFindLimit($fl)
    {
        $this->find_limit = $fl;
    }

    /**
      Find records that match this instance
      @param $sort array of columns to sort by
      @return an array of controller objects
    */
    public function find($sort='', $reverse=false)
    {
        if (!is_array($sort)) {
            $sort = array($sort);
        }

        $table_def = $this->getDefinition();

        $sql = 'SELECT ';
        foreach($this->columns as $name => $definition) {
            if (!isset($table_def[$name])) {
                continue;
            }
            $sql .= $this->connection->identifier_escape($name).',';
        }
        $sql = substr($sql,0,strlen($sql)-1);
        
        $sql .= ' FROM '.$this->fq_name.' WHERE 1=1';
        
        $args = array();
        foreach($this->instance as $name => $value) {
            $sql .= ' AND '.$this->connection->identifier_escape($name).' = ?';
            $args[] = $value;
        }

        foreach ($this->filters as $filter) {
            $sql .= ' AND ' . $this->connection->identifier_escape($filter['left'])
                . ' ' . $filter['op'];
            if (!$filter['rightIsLiteral'] && isset($this->columns[$filter['right']])) {
                $sql .= ' ' . $this->connection->identifier_escape($filter['right']);
            } else {
                $sql .= ' ?';
                $args[] = $filter['right'];
            }
        }

        $order_by = '';
        foreach($sort as $name) {
            if (!isset($this->columns[$name])) {
                continue;
            }
            $order_by .= $this->connection->identifier_escape($name);
            if ($reverse) {
                $order_by .= ' DESC';
            }
            $order_by .= ',';
        }
        if ($order_by !== '') {
            $order_by = substr($order_by,0,strlen($order_by)-1);
            $sql .= ' ORDER BY '.$order_by;
        }

        $prep = $this->connection->prepare_statement($sql);
        $result = $this->connection->exec_statement($prep, $args);

        $ret = array();
        $my_type = get_class($this);
        while($row = $this->connection->fetch_row($result)) {
            $obj = new $my_type($this->connection);
            foreach($this->columns as $name => $definition) {
                if (!isset($row[$name])) continue;
                $obj->$name($row[$name]);
            }
            $ret[] = $obj;
            if ($this->find_limit > 0 && count($ret) >= $this->find_limit) {
                break;
            }
        }

        return $ret;
    }

    /**
      Delete record from the database.
      Requires a uniqueness constraint. Assign
      those columns before calling delete().
      @return boolean
    */
    public function delete()
    {
        if (empty($this->unique)) {
            return false;
        }
        foreach($this->unique as $column) {
            if (!isset($this->instance[$column])) {
                return false;
            }
        }

        $sql = 'DELETE FROM '.$this->fq_name.' WHERE 1=1';
        $args = array();
        foreach($this->unique as $name) {
            $sql .= ' AND '.$this->connection->identifier_escape($name).' = ?';
            $args[] = $this->instance[$name];
        }

        $prep = $this->connection->prepare_statement($sql);
        $result = $this->connection->exec_statement($prep, $args);

        return ($result === false) ? false : true;
    }

    /**
      Get database-specific type
      @param $type a "meta-type" with different underlying type
        depending on the DB
      @param $dbms string DB name
      @return string
    */
    protected function getMeta($type, $dbms)
    {
        if (!isset($this->meta_types[strtoupper($type)])) {
            return $type;
        }
        $meta = $this->meta_types[strtoupper($type)];

        return isset($meta[$dbms]) ? $meta[$dbms] : $meta['default'];
    }

    /**
      Validate SQL binary operator
      @param $operator [string] operator
      @return [string] valid operator or [boolean] false
    */
    protected function validateOp($operator)
    {
        if (strlen($operator) == 0) {
            return false;
        }

        switch ($operator) {
            case '<':
            case '>':
            case '=':
            case '<>':
            case '>=':
            case '<=':
                return $operator;
            case '!=':
                return '<>';
            default:
                return false;
        }
    }

    /**
      Save current record. If a uniqueness constraint
      is defined it will INSERT or UPDATE appropriately.
      @return 
        [boolean] false on failure
        [SQL result] object *or* [int] ID on success
      
      The only time save() will not return a result object
      on success is on an insert into a table containing
      an incrementing ID column. In most cases this is
      more useful. Databases typically start counting from
      1 rather than 0 so it should still work to write:
        if ($model->save())
      But it would be slightly safer to write:
        if ($model->save() !== false)
    */
    public function save()
    {
        if (!is_array($this->hooks) || empty($this->hooks)) {
            $this->loadHooks();
        }
        foreach($this->hooks as $hook_obj) {
            if (method_exists($hook_obj, 'onSave')) {
                $hook_obj->onSave($this->name, $this);
            }
        }

        $new_record = false;
        // do we have values to look up?
        foreach($this->unique as $column)
        {
            if (!isset($this->instance[$column])) {
                $new_record = true;
            }
        }
        if (count($this->unique) == 0) {
            $new_record = true;
        }

        if (!$new_record) {
            // see if matching record exists
            $check = 'SELECT * FROM '.$this->fq_name
                .' WHERE 1=1';
            $args = array();
            foreach($this->unique as $column) {
                $check .= ' AND '.$this->connection->identifier_escape($column).' = ?';
                $args[] = $this->instance[$column];
            }
            $prep = $this->connection->prepare_statement($check);
            $result = $this->connection->exec_statement($prep, $args);
            if ($this->connection->num_rows($result)==0) {
                $new_record = true;
            }
        }

        if ($new_record) {
            return $this->insertRecord();
        } else {
            return $this->updateRecord();
        }
    }

    /**
      Helper. Build & execute insert query
      @return SQL result object or boolean false
    */
    protected function insertRecord()
    {
        $sql = 'INSERT INTO '.$this->fq_name;
        $cols = '(';
        $vals = '(';
        $args = array();
        $table_def = $this->getDefinition();
        foreach($this->instance as $column => $value) {
            if (isset($this->columns[$column]['increment']) && $this->columns[$column]['increment']) {
                // omit autoincrement column from insert
                continue;
            } else if (!isset($table_def[$column])) {
                // underlying table is missing this column
                continue;
            }
            $cols .= $this->connection->identifier_escape($column).',';
            $vals .= '?,';    
            $args[] = $value;
        }
        $cols = substr($cols,0,strlen($cols)-1).')';
        $vals = substr($vals,0,strlen($vals)-1).')';
        $sql .= ' '.$cols.' VALUES '.$vals;

        $prep = $this->connection->prepare_statement($sql);
        $result = $this->connection->exec_statement($prep, $args);

        if ($result) {
            $this->record_changed = false;

            /** if the insert succeeded and the table contains an incrementing
                id column, that value will most likely be more useful
                than the result object */
            foreach($this->columns as $name => $info) {
                if (isset($info['increment']) && $info['increment'] === true) {
                    $new_id = $this->connection->insert_id();
                    if ($new_id !== false) {
                        $result = $new_id;
                        break;
                    }
                }
            }
        }

        return $result;
    }

    /**
      Helper. Build & execute update query
      @return SQL result object or boolean false
    */
    protected function updateRecord()
    {
        $sql = 'UPDATE '.$this->fq_name;
        $sets = '';
        $where = '1=1';
        $set_args = array();
        $where_args = array();
        $table_def = $this->getDefinition();
        foreach($this->instance as $column => $value) {
            if (in_array($column, $this->unique)) {
                $where .= ' AND '.$this->connection->identifier_escape($column).' = ?';
                $where_args[] = $value;
            } else {
                if (isset($this->columns[$column]['increment']) && $this->columns[$column]['increment']) {
                    continue;
                } else if (!isset($table_def[$column])) {
                    // underlying table is missing this column
                    continue;
                }
                $sets .= ' '.$this->connection->identifier_escape($column).' = ?,';
                $set_args[] = $value;
            }
        }
        $sets = substr($sets,0,strlen($sets)-1);

        $sql .= ' SET '.$sets.' WHERE '.$where;
        $all_args = $set_args;
        foreach($where_args as $arg) {
            $all_args[] = $arg;
        }
        $prep = $this->connection->prepare_statement($sql);
        $result = $this->connection->exec_statement($prep, $all_args);

        if ($result) {
            $this->record_changed = false;
        }

        return $result;
    }

    /**
      Compare existing table to definition
      Add any columns that are missing from the table structure
      Extra columns that are present in the table but not in the
      controlelr class are left as-is.
      @param $db_name name of the database containing the table 
      @param $mode the normalization mode. See above.
      @return number of columns added or False on failure
    */
    public function normalize($db_name, $mode=BasicModel::NORMALIZE_MODE_CHECK)
    {
        if ($mode != BasicModel::NORMALIZE_MODE_CHECK && $mode != BasicModel::NORMALIZE_MODE_APPLY) {
            echo "Error: Unknown mode ($mode)\n";
            return false;
        }

        echo "==========================================\n";
        printf("%s table %s\n", 
            ($mode==BasicModel::NORMALIZE_MODE_CHECK)?"Checking":"Updating", 
            "{$db_name}.{$this->name}"
        );
        echo "==========================================\n";

        if (!$this->connection->table_exists($this->name)) {
            return $this->normalizeCreateTable($db_name, $mode);
        }

        // get lowercased versions of the class' column names
        // and the current table's column names to check for
        // case mismatches
        $current = $this->connection->detailedDefinition($this->name);

        $new_columns = array();
        $unknown = array();
        $recase_columns = array();

        $recase_columns = array_merge($array, $this->normalizeChangeCase($db_name, $mode));
        $recase_columns = array_merge($array, $this->normalizeRename($db_name, $mode));
        $recase_columns = array_merge($array, $this->normalizeColumnAttributes($db_name, $mode));

        foreach ($this->columns as $col_name => $defintion) {
            if (!in_array($col_name,array_keys($current))) {
                $new_columns[] = $col_name;
            }
        }
        foreach($current as $col_name => $type) {
            if (!in_array($col_name,array_keys($this->columns)) && !in_array(strtolower($col_name), $lowercase_this)) {
                $unknown[] = $col_name;
                echo "Ignoring unknown column: $col_name in current definition (delete manually if desired)\n";
            }
        }
        $our_columns = array_keys($this->columns);
        $their_columns = array_keys($current);
        for ($i=0;$i<count($our_columns);$i++) {
            if (!in_array($our_columns[$i],$new_columns)) {
                continue; // column already exists
            }
            printf("%s column: %s\n", 
                    ($mode==BasicModel::NORMALIZE_MODE_CHECK)?"Need to add":"Adding", 
                    "{$our_columns[$i]}"
            );
            $sql = '';
            foreach ($their_columns as $their_col) {
                $sql = 'ALTER TABLE '.$this->name.' ADD COLUMN '
                    .$this->connection->identifier_escape($our_columns[$i]).' '
                    .$this->arrayToSQL($this->columns[$our_columns[$i]], $this->connection->dbms_name());
                if (isset($our_columns[$i-1]) && $our_columns[$i-1] == $their_col) {
                    $sql .= ' AFTER '.$this->connection->identifier_escape($their_col);
                    break;
                } elseif (isset($our_columns[$i+1]) && $our_columns[$i+1] == $their_col) {
                    $sql .= ' FIRST';
                    break;
                }
                if (isset($our_columns[$i-1]) && in_array($our_columns[$i-1],$new_columns)) {
                    $sql .= ' AFTER '.$this->connection->identifier_escape($our_columns[$i-1]);
                    break;
                }
            }
            if ($sql !== '') {
                if (isset($this->columns[$our_columns[$i]]['increment']) && $this->columns[$our_columns[$i]]['increment']) {
                    // increment must be indexed
                    $index = 'INDEX';
                    if ($this->isPrimaryKey($our_columns[$i])) {
                        $index = 'PRIMARY KEY ';
                    }
                    $sql .= ', ADD ' . $index . ' (' . $this->connection->identifier_escape($our_columns[$i]) . ')'; 
                } elseif ($this->isIndexed($our_columns[$i])) {
                    $sql .= ', ADD INDEX (' . $this->connection->identifier_escape($our_columns[$i]) . ')'; 
                }
                if ($mode == BasicModel::NORMALIZE_MODE_CHECK) {
                    echo "\tSQL Details: $sql\n";
                } else if ($mode == BasicModel::NORMALIZE_MODE_APPLY) {
                    $added = $this->connection->query($sql);
                    // hook function for initiailization or migration queries
                    if ($added && method_exists($this, 'hookAddColumn'.$our_columns[$i])) {
                        $func = 'hookAddColumn'.$our_columns[$i];
                        $this->$func();
                    }
                }
            }

            if ($sql === '') {
                echo "\tError: could not find context for {$our_columns[$i]}\n";
            }
        }

        $alters = count($new_columns) + count($recase_columns);
        echo "==========================================\n";
        printf("%s %d column%s.\n",
            ($mode==BasicModel::NORMALIZE_MODE_CHECK)?"Check complete. Need to adjust":"Update complete. Added",
            $alters, ($alters!=1)?"s":""
            );
        echo "==========================================\n\n";

        if ($mode == BasicModel::NORMALIZE_MODE_APPLY && count($new_columns) > 0) {
            $this->afterNormalize($db_name, $mode);
        }

        if ($alters > 0) {
            return $alters;
        } else if (count($unknown) > 0) {
            return -1*count($unknown);
        }

        return 0;

    // normalize()
    }

    private function normalizeCreateTable($db_name, $mode=BasicModel::NORMALIZE_MODE_CHECK)
    {
        if ($mode == BasicModel::NORMALIZE_MODE_CHECK) {
            echo "Table {$this->name} not found!\n";
            echo "==========================================\n";
            printf("%s table %s\n","Check complete. Need to create", $this->name);
            echo "==========================================\n\n";
            return 999;
        } elseif ($mode == BasicModel::NORMALIZE_MODE_APPLY) {
            echo "==========================================\n";
            $cResult = $this->create(); 
            if ($cResult) {
                $this->afterNormalize($db_name, $mode);
            }
            printf("Update complete. Creation of table %s %s\n",$this->name, ($cResult)?"OK":"failed");
            echo "==========================================\n\n";
            return true;
        }
    }

    private function normalizeChangeCase($db_name, $mode=BasicModel::NORMALIZE_MODE_CHECK)
    {
        $current = $this->connection->detailedDefinition($this->name);
        $lowercase_current = array();
        $casemap = array();
        foreach($current as $col_name => $definition) {
            $lowercase_current[] = strtolower($col_name);
            $casemap[strtolower($col_name)] = $col_name;
        }
        $lowercase_this = array();
        foreach($this->columns as $col_name => $definition) {
            $lowercase_this[] = strtolower($col_name);
        }

        $recase_columns = array();
        foreach ($this->columns as $col_name => $defintion) {
            if (in_array(strtolower($col_name), $lowercase_current) && !in_array($col_name, array_keys($current))) {
                printf("%s column %s as %s\n", 
                        ($mode==BasicModel::NORMALIZE_MODE_CHECK)?"Need to rename":"Renaming", 
                        $casemap[strtolower($col_name)], $col_name);
                $recase_columns[] = $col_name;
                $sql = 'ALTER TABLE ' . $this->connection->identifier_escape($this->name) . ' CHANGE COLUMN '
                        . $this->connection->identifier_escape($casemap[strtolower($col_name)]) . ' '
                        . $this->connection->identifier_escape($col_name) . ' '
                        . $this->getMeta($this->columns[$col_name]['type'], $this->connection->dbms_name());
                if (isset($this->columns[$col_name]['default'])) {
                    $sql .= ' DEFAULT '.$this->columns[$col_name]['default'];
                }
                if (isset($this->columns[$col_name]['not_null'])) {
                    $sql .= ' NOT NULL';
                }
                printf("\tSQL Details: %s\n", $sql);
                if ($mode == BasicModel::NORMALIZE_MODE_APPLY) {
                    $renamed = $this->connection->query($sql);
                }
            }
        }

        return $recase_columns;
    }

    private function normalizeColumnAttributes($db_name, $mode=BasicModel::NORMALIZE_MODE_CHECK)
    {
        $current = $this->connection->detailedDefinition($this->name);
        $recase_columns = array();
        $redo_pk = false;
        foreach ($this->columns as $col_name => $defintion) {
            if (in_array($col_name, array_keys($current))) {
                $type = $this->getMeta($this->columns[$col_name]['type'], $this->connection->dbms_name());
                $rebuild = false;
                if (strtoupper($type) != $current[$col_name]['type']) {
                    printf("%s column %s from %s to %s\n", 
                            ($mode==BasicModel::NORMALIZE_MODE_CHECK)?"Need to change":"Changing", 
                            $col_name, $current[$col_name]['type'], $type);
                    $rebuild = true;
                } else if (isset($this->columns[$col_name]['default']) && $this->columns[$col_name]['default'] != $current[$col_name]['default']) {
                    printf("%s column %s default value from %s to %s\n", 
                            ($mode==BasicModel::NORMALIZE_MODE_CHECK)?"Need to change":"Changing", 
                            $col_name, $current[$col_name]['default'], $this->columns[$col_name]['default']);
                    $rebuild = true;
                } else if (isset($this->columns[$col_name]['increment']) && $this->columns[$col_name]['increment'] && $current[$col_name]['increment'] === false) {
                    printf("%s for column %s\n", 
                            ($mode==BasicModel::NORMALIZE_MODE_CHECK)?"Need to set increment":"Setting increment", 
                            $col_name);
                    $rebuild = true;
                } else if (isset($this->columns[$col_name]['primary_key']) && $this->columns[$col_name]['primary_key'] && $current[$col_name]['primary_key'] === false) {
                    $redo_pk = true;
                }
                if ($rebuild) {
                    $sql = 'ALTER TABLE ' . $this->connection->identifier_escape($this->name) . ' CHANGE COLUMN '
                            . $this->connection->identifier_escape($col_name) . ' '
                            . $this->connection->identifier_escape($col_name) . ' '
                            . $this->arrayToSQL($this->columns[$col_name], $this->connection->dbms_name());
                    if (isset($this->columns[$col_name]['increment']) && $this->columns[$col_name]['increment'] && $this->isIndexed($col_name)) {
                        $sql .= ', ADD ' . $index . ' (' . $this->connection->identifier_escape($this->columns[$col_name]) . ')'; 
                    }
                    printf("\tSQL Details: %s\n", $sql);
                    $recase_columns[] = $col_name;
                    if ($mode == BasicModel::NORMALIZE_MODE_APPLY) {
                        $modified = $this->connection->query($sql);
                    }
                }
            }
        }

        if ($redo_pk) {
            $this->normalizeReplacePK($db_name, $mode);
            $recase_columns[] = 'PRIMARY KEY';
        }

        return $recase_columns;
    }

    private function normalizeRename($db_name, $mode=BasicModel::NORMALIZE_MODE_CHECK)
    {
        $current = $this->connection->detailedDefinition($this->name);
        $recase_columns = array();
        foreach ($this->columns as $col_name => $defintion) {
            if (!in_array($col_name, array_keys($current)) && isset($definition['replaces']) && in_array($definition['replaces'], array_keys($current))) {
                printf("%s column %s as %s\n", 
                        ($mode==BasicModel::NORMALIZE_MODE_CHECK)?"Need to rename":"Renaming", 
                        $definition['replaces'], $col_name);
                $recase_columns[] = $col_name;
                $sql = 'ALTER TABLE ' . $this->connection->identifier_escape($this->name) . ' CHANGE COLUMN '
                        . $this->connection->identifier_escape($definition['replaces']) . ' '
                        . $this->connection->identifier_escape($col_name) . ' '
                        . $this->getMeta($this->columns[$col_name]['type'], $this->connection->dbms_name());
                if (isset($this->columns[$col_name]['default'])) {
                    $sql .= ' DEFAULT '.$this->columns[$col_name]['default'];
                }
                if (isset($this->columns[$col_name]['not_null'])) {
                    $sql .= ' NOT NULL';
                }
                printf("\tSQL Details: %s\n", $sql);
                if ($mode == BasicModel::NORMALIZE_MODE_APPLY) {
                    $renamed = $this->connection->query($sql);
                    if ($renamed && method_exists($this, 'hookAddColumn'.$col_name)) {
                        $func = 'hookAddColumn'.$col_name;
                        $this->$func();
                    }
                }
            }
        }

        return $recase_columns;
    }

    private function normalizeReplacePK($db_name, $mode=BasicModel::NORMALIZE_MODE_CHECK)
    {
        $current = $this->connection->detailedDefinition($this->name);
        echo ($mode==BasicModel::NORMALIZE_MODE_CHECK)?"Need to set primary key":"Setting primary key";
        $sql = 'ALTER TABLE ' . $this->connection->identifier_escape($this->name);
        foreach ($current as $col_name=>$info) {
            if ($info['primary_key'] === true) {
                $sql .= ' DROP PRIMARY KEY,';
                break;
            }
        }
        $sql .= ' ADD PRIMARY KEY(';
        foreach ($this->columns as $col_name => $info) {
            if ($this->isPrimaryKey($col_name)) {
                $sql .= $this->connection->identifier_escape($col_name) . ',';
            }
        }
        $sql = substr($sql, 0, strlen($sql)-1);
        $sql .= ')';
        echo "\tSQL Details: $sql\n";

        return 'PRIMARY KEY';
    }

    /**
      Rewrite the given file to create accessor
      functions for all of its columns
    */
    public function generate($filename)
    {
        $pieces = $this->findAccessors($filename);

        if ($pieces === false) {
            echo "Error: could not locate code block\n";
            return false;
        }

        $fptr = fopen($filename,'w');
        fwrite($fptr,$pieces['before']);
        // use 'replaces' to build legacy accessor functions
        // mapping old column names to current column names
        $all_methods = array();
        foreach($this->columns as $name => $definition) {
            $all_methods[$name] = $name;
            if (isset($definition['replaces'])) {
                $all_methods[$definition['replaces']] = $name;
            }
        }
        foreach($all_methods as $method_name => $name) {
            $this->writeAccessor($fptr, $method_name, $name);
        }
        fwrite($fptr,$pieces['after']);
        fclose($fptr);

        return true;
    // generate()
    }

    protected function findAccessors($filename)
    {
        $start_marker = '/* START ACCESSOR FUNCTIONS */';
        $end_marker = '/* END ACCESSOR FUNCTIONS */';
        $before = '';
        $after = '';
        $foundStart = false;
        $foundEnd = false;
        $fptr = fopen($filename,'r');
        while(!feof($fptr)) {
            $line = fgets($fptr);
            if (!$foundStart) {
                $before .= $line;
                if (strstr($line,$start_marker)) {
                    $foundStart = true;
                }
            } elseif($foundStart && !$foundEnd) {
                if (strstr($line, $end_marker)) {
                    $foundEnd = true;
                    $after .= $line;
                }
            } elseif($foundStart && $foundEnd) {
                $after .= $line;
            }
        }
        fclose($fptr);

        if (!$foundStart || !$foundEnd) {
            return false;
        } else {
            return array('before' => $before, 'after' => $after);
        }
    }

    protected function writeAccessor($fptr, $method_name, $name)
    {
        fwrite($fptr,"\n");
        fwrite($fptr,"    public function ".$method_name."()\n");
        fwrite($fptr,"    {\n");
        fwrite($fptr,"        if(func_num_args() == 0) {\n");
        fwrite($fptr,'            if(isset($this->instance["'.$name.'"])) {'."\n");
        fwrite($fptr,'                return $this->instance["'.$name.'"];'."\n");
        fwrite($fptr,'            } else if (isset($this->columns["'.$name.'"]["default"])) {'."\n");
        fwrite($fptr,'                return $this->columns["'.$name.'"]["default"];'."\n");
        fwrite($fptr,"            } else {\n");
        fwrite($fptr,"                return null;\n");
        fwrite($fptr,"            }\n");
        fwrite($fptr,"        } else if (func_num_args() > 1) {\n");
        fwrite($fptr,'            $value = func_get_arg(0);'."\n");
        fwrite($fptr,'            $op = $this->validateOp(func_get_arg(1));'."\n");
        fwrite($fptr,'            if ($op === false) {'."\n");
        fwrite($fptr,'                throw new Exception(\'Invalid operator: \' . func_get_arg(1));'."\n");
        fwrite($fptr,"            }\n");
        fwrite($fptr,'            $filter = array('."\n");
        fwrite($fptr,'                \'left\' => \''.$name.'\','."\n");
        fwrite($fptr,'                \'right\' => $value,'."\n");
        fwrite($fptr,'                \'op\' => $op,'."\n");
        fwrite($fptr,'                \'rightIsLiteral\' => false,'."\n");
        fwrite($fptr,"            );\n");
        fwrite($fptr,'            if (func_num_args() > 2 && func_get_arg(2) === true) {'."\n");
        fwrite($fptr,'                $filter[\'rightIsLiteral\'] = true;'."\n");
        fwrite($fptr,"            }\n");
        fwrite($fptr,'            $this->filters[] = $filter;'."\n");
        fwrite($fptr,"        } else {\n");
        fwrite($fptr,'            if (!isset($this->instance["'.$name.'"]) || $this->instance["'.$name.'"] != func_get_args(0)) {'."\n");
        fwrite($fptr,'                if (!isset($this->columns["'.$name.'"]["ignore_updates"]) || $this->columns["'.$name.'"]["ignore_updates"] == false) {'."\n");
        fwrite($fptr,'                    $this->record_changed = true;'."\n");
        fwrite($fptr,"                }\n");
        fwrite($fptr,"            }\n");
        fwrite($fptr,'            $this->instance["'.$name.'"] = func_get_arg(0);'."\n");
        fwrite($fptr,"        }\n");
        fwrite($fptr,'        return $this;'."\n");
        fwrite($fptr,"    }\n");
    }

    protected $licenses = array(
        'gpl' => '
/*******************************************************************************

    Copyright {{YEAR}} Whole Foods Co-op

    This file is part of CORE-POS.

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
        ',
    );

    public function newModel($name, $as_view=false)
    {
        $fptr = fopen($name.'.php','w');
        fwrite($fptr, chr(60)."?php\n");
        fwrite($fptr, str_replace('{{YEAR}}', date('Y'), $this->licenses['gpl']) . "\n");
        fwrite($fptr, "
/**
  @class $name
*/
class $name extends " . ($as_view ? 'ViewModel' : 'BasicModel') . "\n");
        fwrite($fptr,"{\n");
        fwrite($fptr,"\n");
        fwrite($fptr,"    protected \$name = \"".substr($name,0,strlen($name)-5)."\";\n");
        fwrite($fptr,"\n");
        fwrite($fptr,"    protected \$columns = array(\n    );\n");
        fwrite($fptr,"\n");
        if ($as_view) {
            fwrite($fptr,"    public function definition()\n");
            fwrite($fptr,"    {\n");
            fwrite($fptr,"    }\n");
        }
        fwrite($fptr,"\n");
        fwrite($fptr,"    /* START ACCESSOR FUNCTIONS */\n");
        fwrite($fptr,"    /* END ACCESSOR FUNCTIONS */\n");
        fwrite($fptr,"}\n");
        fwrite($fptr,"\n");
        fclose($fptr);

    // newModel()
    }

    /**
      Return column names and values as JSON object
    */
    public function toJSON()
    {
        return json_encode($this->instance);
    }

    /**
      Return an HTML string with <option> tags for
      each record. Table must have a single column 
      primary key. The 2nd column of the table is used
      to label the <options>.
      @param $selected [PK value] marks one of the tags
        as selected.
    */
    public function toOptions($selected=0)
    {
        if (count($this->unique) != 1) {
            return '';
        }
        $id_col = $this->unique[0];
        $label_col = array_keys($this->columns);
        $label_col = $label_col[1];
        $ret = '';
        foreach ($this->find($label_col) as $obj) {
            $ret .= sprintf('<option %s value="%d">%s</option>',
                    $selected == $obj->$id_col() ? 'selected' : '',
                    $obj->$id_col(),
                    $obj->$label_col()
            );
        }

        return $ret;
    }

    /**
      Return information about the table/view
      this model deals with
    */
    public function doc()
    {
        return 'This model has yet to be documented';
    }

    /**
      Return a Github-flavored markdown table of
      information about the model's column structure
    */
    public function columnsDoc()
    {
        $ret = str_pad('Name', 25, ' ') . '|' . str_pad('Type', 15, ' ') . '|Info' . "\n";
        $ret .= str_repeat('-', 25) . '|' . str_repeat('-', 15) . '|' . str_repeat('-', 10) . "\n";
        foreach ($this->columns as $name => $info) {
            $ret .= str_pad($name, 25, ' ') . '|';
            $ret .= str_pad($info['type'], 15, ' ') . '|';
            if (isset($info['primary_key'])) {
                $ret .= 'PK ';
            }
            if (isset($info['index'])) {
                $ret .= 'Indexed ';
            }
            if (isset($info['increment'])) {
                $ret .= 'Increment ';
            }
            if (isset($info['default'])) {
                $ret .= 'Default=' . $info['default'];
            }
            $ret .= "\n";
        }

        return $ret;
    }

    /**
      Interface method
      Get all known models
    */
    public function getModels()
    {
        return array();
    }

    /**
      Array of hook objects associated with this table
    */
    protected $hooks = array();
    /**
      Interface method
      Search available classes to load applicable
      hook objects into this instance
    */
    protected function loadHooks()
    {
       $this->hooks = array();
    }

    /**
      Interface method
      Set up database connection by database name
    */
    public function setConnectionByName($db_name)
    {
    }

    /**
      Interface method
      Called after normalize() method applies 
    */
    protected function afterNormalize($db_name, $mode)
    {
    }

    /* Argument signatures, to php, where BasicModel.php is the first:
     * 2 args: Generate Accessor Functions: php BasicModel.php <Subclass Filename>\n";
     * 3 args: Create new Model: php BasicModel.php --new <Model Name>\n";
     * 4 args: Update Table Structure: php BasicModel.php --update <Database name> <Subclass Filename[[Model].php]>\n";
    */
    public function cli($argc, $argv)
    {
        if ($argc > 2 && $argv[1] == '--doc') {
            array_shift($argv);
            array_shift($argv);
            $this->printMarkdown($argv);
            return 0;
        }

        if (($argc < 2 || $argc > 4) || ($argc == 3 && $argv[1] != "--new" && $argv[1] != '--new-view') || ($argc == 4 && $argv[1] != '--update')) {
            echo "Generate Accessor Functions: php BasicModel.php <Subclass Filename>\n";
            echo "Create new Model: php BasicModel.php --new <Model Name>\n";
            echo "Create new View Model: php BasicModel.php --new-view <Model Name>\n";
            echo "Update Table Structure: php BasicModel.php --update <Database name> <Subclass Filename>\n";
            echo "Generate markdown documentation: php BasicModel.php --doc <Model Filename(s)>\n";
            return 1;
        }

        // Create new Model
        if ($argc == 3) {
            $modelname = $argv[2];
            if (substr($modelname,-4) == '.php') {
                $modelname = substr($modelname,0,strlen($modelname)-4);
            }
            if (substr($modelname,-5) != 'Model') {
                $modelname .= 'Model';
            }
            echo "Generating Model '$modelname'\n";
            $as_view = $argv[1] == '--new-view' ? true : false;
            $this->newModel($modelname, $as_view);
            return 0;
        }

        $classfile = $argv[1];
        if ($argc == 4) {
            $classfile = $argv[3];
        }
        if (substr($classfile,-4) != '.php') {
            $classfile .= '.php';
        }
        if (!file_exists($classfile)) {
            echo "Error: file '$classfile' does not exist\n";
            return 1;
        }

        $class = pathinfo($classfile, PATHINFO_FILENAME);
        include($classfile);
        if (!class_exists($class)) {
            echo "Error: class '$class' does not exist\n";
            return 1;
        }

        // A new object of the type named on the command line.
        $obj = new $class(null);
        if (!is_a($obj, 'BasicModel')) {
            echo "Error: invalid class. Must be BasicModel\n";
            return 1;
        }

        // Generate accessor functions
        if ($argc == 2) {
            $try = $obj->generate($classfile);
            if ($try) {
                echo "Generated accessor functions\n";
            } else {
                echo "Failed to generate functions\n";
            }
        } elseif ($argc == 4) {
            // Update Table Structure
            // Show what changes are needed but don't make them yet.
            $obj->setConnectionByName($argv[2]);
            $try = $obj->normalize($argv[2],BasicModel::NORMALIZE_MODE_CHECK);
            // If there was no error and there is anything to change,
            //  including creating the table.
            // Was: If the table exists and there is anything to change
            //  get OK to change.
            if ($try !== false && $try > 0) {
                while(true) {
                    echo 'Apply Changes [Y/n]: ';
                    $inp = rtrim(fgets(STDIN));
                    if ($inp === 'n' || $inp === false || $inp === '') {
                        echo "Goodbye.\n";
                        break;
                    } elseif($inp ==='Y') {
                        // THIS WILL APPLY PROPOSED CHANGES!
                        $obj->normalize($argv[2], BasicModel::NORMALIZE_MODE_APPLY, true);
                        break;
                    }
                }
            }
        }
        return 0;
    }

    protected function printMarkdown($files)
    {
        $tables = array();
        foreach ($files as $file) {
            if (!file_exists($file)) {
                continue;
            }
            if (!substr($file, -4) == 'php') {
                continue;
            }
            $class = pathinfo($file, PATHINFO_FILENAME);
            if (!class_exists($class)) { // nested / cross-linked includes
                include($file);
                if (!class_exists($class)) {
                    continue;
                }
            }
            $obj = new $class(null);
            if (!is_a($obj, 'BasicModel')) {
                continue;
            }

            $table = $obj->getName();
            $doc = '### ' . $table . "\n";
            if (is_a($obj, 'ViewModel')) {
                $doc .= '**View**' . "\n\n";
            }
            $doc .= $obj->columnsDoc();
            $doc .= $obj->doc();

            $tables[$table] = $doc;
        }
        ksort($tables);
        foreach ($tables as $t => $doc) {
            echo '* [' . $t . '](#' . strtolower($t) . ')' . "\n";
        }
        echo "\n";
        foreach ($tables as $t => $doc) {
            echo $doc;
            echo "\n";
        }
    }
}

if (php_sapi_name() === 'cli' && basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    $obj = new BasicModel(null);
    $obj->cli($argc, $argv);
}
