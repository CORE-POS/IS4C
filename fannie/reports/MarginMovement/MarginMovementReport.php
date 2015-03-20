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

class MarginMovementReport extends FannieReportPage 
{
    public $description = '[Margin Movement] lists item movement with margin information.';
    public $report_set = 'Movement Reports';
    public $themed = true;

    protected $title = "Fannie : Margin Movement Report";
    protected $header = "Margin Movement Report";

    protected $sort_column = 5;
    protected $sort_direction = 1;

    protected $report_headers = array('UPC', 'Desc', 'Dept#', 'Dept', 'Cost', 'Sales', 'Margin', 'Markup', 'Contrib');
    protected $required_fields = array('date1', 'date2');

    public function report_description_content()
    {
        $deptStart = FormLib::get('deptStart');
        $deptEnd = FormLib::get('deptEnd');
        $include_sales = FormLib::get('includeSales', 0);
        $buyer = FormLib::get('buyer', '');
    
        $ret = array();
        if ($buyer === '') {
            $ret[] = 'Department '.$deptStart.' to '.$deptEnd;
        } else if ($buyer == -1) {
            $ret[] = 'All Super Departments';
        } else {
            $ret[] = 'Super Department '.$buyer;
        }

        if ($include_sales == 1) {
            $ret[] = 'Includes sale items';
        }

        return $ret;
    }

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $date1 = FormLib::get('date1', date('Y-m-d'));
        $date2 = FormLib::get('date2', date('Y-m-d'));
        $deptStart = FormLib::get('deptStart');
        $deptEnd = FormLib::get('deptEnd');
        $include_sales = FormLib::get('includeSales', 0);
    
        $buyer = FormLib::get('buyer', '');

        // args/parameters differ with super
        // vs regular department
        $args = array($date1.' 00:00:00', $date2.' 23:59:59');
        $where = ' 1=1 ';
        if ($buyer !== '') {
            if ($buyer != -1) {
                $where = ' s.superID=? ';
                $args[] = $buyer;
            }
        } else {
            $where = ' d.department BETWEEN ? AND ? ';
            $args[] = $deptStart;
            $args[] = $deptEnd;
        }

        $dlog = DTransactionsModel::selectDlog($date1, $date2);

        $query = "SELECT d.upc,
                    p.description,
                    d.department,
                    t.dept_name,
                    SUM(total) AS total,
                    SUM(d.cost) AS cost,"
                    . DTrans::sumQuantity('d') . " AS qty
                  FROM $dlog AS d "
                    . DTrans::joinProducts('d', 'p', 'inner')
                    . DTrans::joinDepartments('d', 't');
        // join only needed with specific buyer
        if ($buyer !== '' && $buyer > -1) {
            $query .= 'LEFT JOIN superdepts AS s ON d.department=s.dept_ID ';
        }
        $query .= "WHERE tdate BETWEEN ? AND ?
            AND $where
            AND d.cost <> 0 ";
        if ($include_sales != 1) {
            $query .= "AND d.discounttype=0 ";
        }
        $query .= "GROUP BY d.upc,p.description,d.department,t.dept_name
            ORDER BY sum(total) DESC";

        $prep = $dbc->prepare_statement($query);
        $result = $dbc->exec_statement($query, $args);

        $data = array();
        $sum_total = 0.0;
        $sum_cost = 0.0;
        while($row = $dbc->fetch_row($result)) {
            $margin = ($row['total'] - $row['cost']) / $row['total'] * 100;
            $record = array(
                $row['upc'],
                $row['description'],
                $row['department'],
                $row['dept_name'],
                sprintf('%.2f', $row['cost']),
                sprintf('%.2f', $row['total']),
                sprintf('%.2f', $margin),
                sprintf('%.2f', ($row['total'] - $row['cost']) / $row['qty']),
            );

            $sum_total += $row['total'];
            $sum_cost += $row['cost'];

            $data[] = $record;
        }

        // go through and add a contribution to margin value
        for ($i=0; $i<count($data); $i++) {
            // (item_total - item_cost) / total sales
            $contrib = ($data[$i][5] - $data[$i][4]) / $sum_total * 100;
            $data[$i][] = sprintf('%.2f', $contrib);
        }

        return $data;
    }

    public function calculate_footers($data)
    {
        if (count($data) == 0) {
            return array();
        }

        $sum_cost = 0.0;
        $sum_ttl = 0.0;
        foreach($data as $row) {
            $sum_cost += $row[4];
            $sum_ttl += $row[5];
        }

        return array('Totals', null, null, null, sprintf('%.2f',$sum_cost), sprintf('%.2f',$sum_ttl), '', null, null);
    }

    public function form_content()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $depts = new DepartmentsModel($dbc);
        $d_list = $depts->find('dept_no');
        $supers = new SuperDeptNamesModel($dbc);
        $supers->superID(0, '>');
        $s_list = $supers->find('superID');

        $form = FormLib::dateAndDepartmentForm($d_list, $s_list);

        /** add one extra field **/
        $checkbox = '<div class=col-sm-5>'
            . '<label><input type=checkbox name=includeSales value=1 />'
            . ' Include Sale Items</label>'
            . '</div>';
        $this->add_onload_command("\$('#date-dept-form-left-col').after('$checkbox');\n");

        return $form;
    }
}

FannieDispatch::conditionalExec();

?>
