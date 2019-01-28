<?php

include(__DIR__ . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class EndCapperReport extends FannieReportPage
{
    protected $header = 'End Capper Report';
    protected $title = 'End Capper Report';

    protected $report_cache = 'none';
    protected $report_headers = array('UPC','Brand','Description','$','Qty','Rings', 'Lift%');
    protected $required_fields = array('id');

    public $discoverable = false;
    public $report_set = 'Batches';
    protected $new_tablesorter = true;

    function report_description_content()
    {
        $ret = array();
        if ($this->report_format == 'html') {
            $store = FormLib::storePicker();
            $ret[] = '<p><form action="EndCapperReport.php" method="get" class="form-inline">';
            $ret[] = "<span style=\"color:black; display:inline;\">
                    Store: {$store['html']} 
                    </span><button type=\"submit\" class=\"btn btn-default\">Change Store</button>";
            $ret[] = sprintf('<input type="hidden" name="id" value="%d" />', FormLib::get('id'));
            $ret[] = '</form></p>';
        }

        return $ret;
    }


    public function fetch_report_data()
    {
        $store = FormLib::get('store', false);
        if (!$store) {
            $store = COREPOS\Fannie\API\lib\Store::getIdByIp();
        }
        $ecID = $this->form->id;
        $prep = $this->connection->prepare('SELECT json FROM EndCaps WHERE endCapID=?');
        $json = $this->connection->getValue($prep, array($ecID));
        $json = json_decode($json, true);

        $batchP = $this->connection->prepare('
            SELECT b.batchID, l.salePrice
            FROM batches AS b
                INNER JOIN batchList AS l ON b.batchID=l.batchID
            WHERE l.upc=?
                AND ? BETWEEN b.startDate AND b.endDate
                AND ? BETWEEN b.startDate AND b.endDate
                AND b.discountType > 0
                ORDER BY l.salePrice');

        $prodP = $this->connection->prepare('SELECT department, normal_price FROM products WHERE upc=?');

        $saleLineP = $this->connection->prepare('
            SELECT b.upc
            FROM batchList AS b
                ' . DTrans::joinProducts('b', 'p', 'INNER') . '
            WHERE b.salePrice=?
                AND b.batchID=?
                AND p.department=?
                AND b.upc LIKE ?');
        $lineP = $this->connection->prepare('
            SELECT upc
            FROM products
            WHERE department=?
                AND normal_price=?
                AND upc LIKE ?');
        $liftP = $this->connection->prepare('
            SELECT SUM(saleQty) as qty, SUM(compareQty) AS comp
            FROM SalesLifts
            WHERE batchID=?
                AND upc=?
                AND storeID=?');

        $upcs = array();
        $lifts = array();
        foreach ($json['shelves'] as $shelf) {
            foreach ($shelf as $item) {
                $prefix = $item['upc'];
                if ($item['isLine']) {
                    $prefix = substr($item['upc'], 0, 8) . '%';
                }

                $info = $this->connection->getRow($prodP, array($item['upc']));
                $batchR = $this->connection->execute($batchP, array($item['upc'], $json['startDate'], $json['endDate']));
                $numRows = $this->connection->numRows($batchR);
                $found = false;
                while ($batchW = $this->connection->fetchRow($batchR)) {
                    $saleR = $this->connection->execute($saleLineP, array($batchW['salePrice'], $batchW['batchID'], $info['department'], $prefix));
                    while ($saleW = $this->connection->fetchRow($saleR)) {
                        $upcs[] = $saleW['upc'];
                        $found = true;
                        $liftW = $this->connection->getRow($liftP, array($batchW['batchID'], $saleW['upc'], $store));
                        $liftPercent = $liftW['comp'] != 0 ? (($liftW['qty'] - $liftW['comp']) / $liftW['comp']) : 999.99;
                        $lifts[$saleW['upc']] = sprintf('<a href="../../reports/BatchReport/BatchLiftReport.php?upc=%s&id=%d&store=%d">%.2f</a>',
                            $saleW['upc'], $batchW['batchID'], $store, 100*$liftPercent);
                    }
                    if ($found) {
                        break;
                    }
                }
                if ($found) continue;

                $lineR = $this->connection->execute($lineP, array($info['department'], $info['normal_price'], $prefix));
                while ($lineW = $this->connection->fetchRow($lineR)) {
                    $upcs[] = $lineW['upc'];
                }
            }
        }

        list($inStr, $args) = $this->connection->safeInClause($upcs);
        $dlog = DTransactionsModel::selectDlog($json['startDate'], $json['endDate']);
        $prep = $this->connection->prepare("
            SELECT d.upc,
                p.brand,
                p.description,
                " . DTrans::sumQuantity('d') . " AS qty,
                COUNT(*) AS rings,
                SUM(total) AS ttl
            FROM {$dlog} AS d
                " . DTrans::joinProducts('d', 'p', 'INNER') . "
            WHERE p.upc IN ({$inStr})
                AND " . DTrans::isStoreID($store, 'd') . "
                AND tdate BETWEEN ? AND ?
            GROUP BY d.upc,
                p.brand,
                p.description");
        $args[] = $store;
        $args[] = $json['startDate'];
        $args[] = $json['endDate'] . ' 23:59:59';
        $res = $this->connection->execute($prep, $args);
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $data[] = array(
                $row['upc'],
                $row['brand'],
                $row['description'],
                sprintf('%.2f', $row['ttl']),
                sprintf('%.2f', $row['qty']),
                sprintf('%.2f', $row['rings']),
                isset($lifts[$row['upc']]) ? $lifts[$row['upc']] : 'n/a',
            );
        }

        return $data;
    }

    public function calculate_footers($data)
    {
        $sums = array(0, 0, 0);
        foreach ($data as $row) {
            $sums[0] += $row[3];
            $sums[1] += $row[4];
            $sums[2] += $row[5];
        }

        return array('Total', null, null, $sums[0], $sums[1], $sums[2], '');
    }

    public function form_content()
    {
        return '<!-- intentionally blank -->';
    }
}

FannieDispatch::conditionalExec();

