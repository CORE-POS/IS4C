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

    private function getBasics($store, $super)
    {
        $query = "SELECT m.super_name AS name, MAX(m.superID) AS superID,
            SUM(percentageStoreSales) AS percentage,
            SUM(CASE WHEN p.cost <> 0 THEN 1 ELSE 0 END) as hasCost,
            SUM(
                CASE WHEN p.cost=0 THEN 0 
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
        $args = array($store);
        if ($super !== false) {
            $query = "SELECT d.dept_name AS name,
                SUM(percentageSuperDeptSales) AS percentage,
                SUM(CASE WHEN p.cost <> 0 THEN 1 ELSE 0 END) as hasCost,
                SUM(
                    CASE WHEN p.cost=0 THEN 0 
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
            $args[] = $super;
        }

        $label = $super !== false ? 'Category' : 'Store';
        $ret = '<table class="table table-bordered">
            <tr><th>Name</th><th># of Items</th><th>% of Items w/ Costs</th><th>% of ' . $label . ' Sales</th><th>Expected Margin</th></tr>';
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, $args);
        while ($row = $this->connection->fetchRow($res)) {
            if ($super === false) {
                $uri = $_SERVER['REQUEST_URI'];
                $uri .= strpos($uri, '?') ? '&super=' : '?super=';
                $row['name'] = sprintf('<a href="%s%d">%s</a>', $uri, $row['superID'], $row['name']);
            }
            $ret .= sprintf('<tr><td>%s</td><td>%d</td><td>%.2f</td>
                            <td>%.2f</td><td>%.2f</td>',
                            $row['name'], $row['items'],
                            ($row['hasCost'] / $row['items']) * 100,
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
        $ret = '<table class="table table-bordered">';
        while ($row = $this->connection->fetchRow($res)) {
            $ret .= sprintf('<tr class="info"><th colspan="5">%s</th></tr>',
                $row['name']);
            $ret .= $this->getItems($store, $limit, $row['superID'], $row['dept_no'], $highlight);
        }
        $ret .= '</table>';

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
        $ret = '<tr><th>Item</th><th>Name</th><th>Current Margin</th><th>% Store Sales</th><th>% Category Sales</th></tr>';
        while ($row = $this->connection->fetchRow($res)) {
            $css = '';
            if ($row['margin'] > 0 && ($row['margin']*100) < $highlight) {
                $css = 'class="danger"';
            }
            $ret .= sprintf('<tr %s><td><a href="ItemEditorPage.php?searchupc=%s">%s</a></td>
                <td>%s</td><td>%.2f</td><td>%.3f</td><td>%.2f</td>',
                $css, $row['upc'], $row['upc'],
                $row['brand'] . ' ' . $row['description'], $row['margin']*100, $row['store']*100,
                ($dept ? $row['dept'] : $row['super']) * 100
            );
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
        $bTable = $this->getBasics($store, $superDept);
        $vTable = $this->getAllVendors($store, $vendors, $superDept, 0);
        $iTable = $this->getAllItems($store, $items, $superDept, $highlight);
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
    <div role="tabpanel" class="tab-pane active" id="basic">{$bTable}</div>
    <div role="tabpanel" class="tab-pane" id="vendor">{$vTable}</div>
    <div role="tabpanel" class="tab-pane" id="item">{$iTable}</div>
</div>
HTML;
    }

}

FannieDispatch::conditionalExec();

