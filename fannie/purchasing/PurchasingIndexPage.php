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

class PurchasingIndexPage extends FannieRESTfulPage {
    
    protected $header = 'Purchase Orders';
    protected $title = 'Purchase Orders';

    public $description = '[Purchase Order Menu] lists purchase order related pages.';
    public $themed = true;

    protected $must_authenticate = True;

    function put_handler()
    {
        try {
            $vendors = $this->form->vendors;
            if (count($vendors) == 0) {
                throw new Exception('At least one vendor required');
            }
            if (!class_exists('OrderGenTask')) {
                include(dirname(__FILE__) . '/../cron/tasks/OrderGenTask.php');
            }
            $task = new OrderGenTask();
            $task->setConfig($this->config);
            $task->setLogger($this->logger);
            $task->setSilent(true);
            $task->setVendors($vendors);
            $task->run();

            return 'ViewPurchaseOrders.php?init=pending';
        } catch (Exception $ex) {
            return true;
        }
    }

    function put_view()
    {
        $res = $this->connection->query('
            SELECT v.vendorID, v.vendorName
            FROM vendors AS v
                INNER JOIN vendorItems AS i ON v.vendorID=i.vendorID
                INNER JOIN InventoryCache AS c ON i.upc=c.upc
            GROUP BY v.vendorID, v.vendorName
            ORDER BY v.vendorName');
        $ret = '<form>
            <input type="hidden" name="_method" value="put" />
            <table class="table table-bordered table-striped">
            <tr><th>Vendor</th><th>Include</th>';
        while ($row = $this->connection->fetchRow($res)) {
            $ret .= sprintf('<tr>
                <td>%s</td>
                <td><input type="checkbox" name="vendors[]" value="%d" />
                </tr>',
                $row['vendorName'], $row['vendorID']);
        }
        $ret .= '</table>
            <p>
                <button type="submit" class="btn btn-default">Generate Orders</button>
            </p>
        </form>';

        return $ret;
    }

    function get_view()
    {

        return '<ul>
            <li><a href="ViewPurchaseOrders.php">View Orders</a>
            <li><a href="PurchasingSearchPage.php">Search Orders</a>
            <li>Import Order
                <ul>
                    <li><a href="ManualPurchaseOrderPage.php">Manually</a></li>
                    <li><a href="ImportPurchaseOrder.php">From Spreadsheet</a></li>
                    <li><a href="importers/AlbertsPdfImport.php">Custom Alberts PDF Import</a></li>
                    <li><a href="importers/CpwInvoiceImport.php">Custom CPW XLS Import</a></li>
                    <li><a href="importers/SpartanNashInvoiceImport.php">Custom Spartan Nash CSV Import</a></li>
                </ul>
            </li>
            <li>Create Order
                <ul>
                <li><a href="EditOnePurchaseOrder.php">By Vendor</a></li>
                <li><a href="EditManyPurchaseOrders.php">By Item</a></li>
                <li><a href="?_method=put">Generate Orders</a></li>
                </ul>
            </li>
            <li>Reports
                <ul>
                <li><a href="reports/UnfiExportForMas.php">UNFI Export for MAS90</a></li>
                <li><a href="reports/LocalInvoicesReport.php">Local Item Purchases Report</a></li>
                <li><a href="reports/OutOfStockReport.php">Out of Stocks Report</a></li>
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
}

FannieDispatch::conditionalExec();

?>
