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
    public $report_set = 'WIC';
    public $themed = true;

    protected $report_headers = array('UPC', 'Description', 'Quantity purchased with WIC Tender', 'Quantity purchased with Non-Wic Tender');
    protected $sort_direction = 1;
    protected $title = "Fannie : WIC Tender Report";
    protected $header = "WIC Tender Report";
    protected $required_fields = array('date1', 'date2');

    function report_description_content()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('TRANS_DB'));
        try {
            $date1 = $this->form->date1;
            $date2 = $this->form->date2;
        } catch (Exception $ex) {
            return array();
        }
        
        /* Count WIC transactions */
        $query = "
            SELECT count(description) as count 
            FROM dlog_90_view
            WHERE description='WIC'
            AND tdate>= '" . $date1 . 
                " 00:00:00' and tdate<='" . $date2 . " 23:59:59';
            ";
        $result = $dbc->query($query);
        $row = $dbc->fetch_row($result);

        return array($row['count'] . " <strong>WIC transactions</strong> occured during this period.");
    }

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('TRANS_DB'));
        try {
            $date1 = $this->form->date1;
            $date2 = $this->form->date2;
        } catch (Exception $ex) {
            return array();
        }
        
        /**
          Find every transaction with wicable items
          and check whether or not WIC was used as a tender
          in that transaction
        */
        $query = '
            SELECT YEAR(tdate) AS year,
                MONTH(tdate) AS month,
                DAY(tdate) AS day,
                trans_num,
                SUM(CASE WHEN d.description=\'WIC\' THEN 1 ELSE 0 END) as usedWic
            FROM dlog_90_view AS d
                LEFT JOIN ' . $this->config->get('OP_DB') . $dbc->sep() . 'products AS p ON p.upc=d.upc AND p.store_id=d.store_id
            WHERE d.tdate BETWEEN \'' . $date1 . ' 00:00:00\'
                AND \'' . $date2 . ' 23:59:59\'
            GROUP BY YEAR(tdate),
                MONTH(tdate),
                DAY(tdate),
                trans_num
            HAVING SUM(CASE WHEN p.wicable=1 THEN 1 ELSE 0 END) <> 0';
        $result = $dbc->query($query);
        $wicTrans = array();
        /**
          Used date + trans_num to build a unique transaction identifier
          and store whether the transaction included WIC tender
        */
        while ($row = $dbc->fetchRow($result)) {
            $key = mktime(0, 0, 0, $row['month'], $row['day'], $row['year'])
                . '-' . $row['trans_num'];
            $wicTrans[$key] = $row['usedWic'];
        }
        /* Find Tender Type for each transaction */
        /*
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
                " 00:00:00' and d.tdate<='" . $_GET['date2'] . " 23:59:59';";
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
            */
            
            /* Find sum of sales per item purchased with WIC and Non-WIC */
            $query = "
            select YEAR(tdate) AS year,
                MONTH(tdate) AS month,
                DAY(tdate) AS day,
                trans_num,
                d.upc, 
                d.description, 
                d.quantity
            from dlog_90_view as d 
                INNER JOIN " . $this->config->get('OP_DB') . $dbc->sep() . "products AS p ON p.upc=d.upc AND p.store_id=d.store_id
            where 
                p.wicable=1 
                and ((p.department<200 and p.department>220)
                    or p.department<500)
                and d.tdate>= '" . $date1 . 
                " 00:00:00' and d.tdate<='" . $date2 . " 23:59:59';
                ";
            $result = $dbc->query($query);
            $count = 0;
            $items = array();
            /**
              Accumulate sales by UPC
              Use the same transaction identifier to check
              whether the transaction included a WIC tender
            */
            while ($row = $dbc->fetch_row($result)) {
                if (!isset($items[$row['upc']])) {
                    $items[$row['upc']] = array(
                        $row['upc'],
                        $row['description'],
                        0,
                        0,
                    );
                }
                $key = mktime(0, 0, 0, $row['month'], $row['day'], $row['year'])
                    . '-' . $row['trans_num'];
                if (isset($wicTrans[$key]) && $wicTrans[$key] == 1) {
                    $items[$row['upc']][2] += $row['quantity'];
                } else {
                    $items[$row['upc']][3] += $row['quantity'];
                }
            }
            
            /**
              Rebuild the array w/ numeric keys instead
              of UPCs as keys
            */
            $data = array();
            foreach ($items as $upc => $info) {
                $data[] = $info;
            }

            return $data;
    }

    public function calculate_footers($data)
    {
        $sums = array(0, 0);
        foreach ($data as $row) {
            $sums[0] += $row[2];
            $sums[1] += $row[3];
        }

        return array('Total', null, $sums[0], $sums[1]);
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

