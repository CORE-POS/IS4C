<?php

class SalesLiftTask extends FannieTask
{

    public $name = 'Sales Lift';

    public $description = 'Pre-calculates comparison data for sales batch performance';

    public $default_schedule = array(
        'min' => 30,
        'hour' => 1,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));

        $res = $dbc->query('SELECT batchID, startDate, endDate FROM batches WHERE discountType > 0 AND startDate > \'2018-04-01\'');
        $chkP = $dbc->prepare('SELECT batchID FROM SalesLifts WHERE batchID=?');
        $yesterday = new DateTime('2 days ago');
        while ($row = $dbc->fetchRow($res)) {
            $start = new Datetime($row['startDate']);
            $end = new DateTime($row['endDate']);
            if ($start > $yesterday) {
                // batch not started yet
                continue;
            }
            $chk = $dbc->getValue($chkP, array($row['batchID']));
            if ($chk == false || $end > $yesterday) {
                echo "Recalculating {$row['batchID']}\n";
                $this->recalculate($dbc, $row['batchID'], $start, $end);
            }
        }
    }

    private function recalculate($dbc, $batchID, $startDate, $endDate)
    {
        $span = $endDate->diff($startDate);
        $days = $span->format('%a') + 1;
        $oneDay = new DateInterval('P1D');

        $insP = $dbc->prepare('INSERT INTO SalesLifts (upc, batchID, storeID, saleDate, saleQty, saleTotal,
            compareDate, compareQty, compareTotal) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $delP = $dbc->prepare('DELETE FROM SalesLifts WHERE batchID=?');
        $dbc->execute($delP, array($batchID));

        $upcs = array();
        $upcP = $dbc->prepare('SELECT upc FROM batchList WHERE batchID=?');
        $upcR = $dbc->execute($upcP, array($batchID));
        while ($upcW = $dbc->fetchRow($upcR)) {
            $upcs[] = $upcW['upc'];
        }

        $storeP = $dbc->prepare('SELECT storeID FROM StoreBatchMap WHERE batchID=?');
        $storeR = $dbc->execute($storeP, array($batchID));
        if ($dbc->numRows($storeR) == 0) {
            $storeR = $dbc->query('SELECT 1 AS storeID');
        }
        $dbc->startTransaction();
        while ($storeW = $dbc->fetchRow($storeR)) {
            $storeID = $storeW['storeID'];
            $walk = clone $startDate;
            $compare = clone $startDate;
            $compare->sub(new DateInterval('P' . $days . 'D'));
            while ($walk <= $endDate) {
                $dlog1 = DTransactionsModel::selectDlog($walk->format('Y-m-d'));
                $dlog2 = DTransactionsModel::selectDlog($compare->format('Y-m-d'));
                $saleP = $dbc->prepare("SELECT SUM(total) AS ttl, " . DTrans::sumQuantity() . " AS qty
                    FROM {$dlog1}
                    WHERE store_id=?
                        AND upc=?
                        AND tdate BETWEEN ? AND ?");
                $compP = $dbc->prepare("SELECT SUM(total) AS ttl, " . DTrans::sumQuantity() . " AS qty
                    FROM {$dlog2}
                    WHERE store_id=?
                        AND upc=?
                        AND tdate BETWEEN ? AND ?");
                $upcP = $dbc->prepare('SELECT upc FROM batchList WHERE batchID=?');
                $upcR = $dbc->execute($upcP, array($batchID));
                foreach ($upcs as $upc) {
                    $args1 = array($storeID, $upc, $walk->format('Y-m-d 00:00:00'), $walk->format('Y-m-d 23:59:59'));
                    $args2 = array($storeID, $upc, $compare->format('Y-m-d 00:00:00'), $compare->format('Y-m-d 23:59:59'));
                    $sales = $dbc->getRow($saleP, $args1);
                    $sales = $sales ? $sales : array('qty'=>0, 'ttl'=>0);
                    $comps = $dbc->getRow($compP, $args2);
                    $comps = $comps ? $comps : array('qty'=>0, 'ttl'=>0);

                    $insArgs = array(
                        $upc,
                        $batchID,
                        $storeID,
                        $walk->format('Y-m-d'),
                        $sales['qty'],
                        $sales['ttl'],
                        $compare->format('Y-m-d'),
                        $comps['qty'],
                        $comps['ttl'],
                    );
                    $dbc->execute($insP, $insArgs);
                }

                $walk->add($oneDay);
                $compare->add($oneDay);
            }
        }
        $dbc->commitTransaction();
    }
}

