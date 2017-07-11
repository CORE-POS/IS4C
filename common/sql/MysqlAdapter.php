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

namespace COREPOS\common\sql;

class MysqlAdapter implements DialectAdapter
{
    public function createNamedDB($name)
    {
        return 'CREATE DATABASE ' . $this->identifierEscape($name);
    }

    public function useNamedDB($name)
    {
        return 'USE ' . $this->identifierEscape($name);
    }

    public function identifierEscape($str)
    {
        return '`' . $str . '`';
    }

    public function getViewDefinition($view_name, $dbc, $db_name)
    {
        $result = $dbc->query("SHOW CREATE VIEW " . $this->identifierEscape($view_name, $db_name), $db_name);
        if ($dbc->numRows($result) > 0) {
            $row = $dbc->fetchRow($result);
            return $row[1];
        } else {
            return false;
        }
    }

    public function defaultDatabase()
    {
        return 'SELECT DATABASE() as dbname';
    }

    public function temporaryTable($name, $source_table)
    {
        return '
            CREATE TEMPORARY TABLE ' . $name . '
            LIKE ' . $source_table;
    }

    public function sep()
    {
        return ".";
    }

    public function addSelectLimit($query, $int_limit)
    {
        return sprintf("%s LIMIT %d",$query,$int_limit);
    }

    public function currency()
    {
        return 'decimal(10,2)';
    }

    public function curtime()
    {
        return 'CURTIME()';
    }

    public function datediff($date1, $date2)
    {
        return "datediff($date1,$date2)";
    }

    public function monthdiff($date1, $date2)
    {
        return "period_diff(date_format($date1, '%Y%m'), date_format($date2, '%Y%m'))";
    }

    public function yeardiff($date1, $date2)
    {
        return "DATE_FORMAT(FROM_DAYS(DATEDIFF($date1,$date2)), '%Y')+0";
    }

    public function weekdiff($date1, $date2)
    {
        return "week($date1) - week($date2)";
    }

    public function seconddiff($date1, $date2)
    {
        return "TIMESTAMPDIFF(SECOND,$date1,$date2)";
    }

    public function dateymd($date1)
    {
        $str = "DATE_FORMAT($date1,'%Y%m%d')";
        return $this->convert($str, 'INT');
    }

    public function dayofweek($field)
    {
        return "DATE_FORMAT($field,'%w')+1";
    }

    public function convert($expr, $type)
    {
        if (strtoupper($type)=='INT') {
            $type='SIGNED';
        }
        return "CONVERT($expr,$type)";
    }

    public function locate($substr, $str)
    {
        return "LOCATE($substr,$str)";
    }

    public function concat($expressions)
    {
        $ret = 'CONCAT(';
        $ret = array_reduce($expressions, function($carry, $e) { return $carry . $e . ','; }, $ret);
        
        return substr($ret, 0, strlen($ret)-1) . ')';
    }

    public function setLockTimeout($seconds)
    {
        return sprintf('SET SESSION innodb_lock_wait_timeout = %d', $seconds);
    }

    public function setCharSet($charset)
    {
        return "SET NAMES '$charset'";
    }
}

