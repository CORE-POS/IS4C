<?php


include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ScanTransferPage extends FannieRESTfulPage
{
    protected $header = 'Create Transfer';
    protected $title = 'Create Transfer';
    public $description = '[Scan Transfer] is a tool to build store-transfer purchase orders.';
    protected $enable_linea = true;
    protected $must_authenticate = true;

    public function preprocess()
    {
        if (php_sapi_name() !== 'cli') {
            @session_start();
            if (!isset($_SESSION['items'])) {
                $_SESSION['items'] = array();
            }
        }

        return parent::preprocess();
    }

    private function getStoreVendor($dbc, $storeID)
    {
        $prep = $dbc->prepare("
            SELECT v.vendorID
            FROM vendors AS v
                INNER JOIN Stores AS s ON v.vendorName=s.description
            WHERE s.storeID=?");
        return $dbc->getValue($prep, array($storeID));
    }

    protected function post_view()
    {
        try {
            $from = $this->form->from;
            $dest = $this->form->to;
            $uid = FannieAuth::getUID($this->current_user);
            $orderOut = new PurchaseOrderModel($this->connection);
            $orderOut->storeID($from);
            $orderOut->vendorID($this->getStoreVendor($this->connection, $dest));
            $orderOut->userID($uid);
            $orderOut->placed(1);
            $orderOut->creationDate(date('Y-m-d H:i:s'));
            $orderOut->placedDate(date('Y-m-d H:i:s'));
            $outID = $orderOut->save();

            $orderIn = new PurchaseOrderModel($this->connection);
            $orderIn->storeID($dest);
            $orderIn->vendorID($this->getStoreVendor($this->connection, $from));
            $orderIn->userID($uid);
            $orderIn->placed(1);
            $orderIn->creationDate(date('Y-m-d H:i:s'));
            $orderOut->placedDate(date('Y-m-d H:i:s'));
            $inID = $orderIn->save();

            $orderOut->orderID($outID);
            $orderOut->vendorInvoiceID('XFER-OUT-' . $inID);
            $orderOut->save();

            $orderIn->orderID($inID);
            $orderIn->vendorInvoiceID('XFER-IN-' . $outID);
            $orderIn->save();

            $item = new PurchaseOrderItemsModel($this->connection);
            foreach ($_SESSION['items'] as $upc => $data) {
                $item->internalUPC($upc);
                $item->sku($upc);
                $item->brand($data['brand']);
                $item->description = $data['desc'];
                $item->unitCost($data['cost']);  
                $item->salesCode($data['code']);
                $item->caseSize(1);
                
                $item->quantity($data['qty']);
                $item->orderID($inID);
                $item->save();

                $item->quantity(-1*$data['qty']);
                $item->orderID($outID);
                $item->save();
            }

            unset($_SESSION['items']);

            return <<<HTML
<div class="alert alert-success">Transfer orders created</div>
<p>
    <a href="ViewPurchaseOrders.php?id={$outID}" class="btn btn-default">Outgoing Order</a>
</p>
<p>
    <a href="ViewPurchaseOrders.php?id={$inID}" class="btn btn-default">Incoming Order</a>
</p>
HTML;
            
        } catch (Exception $ex) {
            return '<div class="alert alert-danger">Something went wrong (' . $ex->getMessage() . ')</div>';
        }
    }

    protected function post_id_handler()
    {
        try {
            $upc = BarcodeLib::padUPC($this->id);
            if (isset($_SESSION['items'][$upc])) {
                $_SESSION['items'][$upc]['qty'] += $this->form->qty;
            } else {
                $_SESSION['items'][$upc] = array(
                    'qty' => $this->form->qty,
                    'cost' => $this->form->cost,
                    'brand' => $this->form->brand,
                    'desc' => $this->form->desc,
                    'code' => $this->form->coding,
                );
            }
        } catch (Exception $ex) {
        }

        return 'ScanTransferPage.php';
    }

    protected function get_id_view()
    {
        $prod = new ProductsModel($this->connection);
        $infoP = $this->connection->prepare('
            SELECT *
            FROM products AS p
                LEFT JOIN departments AS d ON p.department=d.dept_no
            WHERE upc=?
                AND store_id=1');
        $upc = BarcodeLib::padUPC($this->id);
        $info = $this->connection->getRow($infoP, array($upc));
        
        $ret = <<<HTML
<form method="post">
    <div class="form-group">
        <label>Quantity</label>
        <input type="number" min="0" max="999" step="0.01" name="qty" id="qty" class="form-control input-sm" value="1" />
    </div>
    <div class="form-group">
        <label>Unit Cost</label>
        <input type="number" min="-999" max="999" step="0.01" name="cost" class="form-control input-sm" value="{$info['cost']}" />
    </div>
    <div class="form-group">
        <label>Coding</label>
        <input type="number" min="0" max="99999" step="1" name="coding" class="form-control input-sm" value="{$info['salesCode']}" />
    </div>
    <div class="form-group">
        <label>Brand</label>
        <input type="text" name="brand" class="form-control input-sm" value="{$info['brand']}" />
    </div>
    <div class="form-group">
        <label>Description</label>
        <input type="text" name="desc" class="form-control input-sm" value="{$info['description']}" />
    </div>
    <p>
        <input type="hidden" name="id" value="{$upc}" />
        <button type="submit" class="btn btn-default">Add</button>
        <a href="ScanTransferPage.php" class="btn btn-default btn-reset">Go Back</a>
    </p>
</form>
HTML;
        $this->addOnloadCommand("\$('#qty').focus();\n");

        return $ret;
    }

    protected function get_view()
    {
        $ret = '<table class="table table-bordered table-striped small">
            <thead><tr>
                <th>Coding</th>
                <th>Brand</th>
                <th>Description</th>
                <th>Unit Cost</th>
                <th>Qty</th>
            </tr></thead><tbody>';
        foreach ($_SESSION['items'] as $upc => $data) {
            $ret .= sprintf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%.2f</td><td>%.2f</td></tr>',
                    $data['code'], $data['brand'], $data['desc'], $data['cost'], $data['qty']);
        }
        $ret .= <<<HTML
</tbody>
</table>
<div class="panel panel-default">
    <div class="panel-heading">Add Item</div>
    <div class="panel-body">
        <div class="form-inline">
            <form method="get">
                <label>UPC</label>
                <input type="text" name="id" id="upcIn" class="form-control" />
                <button type="submit" class="btn btn-default">Add</button>
            </form>
        </div>
    </div>
</div>
HTML;
        $stores = FormLib::storePicker('store', false);
        $myID = COREPOS\Fannie\API\lib\Store::getIdByIP();
        $ret .= '<div class="panel panel-default">
            <div class="panel-heading">Finalize</div>
            <div class="panel-body">
            <form method="post">
            <div class="form-group">
                <label>From</label>
                <select name="from" class="form-control input-sm">';
        foreach ($stores['names'] as $id => $name) {
            $ret .= sprintf('<option %s value="%d">%s</option>',
                ($myID == $id ? 'selected' : ''), $id, $name);
        }
        $ret .= '</select>
            </div>
            <div class="form-group">
                <label>To</label>
                <select name="to" class="form-control input-sm">';
        $selected = 0;
        foreach ($stores['names'] as $id => $name) {
            if ($selected == 0 && $id != $myID) {
                $selected = 1;
            } elseif ($selected == 1) {
                $selected = 2;
            }
            $ret .= sprintf('<option %s value="%d">%s</option>',
                ($selected == 1 ? 'selected' : ''), $id, $name);
        }
        $ret .= '</select>
            </div>
            <p>
                <button type="submit" class="btn btn-default">Create Transfer</button>
            </p>
        </form>
        </div>
        </div>';
        
        $this->addOnloadCommand("enableLinea('#upcIn');\n");
        $this->addOnloadCommand("\$('#upcIn').focus();\n");

        return $ret;
    }
}

FannieDispatch::conditionalExec();

