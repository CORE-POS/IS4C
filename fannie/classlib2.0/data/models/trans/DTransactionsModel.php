<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

class DTransactionsModel extends BasicModel 
{

    protected $name = 'dtransactions';

    protected $preferred_db = 'trans';

    protected $columns = array(
    'datetime'    => array('type'=>'DATETIME','index'=>True),
    'store_id'    => array('type'=>'SMALLINT'),
    'register_no'    => array('type'=>'SMALLINT'),
    'emp_no'    => array('type'=>'SMALLINT'),
    'trans_no'    => array('type'=>'INT'),
    'upc'        => array('type'=>'VARCHAR(13)','index'=>True),
    'description'    => array('type'=>'VARCHAR(30)'),
    'trans_type'    => array('type'=>'VARCHAR(1)','index'=>True),
    'trans_subtype'    => array('type'=>'VARCHAR(2)'),
    'trans_status'    => array('type'=>'VARCHAR(1)'),
    'department'    => array('type'=>'SMALLINT','index'=>True),
    'quantity'    => array('type'=>'DOUBLE'),
    'scale'        => array('type'=>'TINYINT','default'=>0.00),
    'cost'        => array('type'=>'MONEY'),
    'unitPrice'    => array('type'=>'MONEY'),
    'total'        => array('type'=>'MONEY'),
    'regPrice'    => array('type'=>'MONEY'),
    'tax'        => array('type'=>'SMALLINT'),
    'foodstamp'    => array('type'=>'TINYINT'),
    'discount'    => array('type'=>'MONEY'),
    'memDiscount'    => array('type'=>'MONEY'),
    'discountable'    => array('type'=>'TINYINT'),
    'discounttype'    => array('type'=>'TINYINT'),
    'voided'    => array('type'=>'TINYINT'),
    'percentDiscount'=> array('type'=>'TINYINT'),
    'ItemQtty'    => array('type'=>'DOUBLE'),
    'volDiscType'    => array('type'=>'TINYINT'),
    'volume'    => array('type'=>'TINYINT'),
    'VolSpecial'    => array('type'=>'MONEY'),
    'mixMatch'    => array('type'=>'VARCHAR(13)'),
    'matched'    => array('type'=>'SMALLINT'),
    'memType'    => array('type'=>'TINYINT'),
    'staff'        => array('type'=>'TINYINT'),
    'numflag'    => array('type'=>'INT','default'=>0),
    'charflag'    => array('type'=>'VARCHAR(2)','default'=>"''"),
    'card_no'    => array('type'=>'INT','index'=>True),
    'trans_id'    => array('type'=>'INT'),
    'pos_row_id' => array('type'=>'BIGINT UNSIGNED', 'index'=>true),
    'store_row_id' => array('type'=>'BIGINT UNSIGNED', 'increment'=>true, 'index'=>true),
    );

