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
            'post<id><multiVendor>',
            'get<id><store><export>'
        );

        return parent::preprocess();
    }

    protected function get_id_store_export_handler()
    {
        $lcP = $this->connection->prepare('
            SELECT l.likeCode
            FROM LikeCodeActiveMap AS l
                INNER JOIN upcLike AS u ON l.likeCode=u.likeCode
                INNER JOIN products AS p ON u.upc=p.upc
                INNER JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
            WHERE l.storeID=?
                AND m.superID=?
                AND l.inUse=1
            GROUP BY l.likeCode');
        $likeCodes = $this->connection->getAllValues($lcP, array($this->store, $this->id));
        list($inStr, $args) = $this->connection->safeInClause($likeCodes);
        $query = "SELECT l.likeCode,
                l.likeCodeDesc,
                m.sku,
                v.description,
                m.vendorID,
                v.cost,
                v.vendorDept,
                l.preferredVendorID,
                v.units,
                v.size,
                l.sortRetail,
                n.vendorName,
                l.multiVendor
            FROM likeCodes AS l
                LEFT JOIN VendorLikeCodeMap AS m ON l.likeCode=m.likeCode AND m.vendorID=l.preferredVendorID
                LEFT JOIN vendorItems AS v ON m.vendorID=v.vendorID AND m.sku=v.sku
                LEFT JOIN vendors AS n ON l.preferredVendorID=n.vendorID
            WHERE l.likeCode IN ({$inStr})
            ORDER BY l.sortRetail, l.likeCodeDesc, l.likeCode, m.vendorID";
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, $args);

        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename=lc_skus_" . date('Y-m-d') . ".csv");
        header("Pragma: no-cache");
        header("Expires: 0");
        echo "LC,LC Description,Vendor,SKU,Item,Unit Cost,Unit Size,Case Size,OOS\r\n";
        while ($row = $this->connection->fetchRow($res)) {
            echo $row['likeCode'] . ",";
            echo '"' . $row['likeCodeDesc'] . '",';
            echo '"' . $row['vendorName'] . '",';
            echo '"' . $row['sku'] . '",';
            echo '"' . $row['description'] . '",';
            echo $row['cost'] . ",";
            echo '"' . $row['size'] . '",';
            echo '"' . $row['units'] . '",';
            echo ($row['vendorName'] != '' && $row['vendorDept'] != 999999) ? 'OOS' : '';
            echo "\r\n";
        }

        return false;
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
            293 => $this->getItems(293),
            292 => $this->getItems(292),
            136 => $this->getItems(136),
        );
        $store = FormLib::get('store');
        $lcP = $this->connection->prepare('
            SELECT l.likeCode
            FROM LikeCodeActiveMap AS l
                INNER JOIN upcLike AS u ON l.likeCode=u.likeCode
                INNER JOIN products AS p ON u.upc=p.upc
                INNER JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
            WHERE l.storeID=?
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
        $lcR = $this->connection->execute($lcP, array($store, $this->id));
        $allCodes = array();
        while ($lcW = $this->connection->fetchRow($lcR)) {
            $allCodes[] = $lcW['likeCode'];
        }
        $args = array($store);
        list($inStr, $args) = $this->connection->safeInClause($allCodes, $args);
        $query = $this->connection->prepare("SELECT l.likeCode,
                l.likeCodeDesc,
                m.sku,
                v.description,
                m.vendorID,
                v.cost,
                v.vendorDept,
                l.preferredVendorID,
                a.inUse,
                {$sortFirst},
                multiVendor
            FROM likeCodes AS l
                LEFT JOIN VendorLikeCodeMap AS m ON l.likeCode=m.likeCode
                LEFT JOIN vendorItems AS v ON m.vendorID=v.vendorID AND m.sku=v.sku
                LEFT JOIN LikeCodeActiveMap AS a ON l.likeCode=a.likeCode AND a.storeID=?
            WHERE l.likeCode IN ({$inStr})
            ORDER BY {$sortFirst}, l.likeCodeDesc, l.likeCode, m.vendorID");
        $res = $this->connection->execute($query, $args);
        $map = array();
        $counts = array('act'=>0, 'inact'=>0);
        while ($row = $this->connection->fetchRow($res)) {
            $code = $row['likeCode'];
            if (!isset($map[$code])) {
                $map[$code] = array(
                    'skus'=>array(),
                    'name'=>$row['likeCodeDesc'],
                    'multi'=>$row['multiVendor'],
                    'vendorID'=>$row['preferredVendorID'],
                    'cat' => $row[$sortFirst],
                    'inUse' => $row['inUse'],
                );
                $counts['act'] += ($row['inUse'] || $this->id == -1) ? 1 : 0;
                $counts['inact'] += (!$row['inUse'] || $this->id != -1) ? 1 : 0;
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
                if ($skus[$vendor]['cost'] < $best) {
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
            $inactiveClass = ($this->id != -1 && $data['inUse'] == 0) ? ' collapse inactiveRow warning' : '';
            $tableBody .= "<tr class=\"{$inactiveClass}\"><td class=\"rowLC\"><a href=\"LikeCodeEditor.php?start={$lc}\">{$lc}</a></td>
                <td><a href=\"LikeCodeEditor.php?start={$lc}\">{$data['name']}</a>
                <input type=\"checkbox\" {$checkMulti} {$internalDisable} class=\"pull-right\" 
                onchange=\"skuMap.setMulti({$lc}, this.checked);\"
                title=\"Blend Costs\"/></td>";
            foreach (array(292, 293, 136) as $vID) {
                if (isset($data['skus'][$vID])) {
                    $css = '';
                    $disableRadio = '';
                    if (isset($data['skus'][$vID]['best'])) {
                        $css = 'success';
                    }
                    $checkRadio = $vID == $data['vendorID'] ? 'checked' : '';
                    $tableBody .= "<td class=\"skuField{$vID} {$css}\"><input type=\"text\" name=\"sku[]\" 
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

        $this->addScript('skuMap.js?date=20180228');
        foreach (array(292, 293, 136) as $vID) {
            $this->addOnloadCommand("skuMap.autocomplete('.sku-field$vID', $vID);");
            $this->addOnloadCommand("skuMap.unlink('.sku-field$vID', $vID);");
        }
        $updateP = $this->connection->prepare('SELECT MIN(modified) FROM vendorItems WHERE vendorID=?');
        $alb = $this->connection->getValue($updateP, array(292));
        $cpw = $this->connection->getValue($updateP, array(293));
        $rdw = $this->connection->getValue($updateP, array(136));

        return <<<HTML
<p><label><input type="checkbox" {$internalDisable} onchange="skuMap.toggleInact(this.checked);" /> Show inactive</label>
(Active {$counts['act']}, Inactive {$counts['inact']})
<a href="LikeCodeSKUsPage.php?id={$this->id}&store={$store}&export=1" class="btn btn-default" $internalDisable>Export</a>
</p>
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

    public function helpContent()
    {
        return <<<HTML
<p>Select a super department and store to get started. <strong>Internal Use</strong> is a special
category for non-retail purchases</p>.
<p>The list shows each like code and associated items from each vendor. By default only active like
codes are displayed but the toggle at the top of the list will show inactive like codes, too. Inactive
rows are shaded yellow. Within the vendor columns a green highlight indicates the best current price.
A red highlight indicates the item was unavailable as of the most recent price file. The date of the
most recent price file update is listed at the top of the table in parenthesis after each vendor.</p>
<p>To add or alter an associated vendor item, just start typing and any matching items will appear.
The cost listed here is a unit cost - either per pound or per package. The button next to the cost
will set the preferred vendor for a given like code. A vendor can only be selected as preferred if
it has an item associated with the like code and that item is in stock. The checkbox in the like
code description's column enables or disables blended cost calculations.</p>
</p>
HTML;
    }
}

FannieDispatch::conditionalExec();

