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
 * 22Jul13 EL Attempt to use dlog views must wait until they include cost.
*/
include('../../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class StoreSummaryReport extends FannieReportPage2 {

	private $grandTTL;
	private $grandCostsTotal;
	private $grandSalesTotal;
	private $grandTax1Total;
	private $grandTax2Total;

	protected $report_desc = array();

	public function __construct() {
		// To set authentication.
		parent::__construct();
		// Would dialing-direct work? Seems to. No, it doesn't.
		// FanniePage::__construct();
	}

	function preprocess(){
		$this->title = "Fannie : Store Summary Report";
		$this->header = "Store Summary Report";
		$this->report_cache = 'none';
		$this->grandTTL = 1;
		$this->multi_report_mode = True;
		if (isset($_REQUEST['sortable']))
			$this->sortable = True;
		else
			$this->sortable = False;
		$this->cellTextAlign = 'right';

		if (isset($_REQUEST['date1'])){
			$this->content_function = "report_content";
			$this->has_menus(True); // 1Jul13 was False, normal for reports of this kind.
			$this->report_headers = array('','Qty','Costs','% Costs','DeptC%','Sales','% Sales','DeptS %',
				'Margin %','GST','HST');

			/**
			  Check if a non-html format has been requested
			   from the links in the initial display, not the form.
			*/
			if (isset($_REQUEST['excel']) && $_REQUEST['excel'] == 'xls')
				$this->report_format = 'xls';
			elseif (isset($_REQUEST['excel']) && $_REQUEST['excel'] == 'csv')
				$this->report_format = 'csv';
		}
		else 
			$this->add_script("../../src/CalendarControl.js");

		return True;
	}

	function report_description_content(){
		return $this->report_desc;
	}

	function get_superdept_name($department_name){
		global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
		$ret = '';
		if ( $department_name != "" ) {
			$sel = "SELECT super_name
			FROM {$FANNIE_OP_DB}.MasterSuperDepts s
			INNER JOIN {$FANNIE_OP_DB}.departments d ON d.dept_no = s.dept_ID
			WHERE d.dept_name = ?";
			$selP = $dbc->prepare_statement($sel);
			$selArgs = array($department_name);
			$selR = $dbc->exec_statement($selP, $selArgs);
			if ($selR && $dbc->num_rows($selR)>0) {
				while($row = $dbc->fetch_array($selR)){
					$ret = $row['super_name'];
					break;
				}
			}
		}
		return $ret;
	}

	function fetch_report_data()
    {
		global $FANNIE_ARCHIVE_DB, $FANNIE_OP_DB, $FANNIE_COOP_ID;
        $dbc = FannieDB::get($FANNIE_OP_DB);
		$d1 = FormLib::get_form_value('date1',date('Y-m-d'));
		$d2 = FormLib::get_form_value('date2',date('Y-m-d'));
		$dept = $_REQUEST['dept'];

		$this->report_desc[] = sprintf("<H3 style='margin-bottom:0;'>Store Summary: %s </H3>",
														($d1 == $d2) ? "For $d1" : "From $d1 to $d2");
		if ($dept == 0) {
			$this->report_desc[] = "<p>Using the department# the upc was assigned to at time of sale</p>";
		}
		elseif ($dept == 1) {
			$this->report_desc[] = "<p>Using the department# the upc is assigned to now</p>";
		}
		else {
			// fetch_report_data() will abort on this condition.
			$this->report_desc[] = "<p>Department#-source choice >${dept}< not known.</p>";
		}
		$this->report_desc[] = "<p>Note: For items where cost is not recorded the margin in the deptMargin table is relied on.</p>";

		if ( 1 ) {
			$dlog = DTransactionsModel::selectDtrans($d1,$d2);
			$datestamp = $dbc->identifier_escape('datetime');
		} else {
			$dlog = DTransactionsModel::selectDlog($d1,$d2);
			$datestamp = $dbc->identifier_escape('tdate');
		}
		//$this->report_desc[] = "dlog: $dlog   datestamp: $datestamp";
		/* dlog is probably more efficient. But it doesn't work at this point.
		 * 22Jul13 Needs t.cost, which is not in the dlog views now,
		 *  but I think Andy has changed that recently.
		 *$dlog = selectDlog($d1,$d2);
		 *$this->report_desc[] = "dlog: $dlog";
		*/
//		$dbc->logger("dlog: $dlog");

		if ( isset($FANNIE_COOP_ID) && $FANNIE_COOP_ID == 'WEFC_Toronto' )
			$shrinkageUsers = " AND t.card_no not between 99990 and 99998";
		else
			$shrinkageUsers = "";

		// The eventual return value.
		$data = array();

		$taxNames = array(0 => '');
		$tQ = $dbc->prepare_statement("SELECT id, rate, description FROM {$FANNIE_OP_DB}.taxrates WHERE id > 0 ORDER BY id");
		$tR = $dbc->exec_statement($tQ);
		// Try generating code in this loop for use in SELECT and reporting.
		//  See SalesAndTaxTodayReport.php
		while ( $trow = $dbc->fetch_array($tR) ) {
			$taxNames[$trow['id']] = $trow['description'];
		}

		/* SHOW CREATE VIEW 
		`dlog201304` AS select `d`.`datetime` AS `tdate`,
		`d`.`register_no` AS `register_no`,
		`d`.`emp_no` AS `emp_no`,
		`d`.`trans_no` AS `trans_no`,
		`d`.`upc` AS `upc`,
		(case when ((`d`.`trans_subtype` in ('CP','IC')) or (`d`.`upc` like '%000000052')) then 'T' when (`d`.`upc` = 'DISCOUNT') then 'S' else `d`.`trans_type` end) AS `trans_type`,
		(case when (`d`.`upc` = 'MAD Coupon') then 'MA' else (case when (`d`.`upc` like '%00000000052') then 'RR' else `d`.`trans_subtype` end) end) AS `trans_subtype`,
		`d`.`trans_status` AS `trans_status`,
		`d`.`department` AS `department`,
		`d`.`quantity` AS `quantity`,
		`d`.`unitPrice` AS `unitPrice`,
		`d`.`total` AS `total`,
		`d`.`tax` AS `tax`,
		`d`.`foodstamp` AS `foodstamp`,
		`d`.`ItemQtty` AS `itemQtty`,
		`d`.`memType` AS `memType`,
		`d`.`staff` AS `staff`,
		`d`.`numflag` AS `numflag`,
		`d`.`charflag` AS `charflag`,
		`d`.`card_no` AS `card_no`,
		`d`.`trans_id` AS `trans_id`,
		concat(cast(`d`.`emp_no` as char charset latin1),'-',cast(`d`.`register_no` as char charset latin1),'-',cast(`d`.`trans_no` as char charset latin1)) AS `trans_num`
		FROM `transArchive201304` `d`
		WHERE ((`d`.`trans_status` not in ('D','X','Z')) and (`d`.`emp_no` not in (9999,56)) and (`d`.`register_no` <> 99))

						Removed:
		*/
		/* Using department settings at the time of sale.
		 * I.e. The department# from the transaction.
		 *  If that department# no longer exists or is different then the report will be wrong.
		 *  This does not use a departments table contemporary with the transactions.
		 * [0]Dept_name [1]Cost, [2]HST, [3]GST, [4]Sales, [x]Qty, [x]superID, [x]super_name
		*/
		if ($dept == 0){
			// Change varname to sales or totals
			$costs = "SELECT
						d.Dept_name dname,
						sum(CASE WHEN t.trans_type = 'I' THEN t.cost WHEN t.trans_type = 'D' AND m.margin > 0.00 THEN t.total - (t.total * m.margin) END) costs,
						sum(CASE WHEN t.tax = 1 THEN t.total * x.rate ELSE 0 END) taxes1,
						sum(CASE WHEN t.tax = 2 THEN t.total * x.rate ELSE 0 END) taxes2,
						sum(t.total) sales,
						sum(t.quantity) qty,
						s.superID sid,
						s.super_name sname
					FROM
						$dlog AS t LEFT JOIN
						{$FANNIE_OP_DB}.departments AS d ON d.dept_no=t.department LEFT JOIN
						{$FANNIE_OP_DB}.MasterSuperDepts AS s ON t.department=s.dept_ID LEFT JOIN
						{$FANNIE_OP_DB}.deptMargin AS m ON t.department=m.dept_id LEFT JOIN
						{$FANNIE_OP_DB}.taxrates AS x ON t.tax=x.id
					WHERE 
						($datestamp BETWEEN ? AND ?)
						AND (s.superID > 0 OR s.superID IS NULL) 
						AND t.trans_type in ('I','D')
						AND t.trans_status not in ('D','X','Z')
						AND t.emp_no not in (9999){$shrinkageUsers}
						AND t.register_no != 99
						AND t.upc != 'DISCOUNT'
						AND t.`trans_subtype` not in ('CP','IC')
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
					CASE WHEN e.dept_name IS NULL THEN d.dept_name ELSE e.dept_name END dname,
					sum(CASE WHEN t.trans_type = 'I' THEN t.cost WHEN t.trans_type = 'D' AND m.margin > 0.00 THEN t.total - (t.total * m.margin) END) costs,
					sum(CASE WHEN t.tax = 1 THEN t.total * x.rate ELSE 0 END) taxes1,
					sum(CASE WHEN t.tax = 2 THEN t.total * x.rate ELSE 0 END) taxes2,
					sum(t.total) sales,
					sum(t.quantity) qty,
					CASE WHEN s.superID IS NULL THEN r.superID ELSE s.superID END sid,
					CASE WHEN s.super_name IS NULL THEN r.super_name ELSE s.super_name END sname
				FROM
					$dlog AS t LEFT JOIN
					products AS p ON t.upc=p.upc LEFT JOIN
					departments AS d ON d.dept_no=t.department LEFT JOIN
					departments AS e ON p.department=e.dept_no LEFT JOIN
					MasterSuperDepts AS s ON s.dept_ID=p.department LEFT JOIN
					MasterSuperDepts AS r ON r.dept_ID=t.department LEFT JOIN
					deptMargin AS m ON p.department=m.dept_id LEFT JOIN
					{$FANNIE_OP_DB}.taxrates AS x ON t.tax=x.id
				WHERE
					($datestamp BETWEEN ? AND ?)
					AND (s.superID > 0 OR (s.superID IS NULL AND r.superID > 0)
						OR (s.superID IS NULL AND r.superID IS NULL))
					AND t.trans_type in ('I','D')
					AND t.trans_status not in ('D','X','Z')
					AND t.emp_no not in (9999){$shrinkageUsers}
					AND t.register_no != 99
					AND t.upc != 'DISCOUNT'
					AND t.`trans_subtype` not in ('CP','IC')
				GROUP BY
					CASE WHEN s.superID IS NULL THEN r.superID ELSE s.superID end,
					CASE WHEN s.super_name IS NULL THEN r.super_name ELSE s.super_name END,
					CASE WHEN e.dept_name IS NULL THEN d.dept_name ELSE e.dept_name end,
					CASE WHEN e.dept_no IS NULL THEN d.dept_no ELSE e.dept_no end
				ORDER BY
					CASE WHEN s.superID IS NULL THEN r.superID ELSE s.superID end,
					CASE WHEN e.dept_no IS NULL THEN d.dept_no ELSE e.dept_no end";
		}
		else {
			// Abort. The message is in the heading.
			return $data;
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
			// GROUP BY produces 1 row per dept. Values are sums.
			$supers[$curSuper]['depts'][] = array('name'=>$row['dname'],
				'qty'=>$row['qty'],
				'costs'=>$row['costs'],
				'sales'=>$row['sales'],
				'taxes1'=>$row['taxes1'],
				'taxes2'=>$row['taxes2']);

			$this->grandCostsTotal += $row['costs'];
			$this->grandSalesTotal += $row['sales'];
			$this->grandTax1Total += $row['taxes1'];
			$this->grandTax2Total += $row['taxes2'];
		}

		foreach($supers as $s){
			if ($s['sales']==0) continue;

			$superCostsSum = $s['costs'];
			$superSalesSum = $s['sales'];
			$report = array();
			foreach($s['depts'] as $d){
				$record = array(
					$d['name'],
					sprintf('%.2f',$d['qty']),
					sprintf('$%.2f',$d['costs']),
					sprintf('%.2f %%',($d['costs'] / $this->grandCostsTotal) * 100),
					sprintf('%.2f %%',($d['costs'] / $superCostsSum) * 100),
					sprintf('$%.2f',$d['sales']),
					sprintf('%.2f %%',($d['sales'] / $this->grandSalesTotal) * 100),
					sprintf('%.2f %%',($d['sales'] / $superSalesSum) * 100),
					($d['sales']>0 && $d['costs']>0)?
						number_format(((($d['sales'] - $d['costs']) / $d['sales']) * 100),2).' %':
						'n/a',
					sprintf('%.2f',$d['taxes2']),
					sprintf('%.2f',$d['taxes1'])
				);
				$report[] = $record;
			}

			$data[] = $report;

		}

		// The summary of grand totals proportions.

		$report = array();

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
		$report[] = $record;

		// Grand totals
		$record = array(
			'WHOLE STORE',
			'',
			'$ '.number_format($this->grandCostsTotal,2),
			'',
			'',
			'$ '.number_format($this->grandSalesTotal,2),
			'$ '.number_format(($this->grandSalesTotal - $this->grandCostsTotal),2),
			'',
			number_format(((($this->grandSalesTotal - $this->grandCostsTotal) / $this->grandSalesTotal) * 100),2).' %',
			'$ '.number_format($this->grandTax2Total,2),
			'$ '.number_format($this->grandTax1Total,2)
		);
		$report[] = $record;

		$this->summary_data[] = $report;

		$this->grandTTL = $grandTotal;
		return $data;

	// fetch_report_data()
	}

	function calculate_footers($data){

		$sumQty = 0.0;
		$sumSales = 0.0;
		$sumCosts = 0.0;
		$sumTax1 = 0.0;
		$sumTax2 = 0.0;
		foreach($data as $row){
			$sumQty += $row[1];
			$sumCosts += preg_replace("/[^.0-9]/",'',$row[2]);
			$sumSales += preg_replace("/[^.0-9]/",'',$row[5]);
			$sumTax2 += preg_replace("/[^.0-9]/",'',$row[9]);
			$sumTax1 += preg_replace("/[^.0-9]/",'',$row[10]);
		}
		return array( $this->get_superdept_name($data[0][0]),
			$sumQty,
			sprintf('$ %s',number_format($sumCosts,2)),
			sprintf('%.2f %%',($sumCosts/$this->grandCostsTotal)*100),
			null,
			sprintf('$ %s',number_format($sumSales,2)),
			sprintf('%.2f %%',($sumSales/$this->grandSalesTotal)*100),
			null,
			($sumSales>0.0 && $sumCosts>0.0)?
				number_format(((($sumSales - $sumCosts) / $sumSales) * 100),2).' %':
				'n/a',
			sprintf('$ %s',number_format($sumTax2,2)),
			sprintf('$ %s',number_format($sumTax1,2))
		);

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
		<form action=StoreSummaryReport.php method=get>
		<table cellspacing=4 cellpadding=4 border=0>
		<tr>
		<th>Start Date</th>
		<td><input type=text id=date1 name=date1 onclick="showCalendarControl(this);" value="<?php echo $lastMonday; ?>" /></td>
		<td rowspan="2">
		<?php echo FormLib::date_range_picker(); ?>
		</td>
		</tr><tr>
		<th>End Date</th>
		<td><input type=text id=date2 name=date2 onclick="showCalendarControl(this);" value="<?php echo $lastSunday; ?>" /></td>
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

	// form_content()
	}

// StoreSummaryReport
}

FannieDispatch::conditionalExec(false);

?>
