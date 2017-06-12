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

class InventoryTask extends FannieTask
{

    public $name = 'Inventory (Approximate)';

    public $description = 'Tries to calculate how much inventory is present
    based on recent sales & orders.';

    public $default_schedule = array(
        'min' => 45,
        'hour' => 2,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    private $store_id = 0;
    private $vendor_id = 0;

    public function setStoreID($s)
    {
        $this->store_id = $s;
    }

    public function setVendorID($v)
    {
        $this->vendor_id = $v;
    }

    /**
      Normal nightly behavior is to clear the whole cache and
      recalculate everything. But if store and vendor IDs have 
      been specified then only those applicable entries are
      cleared
    */
    private function clearEntries($dbc, $store_id, $vendor_id)
    {
        if ($store_id && $vendor_id) {
            $prep = $dbc->prepare('
                DELETE FROM InventoryCache
                WHERE storeID=?
                    AND upc IN (
                    SELECT upc FROM products WHERE store_id=? AND default_vendor_id=?
                    )');
            $dbc->execute($prep, array($store_id, $store_id, $vendor_id));
        } else {
            $dbc->query('TRUNCATE TABLE InventoryCache');
        }
    }

    /**
      Normal nightly behavior is to get all base counts and
      rebuild cache but if store and vendor IDs have been
      specified only those entires are recalculated
    */
    private function getCounts($dbc, $store_id, $vendor_id)
    {
        $countQ = '
            SELECT i.upc,
                storeID,
                count,
                countDate,
                par
            FROM InventoryCounts AS i ';
        $countArgs = array();
        if ($store_id && $vendor_id) {
            $countQ .= '
                INNER JOIN products AS p ON p.upc=i.upc AND p.store_id=i.storeID
                WHERE mostRecent=1 
                    AND i.storeID=?
                    AND p.default_vendor_id=?
                ORDER BY countDate DESC';
            $countArgs[] = $store_id;
            $countArgs[] = $vendor_id;
        } else {
            $countQ .= ' WHERE mostRecent=1
                ORDER BY countDate DESC';
        }
        $countP = $dbc->prepare($countQ);

        return $dbc->execute($countP, $countArgs);
    }

    public function run()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $this->clearEntries($dbc, $this->store_id, $this->vendor_id);

        $dbc->startTransaction();
        $insP = $dbc->prepare('
            INSERT INTO InventoryCache
                (upc, storeID, cacheStart, cacheEnd, baseCount, ordered, sold, shrunk)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?)');

        $countR = $this->getCounts($dbc, $this->store_id, $this->vendor_id);
        $last = array(false, false);
        while ($row = $dbc->fetchRow($countR)) {
            if ($last[0] == $row['upc'] && $last[1] == $row['storeID']) {
                continue;
            }
            $last = array($row['upc'], $row['storeID'], $row['countDate']);
            $sales = 0;
            $sales += $this->getSales($dbc, $last);
            $aliases = COREPOS\Fannie\API\item\InventoryLib::getAliases($dbc, $row['upc']);
            foreach ($aliases as $alias) {
                $aliasSales = $this->getSales($dbc, array($alias['upc'], $row['storeID'], $row['countDate']));
                $sales += ($alias['multiplier'] * $aliasSales);
            }

            $orders = InventoryCacheModel::calculateOrdered($dbc, $row['upc'], $row['storeID'], $row['countDate']);

            $dtrans = DTransactionsModel::selectDTrans($row['countDate'], date('Y-m-d', strtotime('yesterday')));
            $shrinkP = $dbc->prepare('
                SELECT d.upc,
                    d.store_id,
                    ' . DTrans::sumQuantity('d') . ' AS qty
                FROM ' . $dtrans . ' AS d
                WHERE d.trans_status = \'Z\'
                    AND ' . DTrans::isNotTesting('d') . '
                    AND d.upc=?
                    AND d.store_id=?
                    AND d.datetime >= ?
                GROUP BY d.upc,
                    d.store_id');
            $shrink = $dbc->getRow($shrinkP, $last);
            $shrink = $shrink ? $shrink['qty'] : 0;

            $args = array(
                $row['upc'],
                $row['storeID'],
                $row['countDate'],
                date('Y-m-d 23:59:59', strtotime('yesterday')),
                $row['count'],
                $orders,
                $sales,
                $shrink,
            );
            $insR = $dbc->execute($insP, $args);
        }
        $dbc->commitTransaction();

        $dbc->query('
            UPDATE InventoryCache
            SET onHand = baseCount + ordered - sold - shrunk
        ');

        $this->trimCounts($dbc, $this->store_id, $this->vendor_id);
    }

    // trim the backlog of count data
    // don't keep more than a 3 count history per item
    private function trimCounts($dbc, $store_id, $vendor_id)
    {
        if ($store_id && $vendor_id) {
            return true;
        }

        $dbc->startTransaction();
        $clearR = $dbc->query("SELECT upc, storeID FROM InventoryCounts GROUP BY upc, storeID HAVING COUNT(*) > 3");
        $getP = $dbc->prepare("SELECT inventoryCountID, mostRecent FROM InventoryCounts WHERE upc=? AND storeID=? ORDER BY countDate DESC");
        $delP = $dbc->prepare("DELETE FROM InventoryCounts WHERE inventoryCountID=?");
        while ($clearW = $dbc->fetchRow($clearR)) {
            $args = array($clearW['upc'], $clearW['storeID']);
            $counter = 1;
            $res = $dbc->execute($getP, $args);
            while ($row = $dbc->fetchRow($res)) {
                if ($counter > 3 && $row['mostRecent'] != 1) {
                    $dbc->execute($delP, array($row['inventoryCountID']));
                }
                $counter++;
            }
        }
        $dbc->commitTransaction();
    }

    private function getSales($dbc, $args)
    {
        $dlog = DTransactionsModel::selectDLog($args[2], date('Y-m-d', strtotime('yesterday')));
        $salesP = $dbc->prepare('
            SELECT d.upc,
                d.store_id,
                ' . DTrans::sumQuantity('d') . ' AS qty,
                p.scale AS byWeight
            FROM ' . $dlog . ' AS d
                ' . DTrans::joinProducts('d', 'p', 'INNER') . '
            WHERE p.default_vendor_id > 0
                AND d.trans_status <> \'R\'
                AND d.upc=?
                AND d.store_id=?
                AND d.tdate >= ?
                AND d.charflag <> \'SO\'
            GROUP BY d.upc,
                d.store_id
            HAVING qty > 0');
        $sales = $dbc->getRow($salesP, $args);
        if ($sales === false) {
            return 0;
        }
        return $sales['byWeight'] ? $sales['qty'] * 1.001 : $sales['qty'];
    }
}

