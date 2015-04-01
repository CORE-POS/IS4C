<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(dirname(__FILE__) . '/../../classlib2.0/FannieAPI.php');
}

class VendorSalesReport extends FannieReportPage
{
    public $themed = true;
    public $description = '[Vendor Sales] lists sales totals by vendor for a date range.';
    protected $header = 'Vendor Sales Report';
    protected $title = 'Vendor Sales Report';
    protected $required_fields = array('date1', 'date2');
    protected $report_headers = array('Vendor', 'Qty', '$ Sales', '% Sales');
    protected $sort_column = 3;
    protected $sort_direction = 1;

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $date1 = FormLib::getDate('date1', date('Y-m-d'));
        $date2 = FormLib::getDate('date2', date('Y-m-d'));
        $deptStart = FormLib::get('deptStart', 1);
        $deptEnd = FormLib::get('deptStart', 1);
        $buyer = FormLib::get('buyer', '');
        $dlog = DTransactionsModel::selectDlog($date1, $date2);

        /**
          Report Query notes:
          * Combining vendorName and prodExtra.distributor is a nod to
            legacy data. Eventually data should be fully normalized on
            products.default_vendor_id
          * Excluding prodExtra.distributor empty string combines those
            records with SQL NULL. Having two different "blank" rows
            is confusing for users.
          * Joins are only needed is a super department condition is 
            involved. WHERE clause changes similarly.
        */
        $query = '
            SELECT COALESCE(v.vendorName, x.distributor) AS vendor,
                ' . DTrans::sumQuantity('t') . ' AS qty,
                SUM(t.total) AS ttl
            FROM ' . $dlog . ' AS t
                LEFT JOIN products AS p ON t.upc=p.upc
                LEFT JOIN vendors AS v ON p.default_vendor_id=v.vendorID
                LEFT JOIN prodExtra AS x ON p.upc=x.upc AND x.distributor <> \'\'
                ';
        if ($buyer !== '' && $buyer == -1) {
            $query .= ' LEFT JOIN MasterSuperDepts AS s ON t.department=s.dept_ID ';
        } elseif ($buyer !== '' && $buyer >= 0) {
            $query .= ' LEFT JOIN superdepts AS s ON t.department=s.dept_ID ';
        }
        $query .= '
            WHERE t.tdate BETWEEN ? AND ?
                AND t.trans_type IN (\'I\',\'D\') ';
        $args = array($date1 . ' 00:00:00', $date2 . ' 23:59:59');
        if ($buyer === '') {
            $query .= ' AND t.department BETWEEN ? AND ? ';
            $args[] = $dept1;
            $args[] = $dept2;
        } elseif ($buyer == -1) {
            $query .= ' AND s.superID <> 0 ';
        } elseif ($buyer >= 0) {
            $query .= ' AND s.superID = ? ';
            $args[] = $buyer;
        }
        $query .= '
            GROUP BY COALESCE(v.vendorName, x.distributor)
            ORDER BY SUM(total) DESC';

        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, $args);
        $data = array();
        $total_sales = 0.0;
        while ($w = $dbc->fetch_row($result)) {
            $data[] = array(
                $w['vendor'],
                sprintf('%.2f', $w['qty']),
                sprintf('%.2f', $w['ttl']),
                0.0, // placeholder for percentage of total
            );
            $total_sales += $w['ttl'];
        }

        for ($i=0; $i<count($data); $i++) {
            $data[$i][3] = sprintf('%.2f%%', $data[$i][2] / $total_sales * 100); 
            $data[$i][2] = '$' . $data[$i][2];
        }

        return $data;
    }

    public function calculate_footers($data)
    {
        $sums = array(0.0, 0.0, 0.0);
        foreach ($data as $row) {
            $sums[0] += $row[1];
            $sums[1] += trim($row[2], '$');
            $sums[2] += trim($row[3], '%');
        }
        return array('', $sums[0], '$' . $sums[1], $sums[2] . '%');
    }

    public function form_content()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $depts = new DepartmentsModel($dbc);
        $supers = new SuperDeptNamesModel($dbc);

        return FormLib::dateAndDepartmentForm($depts->find('dept_no'), $supers->find('superID'), true);
    }
}

FannieDispatch::conditionalExec();

