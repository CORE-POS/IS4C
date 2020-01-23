<?php

class SavingsMessageTask extends FannieTask
{
    public $default_schedule = array(
        'min' => 30,
        'hour' => 3,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));

        $start = date('Ymd', mktime(0, 0, 0, 1, 1, date('Y')));
        $end = date('Ymd');

        $prep = $dbc->prepare("SELECT card_no, SUM(memDiscountTotal) AS ttl
            FROM " . FannieDB::fqn('transactionSummary', 'plugin:WarehouseDatabase') . "
            WHERE date_id BETWEEN ? AND ?
                AND card_no=10000
            GROUP BY card_no
            HAVING SUM(memDiscountTotal) > 0");
        $res = $dbc->execute($prep, array($start, $end . ' 23:59:59'));
        $dbc->startTransaction();
        while ($row = $dbc->fetchRow($res)) {
            $msg = sprintf('Owner Savings this year: $%.2f', $row['ttl']);
            $this->setMessage($dbc, $row['card_no'], $msg);
        }
        $dbc->commitTransaction();
    }

    private function setMessage($dbc, $cardno, $msg)
    {
        $idP = $dbc->prepare("SELECT customerNotificationID
            FROM CustomerNotifications
            WHERE cardNo=?
                AND modifierModule='SavingsMessage'");
        $id = $dbc->getValue($idP, array($cardno));
        if ($id) {
            $upP = $dbc->prepare("UPDATE CustomerNotifications
                SET message=?
                WHERE cardNo=?
                    AND modifierModule='SavingsMessage'");
            $dbc->execute($upP, array($msg, $cardno));
        } else{
            $insP = $dbc->prepare("INSERT INTO CustomerNotifications
                (cardNo, source, type, message, modifierModule)
                VALUES (?, 'task', 'receipt', ?, 'SavingsMessage')");
            $dbc->execute($insP, array($cardno, $msg));
        }
    }
}

