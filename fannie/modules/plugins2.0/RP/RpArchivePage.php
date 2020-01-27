<?php include(__DIR__ . '/../../../config.php'); if (!class_exists('FannieAPI')) { include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class RpArchivePage extends FannieRESTfulPage
{
    protected $header = 'Archived Orders';
    protected $title = 'Archived Orders';

    protected function get_view()
    {
        $store = FormLib::get('store');
        if (!$store) {
            $store = COREPOS\Fannie\API\lib\Store::getIdByIp();
        }

        $prep = $this->connection->prepare("
            SELECT i.brand, i.description, o.placedDate, i.receivedDate, i.quantity
            FROM PurchaseOrder AS o
                INNER JOIN PurchaseOrderItems AS i ON o.orderID=i.orderID
            WHERE o.userID=-99
                AND o.vendorID=-2
                AND o.storeID=?
                AND
                    (o.placedDate >= CURDATE()
                    OR
                    i.receivedDate >= CURDATE()
                )
            ORDER BY i.receivedDate");
        $res = $this->connection->execute($prep, array($store));
        $table = '';
        while ($row = $this->connection->fetchRow($res)) {
            $table .= sprintf('<tr><td>%s</td><td>%s</td><td>%.2f</td><td>%s</td><td>%s</td></tr>',
                $row['brand'],
                $row['description'],
                $row['quantity'],
                $row['placedDate'],
                $row['receivedDate']
            );
        }

        return <<<HTML
<table class="table table-bordered">
    <tr><th>Vendor</th><th>Item</th><th>Quantity</th><th>Ordered</th><th>Est. Delivery</th></tr>
    {$table}
</table>
HTML;
    }
}

FannieDispatch::conditionalExec();

