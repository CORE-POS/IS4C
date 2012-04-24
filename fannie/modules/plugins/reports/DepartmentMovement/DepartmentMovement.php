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

class DepartmentMovement extends FannieReport {

	public $description = "
	Report product sales by department.
	";

	protected $header = "Fannie : Department Movement";
	protected $title = "Department Movement";
	private $db_connection;
	private $mode;

	function preprocess(){
		$this->mode = 'form';
		if (isset($_REQUEST['submit'])){
			$this->mode = 'results';
			$this->window_dressing = False;
		}
		return True;
	}

	function body_content(){
		switch ($this->mode){
		case 'results':
			return $this->report_results();
		case 'form':
			return $this->report_form();
		}
	}

	function report_results(){
		$start_date = get_form_value('date1',date('Y-m-d'));
		$end_date = get_form_value('date2',date('Y-m-d'));
		$start_dept = get_form_value('deptStart',0);
		$end_dept = get_form_value('deptEnd',0);
		$super = get_form_value('buyer',0);
		$sort = get_form_value('sort','PLU');
		$order = get_form_value('order','total');
		$dir = get_form_value('dir','DESC');
		$otherdir = ($dir == "DESC") ? "ASC" : "DESC";
		$dlog = select_dlog($start_date, $end_date);
		
		// define query and column so printing
		// can be standardized below
		$query = "";
		$columns = array();
		$dbc = op_connect();
		switch($sort){
		default:
		case 'PLU':
			$query = "SELECT t.upc, p.description,
				SUM(case when t.trans_status in ('M') then t.itemqtty else t.quantity end) as qty,
				SUM(t.total) AS total,
				d.dept_no,d.dept_name,s.superID,x.distributor,s.super_name
				FROM $dlog AS t LEFT JOIN products AS p ON t.upc=p.upc
				LEFT JOIN departments AS d ON d.dept_no=t.department
				LEFT JOIN MasterSuperDepts AS s ON t.department=s.dept_ID
				LEFT JOIN prodExtra AS x ON t.upc=x.upc
				WHERE t.tdate BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'
				AND t.trans_type IN ('I','D','M')";

			if ($super == 0)
				$query .= " AND t.department BETWEEN $start_dept AND $end_dept ";
			elseif ($super > 0)
				$query .= " AND s.superID=$super ";
			elseif ($super == -2)
				$query .= " AND s.superID<>0 ";
	
			$query .= "GROUP BY t.upc,p.description,d.dept_no,
				d.dept_name,s.superID,x.distributor,s.super_name
				ORDER BY $order $dir";

			$columns = array(
				'UPC' => array('col'=>'t.upc'),
				'Description' => array('col'=>'p.description'),
				'Qty' => array('col'=>'qty','align'=>'right','format'=>'%.2f'),
				'Sales' => array('col'=>'total','align'=>'right','format'=>'%.2f'),
				'Dept#' => array('col'=>'d.dept_no'),
				'Dept' => array('col'=>'d.dept_name'),
				'Sub dept' => array('col'=>'s.super_name'),
				'Vendor' => array('col'=>'x.distributor')
			);				
			break;
		case 'Department':
			$query = "SELECT t.department,d.dept_name,
				SUM(case when t.trans_status in ('M') then t.itemqtty else t.quantity end) as qty,
				SUM(t.total) AS total
				FROM $dlog AS t LEFT JOIN departments AS d ON d.dept_no=t.department
				LEFT JOIN MasterSuperDepts AS s ON s.dept_ID=t.department
				WHERE t.tdate BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'
				AND t.trans_type IN ('D','I','M')";

			if ($super == 0)
				$query .= " AND t.department BETWEEN $dept_start AND $dept_end ";
			elseif ($super > 0)
				$query .= " AND s.superID=$super ";
			else if ($super == -2)
				$query .= " AND s.superID <> 0 ";

			$query .= "GROUP BY t.department, d.dept_name
				ORDER BY $order $dir";

			$columns = array(
				'Dept#' => array('col'=>'t.department'),
				'Dept' => array('col'=>'d.dept_name'),
				'Qty' => array('col'=>'qty','align'=>'right','format'=>'%.2f'),
				'Sales' => array('col'=>'total','align'=>'right','format'=>'%.2f')
			);				
			break;

		case 'Date':
			// change default sort
			if (!isset($_REQUEST['order'])){
				$order = "date_string";
				$dir = "ASC";
			}

			$query = "SELECT ".$dbc->dateymd('tdate')." as date_string,
				SUM(case when t.trans_status in ('M') then t.itemqtty else t.quantity end) as qty,
				SUM(t.total) AS total
				FROM $dlog AS t LEFT JOIN MasterSuperDepts AS s ON s.dept_ID=t.department
				WHERE t.tdate BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'
				AND t.trans_type IN ('D','I','M')";

			if ($super == 0)
				$query .= " AND t.department BETWEEN $dept_start AND $dept_end ";
			elseif ($super > 0)
				$query .= " AND s.superID=$super ";
			else if ($super == -2)
				$query .= " AND s.superID <> 0 ";

			$query .= "GROUP BY date_string
				ORDER BY $order $dir";

			$columns = array(
				'Date' => array('col'=>'date_string','date'=>'m/d/Y'),
				'Qty' => array('col'=>'qty','align'=>'right','format'=>'%.2f'),
				'Sales' => array('col'=>'total','align'=>'right','format'=>'%.2f')
			);				
			break;
		case 'Weekday':
			if (!isset($_REQUEST['order'])){
				$order = "DoW_num";
				$dir = "ASC";
			}
			$dow = "CASE "
				."WHEN ".$dbc->dayofweek('tdate')."=1 THEN 'Sun' "
				."WHEN ".$dbc->dayofweek('tdate')."=2 THEN 'Mon' "
				."WHEN ".$dbc->dayofweek('tdate')."=3 THEN 'Tue' "
				."WHEN ".$dbc->dayofweek('tdate')."=4 THEN 'Wed' "
				."WHEN ".$dbc->dayofweek('tdate')."=5 THEN 'Thu' "
				."WHEN ".$dbc->dayofweek('tdate')."=6 THEN 'Fri' "
				."WHEN ".$dbc->dayofweek('tdate')."=7 THEN 'Sat' "
				."ELSE 'Err' END";

			$query = "SELECT ".$dbc->dayofweek("tdate")." AS DoW_num,
				$dow AS DoW,
				SUM(case when t.trans_status in ('M') then t.itemqtty else t.quantity end) as qty,
				SUM(t.total) AS total,
				SUM(case when t.trans_status in ('M') then t.itemqtty else t.quantity end)
				  /COUNT(DISTINCT(".$dbc->dateymd('tdate').")) as avg_qty,
				SUM(total)/COUNT(DISTINCT(".$dbc->dateymd('tdate').")) as avg_ttl
				FROM $dlog AS t LEFT JOIN MasterSuperDepts AS s ON s.dept_ID=t.department
				WHERE t.tdate BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'
				AND t.trans_type IN ('D','I','M')";

			if ($super == 0)
				$query .= " AND t.department BETWEEN $dept_start AND $dept_end ";
			elseif ($super > 0)
				$query .= " AND s.superID=$super ";
			else if ($super == -2)
				$query .= " AND s.superID <> 0 ";

			$query .= "GROUP BY DoW_num, DoW ORDER BY $order $dir";

			$columns = array(
				'Day' => array('col'=>'DoW','sort'=>'DoW_num'),
				'Total Qty' => array('col'=>'qty','align'=>'right','format'=>'%.2f'),
				'Avg. Qty' => array('col'=>'avg_qty','align'=>'right','format'=>'%.2f'),
				'Total Sales' => array('col'=>'total','align'=>'right','format'=>'%.2f'),
				'Avg. Sales' => array('col'=>'avg_ttl','align'=>'right','format'=>'%.2f')
			);
			break;
		}

		$ret = get_sortable_table($dbc, $query, $columns, $this->module_url(), $order);
		$dbc->close();

		return $ret;
	}
	
