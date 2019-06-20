<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class RpLocalLCsPage extends FannieRESTfulPage
{
    protected $header = 'RP Local Like Codes';
    protected $title = 'RP Local Like Codes';

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
            $table .= sprintf('<tr><td>%d</td><td>%s</td></tr>', $row['likeCode'], $row['likeCodeDesc']);
        }

        return <<<HTML
<b>Locally-Available Like Codes</b>
<table class="table">
    {$table}
</table>
<form method="post" action="RpLocalLCsPage.php">
<div class="form-group">
    <label>Add Like Code</label>
    <input type="text" name="id" class="form-control" />
</div>
<div class="form-group">
    <button type="submit" class="btn btn-default">Add</button>
</div>
</form>
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

