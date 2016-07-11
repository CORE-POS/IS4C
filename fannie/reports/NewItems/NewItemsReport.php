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

class NewItemsReport extends FannieReportPage 
{
    public $description = '[New Items] shows products recently added to POS. This is more
        approximate than definitive.';
    public $themed = true;
    public $report_set = 'Operational Data';

    protected $title = "Fannie : New Items Report";
    protected $header = "New Items Report";

    protected $report_headers = array('Added', 'UPC', 'Desc', 'Dept#', 'Dept');
    protected $required_fields = array('date1', 'date2');

    public function report_description_content()
    {
        $deptStart = FormLib::get('deptStart');
        $deptEnd = FormLib::get('deptEnd');
        $buyer = FormLib::get('buyer', '');
    
        $ret = array();
        if ($buyer === '') {
            $ret[] = 'Department '.$deptStart.' to '.$deptEnd;
        } else if ($buyer == -1) {
            $ret[] = 'All Super Departments';
        } else {
            $ret[] = 'Super Department '.$buyer;
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
    
        $buyer = FormLib::get('buyer', '');

        // args/parameters differ with super
        // vs regular department
        $args = array();
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
            list($conditional, $args) = DTrans::departmentClause($deptStart, $deptEnd, $deptMulti, $args, 'p');
            $where .= $conditional;
        }
        if (count($subs) > 0) {
            list($inStr, $args) = $dbc->safeInClause($subs, $args);
            $where .= " AND p.subdept IN ($inStr) ";
        }
        $args[] = $date1.' 00:00:00';
        $args[] = $date2.' 23:59:59';

        $query = "SELECT MIN(CASE WHEN a.modified IS NULL THEN p.modified ELSE a.modified END) AS entryDate, 
            a.upc, p.description, p.department, d.dept_name
            FROM products AS p INNER JOIN prodUpdate AS a ON a.upc=p.upc
            LEFT JOIN departments AS d ON d.dept_no=p.department ";
        // join only needed with specific buyer
        if ($buyer !== '' && $buyer > -1) {
            $query .= 'LEFT JOIN superdepts AS s ON p.department=s.dept_ID ';
        } elseif ($buyer !== '' && $buyer == -2) {
            $query .= 'LEFT JOIN MasterSuperDepts AS s ON p.department=s.dept_ID ';
        }
        $query .= "WHERE $where
            GROUP BY p.upc,p.description,p.department, d.dept_name
            HAVING entryDate BETWEEN ? AND ?
            ORDER BY entryDate";

        $prep = $dbc->prepare($query);
        $result = $dbc->execute($query, $args);

        $data = array();
        while ($row = $dbc->fetchRow($result)) {
            $data[] = $this->rowToRecord($row);
        }

        return $data;
    }

    private function rowToRecord($row)
    {
        return array(
            $row['entryDate'],
            $row['upc'],
            $row['description'],
            $row['department'],
            $row['dept_name'],
        );
    }

    public function form_content()
    {
        return FormLib::dateAndDepartmentForm();
    }

    public function helpContent()
    {
        return '<p>
            List items that were added to POS
            in the given date range.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $data = array('entryDate'=>'2000-01-01','upc'=>'4011',
            'description'=>'test','department'=>1,'dept_name'=>'test');
        $phpunit->assertInternalType('array', $this->rowToRecord($data));
    }
}

FannieDispatch::conditionalExec();

