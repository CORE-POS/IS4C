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

class VendorListReport extends FannieReportPage 
{
    protected $required_fields = array('store', 'super');
    public $description = '[Vendor List] displays current vendors';
    public $report_set = 'Vendors';
    protected $title = "Fannie : Vendor List";
    protected $header = "Vendor List Report";
    protected $report_headers = array('Vendor', 'Phone', 'Fax', 'Email', 'Notes','# of items');

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        try {
            $super = $this->form->super;
            $store = $this->form->store;
        } catch (Exception $ex) {
            return array();
        }

        $prep = $dbc->prepare('
            SELECT v.vendorName,
                v.phone,
                v.fax,
                v.email,
                v.notes,
                COUNT(p.upc) AS skus
            FROM vendors AS v
                LEFT JOIN products AS p ON p.default_vendor_id=v.vendorID
                LEFT JOIN superdepts AS s ON p.department=s.dept_ID
            WHERE s.superID=?
                AND p.store_id=?
            GROUP BY v.vendorName,
                v.phone,
                v.fax,
                v.email');
        
        $res = $dbc->execute($prep, array($super, $store));
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $data[] = $this->rowToRecord($row);
        }

        return $data;
    }

    private function rowToRecord($row)
    {
        return array(
            $row['vendorName'],
            $row['phone'] == null ? '' : $row['phone'],
            $row['fax'] == null ? '' : $row['fax'],
            $row['email'] == null ? '' : $row['email'],
            $row['notes'] == null ? '' : $row['notes'],
            $row['skus'],
        );
    }

    public function form_content()
    {
        $dbc = $this->connection;
        $names = new SuperDeptNamesModel($dbc);
        $stores = FormLib::storePicker();
        $ret = '
            <form method="get">
            <div class="form-group">
                <label>Super Department</label>
                <select name="super" class="form-control">
                ' . $names->toOptions() . '
                </select>
            </div> 
            <div class="form-group">
                <label>Store</label>
                ' . $stores['html'] . '
            </div> 
            <div class="form-group">
                <p><button type="submit" class="btn btn-default btn-core">Submit</button></p>
            </div> 
            </form>';

        return $ret;
    }

    public function unitTest($phpunit)
    {
        $data = array('vendorName'=>'foo', 'phone'=>1, 'fax'=>1, 'email'=>1, 'skus'=>1, 'notes'=>'foo');
        $phpunit->assertInternalType('array', $this->rowToRecord($data));
    }
}

FannieDispatch::conditionalExec();

