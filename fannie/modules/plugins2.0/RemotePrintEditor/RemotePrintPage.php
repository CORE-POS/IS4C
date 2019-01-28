<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class RemotePrintPage extends FannieRESTfulPage
{
    protected $header = 'Remote Print Editor';
    protected $title = 'Remote Print Editor';
    public $description = '[Remote Print Editor] sets UPCs and/or departments for remote printing';

    protected function post_id_handler()
    {
        $type = FormLib::get('type', 'u');
        $prep = $this->connection->prepare('INSERT INTO RemotePrint (identifier, type) VALUES (?, ?)');
        $args = array(BarcodeLib::padUPC($this->id), 'UPC');
        if ($type != 'u') {
            $args = array($this->id, 'Department');
        }
        $this->connection->execute($prep, $args);

        return 'RemotePrintPage.php?type=' . $type;
    }

    protected function delete_id_handler()
    {
        $prep = $this->connection->prepare('DELETE FROM RemotePrint WHERE remotePrintID=?');
        $this->connection->execute($prep, array($this->id));

        return 'RemotePrintPage.php';
    }

    protected function get_view()
    {
        $query = "
            SELECT r.*,
                CASE
                    WHEN p.description IS NOT NULL THEN p.description
                    ELSE d.dept_name
                END AS name
            FROM RemotePrint AS r
                " . DTrans::joinProducts('r', 'p', 'LEFT') . " AND r.type='UPC'
                LEFT JOIN departments AS d ON r.identifier=d.dept_no AND r.type<>'UPC'
            ORDER BY r.type, r.identifier";
        $query = str_replace('r.upc', 'r.identifier', $query);
        $res = $this->connection->query($query);
        $table = '';
        while ($row = $this->connection->fetchRow($res)) {
            $table .= sprintf('<tr><td>%s</td><td>%s</td><td><a href="?_method=delete&id=%d">%s</a></td></tr>',
                $row['identifier'],
                $row['name'],
                $row['remotePrintID'],
                COREPOS\Fannie\API\lib\FannieUI::deleteIcon()
            );
        }
        $upcSelect = 'selected';
        $deptSelect = '';
        if (FormLib::get('type') == 'd') {
            $upcSelect = '';
            $deptSelect = 'selected';
        }
        $this->addOnloadCommand("\$('#rpID').focus();");

        return <<<HTML
<form method="post" action="RemotePrintPage.php">
    <div class="form-inline">
        <select name="type" class="form-control">
            <option value="u" {$upcSelect}>UPC</option>
            <option value="d" {$deptSelect}>Department</option>
        </select>
        <input type="text" name="id" id="rpID" class="form-control" />
        <button type="submit" class="btn btn-default">Add Entry</button>
    </div>
</form>
<p>
<table class="table table-bordered table-striped">
    <tr><th>UPC/Department</th><th>Name</th><th>Delete</th></tr>
    {$table}
</table>
</p>
HTML;
    }
}

FannieDispatch::conditionalExec();

