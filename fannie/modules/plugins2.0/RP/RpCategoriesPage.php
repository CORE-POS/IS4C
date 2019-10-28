<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('RpOrderCategoriesModel')) {
    include(__DIR__ . '/RpOrderCategoriesModel.php');
}

class RpCategoriesPage extends FannieRESTfulPage
{
    protected $header = 'RP Categories';
    protected $title = 'RP Categories';
    protected $must_authenticate = true;

    protected function post_id_view()
    {
        $seq = 0;
        $this->connection->startTransaction();
        $prep = $this->connection->prepare("UPDATE RpOrderCategories SET seq=? WHERE rpOrderCategoryID=?");
        for ($i=0; $i<count($this->id); $i++) {
            $this->connection->execute($prep, array($seq, $this->id[$i]));
            $seq++;
        }
        $this->connection->commitTransaction();

        return $this->get_view();
    }

    protected function get_view()
    {
        $model = new RpOrderCategoriesModel($this->connection);
        $table = '';
        foreach ($model->find('seq') as $obj) {
            $table .= sprintf('<tr><td>%s<input type="hidden" name="id[]" value="%d" /></td></tr>',
                $obj->name(), $obj->rpOrderCategoryID());
        }
        $this->addOnloadCommand("\$('#catList').sortable();");

        return <<<HTML
<form method="post">
    <p>
        <button type="submit" class="btn btn-default">Save</button>
        &nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;
        <a class="btn btn-default" href="RpMenu.php">Main Menu</a>
    </p>
    <table class="table table-bordered table-striped">
        <thead><tr><th>Categories</th></tr></thead>
        <tbody id="catList">
        {$table}
        </tbody>
    </table>
    <p>
        <button type="submit" class="btn btn-default">Save</button>
        &nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;
        <a class="btn btn-default" href="RpMenu.php">Main Menu</a>
    </p>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

