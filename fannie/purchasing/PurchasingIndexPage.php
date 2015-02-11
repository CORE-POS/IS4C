<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
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

    function get_view(){

        return '<ul>
            <li><a href="ViewPurchaseOrders.php">View Orders</a>
            <li><a href="PurchasingSearchPage.php">Search Orders</a>
            <li>Import Order
                <ul>
                    <li><a href="ManualPurchaseOrderPage.php">Manually</a></li>
                    <li><a href="ImportPurchaseOrder.php">From Spreadsheet</a></li>
                    <li><a href="importers/AlbertsPdfImport.php">Custom Alberts PDF Import</a></li>
                    <li><a href="importers/CpwInvoiceImport.php">Custom CPW XLS Import</a></li>
                    <li>Custom RDW PDF Import</li>
                </ul>
            </li>
            <li>Create Order
                <ul>
                <li><a href="EditOnePurchaseOrder.php">By Vendor</a></li>
                <li><a href="EditManyPurchaseOrders.php">By Item</a></li>
                </ul>
            </li>
            <li>Reports
                <ul>
                <li><a href="reports/UnfiExportForMas.php">UNFI Export for MAS90</li>
                <li><a href="reports/LocalInvoicesReport.php">Local Item Purchases Report</li>
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
