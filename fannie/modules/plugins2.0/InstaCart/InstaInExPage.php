<?php

use COREPOS\Fannie\API\item\ItemText;
use COREPOS\Fannie\API\lib\FannieUI;

include(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class InstaInExPage extends FannieRESTfulPage
{
    protected $header = 'Instacart Eligibility';
    protected $title = 'Instacart Eligibility';

    public function preprocess()
    {
        $this->addRoute('post<super>', 'get<unsuper>', 'post<dept>', 'get<undept>',
            'post<sub>', 'get<unsub>');

        return parent::preprocess();
    }

    protected function get_unsuper_handler()
    {
        $plugin = $this->config->get('PLUGIN_SETTINGS');
        $table = $plugin['InstaCartDB'] . $this->connection->sep() . 'InstaSupers';
        $prep = $this->connection->prepare("DELETE FROM {$table} WHERE instaSuperID=?");
        $res = $this->connection->execute($prep, array($this->unsuper));

        return 'InstaInExPage.php?hierarchy=1';
    }

    protected function post_super_handler()
    {
        $plugin = $this->config->get('PLUGIN_SETTINGS');
        $table = $plugin['InstaCartDB'] . $this->connection->sep() . 'InstaSupers';
        $prep = $this->connection->prepare("SELECT instaSuperID FROM {$table} WHERE instaSuperID=?");
        if (!$this->connection->getValue($prep, array($this->super))) {
            $ins = $this->connection->prepare("INSERT INTO {$table} (instaSuperID) VALUES (?)");
            $this->connection->execute($ins, array($this->super));
        }

        return 'InstaInExPage.php?hierarchy=1';
    }

    protected function get_undept_handler()
    {
        $plugin = $this->config->get('PLUGIN_SETTINGS');
        $table = $plugin['InstaCartDB'] . $this->connection->sep() . 'InstaDepts';
        $prep = $this->connection->prepare("DELETE FROM {$table} WHERE instaDeptID=?");
        $res = $this->connection->execute($prep, array($this->undept));

        return 'InstaInExPage.php?hierarchy=1';
    }

    protected function post_dept_handler()
    {
        $plugin = $this->config->get('PLUGIN_SETTINGS');
        $table = $plugin['InstaCartDB'] . $this->connection->sep() . 'InstaDepts';
        $prep = $this->connection->prepare("SELECT instaDeptID FROM {$table} WHERE instaDeptID=?");
        if (!$this->connection->getValue($prep, array($this->dept))) {
            $ins = $this->connection->prepare("INSERT INTO {$table} (instaDeptID) VALUES (?)");
            $this->connection->execute($ins, array($this->dept));
        }

        return 'InstaInExPage.php?hierarchy=1';
    }

    protected function get_unsub_handler()
    {
        $plugin = $this->config->get('PLUGIN_SETTINGS');
        $table = $plugin['InstaCartDB'] . $this->connection->sep() . 'InstaSubs';
        $prep = $this->connection->prepare("DELETE FROM {$table} WHERE instaSubID=?");
        $res = $this->connection->execute($prep, array($this->unsub));

        return 'InstaInExPage.php?hierarchy=1';
    }

    protected function post_sub_handler()
    {
        $plugin = $this->config->get('PLUGIN_SETTINGS');
        $table = $plugin['InstaCartDB'] . $this->connection->sep() . 'InstaSubs';
        $prep = $this->connection->prepare("SELECT instaSubID FROM {$table} WHERE instaSubID=?");
        if (!$this->connection->getValue($prep, array($this->sub))) {
            $ins = $this->connection->prepare("INSERT INTO {$table} (instaSubID) VALUES (?)");
            $this->connection->execute($ins, array($this->sub));
        }

        return 'InstaInExPage.php?hierarchy=1';
    }

    protected function post_id_handler()
    {
        $plugin = $this->config->get('PLUGIN_SETTINGS');
        $table = $plugin['InstaCartDB'] . $this->connection->sep() . 'InstaExcludes';
        if ($plugin['InstaCartMode']) {
            $table = $plugin['InstaCartDB'] . $this->connection->sep() . 'InstaIncludes';
        }
        $upc = BarcodeLib::padUPC($this->id);
        $prep = $this->connection->prepare("SELECT upc FROM {$table} WHERE upc=?");
        if (!$this->connection->getValue($prep, array($upc))) {
            $ins = $this->connection->prepare("INSERT INTO $table (upc) VALUES (?)");
            $this->connection->execute($ins, array($upc));
        }

        return 'InstaInExPage.php';
    }

    protected function delete_id_handler()
    {
        $plugin = $this->config->get('PLUGIN_SETTINGS');
        $table = $plugin['InstaCartDB'] . $this->connection->sep() . 'InstaExcludes';
        if ($plugin['InstaCartMode']) {
            $table = $plugin['InstaCartDB'] . $this->connection->sep() . 'InstaIncludes';
        }
        $upc = BarcodeLib::padUPC($this->id);
        $prep = $this->connection->prepare("DELETE FROM {$table} WHERE upc=?");
        $this->connection->execute($prep, array($upc));

        return 'InstaInExPage.php';
    }

    protected function get_view()
    {
        $this->addScript('../../../src/javascript/chosen/chosen.jquery.min.js');
        $this->addCssFile('../../../src/javascript/chosen/bootstrap-chosen.css');
        $this->addOnloadCommand("\$('select.chosen:visible').chosen();\n");
        $this->addOnloadCommand("\$('.nav-tabs a').on('shown.bs.tab', function() {\$('select.chosen:visible').chosen();});\n");
        $this->addOnloadCommand("\$('#form-in').focus();");
        $iactive = 'active';
        $dactive = '';
        if (FormLib::get('hierarchy')) {
            $iactive = '';
            $dactive = 'active';
        }

        $plugin = $this->config->get('PLUGIN_SETTINGS');
        if ($plugin['InstaCartMode']) {
            $itemTable = $this->itemTable($plugin['InstaCartDB'] . $this->connection->sep() . 'InstaIncludes', 'Included Items');
            $alert = "Opt-in Mode. Only items listed here will be submitted.";
        } else {
            $itemTable = $this->itemTable($plugin['InstaCartDB'] . $this->connection->sep() . 'InstaExcludes', 'Excluded Items');
            $alert = "Opt-out Mode. Items will be listed unless they're excluded indivudally or via department hierarchy";
        }
        $deptTable = $this->departmentTables();

        $ret = <<<HTML
<div class="alert alert-info">{$alert}</div>
<div>
<ul class="nav nav-tabs" role="tablist">
    <li role="presentation" class="{$iactive}"><a href="#items" aria-controls="items" role="tab" data-toggle="tab">Items</a></li>
    <li role="presentation" class="{$dactive}"><a href="#global" aria-controls="global" role="tab" data-toggle="tab">Department Hierarchy</a></li>
</ul>
<div class="tab-content">
    <div role="tabpanel" class="tab-pane {$iactive}" id="items">
        <form method="post" action="InstaInExPage.php">
        <p>
            <div class="input-group">
                <span class="input-group-addon">UPC</span>
                <input type="text" name="id" class="form-control" id="form-in" />
                <span class="input-group-btn">
                    <button type="submit" class="btn btn-default">Add</button>
                </span>
            </div>
        </p>
        </form>
        <hr />
        {$itemTable}
    </div>
    <div role="tabpanel" class="tab-pane {$dactive}" id="global">
        {$deptTable}
    </div>
</div>
</div>
HTML;
        return $ret;
    }

    private function departmentTables()
    {
        $plugin = $this->config->get('PLUGIN_SETTINGS');
        $ret = '<div class="panel panel-default">
            <div class="panel-heading">Super Departments</div>
            <div class="panel-body">
                <table class="table table-bordered table-striped">';

        $res = $this->connection->query('SELECT
                s.superID, s.super_name
            FROM superDeptNames AS s
                INNER JOIN ' . $plugin['InstaCartDB'] . $this->connection->sep() . 'InstaSupers AS i
                    ON s.superID=i.instaSuperID
            ORDER BY s.super_name');
        while ($row = $this->connection->fetchRow($res)) {
            $ret .= sprintf('<tr><td>%s</td><td><a href="?unsuper=%d">%s</a></td></tr>',
                $row['super_name'], $row['superID'], FannieUI::deleteIcon());
        }
        $ret .= '</table>';
        $supers = new MasterSuperDeptsModel($this->connection);
        $opts = $supers->toOptions();
        $ret .= '<form method="post">
            <div class="form-group">
                <label>Add Super Department</label>
                <select name="super" class="form-control chosen">' . $opts . '</select>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-default">Add</button>
            </div>
            </form>';
        
        $ret .= '</div></div>';

        $ret .= '<div class="panel panel-default">
            <div class="panel-heading">Departments</div>
            <div class="panel-body">
                <table class="table table-bordered table-striped">';

        $res = $this->connection->query('SELECT
                d.dept_no, d.dept_name, m.super_name
            FROM departments AS d
                INNER JOIN ' . $plugin['InstaCartDB'] . $this->connection->sep() . 'InstaDepts AS i
                    ON d.dept_no=i.instaDeptID
                LEFT JOIN MasterSuperDepts AS m ON d.dept_no=m.dept_ID
            ORDER BY d.dept_name');
        while ($row = $this->connection->fetchRow($res)) {
            $ret .= sprintf('<tr><td>%s: %s</td><td><a href="?undept=%d">%s</a></td></tr>',
                $row['super_name'], $row['dept_name'], $row['dept_no'], FannieUI::deleteIcon());
        }
        $ret .= '</table>';
        $depts = new DepartmentsModel($this->connection);
        $opts = $depts->toOptions();
        $ret .= '<form method="post">
            <div class="form-group">
                <label>Add Department</label>
                <select name="dept" class="form-control chosen">' . $opts . '</select>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-default">Add</button>
            </div>
            </form>';
        
        $ret .= '</div></div>';

        $ret .= '<div class="panel panel-default">
            <div class="panel-heading">Sub Departments</div>
            <div class="panel-body">
                <table class="table table-bordered table-striped">';

        $res = $this->connection->query('SELECT
                s.subdept_no, s.subdept_name,
                d.dept_name, m.super_name
            FROM subdepts AS s
                INNER JOIN ' . $plugin['InstaCartDB'] . $this->connection->sep() . 'InstaSubs AS i
                    ON s.subdept_no=i.instaSubID
                LEFT JOIN departments AS d ON s.dept_ID=d.dept_no
                LEFT JOIN MasterSuperDepts AS m ON s.dept_ID=m.dept_ID
            ORDER BY s.subdept_name');
        while ($row = $this->connection->fetchRow($res)) {
            $ret .= sprintf('<tr><td>%s: %s: %s</td><td><a href="?unsub=%d">%s</a></td></tr>',
                $row['super_name'], $row['dept_name'], $row['subdept_name'], $row['subdept_no'], FannieUI::deleteIcon());
        }
        $ret .= '</table>';
        $subs = new SubDeptsModel($this->connection);
        $opts = $subs->toOptions();
        $ret .= '<form method="post">
            <div class="form-group">
                <label>Add Sub Department</label>
                <select name="sub" class="form-control chosen">' . $opts . '</select>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-default">Add</button>
            </div>
            </form>';
        
        $ret .= '</div></div>';

        return $ret;
    }

    private function itemTable($baseTable, $header)
    {
        $ret = '<h3>' . $header . '</h3>';
        $ret .= '<table class="table table-bordered table-striped small">
                <thead><th>UPC</th><th>Brand</th><th>Item</th><th>&nbsp;</th></thead>
                <tbody>';
        $res = $this->connection->query("
            SELECT i.upc,
                " . ItemText::longBrandSQL() . ",
                " . ItemText::longDescriptionSQL() . "
            FROM {$baseTable} AS i
                " . DTrans::joinProducts('i', 'p', 'INNER') . "
                LEFT JOIN productUser AS u ON i.upc=u.upc
            ORDER BY brand, description");
        while ($row = $this->connection->fetchRow($res)) {
            $ret .= sprintf('<tr>
                    <td><a href="../../../item/ItemEditorPage.php?searchupc=%s">%s</a></td>
                    <td>%s</td><td>%s</td>
                    <td><a href="InstaInExPage.php?_method=delete&id=%s">%s</a></td>
                    </tr>',
                    $row['upc'], $row['upc'],
                    $row['brand'], $row['description'],
                    $row['upc'], FannieUI::deleteIcon()
            );
        }

        $ret .= '</tbody></table>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

