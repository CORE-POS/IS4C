<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('RpSessionsModel')) {
    include(__DIR__ . '/models/RpSessionsModel.php');
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
        $brand = FormLib::get('brand');
        $prep = $this->connection->prepare("UPDATE PurchaseOrderItems SET receivedDate=? WHERE orderID=? AND internalUPC=? AND brand=?");
        $this->connection->execute($prep, array($date, $orderID, $this->id, $brand));
        echo 'OK';

        return false;
    }

    protected function get_archive_handler()
    {
        if ($_SESSION['rpState']) {
            $_SESSION['rpState']['directAmt'] = array();
            $json = json_encode($_SESSION['rpState']);
            $model = new RpSessionsModel($this->connection);
            $model->userID(FannieAuth::getUID($this->current_user));
            $model->data($json);
            $model->save();
        }
        return true;
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
                r.upc,
                i.internalUPC,
                c.seq
            FROM PurchaseOrder AS o
                INNER JOIN PurchaseOrderItems AS i ON o.orderID=i.orderID
                LEFT JOIN RpOrderItems AS r ON i.internalUPC=LEFT(r.upc, 13) AND o.storeID=r.storeID
                lEFT JOIN RpOrderCategories AS c ON r.categoryID=c.rpOrderCategoryID
                LEFT JOIN vendors AS n ON o.vendorID=n.vendorID
            WHERE o.orderID IN ({$inStr})
            ORDER BY n.vendorName, CASE WHEN o.vendorID=-2 THEN i.brand ELSE '' END, c.seq");
        $res = $this->connection->execute($prep, $args);
        $ret = '<table class="table table-bordered">
            <tr><th>Vendor</th><th>Status</th><th>Description</th><th>CS</th><th>Incoming</th></tr>';
        $orgP = $this->connection->prepare("SELECT organic FROM likeCodes WHERE likeCode=?");
        $costTotal = 0;
        $mapP = $this->connection->prepare("SELECT description FROM RpFixedMaps AS r INNER JOIN vendorItems AS v
            ON r.sku=v.sku AND r.vendorID=v.vendorID WHERE r.likeCode=?");
        $lcP = $this->connection->prepare("SELECT likeCodeDesc FROM likeCodes WHERE likeCode=?");
        $copyPaste = array();
        while ($row = $this->connection->fetchRow($res)) {
            $row['vendorName'] = str_replace(' (Produce)', '', $row['vendorName']);
            $suffix = '';
            $recv = '';
            $likecode = str_replace('LC', '', $row['upc']);
            if (strstr($likecode, '-')) {
                list($likecode, $rest) = explode('-', $likecode, 2);
            }
            $organic = $this->connection->getValue($orgP, array(str_replace('LC', '', $row['upc'])));
            $map = $this->connection->getValue($mapP, array(str_replace('LC', '', $row['upc'])));
            $lcName = $this->connection->getValue($lcP, array($likecode));
            if ($row['brand'] && $row['vendorName'] == 'Direct Produce') {
                $row['vendorName'] = $row['brand'];
                $suffix = ' (ea)';
                if (strpos($row['receivedDate'], ' ')) {
                    list($row['receivedDate'],) = explode(' ', $row['receivedDate']);
                }
                $recv = sprintf('<input type="text" size="2" class="form-control input-sm date-field"
                            value="%s" style="font-size: 85%%;" data-upc="%s" data-order-id="%d" 
                            data-brand="%s" />',
                    $row['receivedDate'], $row['upc'], $row['orderID'], $row['brand']);
                if (!isset($copyPaste[$row['brand']])) {
                    $copyPaste[$row['brand']] = '';
                }
                $copyPaste[$row['brand']] .= $row['quantity'] . "\t" . $lcName . "\n";
            }
            if ($map) {
                $row['vendorItem'] = $map;
            }
            $ret .= sprintf('<tr class="%s">
                <td class="vendor">%s</td><td>%s</td><td>%s $%.2f (%s) %s %s</td><td>%s%s</td>
                <td class="incoming">%s</td></tr>',
                $this->vendorColor($row['vendorName']),
                $row['vendorName'],
                ($organic ? 'org' : 'non-o'),
                $lcName,
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

        if (count($copyPaste) > 0) {
            $ret .= '<hr />';
            foreach ($copyPaste as $farm => $msg) {
                $ret .= '<b>' . $farm . '</b><br />';
                $ret .= '<pre>' . $msg . '</pre>';
                $ret .= '<hr />';
            }
        }

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
    body {
        -webkit-print-color-adjust: exact;
    }
}
CSS;
    }

    protected function javascript_content()
    {
        return <<<JAVASCRIPT
function setRecvDate(d, upc, oID, brand) {
    var dstr = 'id='+upc+'&orderID='+oID+'&date='+d+'&brand='+encodeURIComponent(brand);
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
            setRecvDate(myDate, $(inp).attr("data-upc"), $(inp).attr("data-order-id"), $(inp).attr('data-brand'));
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

