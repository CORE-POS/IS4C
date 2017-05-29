<?php

include(__DIR__ . '/../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../classlib2.0/FannieAPI.php');
}

class MovementTagCheck extends FannieRESTfulPage
{
    protected $header = 'Movement Tag Checks';
    protected $title = 'Movement Tag Checks';
    public $description = '[Movement Tag Checks] lists information about when movement tags were printed and how movement has changed.';

    protected function get_id_view()
    {
        $ret = $this->get_view();
        $standard = FormLib::get('via', 0);
        $ret .= sprintf('<h3>Cutoff: %.2f%% %s</h3>', $this->id, $standard ? 'Case' : 'Item');
        $ret .= '<table class="table table-bordered table-striped">
            <tr>
                <th>UPC</th>
                <th>Brand</th>
                <th>Description</th>
                <th>Tag Date</th>
                <th>Tag Par</th>
                <th>Current Par</th>
                <th>% Change</th>
                <th>Change as % of Case</th>
            </tr>';
        $query = $this->connection->prepare('
            SELECT p.upc, p.brand, p.description,
                p.auto_par, m.lastPar, i.units, i.units, i.units, i.units,
                m.modified
            FROM MovementTags AS m
                INNER JOIN products AS p ON p.upc=m.upc AND p.store_id=m.storeID
                LEFT JOIN vendorItems AS i ON p.upc=i.upc AND p.default_vendor_id=i.vendorID
            WHERE ABS(m.lastPar - p.auto_par) / m.lastPar > ?
        ');
        $arg = $standard ? 0 : $this->id/100;
        $res = $this->connection->execute($query, array($arg));
        while ($row = $this->connection->fetchRow($res)) {
            $caseSize = $row['units'] ? $row['units'] : 1;
            $change = abs((7*$row['lastPar']) - (7*$row['auto_par']));
            if ($standard && ($change/$caseSize) < ($this->id/100)) {
                continue;
            }
            $ret .= sprintf('<tr>
                    <td><a href="ItemEditorPage.php?searchupc=%s">%s</a></td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%.2f</td>
                    <td>%.2f</td>
                    <td>%.2f%%</td>
                    <td>%.2f%%</td>
                    </tr>',
                    $row['upc'], $row['upc'],
                    $row['brand'], 
                    $row['description'], 
                    $row['modified'], 
                    7*$row['lastPar'], 
                    7*$row['auto_par'], 
                    (abs($row['lastPar'] - $row['auto_par']) / $row['lastPar']) * 100,
                    $change / $caseSize * 100
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
        <select name="via" class="form-control">
            <option value="0">Of single package movement</option>
            <option value="1">Of case movement</option>
        </select>
        <button type="submit" class="btn btn-default">Submit</button>
    </div>
</form>
HTML;
    }

    public function helpContent()
    {
        return '<p>Movement Tags are a special kind of shelftag layout that includes average
item movement. Since movement inevitably changes over time, this tool provides a list
of items that have changed more than a chosen threshold.</p>';
    }
}

FannieDispatch::conditionalExec();

