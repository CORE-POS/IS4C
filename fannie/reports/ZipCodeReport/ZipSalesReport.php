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
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class ZipSalesReport extends FannieReportPage 
{
    public $description = '[Zip Code Sales] lists customer counts and sales by zip code for a given period';
    public $report_set = 'Membership';
    public $themed = true;

    protected $title = "Fannie : Zip Code Report";
    protected $header = "Zip Code Report";
    protected $required_fields = array('date1', 'date2');

    function fetch_report_data()
    {
        $date1 = $this->form->date1;
        $date2 = $this->form->date2;
        $dlog = DTransactionsModel::selectDLog($date1, $date2);

        $ret = array();
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $query = "
            SELECT 
                CASE WHEN m.zip='' OR m.zip IS NULL THEN 'none' ELSE LEFT(m.zip, 5) END as zipcode,
                COUNT(DISTINCT d.card_no) AS customers,
                SUM(total) AS ttl
            FROM {$dlog} AS d
                INNER JOIN meminfo AS m ON m.card_no=d.card_no 
                INNER JOIN MasterSuperDepts AS s ON d.department=s.dept_ID
            WHERE d.tdate BETWEEN ? AND ?
                AND s.superID <> 0
                AND d.trans_type IN ('I', 'D')
            GROUP BY CASE WHEN m.zip='' OR m.zip IS NULL THEN 'none' ELSE LEFT(m.zip, 5) END";
        $prep = $dbc->prepare($query);
        try {
            $result = $dbc->execute($prep, array($date1 . ' 00:00:00', $date2 . ' 23:59:59'));
        } catch (Exception $ex) {
            // MySQL 5.6 GROUP BY
            return array();
        }
        $ttl = array(0, 0);
        while ($row = $dbc->fetchRow($result)) {
            $ret[] = array(
                $row['zipcode'],
                sprintf("%d", $row['customers']),
                0,
                sprintf("%.2f", $row['ttl']),
                0,
            );
            $ttl[0] += $row['customers'];
            $ttl[1] += $row['ttl'];
        }
        for ($i=0; $i<count($ret); $i++) {
            $ret[$i][2] = sprintf('%.2f', ($ret[$i][1] / $ttl[0]) * 100);
            $ret[$i][4] = sprintf('%.2f', ($ret[$i][3] / $ttl[1]) * 100);
        }

        return $ret;
    }

    function calculate_footers($data)
    {
        $this->report_headers = array('Zip Code', '# of Customers', '%', 'Total $', '%');
        $this->sort_column = 3;
        $this->sort_direction = 1;
            $sumQty = 0.0;
            $sumSales = 0.0;
            $sumUnique = 0.0;
        foreach($data as $row){
            $sumUnique += $row[1];
            $sumSales += $row[3];
        }
        return array('Total',$sumUnique, '', $sumSales);
    }

    function form_content()
    {
        $ret = '';
        $ret .= '<form action="ZipSalesReport.php" method="get">
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

