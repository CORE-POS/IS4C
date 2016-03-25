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

class DDDReport extends FannieReportPage 
{
    public $description = '[Shrink Reports] lists items marked as DDD/shrink at the registers.';
    public $themed = true;

    protected $title = "Fannie : DDD Report";
    protected $header = "DDD Report";
    protected $report_headers = array('Date','UPC','Item','Dept#','Dept Name','Account#', 'Super Dept', 'Qty','$','Reason', 'Loss');
    protected $required_fields = array('submitted');

    protected $sort_direction = 1;

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $FANNIE_TRANS_DB = $this->config->get('TRANS_DB');

        $dtrans = $FANNIE_TRANS_DB . $dbc->sep() . 'transarchive';
        $union = true;
        $args = array();
        try {
            $date1 = $this->form->date1;
            $date2 = $this->form->date2;
            $dtrans = DTransactionsModel::selectDTrans($date1, $date2);
            $union = false;
            $args[] = $date1 . ' 00:00:00';
            $args[] = $date2 . ' 23:59:59';
        } catch (Exception $ex) {
            $date1 = '';
            $date2 = '';
        }
        $store = FormLib::get('store', 0);
        $args[] = $store;

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
                    s.description AS shrinkReason,
                    m.super_name,
                    e.salesCode,
                    d.charflag
                  FROM {{table}} AS d
                    LEFT JOIN departments AS e ON d.department=e.dept_no
                    LEFT JOIN ShrinkReasons AS s ON d.numflag=s.shrinkReasonID
                    LEFT JOIN MasterSuperDepts AS m ON d.department=m.dept_ID
                  WHERE trans_status = 'Z'
                    AND trans_type IN ('D', 'I')
                    AND emp_no <> 9999
                    AND register_no <> 99
                    AND upc <> '0'
                    {{date_clause}}
                    AND " . DTrans::isStoreID($store, 'd') . "
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
            // prepend store argument as both queries will have a store
            // clause requiring the parameter
            array_unshift($args, $store);
        }

        $data = array();
        $prep = $dbc->prepare($fullQuery);
        $result = $dbc->execute($prep, $args);
        while ($row = $dbc->fetchRow($result)) {
            $data[] = $this->rowToRecord($row);
        }

        return $data;
    }

    private function rowToRecord($row)
    {
        return array(
            date('Y-m-d', mktime(0, 0, 0, $row['month'], $row['day'], $row['year'])),
            $row['upc'],
            $row['description'],
            $row['department'],
            $row['dept_name'],
            $row['salesCode'],
            $row['super_name'],
            sprintf('%.2f', $row['quantity']),
            sprintf('%.2f', $row['total']),
            empty($row['shrinkReason']) ? 'n/a' : $row['shrinkReason'],
            $row['charflag'] == 'C' ? 'No' : 'Yes',
        );
    }
    
    public function form_content()
    {
        $store = FormLib::storePicker();
        return '
        <form action="' . $_SERVER['PHP_SELF'] . '" method="get">
<div class="well">Dates are optional; omit for last quarter</div>
<div class="col-sm-4">
    <div class="form-group">
    <label>Date Start</label>
    <input type=text id=date1 name=date1 class="form-control date-field" />
    </div>
    <div class="form-group">
    <label>Date End</label>
    <input type=text id=date2 name=date2 class="form-control date-field" />
    </div>
    <div class="form-group">
    <label>Store</label>
    ' . $store['html'] . '
    </div>
    <p>
    <button type=submit name=submitted value=1 class="btn btn-default btn-core">Submit</button>
    <button type=reset name=reset class="btn btn-default btn-reset">Start Over</button>
    </p>
</div>
<div class="col-sm-4">'
    . FormLib::date_range_picker() . '
</div>
</form>
        ';
    }

    public function helpContent()
    {
        return '<p>
            List items marked as shrink for a given date range. In this
            context, shrink is tracking losses.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $data = array('month'=>1, 'day'=>1, 'year'=>2000, 'upc'=>'4011',
            'description'=>'test', 'department'=>1, 'dept_name'=>'test',
            'salesCode'=>100, 'super_name'=>'test', 'quantity'=>1,
            'total'=>1, 'shrinkReason'=>'test', 'charflag'=>'C');
        $phpunit->assertInternalType('array', $this->rowToRecord($data));
    }
}

FannieDispatch::conditionalExec();

