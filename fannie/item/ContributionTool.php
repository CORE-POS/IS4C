<?php

require(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../classlib2.0/FannieAPI.php');
}

class ContributionTool extends FannieRESTfulPage
{
    protected $header = 'Contribution Tool';
    protected $title = 'Contribution Tool';

    public $description = '[Contribution Tool] explores product data and margin';

    public function preprocess()
    {
        $stores = FormLib::storePicker('store', false);
        $items = FormLib::get('items', 50);
        $vendors = FormLib::get('vendors', 10);
        $highlight = FormLib::get('highlight', 0);
        $superDept = FormLib::get('super', false);
        $store = FormLib::get('store', COREPOS\Fannie\API\lib\Store::getIdByIp());

        if (FormLib::get('itemExcel')) {
            $data = $this->getAllItems($store, $items, $superDept, $highlight);
            $out = array();
            foreach ($data as $name => $items) {
                $out[] = array($name);
                $out[] = array('Item', 'Name', 'Current Margin', '% Store Sales', '% Category Sales');
                foreach ($items as $row) {
                    $out[] = array(
                        $row['upc'],
                        $row['brand'] . ' ' . $row['description'],
                        $row['margin'] * 100,
                        $row['store'] * 100,
                        ($row['layer'] == 'dept' ? $row['dept'] : $row['super']) * 100,
                    );
                }
            }
            $ext = \COREPOS\Fannie\API\data\DataConvert::excelFileExtension();
            $ret = \COREPOS\Fannie\API\data\DataConvert::arrayToExcel($out);
            header('Content-Type: application/ms-excel');
            header('Content-Disposition: attachment; filename="contrib-items.' . $ext . '"');
            echo $ret;

            return false;
        } elseif (FormLib::get('basicExcel')) {
            $data = $this->getBasics($store, $superDept);
            $out = array();
            $label = $superDept !== false ? 'Category' : 'Store';
            $out[] = array('Name', '# of Items', '% of Items w/ Costs', '% of ' . $label . ' Sales', 'Expected Margin');
            foreach ($data as $row) {
                $out[] = array(
                    $row['name'],
                    $row['items'],
                    ($row['hasCost'] / $row['items']) * 100,
                    $row['promo'] * 100,
                    $row['percentage'] * 100,
                    $row['contribution'] * 100,
                );
            }
            $ext = \COREPOS\Fannie\API\data\DataConvert::excelFileExtension();
            $ret = \COREPOS\Fannie\API\data\DataConvert::arrayToExcel($out);
            header('Content-Type: application/ms-excel');
            header('Content-Disposition: attachment; filename="contrib-basics.' . $ext . '"');
            echo $ret;

            return false;
        } elseif (FormLib::get('all')) {
            header('Content-Type: application/ms-excel');
            header('Content-Disposition: attachment; filename="contrib-all-' . $store . '.csv"');
            $this->exportAll($store);

            return false;
        }

        return parent::preprocess();
    }

    private function exportAll($store)
    {
        $promoP = $this->connection->prepare('
            SELECT q.upc, SUM(total), SUM(saleTotal)
            FROM ' . FannieDB::fqn('productWeeklyLastQuarter', 'arch') . ' as q
            WHERE storeID=?
            GROUP BY upc');
        $promoR = $this->connection->execute($promoP, array($store));
        $promos = array();
        while ($row = $this->connection->fetchRow($promoR)) {
            $promos[$row['upc']] = ($row[2] / $row[1]) * 100;
        }
        $query = "SELECT q.upc,
                p.brand,
                p.description,
                COALESCE(v.vendorName, 'Unknown') AS vendorName,
                p.cost,
                p.normal_price,
                m.super_name,
                d.dept_name,
                p.price_rule_id,
                t.description AS ruleType,
                d.margin as deptMargin,
                a.margin AS vendorMargin,
                q.percentageStoreSales,
                q.percentageSuperDeptSales
            FROM " . FannieDB::fqn('productSummaryLastQuarter', 'arch') . " AS q
                INNER JOIN products AS p ON q.upc=p.upc AND q.storeID=p.store_id
                INNER JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
                INNER JOIN departments AS d ON p.department=d.dept_no
                LEFT JOIN vendors AS v ON p.default_vendor_id=v.vendorID
                LEFT JOIN PriceRules AS r ON p.price_rule_id=r.priceRuleID
                LEFT JOIN PriceRuleTypes AS t ON r.priceRuleTypeID=t.priceRuleTypeID
                LEFT JOIN vendorItems AS i ON p.upc=i.upc AND p.default_vendor_id=i.vendorID
                LEFT JOIN vendorDepartments AS a ON i.vendorDept=a.deptID AND i.vendorID=a.vendorID
            WHERE q.storeID=?
                AND p.cost <> 0
                AND p.normal_price <> 0";
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, array($store));
        echo "UPC,Brand,Description,Vendor,Category,Department,Price,Cost,Actual Margin,Target Margin,Diff,Rule,% Store Sales,% Category Sales,% Promo Sales\n";
        while ($row = $this->connection->fetchRow($res)) {
            printf('"%s","%s","%s","%s","%s","%s",%.2f,%.3f,',
                $row['upc'], $row['brand'], $row['description'], $row['vendorName'],
                $row['super_name'], $row['dept_name'], $row['normal_price'], $row['cost']);
            $target = $row['vendorMargin'] ? $row['vendorMargin'] : $row['deptMargin'];
            $actual = ($row['normal_price'] - $row['cost']) / $row['normal_price'];
            printf('%.2f,%.2f,%.2f,', $actual*100, $target*100, ($actual-$target)*100);
            $rule = 'n/a';
            if ($row['price_rule_id']) {
                $rule = 'Variable';
            }
            if ($row['ruleType']) {
                $rule = $row['ruleType'];
            }
            printf('"%s",%.3f,%.3f,%.2f',
                $rule, $row['percentageStoreSales']*100, $row['percentageSuperDeptSales']*100, $promos[$row['upc']]);
            echo "\n";
        }
    }

