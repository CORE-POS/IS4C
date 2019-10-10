<?php

/**
 * Create EquityPaymentPlanAccounts records
 * for different kinds of recurring payments
*/
class EqPlanTask extends FannieTask
{
    public function run()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $this->abMismatch($dbc);
        $this->getOnline($dbc);
        $this->getInStore($dbc);

        $this->unexpectedPayments($dbc);
    }

    private function hasPlan($dbc, $card)
    {
        $prep = $dbc->prepare('SELECT cardNo FROM EquityPaymentPlanAccounts WHERE cardNo=?');
        return $dbc->getValue($prep, array($card));
    }

    private function createPlan($dbc, $card, $planID)
    {
        $insP = $dbc->prepare('INSERT INTO EquityPaymentPlanAccounts (cardNo, equityPaymentPlanID) VALUES (?, ?)');
        return $dbc->execute($insP, array($card, $planID));
    }

    private function getOnline($dbc)
    {
        $yesterday = date('Y-m-d', strtotime('30 days ago'));
        $dlog = DTransactionsModel::selectDlog($yesterday);
        $prep = $dbc->prepare("
            SELECT tdate, trans_num, card_no
            FROM {$dlog}
            WHERE tdate >= ?
                AND department=992
                AND store_id=50");
        $res = $dbc->execute($prep, array($yesterday));
        $chkP = $dbc->prepare("SELECT total FROM {$dlog} WHERE tdate BETWEEN ? AND ? and department=991 AND trans_num=?");
        while ($row = $dbc->fetchRow($res)) {
            if ($this->hasPlan($dbc, $row['card_no'])) {
                continue;
            }
            $date = date('Y-m-d', strtotime($row['tdate']));
            $chk = $dbc->getValue($chkP, array($date . ' 00:00:00', $date . ' 23:59:59', $row['trans_num']));
            if ($chk === false) {
                $this->createPlan($dbc, $row['card_no'], 3);
            }
        }
    }

    private function getInStore($dbc)
    {
        $yesterday = date('Y-m-d', strtotime('yesterday'));
        $yID = date('Ymd', strtotime('yesterday'));
        $dlog = DTransactionsModel::selectDlog($yesterday);
        $ptrans = $this->config->get('TRANS_DB') . $dbc->sep() . 'PaycardTransactions';

        $getCard = $dbc->prepare("
            SELECT card_no FROM {$dlog}
            WHERE tdate BETWEEN ? AND ?
                AND trans_num=?");
        $prep = $dbc->prepare("
            SELECT empNo, registerNo, transNo, dateID
            FROM {$ptrans}
            WHERE dateID >= ?
                AND empNo <> 9999
                AND registerNo <> 99
                AND transType='R.Sale'
                AND xResultMessage LIKE 'Approved %'
                AND amount=20
        ");
        $res = $dbc->execute($prep, array($yID));
        while ($row = $dbc->fetchRow($res)) {
            $date = substr($row['dateID'], 0, 4) . '-' . substr($row['dateID'], 4, 2) . '-' . substr($row['dateID'], -2);
            $card = $dbc->getValue($getCard, array(
                $date . ' 00:00:00',
                $date . ' 23:59:59',
                $row['empNo'] . '-' . $row['registerNo'] . '-' . $row['transNo'],
            ));
            if ($card && !$this->hasPlan($dbc, $card)) {
                $this->createPlan($dbc, $card, 2);
            }
        }
    }

    private function unexpectedPayments($dbc)
    {
        $yesterday = date('Y-m-d', strtotime('yesterday'));
        $dlog = DTransactionsModel::selectDlog($yesterday);

        $prep = $dbc->prepare("
            SELECT d.card_no
            FROM {$dlog} AS d
                INNER JOIN EquityPaymentPlanAccounts AS e ON d.card_no=e.cardNo
            WHERE emp_no <> 1001
                AND store_id <> 50
                AND department = 991
                AND tdate BETWEEN ? AND ?
            GROUP BY d.card_no
            HAVING SUM(total) <> 0
        ");
        $res = $dbc->execute($prep, array($yesterday . ' 00:00:00', $yesterday . ' 23:59:59'));
        while ($row = $dbc->fetchRow($res)) {
            $this->cronMsg('Unexpected equity payment from owner #' . $row['card_no'], FannieLogger::ALERT);
        }
    }

    private function abMismatch($dbc)
    {
        $res = $dbc->query("
            SELECT card_no, sum(stockPurchase) AS ttl
            FROM " . FannieDB::fqn('stockpurchases', 'trans') . "
            WHERE dept=992 AND card_no <> 11
            GROUP BY card_no
            HAVING sum(stockPurchase) > 20
        ");
        while ($row = $dbc->fetchRow($res)) {
            $this->cronMsg(sprintf('Excess A equity #%d, $%.2f', $row['card_no'], $row['ttl']), FannieLogger::ALERT);
        }
    }
}

