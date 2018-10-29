<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class RpImport extends FannieRESTfulPage
{
    protected $header = 'RP Import';
    protected $title = 'RP Import';

    protected function post_view()
    {
        $items = array();
        foreach (explode("\n", FormLib::get('in')) as $line) {
            if (preg_match('/(\d+)\](.)\[(.+){(.+)}(.+)\|(.+)_/', $line, $matches)) {
                list($type,$origin) = explode('\\', $matches[5]);
                $items[] = array(
                    'lc' => $matches[1],
                    'organic' => strtolower($matches[2]) == 'c' ? true : false,
                    'name' => $matches[3],
                    'price' => $matches[4],
                    'scale' => strtolower($type) == 'lb' ? true : false,
                    'origin' => $origin,
                    'vendor' => $matches[6],
                );
            }
        }

        $dbc = $this->connection;
        $lcP = $dbc->prepare('UPDATE likeCodes SET organic=?, preferredVendorID=? WHERE likeCode=?');
        $orgP = $dbc->prepare('
            UPDATE upcLike AS u
                INNER JOIN products AS p ON u.upc=p.upc
            SET p.numflag = p.numflag | ?
            WHERE u.likeCode=?'); 
        $nonP = $dbc->prepare('
            UPDATE upcLike AS u
                INNER JOIN products AS p ON u.upc=p.upc
            SET p.numflag = p.numflag & ?
            WHERE u.likeCode=?'); 
        $orgBits = 1 << (17 - 1);
        $nonBits = 0xffffffff ^ $orgBits;

        $ret = '<table class="table table-bordered">
            <tr><th>LC</th><th>Name</th><th>Retail</th><th>Vendor</th><th>Origin</th><th>Organic</th><th>Scale</th></tr>';
        $dbc->startTransaction();
        foreach ($items as $i) {
            $ret .= sprintf('<tr><td>%d</td><td>%s</td><td>%.2f</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                $i['lc'], $i['name'], $i['price'], $i['vendor'], $i['origin'],
                ($i['organic'] ? 'Yes' : 'No'),
                ($i['scale'] ? 'Yes' : 'No')
            );
            $args = array(
                $i['organic'] ? 1 : 0,
                $this->vendorToID($i['vendor']),
                $i['lc'],
            );
            $dbc->execute($lcP, $args);
            if ($i['organic']) {
                $dbc->execute($orgP, array($orgBits, $i['lc']));
            } else {
                $dbc->execute($nonP, array($nonBits, $i['lc']));
            }
        }
        $ret .= '</table>';
        $dbc->commitTransaction();

        return $ret;
    }

    private function vendorToID($vendor)
    {
        switch (strtolower($vendor)) {
            case 'alberts':
                return 28;
            case 'cpw':
                return 25;
            case 'rdw':
                return 136;
            case 'unfi':
                return 1;
            case 'direct':
                return -2;
            default:
                return 0;
        }
    }

    protected function get_view()
    {
        return <<<HTML
<form method="post">
    <div class="form-group">
        <label>Excel Columns</label>
        <textarea name="in" rows="25" class="form-control"></textarea>
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default btn-core">Import</button>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

