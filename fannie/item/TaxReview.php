<?php

include(__DIR__ . '/../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../classlib2.0/FannieAPI.php');
}

class TaxReview extends FannieRESTfulPage
{
    protected $header = 'Tax Review';
    protected $title = 'Tax Review';

    protected function get_id_view()
    {
        $sort = FormLib::get('sort');
        $sort = $sort == 'Newest' ? 'MAX(created)' : 'SUM(auto_par)';
        $exc = FormLib::get('exc');
        $exc = $exc == 'Yes' ? 'long_text IS NOT NULL AND long_text = \'\'' : '1=1';

        $prep = $this->connection->prepare("
            SELECT p.upc,
                MAX(p.brand) AS brand,
                MAX(p.description) AS description,
                MAX(p.created) AS created,
                SUM(p.auto_par) AS ttl
            FROM products AS p
                LEFT JOIN productUser AS u ON p.upc=u.upc
            WHERE p.department=?
                AND {$exc}
            GROUP BY p.upc
            ORDER BY {$sort} DESC");
        $res = $this->connection->execute($prep, array($this->id));
        $table = '';
        while ($row = $this->connection->fetchRow($res)) {
            $table .= sprintf('<tr>
                <td><a href="ItemEditorPage.php?searchupc=%s">%s</td>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                </tr>',
                $row['upc'], $row['upc'],
                $row['brand'],
                $row['description'],
                $row['created'],
                $row['ttl']);
        }

        return <<<HTML
<table class="table table-bordered table-striped">
    {$table}
</table>
HTML;
    }

    protected function get_view()
    {
        $depts = new DepartmentsModel($this->connection);
        $dOpts = $depts->toOptions();
        $this->addScript('../src/javascript/chosen/chosen.jquery.min.js');
        $this->addCssFile('../src/javascript/chosen/bootstrap-chosen.css');
        $this->addOnloadCommand("\$('select.chosen').chosen({search_contains: true});");

        return <<<HTML
<form method="get">
    <div class="form-group">
        <label>Department</label>
        <select name="id" class="form-control chosen">{$dOpts}</select>
    </div>
    <div class="form-group">
        <label>Sort by</label>
        <select name="sort" class="form-control">
            <option>Newest</option>
            <option>Sales</option>
        </select>
    </div>
    <div class="form-group">
        <label>Exclude if already has ingredients</label>
        <select name="exc" class="form-control">
            <option>Yes</option>
            <option>No</option>
        </select>
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Get Items</button>
    </div>
</form>
HTML;
    }

}

FannieDispatch::conditionalExec();

