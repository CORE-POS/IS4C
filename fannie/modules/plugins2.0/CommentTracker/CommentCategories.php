<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('\\FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class CommentCategories extends FannieRESTfulPage
{
    protected $header = 'Comment Categories';
    protected $title = 'Comment Categories';

    protected function post_id_handler()
    {
        $ids = FormLib::get('id');
        $names = FormLib::get('name');
        $nMethods = FormLib::get('notify');
        $nAddrs = FormLib::get('address');
        $new = FormLib::get('new');

        $settings = $this->config->get('PLUGIN_SETTINGS');
        $this->connection->selectDB($settings['CommentDB']);
        $model = new CategoriesModel($this->connection);

        if (trim($new) !== '') {
            $model->name($new);
            $model->save();     
        }

        for ($i=0; $i<count($ids); $i++) {
            $model->categoryID($ids[$i]);
            $model->name($names[$i]);
            $model->notifyMethod($nMethods[$i]);
            $model->notifyAddress($nAddrs[$i]);
            $model->save();
        }

        return 'CommentCategories.php';
    }

    protected function delete_id_handler()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $this->connection->selectDB($settings['CommentDB']);
        $model = new CategoriesModel($this->connection);
        $model->categoryID($this->id);
        $model->delete();

        return 'CommentCategories.php';

    }

    protected function get_view()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $this->connection->selectDB($settings['CommentDB']);
        $model = new CategoriesModel($this->connection);

        $rows = '';
        foreach ($model->find('name') as $obj) {
            $rows .= sprintf('<tr><td><input type="hidden" name="id[]" value="%d" />
                <input type="text" class="form-control" name="name[]" value="%s" /></td>
                <td><input type="text" class="form-control" name="notify[]" value="%s" /></td>
                <td><input type="text" class="form-control" name="address[]" value="%s" /></td>
                <td><a href="?_method=delete&id=%d">%s</a>
                </tr>',
                $obj->categoryID(),
                $obj->name(),
                $obj->notifyMethod(),
                $obj->notifyAddress(),
                $obj->categoryID(),
                COREPOS\Fannie\API\lib\FannieUI::deleteIcon()
            );
        }

        return <<<HTML
<form method="post">
    <table class="table table-bordered">
    <thead>
        <tr><th>Name</th><th>Notification Method</th><th>Notification Address(es)</th></tr>
    </thead>
    <tbody>
        {$rows}
    </tbody>
</table>
<p>
    <div class="form-group">
        <label>Create new category</label>
        <input type="text" name="new" class="form-control" />
    </div>
</p>
<p>
    <button class="btn btn-default btn-core">Save</button>
</p>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

