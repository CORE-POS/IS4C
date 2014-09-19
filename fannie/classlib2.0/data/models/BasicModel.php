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
      When updating server-side tables, apply
      the same updates to lane-side tables.
      Default is false.
    */
    protected $normalize_lanes = false;

    /**
      Status variable. Besides normalize() itself
      some hook functions may need to know if the
      current update is on the server vs on the lane.
    */
    protected $currently_normalizing_lane = false;

    /**
      Name of preferred database
    */
    protected $preferred_db = '';
    public function preferredDB()
    {
        return $this->preferred_db;
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

        // detect fully qualfied name
        if (is_a($this->connection, 'SQLManager') && $this->connection->isConnected()) {
            $db_name = $this->connection->defaultDatabase();
            if ($this->connection->tableExists($db_name . $this->connection->sep() . $this->name)) {
                $this->fq_name = $this->connection->identifier_escape($db_name) 
                                . $this->connection->sep() 
                                . $this->connection->identifier_escape($this->name);
            } else {
                $this->fq_name = $this->connection->identifier_escape($this->name);
            }
        } else {
            $this->fq_name = $this->name;
        }
        // fq name not working right now...
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
            /** doesn't work
            $this->fq_name = $this->connection->identifier_escape($db_name) 
                            . $this->connection->sep() 
                            . $this->connection->identifier_escape($this->name);
            */
            $this->fq_name = $db_name . $this->connection->sep() . $this->name;
            return true;
        } else {
            return false;
        }
    }

    /**
      Create the table
      @return boolean
    */
    public function create()
    {
        if ($this->connection->table_exists($this->fq_name)) {
            return true;
        }

        $dbms = $this->connection->dbms_name();
        $pkey = array();
        $indexes = array();
        $inc = false;
        $sql = 'CREATE TABLE '.$this->fq_name.' (';
        foreach($this->columns as $cname => $definition) {
            if (!isset($definition['type'])) {
                return false;
            }

            $sql .= $this->connection->identifier_escape($cname);    
            $sql .= ' ';

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
                $inc = true;
            } elseif (isset($definition['default']) && (
                is_string($definition['default']) || is_numeric($definition['default'])
            )) {
                if ($dbms == 'mssql') {
                    $sql .= ' '.$definition['default'];
                } else {
                    $sql .= ' DEFAULT '.$definition['default'];
                }
            }

            $sql .= ',';

            if (isset($definition['primary_key']) && $definition['primary_key']) {
                $pkey[] = $cname;
            } elseif (isset($definition['index']) && $definition['index']) {
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
        if ($inc && $dbms == 'mssql')
            $sql .= ' ON [PRIMARY]';

        $result = $this->connection->exec_statement($sql);

        return ($result === false) ? false : true;

    // create()
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

        $table_def = $this->connection->table_definition($this->fq_name);

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

        $table_def = $this->connection->table_definition($this->fq_name);

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
        $table_def = $this->connection->table_definition($this->fq_name);
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
                if (isset($info['increment']) && $info['increment'] == true) {
                    $id = $this->connection->insert_id();
                    if ($id !== false) {
                        $result = $id;
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
        $table_def = $this->connection->table_definition($this->fq_name);
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

    public function pushToLanes()
    {
        global $FANNIE_LANES;
        // load complete record
        if (!$this->load()) {
            return false;
        }

        $current = $this->connection;
        // save to each lane
        foreach($FANNIE_LANES as $lane) {
            $sql = new SQLManager($lane['host'],$lane['type'],$lane['op'],
                        $lane['user'],$lane['pw']);    
            if (!is_object($sql) || $sql->connections[$lane['op']] === false) {
                continue;
            }
            $this->connection = $sql;
            $this->save();
        }
        $this->connection = $current;

        return true;
    }

    public function deleteFromLanes()
    {
        global $FANNIE_LANES;
        // load complete record
        if (!$this->load()) {
            return false;
        }

        $current = $this->connection;
        // save to each lane
        foreach($FANNIE_LANES as $lane) {
            $sql = new SQLManager($lane['host'],$lane['type'],$lane['op'],
                        $lane['user'],$lane['pw']);    
            if (!is_object($sql) || $sql->connections[$lane['op']] === false) {
                continue;
            }
            $this->connection = $sql;
            $this->delete();
        }
        $this->connection = $current;

        return true;
    }

    protected function normalizeLanes($db_name, $mode=BasicModel::NORMALIZE_MODE_CHECK, $doCreate=False)
    {
        global $FANNIE_LANES, $FANNIE_OP_DB, $FANNIE_TRANS_DB;

        // map server db name to lane db name
        $lane_db = false;
        if ($db_name == $FANNIE_OP_DB) {
            $lane_db = 'op';
        } else if ($db_name == $FANNIE_TRANS_DB) {
            $lane_db = 'trans';
        }

        if ($lane_db === false) {
            return false;
        }

        $this->currently_normalizing_lane = true;

        $current = $this->connection;
        $save_fq = $this->fq_name;
        // call normalize() on each lane
        foreach($FANNIE_LANES as $lane) {
            $sql = new SQLManager($lane['host'],$lane['type'],$lane[$lane_db],
                        $lane['user'],$lane['pw']);    
            if (!is_object($sql) || $sql->connections[$lane[$lane_db]] === false) {
                continue;
            }
            $this->connection = $sql;
            
            $this->fq_name = $lane[$lane_db] . $sql->sep() . $this->name;
            $this->normalize($db_name, $mode, $doCreate);
        }
        $this->connection = $current;
        $this->fq_name = $save_fq;

        $this->currently_normalizing_lane = false;

        return true;
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
    public function normalize($db_name, $mode=BasicModel::NORMALIZE_MODE_CHECK, $doCreate=False)
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

        /**
          FannieDB only manages server connections.
          If normalize is called in lane mode, the 
          calling function is responsible for
          initializing the connection.
        */
        if (!$this->currently_normalizing_lane) {
            $this->connection = FannieDB::get($db_name);
        }

        if (!$this->connection->table_exists($this->name)) {
            if ($mode == BasicModel::NORMALIZE_MODE_CHECK) {
                echo "Table {$this->name} not found!\n";
                echo "==========================================\n";
                printf("%s table %s\n","Check complete. Need to create", $this->name);
                echo "==========================================\n\n";
                return 999;
            } else if ($mode == BasicModel::NORMALIZE_MODE_APPLY) {
                echo "==========================================\n";
                if ($doCreate) {
                    $cResult = $this->create(); 
                    printf("Update complete. Creation of table %s %s\n",$this->name, ($cResult)?"OK":"failed");
                    // create succeeded, normalize_lanes enabled
                    if ($cResult && $this->normalize_lanes && !$this->currently_normalizing_lane) {
                        $this->normalizeLanes($db_name, $mode, $doCreate);
                    }
                } else {
                    printf("Update complete. Creation of table %s %s\n",$this->name, ($doCreate)?"OK":"not supported");
                }
                echo "==========================================\n\n";
                return true;
            }
        }

        // get lowercased versions of the class' column names
        // and the current table's column names to check for
        // case mismatches
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

        $new_columns = array();
        $new_indexes = array();
        $unknown = array();
        $recase_columns = array();
        $redo_pk = false;
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
            } else if (!in_array($col_name, array_keys($current)) && isset($definition['replaces']) && in_array($definition['replaces'], array_keys($current))) {
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
            } else if (in_array($col_name, array_keys($current))) {
                $type = $this->getMeta($this->columns[$col_name]['type'], $this->connection->dbms_name());
                $rebuild = false;
                if (strtoupper($type) != $current[$col_name]['type']) {
                    printf("%s column %s from %s to %s\n", 
                            ($mode==BasicModel::NORMALIZE_MODE_CHECK)?"Need to change":"Changing", 
                            $col_name, $current[$col_name]['type'], $type);
                    $rebuild = true;
                } else if (isset($our_columns[$col_name]['default']) && $our_columns[$col_name]['default'] != $current[$col_name]['default']) {
                    printf("%s column %s default value from %s to %s\n", 
                            ($mode==BasicModel::NORMALIZE_MODE_CHECK)?"Need to change":"Changing", 
                            $col_name, $current[$col_name]['default'], $our_columns[$col_name]['default']);
                    $rebuild = true;
                } else if (isset($our_columns[$col_name]['increment']) && $our_columns[$col_name]['increment'] && $current[$col_name]['increment'] === false) {
                    printf("%s for column %s\n", 
                            ($mode==BasicModel::NORMALIZE_MODE_CHECK)?"Need to set increment":"Setting increment", 
                            $col_name);
                    $rebuild = true;
                } else if (isset($our_columns[$col_name]['primary_key']) && $our_columns[$col_name]['primary_key'] && $current[$col_name]['primary_key'] === false) {
                    $redo_pk = true;
                }
                if ($rebuild) {
                    $sql = 'ALTER TABLE ' . $this->connection->identifier_escape($this->name) . ' CHANGE COLUMN '
                            . $this->connection->identifier_escape($col_name) . ' '
                            . $this->connection->identifier_escape($col_name) . ' '
                            . $this->getMeta($this->columns[$col_name]['type'], $this->connection->dbms_name());
                    if (isset($this->columns[$col_name]['default'])) {
                        $sql .= ' DEFAULT '.$this->columns[$col_name]['default'];
                    }
                    if (isset($this->columns[$col_name]['not_null'])) {
                        $sql .= ' NOT NULL';
                    }
                    if (isset($this->columns[$col_name]['increment']) && $this->columns[$col_name]['increment']) {
                        if ($this->connection->dbms_name() == 'mssql') {
                            $sql .= ' IDENTITY (1, 1) NOT NULL';
                        } else {
                            $sql .= ' NOT NULL AUTO_INCREMENT';
                        }
                        if ($current[$col_name]['primary_key'] !== true) {
                            // increment must be indexed or PK
                            // only use PK on single-column PK
                            $index = 'INDEX';
                            if (isset($this->columns[$col_name]['primary_key']) && $this->columns[$col_name]['primary_key']) {
                                $count_pk = 0;
                                foreach ($this->columns as $col) {
                                    if (isset($col['primary_key']) && $col['primary_key']) {
                                        $pk_count++;
                                    }
                                }
                                if ($pk_count == 1) {
                                    $index = 'PRIMARY KEY ';
                                    $redo_pk = false;
                                }
                            }
                            $sql .= ', ADD ' . $index . ' (' . $this->connection->identifier_escape($this->columns[$col_name]) . ')'; 
                        }
                    }
                    printf("\tSQL Details: %s\n", $sql);
                    $recase_columns[] = $col_name;
                    if ($mode == BasicModel::NORMALIZE_MODE_APPLY) {
                        $modified = $this->connection->query($sql);
                    }
                }
            } else if (!in_array($col_name,array_keys($current))) {
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
        for($i=0;$i<count($our_columns);$i++) {
            if (!in_array($our_columns[$i],$new_columns)) {
                continue; // column already exists
            }
            printf("%s column: %s\n", 
                    ($mode==BasicModel::NORMALIZE_MODE_CHECK)?"Need to add":"Adding", 
                    "{$our_columns[$i]}"
            );
            $sql = '';
            foreach($their_columns as $their_col) {
                if (isset($our_columns[$i-1]) && $our_columns[$i-1] == $their_col) {
                    $sql = 'ALTER TABLE '.$this->name.' ADD COLUMN '
                        .$this->connection->identifier_escape($our_columns[$i]).' '
                        .$this->getMeta($this->columns[$our_columns[$i]]['type'],
                            $this->connection->dbms_name());
                    if (isset($this->columns[$our_columns[$i]]['default'])) {
                        $sql .= ' DEFAULT '.$this->columns[$our_columns[$i]]['default'];
                    }
                    if (isset($this->columns[$our_columns[$i]]['not_null'])) {
                        $sql .= ' NOT NULL';
                    }
                    if (isset($this->columns[$our_columns[$i]]['increment']) && $this->columns[$our_columns[$i]]['increment']) {
                        if ($this->connection->dbms_name() == 'mssql') {
                            $sql .= ' IDENTITY (1, 1) NOT NULL';
                        } else {
                            $sql .= ' NOT NULL AUTO_INCREMENT';
                        }
                    }
                    $sql .= ' AFTER '.$this->connection->identifier_escape($their_col);
                    if (isset($this->columns[$our_columns[$i]]['increment']) && $this->columns[$our_columns[$i]]['increment']) {
                        // increment must be indexed
                        $index = 'INDEX';
                        if (isset($this->columns[$our_columns[$i]]['primary_key']) && $this->columns[$our_columns[$i]]['primary_key']) {
                            $index = 'PRIMARY KEY ';
                        }
                        $sql .= ', ADD ' . $index . ' (' . $this->connection->identifier_escape($our_columns[$i]) . ')'; 
                    }
                    break;
                } elseif (isset($our_columns[$i+1]) && $our_columns[$i+1] == $their_col) {
                    $sql = 'ALTER TABLE '.$this->name.' ADD COLUMN '
                        .$this->connection->identifier_escape($our_columns[$i]).' '
                        .$this->getMeta($this->columns[$our_columns[$i]]['type'],
                            $this->connection->dbms_name());
                    if (isset($this->columns[$our_columns[$i]]['default'])) {
                        $sql .= ' DEFAULT '.$this->columns[$our_columns[$i]]['default'];
                    }
                    if (isset($this->columns[$our_columns[$i]]['not_null'])) {
                        $sql .= ' NOT NULL';
                    }
                    if (isset($this->columns[$our_columns[$i]]['increment']) && $this->columns[$our_columns[$i]]['increment']) {
                        if ($this->connection->dbms_name() == 'mssql') {
                            $sql .= ' IDENTITY (1, 1) NOT NULL';
                        } else {
                            $sql .= ' NOT NULL AUTO_INCREMENT';
                        }
                    }
                    $sql .= ' FIRST';
                    if (isset($this->columns[$our_columns[$i]]['increment']) && $this->columns[$our_columns[$i]]['increment']) {
                        // increment must be indexed
                        $index = 'INDEX';
                        if (isset($this->columns[$our_columns[$i]]['primary_key']) && $this->columns[$our_columns[$i]]['primary_key']) {
                            $index = 'PRIMARY KEY ';
                        }
                        $sql .= ', ADD ' . $index . ' (' . $this->connection->identifier_escape($our_columns[$i]) . ')'; 
                    }
                    break;
                }
                if (isset($our_columns[$i-1]) && in_array($our_columns[$i-1],$new_columns)) {
                    $sql = 'ALTER TABLE '.$this->name.' ADD COLUMN '
                        .$this->connection->identifier_escape($our_columns[$i]).' '
                        .$this->getMeta($this->columns[$our_columns[$i]]['type'],
                            $this->connection->dbms_name());
                    if (isset($this->columns[$our_columns[$i]]['default'])) {
                        $sql .= ' DEFAULT '.$this->columns[$our_columns[$i]]['default'];
                    }
                    if (isset($this->columns[$our_columns[$i]]['not_null'])) {
                        $sql .= ' NOT NULL';
                    }
                    if (isset($this->columns[$our_columns[$i]]['increment']) && $this->columns[$our_columns[$i]]['increment']) {
                        if ($this->connection->dbms_name() == 'mssql') {
                            $sql .= ' IDENTITY (1, 1) NOT NULL';
                        } else {
                            $sql .= ' NOT NULL AUTO_INCREMENT';
                        }
                    }
                    $sql .= ' AFTER '.$this->connection->identifier_escape($our_columns[$i-1]);
                    if (isset($this->columns[$our_columns[$i]]['increment']) && $this->columns[$our_columns[$i]]['increment']) {
                        // increment must be indexed
                        $index = 'INDEX';
                        if (isset($this->columns[$our_columns[$i]]['primary_key']) && $this->columns[$our_columns[$i]]['primary_key']) {
                            $index = 'PRIMARY KEY ';
                        }
                        $sql .= ', ADD ' . $index . ' (' . $this->connection->identifier_escape($our_columns[$i]) . ')'; 
                    }
                    break;
                }
            }
            if ($sql !== '') {
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

            // If the new column is indexed create the index.
            if ($sql !== '' && isset($this->columns[$our_columns[$i]]['index'])
                && $this->columns[$our_columns[$i]]['index']) {
                $new_indexes[]=$our_columns[$i];
                $index_sql = 'ALTER TABLE '.$this->name.' ADD INDEX '
                        .$this->connection->identifier_escape($our_columns[$i])
                        .' ('.$this->connection->identifier_escape($our_columns[$i]).')';
                if ($mode == BasicModel::NORMALIZE_MODE_CHECK) {
                    echo "Need to add index to column: {$our_columns[$i]}\n";
                    echo "\tSQL Details: $index_sql\n";
                } else if ($mode == BasicModel::NORMALIZE_MODE_APPLY) {
                    echo "Adding index to column: {$our_columns[$i]}\n";
                    $this->connection->query($index_sql);
                }
            }
        }
        if ($redo_pk) {
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
                if (isset($info['primary_key']) && $info['primary_key']) {
                    $sql .= $this->connection->identifier_escape($col_name) . ',';
                }
            }
            $sql = substr($sql, 0, strlen($sql)-1);
            $sql .= ')';
            echo "\tSQL Details: $sql\n";
            $new_indexes[] = 'PRIMARY KEY';
        }
        $alters = count($new_columns) + count($recase_columns);
        echo "==========================================\n";
        printf("%s %d column%s  %d index%s.\n",
            ($mode==BasicModel::NORMALIZE_MODE_CHECK)?"Check complete. Need to adjust":"Update complete. Added",
            $alters, ($alters!=1)?"s":"",
            count($new_indexes), (count($new_indexes)!=1)?"es":""
            );
        echo "==========================================\n\n";

        // apply updates to lanes as well
        if ($mode == BasicModel::NORMALIZE_MODE_APPLY && $this->normalize_lanes && !$this->currently_normalizing_lane && count($new_columns) > 0) {
            $this->normalizeLanes($db_name, $mode, $doCreate);
        }

        if ($alters > 0) {
            return $alters;
        } else if (count($unknown) > 0) {
            return -1*count($unknown);
        }

        return 0;

    // normalize()
    }

    /**
      Array of hook objects associated with this table
    */
    protected $hooks = array();
    /**
      Search available classes to load applicable
      hook objects into this instance
    */
    protected function loadHooks()
    {
       $this->hooks = array();
       if (class_exists('FannieAPI')) {
           $hook_classes = FannieAPI::listModules('BasicModelHook');
           foreach($hook_classes as $class) {
                if (!class_exists($class)) continue;
                $hook_obj = new $class();
                if ($hook_obj->operatesOnTable($this->name)) {
                    $this->hooks[] = $hook_obj;
                }
           }
       }
    }

    /**
      Rewrite the given file to create accessor
      functions for all of its columns
    */
    public function generate($filename)
    {
        $start_marker = '/* START ACCESSOR FUNCTIONS */';
        $end_marker = '/* END ACCESSOR FUNCTIONS */';
        $before = '';
        $after = '';
        $foundStart = false;
        $foundEnd = false;
        $fp = fopen($filename,'r');
        while(!feof($fp)) {
            $line = fgets($fp);
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
        fclose($fp);

        if (!$foundStart || !$foundEnd) {
            echo "Error: could not locate code block\n";
            if (!$foundStart) echo "Missing start\n";
            if (!$foundEnd) echo "Missing end\n";
            return false;
        }

        $fp = fopen($filename,'w');
        fwrite($fp,$before);
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
            fwrite($fp,"\n");
            fwrite($fp,"    public function ".$method_name."()\n");
            fwrite($fp,"    {\n");
            fwrite($fp,"        if(func_num_args() == 0) {\n");
            fwrite($fp,'            if(isset($this->instance["'.$name.'"])) {'."\n");
            fwrite($fp,'                return $this->instance["'.$name.'"];'."\n");
            fwrite($fp,'            } else if (isset($this->columns["'.$name.'"]["default"])) {'."\n");
            fwrite($fp,'                return $this->columns["'.$name.'"]["default"];'."\n");
            fwrite($fp,"            } else {\n");
            fwrite($fp,"                return null;\n");
            fwrite($fp,"            }\n");
            fwrite($fp,"        } else if (func_num_args() > 1) {\n");
            fwrite($fp,'            $value = func_get_arg(0);'."\n");
            fwrite($fp,'            $op = $this->validateOp(func_get_arg(1));'."\n");
            fwrite($fp,'            if ($op === false) {'."\n");
            fwrite($fp,'                throw new Exception(\'Invalid operator: \' . func_get_arg(1));'."\n");
            fwrite($fp,"            }\n");
            fwrite($fp,'            $filter = array('."\n");
            fwrite($fp,'                \'left\' => \''.$name.'\','."\n");
            fwrite($fp,'                \'right\' => $value,'."\n");
            fwrite($fp,'                \'op\' => $op,'."\n");
            fwrite($fp,'                \'rightIsLiteral\' => false,'."\n");
            fwrite($fp,"            );\n");
            fwrite($fp,'            if (func_num_args() > 2 && func_get_arg(2) === true) {'."\n");
            fwrite($fp,'                $filter[\'rightIsLiteral\'] = true;'."\n");
            fwrite($fp,"            }\n");
            fwrite($fp,'            $this->filters[] = $filter;'."\n");
            fwrite($fp,"        } else {\n");
            fwrite($fp,'            if (!isset($this->instance["'.$name.'"]) || $this->instance["'.$name.'"] != func_get_args(0)) {'."\n");
            fwrite($fp,'                if (!isset($this->columns["'.$name.'"]["ignore_updates"]) || $this->columns["'.$name.'"]["ignore_updates"] == false) {'."\n");
            fwrite($fp,'                    $this->record_changed = true;'."\n");
            fwrite($fp,"                }\n");
            fwrite($fp,"            }\n");
            fwrite($fp,'            $this->instance["'.$name.'"] = func_get_arg(0);'."\n");
            fwrite($fp,"        }\n");
            fwrite($fp,'        return $this;'."\n");
            fwrite($fp,"    }\n");
        }
        fwrite($fp,$after);
        fclose($fp);

        return true;
    // generate()
    }

    public function newModel($name, $as_view=false)
    {
        $fp = fopen($name.'.php','w');
        fwrite($fp, chr(60)."?php
/*******************************************************************************

    Copyright ".date("Y")." Whole Foods Co-op

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

/**
  @class $name
*/
class $name extends " . ($as_view ? 'ViewModel' : 'BasicModel') . "\n");
        fwrite($fp,"{\n");
        fwrite($fp,"\n");
        fwrite($fp,"    protected \$name = \"".substr($name,0,strlen($name)-5)."\";\n");
        fwrite($fp,"\n");
        fwrite($fp,"    protected \$columns = array(\n    );\n");
        fwrite($fp,"\n");
        if ($as_view) {
            fwrite($fp,"    public function definition()\n");
            fwrite($fp,"    {\n");
            fwrite($fp,"    }\n");
        }
        fwrite($fp,"\n");
        fwrite($fp,"    /* START ACCESSOR FUNCTIONS */\n");
        fwrite($fp,"    /* END ACCESSOR FUNCTIONS */\n");
        fwrite($fp,"}\n");
        fwrite($fp,"\n");
        fclose($fp);

    // newModel()
    }

    public function getModels()
    {
        /**
          Experiment using lambdas. I was curious
          if I could do recursion without having
          a named function.
        */
        $search = function($path) use (&$search) {
            if (is_file($path) && substr($path,'-4')=='.php') {
                include_once($path);
                $class = basename(substr($path,0,strlen($path)-4));
                if (class_exists($class) && is_subclass_of($class, 'BasicModel')) {
                    return array($class);
                }
            } elseif(is_dir($path)) {
                $dh = opendir($path);
                $ret = array();    
                while( ($file=readdir($dh)) !== False) {
                    if ($file == '.' || $file == '..') {
                        continue;
                    }
                    $ret = array_merge($ret, $search($path.'/'.$file));
                }
                return $ret;
            }
            return array();
        };
        $models = $search(dirname(__FILE__));

        return $models;
    }
}

if (php_sapi_name() === 'cli' && basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {

    $obj = new BasicModel(null);

    /* Argument signatures, to php, where BasicModel.php is the first:
   * 2 args: Generate Accessor Functions: php BasicModel.php <Subclass Filename>\n";
   * 3 args: Create new Model: php BasicModel.php --new <Model Name>\n";
   * 4 args: Update Table Structure: php BasicModel.php --update <Database name> <Subclass Filename[[Model].php]>\n";
    */
    if (($argc < 2 || $argc > 4) || ($argc == 3 && $argv[1] != "--new" && $argv[1] != '--new-view') || ($argc == 4 && $argv[1] != '--update')) {
        echo "Generate Accessor Functions: php BasicModel.php <Subclass Filename>\n";
        echo "Create new Model: php BasicModel.php --new <Model Name>\n";
        echo "Create new View Model: php BasicModel.php --new-view <Model Name>\n";
        echo "Update Table Structure: php BasicModel.php --update <Database name> <Subclass Filename>\n";
        exit;
    }

    include(dirname(__FILE__).'/../../../config.php');
    include_once(dirname(__FILE__).'/../../FannieAPI.php');

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
        $obj = new BasicModel(null);
        $as_view = $argv[1] == '--new-view' ? true : false;
        $obj->newModel($modelname, $as_view);
        exit;
    }

    /**
      Generate all is purposely undocumented write now. It's useful
      if updating the acutal column methods since they need to then
      be rebuilt for every file, but it also has the potential to 
      make a giant mess.
    */
    if ($argc == 2 && $argv[1] == '--generate-all') {
        $all = FannieAPI::listModules('BasicModel');
        foreach ($all as $model_class) {
            echo 'Class ' . $model_class . "\n";
            $reflector = new ReflectionClass($model_class);
            $filename = $reflector->getFileName();
            if (!is_writable($filename)) {
                echo 'ERROR: cannot rewrite file: ' . $filename . "\n";
                continue;
            } else {
                $writer = new $model_class(null);
                $writer->generate($filename);
            }
        }
        exit;
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
        exit;
    }

    $class = pathinfo($classfile, PATHINFO_FILENAME);
    include($classfile);
    if (!class_exists($class)) {
        echo "Error: class '$class' does not exist\n";
        exit;
    }

    // A new object of the type named on the command line.
    $obj = new $class(null);
    if (!is_a($obj, 'BasicModel')) {
        echo "Error: invalid class. Must be BasicModel\n";
        exit;
    }

    // Generate accessor functions
    if ($argc == 2) {
        $try = $obj->generate($classfile);
        if ($try) {
            echo "Generated accessor functions\n";
        } else {
            echo "Failed to generate functions\n";
        }
    } else if ($argc == 4) {
        // Update Table Structure
        // Show what changes are needed but don't make them yet.
        $try = $obj->normalize($argv[2],BasicModel::NORMALIZE_MODE_CHECK);
        // If there was no error and there is anything to change,
        //  including creating the table.
        // Was: If the table exists and there is anything to change
        //  get OK to change.
        if ($try !== false && $try > 0) {
            while(true) {
                echo 'Apply Changes [Y/n]: ';
                $in = rtrim(fgets(STDIN));
                if ($in === 'n' || $in === false || $in === '') {
                    echo "Goodbye.\n";
                    break;
                } elseif($in ==='Y') {
                    // THIS WILL APPLY PROPOSED CHANGES!
                    $obj->normalize($argv[2], BasicModel::NORMALIZE_MODE_APPLY, true);
                    break;
                }
            }
        }
    }
    exit;
}

