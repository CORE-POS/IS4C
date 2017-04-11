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

class OwnerDonationReport extends FannieReportPage 
{
    public $description = '[Owner Donation Report] shows all owner donations for a given year';
    public $report_set = 'Membership';

    protected $report_headers = array('Month', 'Month', 'Total');
    protected $title = "Fannie : Owner Donation Report";
    protected $header = "Owner Donation Report";
    protected $required_fields = array('card_no', 'year');

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        try {
            $year = $this->form->year;
            $card_no = $this->form->card_no;
        } catch (Exception $ex) {
            return array();
        }

        $date1 = $year . '-01-01';
        $date2 = $year . '-12-31';

        $dlog = DTransactionsModel::selectDlog($date1, $date2);

        $query = $dbc->prepare("
            SELECT MONTH(tdate),
                SUM(total) AS ttl
            FROM {$dlog} AS d
            WHERE department=701
                AND tdate BETWEEN ? AND ?
                AND card_no=?
            GROUP BY MONTH(tdate)
        ");
        $res = $dbc->execute($query, array($date1 . ' 00:00:00', $date2 . ' 23:59:59', $card_no));
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $data[] = array(
                $row[0],
                date('F Y', mktime(0,0,0,$row[0],1,$year)),
                sprintf('%.2f', $row['ttl']),
            );
        }

        return $data;
    }

    public function calculate_footers($data)
    {
        $sum = 0;
        foreach ($data as $row) {
            $sum += $row[2];
        }

        return array('Total', '', number_format($sum, 2)); 
    }

    public function form_content()
    {
        $year = date('Y');
        return <<<HTML
<form method ="get" action="OwnerDonationReport.php">
    <div class="form-group"> <label>Owner #</label>
        <input type=text name=card_no class="form-control" />
    </div>
    <div class="form-group">
        <label>Year</label>
        <input type=text name=year value="{$year}" class="form-control" />
    </div>
    <p>
        <button type=submit name=submit class="btn btn-default btn-core">Submit</button>
    </p>
</form>
HTML;
    }

    public function helpContent()
    {
        return '<p>
            This report lists round-up donations for a given date
            range, subdivided by cashier.
            </p>';
    }
}

FannieDispatch::conditionalExec();

