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

class VendorCategoryReport extends FannieReportPage 
{
    protected $required_fields = array('id', 'category');
    public $description = '[Vendor Category] lists items a particular vendor category';
    public $report_set = 'Vendors';
    protected $title = "Fannie : Vendor Category";
    protected $header = "Vendor Category Report";

    protected $report_headers = array('UPC', 'Brand', 'Description', 'Dept#', 'Dept', 'Cost', 'Price', 'Margin');

    public function report_description_content()
    {
        try {
            $dept = new VendorDepartmentsModel($this->connection);
            $dept->vendorID($this->form->id);
            $dept->deptID($this->form->category);
            if ($dept->load()) {
                return array(
                    'Category: ' . $dept->name(),
                    'Margin Target: ' . sprintf('%.2f%%', $dept->margin()*100),
                );
            } else {
                return array();
            }
        } catch (Exception $ex) {
            return array();
        }
    }

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        try {
            $vendorID = $this->form->id;
            $deptID = $this->form->category;
        } catch (Exception $ex) {
            return array();
        }

        $prep = $dbc->prepare('
            SELECT p.upc,
                p.brand,
                p.description,
                p.department,
                d.dept_name,
                p.cost,
                p.normal_price
            FROM vendorItems AS v
                INNER JOIN products AS p ON p.default_vendor_id=v.vendorID AND p.upc=v.upc
                LEFT JOIN departments AS d ON p.department=d.dept_no
            WHERE v.vendorID=?
                AND v.vendorDept=?
        ');
        $res = $dbc->execute($prep, array($vendorID, $deptID));
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
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
            $row['department'],
            $row['dept_name'],
            $row['cost'],
            $row['normal_price'],
            sprintf('%.2f%%', \COREPOS\Fannie\API\item\Margin::toMargin($row['cost'], $row['normal_price'])*100),
        );
    }

    public function calculate_footers($data)
    {
        $sum = array_reduce($data, function($carry,$i){ return $carry + $i[7]; });
        $avg = count($data) == 0 ? 0 : $sum / count($data);

        return array('Average Margin', null, null, null, null, null, null, sprintf('%.2f%%', $avg));
    }

    public function form_content()
    {
        return <<<HTML
<div class="alert alert-danger">Direct input not supported</div>
<p>
Go to <a href="../../item/vendors/VendorIndexPage.php">Manage Vendors</a>, select a vendor,
and choose <em>View or Edit vendor subcategory margin(s)</em>. Click the rightmost icon for a subcategory
to view a report of items in that subcategory.
</p>
HTML;
    }

    public function unitTest($phpunit)
    {
        $data = array('upc'=>'4011', 'brand'=>'test', 'description'=>'test',
            'department'=>1, 'dept_name'=>'test', 'cost'=>1, 'normal_price'=>2,
            'percent'=>1);
        $phpunit->assertInternalType('array', $this->rowToRecord($data));
        $phpunit->assertInternalType('array', $this->calculate_footers($this->dekey_array(array($data))));
    }
}

FannieDispatch::conditionalExec();

