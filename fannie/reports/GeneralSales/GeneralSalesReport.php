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
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'src/select_dlog.php');
include($FANNIE_ROOT.'classlib2.0/lib/FormLib.php');
include($FANNIE_ROOT.'classlib2.0/FannieReportPage.php');

class GeneralSalesReport extends FannieReportPage {

	private $grandTTL;

	function preprocess(){
		$this->title = "Fannie : General Sales Report";
		$this->header = "General Sales Report";
		$this->report_cache = 'none';
		$this->grandTTL = 1;
		$this->multi_report_mode = True;
		$this->sortable = False;

		if (isset($_REQUEST['date1'])){
			$this->content_function = "report_content";
			$this->has_menus(False);
			$this->report_headers = array('','Sales','Quantity','% Sales','Dept %');

			/**
			  Check if a non-html format has been requested
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

	function fetch_report_data(){
		global $dbc, $FANNIE_ARCHIVE_DB;
		$d1 = FormLib::get_form_value('date1',date('Y-m-d'));
		$d2 = FormLib::get_form_value('date2',date('Y-m-d'));
		$dept = $_REQUEST['dept'];

		$dlog = select_dlog($d1,$d2);

		$sales = "SELECT d.Dept_name,sum(t.total),
				sum(case when unitPrice=0.01 THEN 1 else t.quantity END),
				s.superID,s.super_name
				FROM $dlog AS t LEFT JOIN departments AS d
				ON d.dept_no=t.department LEFT JOIN
				MasterSuperDepts AS s ON t.department=s.dept_ID
				WHERE 
				(tDate BETWEEN ? AND ?)
				AND (s.superID > 0 OR s.superID IS NULL) 
				AND (t.trans_type = 'I' or t.trans_type = 'D')
				GROUP BY s.superID,s.super_name,d.dept_name,t.department
				ORDER BY s.superID,t.department";
		if ($dept == 1){
			$sales = "SELECT CASE WHEN e.dept_name IS NULL THEN d.dept_name ELSE e.dept_name end,
				sum(t.total),sum(CASE WHEN unitPrice=0.01 then 1 else t.quantity END),
				CASE WHEN s.superID IS NULL THEN r.superID ELSE s.superID end,
				CASE WHEN s.super_name IS NULL THEN r.super_name ELSE s.super_name END
				FROM $dlog AS t LEFT JOIN
				products AS p ON t.upc=p.upc LEFT JOIN
				departments AS d ON d.dept_no=t.department LEFT JOIN
				departments AS e ON p.department=e.dept_no LEFT JOIN
				MasterSuperDepts AS s ON s.dept_ID=p.department LEFT JOIN
				MasterSuperDepts AS r ON r.dept_ID=t.department
				WHERE
				(tDate BETWEEN ? AND ?)
				AND (t.trans_type = 'I' or t.trans_type = 'D')
				AND (s.superID > 0 OR (s.superID IS NULL AND r.superID > 0)
				OR (s.superID IS NULL AND r.superID IS NULL))
				GROUP BY
				CASE WHEN s.superID IS NULL THEN r.superID ELSE s.superID end,
				CASE WHEN s.super_name IS NULL THEN r.super_name ELSE s.super_name END,
				CASE WHEN e.dept_name IS NULL THEN d.dept_name ELSE e.dept_name end,
				CASE WHEN e.dept_no IS NULL THEN d.dept_no ELSE e.dept_no end
				ORDER BY
				CASE WHEN s.superID IS NULL THEN r.superID ELSE s.superID end,
				CASE WHEN e.dept_no IS NULL THEN d.dept_no ELSE e.dept_no end";
		}
		$supers = array();
		$prep = $dbc->prepare_statement($sales);
		$salesR = $dbc->exec_statement($prep,array($d1.' 00:00:00',$d2.' 23:59:59'));
	
		$curSuper = 0;
		$grandTotal = 0;
		while($row = $dbc->fetch_row($salesR)){
			if ($curSuper != $row[3]){
				$curSuper = $row[3];
			}
			if (!isset($supers[$curSuper]))
				$supers[$curSuper] = array('sales'=>0.0,'qty'=>0.0,'name'=>$row[4],'depts'=>array());
			$supers[$curSuper]['sales'] += $row[1];
			$supers[$curSuper]['qty'] += $row[2];
			$supers[$curSuper]['depts'][] = array('name'=>$row[0],'sales'=>$row[1],'qty'=>$row[2]);
			$grandTotal += $row[1];
		}

		$data = array();
		foreach($supers as $s){
			if ($s['sales']==0) continue;
			$superSum = $s['sales'];
			$report = array();
			foreach($s['depts'] as $d){
				$record = array(
					$d['name'],
					sprintf('%.2f',$d['sales']),
					sprintf('%.2f',$d['qty']),
					sprintf('%.2f',($d['sales'] / $grandTotal) * 100),
					sprintf('%.2f',($d['sales'] / $superSum) * 100)
				);
				$report[] = $record;
			}

			$data[] = $report;

			/*
			printf("<tr border = 1 align=right bgcolor=#ffff99><th>%s</th><th>\$%.2f</th><th>%.2f</th>
				<th>%.2f %%</th><td>&nbsp;</td></tr>\n",
				$s['name'],$s['sales'],$s['qty'],$s['sales']/$grandTotal * 100);
			*/
		}

		$this->grandTTL = $grandTotal;
		return $data;
	}

	function calculate_footers($data){
		$sumQty = 0.0;
		$sumSales = 0.0;
		foreach($data as $row){
			$sumQty += $row[2];
			$sumSales += $row[1];
		}
		return array(null,$sumSales,$sumQty,sprintf('%.2f',($sumSales/$this->grandTTL)*100),null);
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
		<form action=GeneralSalesReport.php method=get>
		<table cellspacing=4 cellpadding=4>
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
		<td>Excel <input type=checkbox name=excel /></td>
		<td><input type=submit name=submit value="Submit" /></td>
		</tr>
		</table>
		</form>
		<?php
	}

}

$obj = new GeneralSalesReport();
$obj->draw_page();
?>
