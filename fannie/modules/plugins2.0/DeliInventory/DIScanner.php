<?php

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class DIScanner extends FannieRESTfulPage 
{
    public $page_set = 'Plugin :: Deli Inventory';
    public $description = '[Deli Inventory Scanner] is a tool for entering deli inventory';
    protected $title = 'Deli Inventory';
    protected $header = '';

    public function preprocess()
    {
        $this->addRoute('post<id><qty>', 'get<search>');

        return parent::preprocess();
    }

    protected function get_search_handler()
    {
        $dbc = $this->connection;
        $store = COREPOS\Fannie\API\lib\Store::getIdByIp();
        $prep = $dbc->prepare("SELECT upc, item
            FROM deliInventoryCat
            WHERE upc is not null
                AND upc <> ''
                AND storeID=?
                AND item LIKE ?
            ORDER BY item");
        $res = $dbc->execute($prep, array($store, '%' . $this->search . '%'));
        $ret = array();
        while ($row = $dbc->fetchRow($res)) {
            $ret[] = array(
                'label' => $row['item'],
                'value' => $row['upc'],
            );
        }

        echo json_encode($ret);

        return false;
    }

    protected function post_id_qty_handler()
    {
        $realQty = FormLib::get('realQty', false);
        $realCases = FormLib::get('realCases', false);
        $dbc = $this->connection;
        $store = COREPOS\Fannie\API\lib\Store::getIdByIp();

        if ($realQty !== false) {
            $prep = $dbc->prepare("
                UPDATE deliInventoryCat
                SET fraction=?,
                    modified=" . $dbc->now() . "
                WHERE upc=?
                    AND storeID=?");
            $res = $dbc->execute($prep, array($realQty, BarcodeLib::padUPC($this->id), $store));
            echo $this->getRecent($dbc);
        } elseif ($realCases !== false) {
            $prep = $dbc->prepare("
                UPDATE deliInventoryCat
                SET cases=?,
                    modified=" . $dbc->now() . "
                WHERE upc=?
                    AND storeID=?");
            $res = $dbc->execute($prep, array($realCases, BarcodeLib::padUPC($this->id), $store));
            echo $this->getRecent($dbc);
        } 

        return false;
    }

    private function getRecent($dbc)
    {
        $store = COREPOS\Fannie\API\lib\Store::getIdByIp();
        $query = "SELECT d.upc, d.item,
                d.fraction, d.cases
            FROM deliInventoryCat AS d
            WHERE d.storeID=?
            ORDER BY modified DESC";
        $args = array($store);
        $query = $dbc->addSelectLimit($query, 15);
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $args);
        $ret = '<table class="table table-bordered tabled-striped">';
        while ($row = $dbc->fetchRow($res)) {
            $item = $row['upc'] . ' ' . $row['item'];
            $ret .= sprintf('<tr><td>%.2f</td><td>%.2f</td><td>%s</td></tr>', $row['cases'], $row['fraction'], $item);
        }
        $ret .= '</table>';

        return $ret;
    }

    protected function post_id_handler()
    {
        $store = COREPOS\Fannie\API\lib\Store::getIdByIp();
        $dbc = $this->connection;
        $prep = $dbc->prepare("
            SELECT d.upc, d.item,
                d.fraction, d.cases, d.units
            FROM deliInventoryCat AS d
            WHERE d.storeID=?
                AND d.upc=?");
        $this->id = BarcodeLib::padUPC($this->id);
        if (substr($this->id, 0, 3) == "002") {
            $this->id = substr($this->id, 0, 7) . '000000';
        }
        $row = $dbc->getRow($prep, array($store, $this->id));
        if ($row === false) {
            echo '<div class="alert alert-danger">Item not found' . $this->id . '</div>' . 'err:' . $dbc->error();
            return false;
        }
        $item = $row['upc'] . ' ' . $row['item'];
        if (!$row['fraction']) {
            $row['fraction'] = 0;
        }
        $row['fraction'] = sprintf('%.2f', $row['fraction']);

        echo <<<HTML
<h3>{$item}</h3>
<div class="row lead">
    <div class="col-sm-3">
        Current eaches: <span id="curQty">{$row['fraction']}</span>
        <input type="hidden" id="lastQty" value="{$row['fraction']}" />
    </div>
    <div class="col-sm-3">
        <div class="input-group">
            <span class="input-group-addon">Eaches</span>
            <input type="number" name="qty" id="newQty" class="form-control" 
                onkeyup="scanner.keybindQty(event);" onkeydown="scanner.tabQty(event);"
                min="-999" max="999" step="1" />
        </div> 
    </div>
</div>
<div class="row lead">
    <div class="col-sm-3">
        Current cases ({$row['units']}): <span id="curCases">{$row['cases']}</span>
        <input type="hidden" id="lastCases" value="{$row['cases']}" />
    </div>
    <div class="col-sm-3">
        <div class="input-group">
            <span class="input-group-addon">Cases</span>
            <input type="number" name="cases" id="newCases" class="form-control" 
                onkeyup="scanner.keybindCases(event);" onkeydown="scanner.tabCases(event);"
                min="-999" max="999" step="1" />
        </div> 
    </div>
    <input type="hidden" name="id" value="{$this->id}" />
</div>
HTML;

        return false;
    }

    function css_content()
    {
        return <<<CSS
input.focused {
    background: #ffeebb;
}
CSS;
    }

    protected function get_view()
    {
        $this->addScript('scanner.js?date=20190415');
        $this->addOnloadCommand("scanner.autocomplete('#upc');");
        $this->addOnloadCommand("\$('#upc').on('autocompleteselect', function(event, ui) { scanner.autosubmit(event, ui); });");
        $this->addOnloadCommand("\$('#upc').focus();");

        return <<<HTML
<form method="post" id="searchform" onsubmit="scanner.search(); return false;">
    <hr />
    <p>
        <div class="input-group">
            <span class="input-group-addon">Search</span>
            <input type="text" name="id" id="upc" class="form-control focused" />
            <span class="input-group-btn">
                <button type="submit" class="btn btn-default" tabindex="-1">Go</button>
            </span>
        </div>
    </p>
</form>
<div id="results"></div>
<div id="recent"></div>
HTML;
    }
}

FannieDispatch::conditionalExec();