    /**
      Overrides (extends) the base function to check multiple tables that should
      all have identical or similar structure
        after doing a normal run of the base.
    */
    public function normalize($db_name, $mode=BasicModel::NORMALIZE_MODE_CHECK, $doCreate=false)
    {
        $config = FannieConfig::factory();
        $FANNIE_TRANS_DB = $config->get('TRANS_DB');
        $FANNIE_ARCHIVE_DB = $config->get('ARCHIVE_DB');
        $FANNIE_ARCHIVE_METHOD = $config->get('ARCHIVE_METHOD');
        $trans_adds = 0;
        $log_adds = 0;

        //EL If this isn't initialized it is "dlog_15" on the 2nd, preview_only=false run
        $this->name = 'dtransactions';
        // check self first
        $chk = parent::normalize($db_name, $mode, $doCreate);
        if ($chk !== false) {
            $trans_adds += $chk;
        }
        $this->columns['store_row_id']['increment'] = false;
        $this->columns['store_row_id']['primary_key'] = false;
        $this->columns['store_row_id']['index'] = false;
        $this->columns['pos_row_id']['index'] = false;
        
        $this->name = 'transarchive';
        $chk = parent::normalize($db_name, $mode, $doCreate);
        if ($chk !== false) {
            $trans_adds += $chk;
        }

        $this->name = 'suspended';
        $tmp1 = $this->columns['store_row_id'];
        $tmp2 = $this->columns['pos_row_id'];
        unset($this->columns['store_row_id']);
        unset($this->columns['pos_row_id']);
        $chk = parent::normalize($db_name, $mode, $doCreate);
        if ($chk !== false) {
            $trans_adds += $chk;
        }
        $this->columns['pos_row_id'] = $tmp2;
        $this->columns['store_row_id'] = $tmp1;

        $this->connection = FannieDB::get($FANNIE_ARCHIVE_DB);
        $dated_tables = array();
        if ($FANNIE_ARCHIVE_METHOD == 'partitions') {
            $this->name = 'bigArchive';
            $chk = parent::normalize($FANNIE_ARCHIVE_DB, $mode, $doCreate);
            if ($chk !== false) {
                $trans_adds += $chk;
            }
        } else {
            $pattern = '/^transArchive\d\d\d\d\d\d$/';
            $tables = $this->connection->getTables($FANNIE_ARCHIVE_DB);
            foreach($tables as $t) {
                if (preg_match($pattern,$t)) {
                    $this->name = $t;
                    $chk = parent::normalize($FANNIE_ARCHIVE_DB, $mode, $doCreate);
                    if ($chk !== False) {
                        $trans_adds += $chk;
                    }
                    // track existing monthly archives by date string
                    $dated_tables[substr($t, -6)] = true;
                }
            }
        }
    
        // move on to dlog views.
        // dlog_15 is used for detection since it's the only
        // actual table.
        // In the model the datestamp field datetime is swapped out for tdate
        // and trans_num is tacked on the end
        $this->connection = FannieDB::get($FANNIE_TRANS_DB);
        $this->name = 'dlog_15';
        unset($this->columns['datetime']);
        $tdate = array('tdate'=>array('type'=>'datetime','index'=>True));
        $trans_num = array('trans_num'=>array('type'=>'VARCHAR(25)'));
        $this->columns = $tdate + $this->columns + $trans_num;
        $chk = parent::normalize($db_name, $mode, $doCreate);
        if ($chk !== false) {
            $log_adds += $chk;
        }

        // rebuild views
        // use BasicModel::normalize in check mode to detect missing columns
        // the ALTER queries it suggests won't work but the return value is
        // still correct. If it returns > 0, the view needs to be rebuilt
        $this->name = 'dlog';
        ob_start();
        $chk = parent::normalize($db_name, BasicModel::NORMALIZE_MODE_CHECK);
        ob_end_clean();
        if ($chk !== false && $chk > 0) {
            $log_adds += $chk;
            $this->normalizeLog('dlog','dtransactions',$mode);
        }
        $this->name = 'dlog_90_view';
        ob_start();
        $chk = parent::normalize($db_name, BasicModel::NORMALIZE_MODE_CHECK);
        ob_end_clean();
        if ($chk !== false && $chk > 0) {
            $log_adds += $chk;
            $this->normalizeLog('dlog_90_view','transarchive',$mode);
        }

        $this->connection = FannieDB::get($FANNIE_ARCHIVE_DB);
        if ($FANNIE_ARCHIVE_METHOD == 'partitions') {
            $this->name = 'dlogBig';
            ob_start();
            $chk = parent::normalize($FANNIE_ARCHIVE_DB, BasicModel::NORMALIZE_MODE_CHECK);
            ob_end_clean();
            if ($chk !== false && $chk > 0) {
                $log_adds += $chk;
                $this->normalizeLog('dlogBig','bigArchive',$mode);
            }
        } else {
            $pattern = '/^dlog\d\d\d\d\d\d$/';
            $tables = $this->connection->getTables($FANNIE_ARCHIVE_DB);
            foreach($tables as $t) {
                if (preg_match($pattern,$t)) {
                    $this->name = $t;
                    ob_start();
                    $chk = parent::normalize($FANNIE_ARCHIVE_DB, BasicModel::NORMALIZE_MODE_CHECK);
                    ob_end_clean();
                    if ($chk !== false && $chk > 0) {
                        $log_adds += $chk;
                        $this->normalizeLog($t, 'transArchive'.substr($t,4),$mode);
                    }
                    unset($dated_tables[substr($t, -6)]);
                }
            }
            // create any missing dlogs for existing month tables
            foreach ($dated_tables as $date => $val) {
                ob_start();
                $chk = parent::normalize($FANNIE_ARCHIVE_DB, BasicModel::NORMALIZE_MODE_CHECK);
                ob_end_clean();
                if ($chk !== false && $chk > 0) {
                    $log_adds += $chk;
                    $this->normalizeLog('dlog' . $date, 'transArchive'. $date,$mode);
                }
            }
        }

        // EL: Need to restore $this-columns to original values.
        $this->connection = FannieDB::get($FANNIE_TRANS_DB);
        unset($this->columns['tdate']);
        unset($this->columns['trans_num']);
        $datetime = array('datetime'=>array('type'=>'datetime','index'=>true));
        $this->columns = $datetime + $this->columns;
        $this->columns['store_row_id']['increment'] = true;
        $this->columns['store_row_id']['primary_key'] = true;
        $this->columns['store_row_id']['index'] = false;
        $this->columns['pos_row_id']['index'] = true;

        return $log_adds + $trans_adds;

    // normalize()
    }

