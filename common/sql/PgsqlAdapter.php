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

class PgsqlAdapter implements DialectAdapter
{
    public function createNamedDB($name)
    {
        return 'CREATE SCHEMA ' . $this->identifierEscape($name);
    }

    public function useNamedDB($name)
    {
        return 'SET search_path TO ' . $this->identifierEscape($name);
    }

    public function identifierEscape($str)
    {
        return '"' . $str . '"';
    }

    public function getViewDefinition($view_name, $dbc, $db_name)
    {
        $result = $dbc->query("SELECT oid FROM pg_class
                WHERE relname LIKE '$view_name'",
                $db_name);
        if ($dbc->numRows($result) > 0) {
            $row = $dbc->fetchRow($result);
            $defQ = sprintf('SELECT pg_get_viewdef(%d)', $row['oid']);
            $defR = $dbc->query($defQ, $db_name);
            if ($dbc->numRows($defR) > 0) {
                $def = $dbc->fetchRow($defR);
                return $def[0];
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function defaultDatabase()
    {
        return 'SELECT CURRENT_DATABASE() as dbname';
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
        return 'MONEY';
    }

    public function curtime()
    {
        return 'CURRENT_TIME';
    }

    public function datediff($date1, $date2)
    {
        return "extract(day from ($date2 - $date1))";
    }

    public function monthdiff($date1, $date2)
    {
        return "EXTRACT(year FROM age($date2,$date1))*12 + EXTRACT(month FROM age($date2,$date1))";
    }

    public function yeardiff($date1, $date2)
    {
        return "extract(year from age($date1,$date2))";
    }

    public function weekdiff($date1, $date2)
    {
        return "EXTRACT(WEEK FROM $date1) - EXTRACT(WEEK FROM $date2)";
    }

    public function seconddiff($date1, $date2)
    {
        return "EXTRACT(EPOCH FROM $date1) - EXTRACT(EPOCH FROM $date2)";
    }

    public function dateymd($date1)
    {
        return "TO_CHAR($date1, 'YYYYMMDD')";
    }

    public function dayofweek($field)
    {
        return "EXTRACT(dow from $field)";
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
        
        return substr($ret, 0, strlen($ret)-2);
    }

    public function setLockTimeout($seconds)
    {
        return sprintf('SET LOCAL lock_timeout = \'%ds\'', $seconds);
    }

    public function setCharSet($charset)
    {
        return "SET NAMES '$charset'";
    }
}

