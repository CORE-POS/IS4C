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

class WicTenderReport extends FannieReportPage 
{
    public $description = '[WIC Tender Report] tracks WIC items sold by tender';
    public $report_set = 'Reports';
    public $themed = true;

    protected $report_headers = array('UPC', 'Description', 'Quantity purchased with WIC Tender', 'Quantity purchased with Non-Wic Tender');
    protected $sort_direction = 1;
    protected $title = "Fannie : WIC Tender Report";
    protected $header = "WIC Tender Report";
    protected $required_fields = array('date1');
    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('TRANS_DB'));
        $query = "SELECT d.upc,
            CASE WHEN d.description='credit card' 
                or d.description='cash' 
                or d.description='check' 
                or d.description='store credit' 
                or d.description='gift card' 
                    THEN 'other'
            WHEN d.description='wic' 
                THEN 'WIC'
                END as Tender
            from dlog_90_view as d 
                left join is4c_op.products as p on p.upc=d.upc 
            where (
                p.wicable=1 
                or d.description='credit card' 
                or d.description='cash' 
                or d.description='check' 
                or d.description='wic' 
                or d.description='store credit' 
                or d.description='gift card'
                ) 
                and d.tdate>= '" . $_GET['date1'] . 
                "' and d.tdate<='" . $_GET['date2'] . "';";
            $result = $dbc->query($query);
            while ($row = $dbc->fetch_row($result)) {
                $tmpTender[] = $row['Tender'];
            }
            for ($i=count($tmpTender); $i>=0; --$i) {
                if ($tmpTender[$i] != NULL) {
                    $tender = $tmpTender[$i];
                } else {
                    $tmpTender[$i] = $tender;
                }
            }
            $query = "
            select d.trans_no, 
                d.upc, 
                d.description, 
                d.quantity
            from dlog_90_view as d 
                left join is4c_op.products as p on p.upc=d.upc 
            where (
                p.wicable=1 
                or d.description='credit card' 
                or d.description='cash' 
                or d.description='check'
                or d.description='wic' 
                or d.description='store credit' 
                or d.description='gift card'
                ) 
                and d.tdate>='2015-11-09 00:00:00'
                and (p.department>200 and p.department>220)
                    or p.department<500
                and d.tdate>= '" . $_GET['date1'] . 
                "' and d.tdate<='" . $_GET['date2'] . "';";
            $result = $dbc->query($query);
            while ($row = $dbc->fetch_row($result)) {
               if ( array_search($row['upc'], $item[0]) != NULL) {
                    if ( $tmpTender[$count] == 'WIC' ) {
                        $wicTend[ array_search($row['upc'], $item[0]) ] += $row['quantity'];
                    } else {
                        $otherTend[ array_search($row['upc'], $item[0]) ] += $row['quantity'];
                    }        
                } else {
                    $item[0][] = $row['upc'];
                    $item[1][] = $row['description'];
                    $item[2][] = sprintf("%01.2f", $row['quantity']);
                }
                $count++;
            }
            
        return $item;
    }

    public function form_content()
    {
        return '<form method="get" action="WicTenderReport.php" id="form1">
            <div class="form-group">
            <label>Start Date</label>
            <input type="text" name="date1" class="form-control date-field" required/>
            </div>
            <div class="form-group">
            <label>End Date</label>
            <input type="text" name="date2" class="form-control date-field" required/>
            </div>
            <p>
            <button type="submit" class="btn btn-default">Generate Report</button>
            </p>
            </form>';
    }

    public function helpContent()
    {
        return '<p>Show number of transactions that are made
        in a chosen time period containing WIC items, and 
        report what itemes are being purchased with WIC tender
        or non-WIC tender. <em>Sum report by</em>
            gives different report formats.
            <ul>
                <li><em>UPC</em> shows a row for each item. Sales totals
                are for the entire date range.</li>
                <li><em>Date</em> show a row for each days. Sales totals
                are all sales in the brand that day.</li>
                <li><em>Department</em> shows a row for each POS department.
                Sales totals are all sales in that particular department
                for the entire date range.</li>
            </ul>';
    }
}

FannieDispatch::conditionalExec();

?>
