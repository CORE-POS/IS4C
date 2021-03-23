<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('NcgCouponsModel')) {
    include(__DIR__ . '/NcgCouponsModel.php');
}

class NcgCouponReport extends FannieRESTfulPage
{
    protected $header = 'NCG Coupons';
    protected $title = 'NCG Coupons';

    protected function get_id_view()
    {
        $model = new NcgCouponsModel($this->connection);
        $model->couponUPC($this->id);
        $model->load();

        $dlog = DTransactionsModel::selectDlog($model->startDate(), $model->endDate());

        $basketP = $this->connection->prepare("SELECT retailTotal, memType, card_no FROM core_warehouse.transactionSummary
            WHERE date_id=? AND store_id=? AND trans_num=?");

        $prep = $this->connection->prepare("SELECT date_id, store_id, trans_num FROM {$dlog}
            WHERE tdate BETWEEN ? AND ? AND upc=?
            GROUP BY date_id, store_id, trans_num
            HAVING SUM(total) <> 0");
        $res = $this->connection->execute($prep, array($model->startDate(), $model->endDate() . ' 23:59:59', $this->id));
        $count = 0;
        $sales = 0;
        $memCount = 0;
        $memSales = 0;
        $redeemers = array();
        while ($row = $this->connection->fetchRow($res)) {
            $count++;
            $basket = $this->connection->getRow($basketP, array($row['date_id'], $row['store_id'], $row['trans_num']));
            $sales += $basket['retailTotal'];
            if ($basket['memType'] == 1 || $basket['memType'] == 3 || $basket['memType'] == 5) {
                $memCount++;
                $memSales += $basket['retailTotal'];
                $redeemers[$basket['card_no']] = true;
            }
        }

        $obj = $model->toStdClass();
        $avg = sprintf('%.2f', $sales / $count);
        $memAvg = sprintf('%.2f', $memSales / $memCount);

        $start = date('Ymd', strtotime($model->startDate()));
        $end = date('Ymd', strtotime($model->endDate()));
        list($inStr, $args) = $this->connection->safeInClause(array_keys($redeemers), array($start, $end));
        $otherP = $this->connection->prepare("SELECT AVG(retailTotal)
            FROM core_warehouse.transactionSummary
            WHERE date_id BETWEEN ? AND ?
                AND memType IN (1,3,5)
                AND retailTotal > 0
                AND card_no NOT IN ({$inStr})");
        $other = $this->connection->getValue($otherP, $args);
        $other = sprintf('%.2f', $other);
        $diff = $memAvg - $other;

        return <<<HTML
<h3>{$obj->couponUPC} - {$obj->description}</h3>
<p>
<b>Coupons</b>: {$count}<br />
<b>Sales on Coupon Transactions</b>: {$sales}<br />
<b>Average Coupon Basket</b>: {$avg}<br />
<b>Owner Sales on Coupon Transactions</b>: {$memSales}<br />
<b>Average Owner Coupon Basket</b>: {$memAvg}<br />
<b>Average Basket Other Owners</b>: {$other}<br />
<b>Basket Difference</b>: {$diff}<br />
</p>
HTML;
    }
}

FannieDispatch::conditionalExec();

