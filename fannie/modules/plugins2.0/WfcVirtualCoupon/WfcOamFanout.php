<?php

class WfcOamFanout extends FannieTask 
{
    public $name = 'WFC OAM Fan Out';
    public $description = 'Update OAM notifications for today & push to lanes as needed';
    public $log_start_stop = false;

    public function run()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $curP = $dbc->prepare('SELECT * FROM WfcOamSchedule WHERE ? BETWEEN startDate AND endDate');
        $curRow = $dbc->getRow($curP, array(date('Y-m-d')));
        if ($curRow) {
            $prep = $dbc->prepare("SELECT card_no
                FROM " . FannieDB::fqn('dlog', 'trans') . "
                WHERE upc=?
                    GROUP BY card_no
                HAVING SUM(quantity) <> 0");
            $res = $dbc->execute($prep, array($curRow['upc']));
            
            $chkP = $dbc->prepare("SELECT cardNo FROM CustomerNotifications WHERE message <> '' AND cardNo=? AND source='WFC.OAM'");
            $setP = $dbc->prepare("UPDATE CustomerNotifications SET message='' WHERE cardNo=? AND source='WFC.OAM'");
            $needSync = 0;
            while ($row = $dbc->fetchRow($res)) {
                $check = $dbc->getValue($chkP, array($row['card_no']));
                if ($check) {
                    $needSync++;
                    $dbc->execute($setP, array($row['card_no']));
                }
            }
            if ($needSync) {
                $this->cronMsg("Re-syncing OAM notifications ({$needSync})");
                COREPOS\Fannie\API\data\SyncLanes::pushTable('CustomerNotifications');
                $curl = curl_init("http://steve/IS4C/fannie/sync/TableSyncPage.php?tablename=&othertable=CustomerNotifications");
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_exec($curl);
                curl_close($curl);
            }
        }
    }
}

