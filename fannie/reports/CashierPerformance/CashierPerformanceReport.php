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

class CashierPerformanceReport extends FannieReportPage 
{

    protected $title = "Fannie : Cashier Performance";
    protected $header = "Cashier Performance Report";
    protected $required_fields = array('date1', 'date2');

    protected $report_headers = array('Cashier', 'Rings', 'Refunds', 'Refund%', 'Avg. Refund',
                                    'Voids', 'Void%', 'Avg. Void', 'Open Rings', 'Open Ring%',
                                    'Avg. Open Ring', 'Cancels', 'Cancel%', 'Avg. Cancel',
                                    '#Trans', 'Minutes', 'Rings/Minute');

    public $description = '[Cashier Performance] lists cashier scan metrics over a given date range.';
    public $themed = true;
    public $report_set = 'Cashiering';

    function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $date1 = $this->form->date1;
        $date2 = $this->form->date2;
        $emp_no = FormLib::get('emp_no', false);

        $dtrans = DTransactionsModel::selectDTrans($date1,$date2);

        $detailP = $dbc->prepare('
            SELECT SUM(CASE WHEN transInterval > 600 THEN 600 ELSE transInterval END) AS seconds,
                COUNT(*) AS numTrans
            FROM ' . $this->config->get('TRANS_DB') . $dbc->sep() . 'CashPerformDay
            WHERE proc_date BETWEEN ? AND ?
                AND emp_no = ?
        ');

        $basicQ = '
            SELECT d.emp_no,
                e.FirstName,
                COUNT(*) AS rings,
                SUM(CASE WHEN trans_status=\'R\' THEN 1 ELSE 0 END) as refundRings,
                SUM(CASE WHEN trans_status=\'V\' THEN 1 ELSE 0 END) as voidRings,
                SUM(CASE WHEN trans_status=\'R\' THEN total ELSE 0 END) as refundTotal,
                SUM(CASE WHEN trans_status=\'V\' THEN total ELSE 0 END) as voidTotal,
                SUM(CASE WHEN trans_type=\'D\' THEN 1 ELSE 0 END) as openRings,
                SUM(CASE WHEN trans_type=\'D\' THEN total ELSE 0 END) as openRingTotal,
                SUM(CASE WHEN trans_status=\'X\' AND charflag <> \'S\' THEN 1 ELSE 0 END) as cancelRings,
                SUM(CASE WHEN trans_status=\'X\' AND charflag <> \'S\' THEN total ELSE 0 END) as cancelTotal
            FROM ' . $dtrans . ' AS d
                INNER JOIN employees AS e ON d.emp_no=e.emp_no
            WHERE d.datetime BETWEEN ? AND ?
                AND trans_type IN (\'I\', \'D\')
                AND trans_status <> \'M\'
                AND register_no <> 99
                AND d.emp_no <> 9999
                AND d.store_id <> 50
        ';
        if ($emp_no) {
            $basicQ .= ' AND d.emp_no = ? ';
        }
        $basicQ .= '
            GROUP BY d.emp_no, e.FirstName
            ORDER BY e.FirstName';
        $basicP = $dbc->prepare($basicQ);
        $args = array($date1 . ' 00:00:00', $date2 . ' 23:59:59');
        if ($emp_no) {
            $args[] = $emp_no;
        }

        $basicR = $dbc->execute($basicP, $args);
        $data = array();
        while ($row = $dbc->fetch_row($basicR)) {
            $record = array(
                $row['FirstName'],
                $row['rings'],
                $row['refundRings'],
                sprintf('%.2f%%', $this->safeDivide($row['refundRings'], $row['rings']) * 100.00),
                sprintf('$%.2f', $this->safeDivide($row['refundTotal'], $row['refundRings'])),
                $row['voidRings'],
                sprintf('%.2f%%', $this->safeDivide($row['voidRings'], $row['rings']) * 100.00),
                sprintf('$%.2f', $this->safeDivide($row['voidTotal'], $row['voidRings'])),
                $row['openRings'],
                sprintf('%.2f%%', $this->safeDivide($row['openRings'], $row['rings']) * 100.00),
                sprintf('$%.2f', $this->safeDivide($row['openRingTotal'], $row['openRings'])),
                $row['cancelRings'],
                sprintf('%.2f%%', $this->safeDivide($row['cancelRings'], $row['rings']) * 100.00),
                sprintf('$%.2f', $this->safeDivide($row['cancelTotal'], $row['cancelRings'])),
            );
            $args[2] = $row['emp_no'];
            $detailR = $dbc->execute($detailP, $args);
            $detailW = $dbc->fetch_row($detailR);
            $time = $detailW['seconds'];
            $trans = $detailW['numTrans'];
            $minutes = $time / 60.0;
            $record[] = $trans;
            $record[] = sprintf('%.2f', $time / 60.0);
            $record[] = sprintf('%.2f', $this->safeDivide($row['rings'], $minutes));
            $data[] = $record;
        }

        return $data;
    }

    public function calculate_footers($data)
    {
        if (count($data) == 0) {
            return array();
        }
        $sums = array();
        for ($i = 0; $i<count($data[0]); $i++) {
            $sums[$i] = 0.0;
        }
        $count = 0.0;
        foreach ($data as $row) {
            for ($i=1; $i<count($row); $i++) {
                $val = trim($row[$i], '$%');
                $sums[$i] += $val;
            }
            $count++;
        }

        $ret = array('Average');
        for ($i=1; $i<count($sums); $i++) {
            $ret[] = sprintf('%.2f', $sums[$i] / $count);
        }

        return $ret;
    }

    private function safeDivide($a, $b)
    {
        if ($b == 0) {
            return 0.0;
        } else {
            return ((float)$a) / ((float)$b);
        }
    }
    
    function form_content()
    {
        global $FANNIE_URL;
        ob_start();
?>
<form method = "get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
<div class="col-sm-4">
    <div class="form-group">
        <label>Cashier#
            <?php echo \COREPOS\Fannie\API\lib\FannieHelp::ToolTip('Leave blank to list all cashiers'); ?></label>
        <input type=text name=emp_no id=emp_no  class="form-control" />
    </div>
    <div class="form-group">
        <label>Date Start</label>
        <input type=text id=date1 name=date1 class="form-control date-field" required />
    </div>
    <div class="form-group">
        <label>End Start</label>
        <input type=text id=date2 name=date2 class="form-control date-field" required />
    </div>
    <div class="form-group">
        <input type="checkbox" name="excel" id="excel" value="xls" />
        <label for="excel">Excel</label>
    </div>
    <p>
        <button type=submit class="btn btn-default btn-submit">Submit</button>
        <button type=reset class="btn btn-default btn-reset">Start Over</button>
    </p>
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
            This report displays information about one or many
            cashiers during the time period. The base unit of 
            measurement is rings. Each ring is one item passing
            through the scanner-scale. Refunds, voids, and cancels
            are shown as both totals and percentages of total
            rings. In this context, "void" means reversing a single
            line item in a transaction and "cancel" means abandoning
            an in-progress transaction completely.
            </p>
            <p>
            The last three columns, #Trans, Minutes, and Rings/Minute
            are only valid for the past 90 days or so. Calculating
            the time spent ringing items on the fly is not feasible so
            that data must be prebuilt. Minutes is measured from the
            first <strong>item</strong> entered into the transaction
            to the last <strong>item</strong> entered into the
            transaction. Time spent entering member numbers, dealing
            with tenders, or between transactions is not included. 
            </p>';
    }
}

FannieDispatch::conditionalExec();

