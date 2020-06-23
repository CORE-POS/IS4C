<?php

if (!class_exists('DeliInvLastestMapModel')) {
    include(__DIR__ . '/models/DeliInvLatestMapModel.php');
}

class DIInvoiceTask extends FannieTask
{
    public function run()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));

        $invQ = $dbc->addSelectLimit("SELECT i.*
            FROM PurchaseOrder AS o
                INNER JOIN PurchaseOrderItems AS i ON o.orderID=i.orderID
            WHERE o.vendorID=?
                AND i.sku=?
                AND i.receivedQty > 0
            ORDER BY receivedDate DESC", 1);
        $invP = $dbc->prepare($invQ);

        $res = $dbc->query("SELECT orderno, vendorID
            FROM deliInventoryCat
            WHERE vendorID IS NOT NULL
                AND vendorID <> 0
                AND orderno IS NOT NULL
                AND orderno <> ''
                AND orderno <> upc
                AND orderno NOT LIKE '%TODO%'
            GROUP BY orderno, vendorID");
        while ($row = $dbc->fetchRow($res)) {
            $inv = $dbc->getRow($invP, array($row['vendorID'], $row['orderno']));
            if ($inv) {
                //echo "Found invoice for {$inv['sku']}\n";
                $model = new DeliInvLatestMapModel($dbc);
                $model->vendorID($row['vendorID']);
                $model->sku($inv['sku']);
                $model->orderID($inv['orderID']);
                $model->unitSize($inv['unitSize']);
                $model->caseSize($inv['caseSize']);
                $model->quantity($inv['quantity']);
                $model->unitCost($inv['unitCost']);
                $model->receivedDate($inv['receivedDate']);
                $model->receivedQty($inv['receivedQty']);
                $model->receivedTotalCost($inv['receivedTotalCost']);
                $model->save();
            }
        }
    }
}

