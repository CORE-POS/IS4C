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

class DepartmentMovementReport extends FannieReportPage {

	function preprocess(){
		/**
		  Set the page header and title, enable caching
		*/
		$this->report_cache = 'day';
		$this->title = "Fannie : Department Movement";
		$this->header = "Department Movement";

		if (isset($_REQUEST['date1'])){
			/**
			  Form submission occurred

			  Change content function, turn off the menus,
			  set up headers
			*/
			$this->content_function = "report_content";
			$this->has_menus(False);
		
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

	/**
	  Add a javascript function for the form
	  This could probably be re-done in jQuery and
	  just inlined directly into the form
	*/
	function javascript_content(){
		if ($this->content_function == "form_content"){
		ob_start();
		?>
		function swap(src,dst){
			var val = document.getElementById(src).value;
			document.getElementById(dst).value = val;
		}
		<?php
		$js = ob_get_contents();
		ob_end_clean();
		return $js;
		}
	}

	/**
	  Lots of options on this report.
	*/
	function fetch_report_data(){
		global $dbc, $FANNIE_ARCHIVE_DB;
		$date1 = FormLib::get_form_value('date1',date('Y-m-d'));
		$date2 = FormLib::get_form_value('date2',date('Y-m-d'));
		$deptStart = FormLib::get_form_value('deptStart','');
		$deptEnd = FormLib::get_form_value('deptEnd','');
		$buyer = FormLib::get_form_value('buyer','');
		$groupby = FormLib::get_form_value('sort','PLU');

		/**
		  Build a WHERE condition for later.
		  Superdepartment (buyer) takes precedence over
		  department and negative values have special
		  meaning
		*/
		$filter_condition = sprintf("d.dept_no BETWEEN %d AND %d",$deptStart,$deptEnd);
		if ($buyer !== "" && $buyer > 0)
			$filter_condition = sprintf("s.superID=%d",$buyer);
		elseif ($buyer !== "" && $buyer == -1)
			$filter_condition = "1=1";
		elseif ($buyer !== "" && $buyer == -2)
			$filter_condition = "s.superID<>0";

		/**
		  Select a summary table. For UPC results, per-unique-ring
		  summary is needed. For date/dept/weekday results the
		  per-department summary is fine (and a smaller table)
		*/
		$dlog = select_dlog($date1,$date2);
		$sumTable = $FANNIE_ARCHIVE_DB.$dbc->sep()."sumRingSalesByDay";
		if (substr($dlog,-4)=="dlog")
			$sumTable = $FANNIE_ARCHIVE_DB.$dbc->sep()."vRingSalesToday";
		if ($groupby != "PLU"){
			$sumTable = $FANNIE_ARCHIVE_DB.$dbc->sep()."sumDeptSalesByDay";
			if (substr($dlog,-4)=="dlog")
				$sumTable = $FANNIE_ARCHIVE_DB.$dbc->sep()."vDeptSalesToday";
		}

		/**
		  Build an appropriate query depending on the grouping option
		*/
		$query = "";
		switch($groupby){
		case 'PLU':
			$query = "SELECT t.upc,p.description, 
				  SUM(t.quantity) as qty,
				  SUM(t.total) AS total,
				  d.dept_no,d.dept_name,s.superID,x.distributor
				  FROM $sumTable as t LEFT JOIN products as p on t.upc = p.upc
				  LEFT JOIN departments as d on d.dept_no = t.dept 
				  LEFT JOIN superdepts AS s ON t.dept = s.dept_ID
				  LEFT JOIN prodExtra as x on t.upc = x.upc
				  WHERE $filter_condition
				  AND tdate >= '$date1 00:00:00' AND tdate <= '$date2 23:59:59' 
				  GROUP BY t.upc,p.description,
				  d.dept_no,d.dept_name,s.superID,x.distributor ORDER BY SUM(t.total) DESC";
			break;
		case 'Department':
			$query =  "SELECT t.dept_ID,d.dept_name,SUM(t.quantity) as Qty, SUM(total) as Sales 
				FROM $sumTable as t LEFT JOIN departments as d on d.dept_no=t.dept_ID 
				LEFT JOIN superdepts AS s ON s.dept_ID = t.dept_ID 
				WHERE $filter_condition
				AND tdate >= '$date1 00:00:00' AND tdate <= '$date2 23:59:59' 
				GROUP BY t.dept_ID,d.dept_name ORDER BY SUM(total) DESC";
			break;
		case 'Date':
			$query =  "SELECT year(tdate),month(tdate),day(tdate),SUM(t.quantity) as Qty, SUM(total) as Sales 
				FROM $sumTable as t LEFT JOIN departments as d on d.dept_no=t.dept_ID 
				LEFT JOIN superdepts AS s ON s.dept_ID = t.dept_ID 
				WHERE $filter_condition
				AND tdate >= '$date1 00:00:00' AND tdate <= '$date2 23:59:59' 
				GROUP BY year(tdate),month(tdate),day(tdate) 
				ORDER BY year(tdate),month(tdate),day(tdate)";
			break;
		case 'Weekday':
			$cols = $dbc->dayofweek("tdate").",CASE 
				WHEN ".$dbc->dayofweek("tdate")."=1 THEN 'Sun'
				WHEN ".$dbc->dayofweek("tdate")."=2 THEN 'Mon'
				WHEN ".$dbc->dayofweek("tdate")."=3 THEN 'Tue'
				WHEN ".$dbc->dayofweek("tdate")."=4 THEN 'Wed'
				WHEN ".$dbc->dayofweek("tdate")."=5 THEN 'Thu'
				WHEN ".$dbc->dayofweek("tdate")."=6 THEN 'Fri'
				WHEN ".$dbc->dayofweek("tdate")."=7 THEN 'Sat'
				ELSE 'Err' END";
			$query =  "SELECT $cols,SUM(t.quantity) as Qty, SUM(total) as Sales 
				FROM $sumTable as t LEFT JOIN departments as d on d.dept_no=t.dept_ID 
				LEFT JOIN superdepts AS s ON s.dept_ID = t.dept_ID 
				WHERE $filter_condition
				AND tdate >= '$date1 00:00:00' AND tdate <= '$date2 23:59:59' 
				GROUP BY $cols
				ORDER BY ".$dbc->dayofweek('tdate');
			break;
		}

		/**
		  Copy the results into an array. Date requires a
		  special case to combine year, month, and day into
		  a single field
		*/
		$result = $dbc->query($query);
		$ret = array();
		while ($row = $dbc->fetch_array($result)){
			$record = array();
			if ($groupby == "Date"){
				$record[] = $row[1]."/".$row[2]."/".$row[0];
				$record[] = $row[3];
				$record[] = $row[4];
			}
			else {
				for($i=0;$i<$dbc->num_fields($result);$i++)
					$record[] .= $row[$i];
			}
			$ret[] = $record;
		}
		return $ret;
	}
	
	/**
	  Sum the quantity and total columns for a footer,
	  but also set up headers and sorting.

	  The number of columns varies depending on which
	  data grouping the user selected. 
	*/
	function calculate_footers($data){
		// no data; don't bother
		if (empty($data))
			return array();

		/**
		  Use the width of the first record to determine
		  how the data is grouped
		*/
		switch(count($data[0])){
		case 8:
			$this->report_headers = array('UPC','Description','Qty','$',
				'Dept#','Department','Subdept','Vendor');
			$this->sort_column = 3;
			$this->sort_direction = 1;
			$sumQty = 0.0;
			$sumSales = 0.0;
			foreach($data as $row){
				$sumQty += $row[2];
				$sumSales += $row[3];
			}
			return array('Total',null,$sumQty,$sumSales,null,null,null,null);
			break;
		case 4:
			/**
			  The Department and Weekday datasets are both four
			  columns wide so I have to resort to form parameters
			*/
			if (FormLib::get_form_value('sort')=='Weekday'){
				$this->report_headers = array('Day','Day','Qty','$');
				$this->sort_column = 0;
				$this->sort_direction = 0;
			}
			else {
				$this->report_headers = array('Dept#','Department','Qty','$');
				$this->sort_column = 3;
				$this->sort_direction = 1;
			}
			$sumQty = 0.0;
			$sumSales = 0.0;
			foreach($data as $row){
				$sumQty += $row[2];
				$sumSales += $row[3];
			}
			return array('Total',null,$sumQty,$sumSales);
			break;
		case 3:
			$this->report_headers = array('Date','Qty','$');
			$sumQty = 0.0;
			$sumSales = 0.0;
			foreach($data as $row){
				$sumQty += $row[1];
				$sumSales += $row[2];
			}
			return array('Total',$sumQty,$sumSales);
			break;
		}
	}

	function form_content(){
		global $dbc;
		$deptsQ = "select dept_no,dept_name from departments order by dept_no";
		$deptsR = $dbc->query($deptsQ);
		$deptsList = "";

		$deptSubQ = "SELECT superID,super_name FROM superDeptNames
				WHERE superID <> 0 
				ORDER BY superID";
		$deptSubR = $dbc->query($deptSubQ);

		$deptSubList = "";
		while($deptSubW = $dbc->fetch_array($deptSubR)){
			$deptSubList .=" <option value=$deptSubW[0]>$deptSubW[1]</option>";
		}
		while ($deptsW = $dbc->fetch_array($deptsR))
			$deptsList .= "<option value=$deptsW[0]>$deptsW[0] $deptsW[1]</option>";
?>
<div id=main>	
<form method = "get" action="DepartmentMovementReport.php">
	<table border="0" cellspacing="0" cellpadding="5">
		<tr>
			<td><b>Select Buyer/Dept</b></td>
			<td><select id=buyer name=buyer>
			   <option value=0 >
			   <?php echo $deptSubList; ?>
			   <option value=-2 >All Retail</option>
			   <option value=-1 >All</option>
			   </select>
 			</td>
			<td><b>Send to Excel</b></td>
			<td><input type=checkbox name=excel id=excel value=1></td>
		</tr>
		<tr>
			<td colspan=5><i>Selecting a Buyer/Dept overrides Department Start/Department End, but not Date Start/End.
			To run reports for a specific department(s) leave Buyer/Dept or set it to 'blank'</i></td>
		</tr>
		<tr> 
			<td> <p><b>Department Start</b></p>
			<p><b>End</b></p></td>
			<td> <p>
 			<select id=deptStartSel onchange="swap('deptStartSel','deptStart');">
			<?php echo $deptsList ?>
			</select>
			<input type=text name=deptStart id=deptStart size=5 value=1 />
			</p>
			<p>
			<select id=deptEndSel onchange="swap('deptEndSel','deptEnd');">
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
			<td><b>Sum movement by?</b></td>
			<td> <select name="sort" size="1">
			<option>PLU</option>
			<option>Date</option>
			<option>Department</option>
			<option>Weekday</option>
			</select> </td>
			<td colspan=2>Date format is YYYY-MM-DD</br>(e.g. 2004-04-01 = April 1, 2004)
			                        </td>
				</tr>
		<tr> 
			<td> <input type=submit name=submit value="Submit"> </td>
			<td> <input type=reset name=reset value="Start Over"> </td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
	</table>
</form>
<?php
	}
}

$obj = new DepartmentMovementReport();
$obj->draw_page();
?>
