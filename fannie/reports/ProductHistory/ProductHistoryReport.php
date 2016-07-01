<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

class ProductHistoryReport extends FannieReportPage 
{
    public $description = '[Product History] lists changes made to a given item over time.';
    public $themed = true;
    public $report_set = 'Operational Data';

    protected $title = "Fannie : Product History";
    protected $header = "Product History Report";
    protected $report_headers = array('Date','Description', 'Price', 'Cost', 'Sale Price', 'Dept#', 'Tax', 'FS', 'WIC', 'Scale', 'Qty Rq\'d', 'NoDisc', 'UserID', 'Update Type');
    protected $required_fields = array('upc');

    protected $sort_direction = 1;

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $date1 = FormLib::get_form_value('date1');
        $date2 = FormLib::get_form_value('date2');
        $upc = $this->form->upc;
        if (is_numeric($upc)) {
            $upc = BarcodeLib::padUPC($upc);
        }

        $table = 'prodUpdate';
        $query = 'SELECT
                    upc,
                    description,
                    price,
                    salePrice,
                    cost,
                    dept,
                    tax,
                    fs,
                    wic,
                    scale,
                    likeCode,
                    modified,
                    user,
                    forceQty,
                    noDisc,
                    inUse,
                    salePrice,
                    updateType
                  FROM ' . $table . '
                  WHERE upc = ?';
        $args = array($upc);
        if ($date1 !== '' && $date2 !== '') {
            // optional: restrict report by date
            $query .= ' AND modified BETWEEN ? AND ? ';
            $args[] = $date1 . ' 00:00:00';
            $args[] = $date2 . ' 23:59:59';
        }
        $query .= ' ORDER BY modified DESC';

        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep,$args);

        $data = array();
        while ($row = $dbc->fetchRow($result)) {
            $data[] = $this->rowToRecord($row);
        }

        return $data;
    }

    private function rowToRecord($row)
    {
        return array(
            $row['modified'],
            $row['description'],
            $row['price'],
            $row['cost'],
            $row['salePrice'],
            $row['dept'],
            $row['tax'],
            $row['fs'],
            $row['wic'] === null ? 'n/a' : $row['wic'],
            $row['scale'],
            $row['forceQty'],
            $row['noDisc'],
            $row['user'],
            $row['updateType'],
        );
    }
    
    public function form_content()
    {
        return '
            <form method="get" action="ProductHistoryReport.php">
            <div class="well">Dates are optional; omit for full history</div>
            <div class="col-sm-4">
            <div class="form-group">
                <label>UPC</label>
                <input type="text" name="upc" class="form-control" required />
            </div>
            <div class="form-group">
                <label>Start Date</label>
                <input type="text" id="date1" name="date1" class="form-control date-field" />
            </div>
            <div class="form-group">
                <label>End Date</label>
                <input type="text" id="date2" name="date2" class="form-control date-field" />
            </div>
            <p>
                <button type="submit" class="btn btn-default">Get Report</button>
            </p>
            </div>
            <div class="col-sm-4">
                ' . FormLib::dateRangePicker() . '
            </div>
            </table>
            </form>';
    }

    public function helpContent()
    {
        return '<p>
            List audit log of changes to a given item.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $data = array('modified'=>'2000-01-01', 'description'=>'test', 'price'=>1,
            'cost'=>1, 'dept'=>1, 'tax'=>0, 'fs'=>1, 'scale'=>0, 'forceQty'=>0,
            'noDisc'=>0, 'user'=>1234, 'wic'=>1, 'salePrice'=>0, 'updateType'=>'TEST');
        $phpunit->assertInternalType('array', $this->rowToRecord($data));
    }
}

FannieDispatch::conditionalExec();

