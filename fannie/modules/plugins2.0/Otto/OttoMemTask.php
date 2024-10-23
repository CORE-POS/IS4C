<?php

if (!class_exists('OttoMem')) {
    include(__DIR__ . '/OttoMem.php');
}

class OttoMemTask extends FannieTask
{
    public function run()
    {
        $thisHour = date('G');
        $lastHour = $thisHour - 1;
        
        $thisHour = str_pad($thisHour, 2, '0', STR_PAD_LEFT);
        $lastHour = str_pad($lastHour, 2, '0', STR_PAD_LEFT);

        $stamp1 = date('Y-m-d') . ' ' . $lastHour . ':00:01';
        $stamp2 = date('Y-m-d') . ' ' . $thisHour . ':00:00';

        $dbc = FannieDB::get($this->config->get('TRANS_DB'));
        $prep = $dbc->prepare("SELECT card_no, store_id FROM dlog WHERE tdate BETWEEN ? AND ?
            AND department=992 GROUP BY card_no, store_id HAVING SUM(total) >= 20");
        $res = $dbc->execute($prep, array($stamp1, $stamp2));
        while ($row = $dbc->fetchRow($res)) {
            $source = $row['store_id'] == 1 ? 'Hillside' : 'Denfeld';
            $om = new OttoMem();
            $om->post($row['card_no'], $source);
        }
    }
}

