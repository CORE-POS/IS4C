<?php

/**
  Special: connect to transactional database on the lane
  instead of the operational database. Tax rates are on
  the operational side on the server and transactional
  side on the lanes.
*/

foreach ($FANNIE_LANES as $lane) {
    $dbc->addConnection($lane['host'],$lane['type'],$lane['trans'],
            $lane['user'],$lane['pw']);
    if ($dbc->connections[$lane['trans']] !== false) {
        $selectQ = '
            SELECT id,
                rate,
                description
            FROM taxrates';
        $insQ = '
            INSERT INTO taxrates
                (id, rate, description)';
        $dbc->query("TRUNCATE TABLE taxrates", $lane['trans']);
        $dbc->transfer($FANNIE_OP_DB, $selectQ, $lane['trans'], $insQ);
    }
}

echo "<li>Tax rates table synched</li>";

