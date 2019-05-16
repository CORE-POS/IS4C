<?php

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class SaOutOfStock extends FannieRESTfulPage
{
    public $page_set = 'Plugin :: Shelf Audit';
    public $description = '[Out of Stock Scanner] is an interface for scanning out-of-stocks';
    protected $enable_linea = true;
    protected $title = 'ShelfAudit Out of Stocks';
    protected $header = '';

    protected function get_id_view()
    {
        $store = COREPOS\Fannie\API\lib\Store::getIdByIp();
        $emp = $this->config->get('EMP_NO');
        $reg = $this->config->get('REGISTER_NO');
        $dtransactions = FannieDB::fqn('dtransactions', 'trans');
        $transP = $this->connection->prepare("SELECT trans_no
            FROM {$dtransactions}
            WHERE mixMatch='OOS'
                AND emp_no=?
                AND register_no=?
                AND store_id=?");
        $trans = $this->connection->getValue($transP, array($emp, $reg, $store));
        if (!$trans) {
            $trans = DTrans::getTransNo($this->connection, $emp, $reg);
        }

        $vals = DTrans::defaults();
        $vals['store_id'] = $store;
        $vals['emp_no'] = $emp;
        $vals['register_no'] = $reg;
        $vals['trans_no'] = $trans;
        $vals['mixMatch'] = 'OOS';
        $vals['trans_type'] = 'I';
        $vals['trans_status'] = 'X';

        $upc = BarcodeLib::padUPC($this->id);
        $prodP = $this->connection->prepare("SELECT description, department, normal_price FROM products WHERE upc=?");
        $prod = $this->connection->getRow($prodP, array($upc));
        if ($prod === false) {
            return '<div class="alert alert-danger">Item not found: ' . $upc . '</div>'
                . $this->get_view();
        }

        $vals['upc'] = $upc;
        $vals['description'] = $prod['description'];
        $vals['department'] = $prod['department'];
        $vals['quantity'] = 1;
        $vals['ItemQtty'] = 1;
        $vals['total'] = $prod['normal_price'];

        $dInfo = DTrans::parameterize($vals, 'datetime', $this->connection->now());
        $insP = $this->connection->prepare("INSERT INTO {$dtransactions}
            ({$dInfo['columnString']}) VALUES ({$dInfo['valueString']})");
        $this->connection->execute($insP, $dInfo['arguments']);

        return '<div class="alert alert-success">Logged ' . $upc . ' ' . $prod['description'] . '</div>'
            . $this->get_view();
    }

    protected function get_view()
    {
        $this->addOnloadCommand("enableLinea('#upc-in');");
        $this->addOnloadCommand("\$('#upc-in').focus();");
        return <<<HTML
<form method="get">
    <div class="form-group">
        <div class="input-group">
            <span class="input-group-addon">UPC</span>
            <input type="text" class="form-control" name="id" id="upc-in" />
        </div>
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Go</button>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

