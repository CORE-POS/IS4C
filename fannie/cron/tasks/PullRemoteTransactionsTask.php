<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB;
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

        $verifyP = $dbc->prepare("SELECT COUNT(*) FROM {$local_dtrans} WHERE store_id=?");

        $stores = new StoresModel($dbc);
        foreach($stores->find() as $store) {
            if ($store->dbHost() == $this->config->get('SERVER')) {
                // that's me! just continue.
                continue;
            } elseif ($store->pull() == 0 || $store->dbDriver() == '') {
                // configured not to pull from this store
                continue;
            }

            $remoteID = $store->storeID();

            $lowerBound = $dbc->getValue($max1, array($remoteID));
            if ($lowerBound === false) {
                $this->cronMsg('Polling problem: cannot lookup info in dtransactions (#' . $remoteID . ')', FannieLogger::WARNING);
                continue;
            } elseif ($lowerBound == 0) {
                $lowerBound = $dbc->getValue($max2, array($remoteID));
                if ($lowerBound === false) {
                    $this->cronMsg('Polling problem: cannot lookup info in dtransactions (#' . $remoteID . ')', FannieLogger::WARNING);
                    continue;
                }
            }

            $connect = $dbc->addConnection($store->dbHost(), $store->dbDriver(),
                                         $store->transDB(), $store->dbUser(),
                                         $store->dbPassword());

            /**
             * Adding a "pong" record on the remote before importing creates a marker
             * that lets other imported records be manipulated without losing
             * MAX(store_row_id) as an indicator where the import ended
             */
            $pongQ = "INSERT INTO dtransactions (datetime, register_no, emp_no, trans_no, upc,
                description, trans_type, trans_subtype, trans_status, department,
                quantity, cost, unitPrice, total, regPrice, scale, tax, foodstamp,
                discount, memDiscount, discountable, discounttype, ItemQtty, volDiscType,
                volume, VolSpecial, mixMatch, matched, voided, memType, staff, numflag,
                charflag, card_no, trans_id) VALUES (" . $dbc->now() . ", 0, 0, 0, 'DAILYPONG',
                'DAILYPONG', 'L', 'OG', '', 0,
                0, 0, 0, 0, 0, 0, 0, 0,
                0, 0, 0, 0, 0, 0,
                0, 0, '', 0, 0, 0, 0, 0,
                '', 0, 1)";
            $dbc->query($pongQ, $store->transDB());

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

            $records = $dbc->getValue($verifyP, array($remoteID));
            if ($records == 0) {
                $this->cronMsg('No records imported for store #' . $remoteID, FannieLogger::ALERT);
            } else {
                $idP = $dbc->prepare("SELECT emp_no, register_no, trans_no, description FROM {$local_dtrans}
                    WHERE store_id=? AND trans_subtype='CM' AND description LIKE '%STORE%'");
                $idR = $dbc->execute($idP, array($store->storeID()));
                $rewriteP = $dbc->prepare("UPDATE {$local_dtrans} SET store_id=?
                    WHERE store_id=?
                        AND emp_no=?
                        AND register_no=?
                        AND trans_no=?");
                while ($idW = $dbc->fetchRow($idR)) {
                    list(,$newID) = explode(' ', $idW['description']);
                    $args = array($newID, $store->storeID(), $idW['emp_no'], $idW['register_no'], $idW['trans_no']);
                    $dbc->execute($rewriteP, $args);
                }
            }

        }
    }
}

