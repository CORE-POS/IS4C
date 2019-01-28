<?php

class SatelliteDuplicateCheck extends FannieTask
{
    public $name = 'Satellite Data Duplicate Check';

    public function run()
    {
        $myID = $this->config->get('STORE_ID');
        $dbc = FannieDB::get($this->config->get('TRANS_DB'));

        $query = "SELECT store_id, store_row_id
            FROM " . FannieDB::fqn('dtransactions', 'trans') . "
            WHERE store_id <> ?
                AND trans_status NOT IN ('Z')
            GROUP BY store_id, store_row_id
            HAVING COUNT(*) > 1";
        $res = $dbc->query($query, array($myID));
        if ($dbc->numRows($res)) {
            $addr = 'andy@wholefoods.coop';
            $from = "From: automail\r\n";
            $subject = 'Duplicate Transaction Records';
            $msg = 'Received duplicate transaction records on ' . date('Y-m-d') . "\n";
            while ($row = $dbc->fetchRow($res)) {
                $msg .= "Store #{$row['store_id']}, store_row_id #{$row['store_row_id']}\n";
            }
            mail($addr, $subject, $msg, $from);
        }
    }
}

