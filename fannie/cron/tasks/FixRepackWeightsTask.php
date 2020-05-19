<?php

class FixRepackWeightsTask extends FannieTask
{

    public $name = 'Fix Repack Weights';

    public $description = 'Adjust repack weights that
        couldn\t be calculated correct at ring time';

    public $default_schedule = array(
        'min' => 47,
        'hour' => 2,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        $dbc = FannieDB::get($this->config->get('TRANS_DB'));
        $date = date('Y-m-d', strtotime('yesterday'));
        $dlog = DTransactionsModel::selectDlog($date);

        $up1 = $dbc->prepare("
            UPDATE {$dlog}
            SET quantity=?,
                ItemQtty=?,
                unitPrice=?,
                regPrice=?
            WHERE tdate BETWEEN ? AND ?
                AND upc=?
                AND store_id=?
                AND store_row_id=?
                AND trans_id=?");

        $up2 = $dbc->prepare("
            UPDATE " . FannieDB::fqn('transarchive', 'trans') . "
            SET quantity=?,
                ItemQtty=?,
                unitPrice=?,
                regPrice=?
            WHERE datetime BETWEEN ? AND ?
                AND upc=?
                AND store_id=?
                AND store_row_id=?
                AND trans_id=?");

        $up3 = $dbc->prepare("
            UPDATE " . FannieDB::fqn('bigArchive', 'arch') . "
            SET quantity=?,
                ItemQtty=?,
                unitPrice=?,
                regPrice=?
            WHERE datetime BETWEEN ? AND ?
                AND upc=?
                AND store_id=?
                AND store_row_id=?
                AND trans_id=?");

        $prep = $dbc->prepare("SELECT
                d.upc, d.total, d.store_row_id, d.store_id, d.trans_id, p.normal_price, d.trans_status
            FROM {$dlog} AS d
                " . DTrans::joinProducts('d', 'p', 'INNER') . "
            WHERE d.upc LIKE '002%'
                AND p.scale = 1
                AND p.normal_price > 0
                AND d.unitPrice = d.total
                AND d.quantity IN (1, -1)
                AND d.discounttype = 0
                AND d.tdate BETWEEN ? AND ?
                AND d.trans_type='I'
            ORDER BY d.upc
            ");
        $res = $dbc->execute($prep, array($date, $date . ' 23:59:59'));
        while ($row = $dbc->fetchRow($res)) {
            $qty = $row['total'] / $row['normal_price'];
            $rounded = round($qty, 2);
            $match = round($row['normal_price']*$rounded, 2);
            if (abs($match - $row['total']) < 0.005) {
                continue;
            } 
            /*
            echo "UPC {$row['upc']}\n";
            echo "Qty is $qty\n";
            echo "Rounds to $rounded\n";
            echo "$match is {$row['total']}\n";
            echo "Row Id {$row['store_row_id']}\n";
             */
            if (abs($match - $row['total']) > 0.15) {
                $this->cronMsg('Strange repack weight encountered for ' . $date,
                    FannieLogger::ERROR);
                continue;
            } 

            $itemQtty = $row['trans_status'] == 'R' ? -1 * $rounded : $rounded;
            $args = array(
                $rounded,
                $itemQtty,
                $row['normal_price'],
                $row['normal_price'],
                $date, $date . ' 23:59:59',
                $row['upc'],
                $row['store_id'],
                $row['store_row_id'],
                $row['trans_id'],
            );
            $dbc->execute($up1, $args);
            $dbc->execute($up2, $args);
            $dbc->execute($up3, $args);
        }
    }
}

