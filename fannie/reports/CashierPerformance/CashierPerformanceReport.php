<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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
                                    '#Trans', 'Minutes');

    public $description = '[Cashier Performance] lists cashier scan metrics over a given date range.';

    function fetch_report_data()
    {
        global $FANNIE_OP_DB, $FANNIE_ARCHIVE_DB, $FANNIE_TRANS_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $date1 = FormLib::get_form_value('date1',date('Y-m-d'));
        $date2 = FormLib::get_form_value('date2',date('Y-m-d'));
        $emp_no = FormLib::get('emp_no', false);

        $dtrans = DTransactionsModel::selectDTrans($date1,$date2);

        $detailP = $dbc->prepare('
            SELECT SUM(transInterval) AS seconds,
                COUNT(*) AS numTrans
            FROM ' . $FANNIE_TRANS_DB . $dbc->sep() . 'CashPerformDay_cache
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
            $record[] = $trans;
            $record[] = sprintf('%.2f', $time / 60.0);
            $data[] = $record;
        }

        return $data;
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
?>
<div id=main>   
<form method = "get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
    <table border="0" cellspacing="0" cellpadding="5">
        <tr> 
            <th>Cashier# <?php echo FannieHelp::ToolTip('Leave blank to list all cashiers'); ?></th>
            <td>
            <input type=text name=emp_no id=emp_no  />
            </td>
            <td>
            <input type="checkbox" name="excel" id="excel" value="xls" />
            <label for="excel">Excel</label>
            </td>   
        </tr>
        <tr>
            <th>Date Start</th>
            <td>    
                       <input type=text size=14 id=date1 name=date1 />
            </td>
            <td rowspan="3">
            <?php echo FormLib::date_range_picker(); ?>
            </td>
        </tr>
        <tr>
            <th>Date End</th>
            <td>
                        <input type=text size=14 id=date2 name=date2 />
               </td>

        </tr>
        <tr>
            <td> <input type=submit name=submit value="Submit"> </td>
            <td> <input type=reset name=reset value="Start Over"> </td>
        </tr>
    </table>
</form>
</div>
<?php
        $this->add_onload_command('$(\'#date1\').datepicker();');
        $this->add_onload_command('$(\'#date2\').datepicker();');
    }
}

FannieDispatch::conditionalExec(false);

?>
