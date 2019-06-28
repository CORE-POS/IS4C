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
    protected $enable_linea = true;

    public function preprocess()
    {
        $this->addRoute('post<id><qty>', 'get<search>', 'post<itemID><flag>');

        return parent::preprocess();
    }

    protected function post_itemID_flag_handler()
    {
        $prep = $this->connection->prepare("UPDATE deliInventoryCat SET attnFlag=? WHERE id=?");
        $this->connection->execute($prep, array($this->flag, $this->itemID));

        return false;
    }

    protected function get_search_handler()
    {
        $dbc = $this->connection;
        $store = COREPOS\Fannie\API\lib\Store::getIdByIp();
        $prep = $dbc->prepare("SELECT upc, item, c.name
            FROM deliInventoryCat AS d
                LEFT JOIN DeliCategories AS c ON d.categoryID=c.deliCategoryID
            WHERE upc is not null
                AND upc <> ''
                AND d.storeID=?
                AND item LIKE ?
            ORDER BY item");
        $res = $dbc->execute($prep, array($store, '%' . $this->search . '%'));
        $ret = array();
        while ($row = $dbc->fetchRow($res)) {
            if ($row['name']) {
                $row['item'] .= ' (' . $row['name'] . ')';
            }
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
            $res = $dbc->execute($prep, array($realQty, $this->getUPC($this->id), $store));
            echo $this->getRecent($dbc);
        } elseif ($realCases !== false) {
            $prep = $dbc->prepare("
                UPDATE deliInventoryCat
                SET cases=?,
                    modified=" . $dbc->now() . "
                WHERE upc=?
                    AND storeID=?");
            $res = $dbc->execute($prep, array($realCases, $this->getUPC($this->id), $store));
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
            SELECT d.upc, d.item, d.attnFlag, d.id,
                d.fraction, d.cases, d.units, d.size
            FROM deliInventoryCat AS d
            WHERE d.storeID=?
                AND d.upc=?");
        $original = $this->id;
        $this->id = $this->getUPC($this->id);
        $row = $dbc->getRow($prep, array($store, $this->id));
        if ($row === false) {
            $nocheck = $this->getUPC(substr($original, 0, strlen($original) - 1));
            $row = $dbc->getRow($prep, array($store, $nocheck));
            $this->id .= '||' . $nocheck;
        }
        if ($row === false) {
            echo '<div class="alert alert-danger">Item not found' . $this->id . '</div>' . 'err:' . $dbc->error();
            return false;
        }
        $item = $row['upc'] . ' ' . $row['item'];
        if (!$row['fraction']) {
            $row['fraction'] = 0;
        }
        $row['fraction'] = sprintf('%.2f', $row['fraction']);
        $attn = $row['attnFlag'] ? 'checked' : '';
        $caseBtns = '
           <button type="button" onclick="scanner.incDec(\'#newQty\', 1);" class="btn btn-lg btn-success">+1</button> 
           <button type="button" onclick="scanner.incDec(\'#newQty\', -1);" class="btn btn-lg btn-danger">-1</button>';
        if (is_numeric($row['units'])) {
            $caseBtns = sprintf('
               <button type="button" onclick="scanner.incDec(\'#newQty\', %d);" class="btn btn-lg btn-success">+%d</button> 
               <button type="button" onclick="scanner.incDec(\'#newQty\', -%d);" class="btn btn-lg btn-danger">-%d</button>%s',
                $row['units'], $row['units'],
                $row['units'], $row['units'],
                $caseBtns);
        }

        echo <<<HTML
<h3><input type="checkbox" onchange="scanner.attn({$row['id']}, this);" title="Needs further attention" {$attn} /> {$item}</h3>
<div class="row lead">
    <div class="col-sm-3">
        Cur. eaches ({$row['size']}): <span id="curQty">{$row['fraction']}</span>
        <input type="hidden" id="lastQty" value="{$row['fraction']}" />
    </div>
    <div class="col-sm-3">
        <div class="input-group">
            <span class="input-group-addon">Eaches</span>
            <input type="number" name="qty" id="newQty" class="form-control" 
                onkeyup="scanner.keybindQty(event);" onkeydown="scanner.tabQty(event);"
                min="-999" max="999" step="1" />
        </div> 
        <div class="buttons">
            {$caseBtns}
        </div> 
    </div>
</div>
<div class="row lead">
    <div class="col-sm-3">
        Cur. cases ({$row['units']}): <span id="curCases">{$row['cases']}</span>
        <input type="hidden" id="lastCases" value="{$row['cases']}" />
    </div>
    <div class="col-sm-3">
        <div class="input-group">
            <span class="input-group-addon">Cases</span>
            <input type="number" name="cases" id="newCases" class="form-control" 
                onkeyup="scanner.keybindCases(event);" onkeydown="scanner.tabCases(event);"
                min="-999" max="999" step="1" />
        </div> 
        <div class="buttons">
           <button type="button" onclick="scanner.incDec('#newCases', 1);" class="btn btn-lg btn-success">+1</button> 
           <button type="button" onclick="scanner.incDec('#newCases', -1);" class="btn btn-lg btn-danger">-1</button> 
        </div>
    </div>
    <input type="hidden" name="id" value="{$this->id}" />
</div>
<div class="row lead">
    <div class="col-sm-3">
        <div class="input-group">
            <div class="input-group-addon">[T]are</div>
            <select id="tareSelect" class="form-control">
                <option value="0">Pick container...</option>
                <option value="0.64">Cambro 2QT</option>
                <option value="1.00">Cambro 4QT</option>
                <option value="1.30">Cambro 6QT</option>
                <option value="1.60">Cambro 8QT</option>
                <option value="2.63">Cambro 12QT</option>
                <option value="3.75">Cambro 22QT</option>
                <option value="5.75">Rice/Flour/Salt Bins</option>
                <option value="0.14">Spice Container</option>
                <option value="3.40">Full Sheet Tray</option>
                <option value="1.45">2" Half-Wide Hotel</option>
                <option value="1.59">2" Half-Long</option>
                <option value="2.80">2" Full Hotel</option>
            </select>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="buttons">
           <button type="button"
                onclick="scanner.addRemoveTare(-1);" class="btn btn-lg btn-success">[A]dd</button> 
           <button type="button" onclick="scanner.addRemoveTare(1);" class="btn btn-lg btn-danger">[R]emove</button> 
        </div>
        <span id="numTares">0</span>
    </div>
</div>
<div class="row lead">
    <div class="col-sm-3">
        <a href="" class="btn btn-default btn-info"
            onclick="$('#upc').val('{$this->id}'); scanner.search(); return false;">Repeat Same Item</a>
    </div>
</div>
HTML;

        return false;
    }

    private function getUPC($str)
    {
        if (substr($str, 0, 2) == 'LC') {
            return $str;
        }
        $str = BarcodeLib::padUPC($str);
        if (substr($str, 0, 3) == "002") {
            $str = substr($str, 0, 7) . '000000';
        }

        return $str;
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
        $this->addScript('scanner.js?date=20190507');
        $this->addOnloadCommand("scanner.autocomplete('#upc');");
        $this->addOnloadCommand("\$('#upc').on('autocompleteselect', function(event, ui) { scanner.autosubmit(event, ui); });");
        $this->addOnloadCommand("\$('#upc').focus();");
        $this->addOnloadCommand("enableLinea('#upc', scanner.search);");
        $back = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        $backBtn = $back ? '<a href="' . $back . '" class="btn btn-default">Back</a>' : '';

        return <<<HTML
<form method="post" id="searchform" onsubmit="scanner.search(); return false;">
    <hr />
    <div class="row">
        <div class="col-sm-10">
            <div class="input-group">
                <span class="input-group-addon hidden-xs">Search</span>
                <input type="text" name="id" id="upc" class="form-control focused" />
                <span class="input-group-btn">
                    <button type="submit" class="btn btn-default" tabindex="-1">Go</button>
                </span>
            </div>
        </div>
        <div class="col-sm-2">
            {$backBtn}
        </div>
    </div>
</form>
<div id="results"></div>
<div id="recent"></div>
<br />
HTML;
    }
}

FannieDispatch::conditionalExec();

