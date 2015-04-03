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

class CashierRecordsReport extends FannieReportPage 
{
    public $description = '[Cashier Records] shows per-cashier sales and transaction totals
        over a given date range. "Records" here should be interpretted like "Record Sales Day".';
    public $themed = true;

    protected $report_headers = array('Emp#', 'Date', '$', '# of Trans');
    protected $sort_column = 3;
    protected $sort_direction = 1;
    protected $report_cache = 'day';
    protected $title = "Fannie : Cashier Shift Records Report";
    protected $header = "Cashier Shift Records Report";
    protected $required_fields = array('date1', 'date2');

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $date1 = FormLib::get('date1', date('Y-m-d'));
        $date2 = FormLib::get('date2', date('Y-m-d'));

        $dlog = DTransactionsModel::selectDlog($date1, $date2);
        $q = $dbc->prepare_statement("select emp_no,sum(-total),count(DISTINCT trans_num),
                year(tdate),month(tdate),day(tdate)
                from $dlog as d where
                tdate BETWEEN ? AND ?
                AND trans_type='T'
                GROUP BY year(tdate),month(tdate),day(tdate),emp_no
                ORDER BY sum(-total) DESC");
        $r = $dbc->exec_statement($q,array($date1.' 00:00:00',$date2.' 23:59:59'));

        $data = array();
        while($row = $dbc->fetch_row($r)){
            $record = array($row['emp_no'],
                        sprintf('%d/%d/%d',$row[4],$row[5],$row[3]),
                        sprintf('%.2f',$row[1]),
                        $row[2]);
            $data[] = $record;
        }
        return $data;
    }

    public function form_content()
    {
        ob_start();
?>
<form method ="get" action="CashierRecordsReport.php">
<div class="col-sm-4">
    <div class="form-group">
    <label>Date Start</label>
    <input type=text id=date1 name=date1 class="form-control date-field" />
    </div>
    <div class="form-group">
    <label>Date End</label>
    <input type=text id=date2 name=date2 class="form-control date-field" />
    </div>
    <p>
    <button type=submit name=submit class="btn btn-default">Submit</button>
    <button type=reset name=reset class="btn btn-default">Start Over</button>
    </p>
</div>
<div class="col-sm-4">
    <?php echo FormLib::date_range_picker(); ?>
</div>
</form>
<?php

        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec();

?>
