<?php

include(__DIR__ . '/../../../config.php');

if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class RpPreBookPage extends FannieRESTfulPage
{
    protected $header = 'Enter PreBooks';
    protected $title = 'Enter PreBooks';

    protected function post_handler()
    {
        $model = new PurchaseOrderModel($this->connection);
        $model->storeID(FormLib::get('store'));
        $model->vendorID(FormLib::get('vendorID'));
        $model->creationDate(date('Y-m-d H:i:s'));
        $model->placed(1);
        $model->placedDate(date('Y-m-d H:i:s'));
        $model->userID(-99);
        $orderID = $model->save();

        $model = new PurchaseOrderModel($this->connection);
        $model->orderID($orderID);
        $model->vendorInvoiceID('PREBOOK ' . $orderID);
        $model->save();

        $itemP = $this->connection->prepare("SELECT * FROM RpOrderItems WHERE upc=?");
        $item = $this->connection->getRow($itemP, array(FormLib::get('item')));
        $cases = FormLib::get('cases');
        $dates = FormLib::get('rdates');
        for ($i=0; $i<count($cases); $i++) {
            if (!is_numeric($cases[$i])) {
                continue;
            }
            $poi = new PurchaseOrderItemsModel($this->connection);
            $poi->orderID($orderID);
            $sku = substr($i . '-' . $item['vendorSKU'], 0, 13);
            $poi->sku($sku);
            $poi->quantity($cases[$i]);
            $poi->unitCost($item['cost'] / $item['caseSize']);
            $poi->caseSize(FormLib::get('caseSize'));
            $poi->unitSize('');
            switch (FormLib::get('vendorID')) {
                case 292:
                    $poi->brand('Alberts');
                    break;
                case 293:
                    $poi->brand('CPW');
                    break;
                case 136:
                    $poi->brand('RDW');
                    break;
            }
            $poi->description($item['vendorItem']);
            $poi->internalUPC($item['upc']);
            $poi->receivedQty($cases[$i] * FormLib::get('caseSize'));
            $poi->receivedDate($dates[$i]);
            $poi->save();
        }

        return 'RpPreBookPage.php?id=' . $orderID;
    }

    protected function get_id_view()
    {
        return <<<HTML
<p>
    <ul>
        <li><a href="RpPreBookPage.php">Create Another PreBook</a></li>
        <li><a href="../../../purchasing/ViewPurchaseOrders.php?id={$this->id}">View Purchase Order</a></li>
    </ul>
</p>
HTML;
    }

    protected function get_view()
    {
        $this->addScript('../../../src/javascript/chosen/chosen.jquery.min.js');
        $this->addCssFile('../../../src/javascript/chosen/bootstrap-chosen.css');
        $this->addOnloadCommand("\$('select.chosen').chosen();");

        $res = $this->connection->query("SELECT upc, vendorItem, caseSize FROM RpOrderItems GROUP BY upc, vendorItem, caseSize");
        $opts = '';
        $cases = array();
        while ($row = $this->connection->fetchRow($res)) {
            $opts .= sprintf('<option value="%s">%s %s</option>', $row['upc'], $row['upc'], $row['vendorItem']);
            $cases[$row['upc']] = $row['caseSize'];
        }
        $caseJSON = json_encode($cases);
        $stores = FormLib::storePicker();

        return <<<HTML
<form method="post">
<div class="form-group">
    <label>Store</label>
    {$stores['html']}
</div>
<div class="form-group">
    <label>Vendor</label>
    <select name="vendorID" class="form-control" required>
        <option value="">Select one...</option>
        <option value="292">Alberts</option>
        <option value="293">CPW</option>
        <option value="136">RDW</option>
    </select>
</div>
<div class="form-group">
    <label>Item</label>
    <select name="item" class="form-control chosen" onchange="updateCaseSize(this.value);" required>
        <option value="">Select one...</option>
        {$opts}
    </select>
</div>
<div class="form-group">
    <label>Case Size</label>
    <input type="text" class="form-control" name="caseSize" id="caseSize" />
</div>
<p>
<b>Shipment(s)</b>
<table class="table table-bordered table-striped">
    <tr><th>Cases</th><th>Arriving</th></tr>
    <tr>
        <td><input type="text" class="form-control" name="cases[]" required /></td>
        <td><input type="text" class="form-control date-field" name="rdates[]" required /></td>
    </tr>
    <tr>
        <td><input type="text" class="form-control" name="cases[]" /></td>
        <td><input type="text" class="form-control date-field" name="rdates[]" /></td>
    </tr>
    <tr>
        <td><input type="text" class="form-control" name="cases[]" /></td>
        <td><input type="text" class="form-control date-field" name="rdates[]" /></td>
    </tr>
    <tr>
        <td><input type="text" class="form-control" name="cases[]" /></td>
        <td><input type="text" class="form-control date-field" name="rdates[]" /></td>
    </tr>
    <tr>
        <td><input type="text" class="form-control" name="cases[]" /></td>
        <td><input type="text" class="form-control date-field" name="rdates[]" /></td>
    </tr>
    <tr>
        <td><input type="text" class="form-control" name="cases[]" /></td>
        <td><input type="text" class="form-control date-field" name="rdates[]" /></td>
    </tr>
</table>
</p>
<p>
    <button type="submit" class="btn btn-default">Create Purchase Order</button>
</p>
</form>
<script>
function updateCaseSize(upc) {
    var cases = {$caseJSON};
    if (upc in cases) {
        $('#caseSize').val(cases[upc]);
    } else {
        $('#caseSize').val('');
    }
}
</script>
HTML;
    }
}

FannieDispatch::conditionalExec();

