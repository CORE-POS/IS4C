<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Community Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

require(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class InvCountPage extends FannieRESTfulPage
{
    protected $header = 'Inventory Counts';
    protected $title = 'Inventory Counts';
    protected $must_authenticate = true;
    protected $enable_linea = true;
    public $description = '[Inventory Counts] shows live inventory figures as well as manages hand counts and pars.';

    public function preprocess()
    {
        $this->addRoute('get<live>');
        $this->addRoute('get<vendor>');
        $this->addRoute('post<vendor>');
        $this->addRoute('get<recalc><store>');
        $this->addRoute('get<recalc><live>');

        return parent::preprocess();
    }

    protected function get_recalc_store_handler()
    {
        if (!class_exists('InventoryTask')) {
            include(dirname(__FILE__) . '/../../cron/tasks/InventoryTask.php');
        }
        $task = new InventoryTask();
        $config = FannieConfig::factory();
        $logger = new FannieLogger();
        $task->setConfig($config);
        $task->setLogger($logger);
        $task->setStoreID($this->store);
        $task->setVendorID($this->recalc);
        $task->run();

        return 'InvCountPage.php?recalc=1&live=' . $this->recalc . '&store=' . $this->store;
    }

    protected function get_recalc_live_view()
    {
        return '<div class="alert alert-success">Refreshed totals</div>'
            . $this->get_live_view();
    }

    protected function post_id_handler()
    {
        $upc = BarcodeLib::padUPC($this->id);
        try {
            $count = $this->form->count;
            $par = $this->form->par;
            $storeID = FormLib::get('storeID', 1);
            $this->saveEntry($upc, $storeID, $count, $par);
        } catch (Exception $ex) {}

        return 'InvCountPage.php?id=' . $this->id;
    }

    private function savePar($upc, $storeID, $par)
    {
        $inv = new InventoryCountsModel($this->connection);
        $inv->upc($upc);
        $inv->storeID($storeID);
        $inv->mostRecent(1);
        foreach ($inv->find() as $i) {
            $i->par($par);
            $i->save();
        }
    }

    private function saveEntry($upc, $storeID, $count, $par)
    {
        $inv = new InventoryCountsModel($this->connection);
        $inv->upc($upc);
        $inv->storeID($storeID);
        $inv->count($count);
        $inv->countDate(date('Y-m-d H:i:s'));
        $inv->mostRecent(1);
        $inv->uid(FannieAuth::getUID($this->current_user));
        $inv->par($par);
        $invID = $inv->save();
       
        if ($invID !== false) {
            $prep = $this->connection->prepare('
                UPDATE InventoryCounts
                SET mostRecent=0
                WHERE upc=?
                    AND storeID=?
                    AND inventoryCountID <> ?');
            $this->connection->execute($prep, array($upc, $storeID, $invID));
            $prep = $this->connection->prepare('
                UPDATE InventoryCache
                SET baseCount=?,
                    ordered=0,
                    sold=0,
                    shrunk=0,
                    onHand=?
                WHERE upc=?
                    AND storeID=?');
            $this->connection->execute($prep, array($count, $count, $upc, $storeID));
        }
    }

    protected function post_vendor_handler()
    {
        try {
            $upc = $this->form->upc;
            $count = $this->form->count;
            $par = $this->form->par;
            $storeID = FormLib::get('store', 1);
            for ($i=0; $i<count($upc); $i++) {
                if (!isset($count[$i]) || $count[$i] === '') {
                    if (isset($par[$i]) && is_numeric($par[$i])) {
                        $this->savePar($upc[$i], $storeID, $par[$i]);
                    }
                    continue;
                }
                if (!isset($par[$i]) || $par[$i] === '') {
                    continue;
                }
                $this->saveEntry($upc[$i], $storeID, $count[$i], $par[$i]);
            }
        } catch (Exception $ex) {}

        return 'InvCountPage.php?vendor=' . $this->vendor . '&store=' . $storeID;
    }

    protected function get_id_view()
    {
        $upc = BarcodeLib::padUPC($this->id);
        $store = FormLib::get('store', 1);
        $info = $this->getMostRecent($upc, $store);
        $prod = new ProductsModel($this->connection);
        $prod->upc($upc);
        $prod->store_id($store);
        $prod->load();
        if ($info === false) {
            $info['countDate'] = 'n/a';
            $info['count'] = 0;
            $info['par'] = 0;    
        }

        $ret = '<div class="alert alert-info">
            ' . $upc . ' - ' . $prod->brand() . ' ' . $prod->description() . '<br />
            <strong>Last Counted</strong>: ' . $info['countDate'] . '<br />
            <strong>Last Count</strong>: ' . $info['count'] . '<br />
            <strong>Current Par</strong>: ' . $info['par'] . '<br />
            </div>';
        $ret .= '<form method="post">
            <div class="form-group">
                <input type="hidden" name="id" value="' . $this->id . '" />
                <input type="hidden" name="storeID" value="' . $store . '" />
                <label>Update Count</label>
                <input type="number" min="0" max="500" step="0.01" class="form-control" 
                    id="count-field" required name="count" />
            </div>
            <div class="form-group">
                <label>Update Par</label>
                <input type="text" class="form-control" required name="par" value="' . $info['par'] . '" />
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-default">Save</button>
            </div>
            </form>';
        $this->addOnloadCommand("\$('#count-field').focus();\n");

        return $ret . '<hr />' . $this->get_view();
    }

    protected function get_vendor_view()
    {
        try {
            $store = $this->form->store;
        } catch (Exception $ex) {
            return '<div class="alert alert-danger">No store selected</div>';
        }
        $this->addScript($this->config->get('URL') . 'src/javascript/jquery.floatThead.min.js');
        $this->addOnloadCommand("\$('.table-float').floatThead();\n");

        $query = '
            SELECT p.upc,
                p.brand,
                p.description,
                v.sku,
                p.auto_par
            FROM products AS p
                LEFT JOIN vendorItems AS v ON v.upc=p.upc AND v.vendorID=p.default_vendor_id
            WHERE p.default_vendor_id=?
                AND p.inUse=1
                AND p.store_id=? ';
        $args = array($this->vendor, $store);
        try {
            if ($this->form->super !== '') {
                $args[] = $this->form->super;
                $query = str_replace('AS p', 'AS p INNER JOIN superdepts AS s ON p.department=s.dept_ID', $query);
                $query .= ' AND s.superID=? ';
            }
        } catch (Exception $ex){}
        $query .= ' ORDER BY p.upc';
        $prep = $this->connection->prepare($query);
        $ret = '<form method="post">
            <input type="hidden" name="vendor" value="' . $this->vendor . '" />
            <input type="hidden" name="store" value="' . $store . '" />
            <table class="table table-bordered table-striped small table-float">
            <thead style="background: #fff;">
            <tr>
                <th class="thead">UPC</th>
                <th class="thead">SKU</th>
                <th class="thead">Brand</th>
                <th class="thead">Description</th>
                <th class="thead">Last Counted</th>
                <th class="thead">Last Count</th>
                <th class="thead">Current Par</th>
                <th class="thead">Avg. Daily Sales</th>
                <th class="thead">New Count</th>
                <th class="thead">New Par</th>
            </tr></thead><tbody>';
        $res = $this->connection->execute($prep, $args);
        while ($row = $this->connection->fetchRow($res)) {
            // omit items that have a breakdown. only the breakdown
            // should have a count & par
            if ($this->isBreakable($row['upc'], $this->vendor)) {
                continue;
            }
            $info = $this->getMostRecent($row['upc'], $store);
            $ret .= sprintf('<tr %s>
                <td>%s<input type="hidden" name="upc[]" value="%s" /></td>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td %s>%.2f</td>
                <td><input type="text" class="form-control input-sm" value="" name="count[]" /></td>
                <td><input type="text" class="form-control input-sm" value="%s" name="par[]" /></td>
                </tr>',
                (!$info ? 'class="warning"' : ''),
                \COREPOS\Fannie\API\lib\FannieUI::itemEditorLink($row['upc']), $row['upc'],
                $row['sku'],
                $row['brand'],
                $row['description'],
                ($info ? '<a href="DateCountPage.php?id=' . $row['upc'] . '&store=' . $store . '">' 
                    . $info['countDate'] . '</a>' : 'n/a'),
                ($info ? $info['count'] : 'n/a'),
                ($info ? $info['par'] : 'n/a'),
                ($info['par'] > (7*$row['auto_par']) ? 'class="danger"' : ''),
                $row['auto_par'],
                ($info ? $info['par'] : '0')
            );
        }
        $ret .= '</tbody></table>
            <p>
                <button type="submit" class="btn btn-default">Save</button>
                <a href="DateCountPage.php?vendor=' . $this->vendor . '&store=' . $store . '"
                    class="btn btn-default btn-reset">Adjust Dates</a>
            </p>
            </form>';

        return $ret;
    }

    private $bdP = null;
    /**
      Item can be broken down into several sale-able units
    */
    private function isBreakable($upc, $vendorID)
    {
        if ($this->bdP === null) {
            $this->bdP = $this->connection->prepare('
                SELECT v.upc
                FROM VendorBreakdowns AS v
                    INNER JOIN vendorItems AS i ON v.sku=i.sku AND v.vendorID=i.vendorID
                WHERE i.upc=?
                    AND v.vendorID=?');
        }

        $ret = $this->connection->getValue($this->bdP, array($upc, $vendorID));

        return $ret === false ? false : true;
    }

    protected function get_live_view()
    {
        try {
            $store = $this->form->store;
        } catch (Exception $ex) {
            return '<div class="alert alert-danger">No store selected</div>';
        }
        $this->addScript($this->config->get('URL') . 'src/javascript/jquery.floatThead.min.js');
        $this->addOnloadCommand("\$('.table-float').floatThead();\n");

        $prep = $this->connection->prepare('
            SELECT p.upc,
                p.brand,
                p.description,
                c.countDate,
                i.baseCount,
                i.ordered,
                i.sold,
                i.shrunk,
                i.onHand,
                c.par
            FROM products AS p
                INNER JOIN InventoryCache AS i ON p.upc=i.upc AND p.store_id=i.storeID
                INNER JOIN InventoryCounts AS c ON p.upc=c.upc AND p.store_id=c.storeID AND c.mostRecent=1
            WHERE p.store_id=?
                AND p.default_vendor_id=?
            ORDER BY p.upc');
        $today = $this->connection->prepare('
            SELECT ' . DTrans::sumQuantity() . ' AS qty
            FROM ' . DTransactionsModel::selectDlog(date('Y-m-d')) . '
            WHERE upc=?
                AND store_id=?');
        $shrink = $this->connection->prepare('
            SELECT ' . DTrans::sumQuantity() . ' AS qty
            FROM ' . DTransactionsModel::selectDTrans(date('Y-m-d')) . '
            WHERE upc=?
                AND store_id=?
                AND trans_status=\'Z\'
                AND register_no <> 99
                AND emp_no <> 9999');
        $res = $this->connection->execute($prep, array($store, $this->live));
        $ret = '<table class="table table-bordered table-striped table-float">';
        $ret .= '<thead style="background:#fff;"><tr>
            <th class="thead">UPC</th>
            <th class="thead">Brand</th>
            <th class="thead">Description</th>
            <th colspan=2" class="thead">Last Physical Count</th>
            <th class="thead">Ordered</th>
            <th class="thead">Sold</th>
            <th class="thead">Shrunk</th>
            <th class="thead">Total Inventory</th>
            </tr></thead><tbody>';
        while ($row = $this->connection->fetchRow($res)) {
            if ($this->isBreakable($row['upc'], $this->live)) {
                continue;
            }
            $adj = $this->connection->getValue($today, array($row['upc'], $store));
            if ($adj) {
                $row['sold'] += $adj;
                $row['onHand'] -= $adj;
            }
            $adj = $this->connection->getValue($shrink, array($row['upc'], $store));
            if ($adj) {
                $row['shrunk'] += $adj;
                $row['onHand'] -= $adj;
            }
            $ret .= sprintf('<tr>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td>%.2f</td>
                <td>%.2f</td>
                <td>%.2f</td>
                <td>%.2f</td>
                <td %s>%.2f</td>
                </tr>',
                $row['upc'],
                $row['brand'],
                $row['description'],
                $row['countDate'],
                $row['baseCount'],
                $row['ordered'],
                $row['sold'],
                $row['shrunk'],
                ($this->underPar($row['onHand'],$row['par']) ? 'class="danger"' : ''),
                $row['onHand']
            );
        }
        $ret .= '</tbody></table>';
        $ret .= '<p>
            <a href="?recalc=' . $this->live . '&store=' . $store . '"
                class="btn btn-default">Recalculate Totals</a>
            </p>';

        return $ret;
    }

    private function underPar($now, $par) {
        if ($par == 1 && $now <= $par) {
            return true;
        } elseif ($now < $par) {
            return true;
        }

        return false;
    }

    private function getMostRecent($upc, $storeID=1)
    {
        $prep = $this->connection->prepare('
            SELECT *
            FROM InventoryCounts
            WHERE mostRecent=1
                AND upc=?
                AND storeID=?
            ORDER BY countDate DESC');
        return $this->connection->getRow($prep, array($upc, $storeID));
    }

    protected function get_view()
    {
        $vendors = new VendorsModel($this->connection);
        $supers = new SuperDeptNamesModel($this->connection);
        $stores = FormLib::storePicker('store', false);
        $this->addOnloadCommand("enableLinea('#linea-field');\n");
        return '<div class="panel panel-default">
            <div class="panel-heading">Enter Item Count</div>
            <div class="panel-body">
            <form method="get">
                <div class="form-group">
                    <label>UPC</label>
                    <input type="text" name="id" id="linea-field" class="form-control" />
                </div>
                <div class="form-group">
                    <label>Store</label>
                    ' . $stores['html'] . '
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-default">Submit</button>
                </div>
            </form>
            </div>
            </div>
        <div class="panel panel-default">
            <div class="panel-heading">Enter Vendor Counts</div>
            <div class="panel-body">
            <form method="get">
                <div class="form-group">
                    <label>Vendor</label>
                    <select name="vendor" class="form-control">
                    ' . $vendors->toOptions() . '
                    </select>
                </div>
                <div class="form-group">
                    <label>Super Department</label>
                    <select name="super" class="form-control">
                        <option value="">Optional</option>
                    ' . $supers->toOptions(-999) . '
                    </select>
                </div>
                <div class="form-group">
                    <label>Store</label>
                    ' . $stores['html'] . '
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-default">Submit</button>
                </div>
            </form>
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-heading">View Live Counts</div>
            <div class="panel-body">
            <form method="get">
                <div class="form-group">
                    <label>Vendor</label>
                    <select name="live" class="form-control">
                    ' . $vendors->toOptions() . '
                    </select>
                </div>
                <div class="form-group">
                    <label>Super Department</label>
                    <select name="super" class="form-control">
                        <option value="">Optional</option>
                    ' . $supers->toOptions(-999) . '
                    </select>
                </div>
                <div class="form-group">
                    <label>Store</label>
                    ' . $stores['html'] . '
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-default">Submit</button>
                </div>
            </form>
            </div>
        </div>
            ';
    }

    public function unitTest($phpunit)
    {
        $this->id = '4011';
        $this->vendor = 1;
        $this->live = 1;
        $form = new COREPOS\common\mvc\ValueContainer();
        $form->store = 1;
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
        $phpunit->assertNotEquals(0, strlen($this->get_id_view()));
        $phpunit->assertNotEquals(0, strlen($this->get_vendor_view()));
        $this->setForm($form);
        $phpunit->assertNotEquals(0, strlen($this->get_vendor_view()));
        $phpunit->assertNotEquals(0, strlen($this->get_live_view()));
    }

}

FannieDispatch::conditionalExec();

