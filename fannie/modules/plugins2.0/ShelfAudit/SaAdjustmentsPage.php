<?php

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class SaAdjustmentsPage extends FannieRESTfulPage 
{
    public $page_set = 'Plugin :: Shelf Audit';
    public $description = '[Adjustments] is an interface for entering final, larger adjustments';
    protected $title = 'ShelfAudit Inventory';
    protected $header = '';

    protected function post_handler()
    {
        $store = FormLib::get('store');
        if (!$store) {
            echo 'Invalid entry';
            exit;
        }

        $upcs = FormLib::get('upc');
        $qtys = FormLib::get('qty');

        $table = FannieDB::fqn('sa_inventory', 'plugin:ShelfAuditDB');
        $curP = $this->connection->prepare("SELECT id FROM {$table} WHERE clear=0 AND section=0 AND storeID=? AND upc=?");
        $insP = $this->connection->prepare("INSERT INTO {$table} (datetime, upc, clear, quantity, section, storeID)
                                                VALUES (?, ?, 0, ?, 0, ?)");
        $upP = $this->connection->prepare("UPDATE {$table} SET quantity=?
                                            WHERE section=0
                                                AND clear=0
                                                AND storeID=?
                                                AND upc=?");
        for ($i=0; $i<count($upcs); $i++) {
            $upc = $upcs[$i];
            $qty = $qtys[$i];
            $exists = $this->connection->getValue($curP, array($store, $upc));
            if ($exists) {
                $this->connection->execute($upP, array($qty, $store, $upc));
            } elseif ($qty <> 0) {
                $this->connection->execute($insP, array(date('Y-m-d H:i:s'), $upc, $qty, $store));
            }
        }

        return 'SaAdjustmentsPage.php?store=' . $store;
    }

    protected function get_view()
    {
        $store = FormLib::get('store');
        if (!$store) {
            $store = COREPOS\Fannie\API\lib\Store::getIdByIp();
        }
        $stores = FormLib::storePicker();
        $stHTML = str_replace('<select', '<select onchange="location=\'SaAdjustmentsPage.php?store=\'+this.value;"', $stores['html']);

        $curP = $this->connection->prepare("
            SELECT SUM(quantity) AS qty
            FROM " . FannieDB::fqn('sa_inventory', 'plugin:ShelfAuditDB') . "
            WHERE clear=0
                AND section=0
                AND storeID=?
                AND upc=?");

        $ret = '<p>' . $stHTML . '</p>';
        $ret .= '<form method="post" action="SaAdjustmentsPage.php">';
        $ret .= '<input type="hidden" name="store" value="' . $store . '" />';

        $ret .= '<h3>Manual Counts (in dollars, at cost)</h3>';
        $ret .= '<table class="table table-bordered small table-striped">';
        $manualR = $this->connection->query("
            SELECT upc, description
            FROM products
            WHERE store_id=1
                AND inUse=0
                AND description LIKE '% @ COST%'
            ORDER BY upc");
        while ($row = $this->connection->fetchRow($manualR)) {
            $current = $this->connection->getValue($curP, array($store, $row['upc']));
            $ret .= sprintf('<tr><td>%s<input type="hidden" name="upc[]" value="%s" /></td>
                                <td>%s</td><td><input type="text" class="form-control input-sm" name="qty[]" value="%.2f" /></td></tr>',
                                ltrim($row['upc'], '0'), $row['upc'],
                                $row['description'], $current);
        }
        $ret .= '</table>';

        $ret .= '<h3>Service Counts (in dollars, at retail)</h3>';
        $ret .= '<table class="table table-bordered small table-striped">';
        $manualR = $this->connection->query("
            SELECT upc, description
            FROM products
            WHERE store_id=1
                AND inUse=0
                AND description LIKE 'INV SERVICE%'
            ORDER BY upc");
        while ($row = $this->connection->fetchRow($manualR)) {
            $current = $this->connection->getValue($curP, array($store, $row['upc']));
            $ret .= sprintf('<tr><td>%s<input type="hidden" name="upc[]" value="%s" /></td>
                                <td>%s</td><td><input type="text" class="form-control input-sm" name="qty[]" value="%.2f" /></td></tr>',
                                ltrim($row['upc'], '0'), $row['upc'],
                                $row['description'], $current);
        }
        $ret .= '</table>';

        $ret .= '<h3>Invoice Adjustments (in dollars, at cost)</h3>';
        $ret .= '<table class="table table-bordered small table-striped">';
        $manualR = $this->connection->query("
            SELECT upc, description
            FROM products
            WHERE store_id=1
                AND inUse=0
                AND description LIKE '% RECV %'
            ORDER BY upc");
        while ($row = $this->connection->fetchRow($manualR)) {
            $current = $this->connection->getValue($curP, array($store, $row['upc']));
            $ret .= sprintf('<tr><td>%s<input type="hidden" name="upc[]" value="%s" /></td>
                                <td>%s</td><td><input type="text" class="form-control input-sm" name="qty[]" value="%.2f" /></td></tr>',
                                ltrim($row['upc'], '0'), $row['upc'],
                                $row['description'], $current);
        }
        $ret .= '</table>';

        $ret .= '<h3>Sales Adjustments (in dollars, at cost, normally negative)</h3>';
        $ret .= '<table class="table table-bordered small table-striped">';
        $manualR = $this->connection->query("
            SELECT upc, description
            FROM products
            WHERE store_id=1
                AND inUse=0
                AND description LIKE '% ADJ SALES %'
            ORDER BY upc");
        while ($row = $this->connection->fetchRow($manualR)) {
            $current = $this->connection->getValue($curP, array($store, $row['upc']));
            $ret .= sprintf('<tr><td>%s<input type="hidden" name="upc[]" value="%s" /></td>
                                <td>%s</td><td><input type="text" class="form-control input-sm" name="qty[]" value="%.2f" /></td></tr>',
                                ltrim($row['upc'], '0'), $row['upc'],
                                $row['description'], $current);
        }
        $ret .= '</table>';

        $ret .= '<p><button type="submit" class="btn btn-default">Save</button></p>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