    public function dlogMode($switch)
    {
        if ($switch) {
            unset($this->columns['datetime']);
            $tdate = array('tdate'=>array('type'=>'datetime','index'=>True));
            $trans_num = array('trans_num'=>array('type'=>'VARCHAR(25)'));
            $this->columns = $tdate + $this->columns + $trans_num;
        } else {
            unset($this->columns['tdate']);
            unset($this->columns['trans_num']);
            $datetime = array('datetime'=>array('type'=>'datetime','index'=>true));
        }
    }

    /**
      Rebuild dlog style views
      @param $view_name name of the view
      @param $table_name underlying table
      @param $mode the normalization mode. See BasicModel.

      The view changes the column "datetime" to "tdate" and
      adds a "trans_num" column. Otherwise it includes all
      the columns from dtransactions. Columns "trans_type"
      and "trans_subtype" still have translations to fix
      older records but everyting else passes through as-is.
    */
    public function normalizeLog($view_name, $table_name, $mode=BasicModel::NORMALIZE_MODE_CHECK)
    {
        printf("%s view: %s",
            ($mode==BasicModel::NORMALIZE_MODE_CHECK)?"Would recreate":"Recreating", 
            "$view_name (of table $table_name)\n"
        );
        if ($this->connection->table_exists($view_name)) {
            $sql = 'DROP VIEW '.$this->connection->identifierEscape($view_name);
            if ($mode == BasicModel::NORMALIZE_MODE_APPLY) {
                $this->connection->query($sql);
            }
        }

        $sql = 'CREATE VIEW '.$this->connection->identifierEscape($view_name).' AS '
            .'SELECT '
            .$this->connection->identifierEscape('datetime').' AS '
            .$this->connection->identifierEscape('tdate').',';
        $c = $this->connection; // for more concise code below
        foreach($this->columns as $name => $definition) {
            if ($name == 'datetime') continue;
            elseif ($name == 'tdate') continue;
            elseif ($name == 'trans_num'){
                // create trans_num field
                $sql .= $c->concat(
                $c->convert($c->identifierEscape('emp_no'),'char'),
                "'-'",
                $c->convert($c->identifierEscape('register_no'),'char'),
                "'-'",
                $c->convert($c->identifierEscape('trans_no'),'char'),
                ''
                ).' as trans_num';
            } elseif($name == 'trans_type') {
                // type conversion for old records. Newer coupon & discount
                // records should have correct trans_type when initially created
                $sql .= "CASE WHEN (trans_subtype IN ('CP','IC') THEN 'T' 
                    WHEN upc = 'DISCOUNT' THEN 'S' ELSE trans_type END AS trans_type,\n";
            } else {
                $sql .= $c->identifierEscape($name).",\n";
            }
        }
        $sql = preg_replace("/,\n$/","\n",$sql);
        $sql .= ' FROM '.$c->identifierEscape($table_name)
            .' WHERE '.$c->identifierEscape('trans_status')
            ." NOT IN ('D','X','Z') AND emp_no <> 9999
            AND register_no <> 99";
        // for plain "dlog" view, add a date restriction
        if ($view_name == 'dlog') {
            $sql .= ' AND datetime >= ' . $c->curdate();
        }
        if ($mode == BasicModel::NORMALIZE_MODE_APPLY) {
            $this->connection->query($sql);
        }

    // normalizeLog()
    }

