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

    public $name = 'Generate Purchase Orders';

    public $description = 'Generates orders based on inventory info';

    public $default_schedule = array(
        'min' => 45,
        'hour' => 3,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    private $silent = false;
    public function setSilent($s)
    {
        $this->silent = $s;
    }

    private $vendors = array();
    public function setVendors($v)
    {
        $this->vendors = $v;
    }

    private $store = 0;
    public function setStore($s)
    {
        $this->store = $s;
    }

    private $userID = 0;
    public function setUser($u)
    {
        $this->userID = $u;
    }

    // translate daily pars into the number of days in an
    // order cycle
    private $multiplier = 1;
    public function setMultiplier($m)
    {
        $this->multiplier = $m;
    }

    private $forecast = 0;
    public function setForecast($f)
    {
        $this->forecast = $f;
    }

    private function freshenCache($dbc)
    {
        $dbc->startTransaction();
        $items = $dbc->query('
            SELECT i.upc,
                i.storeID,
                i.count,
                i.countDate
            FROM InventoryCounts AS i
                LEFT JOIN InventoryCache AS c ON i.upc=c.upc AND i.storeID=c.storeID
            WHERE i.mostRecent=1
                AND c.upc IS NULL'); 
        $ins = $dbc->prepare('
            INSERT INTO InventoryCache
                (upc, storeID, cacheStart, cacheEnd, baseCount, ordered, sold, shrunk, onHand)
            VALUES (?, ?, ?, ?, ?, 0, 0, 0, ?)');
        while ($row = $dbc->fetchRow($items)) {
            $args = array($row['upc'], $row['storeID'], $row['countDate'], $row['countDate'], $row['count'], $row['count']);
            $dbc->execute($ins, $args);
        }
        $dbc->commitTransaction();
    }

    /**
     * Scale pars to align with a forecasted sales total
     */
    private function forecastFactor($dbc, $forecast, $vendors, $store)
    {
        if ($forecast <= 0) {
            return 0;
        }

        list($inStr, $args) = $dbc->safeInClause($vendors);
        $query = "
            SELECT SUM(CASE WHEN p.discounttype=1 THEN i.par*p.special_price ELSE i.par*p.normal_price END) AS retail
            FROM products AS p
                INNER JOIN InventoryCounts AS i ON p.upc=i.upc AND p.store_id=i.storeID AND i.mostRecent=1
            WHERE p.default_vendor_id IN ({$inStr}) ";
        if ($store) {
            $query .= " AND p.store_id=? ";
            $args[] = $store;
        }
        $prep = $dbc->prepare($query);
        $retail = $dbc->getValue($prep, $args);
        if ($retail === false) {
            return 0;
        }

        return $forecast / $retail;
    }

    public function run()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $this->freshenCache($dbc);
        $this->forecast = $this->forecastFactor($dbc, $this->forecast/$this->multiplier, $this->vendors, $this->store);

        $dbc->startTransaction();
        $curP = $dbc->prepare('SELECT onHand,cacheEnd FROM InventoryCache WHERE upc=? AND storeID=? AND baseCount >= 0');
        $catalogP = $dbc->prepare('SELECT * FROM vendorItems WHERE upc=? AND vendorID=?');
        $costP = $dbc->prepare('SELECT cost FROM products WHERE upc=? AND store_id=?');
        $prodP = $dbc->prepare('SELECT * FROM products WHERE upc=? AND store_id=?');
        $halfP = $dbc->prepare('SELECT halfCases FROM vendors WHERE vendorID=?');

        $orderIDs = array();
        $dtP = $dbc->prepare('
            SELECT ' . DTrans::sumQuantity() . '
            FROM ' . $this->config->get('TRANS_DB') . $dbc->sep() . 'dlog
            WHERE tdate > ?
                AND upc=?
                AND store_id=?
                AND trans_status <> \'R\'');
        $shP = $dbc->prepare('
            SELECT ' . DTrans::sumQuantity() . '
            FROM ' . $this->config->get('TRANS_DB') . $dbc->sep() . 'dtransactions
            WHERE datetime > ?
                AND upc=?
                AND store_id=?
                AND trans_status = \'Z\'
                AND emp_no <> 9999
                AND register_no <> 99');
        /**
          Look up all items that have a count and
          compare current [estimated] inventory to
          the par value
        */
        list($inStr, $args) = $dbc->safeInClause($this->vendors);
        if ($this->store != 0) {
            $args[] = $this->store;
        }
        $prep = $dbc->prepare('
            SELECT i.upc,
                i.storeID,
                i.par,
                p.default_vendor_id AS vid
            FROM InventoryCounts AS i
                INNER JOIN products AS p ON i.upc=p.upc AND i.storeID=p.store_id
            WHERE i.mostRecent=1
                AND p.default_vendor_id IN (' . $inStr . ')
                ' . ($this->store != 0 ? ' AND i.storeID=? ' : '') . '
            ORDER BY p.default_vendor_id, i.upc, i.storeID, i.countDate DESC');
        $res = $dbc->execute($prep, $args);
        $orders = array();
        while ($row = $dbc->fetchRow($res)) {
            $cache = $dbc->getRow($curP, array($row['upc'],$row['storeID']));
            if ($cache === false) {
                continue;
            }
            $sales = $dbc->getValue($dtP, array($cache['cacheEnd'], $row['upc'], $row['storeID']));
            $cur = $sales ? $cache['onHand'] - $sales : $cache['onHand'];
            $shrink = $dbc->getValue($shP, array($cache['cacheEnd'], $row['upc'], $row['storeID']));
            $cur = $shrink ? $cur - $shrink : $cur;
            if ($cur < 0) { 
                $cur = 0;
                $this->autoZero($dbc, $row['upc'], $row['storeID']);
            }
            if ($this->multiplier) {
                $row['par'] *= $this->multiplier;
            }
            if ($this->forecast) {
                $row['par'] *= $this->forecast;
            }
            if ($cur !== false && ($cur < $row['par'] || ($cur == 1 && $row['par'] == 1))) {
                $prodW = $dbc->getRow($prodP, array($row['upc'], $row['storeID']));
                if ($prodW === false || $prodW['inUse'] == 0) {
                    continue;
                }
                /**
                  Allocate a purchase order to hold this vendors'
                  item(s)
                */
                if (!isset($orders[$row['vid'].'-'.$row['storeID']])) {
                    $order = new PurchaseOrderModel($dbc);
                    $order->vendorID($row['vid']);
                    $order->creationDate(date('Y-m-d H:i:s'));
                    $order->storeID($row['storeID']);
                    $poID = $order->save();
                    $order->vendorOrderID('CPO-' . $poID);
                    $order->orderID($poID);
                    $order->userID($this->userID);
                    $order->save();
                    $orders[$row['vid'].'-'.$row['storeID']] = $poID;
                    $orderIDs[] = $poID;
                }
                $itemR = $dbc->getRow($catalogP, array($row['upc'], $row['vid']));

                // no catalog entry to create an order
                if ($itemR === false || $itemR['units'] <= 0) {
                    $itemR['sku'] = $row['upc'];
                    $itemR['brand'] = $prodW['brand'];
                    $itemR['description'] = $prodW['description'];
                    $itemR['cost'] = $prodW['cost'];
                    $itemR['saleCost'] = 0;
                    $itemR['size'] = $prodW['size'];
                    $itemR['units'] = 1;
                }

                /**
                  Special case: items with a par of 1 and
                  case size of 1 are slow movers. These will be 
                  ordered when on-hand *reaches* par instead of
                  when on-hand *drops below* par. Replenishment
                  then orders slightly above par depending on
                  cost.
                */
                if ($row['par'] == 1 && $itemR['units'] == 1) {
                    if ($itemR['cost'] >= 15) {
                        $row['par'] = 2;
                    } else {
                        $row['par'] = 3;
                    }
                }

                /**
                  Determine cases required to reach par again
                  and add to order
                */
                $cases = 1;
                $halves = $dbc->getValue($halfP, $row['vid']);
                $increment = $halves ? 0.5 : 1.0;
                while (($cases*$itemR['units']) + $cur < $row['par']) {
                    $cases += $increment;
                }
                $poi = new PurchaseOrderItemsModel($dbc);
                $poi->orderID($orders[$row['vid'].'-'.$row['storeID']]);
                $poi->sku($itemR['sku']);
                $poi->quantity($cases);
                $poi->unitCost($itemR['saleCost'] ? $itemR['saleCost'] : $itemR['cost']);
                if ($poi->unitCost() == 0) {
                    $cost = $dbc->getValue($costP, array($row['upc'], $row['storeID']));
                    $poi->unitCost($cost);
                }
                $poi->caseSize($itemR['units']);
                $poi->unitSize($itemR['size']);
                $poi->brand($itemR['brand']);
                $poi->description($itemR['description']);
                $poi->internalUPC($row['upc']);
                $poi->save();
            }
        }
        $dbc->commitTransaction();

        if (!$this->silent) {
            $this->sendNotifications($dbc, $orders);
        }

        return $orderIDs;
    }

    /**
      Adjust current count to zero. This happens if the current count
      claims to be negative which is impossible.
      1. Get current par
      2. Enter new count of zero with same par
      3. Reset cache to zeroes
    */
    private function autoZero($dbc, $upc, $storeID)
    {
        $parP = $dbc->prepare("SELECT par FROM InventoryCounts WHERE mostRecent=1 AND upc=? AND storeID=? ORDER BY countDate DESC");
        $par = $dbc->getValue($parP, array($upc, $storeID));

        $clearP = $dbc->prepare('UPDATE InventoryCounts SET mostRecent=0 WHERE upc=? AND storeID=?');
        $dbc->execute($clearP, array($upc, $storeID));

        $count = new InventoryCountsModel($dbc);
        $count->upc($upc);
        $count->storeID($storeID);
        $count->count(0);
        $now = date('Y-m-d H:i:s');
        $count->countDate($now);
        $count->mostRecent(1);
        $count->uid(0);
        $count->par($par);
        $count->save();

        $cacheP = $dbc->prepare("
            UPDATE InventoryCache
            SET baseCount=0,
                ordered=0,
                sold=0,
                shrunk=0,
                cacheStart=?,
                cacheEnd=?,
                onHand=0
            WHERE upc=?
                AND storeID=?");
        $dbc->execute($cacheP, array($now, $now, $upc, $storeID));
    }

    private function sendNotifications($dbc, $orders)
    {
        /**
          Fire off email notifications
        */
        $deptP = $dbc->prepare('
            SELECT e.emailAddress
            FROM PurchaseOrderItems AS i
                INNER JOIN products AS p ON p.upc=i.internalUPC
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
            $sendTo = $this->config->get('ADMIN_EMAIL');
            if (count($sendTo) > 0) {
                $msg_body = 'Created new order' . "\n";
                $msg_body .= "http://" . $this->config->get('HTTP_HOST') . '/' . $this->config->get('URL')
                    . 'purchasing/ViewPurchaseOrders.php?id=' . $oid . "\n";
                mail($sendTo, 'Generated Purchase Order', $msg_body);
            }
        }
    }
}

