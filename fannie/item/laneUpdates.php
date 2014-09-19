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

if (!isset($FANNIE_ROOT))
    require(dirname(__FILE__) . '/../config.php');
if (!class_exists('SQLManager'))
    require($FANNIE_ROOT.'src/SQLManager.php');

function addProductAllLanes($upc){
    global $FANNIE_LANES, $FANNIE_OP_DB, $FANNIE_SERVER_DBMS;
    $laneupdate_sql = FannieDB::get($FANNIE_OP_DB);

    $server_table_def = $laneupdate_sql->table_definition('products',$FANNIE_OP_DB);

    // generate list of server columns
    $server_cols = array();
    foreach($server_table_def as $k=>$v)
        $server_cols[$k] = True;

    for ($i = 0; $i < count($FANNIE_LANES); $i++){
        $laneupdate_sql->add_connection($FANNIE_LANES[$i]['host'],$FANNIE_LANES[$i]['type'],
            $FANNIE_LANES[$i]['op'],$FANNIE_LANES[$i]['user'],
            $FANNIE_LANES[$i]['pw']);
        
        if (!isset($laneupdate_sql->connections[$FANNIE_LANES[$i]['op']]) || $laneupdate_sql->connections[$FANNIE_LANES[$i]['op']] === false) {
            // connect failed
            continue;
        }

        // generate list of columns that exist on both
        // the server and the lane
        $lane_table_def = $laneupdate_sql->table_definition('products',$FANNIE_LANES[$i]['op']);
        $matching_columns = array();
        foreach($lane_table_def as $k=>$v){
            if (isset($server_cols[$k])) $matching_columns[] = $k;
        }

        $selQ = "SELECT ";
        $ins = "INSERT INTO products (";
        foreach($matching_columns as $col){
            $selQ .= $col.",";
            $ins .= $col.",";
        }
        $selQ = rtrim($selQ,",")." FROM products WHERE upc='$upc' ORDER BY store_id DESC";
        $selQ = $laneupdate_sql->add_select_limit($selQ, 1, $FANNIE_OP_DB);
        $ins = rtrim($ins,",").")";

        $laneupdate_sql->transfer($FANNIE_OP_DB,$selQ,$FANNIE_LANES[$i]['op'],$ins);
    }
}

function deleteProductAllLanes($upc){
    global $FANNIE_OP_DB, $FANNIE_LANES;
    $laneupdate_sql = FannieDB::get($FANNIE_OP_DB);

    for ($i = 0; $i < count($FANNIE_LANES); $i++){
        $tmp = new SQLManager($FANNIE_LANES[$i]['host'],$FANNIE_LANES[$i]['type'],
            $FANNIE_LANES[$i]['op'],$FANNIE_LANES[$i]['user'],
            $FANNIE_LANES[$i]['pw']);
        if (!isset($tmp->connections[$FANNIE_LANES[$i]['op']]) || $tmp->connections[$FANNIE_LANES[$i]['op']] === false) {
            // connect failed
            continue;
        }
        $delQ = $tmp->prepare_statement("DELETE FROM products WHERE upc=?");
        $delR = $tmp->exec_statement($delQ,array($upc),$FANNIE_LANES[$i]['op']);
    }
}

function updateProductAllLanes($upc){
    deleteProductAllLanes($upc);
    addProductAllLanes($upc);
}

?>
