<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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

class ProductLineReport extends FannieReportPage 
{
    public $description = '[Product Line] shows a list of products from the same brand by UPC prefix';
    public $themed = true;
    public $discoverable = false;

    protected $report_headers = array('UPC', 'Brand', 'Description', 'Alt. Brand', 'Alt. Desc.', 'Price', 'Dept', 'Vendor', 'Location');
    protected $title = "Fannie : Product Line";
    protected $header = "Fannie : Product Line";
    protected $required_fields = array('prefix');

    public function fetch_report_data()
    {
        $prefix = $this->form->prefix;
        $prefix = str_pad($prefix, '0', 5, STR_PAD_LEFT);

        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        if ($dbc->tableExists('FloorSections')) {
            $loc_col = 'f.name AS floorSection';
        } else {
            $loc_col = "'n/a' AS floorSection";
        }

        $query = "
            SELECT p.upc,
                p.description,
                p.brand,
                u.description AS altDescription,
                u.brand AS altBrand,
                v.vendorName AS vendor,
                p.normal_price,
                d.dept_no,
                d.dept_name,
                {$loc_col}
            FROM products AS p
                LEFT JOIN departments AS d ON p.department=d.dept_no
                LEFT JOIN productUser AS u ON p.upc=u.upc
                LEFT JOIN vendors AS v ON p.default_vendor_id=v.vendorID
                LEFT JOIN prodPhysicalLocation AS y ON p.upc=y.upc
                ";
        if ($dbc->tableExists('FloorSections')) {
            $query .= ' LEFT JOIN FloorSections AS f ON y.floorSectionID=f.floorSectionID ';
        }
        $query .= " 
            WHERE SUBSTRING(p.upc, 4, 5) = ? ";
        $args = array($prefix);
        if ($this->config->get('STORE_MODE') == 'HQ') {
            $query .= ' AND p.store_id=? ';
            $args[] = $this->config->get('STORE_ID');
        }
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, $args);
        $data = array();
        while ($row = $dbc->fetchRow($result)) {
            $data[] = $this->rowToRecord($row);
        }

        return $data;
    }

    private function rowToRecord($row)
    {
        return array(
            $row['upc'],
            $row['brand'],
            $row['description'],
            empty($row['altBrand']) ? 'n/a' : $row['altBrand'],
            empty($row['altDescription']) ? 'n/a' : $row['altDescription'],
            sprintf('%.2f', $row['normal_price']),
            $row['dept_no'] . ' ' . $row['dept_name'],
            empty($row['vendor']) ? 'n/a' : $row['vendor'],
            empty($row['floorSection']) ? 'n/a' : $row['floorSection'],
        );
    }

    public function form_content()
    {
        return 'No direct entries allowed on this report';
    }

    public function unitTest($phpunit)
    {
        $data = array('upc'=>'4011', 'brand'=>'test', 'description'=>'test',
            'altBrand'=>'test', 'altDescription'=>'test', 'normal_price'=>1,
            'vendor'=>'test', 'floorSection'=>'test', 'dept_no'=>1, 'dept_name'=>'foo');
        $phpunit->assertInternalType('array', $this->rowToRecord($data));
    }
}

FannieDispatch::conditionalExec();

