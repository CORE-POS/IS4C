<?php

use COREPOS\Fannie\API\item\signage\Tags4x8P;
use COREPOS\Fannie\API\lib\Store;

require(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class PickTagsPage extends FannieRESTfulPage 
{
    protected $header = 'Pick Tags';
    protected $title = 'Pick Tags';
    public $discoverable = false;

    public function preprocess()
    {
        $this->addRoute('post<u>');
        return parent::preprocess();
    }

    protected function post_id_handler()
    {
        list($inStr, $args) = $this->connection->safeInClause($this->id);
        $map = array();
        for ($i=0; $i<count($this->id); $i++) {
            $map[$this->id[$i]] = $this->form->qty[$i];
        }

        $itemP = $this->connection->prepare("
            SELECT p.upc,
                p.brand,
                p.description,
                p.size,
                v.units,
                n.vendorName,
                v.sku
            FROM products AS p
                LEFT JOIN vendorItems AS v ON p.upc=v.upc AND p.default_vendor_id=v.vendorID
                LEFT JOIN vendors AS n ON p.default_vendor_id=n.vendorID
                LEFT JOIN departments AS d ON p.department=d.dept_no
            WHERE p.upc IN ({$inStr})
            ORDER BY n.vendorName, d.salesCode, p.brand, p.description");
        $itemR = $this->connection->execute($itemP, $args);
        $items = array();
        while ($itemW = $this->connection->fetchRow($itemR)) {
            $upc = $itemW['upc'];
            if (!isset($map[$upc])) continue;

            $item = array(
                'upc' => $upc,
                'description' => $itemW['description'],
                'posDescription' => $itemW['description'],
                'brand' => $itemW['brand'],
                'normal_price' => 0,
                'units' => $itemW['units'],
                'size' => $itemW['size'],
                'sku' => $itemW['sku'],
                'vendor' => $itemW['vendorName'],
                'scale' => 0,
                'numflag' => 0,
                'pricePerUnit' => '',
            );
            for ($i=0; $i<$map[$upc]; $i++) {
                $item['normal_price'] = ($i+1) . '/' . $map[$upc];
                $items[] = $item;
            }
            unset($map[$upc]);
        }

        $orderItems = $this->ordersToItems();
        if (count($orderItems) > 0) {
            $items = array_merge($items, $orderItems);
        }

        $pdf = new Tags4x8P($items, 'provided');
        $pdf->drawPDF();

        return false;
    }

    protected function post_handler()
    {
        $orderItems = $this->ordersToItems();
        $pdf = new Tags4x8P($orderItems, 'provided');
        $pdf->drawPDF();

        return false;

    }

    private function ordersToItems()
    {
        $orders = FormLib::get('oid', array());
        list($inStr, $args) = $this->connection->safeInClause($orders);
        $items = array();
        $itemP = $this->connection->prepare("
            SELECT *
            FROM PurchaseOrderItems AS i
                INNER JOIN PurchaseOrder AS p ON i.orderID=p.orderID
                LEFT JOIN vendors AS n ON p.vendorID=n.vendorID
            WHERE i.orderID IN ({$inStr})
            ORDER BY i.orderID, i.salesCode, i.brand, i.description");
        $res = $this->connection->execute($itemP, $args);
        while ($row = $this->connection->fetchRow($res)) {
            $item = array(
                'upc' => $row['internalUPC'],
                'description' => $row['description'],
                'posDescription' => $row['description'],
                'brand' => $row['brand'],
                'normal_price' => 0,
                'units' => $row['caseSize'],
                'size' => $row['unitSize'],
                'sku' => $row['sku'],
                'vendor' => $row['vendorName'],
                'scale' => 0,
                'numflag' => 0,
                'pricePerUnit' => '',
            );
            for ($i=0; $i<$row['quantity']; $i++) {
                $item['normal_price'] = ($i+1) . '/' . $row['quantity'];
                if ($row['isSpecialOrder']) {
                    $item['normal_price'] = 'SO';
                }
                $items[] = $item;
            }
        }

        return $items;
    }

    protected function post_u_view()
    {
        list($inStr, $args) = $this->connection->safeInClause($this->u);
        $simpP = $this->connection->prepare("
            SELECT upc, brand, description
            FROM products
            WHERE upc IN ({$inStr})
            GROUP BY upc, brand, description
            ORDER BY upc, brand, description");
        $simpR = $this->connection->execute($simpP, $args);
        $tableBody = '';
        while ($row = $this->connection->fetchRow($simpR)) {
            $tableBody .= sprintf('<tr>
                <td><input type="hidden" name="id[]" value="%s" />
                <input type="number" class="form-control" name="qty[]" value="1" /></td>
                <td>%s</td><td>%s</td><td>%s</td></tr>',
                $row['upc'], $row['upc'], $row['brand'], $row['description']);
        }

        $poList = $this->purchaseOrderList();
        $noPOs = $poList == '' ? 'collapse' : '';

        return <<<HTML
<form method="post">
    <p><button type="submit" class="btn btn-default">Get Tags</button></p>
    <table class="table table-bordered table-striped">
        <tr><th>Qty</th><th>UPC</th><th>Brand</th><th>Description</th></tr>
        {$tableBody}
    </table>
    <p><button type="submit" class="btn btn-default {$noPOs}">Get Tags</button></p>
    {$poList}
    <p><button type="submit" class="btn btn-default">Get Tags</button></p>
</form>
HTML;
    }

    protected function purchaseOrderList()
    {
        $store = Store::getIdByIp(0);
        $prep = $this->connection->prepare("
            SELECT p.orderID,
                p.placedDate,
                n.vendorName
            FROM PurchaseOrder AS p
                LEFT JOIN vendors AS n ON p.vendorID=n.vendorID 
                INNER JOIN PurchaseOrderItems AS i ON p.orderID=i.orderID
            WHERE p.placed=1
                AND p.storeID=?
                AND p.placedDate > ?
                AND (p.vendorInvoiceID NOT LIKE 'XFER-%' OR p.vendorInvoiceID IS NULL)
            GROUP BY p.orderID, p.placedDate, n.vendorName
            HAVING MAX(i.receivedQty) IS NULL
            ORDER BY p.placedDate DESC
        ");
        $res = $this->connection->execute($prep, array($store, date('Y-m-d', strtotime('30 days ago'))));
        $ret = '';
        while ($row = $this->connection->fetchRow($res)) {
            $ret .= sprintf('<p>
                <input type="checkbox" name="oid[]" value="%d" />
                <a href="../../purchasing/ViewPurchaseOrders.php?id=%d">%s %s</a>
                </p>',
                $row['orderID'], $row['orderID'], $row['placedDate'], $row['vendorName']);
        }

        return $ret;
    }

    protected function get_view()
    {
        $poList = $this->purchaseOrderList();
        return <<<HTML
<form method="post" action="PickTagsPage.php">
    <p><button type="submit" class="btn btn-default">Get Tags</button></p>
    {$poList}
    <p><button type="submit" class="btn btn-default">Get Tags</button></p>
</form>
HTML;
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertInternalType('string', $this->get_view());
        $this->u = array('0000000000111');
        $phpunit->assertInternalType('string', $this->post_u_view());
        $phpunit->assertInternalType('array', $this->ordersToItems());
    }
}

FannieDispatch::conditionalExec();

