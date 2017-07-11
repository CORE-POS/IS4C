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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class OrphanedItemsReport extends FannieReportPage 
{
    protected $required_fields = array('id', 'store');
    public $description = '[Orphaned Items] shows items assigned to a vendor that have no corresponding vendor catalog entry.';
    public $report_set = 'Vendors';
    protected $title = "Fannie : Orphaned Items";
    protected $header = "Orphaned Items";

    protected $report_headers = array('UPC', 'Brand', 'Description', 'Active', 'Last Sold');

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        try {
            $vendorID = $this->form->id;
            $store = $this->form->store;
            $hideInactive = $this->form->hideInactive ? true : false;
            $hideNoSales = $this->form->hideNoSales ? true : false;
        } catch (Exception $ex) {
            return array();
        }

        $query = "
            SELECT p.upc,
                p.brand,
                p.description,
                MAX(p.last_sold) AS last_sold,
                MAX(p.inUse) AS active
            FROM products AS p
                LEFT JOIN vendorItems AS v ON p.upc=v.upc AND p.default_vendor_id=v.vendorID
                LEFT JOIN VendorAliases AS s ON p.upc=s.upc AND p.default_vendor_id=s.vendorID
            WHERE p.default_vendor_id=?
                AND v.upc IS NULL
                AND s.upc IS NULL
                AND " . DTrans::isStoreID($store, 'p') . "
            GROUP BY p.upc,
                p.brand,
                p.description
            HAVING 1=1";
        if ($hideInactive) {
            $query .= " AND MAX(p.inUse) > 0 ";
        }
        if ($hideNoSales) {
            $query .= " AND MAX(p.last_sold) IS NOT NULL ";
        }
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, array($vendorID, $store));
        $data = array();

        while ($row = $dbc->fetchRow($res)) {
            $data[] = array(
                $row['upc'],
                ($row['brand'] ? $row['brand'] : ''),
                $row['description'],
                ($row['active'] ? 'Yes' : 'No'),
                ($row['last_sold'] ? $row['last_sold'] : 'n/a'),
            );
        }

        return $data;
    }

    public function form_content()
    {
        $stores = FormLib::storePicker();
        $model = new VendorsModel($this->connection);
        $vendors = $model->toOptions();
        return <<<HTML
<form method="get">
    <div class="form-group">
        <label>Vendor</label>
        <select name="id" class="form-control">
            {$vendors}
        </select>
    </div>
    <div class="form-group">
        <label>Store</label>
        {$stores['html']}
    </div>
    <div class="form-group">
        <label>Hide Inactive Items</label>
        <select name="hideInactive" class="form-control">
            <option value="1">Yes</option>
            <option value="0">No</option>
        </select>
    </div>
    <div class="form-group">
        <label>Hide Never Sold</label>
        <select name="hideNoSales" class="form-control">
            <option value="1">Yes</option>
            <option value="0">No</option>
        </select>
    </div>
    <p>
        <button type="submit" class="btn btn-default btn-core">Submit</button>
    </p>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

