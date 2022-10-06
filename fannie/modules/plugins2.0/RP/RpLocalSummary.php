<?php

include(__DIR__ . '/../../../config.php');

if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class RpLocalSummary extends FannieReportPage
{
    protected $header = 'Direct Ordering Summary';
    protected $title = 'Direct Ordering Summary';

    protected $new_tablesorter = true;
    protected $report_headers = array('LC', 'Item', 'Farm', 'Qty', '%');
    protected $required_fields = array('date');

    public function fetch_report_data()
    {
        $store = FormLib::get('store');
        $query = "SELECT internalUPC, brand, description, sum(quantity) AS qty 
            FROM PurchaseOrder as o
                INNER JOIN PurchaseOrderItems AS i on o.orderID=i.orderID
            WHERE creationDate >= ?
                AND vendorID=-2 "
                . ($store ? " AND storeID=? " : '') .
                " AND receivedDate IS NOT NULL
            GROUP BY internalUPC, brand, description";
        $args = array($this->form->date);
        if ($store) {
            $args[] = $store;
        }
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, $args);
        $lcs = array();
        while ($row = $this->connection->fetchRow($res)) {
            $lc = $row['internalUPC'];
            if (!isset($lcs[$lc])) {
                $lcs[$lc] = array(
                    'item' => $row['description'],
                    'total' => 0,
                    'farms' => array(),
                );
            }
            $lcs[$lc]['total'] += $row['qty'];
            if (!isset($lcs[$lc]['farms'][$row['brand']])) {
                $lcs[$lc]['farms'][$row['brand']] = 0;
            }
            $lcs[$lc]['farms'][$row['brand']] += $row['qty'];
        }
        $data = array();
        foreach ($lcs as $lc => $info) {
            foreach ($info['farms'] as $farm => $qty) {
                $data[] = array(
                    $lc,
                    $info['item'],
                    $farm,
                    $qty,
                    sprintf('%.2f%%', $qty / $info['total'] * 100),
                );
            }
        }

        return $data;
    }

    public function form_content()
    {
        $stores = FormLib::storePicker();
        return <<<HTML
<form method="get" action="RpLocalSummary.php">
    <div class="form-group">
        <label>Orders since</label>
        <input type="text" class="form-control date-field" name="date" />
    </div>
    <div class="form-group">
        <label>Store</label>
        {$stores['html']}
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Get Report</button>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

