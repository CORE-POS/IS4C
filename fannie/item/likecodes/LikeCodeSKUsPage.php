<?php

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class LikeCodeSKUsPage extends FannieRESTfulPage
{
    protected $title = 'Like Code SKUs';
    protected $header = 'Like Code SKus';

    protected function post_id_handler()
    {
        $sku = trim(FormLib::get('sku'));
        $vID = FormLib::get('vendorID');
        $dbc = $this->connection;

        $existsP = $dbc->prepare('SELECT likeCode FROM VendorLikeCodeMap WHERE likeCode=? AND vendorID=?');
        $exists = $dbc->getValue($existsP, array($this->id, $vID));
        if ($exists && empty($sku)) {
            $delP = $dbc->prepare('DELETE FROM VendorLikeCodeMap WHERE likeCode=? AND vendorID=?');
            $dbc->execute($delP, array($this->id, $vID));
        } elseif ($exists) {
            list($sku,) = explode(' ', $sku, 2);
            $upP = $dbc->prepare('UPDATE VendorLikeCodeMap SET sku=? WHERE likeCode=? AND vendorID=?');
            $dbc->execute($upP, array($sku, $this->id, $vID));
        } else {
            list($sku,) = explode(' ', $sku, 2);
            $insP = $dbc->prepare('INSERT INTO VendorLikeCodeMap (likeCode, vendorID, sku) VALUES (?, ?, ?)');
            $dbc->execute($insP, array($this->id, $vID, $sku));
        }
        echo 'Done';

        return false;
    }

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
                m.vendorID,
                v.cost
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
        foreach (array_keys($map) as $lc) {
            $best = 999999;
            $bestID = false;
            foreach (array_keys($map[$lc]['skus']) as $vendor) {
                if ($map[$lc]['skus'][$vendor]['cost'] < $best) {
                    $best = $map[$lc]['skus'][$vendor]['cost'];
                    $bestID = $vendor;
                }
            }
            if ($bestID) {
                $map[$lc]['skus'][$bestID]['best'] = true;
            }
        }
        $tableBody = '';
        foreach ($map as $lc => $data) {
            if (count($data['skus']) == 0) continue;
            $tableBody .= "<tr><td class=\"rowLC\">{$lc}</td><td>{$data['name']}</td>";
            foreach (array(25, 28, 136) as $vID) {
                if (isset($data['skus'][$vID])) {
                    $css = isset($data['skus'][$vID]['best']) ? 'class="success"' : '';
                    $tableBody .= "<td {$css}><input type=\"text\" name=\"sku[]\" 
                        value=\"{$data['skus'][$vID]['sku']} {$data['skus'][$vID]['description']}\"
                        title=\"{$data['skus'][$vID]['sku']} {$data['skus'][$vID]['description']}\"
                        class=\"form-control input-sm sku-field$vID\" /></td>
                        <td {$css}>\$<span class=\"skuCost{$vID}\">{$data['skus'][$vID]['cost']}</span></td>";
                } else {
                    $tableBody .= '<td><input type="text" class="form-control input-sm sku-field' . $vID . '" /></td>
                        <td>$<span class="skuCost' . $vID . '"></span></td>';
                }
            }
            $tableBody .= '</tr>';
        }

        $this->addScript('skuMap.js');
        foreach (array(25, 28, 136) as $vID) {
            $this->addOnloadCommand("skuMap.autocomplete('.sku-field$vID', $vID);");
            $this->addOnloadCommand("skuMap.unlink('.sku-field$vID', $vID);");
        }

        return <<<HTML
<table class="table table-bordered table-striped small">
<thead> 
    <tr>
        <th class="text-center">Like Code</th>
        <th class="text-center">Like Code</th>
        <th class="text-center" colspan="2">CPW</th>
        <th class="text-center" colspan="2">Alberts</th>
        <th class="text-center" colspan="2">RDW</th>
    </tr>
</thead>
    {$tableBody}
</table>
HTML;
    }
}

FannieDispatch::conditionalExec();

