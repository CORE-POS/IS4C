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

class GeneralSalesReport extends FannieReportPage 
{

    public $description = '[General Sales Report] shows total sales per department for a given date range in dollars as well as a percentage of store-wide sales.';
    public $report_set = 'Sales Reports';

    private $grandTTL = 1;
    protected $title = "Fannie : General Sales Report";
    protected $header = "General Sales Report";
    protected $report_cache = 'none';
    protected $multi_report_mode = false;
    protected $sortable = false;
    protected $no_sort_but_style = true;
    protected $chart_data_columns = array(1);
    protected $report_headers = array('','Sales','Quantity','% Sales','Dept %');
    protected $required_fields = array('date1', 'date2');

    public function preprocess()
    {
        parent::preprocess();
        if ($this->content_function == 'report_content' && $this->report_format == 'html') {
            $this->add_script('../../src/javascript/d3.js/d3.v3.min.js');
            $this->add_script('../../src/javascript/d3.js/charts/pie/pie.js');
            $this->add_onload_command('drawPieChart();');
        }

        return true;
    }

    private function thenQuery($dbc, $dlog, $store)
    {
        $superR = $dbc->query('SELECT dept_ID FROM MasterSuperDepts WHERE superID=0');
        $omitDepts = array();
        while ($superW = $dbc->fetch_row($superR)) {
            $omitDepts[] = $superW['dept_ID'];
        }
        list($omitVals, $omitDepts) = $dbc->safeInClause($omitDepts);

        $sales = "SELECT d.dept_name,
                    sum(t.total) AS ttl,
                    " . DTrans::sumQuantity('t') . " AS qty,
                    s.superID,
                    s.super_name
                FROM $dlog AS t 
                    LEFT JOIN departments AS d ON d.dept_no=t.department 
                    LEFT JOIN MasterSuperDepts AS s ON t.department=s.dept_ID
                WHERE 
                    t.department NOT IN ($omitVals)
                    AND t.trans_type IN ('I', 'D')
                    AND (tdate BETWEEN ? AND ?)
                    AND " . DTrans::isStoreID($store, 't') . "
                GROUP BY s.superID,s.super_name,d.dept_name,t.department
                ORDER BY s.superID,t.department";
        
        return array($sales, $omitDepts);
    }

    private function nowQuery($dbc, $dlog, $store)
    {
        $sales = "SELECT 
            CASE WHEN e.dept_name IS NULL THEN d.dept_name ELSE e.dept_name end AS dept_name,
            sum(t.total) AS ttl,
            " . DTrans::sumQuantity('t') . " AS qty,
            CASE WHEN s.superID IS NULL THEN r.superID ELSE s.superID end AS superID,
            CASE WHEN s.super_name IS NULL THEN r.super_name ELSE s.super_name END AS super_name
            FROM $dlog AS t 
                " . DTrans::joinProducts() . " AND p.upc <> '0000000000000'
                LEFT JOIN departments AS d ON d.dept_no=t.department 
                LEFT JOIN departments AS e ON p.department=e.dept_no 
                LEFT JOIN MasterSuperDepts AS s ON s.dept_ID=p.department 
                LEFT JOIN MasterSuperDepts AS r ON r.dept_ID=t.department
            WHERE
                t.trans_type IN ('I', 'D')
                AND (s.superID > 0 OR (s.superID IS NULL AND r.superID > 0)
                OR (s.superID IS NULL AND r.superID IS NULL))
                AND (tdate BETWEEN ? AND ?)
                AND " . DTrans::isStoreID($store, 't') . "
            GROUP BY
            CASE WHEN s.superID IS NULL THEN r.superID ELSE s.superID end,
            CASE WHEN s.super_name IS NULL THEN r.super_name ELSE s.super_name END,
            CASE WHEN e.dept_name IS NULL THEN d.dept_name ELSE e.dept_name end,
            CASE WHEN e.dept_no IS NULL THEN d.dept_no ELSE e.dept_no end
            ORDER BY
            CASE WHEN s.superID IS NULL THEN r.superID ELSE s.superID end,
            CASE WHEN e.dept_no IS NULL THEN d.dept_no ELSE e.dept_no end";
        $omitDepts = array();

        return array($sales, $omitDepts);
    }

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $date1 = $this->form->date1;
        $date2 = $this->form->date2;
        $dept = FormLib::get('dept', 0);
        $store = FormLib::get('store');

        $dlog = DTransactionsModel::selectDlog($date1,$date2);

        list($sales, $omitDepts) = $this->thenQuery($dbc, $dlog, $store);
        if ($dept == 1){
            list($sales, $omitDepts) = $this->nowQuery($dbc, $dlog, $store);
        }
        $supers = array();
        $prep = $dbc->prepare($sales);
        $args = $omitDepts;
        $args[] = $date1 . ' 00:00:00';
        $args[] = $date2 . ' 23:59:59';
        $args[] = $store;
        $salesR = $dbc->execute($prep, $args);
    
