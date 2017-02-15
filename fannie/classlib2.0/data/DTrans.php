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

/**
  @class DTrans
  Helper class for generating useful bits of
  transaction SQL
*/
class DTrans 
{

    /**
      Array of default values for dtransaction-style tables
      The column 'datetime' is omitted. Normally an SQL
      function like NOW() is used there and cannot be
      a parameter
    */
    public static function defaults()
    {
        $ret = self::$DEFAULTS;
        $ret['store_id'] = (int)FannieConfig::config('STORE_ID');

        return $ret;
    }

    private static $DEFAULTS = array(
        'register_no'=>0,
        'emp_no'=>0,
        'trans_no'=>0,
        'upc'=>'0',
        'description'=>'',
        'trans_type'=>'',
        'trans_subtype'=>'',
        'trans_status'=>'',
        'department'=>'',
        'quantity'=>0,
        'scale'=>0,
        'cost'=>0,
        'unitPrice'=>'',
        'total'=>'',
        'regPrice'=>'',
        'tax'=>0,
        'foodstamp'=>0,
        'discount'=>0,
        'memDiscount'=>0,
        'discountable'=>0,
        'discounttype'=>0,
        'voided'=>0,
        'percentDiscount'=>0,
        'ItemQtty'=>0,
        'volDiscType'=>0,
        'volume'=>0,
        'VolSpecial'=>0,
        'mixMatch'=>'',
        'matched'=>0,
        'memType'=>'',
        'staff'=>'',
        'numflag'=>0,
        'charflag'=>'',
        'card_no'=>0,
        'trans_id'=>0
    );

    /**
      Turn an key=>value array into useful SQL bits
      @param $arr array of column_name => column_value
      @param $datecol [optional] name of datetime column
      @param $datefunc [optional] string database function for current datetime
      @return keyed array
        - columnString => comma separated list of columns
        - valueString => comma separated list of ? placeholders
        - arguments => array of query parameters
    */
    public static function parameterize($arr, $datecol='', $datefunc='')
    {
        $columns = !empty($datecol) && !empty($datefunc) ? $datecol.',' : '';
        $values = !empty($datecol) && !empty($datefunc) ? $datefunc.',' : '';
        $args = array();
        $defaults = self::defaults();
        foreach($arr as $key => $val) {
            // validate column names
            if (!isset($defaults[$key])) {
                continue;
            }
            $columns .= $key.',';
            $values .= '?,';
            $args[] = $val;
        }
        $columns = substr($columns,0,strlen($columns)-1);
        $values = substr($values,0,strlen($values)-1);

        return array(
            'columnString' => $columns,
            'valueString' => $values,
            'arguments' => $args
        );
    }

    /**
      Get SQL condition to select testing transactions
      @param $prefix [optional] table alias
      @return string SQL snippet
    */
    public static function isTesting($prefix='')
    {
        if (!empty($prefix)) {
            $prefix = $prefix . '.';
        }

        return ' (' . $prefix . 'register_no = 99 OR ' . $prefix . 'emp_no = 9999) ';
    }

    /**
      Get SQL condition to select non-testing transactions
      @param $prefix [optional] table alias
      @return string SQL snippet
    */
    public static function isNotTesting($prefix='')
    {
        if (!empty($prefix)) {
            $prefix = $prefix . '.';
        }

        return ' (' . $prefix . 'register_no <> 99 AND ' . $prefix . 'emp_no <> 9999)' ;
    }

    /**
      Get SQL condition to select canceled transactions
      @param $prefix [optional] table alias
      @return string SQL snippet
    */
    public static function isCanceled($prefix='')
    {
        if (!empty($prefix)) {
            $prefix = $prefix . '.';
        }

        return ' (' . $prefix . "trans_status IN ('X', 'Z')) ";
    }

    /**
      Get SQL condition to select valid transactions
      This is essentially the opposite of "isCanceled" but
      excludes some additional informational rows that
      provide commentary but do not impact numeric totals
      @param $prefix [optional] table alias
      @return string SQL snippet
    */
    public static function isValid($prefix='')
    {
        if (!empty($prefix)) {
            $prefix = $prefix . '.';
        }

        return ' (' . $prefix . "trans_status NOT IN ('D', 'X', 'Z')) ";
    }

    /**
      Get SQL condition to select transactions with
      the given store ID. Store ID must be passed to the
      resulting prepared statement as an argument
    */
    public static function isStoreID($store_id, $prefix='')
    {
        if (!empty($prefix)) {
            $prefix = $prefix . '.';
        }
        
        if ($store_id == 0) {
            return ' (0 = ?) ';    
        } else {
            return ' (' . $prefix . 'store_id = ?) ';
        }
    }

