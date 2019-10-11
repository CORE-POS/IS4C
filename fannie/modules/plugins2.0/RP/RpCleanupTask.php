<?php

class RpCleanupTask extends FannieTask 
{
    public $name = 'RP Cleanup Task';

    public $description = 'Reset Produce Ordering Data';    

    public $default_schedule = array(
        'min' => 0,
        'hour' => 23,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $dbc->query("DELETE FROM RpSessions");
        $dbc->query("UPDATE PurchaseOrder SET placed=1, placedDate=" . $dbc->now() . "
                WHERE userID=-99 AND placed=0");
        $dbc->query('DELETE FROM shelftags WHERE id=6');
    }
}
