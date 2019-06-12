<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class RpPrintOrders extends FannieRESTfulPage
{
    protected $header = '';
    protected $title = '';

    protected function post_id_handler()
    {
        $orderID = FormLib::get('orderID');
        $date = FormLib::get('date');
        $prep = $this->connection->prepare("UPDATE PurchaseOrderItems SET receivedDate=? WHERE orderID=? AND internalUPC=?");
        $this->connection->execute($prep, array($date, $orderID, $this->id));
        echo 'OK';

        return false;
    }

    protected function get_id_view()
    {
        $args = explode(',', $this->id);
        list($inStr, $args) = $this->connection->safeInClause($args);

        $prep = $this->connection->prepare("
            SELECT n.vendorName,
                i.quantity,
                i.caseSize,
                i.unitCost,
                i.description,
                r.vendorItem,
                i.sku,
                i.brand,
                i.orderID,
                i.receivedDate,
                r.upc
            FROM PurchaseOrder AS o
                INNER JOIN PurchaseOrderItems AS i ON o.orderID=i.orderID
                LEFT JOIN RpOrderItems AS r ON i.internalUPC=r.upc AND o.storeID=r.storeID
                lEFT JOIN RpOrderCategories AS c ON r.categoryID=c.rpOrderCategoryID
                LEFT JOIN vendors AS n ON o.vendorID=n.vendorID
            WHERE o.orderID IN ({$inStr})
            ORDER BY n.vendorName, i.brand, c.seq");
        $res = $this->connection->execute($prep, $args);
        $ret = '<table class="table table-bordered">
            <tr><th>Vendor</th><th>Status</th><th>Description</th><th>CS</th><th>Incoming</th></tr>';
        $orgP = $this->connection->prepare("SELECT organic FROM likeCodes WHERE likeCode=?");
        while ($row = $this->connection->fetchRow($res)) {
            $row['vendorName'] = str_replace(' (Produce)', '', $row['vendorName']);
            $suffix = '';
            $recv = '';
            if ($row['brand'] && $row['vendorName'] == 'Direct Produce') {
                $row['vendorName'] = $row['brand'];
                $suffix = ' (ea)';
                if (strpos($row['receivedDate'], ' ')) {
                    list($row['receivedDate'],) = explode(' ', $row['receivedDate']);
                }
                $recv = sprintf('<input type="text" size="2" class="form-control input-sm date-field"
                            value="%s" style="font-size: 85%%;" onchange="setRecvDate(this.value, \'%s\', %d);" />',
                    $row['receivedDate'], $row['upc'], $row['orderID']);
            }
            $organic = $this->connection->getValue($orgP, array(str_replace('LC', '', $row['upc'])));
            $ret .= sprintf('<tr class="%s"><td>%s</td><td>%s</td><td>$%.2f (%s) %s %s</td><td>%s%s</td><td>%s</td></tr>',
                $this->vendorColor($row['vendorName']),
                $row['vendorName'],
                ($organic ? 'org' : 'non-o'),
                $row['unitCost'] * $row['caseSize'],
                $row['sku'],
                $row['vendorItem'] ? $row['vendorItem'] : $row['description'],
                $row['caseSize'],
                $row['quantity'],
                $suffix,
                $recv
            );
        }
        $ret .= '</table>';

        return $ret;
    }

    protected function css_content()
    {
        return <<<CSS
@media print {
    .table .info td {
        background-color: #d9edf7 !important;
    }

    .table .danger td {
        background-color: #f2dede !important;
    }

    .table .warning td {
        background-color: #fcf8e3 !important;
    }
}
CSS;
    }

    protected function javascript_content()
    {
        return <<<JAVASCRIPT
function setRecvDate(d, upc, oID) {
    var dstr = 'id='+upc+'&orderID='+oID+'&date='+d;
    $.ajax({
        url: 'RpPrintOrders.php',
        type: 'post',
        data: dstr
    });
}
JAVASCRIPT;
    }

    private function vendorColor($v) {
        switch (strtoupper($v)) {
            case 'ALBERTS':
                return 'info';
            case 'CPW':
                return 'danger';
            case 'RDW':
                return 'warning';
            case 'UNFI':
                return '';
        }
    }
}

FannieDispatch::conditionalExec();

