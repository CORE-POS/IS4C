<?php

class OutOfStocksTask extends FannieTask
{

    public $name = 'Out of Stocks';

    public $description = 'Automatically mark items as out-of-stock or in-stock';

    public $default_schedule = array(
        'min' => 45,
        'hour' => 2,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $this->setOOS($dbc);
        $this->clearOOS($dbc);
    }

    private function clearOOS($dbc)
    {
        $res = $dbc->query("
            SELECT upc, default_vendor_id
            FROM products
            WHERE (numflag & (1 << (19-1))) <> 0
            GROUP BY upc, default_vendor_id");
        $findP = $dbc->prepare("
            SELECT i.quantity, i.receivedQty
            FROM PurchaseOrder AS o
                INNER JOIN PurchaseOrderItems AS i ON o.orderID=i.orderID
            WHERE i.internalUPC=?
                AND o.vendorID=?
                AND i.receivedQty IS NOT NULL
                AND i.quantity > 0
            ORDER BY o.placedDate DESC");
        $prodP = $dbc->prepare("
            UPDATE products
            SET numflag = numflag & ?
            WHERE upc=?
                AND default_vendor_id=?");
        while ($row = $dbc->fetchRow($res)) {
            $lastOrder = $dbc->getRow($findP, array($row['upc'], $row['default_vendor_id']));
            if ( $lastOrder['receivedQty']) {
                $dbc->execute($prodP, array(
                    ~(1 << (19 - 1)),
                    $row['upc'],
                    $row['default_vendor_id'],
                ));
            }
        }
    }

    private function setOOS($dbc)
    {
        $cutoff = date('Y-m-d', strtotime('3 days ago'));
        $prodP = $dbc->prepare("
            UPDATE products
            SET numflag = numflag | ?
            WHERE upc=?
                AND store_id=?
                AND default_vendor_id=?");

        $prep = $dbc->prepare("
            SELECT o.placedDate, i.internalUPC, o.storeID, o.vendorID
            FROM PurchaseOrder AS o
                INNER JOIN PurchaseOrderItems AS i ON o.orderID=i.orderID
            WHERE o.placedDate >= ?
                AND i.receivedQty = 0
                AND i.quantity > 0
            ORDER BY o.placedDate DESC");
        $res = $dbc->execute($prep, array($cutoff));
        while ($row = $dbc->fetchRow($res)) {
            $consecutive = $this->consecutiveOOS($dbc, $row['internalUPC'], $row['storeID'], $row['vendorID'], $row['placedDate']);
            if ($consecutive >= 2) {
                $dbc->execute($prodP, array(
                    1 << (19 - 1),
                    $row['internalUPC'],
                    $row['storeID'],
                    $row['vendorID'],
                ));
                echo $row['internalUPC'] . "\n";
            }
        }
    }

    private function consecutiveOOS($dbc, $upc, $storeID, $vendorID, $ordered)
    {
        if (!isset($this->consP)) {
            $this->consP = $dbc->prepare('
                SELECT i.quantity, i.receivedQty
                FROM PurchaseOrder AS o
                    INNER JOIN PurchaseOrderItems AS i ON o.orderID=i.orderID
                WHERE o.placedDate < ?
                    AND o.storeID=?
                    AND o.vendorID=?
                    AND i.internalUPC=?
                ORDER BY placedDate DESC');
        }
        $ret = 1;
        $res = $dbc->execute($this->consP, array($ordered, $storeID, $vendorID, $upc));
        while ($row = $dbc->fetchRow($res)) {
            if ($row['quantity'] > 0 && $row['receivedQty'] > 0) {
                break;
            }
            $ret++;
        }

        return $ret;
    }
}

