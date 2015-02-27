<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ProductLineReport extends FannieReportPage 
{
    public $description = '[Product Line] shows a list of products from the same brand by UPC prefix';
    public $themed = true;

    protected $report_headers = array('UPC', 'Brand', 'Description', 'Alt. Brand', 'Alt. Desc.', 'Price', 'Vendor');
    protected $title = "Fannie : Product Line";
    protected $header = "Fannie : Product Line";
    protected $required_fields = array('prefix');

    public function fetch_report_data()
    {
        $prefix = FormLib::get('prefix');
        $prefix = str_pad($prefix, '0', 5, STR_PAD_LEFT);

        $dbc = FannieDB::get($this->config->get('OP_DB'));

        $query = "
            SELECT p.upc,
                p.description,
                p.brand,
                u.description AS altDescription,
                u.brand AS altBrand,
                v.vendorName AS vendor,
                p.normal_price
            FROM products AS p
                LEFT JOIN productUser AS u ON p.upc=u.upc
                LEFT JOIN vendors AS v ON p.default_vendor_id=v.vendorID
            WHERE SUBSTRING(p.upc, 4, 5) = ?
            ORDER BY p.upc";
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, array($prefix));
        $data = array();
        while ($row = $dbc->fetch_row($result)) {
            $data[] = array(
                $row['upc'],
                $row['brand'],
                $row['description'],
                empty($row['altBrand']) ? 'n/a' : $row['altBrand'],
                empty($row['altDescription']) ? 'n/a' : $row['altDescription'],
                sprintf('%.2f', $row['normal_price']),
                empty($row['vendor']) ? 'n/a' : $row['vendor'],
            );
        }

        return $data;
    }
}

FannieDispatch::conditionalExec();

