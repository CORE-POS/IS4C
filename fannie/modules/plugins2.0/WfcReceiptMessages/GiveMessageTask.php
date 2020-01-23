<?php

class GiveMessageTask extends FannieTask
{

    public $default_schedule = array(
        'min' => 10,
        'hour' => 2,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));

        $start = date('Y-m-d', mktime(0, 0, 0, 1, 1, date('Y')));
        $end = date('Y-m-d');
        $dlog = DTransactionsModel::selectDlog($start, $end);

        $prep = $dbc->prepare("SELECT card_no, SUM(total) AS ttl
            FROM {$dlog}
            WHERE tdate BETWEEN ? AND ?
                AND department=701
                AND card_no=10000
            GROUP BY card_no
            HAVING SUM(total) > 0");
        $res = $dbc->execute($prep, array($start, $end . ' 23:59:59'));
        $dbc->startTransaction();
        while ($row = $dbc->fetchRow($res)) {
            $msg = sprintf('GIVE Donations this year: $%.2f', $row['ttl']);
            $this->setMessage($dbc, $row['card_no'], $msg);
        }
        $dbc->commitTransaction();
    }

    private function setMessage($dbc, $cardno, $msg)
    {
        $idP = $dbc->prepare("SELECT customerNotificationID
            FROM CustomerNotifications
            WHERE cardNo=?
                AND modifierModule='GiveMessage'");
        $id = $dbc->getValue($idP, array($cardno));
        if ($id) {
            $upP = $dbc->prepare("UPDATE CustomerNotifications
                SET message=?
                WHERE cardNo=?
                    AND modifierModule='GiveMessage'");
            $dbc->execute($upP, array($msg, $cardno));
        } else{
            $insP = $dbc->prepare("INSERT INTO CustomerNotifications
                (cardNo, source, type, message, modifierModule)
                VALUES (?, 'task', 'receipt', ?, 'GiveMessage')");
            $dbc->execute($insP, array($cardno, $msg));
        }
    }
}

