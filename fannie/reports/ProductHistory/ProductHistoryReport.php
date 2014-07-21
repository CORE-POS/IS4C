<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

class ProductHistoryReport extends FannieReportPage 
{
    public $description = '[Product History] lists changes made to a given item over time.';

    protected $title = "Fannie : Product History";
    protected $header = "Product History Report";
    protected $report_headers = array('Date','Description', 'Price', 'Dept#', 'Tax', 'FS', 'Scale', 'Qty Rq\'d', 'NoDisc', 'UserID');
    protected $required_fields = array('upc');

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $date1 = FormLib::get_form_value('date1');
        $date2 = FormLib::get_form_value('date2');
        $upc = FormLib::get_form_value('upc');
        if (is_numeric($upc)) {
            $upc = BarcodeLib::padUPC($upc);
        }

        $table = 'prodUpdate';
        $def = $dbc->tableDefinition('prodUpdate');
        if (!isset($def['prodUpdateID'])) { // older schema
            $table = 'prodUpdateArchive';
        }
        $query = 'SELECT
                    upc,
                    description,
                    price,
                    salePrice,
                    cost,
                    dept,
                    tax,
                    fs,
                    scale,
                    likeCode,
                    modified,
                    user,
                    forceQty,
                    noDisc,
                    inUse
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

        $prep = $dbc->prepare_statement($query);
        $result = $dbc->exec_statement($prep,$args);

        $data = array();
        while($row = $dbc->fetch_row($result)) {
            $record = array(
                $row['modified'],
                $row['description'],
                $row['price'],
                $row['dept'],
                $row['tax'],
                $row['fs'],
                $row['scale'],
                $row['forceQty'],
                $row['noDisc'],
                $row['user'],
            );
            $data[] = $record;
        }

        return $data;
    }
    
    public function form_content()
    {
        $this->add_onload_command('$(\'#date1\').datepicker();');
        $this->add_onload_command('$(\'#date2\').datepicker();');
        return '
            <form method="get" action="ProductHistoryReport.php">
            <table>
            <tr>
                <th>UPC</th>
                <td><input type="text" name="upc" /></td>
                <td><i>Dates are optional; omit for full history</i></td>
            </tr>
            <tr>
                <th>Start Date</th>
                <td><input type="text" id="date1" name="date1" /></td>
                <td rowspan="2">' . FormLib::dateRangePicker() . '</td>
            </tr>
            <tr>
                <th>End Date</th>
                <td><input type="text" id="date2" name="date2" /></td>
            </tr>
            <tr>
                <td><input type="submit" value="Get Report" /></td>
            </tr>
            </table>
            </form>';
    }
}

FannieDispatch::conditionalExec();

