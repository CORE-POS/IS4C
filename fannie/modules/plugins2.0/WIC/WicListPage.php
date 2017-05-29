<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op, Duluth, MN

    This file is part of CORE-POS.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
include(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class WicListPage extends FannieReportPage
{
    protected $title = "Fannie :: WIC Items";
    protected $header = "List WIC Items";

    public $description = '[WIC Item List] shows WIC-eligible items currently carried
    and available from vendors.';
    public $report_set = 'WIC';

    protected $required_fields = array('type');

    protected $report_headers = array('UPC', 'Brand', 'Description', 'Vendor', 'Cost', 'Current Retail');

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc->selectDB($settings['WicDB']);
        $op = $this->config->get('OP_DB') . $dbc->sep();

        if (FormLib::get('type') == 1) {
            $query = '
                SELECT v.upc,
                    v.brand,
                    v.description,
                    n.vendorName,
                    v.cost,
                    v.srp AS normal_price
                FROM ' . $op . 'vendorItems AS v
                    LEFT JOIN ' . $op . 'vendors AS n ON v.vendorID=n.vendorID
                    INNER JOIN WicItems AS w ON v.upc=w.upc
                WHERE v.upc NOT IN (SELECT upc FROM ' . $op . 'products WHERE inUse=1)
                ORDER BY v.upc';
        } else {
            $query = '
                SELECT p.upc,
                    p.brand,
                    p.description,
                    n.vendorName,
                    p.cost,
                    p.normal_price
                FROM ' . $op . 'products AS p
                    LEFT JOIN ' . $op . 'vendors AS n ON p.default_vendor_id=n.vendorID
                    INNER JOIN WicItems AS w ON p.upc=w.upc
                WHERE p.inUse=1
                ORDER BY p.upc';
        }
        $res = $dbc->query($query);
        $data = array();
        while ($w = $dbc->fetchRow($res)) {
            $data[] = array(
                $w['upc'],
                $w['brand'] === null ? '' : $w['brand'],
                $w['description'],
                $w['vendorName'] === null ? '' : $w['vendorName'],
                $w['cost'],
                $w['normal_price'],
            );
        }

        return $data;
    }

    public function calculate_footers($data)
    {
       return array('Number of Items', null, null, null, null, count($data)); 
    }

    public function form_content()
    {
        return '<form method="get">
            <div class="form-group">
                <label>Show</label>
                <select name="type" class="form-control">
                    <option value="0">Items we carry</option>
                    <option value="1">Items available from vendors</option>
                </select>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-default">Get List</button>
            </div>
            </form>';
    }
}

FannieDispatch::conditionalExec();

