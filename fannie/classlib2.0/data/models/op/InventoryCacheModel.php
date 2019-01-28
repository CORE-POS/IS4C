<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

    This file is part of CORE-POS.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/**
  @class InventoryCacheModel
*/
class InventoryCacheModel extends BasicModel
{
    protected $name = "InventoryCache";
    protected $preferred_db = 'op';

    protected $columns = array(
    'upc' => array('type'=>'VARCHAR(13)', 'primary_key'=>true),
    'storeID' => array('type'=>'INT', 'primary_key'=>true),
    'cacheStart' => array('type'=>'DATETIME'),
    'cacheEnd' => array('type'=>'DATETIME'),
    'baseCount' => array('type'=>'DOUBLE', 'default'=>0),
    'ordered' => array('type'=>'DECIMAL(10,2)'),
    'sold' => array('type'=>'DECIMAL(10,2)'),
    'shrunk' => array('type'=>'DECIMAL(10,2)', 'default'=>0),
    'onHand' => array('type'=>'DECIMAL(10,2)'),
    );

    public function __construct($con)
    {
        // change uniqueness constraint in HQ mode
        if (FannieConfig::config('STORE_MODE') == 'HQ') {
            $this->unique = array('upc', 'storeID');
        }
        parent::__construct($con);
    }

    private static $orderStmt = null;
    private static function orderStatement($dbc)
    {
        if (self::$orderStmt === null) {
            self::$orderStmt = $dbc->prepare('
                SELECT 
                    SUM(CASE WHEN receivedQty IS NULL THEN caseSize*quantity ELSE receivedQty END) AS qty
                FROM PurchaseOrderItems AS i
                    INNER JOIN PurchaseOrder AS o ON i.orderID=o.orderID
                WHERE internalUPC=?
                    AND placedDate IS NOT NULL
                    AND storeID=?
                    AND i.isSpecialOrder = 0
                    AND o.inventoryIgnore = 0
                    AND (placedDate >= ? OR receivedDate >= ?)');
            /** Allow negative quantities?
                    AND (i.quantity > 0 OR (i.receivedQty IS NOT NULL AND i.receivedQty > 0))
                    AND (i.receivedQty > 0 OR i.receivedQty IS NULL)
             */
        }

        return self::$orderStmt;
    }

    /**
     * Lookup un-received line items within orders that have been received
     * The item's "ordered" quantity is incremented immediately when an order
     * is placed so inventory is aware of in-transit orders. However, if the
     * receiving process scans items anything that was out of stock won't
     * get scanned. Once some items in a given order have been received it
     * makes sense to conclude anything NOT received in the same order is
     * not actually present and should not count toward inventory's
     * "ordered" quantity.
     */
    private static function reduceUnReceived($dbc, $upc, $storeID, $date)
    {
        $receivedP = $dbc->prepare('SELECT receivedQty FROM PurchaseOrderItems WHERE orderID=? AND receivedQty IS NOT NULL');
        $reduction = 0;
        $getP = $dbc->prepare('SELECT i.orderID, SUM(caseSize*quantity) AS ordered
                FROM PurchaseOrderItems AS i
                    INNER JOIN PurchaseOrder AS o ON i.orderID=o.orderID
                WHERE internalUPC=?
                    AND storeID=?
                    AND i.isSpecialOrder = 0
                    AND o.inventoryIgnore = 0
                    AND i.receivedQty IS NULL
                    AND placedDate >= ?
                GROUP BY i.orderID');
        $getR = $dbc->execute($getP, array($upc, $storeID, $date));
        while ($getW = $dbc->fetchRow($getR)) {
            $received = $dbc->getValue($receivedP, array($getW['orderID']));
            if ($received !== false) {
                $reduction += $getW['ordered'];
            }
        }

        return $reduction;
    }

    public static function calculateOrdered($dbc, $upc, $storeID, $date)
    {
        $orderP = self::orderStatement($dbc);
        $ordered = $dbc->getValue($orderP, array($upc, $storeID, $date, $date));
        $ordered -= self::reduceUnReceived($dbc, $upc, $storeID, $date);

        return $ordered >= 0 ? $ordered : 0;
    }

    public function recalculateOrdered($upc, $storeID)
    {
        $obj = new InventoryCacheModel($this->connection);
        $obj->upc($upc);
        $obj->storeID($storeID);
        if ($obj->load()) {
            $orders = InventoryCacheModel::calculateOrdered($this->connection, $upc, $storeID, $obj->cacheStart());
            $obj->ordered($orders);
            $obj->onHand($obj->baseCount() + $obj->ordered() - $obj->sold() - $obj->shrunk());
            $obj->save();
        }
    }

    public function doc()
    {
        return '
Use:
InventoryCache stores a snapshot of inventory activity to
avoid real-time calculation across large chunks of data.
* baseCount is the amount present at the last count
* ordered is the amount ordered since the last count
  and can be re-calculated from purchase orders
* sold is the amount sold since the last count
  and can be re-calculated from transaction data
* shrunk is the amount shrunk since the last count
  and can be re-calculated from transaction data
* onHand is baseCount + ordered - sold - shrunk
            ';
    }
}