    /**
      Get standard quantity sum. Member-discount line items
      are excluded and quasi-scalabe items with a unitPrice
      of a penny are counted as one instead of whatever value
      is in the quantity field.  
      @param $prefix [optional] table alias
      @return string SQL snippet
    */
    public static function sumQuantity($prefix='')
    {
        if (!empty($prefix)) {
            $prefix = $prefix . '.';
        }

        return ' SUM(CASE '
                . 'WHEN ' . $prefix . "trans_status = 'M' THEN 0 "
                . 'WHEN ' . $prefix . "unitPrice = 0.01 THEN 1 "
                . 'ELSE ' . $prefix . 'quantity '
                . 'END) ';
    }

    /**
      Get join statement for products table
      @param $dlog_alias [optional] alias for the transaction table (default 't')
      @param $product_alias [optional] alias for the products table (default 'p')
      @return string SQL snippet
    */
    public static function joinProducts($dlog_alias='t', $product_alias='p', $join_type='left')
    {
        $conf = FannieConfig::factory();
        $store_id = $conf->get('STORE_ID');
        $store_condition = '';
        if ($conf->get('STORE_MODE') == 'HQ') {
            $store_condition = ' AND ' . $product_alias . '.store_id=' . ((int)$store_id); 
        }

        return ' ' . self::normalizeJoin($join_type) . ' JOIN ' . self::opTable('products')
                . ' AS ' . $product_alias
                . ' ON ' . $product_alias . '.upc = ' . $dlog_alias . '.upc ' . $store_condition;
    }

    private static function normalizeJoin($join_type)
    {
        switch (strtoupper($join_type)) {
            case 'RIGHT':
                return 'RIGHT';
            case 'INNER':
                return 'INNER';
            default:
            case 'LEFT':
                return 'LEFT';
        }
    }

    private static function opTable($table)
    {
        $conf = FannieConfig::factory();
        $fq_table = $table;
        if ($conf->get('OP_DB') != '') {
            $fq_table = $conf->get('OP_DB');
            $fq_table .= ($conf->get('SERVER_DBMS') == 'mssql') ? '.dbo.' : '.';
            $fq_table .= $table;
        }

        return $fq_table;
    }

    /**
      Get join statement for departments table
      @param $dlog_alias [optional] alias for the transaction table (default 't')
      @param $dept_alias [optional] alias for the departments table (default 'd')
      @return string SQL snippet
    */
    public static function joinDepartments($dlog_alias='t', $dept_alias='d')
    {
        return ' LEFT JOIN ' . self::opTable('departments') . ' AS ' . $dept_alias
                . ' ON ' . $dept_alias . '.dept_no = ' . $dlog_alias . '.department ';
    }

    /**
      Get join statement for custdata table
      @param $dlog_alias [optional] alias for the transaction table (default 't')
      @param $cust_alias [optional] alias for the custdata table (default 'c')
      @return string SQL snippet
    */
    public static function joinCustomerAccount($dlog_alias='t', $cust_alias='c')
    {
        return ' LEFT JOIN ' . self::opTable('custdata') . ' AS ' . $cust_alias
                . ' ON ' . $cust_alias . '.CardNo = ' . $dlog_alias . '.card_no '
                . ' AND ' . $cust_alias . '.personNum = 1 ';
    }

    /**
      Get join statement for tenders table
      @param $dlog_alias [optional] alias for the transaction table (default 't')
      @param $tender_alias [optional] alias for the tenders table (default 'n')
      @return string SQL snippet
    */
    public static function joinTenders($dlog_alias='t', $tender_alias='n')
    {
        return ' LEFT JOIN ' . self::opTable('tenders') . ' AS ' . $tender_alias
                . ' ON ' . $tender_alias . '.TenderCode = ' . $dlog_alias . '.trans_subtype ';
    }

