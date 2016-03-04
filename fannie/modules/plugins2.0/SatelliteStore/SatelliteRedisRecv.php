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
class SatelliteRedisRecv extends FannieTask
{
    public $name = 'Satellite Store Transaction Sync';
    
    public function run()
    {
        $conf = $this->config->get('PLUGIN_SETTINGS');
        $my_db = $conf['SatelliteDB'];
        $myID = $conf['SatelliteStoreID'];
        $redis = $conf['SatelliteRedis'];

        $dbc = FannieDB::get($this->config->get('TRANS_DB'));
        if (!$dbc->isConnected()) {
            echo "No connection";
            return false;
        }

        $redis = new Predis\Client();

        $this->getTrans($dbc, $redis);
    }

    private function sendTrans($dbc, $redis)
    {
        try {
            $model = new DTransactionsModel();
            $cols = array_keys($model->getColumns());

            $names = '';
            $vals = '';
            foreach ($cols as $col) {
                $names .= $dbc->identifierEscape($col) . ',';
                $vals .= '?,';
            }
            $insQ = 'INSERT INTO dtransactions
                (' . substr($names, 0, strlen($names)-1) . ') 
                VALUES 
                (' . substr($vals, 0, strlen($vals)-1) . ')';
            $insP = $dbc->prepare($insQ);

            while (($json = $redis->rpop('dtransactions')) !== null) {
                $row = json_decode($json, true);
                $args = array();
                foreach ($cols as $col) {
                    $args[] = $row[$col];
                }
                $dbc->execute($insP, $args);
            }
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

