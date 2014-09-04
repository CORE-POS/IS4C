<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 * 09Oct13 AT Duplicate existing report using FannieReportPage
    => First pass: I turned off the multiple option and altered the fetch data
       routine to return a single report. This is fairly close to matching but
       the formatting isn't as nice. I also added a bit more gating to avoid
       potential (if unlikely) divide by zero warnings.
    => Second pass: I added constants to FannieReportClass that can be used
       to trigger differently formatting and behavior for a particular record.
       This let me match EL's bolding and spacing. In a delightful surprise,
       jQuery's sorting understands the segmentation and sorts the
       superdepartments separately.

 * 22Jul13 EL Attempt to use dlog views must wait until they include cost.
*/
include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class StoreSummaryReportAlt extends FannieReportPage {

    protected $title = "Fannie : Store Summary Report";
    protected $header = "Store Summary Report";

    protected $report_headers = array('','Qty','Costs','% Costs','DeptC%','Sales','% Sales','DeptS %',
                'Margin %','GST','HST');
    protected $required_fields = array('date1', 'date2');

    public $description = '[Store Summary Report] shows total sales, costs and taxes per department for a given date range in dollars as well as a percentage of store-wide sales and costs. It uses actual item cost if known and estimates cost from price and department margin if not; relies on department margins being accurate.';
    public $report_set = 'Sales Reports';

    function preprocess()
    {
        // custom: optional sorting
        if (FormLib::get_form_value('sortable') !== '') {
            $this->sortable = true;
        } else {
            $this->sortable = false;
        }
        $this->cellTextAlign = 'right';

        return parent::preprocess();
    }

    function report_description_content(){
        $ret = array();
        if (FormLib::get_form_value('dept',0) == 0){
            $ret[] = "<p>Using the department# the upc was assigned to at time of sale</p>";
        }
        else{
            $ret[] = "<p>Using the department# the upc is assigned to now</p>";
        }
        $ret[] = "<p>Note: For items where cost is not recorded the margin in the deptMargin table is relied on.</p>";
        return $ret;
    }

    function fetch_report_data(){
        global $FANNIE_OP_DB, $FANNIE_COOP_ID;
        $d1 = FormLib::get_form_value('date1',date('Y-m-d'));
        $d2 = FormLib::get_form_value('date2',date('Y-m-d'));
        $dept = FormLib::get_form_value('dept',0);

        $dbc = FannieDB::get($FANNIE_OP_DB);

        $dtrans = DTransactionsModel::selectDtrans($d1,$d2);
        $datestamp = $dbc->identifier_escape('datetime');

        if ( isset($FANNIE_COOP_ID) && $FANNIE_COOP_ID == 'WEFC_Toronto' )
            $shrinkageUsers = " AND t.card_no not between 99990 and 99998";
        else
            $shrinkageUsers = "";

        // The eventual return value.
        $data = array();

        $taxNames = array(0 => '');
        $tQ = $dbc->prepare_statement("SELECT id, rate, description FROM taxrates WHERE id > 0 ORDER BY id");
        $tR = $dbc->exec_statement($tQ);
        // Try generating code in this loop for use in SELECT and reporting.
        //  See SalesAndTaxTodayReport.php
        while ( $trow = $dbc->fetch_array($tR) ) {
            $taxNames[$trow['id']] = $trow['description'];
        }

        /* Using department settings at the time of sale.
         * I.e. The department# from the transaction.
         *  If that department# no longer exists or is different then the report will be wrong.
         *  This does not use a departments table contemporary with the transactions.
         * [0]Dept_name [1]Cost, [2]HST, [3]GST, [4]Sales, [x]Qty, [x]superID, [x]super_name
        */
        $departments = $dbc->tableDefinition('departments');
        if ($dept == 0){
            // Change varname to sales or totals
            $costs = "SELECT
                    d.dept_name dname,
                    sum(CASE WHEN t.trans_type = 'I' THEN t.cost 
                         WHEN t.trans_type = 'D' AND d.margin > 0.00 
                         THEN t.total - (t.total * d.margin) END) AS costs,
                    sum(CASE WHEN t.tax = 1 THEN t.total * x.rate ELSE 0 END) AS taxes1,
                    sum(CASE WHEN t.tax = 2 THEN t.total * x.rate ELSE 0 END) AS taxes2,
                    sum(t.total) AS sales,
                    sum(t.quantity) AS qty,
                    s.superID AS sid,
                    s.super_name AS sname
                FROM
                    $dtrans AS t LEFT JOIN
                    departments AS d ON d.dept_no=t.department LEFT JOIN
                    MasterSuperDepts AS s ON t.department=s.dept_ID LEFT JOIN ";
                // use margin column from departments if present
                if (!isset($departments['margin']) && $dbc->tableExists('deptMargin')) {
                    $costs .= ' deptMargin AS m ON t.department=m.dept_id LEFT JOIN ';
                    $costs = str_replace('d.margin', 'm.margin', $costs);
                }
                $costs .= " taxrates AS x ON t.tax=x.id
                WHERE 
                    ($datestamp BETWEEN ? AND ?)
                    AND (s.superID > 0 OR s.superID IS NULL) 
                    AND t.trans_type in ('I','D')
                    AND t.trans_status not in ('D','X','Z')
                    AND t.emp_no not in (9999){$shrinkageUsers}
                    AND t.register_no != 99
                    AND t.upc != 'DISCOUNT'
                    AND t.trans_subtype not in ('CP','IC')
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
                     WHEN t.trans_type = 'D' AND d.margin > 0.00 
                     THEN t.total - (t.total * d.margin) END) AS costs,
                sum(CASE WHEN t.tax = 1 THEN t.total * x.rate ELSE 0 END) AS taxes1,
                sum(CASE WHEN t.tax = 2 THEN t.total * x.rate ELSE 0 END) AS taxes2,
                sum(t.total) AS sales,
                sum(t.quantity) AS qty,
                CASE WHEN s.superID IS NULL THEN r.superID ELSE s.superID END AS sid,
                CASE WHEN s.super_name IS NULL THEN r.super_name ELSE s.super_name END AS sname
            FROM
                $dlog AS t LEFT JOIN
                products AS p ON t.upc=p.upc LEFT JOIN
                departments AS d ON d.dept_no=t.department LEFT JOIN
                departments AS e ON p.department=e.dept_no LEFT JOIN
                MasterSuperDepts AS s ON s.dept_ID=p.department LEFT JOIN
                MasterSuperDepts AS r ON r.dept_ID=t.department LEFT JOIN ";
            // use margin column from departments if present
            if (!isset($departments['margin']) && $dbc->tableExists('deptMargin')) {
                $costs .= ' deptMargin AS m ON p.department=m.dept_id LEFT JOIN ';
                $costs = str_replace('d.margin', 'm.margin', $costs);
            }
            $costs .= "taxrates AS x ON t.tax=x.id
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
            GROUP BY
                CASE WHEN s.superID IS NULL THEN r.superID ELSE s.superID end,
                CASE WHEN s.super_name IS NULL THEN r.super_name ELSE s.super_name END,
                CASE WHEN e.dept_name IS NULL THEN d.dept_name ELSE e.dept_name end,
                CASE WHEN e.dept_no IS NULL THEN d.dept_no ELSE e.dept_no end
            ORDER BY
                CASE WHEN s.superID IS NULL THEN r.superID ELSE s.superID end,
                CASE WHEN e.dept_no IS NULL THEN d.dept_no ELSE e.dept_no end";
        }
        $costsP = $dbc->prepare_statement($costs);
        $costArgs = array($d1.' 00:00:00', $d2.' 23:59:59');
        $costsR = $dbc->exec_statement($costsP, $costArgs);

        // Array in which totals used in the report are accumulated.
        $supers = array();
        $curSuper = 0;
        $grandTotal = 0;
        $this->grandCostsTotal = 0;
        $this->grandSalesTotal = 0;
        $this->grandTax1Total = 0;
        $this->grandTax2Total = 0;

        while($row = $dbc->fetch_array($costsR)){
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
            if ($s['sales']==0) continue;

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
                    $costPercent = sprintf('%.2f %%',($d['costs'] / $this->grandCostsTotal) * 100);
                $record[] = $costPercent;

                $costPercent = 'n/a';
                if ($superCostsSum > 0)
                    $costPercent = sprintf('%.2f %%',($d['costs'] / $superCostsSum) * 100);
                $record[] = $costPercent;
    
                $record[] = sprintf('$%.2f',$d['sales']);

                $salePercent = 'n/a';
                if ($this->grandSalesTotal > 0)
                    $salePercent = sprintf('%.2f %%',($d['sales'] / $this->grandSalesTotal) * 100);
                $record[] = $salePercent;

                $salePercent = 'n/a';
                if ($superSalesSum > 0)
                    $salePercent = sprintf('%.2f %%',($d['sales'] / $superSalesSum) * 100);
                $record[] = $salePercent;

                $margin = 'n/a';
                if ($d['sales'] > 0 && $d['costs'] > 0)
                    $margin = sprintf('%.2f %%', (100 * ($d['sales']-$d['costs']) / $d['sales']));
                $record[] = $margin;

                $record[] = sprintf('%.2f',$d['taxes2']);
                $record[] = sprintf('%.2f',$d['taxes1']);

                $data[] = $record;
            }

            // add super record
            $record = array($s['name'],
                    sprintf('%.2f',$s['qty']),
                    sprintf('%.2f',$s['costs'])
            );
            $costPercent = 'n/a';
            if ($this->grandCostsTotal > 0)
                $costPercent = sprintf('%.2f %%',($s['costs'] / $this->grandCostsTotal) * 100);
            $record[] = $costPercent;
            $record[] = '';
            $record[] = sprintf('%.2f',$s['sales']);
            $salePercent = 'n/a';
            if ($this->grandSalesTotal > 0)
                $salePercent = sprintf('%.2f %%',($s['sales'] / $this->grandSalesTotal) * 100);
            $record[] = $salePercent;
            $record[] = '';
            $margin = 'n/a';
            if ($s['sales'] > 0 && $s['costs'] > 0)
                $margin = sprintf('%.2f %%', (100 * ($s['sales']-$s['costs']) / $s['sales']));
            $record[] = $margin;
            $record[] = sprintf('%.2f',$s['taxes2']);
            $record[] = sprintf('%.2f',$s['taxes1']);

            $record['meta'] = FannieReportPage::META_BOLD;

            $data[] = $record;

            // Rather than start a new report, I'm just inserting a blank
            // line between supers
            $data[] = array('meta'=>FannieReportPage::META_BLANK);

            if ($superCount < count($supers)-1)
                $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS);
            $superCount++;
        }

        // The summary of grand totals proportions.

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
            $taxNames['2'],
            $taxNames['1']
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

    function form_content(){
        $lastMonday = "";
        $lastSunday = "";

        $ts = mktime(0,0,0,date("n"),date("j")-1,date("Y"));
        while($lastMonday == "" || $lastSunday == ""){
            if (date("w",$ts) == 1 && $lastSunday != "")
                $lastMonday = date("Y-m-d",$ts);
            elseif(date("w",$ts) == 0)
                $lastSunday = date("Y-m-d",$ts);
            $ts = mktime(0,0,0,date("n",$ts),date("j",$ts)-1,date("Y",$ts));    
        }
        ?>
        <form action=StoreSummaryReportAlt.php method=get>
        <table cellspacing=4 cellpadding=4 border=0>
        <tr>
        <th>Start Date</th>
        <td><input type=text id=date1 name=date1 value="<?php echo $lastMonday; ?>" /></td>
        <td rowspan="2">
        <?php echo FormLib::date_range_picker(); ?>
        </td>
        </tr><tr>
        <th>End Date</th>
        <td><input type=text id=date2 name=date2 value="<?php echo $lastSunday; ?>" /></td>
        </tr><tr>
        <td colspan=2><select name=dept>
        <option value=0>Use department settings at time of sale</option>
        <option value=1>Use current department settings</option>
        </select></td>
        </tr><tr>
        <td colspan=2><!--Excel <input type=checkbox name=excel />
        &nbsp; &nbsp; &nbsp; -->Sortable <input type=checkbox name=sortable />
        &nbsp; &nbsp; &nbsp; <input type=submit name=submit value="Submit" /></td>
        </tr>
        </table>
        </form>
        <?php
        $this->add_onload_command('$(\'#date1\').datepicker();');
        $this->add_onload_command('$(\'#date2\').datepicker();');

    // form_content()
    }

// StoreSummaryReport
}

FannieDispatch::conditionalExec();

?>
