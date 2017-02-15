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

class WicProdReport extends FannieReportPage 
{
    public $description = '[WIC Product Report] lists Information pertaining to WIC items.';
    public $report_set = 'WIC';
    public $themed = true;

    protected $report_headers = array('UPC', 'Brand', 'Description', 'Vendor', 
                                'Cost', 'Retail', 'location', 'Department');
    protected $sort_direction = 1;
    protected $title = "Fannie : WIC Product Report";
    protected $header = "WIC Product Report";

    public function fetch_report_data()
    {        
        $item = array();
        
        global $FANNIE_OP_DB, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        
        $query = "SELECT p.upc, 
                    p.description, 
                    p.brand, 
                    p.normal_price,
                    p.cost, 
                    v.vendorName, 
                    p.department,
                    d.dept_name,
                    fs.name
                FROM products AS p
                    LEFT JOIN productUser AS pu ON pu.upc=p.upc
                    LEFT JOIN vendorItems AS vi ON vi.upc=p.upc
                    LEFT JOIN vendors AS v ON v.vendorID=vi.vendorID
                    LEFT JOIN prodPhysicalLocation AS pl ON pl.upc=p.upc
                    LEFT JOIN FloorSections AS fs ON fs.floorSectionID=pl.floorSectionID
                    LEFT JOIN departments AS d ON p.department=d.dept_no
                WHERE p.inUse=1
                    AND p.wicable=1
                ;";
        $result = $dbc->query($query);
        while ($row = $dbc->fetch_row($result)) {
            $item[$row['upc']][0] = $row['upc'];
            $item[$row['upc']][1] = $row['brand'];
            $item[$row['upc']][2] = $row['description'];
            
            if ($row['vendorName'] == NULL) {
                $item[$row['upc']][3] = 'unknown';
            } else {
                $item[$row['upc']][3] = $row['vendorName'];
            }
            
            $item[$row['upc']][4] = $row['cost'];
            $item[$row['upc']][5] = $row['normal_price'];
            $item[$row['upc']][6] = $row['name'] ? $row['name'] : '';
            $item[$row['upc']][7] = $row['department'] . ' ' . $row['dept_name'];
        }

        sort($item);
        return $this->dekey_array($item);
    }

    public function form_content()
    {
        return '<!-- not needed -->';
    }

    public function helpContent()
    {
        return '<p>
            View Cost/Price information for items currently<br>
            selected as WIC-able.<br>
            </p>';
    }

}

FannieDispatch::conditionalExec();