    /**
      Get an available dtransactions.trans_no value
      @param $connection [SQLManager] database connection
      @param $emp_no [int] employee number
      @param $register_no [int] register number
      @return [int] trans_no
    */
    public static function getTransNo(SQLManager $connection, $emp_no=false, $register_no=false)
    {
        $config = FannieConfig::factory();
        if ($emp_no === false) {
            $emp_no = $config->get('EMP_NO');
        }
        if ($register_no === false) {
            $register_no = $config->get('REGISTER_NO');
        }
        $prep = $connection->prepare('
            SELECT MAX(trans_no) AS trans
            FROM ' . $config->get('TRANS_DB') . $connection->sep() . 'dtransactions
            WHERE emp_no=?
                AND register_no=?');
        $result = $connection->execute($prep, array($emp_no, $register_no));
        if (!$result || $connection->num_rows($result) == 0) {
            return 1;
        } else {
            $row = $connection->fetch_row($result);
            if ($row['trans'] == '') {
                return 1;
            } else {
                return $row['trans'] + 1;
            }
        }
    }

    /**
      Add a transaction record directly to dtransactions on the backend
      @param $connection [SQLManager] database connection
      @param $trans_no [integer] transaction number (dtransactions.trans_no)
      @param $params [array] of column_name => value

      If emp_no and register_no values are not specified, the defaults
      are the configuration settings FANNIE_EMP_NO and FANNIE_REGISTER_NO.

      The following columns are always calculated by addItem() and values
      set in $params will be ignored:
      - datetime (always current)
      - trans_id (assigned based on existing records)
      Additionally, the following values are looked up if $params['card_no']
      is specified:
      - memType
      - staff
    */
    public static function addItem(SQLManager $connection, $trans_no, $params)
    {
        $config = FannieConfig::factory();
        $model = new DTransactionsModel($connection);
        $model->whichDB($config->get('TRANS_DB'));
        $model->trans_no($trans_no);
        $model->emp_no($config->get('EMP_NO'));
        if (isset($params['emp_no'])) {
            $model->emp_no($params['emp_no']);
        }
        $model->register_no($config->get('REGISTER_NO'));
        if (isset($params['register_no'])) {
            $model->register_no($params['register_no']);
        }
        
        $current_records = $model->find('trans_id', true);
        if (count($current_records) == 0) {
            $model->trans_id(1);
        } else {
            $last = $current_records[0];
            $model->trans_id($last->trans_id() + 1);
        }

        if (isset($params['card_no'])) {
            $account = \COREPOS\Fannie\API\member\MemberREST::get($params['card_no']);
            if ($account) {
                $model->memType($account['customerTypeID']);
                $model->staff($account['customers'][0]['staff']);
            }
        }

        $defaults = self::defaults();
        $skip = array('datetime', 'emp_no', 'register_no', 'trans_no', 'trans_id');
        foreach ($defaults as $name => $value) {
            if (in_array($name, $skip)) {
                continue;
            }
            if (isset($params[$name])) {
                $model->$name($params[$name]);
            } else {
                $model->$name($value);
            }
        }
        $model->datetime(date('Y-m-d H:i:s'));

        if ($model->save()) {
            return true;
        } else {
            return false;
        }
    }

    /**
      Add an open ring record to dtransactions on the backend
      @param $connection [SQLManager] database connection
      @param $department [integer] department number
      $param $amount [number] ring amount
      @param $trans_no [integer] transaction number (dtransactions.trans_no)
      @param $params [array] of column_name => value

      If emp_no and register_no values are not specified, the defaults
      are the configuration settings FANNIE_EMP_NO and FANNIE_REGISTER_NO.

      The following columns are automatically calculated based
      on department number and amount:
      - upc
      - description
      - trans_type
      - trans_status
      - unitPrice
      - total
      - regPrice
      - quantity
      - ItemQtty
      Negative amounts result in a refund trans_status

      This method calls DTrans::addItem() so columns datetime and trans_id are
      also automatically assigned.
    */
    public static function addOpenRing(SQLManager $connection, $department, $amount, $trans_no, $params=array())
    {
        $config = FannieConfig::factory();
        $model = new DepartmentsModel($connection);
        $model->whichDB($config->get('OP_DB'));
        $model->dept_no($department);
        $model->load(); 

        $params['trans_type'] = 'D';
        $params['department'] = $department;
        $params['unitPrice'] = $amount;
        $params['total'] = $amount;
        $params['regPrice'] = $amount;
        $params['quantity'] = 1;
        $params['ItemQtty'] = 1;
        if ($amount < 0) {
            $params['quantity'] = -1;
            $params['trans_status'] = 'R';
        }
        $params['description'] = $model->dept_name();
        $params['upc'] = abs($amount) . 'DP' . $department;

        return self::addItem($connection, $trans_no, $params);
    }

    public static function departmentClause($deptStart, $deptEnd, $deptMulti, $args, $alias='d')
    {
        if (count($deptMulti) > 0) {
            $where = ' AND ' . $alias . '.department IN (';
            foreach ($deptMulti as $d) {
                $where .= '?,';
                $args[] = $d;
            }
            $where = substr($where, 0, strlen($where)-1) . ')';
        } else {
            $where = ' AND ' . $alias . '.department BETWEEN ? AND ? ';
            $args[] = $deptStart;
            $args[] = $deptEnd;
        }

        return array($where, $args);
    }
}

