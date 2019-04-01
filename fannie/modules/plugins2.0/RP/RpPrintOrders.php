<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class RpPrintOrders extends FannieRESTfulPage
{
    protected $header = '';
    protected $title = '';

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
                r.upc
            FROM PurchaseOrder AS o
                INNER JOIN PurchaseOrderItems AS i ON o.orderID=i.orderID
                LEFT JOIN RpOrderItems AS r ON i.internalUPC=r.upc AND o.storeID=r.storeID
                LEFT JOIN RpOrderCategories AS c ON r.categoryID=c.rpOrderCategoryID
                LEFT JOIN vendors AS n ON o.vendorID=n.vendorID
            WHERE o.orderID IN ({$inStr})
            ORDER BY n.vendorName, c.seq");
        $res = $this->connection->execute($prep, $args);
        $ret = '<table class="table table-bordered">
            <tr><th>Vendor</th><th>Status</th><th>Description</th><th>CS</th></tr>';
        $orgP = $this->connection->prepare("SELECT organic FROM likeCodes WHERE likeCode=?");
        while ($row = $this->connection->fetchRow($res)) {
            $row['vendorName'] = str_replace(' (Produce)', '', $row['vendorName']);
            $organic = $this->connection->getValue($orgP, array(str_replace('LC', '', $row['upc'])));
            $ret .= sprintf('<tr class="%s"><td>%s</td><td>%s</td><td>$%.2f (%s) %s %s</td><td>%s</td></tr>',
                $this->vendorColor($row['vendorName']),
                $row['vendorName'],
                ($organic ? 'org' : 'non-o'),
                $row['unitCost'] * $row['caseSize'],
                $row['sku'],
                $row['vendorItem'] ? $row['vendorItem'] : $row['description'],
                $row['caseSize'],
                $row['quantity']
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