    static public function selectDlog($start, $end=false, $where=false)
    {
        return self::selectStruct(True, $start, $end, $where);
    }

    static public function select_dlog($start, $end=false, $where=false)
    {
        return self::selectDlog($start, $end, $where);
    }

    static public function selectDtrans($start, $end=false, $where=false)
    {
        return self::selectStruct(False, $start, $end, $where);
    }

    static public function select_dtrans($start, $end=false, $where=false)
    {
        return self::selectDtrans($start, $end, $where);
    }

    /* Return the SQL FROM parameter for a given date range
     *  i.e. the table, view or union of tables or views
     *  in which the transaction records can be found
     *  most efficiently.
     * @param $dlog [boolean]
     *   => true means dlog view style
     *   => false means underlying dtransactions table style
     * @param $start [datetime] date range start
     * @param $end [datetime] date range end
     *   => false implies just the date specified by $start
     * @param $where [array] of SQL conditions and parameters
     *   => false implies no conditions
     *
     * @return [string] qualified table name
     *   Note: return value may be a SQL string that can be
     *   used as if it were a qualified table name
    */
    static private function selectStruct($dlog, $start, $end=false, $where=false)
    {
        $config = FannieConfig::factory();
        $sep = ($config->get('SERVER_DBMS') == 'MSSQL') ? '.dbo.' : '.';
        $FANNIE_TRANS_DB = $config->get('TRANS_DB');
        $FANNIE_ARCHIVE_DB = $config->get('ARCHIVE_DB');
        $FANNIE_ARCHIVE_METHOD = $config->get('ARCHIVE_METHOD');

        /**
          Where clause takes precedence if present
        */
        if (is_array($where) && isset($where['connection']) && isset($where['clauses'])) {
            /**
              Create randomly named temporary table based
              on the structure of dlog_15 or transachive
            */
            $random_name = uniqid('temp'.rand(1000, 9999));
            $temp_table = $FANNIE_ARCHIVE_DB . $sep . $random_name;
            if ($dlog) {
                $source = $FANNIE_TRANS_DB . $sep . 'dlog_15';
            } else {
                $source = $FANNIE_TRANS_DB . $sep . 'transarchive';
            }
            $dbc = $where['connection'];
            $temp_name = $dbc->temporaryTable($temp_table, $source);

            /**
              If creating a temporary table failed (not
              supported by database?) continue on below and
              return the table(s) with the appropriate dates
            */
            if ($temp_name !== false) {

                /**
                  Create a skeleton query that has the appropriate
                  WHERE clauses
                */
                $date_col = $dlog ? 'tdate' : 'datetime';
                $populateQ = '
                    INSERT INTO ' . $temp_name . '
                    SELECT *
                    FROM __SOURCE_TABLE__
                    WHERE ' . $date_col . ' BETWEEN ? AND ? ';
                $params = array($start . ' 00:00:00', ($end ? $end : $start) . ' 23:59:59');
                foreach ($where['clauses'] as $clause) {
                    if (!isset($clause['sql']) || !isset($clause['params'])) {
                        // malformed value
                        continue;
                    }
                    $populateQ .= ' AND ' . $clause['sql'];
                    foreach ($clause['params'] as $p) {
                        $params[] = $p;
                    }
                }

                /**
                  Call self recursively to get the name of the table
                  or tables corresponding to the date range. If multiple
                  tables, use regex to lift table names out of the subquery
                */
                $source_tables = self::selectStruct($dlog, $start, $end);
                if (strstr($source_tables, ' UNION ')) {
                    preg_match_all('/\s+FROM\s+(\w+)\s+/', $source_tables, $matches);
                    $source_tables = array();
                    foreach ($matches[1] as $m) {
                        $source_tables[] = $m;
                    }
                } else {
                    $source_tables = array($source_tables);
                }

                /**
                  Loop through transaction table(s) and 
                  execute the skeleton query with the actual table
                  name slotted in.
                */
                $result = true;
                foreach ($source_tables as $source_table) {
                    $query = str_replace('__SOURCE_TABLE__', $source_table, $populateQ);
                    $prep = $dbc->prepare($query);
                    $result = $dbc->execute($prep, $params);
                    if ($result === false) {
                        // failed to insert data into temp table
                        break;
                    }
                }

                /**
                  In theory temporary table now has the right set
                  of records. Return the table's name to the caller.
                  If anything went wrong, fall through to below and
                  simply return table(s) that match the dates.
                */
                if ($result) {

                    return $temp_name;
                }
            }
        }

        if ($end === false) {
            $end = $start;
        }
        $start_ts = strtotime($start);
        $end_ts = strtotime($end);
    
        // today. return dlog/dtrans
        if (date('Y-m-d',$start_ts) == date('Y-m-d')) {
            return ($dlog) ? $FANNIE_TRANS_DB.$sep.'dlog' : $FANNIE_TRANS_DB.$sep.'dtransactions';
        }

        $days_ago_15 = mktime(0,0,0,date('n'),date('j')-15);    
        $days_ago_90 = mktime(0,0,0,date('n'),date('j')-90);

        // both in past 15 days => dlog_15. No dtrans equivalent
        if ($start_ts > $days_ago_15 && $end_ts > $days_ago_15 && $dlog) {
            return $FANNIE_TRANS_DB.$sep.'dlog_15';
        }

        // same month 
        if (date('Y',$start_ts) == date('Y',$end_ts) && date('n',$start_ts) == date('n',$end_ts)) {
            if ($FANNIE_ARCHIVE_METHOD == 'partitions') {
                return ($dlog) ? $FANNIE_ARCHIVE_DB.$sep.'dlogBig' : $FANNIE_ARCHIVE_DB.$sep.'bigArchive';
            } else {
                $yyyymm = date('Ym',$start_ts);
                return ($dlog) ? $FANNIE_ARCHIVE_DB.$sep.'dlog'.$yyyymm : $FANNIE_ARCHIVE_DB.$sep.'transArchive'.$yyyymm;
            }
        }

        // both in past 90 days => dlog_90_view/transarchive
        if ($start_ts > $days_ago_90 && $end_ts > $days_ago_90) {
            return ($dlog) ? $FANNIE_TRANS_DB.$sep.'dlog_90_view' : $FANNIE_TRANS_DB.$sep.'transarchive';
        }

        //
        // All further options are in the archive tables
        //
        
        // partitions are simple
        if ($FANNIE_ARCHIVE_METHOD == 'partitions') {
            return ($dlog) ? $FANNIE_ARCHIVE_DB.$sep.'dlogBig' : $FANNIE_ARCHIVE_DB.$sep.'bigArchive';
        }

        // monthly archives. build a union containing both dates.    
        $endstamp = mktime(0,0,0,date('n',$end_ts),1,date('Y',$end_ts));
        $startstamp = mktime(0,0,0,date('n',$start_ts),1,date('Y',$start_ts));
        $union = '(select * from ';        
        while($startstamp <= $endstamp) {
            $union .= $FANNIE_ARCHIVE_DB.$sep;
            $union .= ($dlog) ? 'dlog' : 'transArchive';
            $union .= date('Ym',$startstamp);
            $union .= ' union all select * from ';
            $startstamp = mktime(0,0,0,date('n',$startstamp)+1,1,date('Y',$startstamp));
        }
        $union = preg_replace('/ union all select \* from $/','',$union);
        $union .= ')';

        return $union;

    // selectStruct()
    }

