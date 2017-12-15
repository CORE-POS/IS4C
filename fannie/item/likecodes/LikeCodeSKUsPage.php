<?php

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class LikeCodeSKUsPage extends FannieRESTfulPage
{
    protected $title = 'Like Code SKUs';
    protected $header = 'Like Code SKus';

    private function getItems($vendorID)
    {
        $prep = $this->connection->prepare("
            SELECT sku, description
            FROM vendorItems
            WHERE vendorID=?
            ORDER BY description");
        $res = $this->connection->execute($prep, array($vendorID));
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $data[$row['sku']] = $row['description'];
        }

        return $data;
    }

    private function getOptions($data, $current)
    {
        $opt = '';
        foreach ($data as $sku => $item) {
            $opt .= sprintf('<option %s value="%s">%s %s</option>',
                $sku == $current ? 'selected' : '',
                $sku, $sku, $item);
        }

        return $opt;
    }

    protected function get_view()
    {
        $items = array(
            25 => $this->getItems(25),
            28 => $this->getItems(28),
            136 => $this->getItems(136),
        );
        $query = "SELECT l.likeCode,
                l.likeCodeDesc,
                m.sku,
                v.description,
                m.vendorID
            FROM likeCodes AS l
                LEFT JOIN VendorLikeCodeMap AS m ON l.likeCode=m.likeCode
                LEFT JOIN vendorItems AS v ON m.vendorID=v.vendorID AND m.sku=v.sku
            ORDER BY l.likeCode, m.vendorID";
        $res = $this->connection->query($query);
        $map = array();
        while ($row = $this->connection->fetchRow($res)) {
            $code = $row['likeCode'];
            if (!isset($map[$code])) {
                $map[$code] = array('skus'=>array(), 'name'=>$row['likeCodeDesc']);
            }
            if ($row['sku']) {
                $map[$code]['skus'][$row['vendorID']] = $row;
            }
        }
        $tableBody = '';
        foreach ($map as $lc => $data) {
            if (count($data['skus']) == 0) continue;
            $tableBody .= "<tr><td>{$lc}</td><td>{$data['name']}</td>";
            foreach (array(25, 28, 136) as $vID) {
                if (isset($data['skus'][$vID])) {
                    $tableBody .= "<td><input type=\"text\" name=\"sku[]\" 
                        value=\"{$data['skus'][$vID]['sku']} {$data['skus'][$vID]['description']}\"
                        class=\"form-control input-sm sku-field$vID\" /></td>";
                } else {
                    $tableBody .= '<td></td>';
                }
            }
            $tableBody .= '</tr>';
        }

        $this->addScript('skuMap.js');
        foreach (array(25, 28, 136) as $vID) {
            $this->addOnloadCommand("skuMap.autocomplete('.sku-field$vID', $vID);");
        }

        return <<<HTML
<table class="table table-bordered table-striped small">
<thead> 
    <tr>
        <th class="text-center">Like Code</th>
        <th class="text-center">Like Code</th>
        <th class="text-center">CPW</th>
        <th class="text-center">Alberts</th>
        <th class="text-center">RDW</th>
    </tr>
</thead>
    {$tableBody}
</table>
HTML;
    }
}

FannieDispatch::conditionalExec();

