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
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class ZipCodeReport extends FannieReportPage 
{
    public $description = '[Zip Code Report] lists number of customers and sales total by postal code
        for a given date range.';
    public $report_set = 'Membership';
    public $themed = true;

    protected $title = "Fannie : Zip Code Report";
    protected $header = "Zip Code Report";
    protected $required_fields = array('date1', 'date2');

    function fetch_report_data()
    {
        $date1 = $this->form->date1;
        $date2 = $this->form->date2;
        $exclude = FormLib::get_form_value('excludes','');

        $ex = preg_split('/\D+/',$exclude, 0, PREG_SPLIT_NO_EMPTY);
        $exCondition = '';
        $exArgs = array();
        foreach($ex as $num){
            $exCondition .= '?,';
            $exArgs[] = $num;
        }
        $exCondition = substr($exCondition, 0, strlen($exCondition)-1);

        $ret = array();
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $query = "
            SELECT 
                CASE WHEN m.zip='' THEN 'none' ELSE m.zip END as zipcode,
                COUNT(*) as num 
            FROM meminfo AS m 
                INNER JOIN memDates AS d ON m.card_no=d.card_no 
            WHERE ";
        if (!empty($exArgs)) {
            $query .= "m.card_no NOT IN ($exCondition) AND ";
        }
        $query .= "d.start_date >= ?
            GROUP BY zipcode
            ORDER BY COUNT(*) DESC";
        $exArgs[] = $date1.' 00:00:00';
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, $exArgs);
        while ($row = $dbc->fetch_row($result)) {
            $record = array($row['zipcode'], $row['num']);
            $ret[] = $record;
        }

        return $ret;
    }

    function calculate_footers($data)
    {
        switch(count($data[0])){
        case 2:
            $this->report_headers = array('Zip Code', '# of Customers');    
            $this->sort_column = 1;
            $this->sort_direction = 1;
            $sum = 1;
            foreach($data as $row) $sum += $row[1];
            return array('Total', $sum);
        case 4:
        default:
            $this->report_headers = array('Zip Code', '# Transactions', '# of Customers', 'Total $');
            $this->sort_column = 3;
            $this->sort_direction = 1;
            $sumQty = 0.0;
            $sumSales = 0.0;
            $sumUnique = 0.0;
            foreach($data as $row){
                $sumQty += $row[1];
                $sumUnique += $row[2];
                $sumSales += $row[3];
            }
            return array('Total',$sumQty, $sumUnique, $sumSales);
        }
    }

    function form_content()
    {
        $ret = '';
        $ret .= '<form action="ZipCodeReport.php" method="get">
            <div class="col-sm-5">
            <div class="form-group">
                <label>Start Date</label>
                <input type="text" name="date1" id="date1" 
                    class="form-control date-field" required/>
            </div>
            <div class="form-group">
                <label>End Date</label>
                <input type="text" name="date2" id="date2" 
                    class="form-control date-field" required/>
            </div>
            <div class="form-group">
                <label>Exclude #(s)</label>
                <input type="text" name="excludes" class="form-control" />
            </div>
            <p>
               <button type="submit" class="btn btn-default">Get Report</button>
            </p>
            </div>
            <div class="col-sm-5">
                ' . FormLib::dateRangePicker() . '
            </div>
            </form>';   

        return $ret;
    }

    public function helpContent()
    {
        return '<p>
            Lists information about members by their zip code.
            The default Join Date option will only show the number
            of members from each zip code who joined the co-op in
            the given date range.
            </p>
            <p>
            If the CoreWarehouse plugin is available, the report
            can also show total purchase information per zip code
            for all members who shopped in the given date range.
            </p>';
    }

}

FannieDispatch::conditionalExec();

