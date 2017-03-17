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

interface DialectAdapter
{
    public function identifierEscape($str);
    public function getViewDefinition($view_name, $dbc, $db_name);
    public function defaultDatabase();
    public function temporaryTable($name, $source_table);
    public function sep();
    public function addSelectLimit($query, $int_limit);
    public function currency();
    public function curtime();
    public function datediff($date1, $date2);
    public function monthdiff($date1, $date2);
    public function yeardiff($date1, $date2);
    public function weekdiff($date1, $date2);
    public function seconddiff($date1, $date2);
    public function dateymd($date1);
    public function dayofweek($field);
    public function convert($expr, $type);
    public function locate($substr, $str);
    public function concat($expressions);
    public function setLockTimeout($seconds);
    public function setCharSet($charset);
}

