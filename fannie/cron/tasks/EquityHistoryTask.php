<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

class EquityHistoryTask extends FannieTask
{

    public $name = 'Equity History';

    public $description = 'Extracts equity transactions and
adds them to dedicated history tables. Fetches any new 
transactions in the previous 15 days. Can be safely run
repeatedly. Normally run after rotating dtransactions data.
Deprecates nightly.equity.php.';

    public $default_schedule = array(
        'min' => 30,
        'hour' => 1,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    private $loggedP = null;

    private function isLogged($dbc, $info)
    {
        if ($this->loggedP === null) {
            $this->loggedP = $this->logCheckStatement($dbc);
        }
        $args = array(
            $info['tdate'],
            $info['card_no'],
            $info['department'],
            $info['trans_num'],
        );
        if ($this->hasTransID($dbc)) {
            $args[] = $info['trans_id'];
        }
        $loggedR = $dbc->execute($this->loggedP, $args);
        while ($logW = $dbc->fetchRow($loggedR)) {
            if ($logW['stockPurchase'] == $info['total']) {
                return true;
            }
        }

        return false;
    }

    private function hasTransID($dbc)
    {
        $table = $dbc->tableDefinition('stockpurchases');
        return isset($table['trans_id']);
    }

    private function logCheckStatement($dbc)
    {
        $query = '
            SELECT stockPurchase
            FROM stockpurchases
            WHERE tdate=?
                AND card_no=?
                AND dept=?
                AND trans_num=?'; 
        if ($this->hasTransID($dbc)) {
            $query .= ' AND (trans_id=? OR trans_id=0)';
        }

        return $dbc->prepare($query);
    }

    public function run()
    {
        $dbc = FannieDB::get($this->config->get('TRANS_DB'));

        // build department list
        $ret = preg_match_all("/[0-9]+/",$this->config->get('EQUITY_DEPARTMENTS'), $depts);
        $depts = array_pop($depts);
        $dlist = "(";
        $where_args = array();
        foreach ($depts as $d) {
            $dlist .= "?,";
            $where_args[] = $d;
        }
        $dlist = substr($dlist,0,strlen($dlist)-1).")";
        if ($dlist == ')') {
            // no configured departments
            return false;
        }
        
        // lookup AR transactions from past 15 days
        $lookupQ = "SELECT card_no,
                department, total,
                tdate, trans_num, trans_id
                FROM dlog_15
                WHERE department IN $dlist"; 
        $lookupP = $dbc->prepare($lookupQ);
        $lookupR = $dbc->execute($lookupP, $where_args);

        $checkP = $dbc->prepare('SELECT stockPurchase FROM stockpurchases 
                    WHERE tdate=? AND trans_num=? AND card_no=? AND dept=?');
        $addP = $dbc->prepare('INSERT INTO stockpurchases (card_no, stockPurchase, tdate, trans_num, dept)
                            VALUES (?, ?, ?, ?, ?)');
        $model = new StockpurchasesModel($dbc);
        while ($lookupW = $dbc->fetch_row($lookupR)) {
            if ($this->isLogged($dbc, $lookupW)) {
                continue;
            }

            $model->card_no($lookupW['card_no']);
            $model->stockPurchase($lookupW['total']);
            $model->tdate($lookupW['tdate']);
            $model->trans_num($lookupW['trans_num']);
            $model->dept($lookupW['department']);
            $model->trans_id($lookupW['trans_id']);
            if ($model->save() === false) {
                $this->cronMsg('Error adding equity entry '.$lookupW['tdate']. ' '.$lookupW['trans_num'], FannieLogger::ERROR);
            }
        }

        // rebuild ar history sum table
        $dbc->query("TRUNCATE TABLE equity_history_sum");
        $query = "INSERT INTO equity_history_sum
            SELECT card_no, SUM(stockPurchase), MIN(tdate)
            FROM stockpurchases GROUP BY card_no";
        $def = $dbc->tableDefinition('equity_history_sum');
        if (isset($def['mostRecent'])) {
            $query = str_replace('MIN(tdate)', 'MIN(tdate), MAX(tdate)', $query);
        }
        $try = $dbc->query($query);
        if ($try === false) {
            $this->cronMsg('Error rebuilding equity_history_sum table', FannieLogger::ERROR);
        }

        if (isset($def['mostRecent'])) {
            /**
              Lookup transactions with net equity purchase
              of zero. These transactions should not impact
              the first/last equity purchase dates
            */
            $voidedR = $dbc->query('
                SELECT card_no,
                    trans_num
                FROM stockpurchases
                GROUP BY card_no,trans_num
                HAVING SUM(stockPurchase)=0');
            $voids = array();
            while ($row = $dbc->fetchRow($voidedR)) {
                if (!isset($voids[$row['card_no']])) {
                    $voids[$row['card_no']] = array();
                }
                $voids[$row['card_no']][] = $row['trans_num'];
            }

            /**
              For applicable members, lookup min and max
              date values again excluding the net-zero
              transactions. Update date fields for these
              members.
            */
            $upP = $dbc->prepare('
                UPDATE equity_history_sum
                SET startdate=?,
                    mostRecent=?
                WHERE card_no=?');
            foreach ($voids as $card_no => $transactions) {
                $query = '
                    SELECT MIN(tdate) AS startdate,
                        MAX(tdate) AS mostRecent
                    FROM stockpurchases
                    WHERE card_no=?
                        AND trans_num NOT IN (';
                $args = array($card_no);
                foreach ($transactions as $t) {
                    $query .= '?,';
                    $args[] = $t;
                }
                $query = substr($query, 0, strlen($query)-1) . ')';
                $prep = $dbc->prepare($query);
                $res = $dbc->execute($prep, $args);
                if ($res && $dbc->numRows($res)) {
                    $dates = $dbc->fetchRow($res);
                    $dbc->execute($upP, array(
                        $dates['startdate'],
                        $dates['mostRecent'],
                        $card_no,
                    ));
                }
            }
        }

        /**
          Update payment plan accounts based on 
          current payment history 
        */
        $dbc->selectDB($this->config->get('OP_DB'));
        $date = new MemDatesModel($dbc);
        $plan = new EquityPaymentPlansModel($dbc);
        $plans = array();
        foreach ($plan->find() as $p) {
            $plans[$p->equityPaymentPlanID()] = $p;
        }
        $accounts = new EquityPaymentPlanAccountsModel($dbc);
        $balP = $dbc->prepare('
            SELECT payments,
                mostRecent
            FROM ' . $this->config->get('TRANS_DB') . $dbc->sep() . 'equity_history_sum
            WHERE card_no=?');
        $historyP = $dbc->prepare('
            SELECT stockPurchase,
                tdate
            FROM ' . $this->config->get('TRANS_DB') . $dbc->sep() . 'stockpurchases
            WHERE card_no=?
            ORDER BY tdate');
        foreach ($accounts->find() as $account) {
            if (!isset($plans[$account->equityPaymentPlanID()])) {
                // undefined plan
                continue;
            }
            $myplan = $plans[$account->equityPaymentPlanID()];
            $bal = $dbc->getRow($balP, array($account->cardNo()));
            if ($bal['payments'] >= $myplan->finalBalance()) {
                // account is now paid in full
                $account->lastPaymentDate($bal['mostRecent']);
                $account->nextPaymentDate(null);
                $account->nextPaymentAmount(0);
                $account->save();
            } else {
                /**
                  Payment plans are really structured into tiers. For a $20 increment, $100 total
                  plan the tiers are at $20, $40, $60, and $80. I'm not assuming any rigid 
                  enforcement of payment amounts (i.e., someone may make a payment that isn't
                  exactly $20). So after the current tier is established, I go through
                  the whole history to figure out when the tier was reached and track
                  any progress toward tier.
                */
                $payment_number = $this->numberOfPayments($myplan, $bal['payments']);
                $last_threshold_reached = $myplan->initialPayment() + (($payment_number-1)*$myplan->recurringPayment());
                $historyR = $dbc->execute($historyP, array($account->cardNo()));
                list($last_payment, $last_date, $next_payment) = $this->analyzePaymentHistory(
                    $dbc,
                    $historyR,
                    $myplan,
                    $last_threshold_reached
                );
                $account->lastPaymentDate($last_date);
                $account->lastPaymentAmount($last_payment);
                $account->nextPaymentAmount($next_payment);

                // finally, figure out the next payment due date
                // if due dates are all based on the original join date,
                // walk forward through due dates from the beginning
                $basis_date = $last_date;
                if ($myplan->dueDateBasis() == 0) {
                    $date->card_no($account->cardNo());
                    $date->load();
                    $basis_date = $date->start_date();
                    for ($i=1; $i<$payment_number-1; $i++) {
                        $basis_date = $this->getNextPaymentDate($myplan, $basis_date);
                    }
                }
                $account->nextPaymentDate($this->getNextPaymentDate($myplan, $basis_date));

                $account->save();
            }
        }
    }

    /**
      Determine how many recurring payments have been made
      @return [int] next payment
      (e.g., if 4 payments have been made this will return 5)
    */
    private function numberOfPayments($myplan, $balance)
    {
        $payment_number = 1;
        for ($i=$myplan->initialPayment(); $i<=$balance; $i+= $myplan->recurringPayment()) {
            $payment_number++;
        }

        return $payment_number-1;
    }

    /**
      Go through member's payment history in order to locate the payment
      that brought their balance to the last tier. Any further payments will
      decrease the next payment owed
      (e.g., if a member on a $20 installment plan makes a $25 payemnt, their
      next payment owed will only be $15)
    */
    private function analyzePaymentHistory($dbc, $historyR, $myplan, $last_threshold_reached)
    {
        $last_payment = 0;
        $last_date = null;
        $next_payment = $myplan->recurringPayment();
        $sum = 0;
        $reached = false;
        while ($historyW = $dbc->fetchRow($historyR)) {
            $sum += $historyW['stockPurchase'];
            if (!$reached && $sum >= $last_threshold_reached) {
                $last_date = $historyW['tdate'];
                $last_payment = $historyW['stockPurchase'];
                $reached = true;
                $next_payment -= ($last_threshold_reached-$sum);
            } elseif ($reached) {
                $next_payment -= $historyW['stockPurchase'];
            }
        }

        return array($last_payment, $last_date, $next_payment);
    }

    /**
      Calculate when the next payment is due 
      Billing cycle is stored as an integer followed by a letter
      Ex: 2M means a payment every two months
    */
    private function getNextPaymentDate($myplan, $basis_date)
    {
        $cycle = trim($myplan->billingCycle());
        $magnitude = substr($cycle, 0, strlen($cycle)-1);
        $frequency = strtoupper(substr($cycle, -1));
        $ts = strtotime($basis_date);
        switch ($frequency) {
            case 'W':
                $magnitude *= 7;
                // intentional fall through
            case 'D':
                return date('Y-m-d', mktime(0,0,0,date('n',$ts),date('j',$ts)+$magnitude,date('Y',$ts)));
                break;
            case 'M':
                return date('Y-m-d', mktime(0,0,0,date('n',$ts)+$magnitude,date('j',$ts),date('Y',$ts)));
                break;
            case 'Y':
                return date('Y-m-d', mktime(0,0,0,date('n',$ts),date('j',$ts),date('Y',$ts)+$magnitude));
                break;
        }

        return null;
    }
}

