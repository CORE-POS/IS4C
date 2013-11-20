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

include('../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class CashierRecordsReport extends FannieReportPage 
{

    protected $report_headers = array('Emp#', 'Date', '$', '# of Trans');
    protected $sort_column = 3;
    protected $sort_direction = 1;

	public function preprocess()
    {
		$this->report_cache = 'day';
		$this->title = "Fannie : Cashier Shift Records Report";
		$this->header = "Cashier Shift Records Report";

		if (isset($_REQUEST['date1'])){
			$this->content_function = "report_content";
			$this->has_menus(False);
		
			if (isset($_REQUEST['excel']) && $_REQUEST['excel'] == 'xls') {
				$this->report_format = 'xls';
			} elseif (isset($_REQUEST['excel']) && $_REQUEST['excel'] == 'csv') {
				$this->report_format = 'csv';
            }
		}
		else 
			$this->add_script("../../src/CalendarControl.js");

		return true;
	}

	public function fetch_report_data()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $date1 = FormLib::get('date1', date('Y-m-d'));
        $date2 = FormLib::get('date2', date('Y-m-d'));

        $dlog = DTransactionsModel::selectDlog($date1, $date2);
        $q = $dbc->prepare_statement("select emp_no,sum(-total),count(DISTINCT trans_num),
                year(tdate),month(tdate),day(tdate)
                from $dlog as d where
                tdate BETWEEN ? AND ?
                AND trans_type='T'
                GROUP BY year(tdate),month(tdate),day(tdate),emp_no
                ORDER BY sum(-total) DESC");
        $r = $dbc->exec_statement($q,array($date1.' 00:00:00',$date2.' 23:59:59'));

        $data = array();
        while($row = $dbc->fetch_row($r)){
            $record = array($row['emp_no'],
                        sprintf('%d/%d/%d',$row[4],$row[5],$row[3]),
                        sprintf('%.2f',$row[1]),
                        $row[2]);
            $data[] = $record;
        }
        return $data;
	}

	public function form_content()
    {
        ob_start();
?>
<div id=main>
<form method ="get" action="CashierRecordsReport.php">
	<table border="0" cellspacing="0" cellpadding="5">
		<tr> 
            <td>
                <p><b>Date Start</b> </p>
                <p><b>End</b></p>
            </td>
            <td>
                <p>
                <input type=text id=date1 name=date1 onfocus="this.value='';showCalendarControl(this);">
                </p>
                <p>
                <input type=text id=date2 name=date2 onfocus="this.value='';showCalendarControl(this);">
                </p>
            </td>
            <td colspan="2" rowspan="2">
                <?php echo FormLib::date_range_picker(); ?>
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
        return ob_get_clean();
	}
}

FannieDispatch::go();

?>
