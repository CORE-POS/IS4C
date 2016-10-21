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

class HourlyCustomersReport extends FannieReportPage 
{
    public $description = '[Hourly Customers] lists number of customers per hour for a given day.';
    public $themed = true;
    public $report_set = 'Transaction Reports';

    protected $header = "Customers per Hour";
    protected $title = "Fannie : Customers per Hour";

    protected $content_function = 'both_content';
    protected $report_headers = array('Hour', 'Transactions');
    protected $required_fields = array('date');

    public function form_content()
    {
        ob_start();
        ?>
<form method=get action="<?php echo $_SERVER["PHP_SELF"]; ?>" >
<div class="well">Get transactions per hour for what date (YYYY-MM-DD)?</div>
<input type=text name=date id="date" required
    class="form-control date-field" placeholder="Date" />
<p>
<button type=submit class="btn btn-default">Generate</button>
</p>
</form>
        <?php

        return ob_get_clean();
    }

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $date = $this->form->date;
        $dlog = DTransactionsModel::selectDlog($date);

        $hour = $dbc->hour('tdate');
        $query = $dbc->prepare("select $hour as hour,
            count(distinct trans_num)
            from $dlog where
            tdate BETWEEN ? AND ?
            group by $hour
            order by $hour");
        $res = $dbc->execute($query,array($date.' 00:00:00',$date.' 23:59:59'));

        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $data[] = $this->rowToRecord($row);
        }

        return $data;
    }

    private function rowToRecord($row)
    {
        $hour = $row[0];
        if ($hour > 12) {
            $hour -= 12;
        }
        $record = array();
        $record[] = $hour . ($row[0] < 12 ? ':00 am' : ':00 pm');
        $record[] = $row[1];

        return $record;
    }

    public function helpContent()
    {
        return '<p>This report shows hourly transactions over a range of dates.
            The rows are always hours. The columns are either calendar
            dates or named weekdays (e.g., Monday, Tuesday) if grouping
            by week day.</p>
            <p>If a <em>Buyer/Dept</em> option is used, the result will
            be transactions from that super department. Otherwise, the result
            will be transactions from the specified department range. Note there
            are a couple special options in the <em>Buyer/Dept</em> list:
            <em>All</em> is simply all sales and <em>All Retail</em> is
            everything except for super department #0 (zero).</p>';
    }

    public function unitTest($phpunit)
    {
        $data = array(13, 1);
        $phpunit->assertInternalType('array', $this->rowToRecord($data));
    }
}

FannieDispatch::conditionalExec();

