<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

if (!class_exists('MenuScreensModel')) {
    include(__DIR__ . '/models/MenuScreensModel.php');
}

class MSEditor extends FannieRESTfulPage
{
    protected $header = 'Menu Screen Editor';
    protected $title = 'Menu Screen Editor';

    public $description = '[Menu Screen Editor] creates baseline screens';

    protected function post_id_handler()
    {
        $model = new MenuScreensModel($this->connection);
        $model->name(FormLib::get('name'));
        $model->columnCount(FormLib::get('cols'));
        $model->layout(FormLib::get('layout'));
        if ($this->id) {
            $model->menuScreenID($this->id);
            $model->save();
        } else {
            $this->id = $model->save();
        }

        return 'MSEditor.php?id=' . $this->id;
    }

    protected function get_id_view()
    {
        $model = new MenuScreensModel($this->connection);
        $model->menuScreenID($this->id);
        $model->load();
        $ms = $model->toStdClass();

        $this->addCssFile('../DBA/node_modules/codemirror/lib/codemirror.css');
        $this->addScript('../DBA/node_modules/codemirror/lib/codemirror.js');
        $this->addScript('../DBA/node_modules/codemirror//addon/mode/multiplex.js');
        $this->addScript('../DBA/node_modules/codemirror/mode/htmlembedded/htmlembedded.js');
        $this->addScript('../DBA/node_modules/codemirror/mode/css/css.js');
        $this->addScript('../DBA/node_modules/codemirror/mode/xml/xml.js');
        $this->addScript('../DBA/node_modules/codemirror/mode/javascript/javascript.js');
        $this->addScript('../DBA/node_modules/codemirror/mode/htmlmixed/htmlmixed.js');
        $this->addOnloadCommand('CodeMirror.fromTextArea(document.getElementById("queryTA"), { lineNumbers: true, mode: "htmlembedded" });');

        return <<<HTML
<form method="post" action="MSEditor.php">
<div class="form-group">
    <label>Name</label>
    <input type="text" name="name" class="form-control" value="{$ms->name}" />
</div>
<div class="form-group">
    <label>Columns</label>
    <input type="number" class="form-control" min="1" max="6" step="1" name="cols" value="{$ms->columnCount}" />
</div>
<div class="form-group">
    <label>Layout</label>
    <textarea id="queryTA" class="form-control" name="layout" rows="10">{$ms->layout}</textarea>
</div>
<div class="form-group">
    <input type="hidden" name="id" value="{$this->id}" />
    <button type="submit" class="btn btn-default">Save Menu</button>
</div>
</form>
HTML;
    }

    protected function get_view()
    {
        $model = new MenuScreensModel($this->connection);
        $table = '';
        foreach ($model->find('name') as $obj) {
            $table .= sprintf('<tr><td>%s</td>
                <td><a href="MSEditor.php?id=%d">Edit Menu</td>
                <td><a href="MSItems.php?id=%d">Edit Menu Items</td>
                <td><a href="MSRender.php?id=%d">View Menu</td>
                </tr>',
                $obj->name(),
                $obj->menuScreenID(),
                $obj->menuScreenID(),
                $obj->menuScreenID()
            );
        }
        return <<<HTML
<table class="table">
{$table}
</table>
<p>
    <a href="MSEditor.php?id=0" class="btn btn-default">Create New Menu</a>
</p>
<p>
    <a href="MSDevices.php" class="btn btn-default">Devices</a>
</p>
HTML;
    }
}

FannieDispatch::conditionalExec();

