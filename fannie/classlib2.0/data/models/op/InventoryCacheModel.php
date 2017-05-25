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
                    AND i.quantity > 0
                    AND (i.receivedQty > 0 OR i.receivedQty IS NULL)
                    AND (placedDate >= ? OR receivedDate >= ?)');
        }

        return self::$orderStmt;
    }

    public static function calculateOrdered($dbc, $upc, $storeID, $date)
    {
        $orderP = self::orderStatement($dbc);
        $ordered = $dbc->getValue($orderP, array($upc, $storeID, $date, $date));

        return $ordered ? $ordered : 0;
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
}