    private function getBasics($store, $super)
    {
        $query = "SELECT m.super_name AS name, MAX(m.superID) AS superID,
            SUM(percentageStoreSales) AS percentage,
            SUM(CASE WHEN p.cost <> 0 AND normal_price <> 0 THEN 1 ELSE 0 END) as hasCost,
            SUM(
                CASE WHEN p.cost=0 OR p.normal_price=0 THEN 0 
                ELSE ((p.normal_price-p.cost)/p.normal_price) * percentageSuperDeptSales 
            END) AS contribution,
            COUNT(q.upc) AS items
        FROM " . FannieDB::fqn('productSummaryLastQuarter', 'arch') . " AS q
            INNER JOIN products AS p ON q.upc=p.upc AND q.storeID=p.store_id
            INNER JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
            INNER JOIN departments AS d ON p.department=d.dept_no
            LEFT JOIN vendors AS v ON p.default_vendor_id=v.vendorID
        WHERE q.storeID=?
        GROUP BY m.super_name";
        $subQ = "SELECT SUM(total), SUM(saleTotal)
            FROM " . FannieDB::fqn('productWeeklyLastQuarter', 'arch') . " AS q
                INNER JOIN products AS p ON q.upc=p.upc AND q.storeID=p.store_id
                INNER JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
            WHERE q.storeID=? AND m.superID=?";
        $args = array($store);
        if ($super !== false) {
            $query = "SELECT d.dept_name AS name, MAX(p.department) AS superID,
                SUM(percentageSuperDeptSales) AS percentage,
                SUM(CASE WHEN p.cost <> 0 AND p.normal_price <> 0 THEN 1 ELSE 0 END) as hasCost,
                SUM(
                    CASE WHEN p.cost=0 OR p.normal_price=0 THEN 0 
                    ELSE ((p.normal_price-p.cost)/p.normal_price) * percentageDeptSales 
                END) AS contribution,
                COUNT(q.upc) AS items
            FROM " . FannieDB::fqn('productSummaryLastQuarter', 'arch') . " AS q
                INNER JOIN products AS p ON q.upc=p.upc AND q.storeID=p.store_id
                INNER JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
                INNER JOIN departments AS d ON p.department=d.dept_no
                LEFT JOIN vendors AS v ON p.default_vendor_id=v.vendorID
            WHERE q.storeID=?
                AND m.superID=?
            GROUP BY d.dept_name";
            $subQ = "SELECT SUM(total), SUM(saleTotal)
                FROM " . FannieDB::fqn('productWeeklyLastQuarter', 'arch') . " AS q
                    INNER JOIN products AS p ON q.upc=p.upc AND q.storeID=p.store_id
                WHERE q.storeID=? AND p.department=?";
            $args[] = $super;
        }

        $ret = array();
        $subP = $this->connection->prepare($subQ);
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, $args);
        while ($row = $this->connection->fetchRow($res)) {
            $sales = $this->connection->getRow($subQ, array($store, $row['superID']));
            $row['promo'] = $sales[1] / $sales[0];
            $ret[] = $row;
        }

