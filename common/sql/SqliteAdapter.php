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

class SqliteAdapter implements DialectAdapter
{
    public function createNamedDB($name)
    {
        return 'SELECT 1';
    }

    public function useNamedDB($name)
    {
        return 'SELECT 1';
    }

    public function identifierEscape($str)
    {
        return '"' . $str . '"';
    }

    public function getViewDefinition($view_name, $dbc, $db_name)
    {
        $result = $dbc->query("SELECT sql FROM sqlite_master
                WHERE type IN ('view') AND name='$view_name'",
                $db_name);
        $ret = false;
        if ($dbc->numRows($result) > 0) {
            $row = $dbc->fetchRow($result);
            $ret = $row['sql'];
        }
        $dbc->endQuery($result, $db_name);
        return $ret;
    }

    public function defaultDatabase()
    {
        return "pragma database list";
    }

    public function temporaryTable($name, $source_table)
    {
        return ' 
            CREATE TEMPORARY TABLE ' . $name . '
            AS
            SELECT *
            FROM ' . $source_table . '
            WHERE 1=0';
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
        return 'REAL';
    }

    public function curtime()
    {
        return "TIME('NOW')";
    }

    public function datediff($date1, $date2)
    {
        return "CAST( (JULIANDAY($date1) - JULIANDAY($date2)) AS INT)";
    }

    public function monthdiff($date1, $date2)
    {
        return "round((julianday($date1)) - julianday($date2))/30)";
    }

    public function yeardiff($date1, $date2)
    {
        return "round((julianday($date1)) - julianday($date2))/365)";
    }

    public function weekdiff($date1, $date2)
    {
        return "CAST(STRFTIME('%W',$date1) AS INT) - CAST(STRFTIME('%W',$date2) AS INT)";
    }

    public function seconddiff($date1, $date2)
    {
        return "CAST(STRFTIME('%s',$date1) AS INT) - CAST(STRFTIME('%s',$date2) AS INT)";
    }

    public function dateymd($date1)
    {
        return "STRFTIME('%Y%m%d', $date1)";
    }

    public function dayofweek($field)
    {
        return "STRFTIME('%w', $field)";
    }

    public function convert($expr, $type)
    {
        return "CAST($expr AS $type)";
    }

    public function locate($substr, $str)
    {
        return "POSITION($substr IN $str)";
    }

    public function concat($expressions)
    {
        $ret = array_reduce($expressions, function($carry, $e) { return $carry . $e . '||'; }, '');
        
        return substr($ret, 0, strlen($ret)-1);
    }

    public function setLockTimeout($seconds)
    {
        return sprintf('PRAGMA busy_timeout = %d', 1000*$seconds);
    }

    public function setCharSet($charset)
    {
        return 'SELECT 1';
    }
}

