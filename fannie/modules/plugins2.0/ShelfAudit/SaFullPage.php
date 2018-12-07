<?php

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class SaFullPage extends FannieRESTfulPage 
{
    public $page_set = 'Plugin :: Shelf Audit';
    public $description = '[Full] is an interface for scanning and entering quantities on
    hand using a device with a large display.';
    protected $title = 'ShelfAudit Inventory';
    protected $header = '';

    public function preprocess()
    {
        $this->addRoute('post<id><qty>');

        return parent::preprocess();
    }

    protected function post_id_qty_handler()
    {
        $section = FormLib::get('section');
        $realQty = FormLib::get('real');
        $dbc = $this->connection;

        $chkP = $dbc->prepare("SELECT upc
            FROM " . FannieDB::fqn('sa_inventory', 'plugin:ShelfAuditDB') . "
            WHERE upc=?
                AND section=?
                AND clear=0");
        $chk = $dbc->getValue($chkP, array(BarcodeLib::padUPC($this->id), $section));
        if ($chk && $realQty == 0) {
            $prep = $dbc->prepare("DELETE FROM " . FannieDB::fqn('sa_inventory', 'plugin:ShelfAuditDB') . "
                WHERE upc=?
                    AND section=?");
            $dbc->execute($prep, array(BarcodeLib::padUPC($this->id), $section));
        } elseif ($chk) {
            $prep = $dbc->prepare("UPDATE " . FannieDB::fqn('sa_inventory', 'plugin:ShelfAuditDB') . "
                SET quantity=?,
                    datetime=" . $dbc->now() . "
                WHERE upc=?
                    AND section=?");
            $dbc->execute($prep, array($realQty, BarcodeLib::padUPC($this->id), $section));
        } elseif ($realQty != 0) {
            $prep = $dbc->prepare("INSERT INTO " . FannieDB::fqn('sa_inventory', 'plugin:ShelfAuditDB') . "
                (datetime, upc, clear, quantity, section) VALUES (?, ?, 0, ?, ?)");
            $dbc->execute($prep, array(date('Y-m-d H:i:s'), BarcodeLib::padUPC($this->id), $realQty, $section));
        }

        echo $this->getRecent($dbc, $section, FormLib::get('super'));

        return false;
    }

    private function getRecent($dbc, $section, $super)
    {
        $query = "SELECT p.upc, p.description,
                s.quantity, s.datetime,
                u.likeCode
            FROM " . FannieDB::fqn('sa_inventory', 'plugin:ShelfAuditDB') . " AS s
                " . DTrans::joinProducts('s', 'p', 'INNER') . "
                LEFT JOIN upcLike AS u ON p.upc=u.upc
                LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
            WHERE s.section=?
                AND clear=0 ";
        $args = array($section);
        if ($super) {
            $query .= " AND m.superID=? ";
            $args[] = $super;
        }
        $query .= " ORDER BY datetime DESC";
        $query = $dbc->addSelectLimit($query, 15);
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $args);
        $ret = '<table class="table table-bordered tabled-striped">';
        while ($row = $dbc->fetchRow($res)) {
            $item = $row['upc'] . ' ' . $row['description'];
            if ($row['likeCode']) {
                $item = 'LC ' . $row['likeCode'] . ' ' . $row['description'];
            }
            $ret .= sprintf('<tr><td>%.2f</td><td>%s</td></tr>', $row['quantity'], $item);
        }
        $ret .= '</table>';

        return $ret;
    }

    protected function post_id_handler()
    {
        $section = FormLib::get('section');
        $super = FormLib::get('super');
        $dbc = $this->connection;
        $prep = $dbc->prepare("
            SELECT p.upc, p.description,
                s.quantity, s.datetime,
                u.likeCode
            FROM products AS p
                LEFT JOIN " . FannieDB::fqn('sa_inventory', 'plugin:ShelfAuditDB') . " AS s
                    ON p.upc=s.upc AND s.clear=0 AND s.section=?
                LEFT JOIN upcLike AS u ON p.upc=u.upc
            WHERE p.store_id=1
                AND p.upc=?");
        $row = $dbc->getRow($prep, array($section, BarcodeLib::padUPC($this->id)));
        if ($row === false) {
            echo '<div class="alert alert-danger">Item not found</div>';
            return false;
        }
        $item = $row['upc'] . ' ' . $row['description'];
        if ($row['likeCode']) {
            $item = 'LC ' . $row['likeCode'] . ' ' . $row['description'];
        }
        if (!$row['quantity']) {
            $row['quantity'] = 0;
        }

        echo <<<HTML
<h3>{$item}</h3>
<div class="row lead">
    <div class="col-sm-3">
        Current quantity: <span id="curQty">{$row['quantity']}</span>
        <input type="hidden" id="lastQty" value="{$row['quantity']}" />
    </div>
    <div class="col-sm-3">
        <div class="input-group">
            <span class="input-group-addon">Quantity</span>
            <input type="number" name="qty" id="newQty" class="form-control" 
                onkeyup="full.keybind(event);" onkeydown="full.tab(event);"
                min="-999" max="999" step="1" />
        </div> 
    </div>
    <input type="hidden" name="id" value="{$this->id}" />
    <input type="hidden" name="section" value="{$section}" />
    <input type="hidden" name="super" value="{$super}" />
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
        $super = FormLib::get('super', -999);
        $section = FormLib::get('section', 0);
        $backstock = !$section ? 'checked' : 0;
        $floor = $section ? 'checked' : 0;
        $model = new MasterSuperDeptsModel($this->connection);
        $opts = $model->toOptions($super);
        $this->addScript('js/full.js');
        $this->addScript('../../../item/autocomplete.js');
        $ws = $FANNIE_URL . '../../../ws/';
        $this->addOnloadCommand("bindAutoComplete('#upc', '$ws', 'item');\n");
        $this->addOnloadCommand("\$('#upc').focus();");

        return <<<HTML
<form method="post" id="searchform" onsubmit="full.search(); return false;">
    <div class="form-inline">
        <label>Item Filter</label>: <select name="super" onchange="full.setFilter(this.value);" class="form-control small">
            <option value="">None</option>{$opts}</select>
        <label>
            <input tabindex="-1" type="radio" name="section" value=0 {$backstock} /> Backstock
        </label>
        <label>
            <input tabindex="-1" type="radio" name="section" value=1 {$floor} /> Floor
        </label>
    </div>
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

