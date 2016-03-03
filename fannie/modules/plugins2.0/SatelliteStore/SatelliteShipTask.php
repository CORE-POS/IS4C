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
class SatelliteShipTask extends FannieTask
{
    public $name = 'Satellite Store Transaction Sync';

    public $description = 'Ship transaction data from a
satellite store to HQ. Runs repeatedly throughout the day.';

    public $log_start_stop = false;

    public $default_schedule = array(
        'min' => '*/10',
        'hour' => '7-22',
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        $conf = $this->config->get('PLUGIN_SETTINGS');
        $my_db = $conf['SatelliteDB'];
        $myID = $conf['SatelliteStoreID'];

        $remote = FannieDB::get($this->config->get('TRANS_DB'));
        if (!$remote->isConnected()) {
            return false;
        }

        $local = $this->localDB($remote, $myID, $my_db);
        if (!$local->isConnected($my_db)) {
            return false;
        }

        /** get exclusive lock or exist **/
        $lock = fopen(dirname(__FILE__) . '/lockfile', 'r');
        if (!flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);
            return false;
        }

        $this->shipDTrans($remote, $local);
        $this->shipPaycards($remote, $local);
        $this->shipSigs($remote, $local);

        /** release lock **/
        flock($lock, LOCK_UN);
        fclose($lock);
    }

    /**
      Send transaction data to HQ

      The satellite store maintains a list in SatelliteLog
      of each transaction record that has been sent to
      HQ successfully.
    */
    private function shipDTrans($remote, $local)
    {
        $res = $local->query('
            SELECT * 
            FROM dtransactions 
            WHERE store_row_id NOT IN (
                SELECT dtransactionID FROM SatelliteLog
            )');
        if ($local->numRows($res) == 0) {
            return true;
        }

        $logP = $local->prepare('INSERT INTO SatelliteLog (dtransactionID, tdate) VALUES (?, ?)');

        $model = new DTransactionsModel();
        $cols = array_keys($model->getColumns());

        $names = '';
        $vals = '';
        foreach ($cols as $col) {
            $names .= $remote->identifierEscape($col) . ',';
            $vals .= '?,';
        }
        $insQ = 'INSERT INTO dtransactions
            (' . substr($names, 0, strlen($names)-1) . ') 
            VALUES 
            (' . substr($vals, 0, strlen($vals)-1) . ')';
        $insP = $remote->prepare($insQ);

        while ($row = $local->fetchRow($res)) {
            $args = array();
            foreach ($cols as $col) {
                $args[] = $row[$col];
            }
            if ($remote->execute($insP, $args)) {
                $local->execute($logP, array($row['store_row_id'], date('Y-m-d H:i:s')));
            }
        }

        return true;
    }

    private function shipPaycards($remote, $local)
    {
        $res = $local->query('
            SELECT * 
            FROM PaycardTransactions 
            WHERE ' . $local->concat('paycardTransactionID', "'-'", 'registerNo', '') . ' NOT IN (
                SELECT paycardID FROM SatelliteLog
            )');
        if ($local->numRows($res) == 0) {
            return true;
        }

        $logP = $local->prepare('INSERT INTO SatelliteLog (paycardID, tdate) VALUES (?, ?)');

        $model = new PaycardTransactionsModel();
        $cols = array_keys($model->getColumns());

        $names = '';
        $vals = '';
        foreach ($cols as $col) {
            $names .= $remote->identifierEscape($col) . ',';
            $vals .= '?,';
        }
        $insQ = 'INSERT INTO PaycardTransactions
            (' . substr($names, 0, strlen($names)-1) . ') 
            VALUES 
            (' . substr($vals, 0, strlen($vals)-1) . ')';
        $insP = $remote->prepare($insQ);

        while ($row = $local->fetchRow($res)) {
            $args = array();
            foreach ($cols as $col) {
                $args[] = $row[$col];
            }
            if ($remote->execute($insP, $args)) {
                $local->execute($logP, array($row['paycardTransactionID'] . '-' . $row['registerNo'], date('Y-m-d H:i:s')));
            }
        }

        return true;
    }

    private function shipSigs($remote, $local)
    {
        $res = $local->query('
            SELECT * 
            FROM CapturedSignature 
            WHERE capturedSignatureID NOT IN (
                SELECT capSigID FROM SatelliteLog
            )');
        if ($local->numRows($res) == 0) {
            return true;
        }

        $logP = $local->prepare('INSERT INTO SatelliteLog (capSigID, tdate) VALUES (?, ?)');

        $model = new PaycardTransactionsModel();
        $cols = array_keys($model->getColumns());
        $cols = array_filter($cols, function($i){ return $i !== 'capturedSignatureID'; });

        $names = '';
        $vals = '';
        foreach ($cols as $col) {
            $names .= $remote->identifierEscape($col) . ',';
            $vals .= '?,';
        }
        $insQ = 'INSERT INTO CapturedSignature
            (' . substr($names, 0, strlen($names)-1) . ') 
            VALUES 
            (' . substr($vals, 0, strlen($vals)-1) . ')';
        $insP = $remote->prepare($insQ);

        while ($row = $local->fetchRow($res)) {
            $args = array();
            foreach ($cols as $col) {
                $args[] = $row[$col];
            }
            if ($remote->execute($insP, $args)) {
                $local->execute($logP, array($row['capturedSignatureID'], date('Y-m-d H:i:s')));
            }
        }

        return true;
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