    public static function aggregateDlog(SQLManager $connection, $start_date, $end_date, stdclass $where, $groupby=array())
    {
        return self::aggregateStruct($connection, true, $start_date, $end_date, $where, $groupby);
    }

    public static function aggregateDtrans(SQLManager $connection, $start_date, $end_date, stdclass $where, $groupby=array())
    {
        return self::aggregateStruct($connection, false, $start_date, $end_date, $where, $groupby);
    }

    public static function aggregateStruct(SQLManager $connection, $dlog, $start_date, $end_date, stdclass $where, $groupby=array())
    {
        $base_table = self::selectStruct($dlog, $start_date, $end_date);
        $dt_col = ($dlog) ? 'tdate' : 'datetime';
        $clone_table = ($dlog) ? 'dlog_15' : 'transarchive';

        /**
          Grouping is required
        */
        if (!is_array($groupby) || count($groupby) == 0) {
            return $base_table;
        }

        /**
          Validate group by columns
        */
        $model = new DTransactionsModel(null);
        $columns = $model->getColumns();
        $insert_cols = array();
        $select_cols = array();
        for ($i=0; $i<count($groupby); $i++) {
            $group = $groupby[$i];
            if (isset($columns[$group])) {
                $insert_cols[] = $group;
                $select_cols[] = $group;
            } elseif (preg_match('/(.+)\s+AS\s+(\w+)$/', $group, $matches)) {
                $col_definition = $matches[1];
                $col_alias = $matches[2];
                if (isset($columns[$col_alias])) {
                    $insert_cols[] = $col_alias;
                    $select_cols[] = $group;
                    $groupby[$i] = $col_definition;
                } else {
                    return $base_table;
                }
            } else {
                return $base_table;
            }
        }
        /**
          Always include a datetime column
        */
        if (!in_array($dt_col, $insert_cols)) {
            $insert_cols[] = $dt_col;
            $select_cols[] = 'MAX(' . $dt_col . ') AS ' . $dt_col;
        }

        /**
          Create randomly named temporary table based
          on the structure of dlog_15 or transachive
        */
        $config = FannieConfig::factory();
        $sep = $connection->sep();
        $random_name = uniqid('temp'.rand(1000, 9999));
        $temp_table = $config->get('ARCHIVE_DB') . $sep . $random_name;
        $clone_table = $config->get('TRANS_DB') . $sep . $clone_table;
        $temp_name = $connection->temporaryTable($temp_table, $clone_table);
        if ($temp_name === false) {
            return $base_table;
        }

        /**
          Build a query to insert aggregated rows into
          the temporary table
        */
        $query = 'INSERT INTO ' . $temp_name . '(';
        foreach ($insert_cols as $c) {
            $query .= $c . ',';
        }
        $query .= 'total, quantity) ';

        $query .= ' SELECT ';
        foreach ($select_cols as $c) {
            $query .= $c . ',';
        }
        /**
          Always aggregate by total & quantity
        */
        $query .= ' SUM(total) AS total, '
            . DTrans::sumQuantity() . ' AS quantity
            FROM __TRANSACTION_TABLE__
            WHERE ' . $dt_col . ' BETWEEN ? AND ? ';
        $params = array($start_date . ' 00:00:00', $end_date . ' 23:59:59');

        /**
          Add a where clause if one has been specified
        */
        if (property_exists($where, 'sql') && is_array($where->sql)) {
            foreach ($where->sql as $sql) {
                $query .= ' AND ' . $sql;
            }
        }
        if (property_exists($where, 'params') && is_array($where->params)) {
            foreach ($where->params as $p) {
                $params[] = $p;
            }
        }

        /**
          Add the group by clause
        */
        $query .= ' GROUP BY ';
        foreach ($groupby as $group) {
            $query .= $group . ',';
        }
        $query = substr($query, 0, strlen($query)-1);

        /**
          Split monthly archive union if needed
        */
        $source_tables = array();
        if (strstr($base_table, ' UNION ')) {
            preg_match_all('/\s+FROM\s+(\w+)\s+/', $base_table, $matches);
            foreach ($matches[1] as $m) {
                $source_tables[] = $m;
            }
        } else {
            $source_tables = array($base_table);
        }

        /**
          Load data into temporary table from source table(s)
          using built query
        */
        foreach ($source_tables as $source_table) {
            $insertQ = str_replace('__TRANSACTION_TABLE__', $source_table, $query);
            $prep = $connection->prepare($insertQ);
            if (!$connection->execute($prep, $params)) {
                return $base_table;
            }
        }

        return $temp_name;
    }