        return $ret;
    }

    private function basicsHTML($data, $super)
    {
        $uri = $_SERVER['REQUEST_URI'];
        $uri .= strpos($uri, '?') ? '&basicExcel=1' : '?basicExcel=1';
        $ret = '<p><a href="' . $uri . '">Export this to Excel</a></p>';
        $label = $super !== false ? 'Category' : 'Store';
        $ret .= '<table class="table table-bordered">
            <tr><th>Name</th><th># of Items</th><th>% of Items w/ Costs</th><th>% of Promo Sales</th><th>% of ' . $label . ' Sales</th><th>Expected Margin</th></tr>';
        foreach ($data as $row) {
            if ($super === false) {
                $uri = $_SERVER['REQUEST_URI'];
                $uri .= strpos($uri, '?') ? '&super=' : '?super=';
                $row['name'] = sprintf('<a href="%s%d">%s</a>', $uri, $row['superID'], $row['name']);
            }
            $ret .= sprintf('<tr><td>%s</td><td>%d</td><td>%.2f</td>
                            <td>%.2f</td><td>%.2f</td><td>%.2f</td>',
                            $row['name'], $row['items'],
                            ($row['hasCost'] / $row['items']) * 100,
                            $row['promo'] * 100,
                            $row['percentage']*100, $row['contribution']*100
            );
        }
        $ret .= '</table>';

        return $ret;
    }

    private function getAllVendors($store, $limit, $super)
    {
        $query = "SELECT superID, super_name AS name, 0 AS dept_no
            FROM MasterSuperDepts
            GROUP BY superID, super_name
            ORDER BY super_name";
        $args = array();
        if ($super !== false) {
            $query = "SELECT dept_no, dept_name AS name, superID
                FROM departments AS d
                    INNER JOIN MasterSuperDepts AS m ON d.dept_no=m.dept_ID
                WHERE m.superID=?
                ORDER BY dept_no";
            $args[] = $super;
        }
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, $args);
        $ret = '<table class="table table-bordered">';
        while ($row = $this->connection->fetchRow($res)) {
            $ret .= sprintf('<tr class="info"><th colspan="4">%s</th></tr>',
                $row['name']);
            $ret .= $this->getVendors($store, $limit, $row['superID'], $row['dept_no']);
        }
        $ret .= '</table>';

        return $ret;
    }

    private function getVendors($store, $limit, $super, $dept)
    {
        if (!isset($this->vendorP)) {
            $query = "
                SELECT COALESCE(v.vendorName, 'Unknown') AS vendorName,
                    SUM(q.percentageStoreSales) AS store,
                    SUM(q.percentageSuperDeptSales) AS super,
                    SUM(q.percentageDeptSales) AS dept,
                    COUNT(q.upc) AS items
                FROM " . FannieDB::fqn('productSummaryLastQuarter', 'arch') . " AS q
                    INNER JOIN products AS p ON q.upc=p.upc AND q.storeID=p.store_id
                    INNER JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
                    INNER JOIN departments AS d ON p.department=d.dept_no
                    LEFT JOIN vendors AS v ON p.default_vendor_id=v.vendorID
                WHERE m.superID=?
                    AND q.storeID=? ";
            if ($dept) {
                $query .= " AND p.department=? ";
            }
            $query .= " GROUP BY v.vendorName
                ORDER BY SUM(q.percentageStoreSales) DESC";
            $query = $this->connection->addSelectLimit($query, $limit);
            $this->vendorP = $this->connection->prepare($query);
        }
        $args = array($super, $store);
        if ($dept) {
            $args[] = $dept;
        }
        $res = $this->connection->execute($this->vendorP, $args);
        $ret = '<tr><th>Name</th><th># Items</th><th>% Store Sales</th><th>% Category Sales</th></tr>';
        while ($row = $this->connection->fetchRow($res)) {
            $ret .= sprintf('<tr><td>%s</td><td>%d</td><td>%.2f</td><td>%.2f</td>',
                $row['vendorName'], $row['items'], $row['store']*100,
                ($dept ? $row['dept'] : $row['super']) * 100
            );
        }

        return $ret;
    }

    private function allItemsHTML($data)
    {
        $uri = $_SERVER['REQUEST_URI'];
        $uri .= strpos($uri, '?') ? '&itemExcel=1' : '?itemExcel=1';
        $ret = '<p><a href="' . $uri . '">Export to Excel</a></p>';
        $ret .= '<table class="table table-bordered">';
        foreach ($data as $name => $items) {
            $ret .= sprintf('<tr class="info"><th colspan="5">%s</th></tr>', $name);
            $ret .= '<tr><th>Item</th><th>Name</th><th>Current Margin</th><th>% Store Sales</th><th>% Category Sales</th></tr>';
            foreach ($items as $row) {
                $css = '';
                /** Throwing notices; not sure if $highlight should be getting through to here
                if ($row['margin'] > 0 && ($row['margin']*100) < $highlight) {
                    $css = 'class="danger"';
                }
                 */
                $ret .= sprintf('<tr %s><td><a href="ItemEditorPage.php?searchupc=%s">%s</a></td>
                    <td>%s</td><td>%.2f</td><td>%.3f</td><td>%.2f</td>',
                    $css, $row['upc'], $row['upc'],
                    $row['brand'] . ' ' . $row['description'], $row['margin']*100, $row['store']*100,
                    ($row['layer'] == 'dept' ? $row['dept'] : $row['super']) * 100
                );
            }
        }
        $ret .= '</table>';

        return $ret;
    }
    
    private function getAllItems($store, $limit, $super, $highlight)
    {
        $query = "SELECT superID, super_name AS name, 0 AS dept_no
            FROM MasterSuperDepts
            GROUP BY superID, super_name
            ORDER BY super_name";
        $args = array();
        if ($super !== false) {
            $query = "SELECT dept_no, dept_name AS name, superID
                FROM departments AS d
                    INNER JOIN MasterSuperDepts AS m ON d.dept_no=m.dept_ID
                WHERE m.superID=?
                ORDER BY dept_no";
            $args[] = $super;
        }
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, $args);
        $ret = array();
        while ($row = $this->connection->fetchRow($res)) {
            $ret[$row['name']] = $this->getItems($store, $limit, $row['superID'], $row['dept_no'], $highlight);
        }

        return $ret;
    }



    private function getItems($store, $limit, $super, $dept, $highlight)
    {
        if (!isset($this->itemP)) {
            $query = "
                SELECT p.upc, p.brand, p.description,
                    CASE WHEN p.cost = 0 THEN NULL ELSE (p.normal_price-p.cost)/p.normal_price END AS margin,
                    q.percentageStoreSales AS store,
                    q.percentageSuperDeptSales AS super,
                    q.percentageDeptSales AS dept
                FROM " . FannieDB::fqn('productSummaryLastQuarter', 'arch') . " AS q
                    INNER JOIN products AS p ON q.upc=p.upc AND q.storeID=p.store_id
                    INNER JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
                    INNER JOIN departments AS d ON p.department=d.dept_no
                    LEFT JOIN vendors AS v ON p.default_vendor_id=v.vendorID
                WHERE m.superID=?
                    AND q.storeID=? ";
            if ($dept) {
                $query .= " AND p.department=? ";
            }
            $query .= " ORDER BY q.percentageStoreSales DESC";
            $query = $this->connection->addSelectLimit($query, $limit);
            $this->itemP = $this->connection->prepare($query);
        }
        $args = array($super, $store);
        if ($dept) {
            $args[] = $dept;
        }
        $res = $this->connection->execute($this->itemP, $args);
        $ret = array();
        while ($row = $this->connection->fetchRow($res)) {
            $row['layer'] = $dept ? 'dept' : 'super';
            $ret[] = $row;
        }

        return $ret;
    }

    protected function get_view()
    {
        $stores = FormLib::storePicker('store', false);
        $items = FormLib::get('items', 50);
        $vendors = FormLib::get('vendors', 10);
        $highlight = FormLib::get('highlight', 0);
        $superDept = FormLib::get('super', false);
        $store = FormLib::get('store', COREPOS\Fannie\API\lib\Store::getIdByIp());
        $bTable = $this->basicsHTML($this->getBasics($store, $superDept), $superDept);
        $vTable = $this->getAllVendors($store, $vendors, $superDept, 0);
        $iTable = $this->allItemsHTML($this->getAllItems($store, $items, $superDept, $highlight));
        $allURI = 'ContributionTool.php?all=1&store=' . $store;
        return <<<HTML
<p><form method="get">
<div class="container form-inline">
    <div class="input-group">
        <span class="input-group-addon">Store</span>
        {$stores['html']}
    </div>
    <div class="input-group">
        <span class="input-group-addon">Items</span>
        <input name="items" class="form-control" value="{$items}" />
    </div>
    <div class="input-group">
        <span class="input-group-addon">Vendors</span>
        <input name="vendors" class="form-control" value="{$vendors}" />
    </div>
    <div class="input-group">
        <span class="input-group-addon">Highlight</span>
        <input name="highlight" class="form-control" value="{$highlight}" />
    </div>
    <button type="submit" class="btn btn-default">Submit</button>
</div>
</p></form>
<hr />
<ul class="nav nav-tabs" role="tablist">
    <li role="presentation" class="active"><a href="#basic" aria-controls="basic" role="tab" data-toggle="tab">Overview</a></li>
    <li role="presentation"><a href="#vendor" aria-controls="vendor" role="tab" data-toggle="tab">Vendors</a></li>
    <li role="presentation"><a href="#item" aria-controls="item" role="tab" data-toggle="tab">Items</a></li>
</ul>

<div class="tab-content">
    <div role="tabpanel" class="tab-pane active" id="basic">
        {$bTable}
        <p><a href="{$allURI}">Export All Items to Excel</a></p>
    </div>
    <div role="tabpanel" class="tab-pane" id="vendor">{$vTable}</div>
    <div role="tabpanel" class="tab-pane" id="item">{$iTable}</div>
</div>
HTML;
    }

}

FannieDispatch::conditionalExec();

