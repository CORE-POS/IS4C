<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class RdwImport extends FannieRESTfulPage
{
    protected $title = 'RDW COOL Data Import';
    protected $header = 'RDW COOL Data Import';

    protected function post_handler()
    {
        $this->data = array();
        $this->invoice = array();
        $invoice = FormLib::get('invoice');
        $ref = FormLib::get('ref');
        $date = FormLib::get('date');
        $store = FormLib::get('store');
        $orderID = $this->getPO($ref, $store, $date);
        $prev = false;
        $regex = '/([^0-9]*)([0-9]+) (.*) (\d\d\d\d\d) (\d*\.\d\d) (\d*\.\d\d) (\d*\.\d\d) (\d*\.\d\d) (.*)/';
        $altRegex = '/([^0-9]*)([0-9]+) (.*) (\d\d\d\d\d) (\d*\.\d\d) (\d*\.\d\d) (\d*\.\d\d) (\d*\.\d\d)/';
        $invItems = array();
        foreach (explode("\n", $invoice) as $line) {
            $hasSku = preg_match($regex, $line, $matches);
            $hasAlt = preg_match($altRegex, $line, $alts);
            if ($hasSku) {
                $sku = $matches[4];
                $cool = trim($matches[1]);
                if ($cool == 'COSTA') {
                    $cool = 'COSTA RICA';
                }
                $this->data[$sku] = $cool;
                $this->invoice[$sku] = $line;
                $invItems[] = $matches;
                $prev = $sku;
            } elseif ($hasAlt) {
                $sku = $alts[4];
                $cool = trim($alts[1]);
                if ($cool == 'COSTA') {
                    $cool = 'COSTA RICA';
                }
                $this->data[$sku] = $cool;
                $this->invoice[$sku] = $line;
                $alts[9] = 0; // no UPC given
                $invItems[] = $alts;
                $prev = $sku;
            } elseif ($prev) {
                $this->data[$prev] .= ' AND ' . $line;
                $this->data[$prev] = str_replace('N/A AND ', '', $this->data[$prev]);
            }
        }
        $clearP = $this->connection->prepare("DELETE FROM PurchaseOrderItems WHERE orderID=?");
        $this->connection->execute($clearP, array($orderID));
        $model = new PurchaseOrderItemsModel($this->connection);
        foreach ($invItems as $item) {
            list($desc, $case, $unit) = $this->getSize($item[3]);
            $model->orderID($orderID);
            $sku = $item[4];
            $model->sku($sku);
            if ($model->load()) {
                $model->quantity($model->quantity() + $item[2]);
                $model->receivedQty($model->receivedQty() + ($item[2] * $case));
                $model->receivedTotalCost($model->receivedTotalCost() + $item[7]);
                $model->save();
            } else {
                $model->quantity($item[2]);
                $model->unitCost($item[5] / $case);
                $model->caseSize($case);
                $model->unitSize($unit);
                $model->receivedDate($date);
                $model->receivedQty($item[2] * $case);
                $model->receivedTotalCost($item[7]);
                $model->brand($this->data[$sku]);
                $model->description($desc);
                $model->internalUPC(BarcodeLib::padUPC($this->fixUPC($item[9])));
                $model->salesCode(51300);
                $model->save();
            }
        }

        return true;
    }

    private function fixUPC($upc)
    {
        $upc = trim($upc);
        if (strlen($upc) == 5) {
            return $upc[0] == '9' ? substr($upc, 1) : $upc;
        }
        $upc = str_replace(' ', '', $upc);
        $upc = str_replace('-', '', $upc);

        return substr($upc, 0, strlen($upc) - 1);
    }

    private function getSize($item)
    {
        $item = trim(strtoupper($item));
        if (preg_match('/^(\d+) *LB (.*)/', $item, $matches)) {
            return array($matches[2], $matches[1], 'LB');
        } elseif (preg_match('/^(\d+) *# (.*)/', $item, $matches)) {
            return array($matches[2], $matches[1], 'LB');
        } elseif (preg_match('/^(\d+) *CT (.*)/', $item, $matches)) {
            return array($matches[2], $matches[1], 'CT');
        } elseif (preg_match('/^(\d+\/[0-9\.]+ *.+?) (.*)/', $item, $matches)) {
            list($case, $unit) = explode('/', $matches[1]);
            return array($matches[2], $case, $unit);
        } elseif (preg_match('/^(\d+\/[0-9\.]) *# (.*)/', $item, $matches)) {
            list($case, $unit) = explode('/', $matches[1]);
            return array($matches[2], $case, $unit . ' LB');
        } elseif (preg_match('/^(\d+-\d+) (.+?) (.*)/', $item, $matches)) {
            list($min, $max) = explode('-', $matches[1]);
            return array($matches[3], ($min + $max) / 2, $matches[2]);
        } elseif (preg_match('/^([0-9\.]+ *OZ) (.*)/', $item, $matches)) {
            return array($matches[2], 1, $matches[1]);
        }

        return array($item, 1, '');
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

    protected function post_view()
    {
        $vendorID = 136;
        $likeP = $this->connection->prepare("SELECT likeCode
            FROM VendorLikeCodeMap
            WHERE vendorID=? AND sku=?");
        $model = new LikeCodesModel($this->connection);
        $opts = array();
        foreach ($model->find() as $obj) {
            $opts[$obj->likeCode()] = $obj->likeCodeDesc()
                . ' '
                . ($obj->organic() ? '(O)' : '(C)');
        }
        $ret = '<form method="post" action="CoolImportSave.php">
            <table class="table table-bordered">';
        foreach ($this->data as $sku => $cool) {
            $item = $this->invoice[$sku];
            $lc = $this->connection->getValue($likeP, array($vendorID, $sku));
            if ($cool == 'NEW') {
                $cool = 'NEW ZEALAND';
            } elseif (is_numeric($cool) || $cool == '') {
                $lc = -1; // skip update if there's no valid origin
            }
            $ret .= sprintf('<tr><td>%s</td><td>%s</td>
                        <td><input type="text" name="cool[]" class="form-control input-sm" value="%s" /></td>
                        <td><select name="lc[]" class="form-control input-sm chosen">
                        <option value="">Skip item</option>',
                $sku, $item, $cool, $cool);
            foreach ($opts as $val => $label) {
                $ret .= sprintf('<option %s value="%d">%d %s</option>',
                    $lc == $val ? 'selected' : '', $val, $val, $label);
            }
            $ret .= '</select></td></tr>';
        }
        $ret .= '</table>
            <p><button class="btn btn-default" type="submit">Save</button></p>';

        $this->addScript('../../../src/javascript/chosen/chosen.jquery.min.js');
        $this->addCssFile('../../../src/javascript/chosen/bootstrap-chosen.css');
        $this->addOnloadCommand("\$('select.chosen').chosen({search_contains: true});");

        return $ret;
    }

    protected function get_view()
    {
        $stores = FormLib::storePicker();
        return <<<HTML
<form method="post">
<div class="form-group">
    <label>Copy/Paste Invoice Data</label>
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

