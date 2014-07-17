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
  @class DTrans
  Helper class for generating useful bits of
  transaction SQL
*/
class DTrans 
{

    /**
      Array of default values for dtransaction-style tables
      The column 'datetime' is ommitted. Normally an SQL
      function like NOW() is used there and cannot be
      a parameter
    */
    public static $DEFAULTS = array(
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
        'volSpecial'=>0,
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
        foreach($arr as $key => $val) {
            // validate column names
            if (!isset(self::$DEFAULTS[$key])) {
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
        global $FANNIE_OP_DB, $FANNIE_SERVER_DBMS;
        $table = 'products';
        if (isset($FANNIE_OP_DB) && !empty($FANNIE_OP_DB)) {
            $table = $FANNIE_OP_DB;
            $table .= ($FANNIE_SERVER_DBMS == 'mssql') ? '.dbo.' : '.';
            $table .= 'products';
        }

        return ' ' . self::normalizeJoin($join_type) . ' JOIN ' . $table 
                . ' AS ' . $product_alias
                . ' ON ' . $product_alias . '.upc = ' . $dlog_alias . '.upc ';
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

    /**
      Get join statement for departments table
      @param $dlog_alias [optional] alias for the transaction table (default 't')
      @param $dept_alias [optional] alias for the departments table (default 'd')
      @return string SQL snippet
    */
    public static function joinDepartments($dlog_alias='t', $dept_alias='d')
    {
        global $FANNIE_OP_DB, $FANNIE_SERVER_DBMS;
        $table = 'departments';
        if (isset($FANNIE_OP_DB) && !empty($FANNIE_OP_DB)) {
            $table = $FANNIE_OP_DB;
            $table .= ($FANNIE_SERVER_DBMS == 'mssql') ? '.dbo.' : '.';
            $table .= 'departments';
        }

        return ' LEFT JOIN ' . $table . ' AS ' . $dept_alias
                . ' ON ' . $dept_alias . '.dept_no = ' . $dlog_alias . '.department ';
    }

    /**
      Get join statement for custdata table
      @param $dlog_alias [optional] alias for the transaction table (default 't')
      @param $cust_alias [optional] alias for the custdata table (default 'c')
      @return string SQL snippet
    */
    public static function joinCustdata($dlog_alias='t', $cust_alias='c')
    {
        global $FANNIE_OP_DB, $FANNIE_SERVER_DBMS;
        $table = 'custdata';
        if (isset($FANNIE_OP_DB) && !empty($FANNIE_OP_DB)) {
            $table = $FANNIE_OP_DB;
            $table .= ($FANNIE_SERVER_DBMS == 'mssql') ? '.dbo.' : '.';
            $table .= 'custdata';
        }

        return ' LEFT JOIN ' . $table . ' AS ' . $cust_alias
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
        global $FANNIE_OP_DB, $FANNIE_SERVER_DBMS;
        $table = 'tenders';
        if (isset($FANNIE_OP_DB) && !empty($FANNIE_OP_DB)) {
            $table = $FANNIE_OP_DB;
            $table .= ($FANNIE_SERVER_DBMS == 'mssql') ? '.dbo.' : '.';
            $table .= 'tenders';
        }

        return ' LEFT JOIN ' . $table . ' AS ' . $tender_alias
                . ' ON ' . $tender_alias . '.TenderCode = ' . $dlog_alias . '.trans_subtype ';
    }
}

