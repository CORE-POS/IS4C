<?php

/**
  Class that connects special orders
  to purchase orders
*/
class SoPoBridge
{
    /**
      Requires SQLManager, FannieConfig
    */
    public function __construct($dbc, $config)
    {
        $this->dbc = $dbc;
        $this->config = $config;
    }

    /**
      Get ID of currently open purchase order for
      the given vendor and store
    */
    private function getPurchaseOrderID($vendorID, $storeID)
    {
        $cutoff = date('Y-m-d 00:00:00', strtotime('28 days ago'));
        $prep = $this->dbc->prepare('
            SELECT orderID
            FROM ' . FannieDB::fqn('PurchaseOrder', 'op') . '
            WHERE vendorID=?
                AND storeID=?
                AND placed=0
                AND creationDate >= ?
            ORDER BY creationDate DESC');
        return $this->dbc->getValue($prep, array($vendorID, $storeID, $cutoff));
    }

    /**
      Check whether this special order line item has a valid
      SKU and thus can be added to a purchase order
    */
    public function canPurchaseOrder($soID, $transID)
    {
        $pending = $this->config->get('TRANS_DB') . $this->dbc->sep() . 'PendingSpecialOrder';
        $prep = $this->dbc->prepare('
            SELECT v.sku, n.vendorID, d.salesCode
            FROM ' . $pending . ' AS o
                INNER JOIN ' . FannieDB::fqn('vendors', 'op') . ' AS n ON LEFT(n.vendorName, LENGTH(o.mixMatch)) = o.mixMatch
                LEFT JOIN ' . FannieDB::fqn('vendorItems', 'op') . ' AS v on n.vendorID=v.vendorID AND o.upc=v.upc
                LEFT JOIN ' . FannieDB::fqn('departments', 'op') . ' AS d ON o.department=d.dept_no
            WHERE o.order_id=?
                AND o.trans_id=?
        ');

        return $this->dbc->getRow($prep, array($soID, $transID));
    }

    private function numCases($soID, $transID)
    {
        $pending = $this->config->get('TRANS_DB') . $this->dbc->sep() . 'PendingSpecialOrder';
        $prep = $this->dbc->prepare('
            SELECT ItemQtty
            FROM ' . $pending . '
            WHERE order_id=?
                AND trans_id=?');
        return $this->dbc->getValue($prep, array($soID, $transID));
    }

    /**
      Add special order line item to existing purchase order
    */
    public function addItemToPurchaseOrder($soID, $transID, $storeID)
    {
        $vendorInfo = $this->canPurchaseOrder($soID, $transID);
        if ($vendorInfo === false) {
            return false;
        }
        $poID = $this->getPurchaseOrderID($vendorInfo['vendorID'], $storeID);
        $porder = new PurchaseOrderModel($this->dbc);
        // init purchase order if necessary
        if ($poID === false) {
            $porder->vendorID($vendorInfo['vendorID']);
            $porder->storeID($storeID);
            $porder->creationDate(date('Y-m-d H:i:s'));
            $porder->vendorOrderID('SO-' . date('Ymd'));
            $poID = $porder->save();
        }

        // get number of cases
        $cases = $this->numCases($soID, $transID);

        $prep = $this->dbc->prepare('SELECT * FROM vendorItems WHERE sku=? AND vendorID=?');
        $item = $this->dbc->getRow($prep, array($vendorInfo['sku'], $vendorInfo['vendorID']));
        $pending = $this->config->get('TRANS_DB') . $this->dbc->sep() . 'PendingSpecialOrder';
        $prep = $this->dbc->prepare("SELECT description, quantity AS units, 0 AS cost, '' AS brand, upc
            FROM {$pending} WHERE order_id=? AND trans_id=?");
        $spoRow = $this->dbc->getRow($prep, array($soID, $transID));
        if ($item === false) {
            $item = $spoRow;
            $item['sku'] = is_numeric($item['upc']) ? $item['upc'] : uniqid();
        }
        $item['units'] = $spoRow['units'];

        $poSKU = substr($item['sku'], -12) . '.';
        $poitem = new PurchaseOrderItemsModel($this->dbc);
        $poitem->orderID($poID);
        $poitem->sku($poSKU);
        $poitem->salesCode($vendorInfo['salesCode']);
        $poitem->isSpecialOrder(1);
        $poitem->unitCost($item['cost']);
        $poitem->quantity($cases);
        $poitem->caseSize($item['units']);
        $poitem->brand($item['brand']);
        $poitem->description($item['description']);
        // put specialOrderID & transID into UPC to cross reference 
        // between PO and SO lines
        $poitem->internalUPC(str_pad($soID, 9, '0', STR_PAD_LEFT) . str_pad($transID, 4, '0', STR_PAD_LEFT));
        $saved = $poitem->save();

        return $saved ? $poID : false;
    }

    /**
      Find the purchase order containing this item
    */
    public function findPurchaseOrder($soID, $transID, $storeID)
    {
        $vendorInfo = $this->canPurchaseOrder($soID, $transID);
        if ($vendorInfo === false) {
            return false;
        }
        $cases = $this->numCases($soID, $transID);

        $prep = $this->dbc->prepare('
            SELECT o.orderID
            FROM ' . FannieDB::fqn('PurchaseOrder', 'op') . ' AS o
                INNER JOIN ' . FannieDB::fqn('PurchaseOrderItems', 'op') . ' AS i ON o.orderID=i.orderID
            WHERE o.vendorID=?
                AND o.storeID=?
                AND i.quantity=?
                AND i.isSpecialOrder=1
                AND i.internalUPC=?
        ');
        return $this->dbc->getValue($prep, array(
            $vendorInfo['vendorID'],
            $storeID,
            $cases,
            str_pad($soID, 9, '0', STR_PAD_LEFT) . str_pad($transID, 4, '0', STR_PAD_LEFT),
        ));
    }

    /**
      Delete a SPO item from a PO
      @param $soID [int] special order ID
      @param $transID [int] special order line item ID
      @param $storeID [int] store ID
      @return [bool] success

      An item is only deleted from a PO if it has not yet been received
    */
    public function removeItemFromPurchaseOrder($soID, $transID, $storeID)
    {
        $orderID = $this->findPurchaseOrder($soID, $transID, $storeID);
        if (!$orderID) {
            return false;
        }
        $upc = str_pad($soID, 9, '0', STR_PAD_LEFT) . str_pad($transID, 4, '0', STR_PAD_LEFT);

        $itemP = $this->dbc->prepare('SELECT * FROM ' . FannieDB::fqn('PurchaseOrderItems', 'op')
                                . ' WHERE internalUPC=? AND orderID=?');
        $item = $this->dbc->getRow($itemP, array($upc, $orderID));
        if ($item === false || $item['receivedDate'] || $item['receivedBy']) {
            return false;
        }

        $cleanP = $this->dbc->prepare("DELETE FROM " . FannieDB::fqn('PurchaseOrderItems', 'op')
                                . " WHERE sku=? AND orderID=?");
        $cleanR = $this->dbc->execute($cleanP, array($item['sku'], $orderID));

        return $cleanR ? true : false;
    }

    /**
      Update line item's status as placed, update order status too if needed
    */
    public function markAsPlaced($soID, $transID)
    {
        $table = $this->config->get('TRANS_DB') . $this->dbc->sep() . 'PendingSpecialOrder';
        $itemP = $this->dbc->prepare('
            UPDATE ' . $table . ' 
            SET memType=1
            WHERE order_id=?
                AND trans_id=?');
        $this->dbc->execute($itemP, array($soID, $transID));

        $all = $this->dbc->prepare('SELECT MIN(memType) FROM ' . $table . ' WHERE order_id=? AND trans_id > 0');
        $min = $this->dbc->execute($all, array($soID));
        if ($min == 1) {
            $table = $this->config->get('TRANS_DB') . $this->dbc->sep() . 'SpecialOrders';
            $upP = $this->dbc->prepare('UPDATE ' . $table . ' SET statusFlag=?, subStatus=? WHERE specialOrderID=?');
            $this->dbc->execute($upP, array(4, time(), $soID));
        }
    }
}

