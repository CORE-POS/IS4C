<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

class TenderInOutReport extends FannieReportPage
{
    public $description = '[Tender Usages] lists each transaction for a given tender in a given date range.';
    public $report_set = 'Tenders';

    protected $title = "Fannie : Tender Usage";
    protected $header = "Tender Usage Report";

    protected $report_headers = array('Date', 'Receipt#', 'Employee', 'Register', 'Amount');
    protected $required_fields = array('date1', 'date2');

    public function report_description_content()
    {
        $code = FormLib::get('tendercode');

        return array(
            'For tender '.$code,
        );
    }

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $date1 = $this->form->date1;
        $date2 = $this->form->date2;
        $code = FormLib::get('tendercode');

        $dlog = DTransactionsModel::selectDlog($date1,$date2);

        $query = $dbc->prepare("select tdate,trans_num,-total as total,emp_no, register_no
              FROM $dlog as t 
              where t.trans_subtype = ? AND
              trans_type='T' AND
              tdate BETWEEN ? AND ?
              AND total <> 0
              order by tdate");
        $result = $dbc->execute($query,array($code,$date1.' 00:00:00',$date2.' 23:59:59'));


        $data = array();
        while ($row = $dbc->fetchRow($result)) {
            $data[] = $this->rowToRecord($row);
        }

        return $data;
    }

    private function rowToRecord($row)
    {
        return array(
            date('Y-m-d', strtotime($row['tdate'])),
            $row['trans_num'],
            $row['emp_no'],
            $row['register_no'],
            $row['total'],
        );
    }

    public function calculate_footers($data)
    {
        $sum = 0.0;
        foreach($data as $row) {
            $sum += $row[4];
        }

        return array('Total', '', null, null, $sum);
    }

    public function form_content()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $tenders = array();
        $p = $dbc->prepare("SELECT TenderCode,TenderName FROM tenders ORDER BY TenderName");
        $r = $dbc->execute($p);
        while($w = $dbc->fetch_row($r)) {
            $tenders[$w['TenderCode']] = $w['TenderName'];
        }

        ob_start();
        ?>
<form method = "get" action="TenderInOutReport.php">
<div class="col-sm-4">
    <div class="form-group"> 
        <label>Tender</label>
        <select name="tendercode" class="form-control">
            <?php foreach($tenders as $code=>$name) {
                printf('<option value="%s">%s</option>',$code,$name);
            } ?>
        </select>
    </div>
    <div class="form-group"> 
        <label>Date Start</label>
        <input type=text id=date1 name=date1 required
            class="form-control date-field" />
    </div>
    <div class="form-group"> 
        <label>Date End</label>
        <input type=text id=date2 name=date2 required
            class="form-control date-field" />
    </div>
    <div class="form-group"> 
        <input type="checkbox" name="excel" id="excel" value="xls" />
        <label for="excel">Excel</label>
    </div>
    <div class="form-group"> 
        <button type=submit name=submit value="Submit"
            class="btn btn-default btn-core">Submit</button>
        <button type=reset name=reset value="Start Over"
            class="btn btn-default btn-reset">Start Over</button>
    </div>
</div>
<div class="col-sm-4">
    <?php echo FormLib::date_range_picker(); ?>
</div>
</form>
        <?php

        return ob_get_clean();
    }
    
    public function helpContent()
    {
        return '<p>
            Lists each individual use of a given tender
            during the selected date range.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $data = array('tdate'=>'2000-01-01', 'trans_num'=>'1-1-1', 'emp_no'=>1,
            'register_no'=>1, 'total'=>10);
        $phpunit->assertInternalType('array', $this->rowToRecord($data));
        $phpunit->assertInternalType('array', $this->calculate_footers($this->dekey_array(array($data))));
    }
}

FannieDispatch::conditionalExec();

