<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

class OrderGenTask extends FannieTask
{

    public $name = 'Auto Order';

    public $description = 'Generates orders based on inventory info';

    public $default_schedule = array(
        'min' => 45,
        'hour' => 3,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $curP = $dbc->prepare('SELECT onHand FROM InventoryCache WHERE upc=? AND storeID=?');
        $catalogP = $dbc->prepare('SELECT * FROM vendorItems WHERE upc=? AND vendorID=?');
        /**
          Look up all items that have a count and
          compare current [estimated] inventory to
          the par value
        */
        $prep = $dbc->prepare('
            SELECT i.upc
                i.storeID,
                i.par,
                p.default_vendor_id AS vid
            FROM InventoryCounts AS i
                INNER JOIN products AS p ON i.upc=p.upc AND i.storeID=p.store_id
            WHERE i.mostRecent=1
            ORDER BY p.defaultVendorID, i.upc, i.storeID, i.countDate DESC');
        $res = $dbc->execute($prep);
        $orders = array();
        while ($row = $dbc->fetchRow($res)) {
            $cur = $dbc->getValue($curP, array($row['upc'],$row['storeID']));
            if ($cur !== false && $cur < $row['par']) {
                /**
                  Allocate a purchase order to hold this vendors'
                  item(s)
                */
                if (!isset($orders[$row['vid']])) {
                    $po = new PurchaseOrderModel($dbc);
                    $po->vendorID($row['vid']);
                    $po->creationDate(date('Y-m-d H:i:s'));
                    $poID = $po->save();
                    $orders[$row['vid']] = $poID;
                }
                $itemR = $dbc->getRow($catalogP, array($row['upc'], $row['vid']));
                // no catalog entry to create an order
                if ($itemR === false || $itemR['units'] <= 0) {
                    continue;
                }

                /**
                  Determine cases required to reach par again
                  and add to order
                */
                $cases = 1;
                while (($cases*$itemR['units']) + $cur < $row['par']) {
                    $cases++;
                }
                $poi = new PurhcaseOrderItemsModel($dbc);
                $poi->orderID($orders[$row['vid']]);
                $poi->sku($itemR['sku']);
                $poi->quantity($cases);
                $poi->unitCost($itemR['saleCost'] ? $itemR['saleCost'] : $itemR['cost']);
                $poi->caseSize($itemR['units']);
                $poi->unitSize($itemR['size']);
                $poi->brand($itemR['brand']);
                $poi->description($itemR['description']);
                $poi->internalUPC($row['upc']);
                $poi->save();
            }
        }

        /**
          Fire off email notifications
        */
        $deptP = $dbc->prepare('
            SELECT e.emailAddress
            FROM PurchaseOrderItems AS i
                INNER JOIN products AS p ON p.upc=i.upc
                INNER JOIN superdepts AS s ON p.department=s.dept_ID
                INNER JOIN superDeptEmails AS e ON s.superID=e.superID
            WHERE orderID=?
            GROUP BY e.emailAddress');
        foreach ($orders as $oid) {
            $sendTo = array();
            $deptR = $dbc->execute($deptP, array($oid));
            while ($deptW = $dbc->fetchRow($deptR)) {
                $sendTo[] = $deptW['emailAddress'];
            }
            if (count($sendTo) > 0) {
                $msg_body = 'Created new order' . "\n";
                $msg_body .= "http://" . $_SERVER['SERVER_NAME'] . '/' . $this->config->get('URL')
                    . 'purchasing/ViewPurchaseOrders.php?id=' . $oid . "\n";
                mail($sendTo, 'Generated Purchase Order', $msg_body);
            }
        }
    }
}

