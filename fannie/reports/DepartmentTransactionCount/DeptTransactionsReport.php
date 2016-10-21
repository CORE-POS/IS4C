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

class DeptTransactionsReport extends FannieReportPage 
{
    public $description = '[Department Transactions] lists the number of transactions in a department
        or departments over a given date range.';
    public $themed = true;
    public $report_set = 'Transaction Reports';

    protected $report_headers = array('Date', '# Matching Trans', '# Total Trans', '%');

    protected $title = "Fannie : Department Transactions Report";
    protected $header = "Department Transactions";

    protected $required_fields = array('date1', 'date2');

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $date1 = $this->form->date1;
        $date2 = $this->form->date2;
        $deptStart = FormLib::get('deptStart');
        $deptEnd = FormLib::get('deptEnd');
        $deptMulti = FormLib::get('departments', array());
    
        $buyer = FormLib::get('buyer', '');

        $dlog = DTransactionsModel::selectDlog($date1, $date2);

        $queryAll = "SELECT YEAR(tdate) AS year, MONTH(tdate) AS month, DAY(tdate) AS day,
            COUNT(DISTINCT trans_num) as trans_count
            FROM $dlog AS d 
            WHERE tdate BETWEEN ? AND ?
            GROUP BY YEAR(tdate), MONTH(tdate), DAY(tdate)
            ORDER BY YEAR(tdate), MONTH(tdate), DAY(tdate)";
        $argsAll = array($date1.' 00:00:00',$date2.' 23:59:59');

        $querySelected = "SELECT YEAR(tdate) AS year, MONTH(tdate) AS month, DAY(tdate) AS day,
            COUNT(DISTINCT trans_num) as trans_count
            FROM $dlog AS d ";
        if ($buyer !== '' && $buyer > -1) {
            $querySelected .= " LEFT JOIN superdepts AS s ON d.department=s.dept_ID ";
        } elseif ($buyer !== '' && $buyer == -2) {
            $query .= 'LEFT JOIN MasterSuperDepts AS s ON d.department=s.dept_ID ';
        }
        $querySelected .= " WHERE tdate BETWEEN ? AND ? ";
        $argsSel = $argsAll;
        if ($buyer !== '') {
            if ($buyer == -2) {
                $querySelected .= ' AND s.superID != 0 ';
            } elseif ($buyer != -1) {
                $querySelected .= ' AND s.superID=? ';
                $argsSel[] = $buyer;
            }
        }
        if ($buyer != -1) {
            list($conditional, $argsSel) = DTrans::departmentClause($deptStart, $deptEnd, $deptMulti, $argsSel);
            $querySelected .= $conditional;
        }
        $querySelected .= " GROUP BY YEAR(tdate), MONTH(tdate), DAY(tdate)";

        $dataset = array();

        $prep = $dbc->prepare($queryAll);
        $result = $dbc->execute($prep,$argsAll);
        while($row = $dbc->fetch_row($result)) {
            $datestr = sprintf("%d/%d/%d",$row['month'],$row['day'],$row['year']);
            $dataset[$datestr] = array('ttl'=>$row['trans_count'],'sub'=>0);
        }

        $prep = $dbc->prepare($querySelected);
        $result = $dbc->execute($prep,$argsSel);
        while($row = $dbc->fetch_row($result)) {
            $datestr = sprintf("%d/%d/%d",$row['month'],$row['day'],$row['year']);
            if (isset($dataset[$datestr])) {
                $dataset[$datestr]['sub'] = $row['trans_count'];
            }
        }

        $data = array();
        foreach($dataset as $date => $count){
            $record = array($date, $count['sub'], $count['ttl']);
            $record[] = sprintf('%.2f%%', ($count['sub']/$count['ttl'])*100);
            $data[] = $record;
        }

        return $data;
    }

    public function form_content()
    {
        return FormLib::dateAndDepartmentForm();
    }

    public function helpContent()
    {
        return '<p>
            Lists number of transactions in a department or set of
            departments over a given date range.
            </p>';
    }
}

FannieDispatch::conditionalExec();

