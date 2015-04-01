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

    protected $report_headers = array('Date', '# Matching Trans', '# Total Trans', '%');

    protected $title = "Fannie : Department Transactions Report";
    protected $header = "Department Transactions";

    protected $required_fields = array('date1', 'date2');

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $date1 = FormLib::get('date1', date('Y-m-d'));
        $date2 = FormLib::get('date2', date('Y-m-d'));
        $deptStart = FormLib::get('deptStart');
        $deptEnd = FormLib::get('deptEnd');
    
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
        if ($buyer !== '') {
            $querySelected .= " LEFT JOIN superdepts AS s ON d.department=s.dept_ID ";
        }
        $querySelected .= " WHERE tdate BETWEEN ? AND ? ";
        $argsSel = $argsAll;
        if ($buyer !== '') {
            $querySelected .= " AND s.superID=? ";
            $argsSel[] = $buyer;
        } else {
            $querySelected .= " AND department BETWEEN ? AND ?";
            $argsSel[] = $deptStart;
            $argsSel[] = $deptEnd;
        }
        $querySelected .= " GROUP BY YEAR(tdate), MONTH(tdate), DAY(tdate)";

        $dataset = array();

        $prep = $dbc->prepare_statement($queryAll);
        $result = $dbc->exec_statement($prep,$argsAll);
        while($row = $dbc->fetch_row($result)) {
            $datestr = sprintf("%d/%d/%d",$row['month'],$row['day'],$row['year']);
            $dataset[$datestr] = array('ttl'=>$row['trans_count'],'sub'=>0);
        }

        $prep = $dbc->prepare_statement($querySelected);
        $result = $dbc->exec_statement($prep,$argsSel);
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
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $depts = new DepartmentsModel($dbc);
        $d_list = $depts->find('dept_no');
        $supers = new SuperDeptNamesModel($dbc);
        $supers->superID(0, '>');
        $s_list = $supers->find('superID');

        $form = FormLib::dateAndDepartmentForm($d_list, $s_list);

        return $form;
    }
}

FannieDispatch::conditionalExec();

?>
