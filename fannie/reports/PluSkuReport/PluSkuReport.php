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

class PluSkuReport extends FannieReportPage 
{
    public $description = '[PLU/SKU Report] lists PLUs in the given department(s) and whether or
        not they have associated vendor SKUs.';
    public $report_set = 'Operational Data';
    public $themed = true;

    protected $report_headers = array('UPC', 'Brand', 'Description', 'Vendor', 'Has Mapping', 'Mapping SKU', 'In Catalog', 'Catalog SKU');
    protected $sort_direction = 1;
    protected $title = "Fannie : PLU/SKU Report";
    protected $header = "PLU/SKU Report";
    protected $required_fields = array('submit');

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $super = FormLib::get('super');
        $departments = FormLib::get('departments', array());
        $deptStart = FormLib::get('deptStart');
        $deptEnd = FormLib::get('deptEnd');
        $subdepts = FormLib::get('subdepts', array());

        $args = array();
        $query = '
            SELECT p.upc,
                p.brand,
                p.description,
                n.vendorName,
                m.sku AS mapSKU,
                i.sku AS catalogSKU,
                n.vendorID
            FROM products AS p 
                LEFT JOIN vendorItems AS i ON p.upc=i.upc AND p.default_vendor_id=i.vendorID
                LEFT JOIN VendorAliases AS m ON p.upc=m.upc AND p.default_vendor_id=m.vendorID
                LEFT JOIN vendors AS n ON p.default_vendor_id=n.vendorID ';
        if ($super !== '' && $super > -1) {
            $query .= ' LEFT JOIN superdepts AS s ON p.department=s.dept_ID ';
        } elseif ($super !== '' && $super == -2) {
            $query .= ' LEFT JOIN MasterSuperDepts AS s ON p.department=s.dept_ID ';
        }
        $query .= ' WHERE p.upc LIKE \'000000%\'
            AND p.inUse=1 ';
        if ($super !== '' && $super > -1) {
            $query .= ' AND s.superID=? ';
            $args[] = $super;
        } elseif ($super !== '' && $super == -2) {
            $query .= ' AND s.superID<>0 ';
        } 
        if (count($departments) > 0) {
            list($inStr, $args) = $dbc->safeInClause($departments, $args);
            $query .= ' AND p.department IN (' . $inStr . ') ';
        } else {
            $query .= ' AND p.department BETWEEN ? AND ? ';
            $args[] = $deptStart;
            $args[] = $deptEnd;
        }
        if (count($subdepts) > 0) {
            list($inStr, $args) = $dbc->safeInClause($subdepts, $args);
            $query .= ' AND p.subdept IN (' . $inStr . ') ';
        }
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $args);
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $data[] = $this->rowToRecord($row);
        }

        return $data;
    }

    private function rowToRecord($row)
    {
        $record = array(
            $row['upc'],
            $row['brand'],
            $row['description'],
            ($row['vendorName'] == null ? 'n/a' : $row['vendorName']),
            ($row['mapSKU'] == null ? 'No' : 'Yes'),
            ($row['mapSKU'] == null ? 'n/a' : $row['mapSKU']),
            ($row['catalogSKU'] == null ? 'No' : 'Yes'),
            ($row['catalogSKU'] == null ? 'n/a' : $row['catalogSKU']),
        );
        if ($row['vendorID'] && $this->report_format == 'html') {
            $text = $record[5];
            $link = '<a href="../../item/vendors/SkuMapPage.php?id=' . $row['vendorID'] . '">'
                . $text . '</a>';
            $record[5] = $link;
        }

        return $record;
    }

    public function form_content()
    {
        $ret = '<form method="get" class="form-horizontal pull-left">'
            . FormLib::standardDepartmentFields() .
            '<p>
            <button type="submit" name="submit" value="1" 
                class="btn btn-default">Get Report</button>     
            </p>
            </form>';

        return $ret;
    }

    public function helpContent()
    {
        return '<p>
            View all Accounts Receivable (AR) activity for a given member.
            Enter the desired member number.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $data = array('upc'=>'4011', 'brand'=>'test', 'description'=>'test',
            'vendorName'=>'test', 'mapSKU'=>'9BAN', 'catalogSKU'=>'9BAN',
            'vendorID'=>1);
        $phpunit->assertInternalType('array', $this->rowToRecord($data));
    }
}

FannieDispatch::conditionalExec();

