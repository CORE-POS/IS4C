<?php

include(__DIR__ . '/../../../config.php');

if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class RpArchivePage extends FannieReportPage
{
    protected $header = 'Archived Orders';
    protected $title = 'Archived Orders';
    protected $new_tablesorter = true;
    protected $required_fields = array('date1', 'date2');
    protected $report_headers = array('Farm', 'Item', 'Quantity', 'Ordered', 'Delivery', 'Purchase Order');

    public function fetch_report_data()
    {
        try {
            $date1 = $this->form->date1;
            $date2 = $this->form->date2;
        } catch (Exception $ex) {
            return array();
        }
        $store = FormLib::get('store');
        if (!$store) {
            $store = COREPOS\Fannie\API\lib\Store::getIdByIp();
        }

        $prep = $this->connection->prepare("
            SELECT i.brand, i.description, o.placedDate, i.receivedDate, i.quantity, i.orderID
            FROM PurchaseOrder AS o
                INNER JOIN PurchaseOrderItems AS i ON o.orderID=i.orderID
            WHERE o.userID=-99
                AND o.vendorID=-2
                AND o.storeID=?
                AND i.receivedDate BETWEEN ? AND ?
            ORDER BY i.receivedDate");
        $res = $this->connection->execute($prep, array($store, $date1, $date2));
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $link = sprintf('<a href="../../../purchasing/ViewPurchaseOrders.php?id=%d">%d</a>',
                $row['orderID'], $row['orderID']);
            $data[] = array(
                $row['brand'],
                $row['description'],
                $row['quantity'],
                $row['placedDate'],
                $row['receivedDate'],
                $link
            );
        }

        return $data;
    }

    public function form_content()
    {
        $dates = FormLib::standardDateFields();
        $stores = FormLib::storePicker();

        $start = strtotime('last monday');
        if (date('N') == 1) {
            $start = time();
        }
        $end = mktime(0, 0, 0, date('n', $start), date('j', $start) + 7, date('Y', $start));
        $sDate = date('Y-m-d', $start);
        $eDate = date('Y-m-d', $end);
        // only default populate if there aren't form values provided
        if (FormLib::get('json', false) === false) {
            $this->addOnloadCommand("\$('#date1').val('{$sDate}');");
            $this->addOnloadCommand("\$('#date2').val('{$eDate}');");
        }

        return <<<HTML
<form method="get" action="RpArchivePage.php">
    {$dates}
    <div class="row"></div>
    <div class="form-group">
        <label>Store</label>
        {$stores['html']}
    </div>
    <div class="form-group">
        <button class="btn btn-default">Get Archived Orders</button>
    </div>
HTML;
    }
}

FannieDispatch::conditionalExec();

