<?php

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class LikeCodeSKUsPage extends FannieRESTfulPage
{
    protected $title = 'Like Code SKUs';
    protected $header = 'Like Code SKUs';

    public function preprocess()
    {
        $this->addRoute('post<id><vendorID>',
            'post<id><vendorID><sku>',
            'post<id><multiVendor>'
        );

        return parent::preprocess();
    }

    protected function post_id_multiVendor_handler()
    {
        $prep = $this->connection->prepare('UPDATE likeCodes SET multiVendor=? WHERE likeCode=?');
        $this->connection->execute($prep, array($this->multiVendor, $this->id));
        echo 'Done';

        return false;
    }

    protected function post_id_vendorID_sku_handler()
    {
        $sku = trim($this->sku);
        $vID = $this->vendorID;
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

    protected function post_id_vendorID_handler()
    {
        $prep = $this->connection->prepare("
            UPDATE " . FannieDB::fqn('likeCodes', 'op') . "
            SET preferredVendorID=?
            WHERE likeCode=?");
        $res = $this->connection->execute($prep, array($this->vendorID, $this->id));
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

    protected function get_id_view()
    {
        $items = array(
            25 => $this->getItems(25),
            28 => $this->getItems(28),
            136 => $this->getItems(136),
        );
        $lcP = $this->connection->prepare('
            SELECT l.likeCode
            FROM LikeCodeActiveMap AS l
                INNER JOIN upcLike AS u ON l.likeCode=u.likeCode
                INNER JOIN products AS p ON u.upc=p.upc
                INNER JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
            WHERE l.storeID=?
                AND l.inUse=1
                AND m.superID=?
            GROUP BY l.likeCode');
        $sortFirst = 'sortRetail';
        $internalDisable = '';
        if ($this->id == -1) {
            $lcP = $this->connection->prepare('
                SELECT likeCode
                FROM LikeCodeActiveMap
                WHERE storeID=?
                    AND internalUse=1
                    AND ? IS NOT NULL');
            $sortFirst = 'sortInternal';
            $internalDisable = 'disabled';
        }
        $lcR = $this->connection->execute($lcP, array(FormLib::get('store'), $this->id));
        $allCodes = array();
        while ($lcW = $this->connection->fetchRow($lcR)) {
            $allCodes[] = $lcW['likeCode'];
        }
        list($inStr, $args) = $this->connection->safeInClause($allCodes);
        $query = $this->connection->prepare("SELECT l.likeCode,
                l.likeCodeDesc,
                m.sku,
                v.description,
                m.vendorID,
                v.cost,
                v.vendorDept,
                l.preferredVendorID,
                {$sortFirst},
                multiVendor
            FROM likeCodes AS l
                LEFT JOIN VendorLikeCodeMap AS m ON l.likeCode=m.likeCode
                LEFT JOIN vendorItems AS v ON m.vendorID=v.vendorID AND m.sku=v.sku
            WHERE l.likeCode IN ({$inStr})
            ORDER BY {$sortFirst}, l.likeCodeDesc, l.likeCode, m.vendorID");
        $res = $this->connection->execute($query, $args);
        $map = array();
        while ($row = $this->connection->fetchRow($res)) {
            $code = $row['likeCode'];
            if (!isset($map[$code])) {
                $map[$code] = array(
                    'skus'=>array(),
                    'name'=>$row['likeCodeDesc'],
                    'multi'=>$row['multiVendor'],
                    'vendorID'=>$row['preferredVendorID'],
                    'cat' => $row[$sortFirst],
                );
            }
            if ($row['sku']) {
                $map[$code]['skus'][$row['vendorID']] = $row;
            }
        }
        foreach (array_keys($map) as $lc) {
            $best = PHP_INT_MAX;
            $bestID = false;
            $skus = $map[$lc]['skus'];
            foreach (array_keys($map[$lc]['skus']) as $vendor) {
                if ($skus[$vendor]['cost'] < $best && $skus[$vendor]['vendorDept'] == 999999) {
                    $best = $skus[$vendor]['cost'];
                    $bestID = $vendor;
                }
            }
            if ($bestID) {
                $map[$lc]['skus'][$bestID]['best'] = true;
            }
        }
        $tableBody = '';
        $category = '';
        foreach ($map as $lc => $data) {
            if ($data['cat'] != $category) {
                $tableBody .= "<tr><th class=\"text-center info\" colspan=\"8\" align=\"center\">{$data['cat']}</th></tr>";
                $category = $data['cat'];
            }
            $checkMulti = $data['multi'] ? 'checked' : '';
            $tableBody .= "<tr><td class=\"rowLC\"><a href=\"LikeCodeEditor.php?start={$lc}\">{$lc}</a></td>
                <td><a href=\"LikeCodeEditor.php?start={$lc}\">{$data['name']}</a>
                <input type=\"checkbox\" {$checkMulti} {$internalDisable} class=\"pull-right\" 
                onchange=\"skuMap.setMulti({$lc}, this.checked);\"
                title=\"Blend Costs\"/></td>";
            foreach (array(28, 25, 136) as $vID) {
                if (isset($data['skus'][$vID])) {
                    $css = '';
                    $disableRadio = '';
                    if (isset($data['skus'][$vID]['best'])) {
                        $css = 'class="success"';
                    } elseif ($data['skus'][$vID]['vendorDept'] != 999999) {
                        $css = 'class="danger"';
                        $disableRadio = 'disabled';
                    }
                    $checkRadio = $vID == $data['vendorID'] ? 'checked' : '';
                    $tableBody .= "<td {$css}><input type=\"text\" name=\"sku[]\" 
                        value=\"{$data['skus'][$vID]['sku']} {$data['skus'][$vID]['description']}\"
                        title=\"{$data['skus'][$vID]['sku']} {$data['skus'][$vID]['description']}\"
                        class=\"form-control input-sm sku-field$vID\" /></td>
                        <td {$css}>\$<span class=\"skuCost{$vID}\">{$data['skus'][$vID]['cost']}</span>
                        <input name=\"pref{$lc}\" class=\"preferred{$vID}\" type=\"radio\" title=\"Preferred Vendor\" 
                            onclick=\"skuMap.setVendor({$lc},{$vID});\" {$checkRadio} {$disableRadio} {$internalDisable} /></td>";
                } else {
                    $tableBody .= '<td><input type="text" class="form-control input-sm sku-field' . $vID . '" /></td>
                        <td>$<span class="skuCost' . $vID . '"></span>
                        <input type="radio" disabled class="preferred' . $vID . '" name="pref' . $lc . '"
                            onclick="skuMap.setVendor(' . $lc . ',' . $vID . ')" />
                        </td>';
                }
            }
            $tableBody .= '</tr>';
        }

        $this->addScript('skuMap.js');
        foreach (array(25, 28, 136) as $vID) {
            $this->addOnloadCommand("skuMap.autocomplete('.sku-field$vID', $vID);");
            $this->addOnloadCommand("skuMap.unlink('.sku-field$vID', $vID);");
        }
        $updateP = $this->connection->prepare('SELECT MIN(modified) FROM vendorItems WHERE vendorDept=999999 AND vendorID=?');
        $alb = $this->connection->getValue($updateP, array(28));
        $cpw = $this->connection->getValue($updateP, array(25));
        $rdw = $this->connection->getValue($updateP, array(136));

        return <<<HTML
<table class="table table-bordered table-striped small">
<thead> 
    <tr>
        <th class="text-center">Like Code</th>
        <th class="text-center">Like Code</th>
        <th class="text-center" colspan="2">Alberts <span class="small">({$alb})</span></th>
        <th class="text-center" colspan="2">CPW <span class="small">({$cpw})</span></th>
        <th class="text-center" colspan="2">RDW <span class="small">({$rdw})</span></th>
    </tr>
</thead>
    {$tableBody}
</table>
HTML;
    }

    protected function get_view()
    {
        $model = new MasterSuperDeptsModel($this->connection);
        $opts = $model->toOptions(-999);
        $store = FormLib::storePicker();

        return <<<HTML
<form method="get">
    <div class="form-group">
        <label>Super Department</label>
        <select name="id" class="form-control">
            <option value="" selected>Select one</option>
            <option value="-1">Internal Use</option>
            {$opts}
        </select>
    </div>
    <div class="form-group">
        <label>Store</label>
        {$store['html']}
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default btn-core">Continue</button>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