	function report_form(){
		global $FANNIE_URL;
		$dbc = op_connect();
		$deptsQ = "select dept_no,dept_name from departments order by dept_no";
		$deptsR = $dbc->query($deptsQ);
		$deptsList = "";
		while ($deptsW = $dbc->fetch_array($deptsR)){
			$deptsList .= "<option value=$deptsW[0]>$deptsW[0] $deptsW[1]</option>";
		}

		$deptSubQ = "SELECT superID,super_name FROM superDeptNames
				WHERE superID <> 0 
				ORDER BY superID";
		$deptSubR = $dbc->query($deptSubQ);
		$deptSubList = "";
		while($deptSubW = $dbc->fetch_array($deptSubR)){
			$deptSubList .=" <option value=$deptSubW[0]>$deptSubW[1]</option>";
		}
		$dbc->close();

		ob_start();
		?>
		<div id=main>
		<?php echo $this->form_tag('get'); ?>
		<table border="0" cellspacing="0" cellpadding="5">
		<tr>
			<td><b>Select Buyer/Dept</b></td>
			<td><select id=buyer name=buyer>
				<option value=0 >
				<?php echo $deptSubList; ?>
				<option value=-2 >All Retail</option>
				<option value=-1 >All</option>
			</select></td>
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
			<td colspan=2>Date format is YYYY-MM-DD</br>(e.g. 2004-04-01 = April 1, 2004)</td>
		</tr>
		<tr> 
			<td> <input type=submit name=submit value="Submit"> </td>
			<td> <input type=reset name=reset value="Start Over"> </td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		</table>
		</form>
		<script src="<?php echo $FANNIE_URL; ?>src/CalendarControl.js"
			type="text/javascript"></script>
		<?php
		return ob_get_clean();	
	}

	function javascript_content(){
		ob_start();
		?>
		function swap(src,dst){
			var val = document.getElementById(src).value;
			document.getElementById(dst).value = val;
		}
		<?php
		return ob_get_clean();
	}
}

?>
