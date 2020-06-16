<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class RpLocalLCsPage extends FannieRESTfulPage
{
    protected $header = 'Local Like Codes';
    protected $title = 'Local Like Codes';

    protected function delete_id_view()
    {
        $prep = $this->connection->prepare('DELETE FROM RpLocalLCs WHERE likeCode=?');
        $this->connection->execute($prep, array($this->id));

        return '<div class="alert alert-success">Like Code Deleted</div>'
            . $this->get_view();
    }

    protected function post_id_view()
    {
        $prep = $this->connection->prepare('INSERT INTO RpLocalLCs (likeCode) VALUES (?)');
        $this->connection->execute($prep, array($this->id));

        return '<div class="alert alert-success">Like Code Added</div>'
            . $this->get_view();
    }

    protected function post_view()
    {
        $this->connection->query('TRUNCATE TABLE RpLocalLCs');
        $prep = $this->connection->prepare('INSERT INTO RpLocalLCs (likeCode) VALUES (?)');
        $this->connection->startTransaction();
        $info = FormLib::get('lcs');
        foreach (explode("\n", $info) as $lc) {
            if (!is_numeric(trim($lc))) {
                continue;
            }
            $this->connection->execute($prep, array(trim($lc)));
        }
        $this->connection->commitTransaction();

        return '<div class="alert alert-success">Updated List</div>'
            . $this->get_view();
    }

    protected function get_view()
    {
        $res = $this->connection->query('SELECT r.likeCode, l.likeCodeDesc
            FROM RpLocalLCs AS r
                INNER JOIN likeCodes AS l ON r.likeCode=l.likeCode
            ORDER BY l.likeCodeDesc');
        $table = '';
        while ($row = $this->connection->fetchRow($res)) {
            $table .= sprintf('<tr><td>%d</td><td>%s</td>
                <td><a href="RpLocalLCsPage.php?_method=delete&id=%s">%s</a>
                </tr>',
                $row['likeCode'], $row['likeCodeDesc'],
                $row['likeCode'],
                COREPOS\Fannie\API\lib\FannieUI::deleteIcon()
            );
        }

        $model = new LikeCodesModel($this->connection);
        $opts = '<option value="">' . $model->toOptions();
        $this->addScript('../../../src/javascript/chosen/chosen.jquery.min.js');
        $this->addCssFile('../../../src/javascript/chosen/bootstrap-chosen.css');
        $this->addOnloadCommand("\$('select.chosen').chosen({search_contains: true});");

        return <<<HTML
<form method="post" action="RpLocalLCsPage.php">
<div class="form-group">
    <label>Add Like Code</label>
    <select name="id" class="form-control chosen">{$opts}</select>
</div>
<div class="form-group">
    <button type="submit" class="btn btn-default">Add</button>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <a href="RpDirectPage.php" class="btn btn-default">Direct Order Guide</a>
</div>
</form>
<b>Locally-Available Like Codes</b>
<table class="table">
    {$table}
</table>
<form method="post" action="RpLocalLCsPage.php">
<div class="form-group">
    <label>Replace List of Like Codes</label>
    <textarea class="form-control" rows="10" name="lcs"></textarea>
</div>
<div class="form-group">
    <button type="submit" class="btn btn-default">Replace</button>
</div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

