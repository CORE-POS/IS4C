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

include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'src/select_dlog.php');
include($FANNIE_ROOT.'classlib2.0/FannieReportPage.php');
include($FANNIE_ROOT.'classlib2.0/lib/FormLib.php');

class NonMovementReport extends FannieReportPage {

	function preprocess(){
		/**
		  Set the page header and title, enable caching
		*/
		$this->title = "Fannie: Non-Movement";
		$this->header = "Non-Movement Report";
		$this->report_cache = 'day';

		if (isset($_REQUEST['date1'])){
			/**
			  Form submission occurred

			  Change content function, turn off the menus,
			  set up headers
			*/
			$this->content_function = "report_content";
			$this->has_menus(False);
			$this->report_headers = array('UPC','Description','Dept#','Dept');
		
			/**
			  Check if a non-html format has been requested
			*/
			if (isset($_REQUEST['excel']) && $_REQUEST['excel'] == 'xls')
				$this->report_format = 'xls';
			elseif (isset($_REQUEST['excel']) && $_REQUEST['excel'] == 'csv')
				$this->report_format = 'csv';
		}
		else {
			$this->add_script("../../src/CalendarControl.js");
			$this->add_script("../../src/jquery/jquery.js");
		}

		return True;
	}

	function fetch_report_data(){
		global $dbc, $FANNIE_ARCHIVE_DB;
		$date1 = FormLib::get_form_value('date1',date('Y-m-d'));
		$date2 = FormLib::get_form_value('date2',date('Y-m-d'));
		$dept1 = FormLib::get_form_value('deptStart',0);
		$dept2 = FormLib::get_form_value('deptEnd',0);

		$tempName = "TempNoMove";
		$dlog = select_dlog($date1,$date2);
		$sumTable = $FANNIE_ARCHIVE_DB.$dbc->sep()."sumUpcSalesByDay";

		$tempQ = "CREATE TABLE $tempName (upc varchar(13))";
		$dbc->query($tempQ);

		$insQ = "INSERT INTO $tempName
			SELECT d.upc FROM $sumTable AS d
			WHERE 
			d.tdate BETWEEN '$date1 00:00:01' 
			AND '$date2 23:59:59'
			GROUP BY d.upc";
		$dbc->query($insQ);

		$query = "SELECT p.upc,p.description,d.dept_no,
			d.dept_name FROM products AS p LEFT JOIN
			departments AS d ON p.department=d.dept_no
			WHERE p.upc NOT IN (select upc FROM $tempName)
			AND p.department
			BETWEEN $dept1 AND $dept2
			ORDER BY p.upc";
		$result = $dbc->query($query);

		/**
		  Simple report
		
		  Issue a query, build array of results
		*/
		$ret = array();
		while ($row = $dbc->fetch_array($result)){
			$record = array();
			$record[] = $row[0];
			$record[] = $row[1];
			$record[] = $row[2];
			$record[] = $row[3];
			$ret[] = $record;
		}

		$dbc->query("DROP TABLE $tempName");
		return $ret;
	}
	
	function form_content(){
		global $dbc;
		$deptsQ = "select dept_no,dept_name from departments order by dept_no";
		$deptsR = $dbc->query($deptsQ);
		$deptsList = "";
		while ($deptsW = $dbc->fetch_array($deptsR))
			$deptsList .= "<option value=$deptsW[0]>$deptsW[0] $deptsW[1]</option>";
?>
<div id=main>	
<form method = "get" action="NonMovementReport.php">
	<table border="0" cellspacing="0" cellpadding="5">
		<tr> 
			<td> <p><b>Department Start</b></p>
			<p><b>End</b></p></td>
			<td> <p>
 			<select onchange="$('#deptStart').val(this.value)">
			<?php echo $deptsList ?>
			</select>
			<input type=text name=deptStart id=deptStart size=5 value=1 />
			</p>
			<p>
 			<select onchange="$('#deptEnd').val(this.value)">
			<?php echo $deptsList ?>
			</select>
			<input type=text name=deptEnd id=deptEnd size=5 value=1 />
			</p></td>

			 <td>
			<p><b>Date Start</b> </p>
		         <p><b>End</b></p>
		       </td>
		            <td>
		             <p>
		               <input type=text size=25 name=date1 onfocus="this.value='';showCalendarControl(this);">
		               </p>
		               <p>
		                <input type=text size=25 name=date2 onfocus="this.value='';showCalendarControl(this);">
		         </p>
		       </td>

		</tr>
		<tr> 
			<td><b>Excel</b>
			</td><td>
			<input type=checkbox name=excel value=xls />
			</td>
			</td>
			<td rowspan=2 colspan=2>Date format is YYYY-MM-DD</br>(e.g. 2004-04-01 = April 1, 2004)<!-- Output to CSV?</td>
		            <td><input type="checkbox" name="csv" value="yes">
			                        yes --> </td>
				</tr>
		<tr>
			<td>
			<b>Netted</td><td>
			<input type=checkbox name=netted />
		</tr>

		<tr> 
			<td> <input type=submit name=submit value="Submit"> </td>
			<td> <input type=reset name=reset value="Start Over"> </td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
	</table>
</form>
</div>
<?php
	}
}

$obj = new NonMovementReport();
$obj->draw_page();
?>
