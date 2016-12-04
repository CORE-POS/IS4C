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

    protected $title = "Fannie : Margin Movement Report";
    protected $header = "Margin Movement Report";

    protected $sort_column = 5;
    protected $sort_direction = 1;

    protected $report_headers = array('UPC', 'Brand', 'Desc', 'Dept#', 'Dept', 'Cost', 'Sales', 'Margin', 'Markup', 'Contrib');
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
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $date1 = $this->form->date1;
        $date2 = $this->form->date2;
        $deptStart = FormLib::get('deptStart');
        $deptEnd = FormLib::get('deptEnd');
        $deptMulti = FormLib::get('departments', array());
        $subs = FormLib::get('subdepts', array());
        $include_sales = FormLib::get('includeSales', 0);
    
        $buyer = FormLib::get('buyer', '');

        // args/parameters differ with super
        // vs regular department
        $args = array($date1.' 00:00:00', $date2.' 23:59:59');
        $where = ' 1=1 ';
        if ($buyer !== '') {
            if ($buyer == -2) {
                $where .= ' AND s.superID != 0 ';
            } elseif ($buyer != -1) {
                $where .= ' AND s.superID=? ';
                $args[] = $buyer;
            }
        }
        if ($buyer != -1) {
            list($conditional, $args) = DTrans::departmentClause($deptStart, $deptEnd, $deptMulti, $args);
            $where .= $conditional;
        }
        if (count($subs) > 0) {
            list($inStr, $args) = $dbc->safeInClause($subs, $args);
            $where .= " AND p.subdept IN ($inStr) ";
        }

        $dlog = DTransactionsModel::selectDlog($date1, $date2);

        $query = "SELECT d.upc,
                    p.brand,
                    p.description,
                    d.department,
                    t.dept_name,
                    SUM(total) AS total,
                    SUM(
                        CASE WHEN (d.cost > 0 AND d.total < 0) OR (d.cost < 0 AND d.total > 0)
                            THEN -1*d.cost
                            ELSE d.cost
                        END
                    ) AS cost,"
                    . DTrans::sumQuantity('d') . " AS qty
                  FROM $dlog AS d "
                    . DTrans::joinProducts('d', 'p', 'inner')
                    . DTrans::joinDepartments('d', 't');
        // join only needed with specific buyer
        if ($buyer !== '' && $buyer > -1) {
            $query .= 'LEFT JOIN superdepts AS s ON d.department=s.dept_ID ';
        } elseif ($buyer !== '' && $buyer == -2) {
            $query .= 'LEFT JOIN MasterSuperDepts AS s ON d.department=s.dept_ID ';
        }
        $query .= "WHERE tdate BETWEEN ? AND ?
            AND $where
            AND d.cost <> 0 ";
        if ($include_sales != 1) {
            $query .= "AND d.discounttype=0 ";
        }
        $query .= "GROUP BY d.upc,p.description,d.department,t.dept_name
            ORDER BY sum(total) DESC";

        $prep = $dbc->prepare($query);
        $result = $dbc->execute($query, $args);

        $data = array();
        $sum_total = 0.0;
        $sum_cost = 0.0;
        while ($row = $dbc->fetchRow($result)) {
            $sum_total += $row['total'];
            $sum_cost += $row['cost'];

            $data[] = $this->rowToRecord($row);
        }

        // go through and add a contribution to margin value
        for ($i=0; $i<count($data); $i++) {
            // (item_total - item_cost) / total sales
            $contrib = ($data[$i][5] - $data[$i][4]) / $sum_total * 100;
            $data[$i][] = sprintf('%.2f', $contrib);
        }

        return $data;
    }

    private function rowToRecord($row)
    {
        $margin = $row['total'] == 0 ? 0 : ($row['total'] - $row['cost']) / $row['total'] * 100;
        return array(
            $row['upc'],
            $row['brand'],
            $row['description'],
            $row['department'],
            $row['dept_name'],
            sprintf('%.2f', $row['cost']),
            sprintf('%.2f', $row['total']),
            sprintf('%.2f', $margin),
            sprintf('%.2f', $row['qty'] == 0 ? 0 : ($row['total'] - $row['cost']) / $row['qty']),
        );
    }

    public function calculate_footers($data)
    {
        if (count($data) == 0) {
            return array();
        }

        $sum_cost = 0.0;
        $sum_ttl = 0.0;
        foreach($data as $row) {
            $sum_cost += $row[5];
            $sum_ttl += $row[6];
        }

        return array('Totals', null, null, null, null, sprintf('%.2f',$sum_cost), sprintf('%.2f',$sum_ttl), '', null, null);
    }

    public function form_content()
    {
        $form = FormLib::dateAndDepartmentForm();

        /** add one extra field **/
        $checkbox = '<div class=col-sm-5>'
            . '<label><input type=checkbox name=includeSales value=1 />'
            . ' Include Sale Items</label>'
            . '</div>';
        $this->add_onload_command("\$('#date-dept-form-left-col').after('$checkbox');\n");

        return $form;
    }

    public function helpContent()
    {
        return '<p>
            This movement report includes total costs as well as
            sales and calculates both margin and contribution to
            margin.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $data = array('total'=>10, 'cost'=>5, 'upc'=>'4011', 'brand'=>'test',
            'description'=>'test', 'department'=>1, 'dept_name'=>'test', 'qty'=>1);
        $phpunit->assertInternalType('array', $this->rowToRecord($data));
        $phpunit->assertInternalType('array', $this->calculate_footers($this->dekey_array(array($data))));
    }
}

FannieDispatch::conditionalExec();

