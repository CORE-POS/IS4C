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

class ChartOfAccountsReport extends FannieReportPage 
{
    public $description = '[Chart of Accounts report] lists tenders, sales, discounts, and taxes for a given 
                    range of dates with their chart of account numbers.';
    public $report_set = 'Finance';
    public $themed = true;

    protected $title = "Fannie : Chart of Accounts Report";
    protected $header = "Chart of Accounts Report";
    protected $report_cache = 'none';

    protected $report_headers = array('Report Date', 'Account #', 'Credits', 'Debits', 'Memo');
    protected $required_fields = array('date1', 'date2');

    function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->setDefaultDB($this->config->get('OP_DB'));
        $d1 = $this->form->date1;
        $d2 = $this->form->date2;
        $dates = array($d1.' 00:00:00', $d2.' 23:59:59');
        $today = date('Ymd');
        $data = array();

        $dlog = DTransactionsModel::selectDlog($d1);
        $tenderQ = $dbc->prepare("
            SELECT TenderName,
                COUNT(d.total) AS num,
                SUM(d.total) as total,
                t.SalesCode
            FROM $dlog AS d
                LEFT JOIN tenders as t ON d.trans_subtype=t.TenderCode
            WHERE d.tdate BETWEEN ? AND ?
                AND d.trans_type = 'T'
                AND d.total <> 0
            GROUP BY t.TenderName,
                t.SalesCode 
            ORDER BY TenderName");
        $tenderR = $dbc->execute($tenderQ,$dates);
        while ($tenderW = $dbc->fetch_row($tenderR)) {
            $credit = $tenderW['total'] <= 0 ? abs($tenderW['total']) : 0.00;
            $debit = $tenderW['total'] > 0 ? $tenderW['total'] : 0.00;
            $data[] = array(
                $today,
                $tenderW['SalesCode'],
                sprintf('%.2f', $credit),
                sprintf('%.2f', $debit),
                $tenderW['TenderName'],
            );
        }

        $salesQ = '
            SELECT t.salesCode AS category,
                SUM(d.quantity) AS qty,
                SUM(d.total) AS total
            FROM ' . $dlog . ' AS d
                LEFT JOIN departments AS t ON d.department=t.dept_no
            WHERE d.department <> 0
                AND d.trans_type <> \'T\'
                AND d.tdate BETWEEN ? AND ?
            GROUP BY t.salesCode
            ORDER BY t.salesCode'; 
        $salesP = $dbc->prepare($salesQ);
        $salesR = $dbc->execute($salesP,$dates);
        $report = array();
        while ($salesW = $dbc->fetch_row($salesR)) {
            $credit = $salesW['total'] < 0 ? abs($salesW['total']) : 0.00;
            $debit = $salesW['total'] >= 0 ? $salesW['total'] : 0.00;
            $data[] = array(
                $today,
                $salesW['category'],
                sprintf('%.2f', $credit),
                sprintf('%.2f', $debit),
                'Sales ' . $salesW['category'],
            );
        }

        $discQ = $dbc->prepare("
                SELECT m.memDesc, 
                    SUM(d.total) AS total,
                    count(*) AS num,
                    m.salesCode
                FROM $dlog d 
                    LEFT JOIN memtype m ON d.memType = m.memtype
                WHERE d.tdate BETWEEN ? AND ?
                   AND d.upc = 'DISCOUNT'
                    AND total <> 0
                GROUP BY m.memDesc,
                    m.salesCode 
                ORDER BY m.memDesc");
        $discR = $dbc->execute($discQ,$dates);
        while ($discW = $dbc->fetch_row($discR)) {
            $credit = $discW['total'] <= 0 ? abs($discW['total']) : 0.00;
            $debit = $discW['total'] > 0 ? $discW['total'] : 0.00;
            $data[] = array(
                $today,
                $discW['salesCode'],
                sprintf('%.2f', $credit),
                sprintf('%.2f', $debit),
                $discW['memDesc'] . ' Discount',
            );
        }

        $report = array();
        $trans = DTransactionsModel::selectDTrans($d1);
        $lineItemQ = $dbc->prepare("
            SELECT d.description,
                SUM(regPrice) AS ttl,
                t.salesCode
            FROM $trans AS d
                LEFT JOIN taxrates AS t ON d.numflag=t.id
            WHERE datetime BETWEEN ? AND ?
                AND d.upc='TAXLINEITEM'
                AND " . DTrans::isNotTesting('d') . "
            GROUP BY d.description
        ");
        $lineItemR = $dbc->execute($lineItemQ, $dates);
        while ($lineItemW = $dbc->fetch_row($lineItemR)) {
            $credit = $lineItemW['ttl'] < 0 ? abs($lineItemW['ttl']) : 0.00;
            $debit = $lineItemW['ttl'] >= 0 ? $lineItemW['ttl'] : 0.00;
            $data[] = array(
                $today,
                $lineItemW['salesCode'],
                sprintf('%.2f', $credit),
                sprintf('%.2f', $debit),
                $lineItemW['description'] . ' tax',
            );
        }

        return $data;
    }

    function calculate_footers($data)
    {
        $credit = 0;
        $debit = 0;
        foreach ($data as $row) {
            $credit += $row[2];
            $debit += $row[3];
        }

        return array(null, null, sprintf('%.2f', $credit), sprintf('%.2f', $debit), '');
    }

    function form_content()
    {
        ob_start();
        ?>
        <form method=get>
        <div>
            <?php echo FormLib::standardDateFields(); ?>
            <div class="form-group">
                <label>Excel <input type=checkbox name=excel /></label>
            </div>
            <p>
            <button type=submit name=submit value="Submit"
                class="btn btn-default">Submit</button>
            </p>
        </div>
        </form>
        <?php
        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>
            This report lists the four major categories of transaction
            information for a given day: tenders, sales, discounts, and
            taxes. Each line includes a chart of accounts number. Chart
            of accounts numbers are assigned to tenders, POS departments,
            member/customer types, and tax rates (respectively).
            </p>
            <p>
            Tenders are payments given by customers such as cash or
            credit cards. Sales are items sold to customers. Discounts
            are percentage discounts associated with an entire
            transaction instead of individual items. Taxes are sales
            tax collected.
            </p>
            <p>
            Tenders should equal sales minus discounts plus taxes.
            </p>';
    }
}

FannieDispatch::conditionalExec();

