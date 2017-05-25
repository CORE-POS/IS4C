<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class PurchasingIndexPage extends FannieRESTfulPage 
{
    protected $header = 'Purchase Orders';
    protected $title = 'Purchase Orders';

    public $description = '[Purchase Order Menu] lists purchase order related pages.';

    protected $must_authenticate = true;

    protected function put_handler()
    {
        try {
            $vendors = $this->form->vendors;
            $vendors = array_filter($vendors, function ($i) { return $i != ''; });
            $vendors = array_unique($vendors);
            if (count($vendors) == 0) {
                throw new Exception('At least one vendor required');
            }
            if (!class_exists('OrderGenTask')) {
                include(dirname(__FILE__) . '/../cron/tasks/OrderGenTask.php');
            }
            $task = new OrderGenTask();
            $task->setConfig($this->config);
            $task->setLogger($this->logger);
            $task->setMultiplier($this->form->multiplier);
            $task->setSilent(true);
            $task->setVendors($vendors);
            $task->setUser(FannieAuth::getUID($this->current_user));
            $task->setStore($this->form->store);
            $task->run();

            return 'ViewPurchaseOrders.php?init=pending';
        } catch (Exception $ex) {
            return true;
        }
    }

    protected function put_view()
    {
        $stores = FormLib::storePicker('store', false);
        $res = $this->connection->query('
            SELECT v.vendorID, v.vendorName
            FROM vendors AS v
                INNER JOIN vendorItems AS i ON v.vendorID=i.vendorID
                INNER JOIN InventoryCache AS c ON i.upc=c.upc
                INNER JOIN products AS p ON c.upc=p.upc AND c.storeID=p.store_id
            WHERE i.vendorID=p.default_vendor_id
                AND v.inactive=0
            GROUP BY v.vendorID, v.vendorName
            ORDER BY v.vendorName');
        $vendorSelect = '<select class="form-control chosen" name="vendors[]">';
        $vendorSelect .= '<option value="">Select vendor...</option>';
        while ($row = $this->connection->fetchRow($res)) {
            $vendorSelect .= sprintf('<option value="%d">%s</option>', $row['vendorID'], $row['vendorName']);
        }
        $vendorSelect .= '</select>';
        $ret = '<form>
            <input type="hidden" name="_method" value="put" />
            <div class="small panel panel-default">
                <div class="panel panel-heading">Vendors & Stores</div>
                <div class="panel panel-body">
            <label>Vendor(s)</label>';
        for ($i=0; $i<5; $i++) {
            $ret .= '<div class="form-group">' . $vendorSelect . '</div>'; 
        }
        $ret .= '
            <p>
                <label>Store</label>
                ' . $stores['html'] . '
            </p>
            </div></div>
            <div class="small panel panel-default">
                <div class="panel panel-heading">Automated Pars</div>
                <div class="panel panel-body">
                    <div class="form-group">
                        <label title="For use with produce plants">Multiplier (optional)</label>
                        <input class="form-control" type="number" value="1" min="1" max="30" name="multiplier" />
                    </div>
                    <div class="form-group">
                        <label title="For use with produce plants">Forecast (optional)</label>
                        <div class="input-group">
                            <span class="input-group input-group-addon">$</span>
                            <input class="form-control" type="forecast" value="0" min="0" max="1000000" name="forecast" />
                        </div>
                    </div>
                </div>
            </div>
            <p>
                <button type="submit" class="btn btn-default btn-core">Generate Orders</button>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <button type="reset" class="btn btn-default" 
                    onclick="$(\'select.chosen\').val(\'\').trigger(\'chosen:updated\');">Reset</button>
            </p>
        </form>';

        $this->addScript('../src/javascript/chosen/chosen.jquery.min.js');
        $this->addCssFile('../src/javascript/chosen/bootstrap-chosen.css');
        $this->addOnloadCommand("\$('select.chosen').chosen();\n");

        return $ret;
    }

    protected function get_view()
    {

        return '<ul>
            <li><a href="ViewPurchaseOrders.php">View Orders</a>
            <li><a href="PurchasingSearchPage.php">Search Orders</a>
            <li>Create Order
                <ul>
                <li><a href="EditOnePurchaseOrder.php">By Vendor</a></li>
                <li><a href="EditManyPurchaseOrders.php">By Item</a></li>
                <li><a href="?_method=put">Generate Orders</a></li>
                <li><a href="ScanTransferPage.php">Store Transfer</a></li>
                </ul>
            </li>
            <li>Import Order
                <ul>
                    <li><a href="ManualPurchaseOrderPage.php">Manually</a></li>
                    <li><a href="ImportPurchaseOrder.php">From Spreadsheet</a></li>
                </ul>
            </li>
            </ul>';
        
    }

    public function helpContent()
    {
        return '<p>Purchase Orders are for incoming inventory - i.e., 
            items the store purchases from a vendor and then sells to
            customers. Purchase Orders depend on vendor data and especially
            vendor item catalogs. Only items in a vendor catalog can be
            added to a purchase order.</p>
            <p>Purchase Orders may include two separate sets of quantity
            and cost fields. One set is for the number of items ordered
            and expected cost. The other set is for the number of items
            acutally received and received cost.</p>
            <p>View and Search are straightforward. Import creates a purchase
            order from a spreadsheet. Creating orders by vendor results
            in a single order. Only UPCs and SKUs from the chosen vendor
            can be added. Creating orders by item will match UPCs and SKUs
            from all known vendors. This option creates separate orders
            for each vendor as needed.</p>
            ';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
        $phpunit->assertNotEquals(0, strlen($this->put_view()));
    }
}

FannieDispatch::conditionalExec();

