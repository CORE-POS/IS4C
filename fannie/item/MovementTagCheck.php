<?php

include(__DIR__ . '/../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../classlib2.0/FannieAPI.php');
}

class MovementTagCheck extends FannieRESTfulPage
{
    protected $header = 'Movement Tag Checks';
    protected $title = 'Movement Tag Checks';

    protected function get_id_view()
    {
        $ret = $this->get_view();
        $ret .= '<table class="table table-bordered table-striped">
            <tr>
                <th>UPC</th>
                <th>Brand</th>
                <th>Description</th>
                <th>Tag Par</th>
                <th>Current Par</th>
                <th>% Change</th>
            </tr>';
        $query = $this->connection->prepare('
            SELECT p.upc, p.brand, p.description,
                p.auto_par, m.lastPar
            FROM MovementTags AS m
                INNER JOIN products AS p ON p.upc=m.upc AND p.store_id=m.storeID
            WHERE ABS(m.lastPar - p.auto_par) / m.lastPar > ?
        ');
        $res = $this->connection->execute($query, array($this->id/100));
        while ($row = $this->connection->fetchRow($res)) {
            $ret .= sprintf('<tr>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%.2f</td>
                    <td>%.2f</td>
                    <td>%.2f</td>
                    </tr>',
                    $row['upc'], 
                    $row['brand'], 
                    $row['description'], 
                    7*$row['lastPar'], 
                    7*$row['auto_par'], 
                    (abs($row['lastPar'] - $row['auto_par']) / $row['lastPar'])
            );
        }
        $ret .= '</table>';

        return $ret;
    }

    protected function get_view()
    {
        return <<<HTML
<form method="get">
    <div class="form-group form-inline">
        <label>Change threshold</label>
        <div class="input-group">
            <input type="text" name="id" value="10" class="form-control">
            <span class="input-group-addon">%</span>
        </div>
        <button type="submit" class="btn btn-default">Submit</button>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

