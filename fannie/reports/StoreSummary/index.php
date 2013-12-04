<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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

	* 03Apr13 AT Added prepared statements. Used SQLManger::identifer_escape
		     rather than direct backticks for datestamp field variable
	* 15Feb13 EL + For trans_type D approximate cost as (total - (total*dept_margin)).
	* 27Jan13 Eric Lee Based on GeneralCosts.
	*         N.B. For trans_type D approximate cost as (total / dept markup).
	*         To exclude Cancelled transactions (X). What are D and Z?
  *						AND t.trans_status not in ('D','X','Z')
	*         To exclude Dummy/Training transactions
	*						AND t.emp_no not in (7000, 9999)
	*         Display: Costs, Sales, Tax1 (HST), Tax2 (GST) in same table.
	*         Might want to try to generate tax-related code from taxNames[]
	*          so the program could be more portable.

	* 25Jan13 EL Add today, yesterday, this week, last week, this month, last month options.
	*  2Jan13 Eric Lee Report of Costs, based on GeneralSales/index.php
	* + Base on a dtrans table
	* + Use variable for name of datestamp field.
	* + Exclude what the dlog view excludes
	* + For trans_type D approximate cost as (total / dept markup).
	* + Page heading.
	* + Format report as a single table. Other HTML adjustments.
	* I'm not sure the dept==1 flavour gets markup right when department has changed
	*  since the transaction.

*/
header("Location: StoreSummaryReport.php");
exit;

