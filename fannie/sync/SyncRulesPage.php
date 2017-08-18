<?php

use COREPOS\Fannie\API\lib\FannieUI;

include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class SyncRulesPage extends FannieRESTfulPage
{
    protected $title = "Fannie : Sync Rules";
    protected $header = "Custom Sync Rules";

    protected function post_handler()
    {
        try {
            $table = $this->form->table;
            $rule = $this->form->rule;
            $model = new TableSyncRulesModel($this->connection);
            $model->tableName($table);
            $model->rule($rule);
            $model->save();
        } catch (Exception $ex) {
        }

        return 'SyncRulesPage.php';
    }

    protected function delete_id_handler()
    {
        $model = new TableSyncRulesModel($this->connection);
        $model->tableName($this->id);
        $model->delete();

        return 'SyncRulesPage.php';
    }

    protected function get_view()
    {
        $model = new TableSyncRulesModel($this->connection);
        $mods = FannieAPI::listModules('COREPOS\\Fannie\\API\\data\\SyncSpecial');
        $mods = array_map(function($i) { return str_replace('\\', '-', $i); }, $mods);
        $tbody = '';
        foreach ($model->find('table') as $rule) {
            $tbody .= sprintf('<tr><td>%s</td><td>%s</td>
                <td><a class="btn btn-danger btn-xs" href="?_method=delete&id=%s">%s</a></tr>',
                $rule->tableName(), $rule->rule(), $rule->tableName(), FannieUI::deleteIcon());
        }
        $opts = array_reduce($mods, function($c, $i) { return "{$c}<option>{$i}</option>"; });

        return <<<HTML
<table class="table table-bordered">
    <thead><tr><th>Table</th><th>Rule</th><th>&nbsp;</th></tr>
    <tbody>
        {$tbody}
    </tbody>
</table>
<form method="post" action="SyncRulesPage.php">
    <div class="form-group">
        <label>Table</label>
        <input name="table" class="form-control" />
    </div>
    <div class="form-group">
        <label>Rule</label>
        <select name="rule" class="form-control">
            {$opts}
        </select>
    </div>
    <div class="form-group">
        <button class="btn btn-default btn-core" type="submit">Add Rule</button>
    </div>
</form>
HTML;
    }

    public function helpContent()
    {
        return '<p>Custom table synchronization rules are used to speed up data transfers
            between the server and lanes and/or to enforce store-specific logic such
            as only syncing items that are flagged as inUse.</p>
            <p>Table names are case sensitive and each table can only have on associated
            rule.</p>
            <p>If both the server and lanes are using MySQL then the <em>MySQLSync</em>
            option is the go-faster solution for large tables. The "products" and
            "custdata" tables will almost always need this (or a variant that\'s also
            fast) with real-world data volumes.</p>';
    }
}

FannieDispatch::conditionalExec();

