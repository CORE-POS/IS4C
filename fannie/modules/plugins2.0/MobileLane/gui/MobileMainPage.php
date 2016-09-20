<?php

include(__DIR__ . '/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
}
if (!class_exists('MobileLanePage')) {
    include(__DIR__ . '/../lib/MobileLanePage.php');
}

class MobileMainPage extends MobileLanePage
{
    private $msg = '';
    protected $enable_linea = true;

    public function preprocess()
    {
        /**
          No statefulness. Employee and register get
          carried through on all requests
        */
        $this->emp = FormLib::get('e', 0);
        $this->reg = FormLib::get('r', 0);
        if ($this->emp == 0 || $this->reg == 0) {
            header('Location: MobileLoginPage.php');
            return false;
        }

        return parent::preprocess();
    }

    protected function post_id_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $upc = BarcodeLib::padUPC($this->id);
        $itemP = $dbc->prepare('
            SELECT description,
                normal_price,
                special_price,
                discount,
                discounttype,
                tax,
                foodstamp,
                cost,
                department,
                mixmatchcode
            FROM products 
            WHERE upc=?
                AND scale=0');
        $item = $dbc->getRow($itemP, array($upc));
        if ($item === false) {
            $this->msg = 'Item not found';
        } else {
            $settings = $this->config->get('PLUGIN_SETTINGS');
            $dbc->selectDB($settings['MobileLaneDB']);
            $model = new MobileTransModel();
            $model->datetime(date('Y-m-d H:i:s'));
            $model->emp_no($this->emp);
            $model->register_no($this->reg);
            $model->trans_no($this->getTransNo($dbc, $this->emp, $this->reg));
            $model->trans_type('I');
            $model->department($item['department']);
            $model->quantity(1);
            $model->cost($item['cost']);
            $model->regPrice($item['normal_price']);
            $model->unitPrice($item['normal_price']);
            $model->total($item['normal_price']);
            $model->tax($item['tax']);
            $model->foodstamp($item['foodstamp']);
            $model->discountable($item['discount']);
            $model->discounttype($item['discounttype']);
            $model->ItemQtty(1);
            $model->mixMatch($item['mixmatchcode']);
            if ($item['discounttype'] == 1) {
                $model->unitPrice($item['special_price']);
                $model->total($item['special_price']);
                $model->discount($item['normal_price'] - $item['special_price']);
            }
            $saved = $model->save();
            if ($saved === false) {
                $this->msg = 'Error adding item';
            }
        }

        return true;
    }

    protected function getTransNo($dbc, $emp, $reg)
    {
        $getP = $dbc->prepare('SELECT trans_no FROM MobileTrans WHERE emp_no=? AND register_no=?');
        $get = $dbc->getValue($getP, array($emp, $reg));
        if ($get !== false) {
            return $get;
        }
        $getP = $dbc->prepare('SELECT MAX(trans_no) FROM ' . $this->config->get('TRANS_DB') . $dbc->sep() . 'dtransactions WHERE emp_no=? AND register_no=?');
        $get = $dbc->getValue($getP, array($emp, $reg));
        if ($get !== false) {
            return $get+1;
        }

        return 1;
    }

    private function listItems($dbc)
    {
        $model = new MobileTransModel($dbc);
        $model->emp_no($this->emp);
        $model->register_no($this->reg);
        $items = '';
        $ttl = 0.0;
        foreach ($model->find() as $i) {
            $items .= sprintf('
                <div class="row">
                    <div class="col-sm-7">%s</div>
                    <div class="col-sm-3">%.2f</div>
                    <div class="col-sm-2">[Void]</div>
                </div>',
                $i->description(),
                $i->total()
            );
            $ttl += $i->total();
        }
        return array($items, $ttl);
    }
    
    protected function post_id_view()
    {
        return $this->get_view();
    }

    protected function get_view()
    {
        $dbc = $this->connection;
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc->selectDB($settings['MobileLaneDB']);
        list($items, $ttl) = $this->listItems($dbc);
        $ttl = sprintf('%.2f', $ttl);
        $msg = $this->msg != '' ? "<div class=\"alert alert-danger\">{$this->msg}</div>" : '';
        $this->addOnloadCommand("\$('#mainInput').focus();\n");
        $this->addOnloadCommand("enableLinea('#mainInput');\n");
        return <<<HTML
<form method="post">
{$items}
{$msg}
<div class="row">
    <div class="col-sm-7">
        <input type="number" class="form-control" name="id" id="mainInput" 
            placeholder="Scan or key item" min="0" max="9999999999999" step="1" />
    </div>
    <div class="col-sm-3 h2">{$ttl}</div>
</div>
<div class="row">
    <div class="col-sm-3">
        <button type="submit" class="btn btn-default btn-info">Add Item</button>
    </div>
    <div class="col-sm-3">
        <a href="" class="btn btn-default btn-success">Tender Out</a>
    </div>
    <div class="col-sm-3">
        <a href="MobileMenuPage.php?e={$this->emp}&r={$this->reg}" class="btn btn-default btn-warning">Menu</a>
    </div>
</div>
<input type="hidden" name="e" value="{$this->emp}" />
<input type="hidden" name="r" value="{$this->reg}" />
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