        $curSuper = 0;
        $grandTotal = 0;
        while ($row = $dbc->fetchRow($salesR)){
            $curSuper = $row['superID'];
            if (!isset($supers[$curSuper])) {
                $supers[$curSuper] = array('sales'=>0.0,'qty'=>0.0,'name'=>$row['super_name'],'depts'=>array());
            }
            $supers[$curSuper]['sales'] += $row['ttl'];
            $supers[$curSuper]['qty'] += $row['qty'];
            $supers[$curSuper]['depts'][] = array('name'=>$row['dept_name'],'sales'=>$row['ttl'],'qty'=>$row['qty']);
            $grandTotal += $row[1];
        }

        $this->grandTTL = $grandTotal;

        return $this->toReportData($supers, $grandTotal);
    }

    private function toReportData($supers, $grandTotal)
    {
        $data = array();
        $num = 1;
        foreach ($supers as $s) {
            if ($s['sales']==0) {
                $num++;
                continue;
            }

            $superSum = $s['sales'];
            foreach($s['depts'] as $d) {
                $record = array(
                    $d['name'],
                    sprintf('%.2f',$d['sales']),
                    sprintf('%.2f',$d['qty']),
                    sprintf('%.2f',($d['sales'] / $grandTotal) * 100),
                    sprintf('%.2f',($d['sales'] / $superSum) * 100)
                );
                $data[] = $record;
            }

            $record = array(
                $s['name'],
                sprintf('%.2f', $s['sales']),
                sprintf('%.2f', $s['qty']),
                '',
                sprintf('%.2f%%', ($s['sales'] / $grandTotal) * 100),
            );
            $record['meta'] = FannieReportPage::META_BOLD | FannieReportPage::META_CHART_DATA;

            $data[] = $record;

            $data[] = array('meta'=>FannieReportPage::META_BLANK);

            if ($num < count($supers)) {
                $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS);
            }
            $num++;
        }

        return $data;
    }

    public function calculate_footers($data)
    {
        $sumQty = 0.0;
        $sumSales = 0.0;
        foreach($data as $row) {
            if (isset($row['meta'])) {
                continue;
            }
            $sumQty += $row[2];
            $sumSales += $row[1];
        }
        return array('Total',number_format($sumSales,2),number_format($sumQty,2), '', null);
    }

    public function form_content()
    {
        list($lastMonday, $lastSunday) = \COREPOS\Fannie\API\lib\Dates::lastWeek();
        $store = FormLib::storePicker();
        ob_start();
        ?>
        <form action=GeneralSalesReport.php method=get class="form-horizontal">
        <div class="row">
            <div class="col-sm-6">
                <p>
                    <label>Start Date</label>
                    <input class="form-control date-field" type=text id=date1 name=date1 value="<?php echo $lastMonday; ?>" />
                </p>
                <p>
                    <label>End Date</label>
                    <input class="form-control date-field" type=text id=date2 name=date2 value="<?php echo $lastSunday; ?>" />
                </p>
                <p>
                    <label>Departments</label>
                    <select name=dept class="form-control">
                        <option value=0>Use department settings at time of sale</option>
                        <option value=1>Use current department settings</option>
                    </select>
                </p>
                <p>
                    <label>Store(s)</label>
                    <?php echo $store['html']; ?>
                </p>
            </div>
            <div class="col-sm-6">
                <p>
                <?php echo FormLib::date_range_picker(); ?>
                </p>
            </div>
        </div>
        <p>
            <button type=submit name=submit value="Submit" class="btn btn-default">Submit</button>
            <label><input type=checkbox name=excel /> Excel</label>
        </p>
        </form>
        <?php

        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>General Sales is an overview with totals for each POS
            deaprtment and subtotals for each super department. In this
            context, individual departments are only counted once under
            their home super department so that the grand total accurately
            reflects total sales.</p>
            <p>The <em>Use department settings...</em> option may change
            line item totals but should not alter the grand total. If an
            item used to be in department #1 but now is in department #2,
            this option controls where its sales appear in the report.</p>';
    }

    public function unitTest($phpunit)
    {
        $supers = array(
            0=>array('sales'=>0),
            1=>array('sales'=>1, 'qty'=>1, 'name'=>'foo', 'depts'=>array(array('name'=>'bar', 'sales'=>1, 'qty'=>1))),
        );
        $data = $this->toReportData($supers, 1);
        $phpunit->assertInternalType('array', $data);
        $phpunit->assertInternalType('array', $this->calculate_footers($data));
    }

}

FannieDispatch::conditionalExec();