    public function doc()
    {
        return '
Use:
This is IT CORE\'s transaction log. A rather important table.

A transaction can be uniquely identified by:
date + register_no + emp_no + trans_no
A record in a transaction can be uniquely identified by:
date + register_no + emp_no + trans_no + trans_id
Note that "date" is not necessary datetime. All records
in a transaction don\'t always have the exact same time
to the second.

pos_row_id is an incrementing ID generated by a lane machine.
This value should be unique for each given register_no. The
store_row_id is an incrementing ID for this table. It should
be unique for every record but does not have any particular
meaning beyond that.

upc is generally a product. The column is always a varchar
here, regardless of dbms, because sometimes non-numeric
data goes here such as \'DISCOUNT\', \'TAX\', or \'amountDPdept\'
(transaction discounts, applicable tax, and open rings,
respectively).

description is what\'s displayed on screen and on receipts.

trans_type indicates the record\'s type Values include
(but may not be limited to at all co-ops):
    I => normally a product identified by upc, but
         can also be a discount line (upc=\'DISCOUNT\')
         or a YOU SAVED line (upc=\'0\'). 
    A => tax total line
    C => a commentary line. These generally exist 
         only for generating the on-screen display
         at the register (subtotal lines, etc).
    D => open ring to a department. In this case,
         upc will be the amount, \'DP\', and the
         department number
    T => tender record. UPC is generally, but not
         always, \'0\' (e.g., manufacturer coupons
         have their own UPCs)
    0 => another commentary line

trans_subtype refines the record\'s type. Values include
(but may not be limited to at all co-ops):
    CM => record is a cashier-written comment.
          Used to make notes on a transaction
    (tender code) => goes with trans_type \'T\',
             exact values depends what\'s
             in core_op.tenders
    0 => no refinement available for this trans_type
    blank => no refinement available for this trans_type

trans_status is a fairly all-purpose indicator. Values include
(but may not be limited to at all co-ops):
    X => the transaction is canceled
    D => this can be omitted with back-end reporting
    R => this line is a refund
    V => this line is a void
    M => this line is a member special discount
    C => this line is a coupon
    Z => this item was damaged, not sold (WFC)
    0 => no particular meaning
    blank => no particular meaning

department is set for a UPC item, an open-department ring,
a member special discount, or a manufacturer coupon. All
other lines have zero here.

quantity and ItemQtty are the number of items sold on
that line. These can be fractional for by-weight items.
These values are normally the same, except for:
    1. member special lines, where ItemQtty is always zero.
       This is useful for tracking actual movement by UPC
    2. refund lines, where quantity is negative and ItemQtty
       is not. No idea what the reasoning was here. 

scale indicates an item sold by-weight. Meaningless on
non-item records.

cost indicates an item\'s cost. Meaningless on non-item
records.

unitPrice is the price a customer will be charged per item.
total is quantity times unitPrice. This is what the
customer actually pays. If an item is on sale, regPrice
indicates the regular price per item. On non-item records,
total is usually the only relevant column. Sales are 
positive, voids/refunds/tenders are negative.

tax indicates whether to tax an item and at what rate

foodstamp indicates whether an item can be paid for
using foodstamps

discount is any per-item discount that was applied.
In the simplest case, this is the regularPrice
minus the unitPrice (times quantity). Discounts are
listed as positive values.

memDiscount is the same as discount, but these
discounts are only applied if the customer is a
member (custdata.Type = \'PC\')

discountable indicates whether an item is eligible
for transaction-wide percent discounts.

discounttype indicates what type of sale an item
is on.
    0 => not on sale
    1 => on sale for everyone
    2 => on sale for members
Values over 2 may be used, but aren\'t used 
consistently across co-ops at this time.

voided indicates whether a line has been voided
    0 => no
    1 => yes
voided is also used as a status flag in some cases
You\'d have to dig into IT CORE code a bit to get a
handle on that.
    
percentDiscount is a percentage discount applied to
the whole transaction. This is an integer, so
5 = 5% = 0.05

volDiscType is a volume discount type. Usage varies
a lot here, but in general:
    volDiscType => core_op.products.pricemethod
    volume => core_op.products.quantity
    VolSpecial => core_op.products.groupprice
If an item is on sale, those become specialpricemethod,
specialquantity, and specialgroupprice (respectively).
Exact calculations depend a lot of volDiscType. 0 means
there is no volume discount, and either 1 or 2 (depending
on IT CORE version) will probably do a simple 3 for $2 style
sale (quantity=3, groupprice=2.00). Higher type values
vary.

mixMatch relates to volume pricing. In general, items
with the same mixMatch setting are interchangeable. This
is so you can do sales across a set of products (e.g., Clif
Bars) and the customer can buy various flavors but still
get the discount.

matched notes item quantites that have already been used
for a volume pricing group. This is so the same item doesn\'t
get counted more than once.

memType and staff match values in core_op.custdata. Including
them here helps determine membership status at the time of 
purchase as opposed to current status.

numflag and charflag are generic status indicators. As far
as I know, there\'s no uniform usage across implementations.

card_no is the customer number from core_op.custdata.
        ';
    }
}