include('../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
$dbc = FannieDB::get($FANNIE_OP_DB);

/* This program has two modes:
 * 1. Display the form for specifying the report.
 *    Test: the submit button has not been clicked so $_REQUEST['submit'] is not set.
 * 2. Prepare and display the report.
 *    Test: the submit button has been clicked so $_REQUEST['submit'] is set.
*/
if (isset($_REQUEST['submit'])){

	$d1 = $_REQUEST['date1'];
	$d2 = $_REQUEST['date2'];
	$dept = $_REQUEST['dept'];

	if ( isset($_REQUEST['other_dates']) ) {
		switch ($_REQUEST['other_dates']) {
			case 'today':
				$d1 = date("Y-m-d");
				$d2 = $d1;
				break;
			case 'yesterday':
				$d1 = date("Y-m-d", strtotime('yesterday'));
				$d2 = $d1;
				break;
			case 'this_week':
				$d1 = date("Y-m-d", strtotime('last monday'));
				$d2 = date("Y-m-d");
				break;
			case 'last_week':
				$d1 = date("Y-m-d", strtotime('last monday - 7 days'));
				$d2 = date("Y-m-d", strtotime('last sunday'));
				break;
			case 'this_month':
				$d1 = date("Y-m-d", strtotime('first day of this month'));
				$d2 = date("Y-m-d");
				break;
			case 'last_month':
				$d1 = date("Y-m-d", strtotime('first day of last month'));
				$d2 = date("Y-m-d", strtotime('last day of last month'));
				break;
		}
	}

	$dlog = DTransactionsModel::selectDtrans($d1,$d2);
	$datestamp = $dbc->identifier_escape('datetime');

	if (isset($_REQUEST['excel'])){
		header("Content-Disposition: inline; filename=costs_{$d1}_{$d2}.xls");
		header("Content-type: application/vnd.ms-excel; name='excel'");
	}
	else {
		printf("<H3 style='margin-bottom:0;'>Store Summary: %s </H3>\n", ($d1 == $d2) ? "For $d1" : "From $d1 to $d2");
		if ($dept == 0) {
			echo "Using the department# the upc was assigned to at time of sale\n";
		}
		elseif ($dept == 1) {
			echo "Using the department# the upc is assigned to now\n";
		}
		else {
			echo "Department#-source choice not known.\n";
		}
		printf("<br /><a href=index.php?date1=%s&date2=%s&dept=%s&submit=yes&excel=yes>Save to Excel</a>",
			$d1,$d2,$dept);
	}

	$taxNames = array(0 => '');
	$taxRates = array(0 => 0);
	$tQ = $dbc->prepare_statement("SELECT id, rate, description FROM core_op.taxrates WHERE id > 0 ORDER BY id");
	$tR = $dbc->exec_statement($tQ);
	while ( $trow = $dbc->fetch_array($tR) ) {
		$taxNames[$trow['id']] = $trow['description'];
		$taxRates[$trow['id']] = $trow['rate'];
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
					departments AS d ON d.dept_no=t.department LEFT JOIN
					MasterSuperDepts AS s ON t.department=s.dept_ID LEFT JOIN
					deptMargin AS m ON t.department=m.dept_id LEFT JOIN
					core_op.taxrates AS x ON t.tax=x.id
				WHERE 
					($datestamp BETWEEN ? AND ?)
					AND (s.superID > 0 OR s.superID IS NULL) 
					AND t.trans_type in ('I','D')
					AND t.trans_status not in ('D','X','Z')
					AND t.emp_no not in (7000, 9999)
					AND t.register_no != 99
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
				core_op.taxrates AS x ON t.tax=x.id
			WHERE
				($datestamp BETWEEN ? AND ?)
				AND (s.superID > 0 OR (s.superID IS NULL AND r.superID > 0)
					OR (s.superID IS NULL AND r.superID IS NULL))
				AND t.trans_type in ('I','D')
				AND t.trans_status not in ('D','X','Z')
				AND t.emp_no not in (7000, 9999)
				AND t.register_no != 99
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
		print "<br />Form variable 'dept' value >{$dept}< is unknown.";
		exit;
	}

	// Array in which totals used in the report are accumulated.
	$supers = array();

	$costsP = $dbc->prepare_statement($costs);
	$costArgs = array($d1.' 00:00:00', $d2.' 23:59:59');
	$costsR = $dbc->exec_statement($costsP, $costArgs);
	
	$curSuper = 0;
	$grandTotal = 0;

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

		$grandCostsTotal += $row['costs'];
		$grandSalesTotal += $row['sales'];
		$grandTax1Total += $row['taxes1'];
		$grandTax2Total += $row['taxes2'];
	}

	$superCount = 0;
	foreach($supers as $s){
		if ($s['sales']==0) continue;
		$superCount++;
		if ( $superCount == 1) {
			echo "<table border=1>\n";
			$headings = "<tr align=center bgcolor='FFFF99'>
			<th>&nbsp;</th>
			<th>Qty</th>
			<th>Costs</th>
			<th>% Costs</th>
			<th>DeptC %</th>
			<th>Sales</th>
			<th>% Sales</th>
			<th>DeptS %</th>
			<th>{$taxNames['2']}</th>
			<th>{$taxNames['1']}</th>
			</tr>";
			echo "$headings\n";
		} else {
			echo "<tr><th colspan='99' style='color:white;'>Blank</th></tr>\n";
			echo "$headings\n";
			//echo "<tr align=center bgcolor='FFFF99'><th>&nbsp;</th><th>Taxes</th><th>Qty</th><th>% Taxes</th><th>Dept %</th></tr>\n";
		}
		$superCostsSum = $s['costs'];
		$superSalesSum = $s['sales'];
//		$superTaxes1Sum = $s['taxes1'];
//		$superTaxes2Sum = $s['taxes2'];
		$deptCount = 0;
		foreach($s['depts'] as $d){
			if ( ++$deptCount > 30 ) {
				echo "$headings\n";
				$deptCount = 0;
			}
			printf("<tr align=right><td >%s</td><td >%.2f</td>
			<td >\$%.2f</td><td >%.2f %%</td><td >%.2f %%</td>
			<td >\$%.2f</td><td >%.2f %%</td><td >%.2f %%</td>
			<td >%.2f</td><td >%.2f</td>
			</tr>\n",
				$d['name'],
				$d['qty'],
				$d['costs'],
				$d['costs'] / $grandCostsTotal * 100,
				$d['costs'] / $superCostsSum * 100,
				$d['sales'],
				$d['sales'] / $grandSalesTotal * 100,
				$d['sales'] / $superSalesSum * 100,
				$d['taxes2'],
				$d['taxes1']);
		}

		if ( count($s['depts']) > 20 ) {
			echo "$headings\n";
		}
		// Footer of Totals for SuperDept
		printf("<tr border = 1 align=right bgcolor=#ffff99>
		<th>%s</th>
		<th>%s</th>
		<th>\$%s</th>
		<th>%.2f %%</th>
		<td>%s</td>
		<th>\$%s</th>
		<th>%.2f %%</th>
		<td>%s</td>
		<th>\$%s</th>
		<th>\$%s</th>
		</tr>\n",
			$s['name'],
			number_format($s['qty'],2),
			number_format($s['costs'],2),
			$s['costs']/$grandCostsTotal * 100,
			'&nbsp;',
			number_format($s['sales'],2),
			$s['sales']/$grandSalesTotal * 100,
			'&nbsp;',
			number_format($s['taxes2'],2),
			number_format($s['taxes1'],2));

		/* With 0.2f
		printf("<tr border = 1 align=right bgcolor=#ffff99>
		<th>%s</th>
		<th>%.2f</th>
		<th>\$%.2f</th>
		<th>%.2f %%</th>
		<td>%s</td>
		<th>\$%.2f</th>
		<th>%.2f %%</th>
		<td>%s</td>
		<th>\$%.2f</th>
		<th>\$%.2f</th>
		</tr>\n",
			$s['name'],
			$s['qty'],
			$s['costs'],
			$s['costs']/$grandCostsTotal * 100,
			'&nbsp;',
			$s['sales'],
			$s['sales']/$grandSalesTotal * 100,
			'&nbsp;',
			$s['taxes2'],
			$s['taxes1']);
	*/

	}

		// Whole-store totals
			$headingsW = "<tr align=center bgcolor='FFFF99'>
			<th>&nbsp;</th>
			<th>&nbsp;</th>
			<th>Costs</th>
			<th>&nbsp;</th>
			<th>&nbsp;</th>
			<th>Sales</th>
			<th>Profit</th>
			<th>Margin %</th>
			<th>{$taxNames['2']}</th>
			<th>{$taxNames['1']}</th>
			</tr>\n";
		echo "<tr><th colspan='99' style='color:white;'>Blank</th></tr>\n";
		echo "$headingsW\n";
		printf("<tr border = 1 align=right bgcolor=#ffff99>
		<th>%s</th>
		<th>%s</th>
		<th>\$%s</th>
		<th>%s</th>
		<td>%s</td>
		<th>\$%s</th>
		<th>\$%s</th>
		<th>%.2f %%</th>
		<th>\$%s</th>
		<th>\$%s</th>
		</tr>\n",
			'WHOLE STORE',
			'&nbsp;',
			number_format($grandCostsTotal,2),
			'&nbsp;',
			'&nbsp;',
			number_format($grandSalesTotal,2),
			number_format(($grandSalesTotal - $grandCostsTotal),2),
			((($grandSalesTotal - $grandCostsTotal) / $grandSalesTotal) * 100),
			number_format($grandTax2Total,2),
			number_format($grandTax1Total,2));
			
	echo "</table><br />";

	/*
	printf("<br /><b>Total Sales: </b>\$%.2f\n",$grandSalesTotal);
	printf("<br /><b>Total Costs: </b>\$%.2f\n",$grandCostsTotal);
	printf("<br /><b>Gross Profit: </b>\$%.2f\n", ($grandSalesTotal - $grandCostsTotal));

	printf("<br /><br /><b>Total {$taxNames['2']}: </b>\$%.2f\n",$grandTax2Total);
	printf("<br /><b>Total {$taxNames['1']}: </b>\$%.2f\n",$grandTax1Total);
	printf("<br /><b>Total Taxes: </b>\$%.2f\n", ($grandTax1Total + $grandTax2Total));
	*/

}
// Form for specifying the report.
else {

$page_title = "Fannie : Store Summary Report";
$header = "Store Summary Report";
include($FANNIE_ROOT.'src/header.html');
$lastMonday = "";
$lastSunday = "";

/* Default date range is the most recent complete Mon-Sun week,
 *  with calculation beginning yesterday.
 *  If today is Friday the 25th the range is 14th to 20th.
 *  If today is Monday the 28th the range is 21st to 27th.
*/
$ts = mktime(0,0,0,date("n"),date("j")-1,date("Y"));
while($lastMonday == "" || $lastSunday == ""){
	if (date("w",$ts) == 1 && $lastSunday != "")
		$lastMonday = date("Y-m-d",$ts);
	elseif(date("w",$ts) == 0)
		$lastSunday = date("Y-m-d",$ts);
	$ts = mktime(0,0,0,date("n",$ts),date("j",$ts)-1,date("Y",$ts));	
}
?>
<script type="text/javascript"
	src="<?php echo $FANNIE_URL; ?>src/CalendarControl.js">
</script>
<form action=index.php method=get>
<style type="text/css">
/* This makes the input and label look like they have the same baseline. */
input[type="radio"] ,
input[type="checkbox"] {
	height: 8px;
}
</style>
<table cellspacing=4 cellpadding=4 border='0'>
<tr style='vertical-align:top;'>
<td>
<table cellspacing='4' cellpadding='4' border='0'>
<tr>
<th>Start Date</th>
<td><input type=text name='date1' onclick="showCalendarControl(this);" value="<?php echo $lastMonday; ?>" />
<tr>
<th>End Date</th>
<td>
<input type=text name='date2' onclick="showCalendarControl(this);" value="<?php echo $lastSunday; ?>" />
</td>
<tr>
<th></th>
<td>
<input id='od01' type='radio' name='other_dates' value='' checked='1' > Start - End Dates
</td>
</table>
</td>
<td rowspan='1'>
<fieldset style='border:330066;'>
<legend>Other dates</legend>
<table style='margin: 0em 0em 0em 0em;'>
<tr style='vertical-align:top;'><td style='margin: 0em 1.0em 0em 0em;'>
<input id='od10'  type='radio' name='other_dates' value='today'> Today</br >
<input id='od11'  type='radio' name='other_dates' value='this_week'> This week</br >
<input id='od12'  type='radio' name='other_dates' value='this_month'> This month</br >
</td>
<td rowspan='1'>
<input id='od20' type='radio' name='other_dates' value='yesterday'> Yesterday</br >
<input id='od21' type='radio' name='other_dates' value='last_week'> Last week</br >
<input id='od22' type='radio' name='other_dates' value='last_month'> Last month</br >
</td>
</tr>
</table>
</fieldset>
</td>
</tr>
<tr><td colspan='99'><select name='dept'>
<option value='0'>Use the department# the upc was assigned to at time of sale</option>
<option value='1'>Use the department# the upc is assigned to now</option>
</select></td>
</tr>
<tr><td>Excel <input type='checkbox' name='excel' /></td>
<td colspan='99'><input type='submit' name='submit' value="Submit" /></td>
</tr>
</table>
<script>
$('input[name="date1"]').focus(function() {
	$("#od01").click();
});
$('input[name="date2"]').focus(function() {
	$("#od01").click();
});
</script>
</form>
<?php
include($FANNIE_ROOT.'src/footer.html');
}
?>
