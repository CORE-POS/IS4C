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

class DDDReport extends FannieReportPage 
{
    public $description = '[Shrink Reports] lists items marked as DDD/shrink at the registers.';

    protected $title = "Fannie : DDD Report";
    protected $header = "DDD Report";
    protected $report_headers = array('Date','UPC','Item','Dept#','Dept Name','Qty','$','Reason');
    protected $required_fields = array('submitted');

    protected $sort_direction = 1;

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $date1 = FormLib::get_form_value('date1');
        $date2 = FormLib::get_form_value('date2');

        $dtrans = $FANNIE_TRANS_DB . $dbc->sep() . 'transarchive';
        $union = true;
        $args = array();
        if ($date1 !== '' && $date2 !== '') {
            $dtrans = DTransactionsModel::selectDTrans($date1, $date2);
            $union = false;
            $args[] = $date1 . ' 00:00:00';
            $args[] = $date2 . ' 23:59:59';
        }

        /**
          I'm using {{placeholders}}
          to build the basic query, then replacing those
          pieces depending on date range options
        */
        $query = "SELECT
                    YEAR(datetime) AS year,
                    MONTH(datetime) AS month,
                    DAY(datetime) AS day,
                    d.upc,
                    d.description,
                    d.department,
                    e.dept_name,
                    SUM(d.quantity) AS quantity,
                    SUM(d.total) AS total,
                    s.description AS shrinkReason
                  FROM {{table}} AS d
                    LEFT JOIN departments AS e ON d.department=e.dept_no
                    LEFT JOIN ShrinkReasons AS s ON d.numflag=s.shrinkReasonID
                  WHERE trans_status = 'Z'
                    AND trans_type IN ('D', 'I')
                    AND trans_subtype IN ('','0')
                    AND emp_no <> 9999
                    AND register_no <> 99
                    AND upc <> '0'
                    {{date_clause}}
                  GROUP BY
                    YEAR(datetime),
                    MONTH(datetime),
                    DAY(datetime),
                    d.upc,
                    d.description,
                    d.department,
                    e.dept_name,
                    s.description";
        
        $fullQuery = '';
        if (!$union) {
            // user selected date range
            $fullQuery = str_replace('{{table}}', $dtrans, $query);
            $fullQuery = str_replace('{{date_clause}}', 'AND datetime BETWEEN ? AND ?', $fullQuery);
        } else {
            // union of today (dtransaction)
            // plus last quarter (transarchive)
            $today_table = $FANNIE_TRANS_DB . $dbc->sep() . 'dtransactions';
            $today_clause = ' AND ' . $dbc->datediff($dbc->now(), 'datetime') . ' = 0';
            $query1 = str_replace('{{table}}', $today_table, $query);
            $query1 = str_replace('{{date_clause}}', $today_clause, $query1);
            $query2 = str_replace('{{table}}', $dtrans, $query);
            $query2 = str_replace('{{date_clause}}', '', $query2);
            $fullQuery = $query1 . ' UNION ALL ' . $query2;
        }

        $data = array();
        $prep = $dbc->prepare($fullQuery);
        $result = $dbc->execute($prep, $args);
        while($row = $dbc->fetch_row($result)) {
            $record = array(
                    date('Y-m-d', mktime(0, 0, 0, $row['month'], $row['day'], $row['year'])),
                    $row['upc'],
                    $row['description'],
                    $row['department'],
                    $row['dept_name'],
                    sprintf('%.2f', $row['quantity']),
                    sprintf('%.2f', $row['total']),
                    empty($row['shrinkReason']) ? 'n/a' : $row['shrinkReason'],
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
            <form method="get" action="DDDReport.php">
            <table>
            <tr>
                <td colspan="3"><i>Dates are optional; omit for last quarter (including today)</i></td>
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
                <td><input type="submit" name="submitted" value="Get Report" /></td>
            </tr>
            </table>
            </form>';
    }
}

FannieDispatch::conditionalExec();

