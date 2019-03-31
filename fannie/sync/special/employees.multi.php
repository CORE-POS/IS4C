<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__) . '/generic.mysql.php');

/**
  Sync the whole table using mysqldump. Then
  look up employees numbers that are valid for
  this store and construct a DELETE query to
  remove all others. Run the DELETE query at
  each lane.
*/

$config = FannieConfig::factory();
$dbc = FannieDB::get($config->get('OP_DB'));
$map = new StoreEmployeeMapModel($dbc);
$map->storeID($config->get('STORE_ID'));
$query = '
    DELETE FROM employees
    WHERE emp_no NOT IN (';
$args = array();
foreach ($map->find() as $obj) {
    $query .= '?,';
    $args[] = $obj->empNo();
}
$query = substr($query, 0, strlen($query)-1) . ')';

foreach ($FANNIE_LANES as $lane) {
    if (isset($lane['offline']) && $lane['offline'] && !$includeOffline) {
        continue;
    }
    $sql = new SQLManager($lane['host'],$lane['type'],$lane['op'],
            $lane['user'],$lane['pw']);
    if ($sql->connections[$lane['op']] !== false) {
        $prep = $sql->prepare($query);
        $sql->execute($prep, $args);
    }
}

echo "<li>Employees table synched</li>";
