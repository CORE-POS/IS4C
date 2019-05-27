<?php

class WfcUnfiTask extends FannieTask
{
    private $qStart = '2019-04-28';
    private $qEnd = '2019-07-28';
    private $qWeeks = 13;
    private $goal = 80001;

    public function run()
    {
        $dbc = FannieDB::get('is4c_op');

        $weekP = $dbc->prepare("
            SELECT SUM(receivedTotalCost) AS ttl
            FROM PurchaseOrder AS o
                INNER JOIN PurchaseOrderItems AS i ON o.orderID=i.orderID
            WHERE o.vendorID=1
                AND o.storeID=1
                AND o.placedDate BETWEEN ? AND ?");

        $msg = "UNFI Hillside Orders\n";
        $msg .= "Current quarter: {$this->qStart} to {$this->qEnd}\n";
        $msg .= "\n";

        $ts = strtotime($this->qStart);
        $end = strtotime($this->qEnd);
        $now = strtotime(date('Y-m-d'));
        $weeks = array();
        while ($ts < $end) {
            $args = array(
                date('Y-m-d', $ts),
                date('Y-m-d', mktime(0, 0, 0, date('n', $ts), date('j', $ts) + 6, date('Y', $ts))),
            );
            if ($ts > $now || ($ts + (6*86400)) > $now) {
                break;
            }
            $key = $args[0] . ' to ' . $args[1];
            $weeks[$key] = $dbc->getValue($weekP, $args);

            $ts = mktime(0, 0, 0, date('n', $ts), date('j', $ts) + 7, date('Y', $ts));
        }
        $total = array_sum($weeks);
        $avgSoFar = $total / count($weeks);

        $minimum = $this->goal * $this->qWeeks;
        $diff = $minimum - $total;
        $weeksLeft = $this->qWeeks - count($weeks);
        $avgLeft = $diff / $weeksLeft;

        $msg .= 'Weekly Average Goal: $' . number_format($this->goal, 2) . "\n";
        $msg .= 'Current Weekly Average: $' . number_format($avgSoFar, 2) . "\n";
        $msg .= 'Weekly Average needed to reach goal: $' . number_format($avgLeft, 2) . "\n";
        $msg .= "\n";
        $msg .= 'Minimum total purchase for current: $' . number_format($minimum, 2) . "\n";
        $msg .= "\n";
        $msg .= 'QTD total purchases: $' . number_format($total, 2) . "\n";
        $msg .= "\n";
        $msg .= "\n";
        foreach ($weeks as $key => $w) {
            $msg .= "Week {$key}    $" . number_format($w, 2) . "\n";
        }

        mail('andy@wholefoods.coop,sbroome@wholefoods.coop,lisa@wholefoods.coop,michael@wholefoods.coop,shannigan@wholefoods.coop', 'Cost+ Update', $msg, "From: costplus@wholefoods.coop\r\n");
    }
}

