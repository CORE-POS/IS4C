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

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    *  8Aug2015 Eric Lee Updated.
    * 27Feb2013 Eric Lee Add functions like the originals with table_name as a parameter.
    *           Supports more product-related tables on the lane.

*/

if (!isset($FANNIE_ROOT))
    require(dirname(__FILE__) . '/../config.php');
if (!class_exists('SQLManager'))
    require($FANNIE_ROOT.'src/SQLManager.php');


function addAllLanes($upc, $table_name){

    $FANNIE_OP_DB = FannieConfig::config('OP_DB');
    $FANNIE_LANES = FannieConfig::config('LANES');
    $STORE_MODE = FannieConfig::config('STORE_MODE');
    $STORE_ID = FannieConfig::config('STORE_ID');
    $laneupdate_sql = FannieDB::get($FANNIE_OP_DB);

    $server_table_def = $laneupdate_sql->tableDefinition("{$table_name}",$FANNIE_OP_DB);
    if (count($server_table_def) == 0)
         echo "<br />server_table_def is empty for >{$table_name}<";

    // generate list of server columns
    $server_cols = array();
    foreach($server_table_def as $k=>$v)
        $server_cols[$k] = True;

    for ($i = 0; $i < count($FANNIE_LANES); $i++){
        $laneupdate_sql->addConnection($FANNIE_LANES[$i]['host'],$FANNIE_LANES[$i]['type'],
            $FANNIE_LANES[$i]['op'],$FANNIE_LANES[$i]['user'],
            $FANNIE_LANES[$i]['pw']);

        if ( $laneupdate_sql->table_exists("$table_name") ) {
            // generate list of columns that exist on both
            // the server and the lane
            $lane_table_def = $laneupdate_sql->tableDefinition("{$table_name}",$FANNIE_LANES[$i]['op']);
            $matching_columns = array();
            foreach($lane_table_def as $k=>$v){
                if (isset($server_cols[$k])) $matching_columns[] = $k;
            }

            $selQ = "SELECT ";
            $ins = "INSERT INTO $table_name (";
            foreach($matching_columns as $col){
                $selQ .= $col.",";
                $ins .= $col.",";
            }
            $selQ = rtrim($selQ,",") .
                " FROM $table_name WHERE upc='$upc'";
            if ($STORE_MODE == 'HQ') {
                $selQ .= ' AND store_id=' . ((int)$STORE_ID);
            }
            if ( isset($matching_columns['store_id']) )
                $selQ .= " ORDER BY store_id DESC";
            $selQ = $laneupdate_sql->addSelectLimit($selQ, 1, $FANNIE_OP_DB);
            $ins = rtrim($ins,",").")";

            if (True) {
                // Production
                $laneupdate_sql->transfer($FANNIE_OP_DB,$selQ,$FANNIE_LANES[$i]['op'],$ins);
            } else {
                // Test
                $tok = $laneupdate_sql->transfer($FANNIE_OP_DB,$selQ,$FANNIE_LANES[$i]['op'],$ins);
                $laneupdate_sql->logger("add to $table_name tok: $tok");
            }
        }
    }
}

function removeAllLanes($upc, $table_name){

    $FANNIE_OP_DB = FannieConfig::config('OP_DB');
    $FANNIE_LANES = FannieConfig::config('LANES');

    for ($i = 0; $i < count($FANNIE_LANES); $i++){
        $tmp = new SQLManager($FANNIE_LANES[$i]['host'],$FANNIE_LANES[$i]['type'],
            $FANNIE_LANES[$i]['op'],$FANNIE_LANES[$i]['user'],
            $FANNIE_LANES[$i]['pw']);
        if ( $tmp->table_exists("$table_name") ) {
            $delQ = "DELETE FROM $table_name WHERE upc='$upc'";
            $delR = $tmp->query($delQ,$FANNIE_LANES[$i]['op']);
        }
    }
}

function updateAllLanes($upc, $tables){
    foreach ($tables as $table) {
        removeAllLanes($upc, $table);
        addAllLanes($upc, $table);
    }
}

function deleteAllLanes($upc, $tables){
    foreach ($tables as $table) {
        removeAllLanes($upc, $table);
    }
}

