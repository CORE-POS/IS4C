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

class DTransactionsModel extends BasicModel 
{

    protected $name = 'dtransactions';

    protected $preferred_db = 'trans';

    protected $columns = array(
    'datetime'    => array('type'=>'DATETIME','index'=>True),
    'store_id'    => array('type'=>'SMALLINT', 'index'=>true),
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
    'charflag'    => array('type'=>'VARCHAR(2)','default'=>''),
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
        global $FANNIE_ARCHIVE_DB, $FANNIE_ARCHIVE_METHOD, $FANNIE_TRANS_DB;
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
        if ($FANNIE_ARCHIVE_METHOD == 'partitions') {
            $this->name = 'bigArchive';
            $chk = parent::normalize($FANNIE_ARCHIVE_DB, $mode, $doCreate);
            if ($chk !== false) {
                $trans_adds += $chk;
            }
        } else {
            $pattern = '/^transArchive\d\d\d\d\d\d$/';
            $tables = $this->connection->get_tables($FANNIE_ARCHIVE_DB);
            foreach($tables as $t) {
                if (preg_match($pattern,$t)) {
                    $this->name = $t;
                    $chk = parent::normalize($FANNIE_ARCHIVE_DB, $mode, $doCreate);
                    if ($chk !== False) {
                        $trans_adds += $chk;
                    }
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
            $tables = $this->connection->get_tables($FANNIE_ARCHIVE_DB);
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
            $sql = 'DROP VIEW '.$this->connection->identifier_escape($view_name);
            if ($mode == BasicModel::NORMALIZE_MODE_APPLY) {
                $this->connection->query($sql);
            }
        }

        $sql = 'CREATE VIEW '.$this->connection->identifier_escape($view_name).' AS '
            .'SELECT '
            .$this->connection->identifier_escape('datetime').' AS '
            .$this->connection->identifier_escape('tdate').',';
        $c = $this->connection; // for more concise code below
        foreach($this->columns as $name => $definition) {
            if ($name == 'datetime') continue;
            elseif ($name == 'tdate') continue;
            elseif ($name == 'trans_num'){
                // create trans_num field
                $sql .= $c->concat(
                $c->convert($c->identifier_escape('emp_no'),'char'),
                "'-'",
                $c->convert($c->identifier_escape('register_no'),'char'),
                "'-'",
                $c->convert($c->identifier_escape('trans_no'),'char'),
                ''
                ).' as trans_num';
            } elseif($name == 'trans_type') {
                // type conversion for old records. Newer coupon & discount
                // records should have correct trans_type when initially created
                $sql .= "CASE WHEN (trans_subtype IN ('CP','IC') OR upc like('%000000052')) then 'T' 
                    WHEN upc = 'DISCOUNT' then 'S' else trans_type end as trans_type,\n";
            } elseif($name == 'trans_subtype'){
                // type conversion for old records. Probably WFC quirk that can
                // eventually go away entirely
                $sql .= "CASE WHEN upc = 'MAD Coupon' THEN 'MA' 
                   WHEN upc like('%00000000052') THEN 'RR' ELSE trans_subtype END as trans_subtype,\n";
            } else {
                $sql .= $c->identifier_escape($name).",\n";
            }
        }
        $sql .= ' FROM '.$c->identifier_escape($table_name)
            .' WHERE '.$c->identifier_escape('trans_status')
            ." NOT IN ('D','X','Z') AND emp_no <> 9999
            AND register_no <> 99";
        if ($mode == BasicModel::NORMALIZE_MODE_APPLY) {
            $this->connection->query($sql);
        }

    // normalizeLog()
    }

    static public function selectDlog($start, $end=false)
    {
        return self::selectStruct(True, $start, $end);
    }

    static public function select_dlog($start, $end=false)
    {
        return self::selectDlog($start, $end);
    }

    static public function selectDtrans($start, $end=false)
    {
        return self::selectStruct(False, $start, $end);
    }

    static public function select_dtrans($start, $end=false)
    {
        return self::selectDtrans($start, $end);
    }

    /* Return the SQL FROM parameter for a given date range
     *  i.e. the table, view or union of tables or views
     *  in which the transaction records can be found
     *  most efficiently.
    */
    static private function selectStruct($dlog, $start, $end=false)
    {
        global $FANNIE_TRANS_DB, $FANNIE_ARCHIVE_DB, $FANNIE_SERVER_DBMS, $FANNIE_ARCHIVE_METHOD;
        $sep = ($FANNIE_SERVER_DBMS=='MSSQL')?'.dbo.':'.';

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

    /* START ACCESSOR FUNCTIONS */

    public function datetime()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["datetime"])) {
                return $this->instance["datetime"];
            } else if (isset($this->columns["datetime"]["default"])) {
                return $this->columns["datetime"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'datetime',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["datetime"]) || $this->instance["datetime"] != func_get_args(0)) {
                if (!isset($this->columns["datetime"]["ignore_updates"]) || $this->columns["datetime"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["datetime"] = func_get_arg(0);
        }
        return $this;
    }

    public function store_id()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["store_id"])) {
                return $this->instance["store_id"];
            } else if (isset($this->columns["store_id"]["default"])) {
                return $this->columns["store_id"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'store_id',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["store_id"]) || $this->instance["store_id"] != func_get_args(0)) {
                if (!isset($this->columns["store_id"]["ignore_updates"]) || $this->columns["store_id"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["store_id"] = func_get_arg(0);
        }
        return $this;
    }

    public function register_no()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["register_no"])) {
                return $this->instance["register_no"];
            } else if (isset($this->columns["register_no"]["default"])) {
                return $this->columns["register_no"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'register_no',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["register_no"]) || $this->instance["register_no"] != func_get_args(0)) {
                if (!isset($this->columns["register_no"]["ignore_updates"]) || $this->columns["register_no"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["register_no"] = func_get_arg(0);
        }
        return $this;
    }

    public function emp_no()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["emp_no"])) {
                return $this->instance["emp_no"];
            } else if (isset($this->columns["emp_no"]["default"])) {
                return $this->columns["emp_no"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'emp_no',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["emp_no"]) || $this->instance["emp_no"] != func_get_args(0)) {
                if (!isset($this->columns["emp_no"]["ignore_updates"]) || $this->columns["emp_no"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["emp_no"] = func_get_arg(0);
        }
        return $this;
    }

    public function trans_no()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["trans_no"])) {
                return $this->instance["trans_no"];
            } else if (isset($this->columns["trans_no"]["default"])) {
                return $this->columns["trans_no"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'trans_no',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["trans_no"]) || $this->instance["trans_no"] != func_get_args(0)) {
                if (!isset($this->columns["trans_no"]["ignore_updates"]) || $this->columns["trans_no"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["trans_no"] = func_get_arg(0);
        }
        return $this;
    }

    public function upc()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["upc"])) {
                return $this->instance["upc"];
            } else if (isset($this->columns["upc"]["default"])) {
                return $this->columns["upc"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'upc',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["upc"]) || $this->instance["upc"] != func_get_args(0)) {
                if (!isset($this->columns["upc"]["ignore_updates"]) || $this->columns["upc"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["upc"] = func_get_arg(0);
        }
        return $this;
    }

    public function description()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["description"])) {
                return $this->instance["description"];
            } else if (isset($this->columns["description"]["default"])) {
                return $this->columns["description"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'description',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["description"]) || $this->instance["description"] != func_get_args(0)) {
                if (!isset($this->columns["description"]["ignore_updates"]) || $this->columns["description"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["description"] = func_get_arg(0);
        }
        return $this;
    }

    public function trans_type()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["trans_type"])) {
                return $this->instance["trans_type"];
            } else if (isset($this->columns["trans_type"]["default"])) {
                return $this->columns["trans_type"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'trans_type',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["trans_type"]) || $this->instance["trans_type"] != func_get_args(0)) {
                if (!isset($this->columns["trans_type"]["ignore_updates"]) || $this->columns["trans_type"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["trans_type"] = func_get_arg(0);
        }
        return $this;
    }

    public function trans_subtype()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["trans_subtype"])) {
                return $this->instance["trans_subtype"];
            } else if (isset($this->columns["trans_subtype"]["default"])) {
                return $this->columns["trans_subtype"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'trans_subtype',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["trans_subtype"]) || $this->instance["trans_subtype"] != func_get_args(0)) {
                if (!isset($this->columns["trans_subtype"]["ignore_updates"]) || $this->columns["trans_subtype"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["trans_subtype"] = func_get_arg(0);
        }
        return $this;
    }

    public function trans_status()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["trans_status"])) {
                return $this->instance["trans_status"];
            } else if (isset($this->columns["trans_status"]["default"])) {
                return $this->columns["trans_status"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'trans_status',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["trans_status"]) || $this->instance["trans_status"] != func_get_args(0)) {
                if (!isset($this->columns["trans_status"]["ignore_updates"]) || $this->columns["trans_status"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["trans_status"] = func_get_arg(0);
        }
        return $this;
    }

    public function department()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["department"])) {
                return $this->instance["department"];
            } else if (isset($this->columns["department"]["default"])) {
                return $this->columns["department"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'department',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["department"]) || $this->instance["department"] != func_get_args(0)) {
                if (!isset($this->columns["department"]["ignore_updates"]) || $this->columns["department"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["department"] = func_get_arg(0);
        }
        return $this;
    }

    public function quantity()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["quantity"])) {
                return $this->instance["quantity"];
            } else if (isset($this->columns["quantity"]["default"])) {
                return $this->columns["quantity"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'quantity',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["quantity"]) || $this->instance["quantity"] != func_get_args(0)) {
                if (!isset($this->columns["quantity"]["ignore_updates"]) || $this->columns["quantity"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["quantity"] = func_get_arg(0);
        }
        return $this;
    }

    public function scale()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["scale"])) {
                return $this->instance["scale"];
            } else if (isset($this->columns["scale"]["default"])) {
                return $this->columns["scale"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'scale',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["scale"]) || $this->instance["scale"] != func_get_args(0)) {
                if (!isset($this->columns["scale"]["ignore_updates"]) || $this->columns["scale"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["scale"] = func_get_arg(0);
        }
        return $this;
    }

    public function cost()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["cost"])) {
                return $this->instance["cost"];
            } else if (isset($this->columns["cost"]["default"])) {
                return $this->columns["cost"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'cost',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["cost"]) || $this->instance["cost"] != func_get_args(0)) {
                if (!isset($this->columns["cost"]["ignore_updates"]) || $this->columns["cost"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["cost"] = func_get_arg(0);
        }
        return $this;
    }

    public function unitPrice()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["unitPrice"])) {
                return $this->instance["unitPrice"];
            } else if (isset($this->columns["unitPrice"]["default"])) {
                return $this->columns["unitPrice"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'unitPrice',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["unitPrice"]) || $this->instance["unitPrice"] != func_get_args(0)) {
                if (!isset($this->columns["unitPrice"]["ignore_updates"]) || $this->columns["unitPrice"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["unitPrice"] = func_get_arg(0);
        }
        return $this;
    }

    public function total()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["total"])) {
                return $this->instance["total"];
            } else if (isset($this->columns["total"]["default"])) {
                return $this->columns["total"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'total',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["total"]) || $this->instance["total"] != func_get_args(0)) {
                if (!isset($this->columns["total"]["ignore_updates"]) || $this->columns["total"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["total"] = func_get_arg(0);
        }
        return $this;
    }

    public function regPrice()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["regPrice"])) {
                return $this->instance["regPrice"];
            } else if (isset($this->columns["regPrice"]["default"])) {
                return $this->columns["regPrice"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'regPrice',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["regPrice"]) || $this->instance["regPrice"] != func_get_args(0)) {
                if (!isset($this->columns["regPrice"]["ignore_updates"]) || $this->columns["regPrice"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["regPrice"] = func_get_arg(0);
        }
        return $this;
    }

    public function tax()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["tax"])) {
                return $this->instance["tax"];
            } else if (isset($this->columns["tax"]["default"])) {
                return $this->columns["tax"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'tax',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["tax"]) || $this->instance["tax"] != func_get_args(0)) {
                if (!isset($this->columns["tax"]["ignore_updates"]) || $this->columns["tax"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["tax"] = func_get_arg(0);
        }
        return $this;
    }

    public function foodstamp()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["foodstamp"])) {
                return $this->instance["foodstamp"];
            } else if (isset($this->columns["foodstamp"]["default"])) {
                return $this->columns["foodstamp"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'foodstamp',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["foodstamp"]) || $this->instance["foodstamp"] != func_get_args(0)) {
                if (!isset($this->columns["foodstamp"]["ignore_updates"]) || $this->columns["foodstamp"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["foodstamp"] = func_get_arg(0);
        }
        return $this;
    }

    public function discount()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["discount"])) {
                return $this->instance["discount"];
            } else if (isset($this->columns["discount"]["default"])) {
                return $this->columns["discount"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'discount',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["discount"]) || $this->instance["discount"] != func_get_args(0)) {
                if (!isset($this->columns["discount"]["ignore_updates"]) || $this->columns["discount"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["discount"] = func_get_arg(0);
        }
        return $this;
    }

    public function memDiscount()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["memDiscount"])) {
                return $this->instance["memDiscount"];
            } else if (isset($this->columns["memDiscount"]["default"])) {
                return $this->columns["memDiscount"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'memDiscount',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["memDiscount"]) || $this->instance["memDiscount"] != func_get_args(0)) {
                if (!isset($this->columns["memDiscount"]["ignore_updates"]) || $this->columns["memDiscount"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["memDiscount"] = func_get_arg(0);
        }
        return $this;
    }

    public function discountable()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["discountable"])) {
                return $this->instance["discountable"];
            } else if (isset($this->columns["discountable"]["default"])) {
                return $this->columns["discountable"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'discountable',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["discountable"]) || $this->instance["discountable"] != func_get_args(0)) {
                if (!isset($this->columns["discountable"]["ignore_updates"]) || $this->columns["discountable"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["discountable"] = func_get_arg(0);
        }
        return $this;
    }

    public function discounttype()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["discounttype"])) {
                return $this->instance["discounttype"];
            } else if (isset($this->columns["discounttype"]["default"])) {
                return $this->columns["discounttype"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'discounttype',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["discounttype"]) || $this->instance["discounttype"] != func_get_args(0)) {
                if (!isset($this->columns["discounttype"]["ignore_updates"]) || $this->columns["discounttype"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["discounttype"] = func_get_arg(0);
        }
        return $this;
    }

    public function voided()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["voided"])) {
                return $this->instance["voided"];
            } else if (isset($this->columns["voided"]["default"])) {
                return $this->columns["voided"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'voided',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["voided"]) || $this->instance["voided"] != func_get_args(0)) {
                if (!isset($this->columns["voided"]["ignore_updates"]) || $this->columns["voided"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["voided"] = func_get_arg(0);
        }
        return $this;
    }

    public function percentDiscount()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["percentDiscount"])) {
                return $this->instance["percentDiscount"];
            } else if (isset($this->columns["percentDiscount"]["default"])) {
                return $this->columns["percentDiscount"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'percentDiscount',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["percentDiscount"]) || $this->instance["percentDiscount"] != func_get_args(0)) {
                if (!isset($this->columns["percentDiscount"]["ignore_updates"]) || $this->columns["percentDiscount"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["percentDiscount"] = func_get_arg(0);
        }
        return $this;
    }

    public function ItemQtty()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["ItemQtty"])) {
                return $this->instance["ItemQtty"];
            } else if (isset($this->columns["ItemQtty"]["default"])) {
                return $this->columns["ItemQtty"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'ItemQtty',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["ItemQtty"]) || $this->instance["ItemQtty"] != func_get_args(0)) {
                if (!isset($this->columns["ItemQtty"]["ignore_updates"]) || $this->columns["ItemQtty"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["ItemQtty"] = func_get_arg(0);
        }
        return $this;
    }

    public function volDiscType()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["volDiscType"])) {
                return $this->instance["volDiscType"];
            } else if (isset($this->columns["volDiscType"]["default"])) {
                return $this->columns["volDiscType"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'volDiscType',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["volDiscType"]) || $this->instance["volDiscType"] != func_get_args(0)) {
                if (!isset($this->columns["volDiscType"]["ignore_updates"]) || $this->columns["volDiscType"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["volDiscType"] = func_get_arg(0);
        }
        return $this;
    }

    public function volume()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["volume"])) {
                return $this->instance["volume"];
            } else if (isset($this->columns["volume"]["default"])) {
                return $this->columns["volume"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'volume',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["volume"]) || $this->instance["volume"] != func_get_args(0)) {
                if (!isset($this->columns["volume"]["ignore_updates"]) || $this->columns["volume"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["volume"] = func_get_arg(0);
        }
        return $this;
    }

    public function VolSpecial()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["VolSpecial"])) {
                return $this->instance["VolSpecial"];
            } else if (isset($this->columns["VolSpecial"]["default"])) {
                return $this->columns["VolSpecial"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'VolSpecial',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["VolSpecial"]) || $this->instance["VolSpecial"] != func_get_args(0)) {
                if (!isset($this->columns["VolSpecial"]["ignore_updates"]) || $this->columns["VolSpecial"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["VolSpecial"] = func_get_arg(0);
        }
        return $this;
    }

    public function mixMatch()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["mixMatch"])) {
                return $this->instance["mixMatch"];
            } else if (isset($this->columns["mixMatch"]["default"])) {
                return $this->columns["mixMatch"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'mixMatch',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["mixMatch"]) || $this->instance["mixMatch"] != func_get_args(0)) {
                if (!isset($this->columns["mixMatch"]["ignore_updates"]) || $this->columns["mixMatch"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["mixMatch"] = func_get_arg(0);
        }
        return $this;
    }

    public function matched()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["matched"])) {
                return $this->instance["matched"];
            } else if (isset($this->columns["matched"]["default"])) {
                return $this->columns["matched"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'matched',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["matched"]) || $this->instance["matched"] != func_get_args(0)) {
                if (!isset($this->columns["matched"]["ignore_updates"]) || $this->columns["matched"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["matched"] = func_get_arg(0);
        }
        return $this;
    }

    public function memType()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["memType"])) {
                return $this->instance["memType"];
            } else if (isset($this->columns["memType"]["default"])) {
                return $this->columns["memType"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'memType',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["memType"]) || $this->instance["memType"] != func_get_args(0)) {
                if (!isset($this->columns["memType"]["ignore_updates"]) || $this->columns["memType"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["memType"] = func_get_arg(0);
        }
        return $this;
    }

    public function staff()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["staff"])) {
                return $this->instance["staff"];
            } else if (isset($this->columns["staff"]["default"])) {
                return $this->columns["staff"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'staff',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["staff"]) || $this->instance["staff"] != func_get_args(0)) {
                if (!isset($this->columns["staff"]["ignore_updates"]) || $this->columns["staff"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["staff"] = func_get_arg(0);
        }
        return $this;
    }

    public function numflag()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["numflag"])) {
                return $this->instance["numflag"];
            } else if (isset($this->columns["numflag"]["default"])) {
                return $this->columns["numflag"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'numflag',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["numflag"]) || $this->instance["numflag"] != func_get_args(0)) {
                if (!isset($this->columns["numflag"]["ignore_updates"]) || $this->columns["numflag"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["numflag"] = func_get_arg(0);
        }
        return $this;
    }

    public function charflag()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["charflag"])) {
                return $this->instance["charflag"];
            } else if (isset($this->columns["charflag"]["default"])) {
                return $this->columns["charflag"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'charflag',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["charflag"]) || $this->instance["charflag"] != func_get_args(0)) {
                if (!isset($this->columns["charflag"]["ignore_updates"]) || $this->columns["charflag"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["charflag"] = func_get_arg(0);
        }
        return $this;
    }

    public function card_no()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["card_no"])) {
                return $this->instance["card_no"];
            } else if (isset($this->columns["card_no"]["default"])) {
                return $this->columns["card_no"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'card_no',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["card_no"]) || $this->instance["card_no"] != func_get_args(0)) {
                if (!isset($this->columns["card_no"]["ignore_updates"]) || $this->columns["card_no"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["card_no"] = func_get_arg(0);
        }
        return $this;
    }

    public function trans_id()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["trans_id"])) {
                return $this->instance["trans_id"];
            } else if (isset($this->columns["trans_id"]["default"])) {
                return $this->columns["trans_id"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'trans_id',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["trans_id"]) || $this->instance["trans_id"] != func_get_args(0)) {
                if (!isset($this->columns["trans_id"]["ignore_updates"]) || $this->columns["trans_id"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["trans_id"] = func_get_arg(0);
        }
        return $this;
    }

    public function pos_row_id()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["pos_row_id"])) {
                return $this->instance["pos_row_id"];
            } else if (isset($this->columns["pos_row_id"]["default"])) {
                return $this->columns["pos_row_id"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'pos_row_id',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["pos_row_id"]) || $this->instance["pos_row_id"] != func_get_args(0)) {
                if (!isset($this->columns["pos_row_id"]["ignore_updates"]) || $this->columns["pos_row_id"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["pos_row_id"] = func_get_arg(0);
        }
        return $this;
    }

    public function store_row_id()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["store_row_id"])) {
                return $this->instance["store_row_id"];
            } else if (isset($this->columns["store_row_id"]["default"])) {
                return $this->columns["store_row_id"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'store_row_id',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["store_row_id"]) || $this->instance["store_row_id"] != func_get_args(0)) {
                if (!isset($this->columns["store_row_id"]["ignore_updates"]) || $this->columns["store_row_id"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["store_row_id"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

