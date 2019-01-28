<?php

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class LcInternalOrderGuide extends FannieRESTfulPage
{
    protected $title = 'Like Code Internal Order Guide';
    protected $header = 'Like Code Internal Order Guide';
    public $description = '[Like Code Order Guide] shows vendor preferences for internalUse like codes';

    protected function get_id_view()
    {
        $prep = $this->connection->prepare('
            SELECT l.likeCode,
                l.likeCodeDesc,
                l.sortInternal,
                v.vendorName
            FROM likeCodes AS l
                INNER JOIN LikeCodeActiveMap AS m ON l.likeCode=m.likeCode
                LEFT JOIN vendors AS v ON l.preferredVendorID=v.vendorID
            WHERE m.storeID=?
                AND m.internalUse=1
            ORDER BY l.sortInternal,
                l.likeCodeDesc');
        $res = $this->connection->execute($prep, array($this->id));

        $upcP = $this->connection->prepare('SELECT upc FROM upcLike WHERE likeCode=?');
        $costP = $this->connection->prepare('SELECT cost FROM products WHERE upc=?');
        $ret = '<table class="table table-bordered table-striped">
            <tr><th>#</th><th>Item</th><th>Vendor</th><th>Cost</th></tr>';
        $lastCat = '';
        while ($row = $this->connection->fetchRow($res)) {
            if ($row['sortInternal'] != $lastCat) {
                $ret .= sprintf('<tr><td colspan="4" class="alert-warning">%s</td></tr>', $row['sortInternal']);
            }
            $lastCat = $row['sortInternal'];
            $upc = $this->connection->getValue($upcP, array($row['likeCode']));
            $cost = $this->connection->getValue($costP, array($upc));
            $ret .= sprintf('<tr><td><a href="LikeCodeEditor.php?start=%d">%d</a></td>
                <td>%s</td><td>%s</td><td>%.3f</td></tr>',
                $row['likeCode'],
                $row['likeCode'],
                $row['likeCodeDesc'],
                $row['vendorName'],
                $cost);
        }
        $ret .= '</table>';

        return $ret;
    }

    protected function get_view()
    {
        $stores = FormLib::storePicker('id');
        return <<<HTML
<form method="get">
    <div class="form-group">
        <label>Store</label>
        {$stores['html']}
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Continue</button>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

