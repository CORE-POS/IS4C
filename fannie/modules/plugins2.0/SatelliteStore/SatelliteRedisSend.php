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
    public $name = 'Satellite Store Redis Send';

    public $log_start_stop = false;

    public $default_schedule = array(
        'min' => '2,7,12,17,22,27,32,37,42,47,52,57',
        'hour' => '7-22',
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );
    
    public function run()
    {
        if ($this->isLocked()) {
            return false;
        }
        $this->lock();

        $conf = $this->config->get('PLUGIN_SETTINGS');
        $my_db = $conf['SatelliteDB'];
        $myID = $conf['SatelliteStoreID'];
        $redis_host = $conf['SatelliteRedis'];

        $remote = FannieDB::get($this->config->get('TRANS_DB'));
        if (!$remote->isConnected()) {
            echo "No connection";
            $this->unlock();
            return false;
        }

        $local = $this->localDB($remote, $myID, $my_db);
        if (!$local->isConnected($my_db)) {
            echo "No local connection";
            $this->unlock();
            return false;
        }

        try {
            $redis = new Predis\Client($redis_host);

            $this->sendTable($local, $redis, $myID, 'dtransactions', 'store_row_id');
            $this->sendTable($local, $redis, $myID, 'PaycardTransactions', 'storeRowId');
            $this->sendTable($local, $redis, $myID, 'CapturedSignature', 'capturedSignatureID');
        } catch (Exception $ex) {
        }

        $this->unlock();
    }

    /**
      Send table data to Redis
      @param $local [SQLManager] connection to local SQL database
      @param $redis [Predis\Client] connection to Redis database
      @param $myID [int] store ID
      @param $table [string] name of table
      @param $column [string] name of unique, incrementing column in table

      This sets a redis key $table:$column:$myID containing the highest
      column value that has been queued from this store. It's important
      that $column be both unique and sequential to avoid missing or
      duplicating records.

      Any record(s) that have not yet been sent based on $column value
      are JSON-encoded and LPUSH'd into a list named $table. The HQ
      side is responsible for RPOP'ing the records 
    */
    private function sendTable($local, $redis, $myID, $table, $column)
    {
        try {
            $lastID = $redis->get($table . ':' . $column . ':' . $myID);
            if ($lastID === null) {
                $lastID = 0;
            }
            $prep = $local->prepare('SELECT * FROM ' . $table . ' WHERE ' . $column . ' > ? ORDER BY ' . $column);
            $res = $local->execute($prep, array($lastID));
            $max = $lastID;
            while ($row = $local->fetchRow($res)) {
                if ($row[$column] > $max) {
                    $max = $row[$column];
                }
                $redis->lpush($table, $this->encode($row));
                $redis->set($table . ':' . $column . ':' . $myID, $max);
            }
        } catch (Exception $ex) {
            // connection to redis failed. 
            // no cleanup required
        }
    }

    /**
     * Encode row data for transmission through Redis
     * By default the encoding is JSON. If the row cannot
     * be JSON encoded, test each value and base64 encode
     * any values that cannot be natively JSON encoded.
     * Known situation where this occurs is binary data strings.
     * @param $row [array] of values
     * @return [string] encoded representation
     */
    private function encode($row)
    {
        $json = json_encode($row);
        if ($json === false) {
            $arr = array();
            foreach (array_keys($row) as $i) {
                if (json_encode($row[$i]) === false) {
                    $arr[$i] = 'base64:' . base64_encode($row[$i]);
                } else {
                    $arr[$i] = $row[$i];
                }
            }
            $json = json_encode($arr);
        }

        return $json;
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

