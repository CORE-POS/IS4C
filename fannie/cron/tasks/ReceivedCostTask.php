<?php
/*******************************************************************************

    Copyright 2016 Whole Foods Co-op

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

class ReceivedCostTask extends FannieTask
{

    public $name = 'Set Received Costs';

    public $description = 'Assign received costs to products based
on received order invoices';

    public $default_schedule = array(
        'min' => 30,
        'hour' => 1,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $cutoff = date('Y-m-d', strtotime('7 days ago'));

        $query = $dbc->prepare("
            SELECT o.internalUPC AS upc,
               r.storeID,
               MAX(o.receivedTotalCost / receivedQty) AS cost
            FROM PurchaseOrderItems AS o
                INNER JOIN PurchaseOrder AS r ON o.orderID=r.orderID
                INNER JOIN products AS p ON o.internalUPC=p.upc AND r.vendorID=p.default_vendor_id 
            WHERE o.receivedDate >= ?
                AND receivedTotalCost > 0
            GROUP BY o.internalUPC,
                r.storeID");
        
        $update = $dbc->prepare("UPDATE products SET received_cost=? WHERE upc=? AND store_id=?");

        $res = $dbc->execute($query, array($cutoff));
        while ($row = $dbc->fetchRow($res)) {
            $args = array($row['cost'], $row['upc'], $row['storeID']);
            $dbc->execute($update, $args);
        }
    }
}

