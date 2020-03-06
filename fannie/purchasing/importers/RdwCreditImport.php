<?php

include(__DIR__ . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class RdwCreditImport extends FannieRESTfulPage
{
    protected $title = 'RDW Credit Import';
    protected $header = 'RDW Credit Import';

    protected function post_handler()
    {
        $invoice = FormLib::get('invoice');
        $ref = FormLib::get('ref');
        $date = FormLib::get('date');
        $store = FormLib::get('store');
        $regex = '/\d+ \d+ (\d+) (.+) (\d?\.?\d+) (-\d+\.\d\d) (-\d+\.\d\d).*/';
        $invItems = array();
        foreach (explode("\n", $invoice) as $line) {
            $hasSku = preg_match($regex, $line, $matches);
            if ($hasSku) {
                $invItems[] = $matches;
            }
        }
        if (count($invItems) == 0) {
            return 'RdwCreditImport';
        }
        $this->orderID = $this->getPO($ref, $store, $date);
        $clearP = $this->connection->prepare("DELETE FROM PurchaseOrderItems WHERE orderID=?");
        $this->connection->execute($clearP, array($this->orderID));
        $model = new PurchaseOrderItemsModel($this->connection);
        foreach ($invItems as $item) {
            $desc = $item[2];
            $case = 1;
            $unit = '';
            $model->orderID($this->orderID);
            $sku = $item[1];
            $model->sku($sku);
            if ($model->load()) {
                $model->quantity($model->quantity() + $item[3]);
                $model->receivedQty($model->receivedQty() + ($item[3] * $case));
                $model->receivedTotalCost($model->receivedTotalCost() + $item[5]);
                $model->save();
            } else {
                $model->quantity($item[3]);
                $model->unitCost($item[4] / $case);
                $model->caseSize($case);
                $model->unitSize($unit);
                $model->receivedDate($date);
                $model->receivedQty($item[3] * $case);
                $model->receivedTotalCost($item[5]);
                $model->brand();
                $model->description($desc);
                $model->internalUPC(BarcodeLib::padUPC('0'));
                $model->salesCode(51300);
                $model->save();
            }
        }

        return true;
    }

    protected function post_view()
    {
        return <<<HTML
<div>
Import complete.
<ul>
    <li><a href="../ViewPurchaseOrders.php?id={$this->orderID}">View Credit</a></li>
    <li><a href="RdwCreditImport.php">Import Another</a></li>
</ul>
</div>
HTML;
    }

    private function getPO($ref, $store, $date)
    {
        $prep = $this->connection->prepare("SELECT orderID FROM PurchaseOrder
            WHERE vendorInvoiceID=? AND vendorID=136 AND storeID=?");
        $exists = $this->connection->getValue($prep, array($ref, $store));
        if ($exists) {
            return $exists;
        }

        $order = new PurchaseOrderModel($this->connection);
        $order->vendorID(136);
        $order->storeID($store);
        $order->vendorInvoiceID($ref);
        $order->placed(1);
        $order->creationDate($date);
        $order->placedDate($date);

        return $order->save();
    }

    protected function get_view()
    {
        $stores = FormLib::storePicker();
        return <<<HTML
<form method="post">
<div class="form-group">
    <label>Copy/Paste Credit Data</label>
    <textarea name="invoice" class="form-control" rows="20"></textarea>
</div>
<div class="form-group">
    <label>Date</label>
    <input type="text" class="form-control date-field" name="date" required />
</div>
<div class="form-group">
    <label>Invoice #</label>
    <input type="text" class="form-control" name="ref" required />
</div>
<div class="form-group">
    <label>Store</label>
    {$stores['html']}
</div>
<div class="form-group">
    <button type="submit" class="btn btn-default">Import</button>
</div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();
