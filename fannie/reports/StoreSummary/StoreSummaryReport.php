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
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class StoreSummaryReport extends FannieReportPage {

    protected $required_fields = array('date1', 'date2');
    protected $show_zero;

    public $description = "[Store Summary Report] shows total sales, costs and taxes
       per department for a given date range in dollars as well as a percentage of 
       store-wide sales and costs. It uses actual item cost if known and estimates 
       cost from price and department margin if not; 
       relies on department margins being accurate.";

    public $report_set = 'Sales Reports';
    public $themed = true;
    protected $sortable = true;
    protected $no_sort_but_style = true;

    function preprocess()
    {
        parent::preprocess();
        $this->title = "Fannie : Store Summary Report";
        $this->header = "Store Summary Report";
        $this->report_cache = 'none';
        if (FormLib::get_form_value('sortable') !== '') {
            $this->no_sort_but_style = false;
        }
        if (FormLib::get_form_value('show_zero') !== '') {
            $this->show_zero = True;
        } else {
            $this->show_zero = False;
        }

        if (FormLib::get_form_value('date1') !== ''){
            $this->content_function = "report_content";

            /**
              Check if a non-html format has been requested
               from the links in the initial display, not the form.
            */
            if (FormLib::get_form_value('excel') !== '') {
                $this->report_format = FormLib::get_form_value('excel');
                $this->hasMenus(False);
            }
        }

        return True;

    // preprocess()
    }

    /**
      Define any CSS needed
      @return A CSS string
    */
    function css_content(){
        $css = ".explain {
            font-family: Arial;
            color: black;
    }
    ";
        $css .= "p.explain {
            font-family: Arial;
            font-size: 1.0em;
            color: black;
            margin: 0 0 0 0;
    }
    ";
        return $css;
    }

    function report_description_content(){
        $ret = array();
        if (FormLib::get_form_value('dept',0) == 0){
            $ret[] = "<p class='explain'>Using the department# the upc was assigned to at time of sale</p>";
        }
        else{
            $ret[] = "<p class='explain'>Using the department# the upc is assigned to now</p>";
        }
        $ret[] = "<p class='explain'>Note: For items where cost is not recorded the margin in the deptMargin table is relied on.</p>";
        return $ret;
    }

    function fetch_report_data(){
        global $FANNIE_OP_DB, $FANNIE_COOP_ID;
        $d1 = $this->form->date1;
        $d2 = $this->form->date2;
        $dept = FormLib::get_form_value('dept',0);
        $store = FormLib::get('store');

        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        // Can dlog views if they include cost.
        $dtrans = DTransactionsModel::select_dtrans($d1,$d2);
        $datestamp = $dbc->identifierEscape('datetime');

        if ( isset($FANNIE_COOP_ID) && $FANNIE_COOP_ID == 'WEFC_Toronto' )
            $shrinkageUsers = " AND (t.card_no not between 99900 and 99998)";
        else
            $shrinkageUsers = "";

        // The eventual return value.
        $data = array();

        $taxNames = array(0 => '');
        $tQ = $dbc->prepare("SELECT id, rate, description FROM taxrates WHERE id > 0 ORDER BY id");
        $tR = $dbc->execute($tQ);
        // Try generating code in this loop for use in SELECT and reporting.
        //  See SalesAndTaxTodayReport.php
        while ( $trow = $dbc->fetchRow($tR) ) {
            $taxNames[$trow['id']] = $trow['description'];
        }

        /**
          Margin column was added to departments but if
          deptMargin is present data may not have been migrated
        */
        $margin = 'd.margin';
        $departments_table = $dbc->tableDefinition('departments');
        if ($dbc->tableExists('deptMargin')) {
            $margin = 'm.margin';
        } elseif (!isset($departments_table['margin'])) {
            $margin = '0.00';
        }

        /* Using department settings at the time of sale.
         * I.e. The department# from the transaction.
         *  If that department# no longer exists or is different then the report will be wrong.
         *  This does not use a departments table contemporary with the transactions.
         * [0]Dept_name [1]Cost, [2]HST, [3]GST, [4]Sales, [x]Qty, [x]superID, [x]super_name
        */
        if ($dept == 0){
            // Change varname to sales or totals
            $costs = "SELECT
                    d.dept_name dname,
                    sum(CASE WHEN t.trans_type = 'I' THEN t.cost 
                         WHEN t.trans_type = 'D' AND $margin > 0.00 
                         THEN t.total - (t.total * $margin) END) AS costs,
                    sum(CASE WHEN t.tax = 1 THEN t.total * x.rate ELSE 0 END) AS taxes1,
                    sum(CASE WHEN t.tax = 2 THEN t.total * x.rate ELSE 0 END) AS taxes2,
                    sum(t.total) AS sales,
                    sum(t.quantity) AS qty,
                    s.superID AS sid,
                    s.super_name AS sname
                FROM
                    $dtrans AS t LEFT JOIN
                    departments AS d ON d.dept_no=t.department LEFT JOIN
                    MasterSuperDepts AS s ON t.department=s.dept_ID LEFT JOIN
                    taxrates AS x ON t.tax=x.id ";
                if ($margin == 'm.margin') {
                    $costs .= " LEFT JOIN deptMargin AS m ON t.department=m.dept_id ";
                }
                $costs .= "
                WHERE 
                    ($datestamp BETWEEN ? AND ?)
                    AND (s.superID > 0 OR s.superID IS NULL) 
                    AND t.trans_type in ('I','D')
                    AND t.trans_status not in ('D','X','Z')
                    AND t.emp_no not in (9999){$shrinkageUsers}
                    AND t.register_no != 99
                    AND t.upc != 'DISCOUNT'
                    AND t.trans_subtype not in ('CP','IC')
                    AND " . DTrans::isStoreID($store, 't') . "
                GROUP BY
                    s.superID, s.super_name, d.dept_name, t.department
                ORDER BY
                    s.superID, t.department";

        }
        /* Using current department settings.
         * I.e. The department for the upc from the current products table.
         *  This does not use a departments table contemporary with the transactions.
        */
        elseif ($dept == 1){
            $costs = "SELECT
                CASE WHEN e.dept_name IS NULL THEN d.dept_name ELSE e.dept_name END AS dname,
                sum(CASE WHEN t.trans_type = 'I' THEN t.cost 
                     WHEN t.trans_type = 'D' AND $margin > 0.00 
                     THEN t.total - (t.total * $margin) END) AS costs,
                sum(CASE WHEN t.tax = 1 THEN t.total * x.rate ELSE 0 END) AS taxes1,
                sum(CASE WHEN t.tax = 2 THEN t.total * x.rate ELSE 0 END) AS taxes2,
                sum(t.total) AS sales,
                sum(t.quantity) AS qty,
                CASE WHEN s.superID IS NULL THEN r.superID ELSE s.superID END AS sid,
                CASE WHEN s.super_name IS NULL THEN r.super_name ELSE s.super_name END AS sname
            FROM
                $dtrans AS t LEFT JOIN
                products AS p ON t.upc=p.upc LEFT JOIN
                departments AS d ON d.dept_no=t.department LEFT JOIN
                departments AS e ON p.department=e.dept_no LEFT JOIN
                MasterSuperDepts AS s ON s.dept_ID=p.department LEFT JOIN
                MasterSuperDepts AS r ON r.dept_ID=t.department LEFT JOIN
                taxrates AS x ON t.tax=x.id ";
            if ($margin == 'm.margin') {
                $costs .= " LEFT JOIN deptMargin AS m ON t.department=m.dept_id ";
            }
            $costs .= "
            WHERE
                ($datestamp BETWEEN ? AND ?)
                AND (s.superID > 0 OR (s.superID IS NULL AND r.superID > 0)
                    OR (s.superID IS NULL AND r.superID IS NULL))
                AND t.trans_type in ('I','D')
                AND t.trans_status not in ('D','X','Z')
                AND t.emp_no not in (9999){$shrinkageUsers}
                AND t.register_no != 99
                AND t.upc != 'DISCOUNT'
                AND t.trans_subtype not in ('CP','IC')
                AND " . DTrans::isStoreID($store, 't') . "
            GROUP BY
                CASE WHEN s.superID IS NULL THEN r.superID ELSE s.superID end,
                CASE WHEN s.super_name IS NULL THEN r.super_name ELSE s.super_name END,
                CASE WHEN e.dept_name IS NULL THEN d.dept_name ELSE e.dept_name end,
                CASE WHEN e.dept_no IS NULL THEN d.dept_no ELSE e.dept_no end
            ORDER BY
                CASE WHEN s.superID IS NULL THEN r.superID ELSE s.superID end,
                CASE WHEN e.dept_no IS NULL THEN d.dept_no ELSE e.dept_no end";
        }
        $costsP = $dbc->prepare($costs);
        $costArgs = array($d1.' 00:00:00', $d2.' 23:59:59');
        $costArgs[] = $store;
        $costsR = $dbc->execute($costsP, $costArgs);

        // Array in which totals used in the report are accumulated.
        $supers = array();
        $curSuper = 0;
        $grandTotal = 0;
        $this->grandCostsTotal = 0;
        $this->grandSalesTotal = 0;
        $this->grandTax1Total = 0;
        $this->grandTax2Total = 0;

        while($row = $dbc->fetchRow($costsR)){
            if ($curSuper != $row['sid']){
                $curSuper = $row['sid'];
            }
            if (!isset($supers[$curSuper])) {
                $supers[$curSuper] = array(
                'name'=>$row['sname'],
                'qty'=>0.0,'costs'=>0.0,'sales'=>0.0,
                'taxes1'=>0.0,'taxes2'=>0.0,
                'depts'=>array());
            }
            $supers[$curSuper]['qty'] += $row['qty'];
            $supers[$curSuper]['costs'] += $row['costs'];
            $supers[$curSuper]['sales'] += $row['sales'];
            $supers[$curSuper]['taxes1'] += $row['taxes1'];
            $supers[$curSuper]['taxes2'] += $row['taxes2'];
            $this->grandCostsTotal += $row['costs'];
            $this->grandSalesTotal += $row['sales'];
            $this->grandTax1Total += $row['taxes1'];
            $this->grandTax2Total += $row['taxes2'];
            // GROUP BY produces 1 row per dept. Values are sums.
            $supers[$curSuper]['depts'][] = array('name'=>$row['dname'],
                'qty'=>$row['qty'],
                'costs'=>$row['costs'],
                'sales'=>$row['sales'],
                'taxes1'=>$row['taxes1'],
                'taxes2'=>$row['taxes2']);
        }

        $superCount=1;
        foreach($supers as $s){
            if ($s['sales']==0 && !$this->show_zero) {
                $superCount++;
                continue;
            }

            $this->report_headers[] = array("{$s['name']}",'Qty','Costs','% Costs',
                'DeptC%','Sales','% Sales','DeptS %', 'Margin %','Contrib', 'GST','HST');

            // add department records
            $superCostsSum = $s['costs'];
            $superSalesSum = $s['sales'];
            foreach($s['depts'] as $d){
                $record = array(
                    $d['name'],
                    sprintf('%.2f',$d['qty']),
                    sprintf('$%.2f',$d['costs'])
                );

                $costPercent = 'n/a';
                if ($this->grandCostsTotal > 0)
                    $costPercent = sprintf('%.2f%%',($d['costs'] / $this->grandCostsTotal) * 100);
                $record[] = $costPercent;

                $costPercent = 'n/a';
                if ($superCostsSum > 0)
                    $costPercent = sprintf('%.2f%%',($d['costs'] / $superCostsSum) * 100);
                $record[] = $costPercent;
    
                $record[] = sprintf('$%.2f',$d['sales']);

                $salePercent = 'n/a';
                if ($this->grandSalesTotal > 0)
                    $salePercent = sprintf('%.2f%%',($d['sales'] / $this->grandSalesTotal) * 100);
                $record[] = $salePercent;

                $salePercent = 'n/a';
                if ($superSalesSum > 0)
                    $salePercent = sprintf('%.2f%%',($d['sales'] / $superSalesSum) * 100);
                $record[] = $salePercent;

                $margin = 'n/a';
                if ($d['sales'] > 0 && $d['costs'] > 0)
                    $margin = sprintf('%.2f%%', (100 * ($d['sales']-$d['costs']) / $d['sales']));
                $record[] = $margin;

                $record[] = sprintf('$%.2f', $d['sales'] - $d['costs']);

                $record[] = sprintf('%.2f',$d['taxes2']);
                $record[] = sprintf('%.2f',$d['taxes1']);

                $data[] = $record;
            }

            /* "super record" is a row of totals for the superdept,
             * instead of using calculate_footers().
             */
            $record = array($s['name'],
                    sprintf('%.2f',$s['qty']),
                    sprintf('$%s',number_format($s['costs'],2))
                    );
            $costPercent = 'n/a';
            if ($this->grandCostsTotal > 0)
                $costPercent = sprintf('%.2f%%',($s['costs'] / $this->grandCostsTotal) * 100);
            $record[] = $costPercent;
            $record[] = '';
            $record[] = sprintf('$%s',number_format($s['sales'],2));
                //sprintf('%.2f',$s['sales']);
            $salePercent = 'n/a';
            if ($this->grandSalesTotal > 0)
                $salePercent = sprintf('%.2f%%',($s['sales'] / $this->grandSalesTotal) * 100);
            $record[] = $salePercent;
            $record[] = '';
            $margin = 'n/a';
            if ($s['sales'] > 0 && $s['costs'] > 0)
                $margin = sprintf('%.2f%%', (100 * ($s['sales']-$s['costs']) / $s['sales']));
            $record[] = $margin;
            $record[] = sprintf('$%.2f', $s['sales'] - $s['costs']);
            $record[] = sprintf('%.2f',$s['taxes2']);
            $record[] = sprintf('%.2f',$s['taxes1']);

            $record['meta'] = FannieReportPage::META_BOLD;

            $data[] = $record;

            // Rather than start a new report, insert a blank line between superdepts.
            $data[] = array('meta'=>FannieReportPage::META_BLANK);

            if ($superCount < count($supers)) {
                $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS);
            }
            $superCount++;
        }

        /** Discounts applied at the member type level.
         */
        $report = array();

        /* Headings
        */
        $this->report_headers[] = array(
            'MEMBER TYPE',
            'Qty',
            '',
            '',
            '',
            'Amount',
            '',
            '',
            '',
            '',
            ''
        );

        $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS);
        // A row for each type of member.
        $dDiscountTotal = 0;
        $dQtyTotal = 0;
        $discQ = $dbc->prepare("
                SELECT m.memDesc, 
                    SUM(t.total) AS Discount,
                    count(*) AS ct
                FROM $dtrans t
                    INNER JOIN {$FANNIE_OP_DB}.memtype m ON t.memType = m.memtype
                WHERE ($datestamp BETWEEN ? AND ?)
                    AND t.upc = 'DISCOUNT'
                    AND t.total <> 0
                    AND t.trans_status not in ('D','X','Z')
                    AND t.emp_no not in (9999){$shrinkageUsers}
                    AND t.register_no != 99
                    AND t.trans_subtype not in ('CP','IC')
                    AND " . DTrans::isStoreID($store, 't') . "
                GROUP BY m.memDesc
                ORDER BY m.memDesc");
        $discR = $dbc->execute($discQ,$costArgs);
        if ($discR === False) {
            $data[] = array("SQL exec on $dtrans failed");
        } else {
           $record = array('','','','','','','','','','','');
            while($discW = $dbc->fetch_row($discR)){
                $record[0]= $discW['memDesc'];
                $record[1]= $discW['ct'];
                    $dQtyTotal += $discW['ct'];
                $record[5]= sprintf('$%.2f',(1*$discW['Discount']));
                    $dDiscountTotal += (1*$discW['Discount']);
                $data[] = $record;
            }
            // Total Footer
            $record = array(
                "DISCOUNTS",
                number_format($dQtyTotal,0),
                '',
                '',
                '',
                number_format($dDiscountTotal,2),
                '',
                '',
                '',
                '',
                ''
            );
            $record['meta'] = FannieReportPage::META_BOLD;
            $data[] = $record;
            $data[] = array('meta'=>FannieReportPage::META_BLANK);
        }

        // The discount total is negative.
        $this->grandSalesTotal += $dDiscountTotal;

        $this->summary_data[] = $report;

        // End of Discounts

        /** The summary of grand totals proportions for the whole store.
         */

        // Headings
        $record = array(
            '',
            '',
            'Costs',
            '',
            '',
            'Sales',
            'Profit',
            '',
            'Margin %',
            isset($taxNames['2']) ? $taxNames['2'] : 'n/a',
            isset($taxNames['1']) ? $taxNames['1'] : 'n/a',
        );
        $record['meta'] = FannieReportPage::META_BOLD;
        $data[] = $record;

        // Grand totals
        $record = array(
            'WHOLE STORE',
            '',
            '$ '.number_format($this->grandCostsTotal,2),
            '',
            '',
            '$ '.number_format($this->grandSalesTotal,2),
            '$ '.number_format(($this->grandSalesTotal - $this->grandCostsTotal),2),
            ''
        );
        $margin = 'n/a';
        if ($this->grandSalesTotal > 0)
            $margin = number_format(((($this->grandSalesTotal - $this->grandCostsTotal) / $this->grandSalesTotal) * 100),2).' %';
        $record[] = $margin;
        $record[] = '$ '.number_format($this->grandTax2Total,2);
        $record[] = '$ '.number_format($this->grandTax1Total,2);
        $record['meta'] = FannieReportPage::META_BOLD;
        $data[] = $record;

        $this->grandTTL = $grandTotal;

        return $data;

    // fetch_report_data()
    }

    public function calculate_footers($data)
    {
        return array();
    // calculate_footers()
    }

    function form_content()
    {
        list($lastMonday, $lastSunday) = \COREPOS\Fannie\API\lib\Dates::lastWeek();
        $stores = FormLib::storePicker();
        ob_start();
        ?>
        <form action=StoreSummaryReport.php method=get>
        <div class="col-sm-5">
            <div class="form-group">
                <label>Start Date</label>
                <input type=text id=date1 name=date1 class="form-control date-field" 
                    value="<?php echo $lastMonday; ?>" />
            </div>
            <div class="form-group">
                <label>End Date</label>
                <input type=text id=date2 name=date2 class="form-control date-field" 
                    value="<?php echo $lastSunday; ?>" />
            </div>
            <div class="form-group">
                <select name=dept class="form-control">
                <option value=0>Use department settings at time of sale</option>
                <option value=1>Use current department settings</option>
                </select>
            </div>
            <div class="form-group">
                <label>Sortable
                    <input type="checkbox" name="sortable" />
                </label>
            </div>
            <div class="form-group">
                <label>Show SuperDepts with $0 or net $0 sales
                    <input type="checkbox" name="sortable" />
                </label>
            </div>
            <div class="form-group">
                <label>Store</label>
                <?php echo $stores['html']; ?>
            </div>
            <p>
                <button type="submit" class="btn btn-default">Submit</button>
            </p>
        </div>
        <div class="col-sm-5">
            <?php echo FormLib::date_range_picker(); ?>
        </div>
        </form>
        <?php

        return ob_get_clean();
    // form_content()
    }

    public function helpContent()
    {
       return '<p>
           This shows total sales, costs and taxes
           per department for a given date range in dollars as well as a percentage of 
           store-wide sales and costs. It uses actual item cost if known and estimates 
           cost from price and department margin if not; 
           relies on department margins being accurate.
           </p>';
    }

// StoreSummaryReport
}

FannieDispatch::conditionalExec();

