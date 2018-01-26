<?php

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class SaInvComparePage extends FannieRESTfulPage 
{
    public $page_set = 'Plugin :: Shelf Audit';
    public $description = '[Inventory Comparison] compares spot counts to recorded inventory levels';
    protected $title = 'ShelfAudit Inventory Comparison';
    protected $header = 'ShelfAudit Inventory Comparison';

    protected function post_view()
    {
        $upcs = FormLib::get('upc');
        $qtys = FormLib::get('qty');
        $skip = FormLib::get('exclude');
        $date = FormLib::get('date');
        $dateObj = new DateTime($date);
        $store = FormLib::get('store');

        $checkP = $this->connection->prepare('SELECT countDate, par FROM InventoryCounts WHERE upc=? AND storeID=? AND mostRecent=1 ORDER BY mostRecent DESC');
        $upP = $this->connection->prepare('UPDATE InventoryCounts SET mostRecent=0 WHERE upc=? AND storeID=?');
        $insP = $this->connection->prepare('INSERT INTO InventoryCounts (upc, storeID, count, countDate, mostRecent, uid, par) VALUES (?, ?, ?, ?, 1, ?, ?)');
        $uid = FannieAuth::getUID($this->current_user);
        $ret = '';
        $this->connection->startTransaction();
        for ($i=0; $i<count($upcs); $i++) {
            $upc = $upcs[$i];
            if (in_array($upc, $skip)) {
                continue;
            }

            $upc = BarcodeLib::padUPC($upc);
            if (!isset($qtys[$i])) {
                $ret .= "Skipping {$upc}; no quantity found<br />";
                continue;
            }

            $current = $this->connection->getRow($checkP, array($upc, $store));
            if ($current !== false) {
                $lastObj = new DateTime($current['countDate']);
                if ($lastObj >= $dateObj) {
                    $ret .= "Skipping {$upc}; counted more recently {$current['countDate']}<br />";
                    continue;
                }
            }

            $this->connection->execute($upP, array($upc, $store));
            $args = array(
                $upc,
                $store,
                $qtys[$i],
                $date,
                $uid, 
                ($current ? $current['par'] : round($qtys[$i])),
            );
            $this->connection->execute($insP, $args);
            $ret .= "Set par for {$upc} to {$qtys[$i]}<br />";
        }
        $this->connection->commitTransaction();

        return $ret;
    }

    private function getItems($vendorID, $storeID, $depts)
    {
        list($inStr, $args) = $this->connection->safeInClause($depts);
        $query = "SELECT s.upc, p.brand, p.description, p.department,
                SUM(s.quantity) AS qty,
                MIN(i.onHand) AS cur
            FROM " . FannieDB::fqn('sa_inventory', 'plugin:ShelfAuditDB') . " AS s
                INNER JOIN products AS p ON p.upc=s.upc AND p.store_id=s.storeID
                LEFT JOIN InventoryCache AS i ON i.upc=s.upc AND i.storeID=s.storeID
            WHERE s.clear=0
                AND p.department IN ({$inStr})
                AND s.storeID=?
                AND p.default_vendor_id=?
            GROUP BY s.upc, p.brand, p.description, p.department";
        $prep = $this->connection->prepare($query);
        $args[] = $storeID;
        $args[] = $vendorID;
        return $this->connection->execute($prep, $args);
    }

    protected function get_id_view()
    {
        $matches = preg_match_all("/[0-9]+/", FormLib::get('dept'), $depts);
        if ($matches == 0) {
            $depts = array();
        } else {
            $depts = array_pop($depts);
        }
        $res = $this->getItems($this->id, FormLib::get('store'), $depts);

        $ret = '<form method="post" action="SaInvComparePage.php">';
        $ret .= '<table class="table small">
            <tr><th>UPC</th><th>Brand</th><th>Description</th>
            <th>Dept#</td><td>Counted</td><td>Current</td><td>Exclude from Update</td></tr>';
        while ($row = $this->connection->fetchRow($res)) {
            $ret .= sprintf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%d</td>
                <td>%.2f<input type="hidden" name="qty[]" value="%s" /></td>
                <td>%.2f</td><td><input type="checkbox" name="exclude[]" value="%s" />
                <input type="hidden" name="upc[]" value="%s" /></td></tr>',
                $row['upc'],
                $row['brand'],
                $row['description'],
                $row['department'],
                $row['qty'],
                $row['qty'],
                $row['cur'],
                $row['upc'],
                $row['upc']
            );
        }
        $ret .= '</table>';
        $ret .= '<div class="form-group">
                <label>Date new counts as</label>
                <input type="text" class="form-control date-field" name="date" />
                <input type="hidden" name="store" value="' . FormLib::get('store') . '" />
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-default btn-core">Update Inventory Counts</button>
            </div>
            </form>';

        return $ret;
    }

    protected function get_view()
    {
        $store = FormLib::storePicker();
        $vendor = new VendorsModel($this->connection);
        $vOpts = $vendor->toOptions();

        return <<<HTML
<form method="get">
    <div class="form-group">
        <label>Store</label>
        {$store['html']}
    </div>
    <div class="form-group">
        <label>Vendor</label>
        <select name="id" class="form-control chosen">
            {$vOpts}
        </select>
    </div>
    <div class="form-group">
        <label>As of</label>
        <input type="text" name="date" class="form-control date-field" />
    </div>
    <div class="form-group">
        <label>Department(s)</label>
        <input type="text" name="dept" class="form-control" />
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default btn-core">Submit</button>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

