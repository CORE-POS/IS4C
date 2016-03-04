<?php
/*******************************************************************************

    Copyright 2016 Whole Foods Co-op

    This file is part of IT CORE.

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
*/
class SatelliteRedisSend extends FannieTask
{
    public $name = 'Satellite Store Transaction Sync';
    
    public function run()
    {
        $conf = $this->config->get('PLUGIN_SETTINGS');
        $my_db = $conf['SatelliteDB'];
        $myID = $conf['SatelliteStoreID'];
        $redis = $conf['SatelliteRedis'];

        $remote = FannieDB::get($this->config->get('TRANS_DB'));
        if (!$remote->isConnected()) {
            echo "No connection";
            return false;
        }

        $local = $this->localDB($remote, $myID, $my_db);
        if (!$local->isConnected($my_db)) {
            echo "No local connection";
            return false;
        }

        $redis = new Predis\Client();

        $this->sendTrans($local, $redis, $myID);
    }

    private function sendTrans($local, $redis, $myID)
    {
        try {
            $lastID = $redis->get('store_row_id:' . $myID);
            if ($lastID === null) {
                $lastID = 0;
            }
            $prep = $local->prepare('SELECT * FROM dtransactions WHERE store_row_id > ?');
            $res = $local->execute($prep, array($lastID));
            $max = $lastID;
            while ($row = $local->fetchRow($res)) {
                if ($row['store_row_id'] > $max) {
                    $max = $row['store_row_id'];
                }
                $redis->lpush('dtransactions', json_encode($row));
            }
            $redis->set('store_row_id:' . $myID, $max);

        } catch (Exception $ex) {
            // connection to redis failed. 
            // no cleanup required
        }
    }

    private function localDB($dbc, $myID, $my_db)
    {
        $prep = $dbc->prepare('
            SELECT *
            FROM ' . $this->config->get('OP_DB') . $dbc->sep() . 'Stores
            WHERE storeID=?');
        $row = $dbc->getRow($prep, array($myID));

        return new SQLManager($row['dbHost'], $row['dbDriver'], $my_db, $row['dbUser'], $row['dbPassword']);
    }
}

