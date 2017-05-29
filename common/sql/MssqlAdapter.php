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

class MssqlAdapter implements DialectAdapter
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
        return '[' . $str . ']';
    }

    public function getViewDefinition($view_name, $dbc, $db_name)
    {
        $result = $dbc->query("SELECT OBJECT_DEFINITION(OBJECT_ID('$view_name'))", $db_name);
        if ($dbc->numRows($result) > 0) {
            $row = $dbc->fetchRow($result);
            return $row[0];
        }

        return false;
    }

    public function defaultDatabase()
    {
        return 'SELECT DB_NAME() as dbname';
    }

    public function temporaryTable($name, $source_table)
    {
        $tname = '#' . $name;
        if (strstr($name, '.dbo.')) {
            list($schema, ) = explode('.dbo.', $name, 2);
            $tname = $schema . '.dbo.#' . $name;
        }
        return '
            CREATE TABLE ' . $tname . '
            LIKE ' . $source_table;
    }

    public function sep()
    {
        return ".dbo.";
    }

    public function addSelectLimit($query, $int_limit)
    {
        return str_ireplace("SELECT ","SELECT TOP $int_limit ",$query);
    }

    public function currency()
    {
        return 'MONEY';
    }

    public function curtime()
    {
        return 'GETDATE()';
    }

    public function datediff($date1, $date2)
    {
        return "datediff(dd,$date2,$date1)";
    }

    public function monthdiff($date1, $date2)
    {
        return "datediff(mm,$date2,$date1)";
    }

    public function yeardiff($date1, $date2)
    {
        return "extract(year from age($date1,$date2))";
    }

    public function weekdiff($date1, $date2)
    {
        return "datediff(wk,$date2,$date1)";
    }

    public function seconddiff($date1, $date2)
    {
        return "datediff(ss,$date2,$date1)";
    }

    public function dateymd($date1)
    {
        return "CONVERT(CHAR(11),$date1,112)";
    }

    public function dayofweek($field)
    {
        return "DATEPART(dw,$field)";
    }

    public function convert($expr, $type)
    {
        return "CONVERT($type,$expr)";
    }

    public function locate($substr, $str)
    {
        return "CHARINDEX($substr,$str)";
    }

    public function concat($expressions)
    {
        $ret = array_reduce($expressions, function($carry, $e) { return $carry . $e . '+'; }, '');
        
        return substr($ret, 0, strlen($ret)-1);
    }

    public function setLockTimeout($seconds)
    {
        return sprintf('SET LOCK_TIMEOUT %d', 1000*$seconds);
    }

    public function setCharSet($charset)
    {
        return 'SELECT 1';
    }
}

