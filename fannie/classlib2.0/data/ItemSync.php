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

namespace COREPOS\Fannie\API\data;

class ItemSync 
{
    private static function laneConnect($dbc, $lane)
    {
        $dbc->addConnection($lane['host'], $lane['type'], $lane['op'], $lane['user'], $lane['pw']);
        if (!$dbc->isConnected($lane['op'])) {
            throw new \Exception('Lane ' . $lane['host'] . ' offline');
        }

        return $dbc;
    }

    private static function addProductAllLanes($upc)
    {
        $FANNIE_OP_DB = \FannieConfig::config('OP_DB');
        $FANNIE_LANES = \FannieConfig::config('LANES');
        $STORE_MODE = \FannieConfig::config('STORE_MODE');
        $STORE_ID = \FannieConfig::config('STORE_ID');
        $laneupdate_sql = \FannieDB::get($FANNIE_OP_DB);

        $server_table_def = $laneupdate_sql->tableDefinition('products',$FANNIE_OP_DB);

        for ($i = 0; $i < count($FANNIE_LANES); $i++) {
            try {
                $laneupdate_sql = self::laneConnect($laneupdate_sql, $FANNIE_LANES[$i]);
            
                // generate list of columns that exist on both
                // the server and the lane
                $lane_table_def = $laneupdate_sql->tableDefinition('products',$FANNIE_LANES[$i]['op']);
                $matching_columns = array();
                foreach($lane_table_def as $k=>$v){
                    if (isset($server_table_def[$k])) $matching_columns[] = $k;
                }

                $selQ = "SELECT ";
                $ins = "INSERT INTO products (";
                foreach ($matching_columns as $col) {
                    $selQ .= $col.",";
                    $ins .= $col.",";
                }
                $selQ = rtrim($selQ,",")
                    . " FROM products WHERE upc='$upc' ";
                if ($STORE_MODE == 'HQ') {
                    $selQ .= ' AND store_id=' . ((int)$STORE_ID);
                }
                $selQ = $laneupdate_sql->addSelectLimit($selQ, 1, $FANNIE_OP_DB);
                $ins = rtrim($ins,",").")";

                $laneupdate_sql->transfer($FANNIE_OP_DB,$selQ,$FANNIE_LANES[$i]['op'],$ins);
            } catch (\Exception $ex) {
                // lane offline
            }
        }
    }

    private static function deleteProductAllLanes($upc)
    {
        $FANNIE_OP_DB = \FannieConfig::config('OP_DB');
        $FANNIE_LANES = \FannieConfig::config('LANES');
        $laneupdate_sql = \FannieDB::get($FANNIE_OP_DB);

        for ($i = 0; $i < count($FANNIE_LANES); $i++){
            try {
                $tmp = self::laneConnect($laneupdate_sql, $FANNIE_LANES[$i]);
                $delQ = $tmp->prepare("DELETE FROM products WHERE upc=?", $FANNIE_LANES[$i]['op']);
                $delR = $tmp->execute($delQ,array($upc),$FANNIE_LANES[$i]['op']);
            } catch (\Exception $ex) {
                // lane offline
            }
        }
    }

    private static function syncItem($upc)
    {
        self::deleteProductAllLanes($upc);
        self::addProductAllLanes($upc);
    }

    public static function sync($upc)
    {
        if (!is_array($upc)) {
            $upc = array($upc);
        }
        foreach ($upc as $u) {
            self::syncItem($u);
        }
        self::notifyStores($upc);
    }

    public static function remove($upc)
    {
        if (!is_array($upc)) {
            $upc = array($upc);
        }
        foreach ($upc as $u) {
            self::deleteProductAllLanes($u);
        }
    }

    private static function notifyStores($upc)
    {
        if (class_exists('\\Datto\\JsonRpc\\Http\\Client')) {
            $dbc = \FannieDB::getReadOnly(\FannieConfig::config('OP_DB'));
            $prep = $dbc->prepare('
                SELECT webServiceUrl FROM Stores WHERE hasOwnItems=1 AND storeID<>?
                ');
            $res = $dbc->execute($prep, array(\FannieConfig::config('STORE_ID')));
            while ($row = $dbc->fetchRow($res)) {
                $client = new \Datto\JsonRpc\Http\Client($row['webServiceUrl']);
                $args = array('upc'=>$upc);
                if (is_array($upc) && count($upc) > 1) {
                    $args['fast'] = true;
                }
                $client->query(time(), 'COREPOS\\Fannie\\API\\webservices\\FannieItemLaneSync', $args);
                $client->send();
            }
        }
    }
}

