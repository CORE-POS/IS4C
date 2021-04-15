<?php

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class LikeCodeBatchPage extends FannieRESTfulPage
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
        if ($this->store == 0) {
            $lcP = $this->connection->prepare('
                SELECT l.likeCode
                FROM LikeCodeActiveMap AS l
                    INNER JOIN upcLike AS u ON l.likeCode=u.likeCode
                    INNER JOIN products AS p ON u.upc=p.upc
                    INNER JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
                WHERE m.superID=?
                    AND l.inUse=1
                GROUP BY l.likeCode');
            $likeCodes = $this->connection->getAllValues($lcP, array($this->id));
        }
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
        $lcArgs = array($store, $this->id);
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
        } elseif ($store == 0) {
            $lcP = $this->connection->prepare('
                SELECT l.likeCode
                FROM LikeCodeActiveMap AS l
                    INNER JOIN upcLike AS u ON l.likeCode=u.likeCode
                    INNER JOIN products AS p ON u.upc=p.upc
                    INNER JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
                WHERE m.superID=?
                GROUP BY l.likeCode');
            $lcArgs = array($this->id);
        }
        $lcR = $this->connection->execute($lcP, $lcArgs);
        $allCodes = array();
        while ($lcW = $this->connection->fetchRow($lcR)) {
            $allCodes[] = $lcW['likeCode'];
        }
        $args = $store != 0 ? array($store) : array();
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
                v.units,
                {$sortFirst},
                multiVendor
            FROM likeCodes AS l
                LEFT JOIN VendorLikeCodeMap AS m ON l.likeCode=m.likeCode
                LEFT JOIN vendorItems AS v ON m.vendorID=v.vendorID AND m.sku=v.sku
                LEFT JOIN LikeCodeActiveMap AS a ON l.likeCode=a.likeCode " . ($store != 0 ? " AND a.storeID=? " : '') . "
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
        list($inStr, $args) = $this->connection->safeInClause($allCodes);
        $retailP = $this->connection->prepare("SELECT u.likeCode, MAX(p.normal_price) AS normal_price
            FROM upcLike AS u INNER JOIN products AS p ON u.upc=p.upc
            WHERE u.likeCode IN ({$inStr})
            GROUP BY u.likeCode");
        $retailR = $this->connection->execute($retailP, $args);
        $retailMap = array();
        while ($row = $this->connection->fetchRow($retailR)) {
            $retailMap[$row['likeCode']] = array(
                'normal' => $row['normal_price'],
                'sale' => 0,
                'isSale', false,
            );
        }
        $contribP = $this->connection->prepare("SELECT u.likeCode, SUM(percentageSuperDeptSales) AS weight
            FROM upcLike AS u
                INNER JOIN " . FannieDB::fqn('productSummaryLastQuarter', 'arch') . " AS q ON q.upc = u.upc
            WHERE u.likeCode IN ({$inStr})
                " . ($store == 0 ? '' : ' AND q.storeID=? ') . "
            GROUP BY u.likeCode");
        if ($store != 0) {
            $args[] = $store;
        }
        $contribR = $this->connection->execute($contribP, $args);
        $contribMap = array();
        while ($row = $this->connection->fetchRow($contribR)) {
            $contribMap[$row['likeCode']] = $row['weight'];
        }
        $tableBody = '';
        $category = '';
        $categories = '<option value="">Select category</option>';
        $filterCat = FormLib::get('cat');
        $filterPri = FormLib::get('pri');
        $priOpts = '<option value="0">Primary selection</option>
            <option value="1" ' . ($filterPri == 1 ? 'selected' : '') . '>Not available</option>
            <option value="2" ' . ($filterPri == 2 ? 'selected' : '') . '>Not selected</option>
            ';
        foreach ($map as $lc => $data) {
            $data['cat'] = strtoupper($data['cat']);
            if ($data['cat'] != $category) {
                if ($filterCat == '' || $filterCat == $data['cat']) {
                    $tableBody .= "<tr><th class=\"text-center info\" colspan=\"8\" align=\"center\">{$data['cat']}</th></tr>";
                }
                $category = $data['cat'];
                $categories .= sprintf('<option %s>%s</option>', ($filterCat == $data['cat'] ? 'selected' : ''), $data['cat']);
            }
            if ($filterCat && $data['cat'] != $filterCat) {
                $data['inUse'] = 0;
            }
            if ($filterPri == 1 && (!isset($data['skus'][$data['vendorID']]) || $data['skus'][$data['vendorID']]['description'] != null)) {
                $data['inUse'] = 0;
            }
            if ($filterPri == 2 && ($data['vendorID'] != null && isset($data['skus'][$data['vendorID']]))) {
                $data['inUse'] = 0;
            }
            $vendorID = $data['vendorID'];
            $cost = 0;
            $caseSize = 0;
            if (isset($data['skus'][$vendorID])) {
                $cost = $data['skus'][$vendorID]['cost'];
                $caseSize = $data['skus'][$vendorID]['units'];
            }
            if ($data['multi'] == 1) {
                $costs = array();
                foreach ($data['skus'] as $vendorInfo) {
                    if ($vendorInfo['cost'] > 0) {
                        $costs[] = $vendorInfo['cost'];
                    }
                }
                $cost = count($costs) == 0 ? 0 : array_sum($costs) / count($costs);
            }

            $inactiveClass = ($this->id != -1 && $data['inUse'] == 0) ? ' collapse inactiveRow warning' : '';
            $tableBody .= "<tr class=\"{$inactiveClass} price-row\"><td class=\"rowLC\"><a href=\"LikeCodeEditor.php?start={$lc}\">{$lc}</a></td>
                <td><a href=\"LikeCodeEditor.php?start={$lc}\">{$data['name']}</a></td>";
            $retail = $retailMap[$lc]['normal'];
            $tableBody .= sprintf('<td>%d</td><td>%.2f</td>
                <td class="cost">%.2f</td>
                <td><input type="text" size="5" class="price form-control input-sm" value="%.2f" 
                    onchange="lcBatch.recalculateMargin(this);" />
                    <input type="hidden" class="orig-price" value="%.2f" />
                    <input type="hidden" class="weight" value="%s" />
                    </td>
                <td class="margin">%.2f</td>',
                $caseSize, $cost * $caseSize, $cost, $retail, $retail, $contribMap[$lc], ($retail - $cost) / $retail);
            $tableBody .= '</tr>';
        }
        $this->addScript('lcBatch.js');
        $this->addOnloadCommand('lcBatch.enableFilters();');
        $this->addOnloadCommand('lcBatch.recalculateSheet();');

        return <<<HTML
<p class="form-inline">
<input type="hidden" name="store" class="filter-field" value="{$store}" />
<input type="hidden" name="id" class="filter-field" value="{$this->id}" />
Filter: 
<select name="cat" class="form-control filter-field">{$categories}</select>
<select name="pri" class="form-control filter-field">{$priOpts}</select>
<a href="LikeCodeSKUsPage.php?{$_SERVER['QUERY_STRING']}" class="btn btn-default">Shopping</a>
</p>
<p><label><input type="checkbox" {$internalDisable} onchange="lcBatch.toggleInact(this.checked);" /> Show inactive</label>
(Active {$counts['act']}, Inactive {$counts['inact']})
<a href="LikeCodeBatchPage.php?id={$this->id}&store={$store}&export=1" class="btn btn-default" $internalDisable>Export</a>
</p>
<div class="row">
<div class="col-sm-11">
<table class="table table-bordered table-striped small">
<thead> 
    <tr>
        <th class="text-center">Like Code</th>
        <th class="text-center">Like Code</th>
        <th class="text-center">Case Size</th>
        <th class="text-center">Case Cost</th>
        <th class="text-center">Unit Cost</th>
        <th class="text-center">Retail</th>
        <th class="text-center">Margin</th>
    </tr>
</thead>
    {$tableBody}
</table>
</div>
<div class="col-sm-1" style="position:fixed; right: 20px; top: 155px;">
    <b>Expected Margin</b>
    <div id="mainMargin"></div>
</div>
</div>
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

