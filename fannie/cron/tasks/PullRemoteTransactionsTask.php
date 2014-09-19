<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

class PullRemoteTransactionsTask extends FannieTask
{

    public $name = 'Multistore Transaction Polling';

    public $description = 'Polls other stores for new
    transaction data and adds it to the local transaction
    table';

    public $default_schedule = array(
        'min' => 5,
        'hour' => 0,
        'day' => '1',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        global $FANNIE_OP_DB, $FANNIE_SERVER, $FANNIE_TRANS_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $local_dtrans = $FANNIE_TRANS_DB . $dbc->sep() . 'dtransactions';

        $max1 = $dbc->prepare('
                    SELECT MAX(store_row_id) AS done
                    FROM ' . $local_dtrans . '
                    WHERE store_id=?
                ');

        $max2 = $dbc->prepare('
                    SELECT MAX(store_row_id) AS done
                    FROM ' . $FANNIE_TRANS_DB . $dbc->sep() . 'transarchive
                    WHERE store_id=?
                ');

        $stores = new StoresModel($dbc);
        foreach($stores->find() as $store) {
            if ($store->dbHost() == $FANNIE_SERVER) {
                // that's me! just continue.
                continue;
            } else if ($store->pull() == 0) {
                // configured not to pull from this store
                continue;
            }

            $remoteID = $store->storeID();

            $lowerBound = 0;
            $dtransMax = $dbc->execute($max1, array($remoteID));
            if ($dtransMax === false) {
                echo $this->cronMsg('Polling problem: cannot lookup info in dtransactions');
                continue;
            } else if ($dbc->num_rows($dtransMax) > 0) {
                $row = $dbc->fetch_row($dtransMax);
                $lowerBound = $row['done'];
            } 
            if ($lowerBound == 0) {
                $transarchiveMax = $dbc->execute($max2, array($remoteID));
                if ($transarchiveMax === false) {
                    echo $this->cronMsg('Polling problem: cannot lookup info in transarchive');
                    continue;
                } else if ($dbc->num_rows($transarchiveMax) > 0) {
                    $row = $dbc->fetch_row($transarchiveMax);
                    $lowerBound = $row['done'];
                }
            }

            $connect = $dbc->add_connection($store->dbHost(), $store->dbDriver(),
                                         $store->transDB(), $store->dbUser(),
                                         $store->dbPassword());
            $columns = $dbc->getMatchingColumns($local_dtrans, $FANNIE_OP_DB,
                                            'dtransactions', $store->transDB());

            $selectQ = 'SELECT ' . $columns . '
                        FROM dtransactions
                        WHERE store_id = ' . ((int)$store->storeID()) . '
                            AND store_row_id > ' . ((int)$lowerBound);
            $insertQ = 'INSERT INTO ' . $local_dtrans . ' (' . $columns . ')';
            
            // note:
            // using operational DB on the local side
            // and transaction DB on the remote side
            // reduces chances of a name collision
            $dbc->transfer($store->transDB(), $selectQ,
                           $FANNIE_OP_DB, $insertQ);
        }
    }
}

