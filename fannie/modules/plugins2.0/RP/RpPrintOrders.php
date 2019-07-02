<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class RpPrintOrders extends FannieRESTfulPage
{
    protected $header = '';
    protected $title = '';

    public function preprocess()
    {
        $this->addRoute('get<archive>');

        return parent::preprocess();
    }

    protected function post_id_handler()
    {
        $orderID = FormLib::get('orderID');
        $date = FormLib::get('date');
        $prep = $this->connection->prepare("UPDATE PurchaseOrderItems SET receivedDate=? WHERE orderID=? AND internalUPC=?");
        $this->connection->execute($prep, array($date, $orderID, $this->id));
        echo 'OK';

        return false;
    }

    protected function get_archive_view()
    {
        $ids = explode(',', $this->archive);
        list($inStr, $args) = $this->connection->safeInClause($ids);

        $archiveP = $this->connection->prepare("
            UPDATE PurchaseOrder
            SET placed=1, placedDate=" . $this->connection->now() . "
            WHERE orderID IN ({$inStr})");
        $this->connection->execute($archiveP, $args);

        $ret = '<div class="alert alert-success">Order(s) archived</div>';
        $prep = $this->connection->prepare("
            SELECT o.orderID, v.vendorName
            FROM PurchaseOrder AS o
                LEFT JOIN vendors AS v ON o.vendorID=v.vendorID
            WHERE o.orderID IN ({$inStr})");
        $res = $this->connection->execute($prep, $args);
        $ret .= '<ul>';
        while ($row = $this->connection->fetchRow($res)) {
            $ret .= sprintf('<li><a href="../../../purchasing/ViewPurchaseOrders.php?id=%d">#%d %s</a></li>',
                $row['orderID'], $row['orderID'], $row['vendorName']);
        }
        $ret .= '</ul>';

        return $ret;
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
        $costTotal = 0;
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
                            value="%s" style="font-size: 85%%;" data-upc="%s" data-order-id="%d" />',
                    $row['receivedDate'], $row['upc'], $row['orderID']);
            }
            $organic = $this->connection->getValue($orgP, array(str_replace('LC', '', $row['upc'])));
            $ret .= sprintf('<tr class="%s">
                <td class="vendor">%s</td><td>%s</td><td>$%.2f (%s) %s %s</td><td>%s%s</td>
                <td class="incoming">%s</td></tr>',
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
            $costTotal += ($row['quantity'] * $row['caseSize'] * $row['unitCost']);
        }
        $ret .= '</table>';
        $ret .= '<p>
            <a href="RpPrintOrders.php?archive=' . $this->id . '" class="btn btn-default">Archive Order(s)</a>
            </p>';

        $this->addOnloadCommand("\$('td.incoming input').change(function () { syncIncoming(this); });");

        return '<div class="pull-right h4">Est. Total: $' . $costTotal . '</div>' . $ret;
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
function syncIncoming(elem) {
    var myDate = elem.value;
    var farm = $(elem).closest('tr').find('td.vendor').text();
    $('td.vendor').each(function () {
        if ($(this).text() == farm) {
            var inp = $(this).closest('tr').find('td.incoming input');
            $(inp).val(myDate);
            setRecvDate(myDate, $(inp).attr("data-upc"), $(inp).attr("data-order-id"));
        }
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

