<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

class PurchaseOrderSummarize extends FannieTask
{

    public $name = 'Summarize Purchase Orders';

    public $description = 'Recalculates total quantities ordered in the
last calendar quarter';

    public $default_schedule = array(
        'min' => 0,
        'hour' => 1,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB;
        $sql = FannieDB::get($FANNIE_OP_DB);
        
        $limit = date('Y-m-d 00:00:00', strtotime('92 days ago'));

        $sql->query('TRUNCATE TABLE PurchaseOrderSummary');

        $calcQ = 'INSERT INTO PurchaseOrderSummary
                SELECT p.vendorID, i.sku,
                SUM(caseSize * receivedQty) as totalReceived,
                SUM(receivedQty) as casesReceived,
                SUM(CASE WHEN receivedQty > 0 THEN 1 ELSE 0 END) as numOrders,
                SUM(CASE WHEN receivedQty < 0 THEN 1 ELSE 0 END) as numCredits,
                MIN(receivedDate) as oldest,
                MAX(receivedDate) as newest
                FROM PurchaseOrder AS p
                INNER JOIN PurchaseOrderItems AS i
                ON p.orderID=i.orderID
                WHERE i.receivedDate >= ?
                GROUP BY p.vendorID, i.sku';
        $calcP = $sql->prepare($calcQ);
        $calcR = $sql->execute($calcP, array($limit));

        $dlog = $FANNIE_TRANS_DB . $sql->sep() . 'transarchive';
        $target = $FANNIE_TRANS_DB . $sql->sep() . 'skuMovementSummary';
        $sql->query('TRUNCATE TABLE ' . $target);
        $getQ = "SELECT upc, 
                SUM(CASE WHEN trans_status<>'M' THEN quantity ELSE 0 END) as totalQty,
                SUM(CASE WHEN trans_status NOT IN ('R','Z','M') THEN quantity ELSE 0 END) as soldQty,
                SUM(CASE WHEN trans_status='R' THEN quantity ELSE 0 END) as returnedQty,
                SUM(CASE WHEN trans_status='Z' THEN quantity ELSE 0 END) as damagedQty
                FROM $dlog WHERE trans_type='I'
                AND emp_no <> 9999 AND register_no <> 99
                AND trans_status <> 'X'
                GROUP BY upc";
        $getR = $sql->query($getQ);

        $this->writeRecords($sql, $target, $getR);
    }

    private function writeRecords($sql, $target, $result)
    {
        $insQ = 'INSERT INTO ' . $target . ' (vendorID, sku, totalQty, soldQty, 
                returnedQty, damagedQty) VALUES (?, ?, ?, ?, ?, ?)';
        $insP = $sql->prepare($insQ);
        $vendorQ = 'SELECT vendorID, sku FROM vendorItems WHERE upc=? ORDER BY vendorID';
        $vendorP = $sql->prepare($vendorQ);
        while($getW = $sql->fetch_row($result)) {
            // there might be a more efficient way of doing this, but checking
            // each UPC against vendorItems will avoid duplicate records
            // where the item is available from multiple vendors
            $vendorR = $sql->execute($vendorP, array($getW['upc']));
            if ($sql->num_rows($vendorR) > 0) {
                $vendorW = $sql->fetch_row($vendorR);
                $sql->execute($insP, array($vendorW['vendorID'], $vendorW['sku'],
                        $getW['totalQty'], $getW['soldQty'], $getW['returnedQty'],
                        $getW['damagedQty']));
            }

            if ($this->test_mode) {
                break;
            }
        }
    }
}

