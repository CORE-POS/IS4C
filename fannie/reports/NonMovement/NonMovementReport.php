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
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class NonMovementReport extends FannieReportPage {

	function preprocess()
    {
		global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
		/**
		  Set the page header and title, enable caching
		*/
		$this->title = "Fannie: Non-Movement";
		$this->header = "Non-Movement Report";
		$this->report_cache = 'none';

		if (isset($_REQUEST['deleteItem'])){
			$upc = FormLib::get_form_value('deleteItem','');
			if (is_numeric($upc))
				$upc = BarcodeLib::padUPC($upc);

			$query = "DELETE FROM products WHERE upc=?";
			$queryP = $dbc->prepare_statement($query);
			$dbc->exec_statement($queryP, array($upc));

			$query = "DELETE FROM productUser WHERE upc=?";
			$queryP = $dbc->prepare_statement($query);
			$dbc->exec_statement($queryP, array($upc));

			$query = "DELETE FROM prodExtra WHERE upc=?";
			$queryP = $dbc->prepare_statement($query);
			$dbc->exec_statement($queryP, array($upc));

			echo 'Deleted';
			exit;
		}

		if (isset($_REQUEST['date1'])){
			/**
			  Form submission occurred

			  Change content function, turn off the menus,
			  set up headers
			*/
			$this->content_function = "report_content";
			$this->has_menus(False);
			$this->report_headers = array('UPC','Description','Dept#','Dept','');
		
			/**
			  Check if a non-html format has been requested
			*/
			if (isset($_REQUEST['excel']) && $_REQUEST['excel'] == 'xls')
				$this->report_format = 'xls';
			elseif (isset($_REQUEST['excel']) && $_REQUEST['excel'] == 'csv')
				$this->report_format = 'csv';
			else {
				$this->add_script("../../src/jquery/jquery.js");
				$this->add_script('delete.js');
			}
		}
		else {
			$this->add_script("../../src/CalendarControl.js");
			$this->add_script("../../src/jquery/jquery.js");
		}

		return True;
	}

	function fetch_report_data()
    {
		global $FANNIE_OP_DB, $FANNIE_ARCHIVE_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
		$date1 = FormLib::get_form_value('date1',date('Y-m-d'));
		$date2 = FormLib::get_form_value('date2',date('Y-m-d'));
		$dept1 = FormLib::get_form_value('deptStart',0);
		$dept2 = FormLib::get_form_value('deptEnd',0);

		$tempName = "TempNoMove";
		$dlog = DTransactionsModel::select_dlog($date1,$date2);
		$sumTable = $FANNIE_ARCHIVE_DB.$dbc->sep()."sumUpcSalesByDay";

		$tempQ = $dbc->prepare_statement("CREATE TABLE $tempName (upc varchar(13))");
		$dbc->exec_statement($tempQ);

		$insQ = $dbc->prepare_statement("INSERT INTO $tempName
			SELECT d.upc FROM $dlog AS d
			WHERE 
			d.tdate BETWEEN ? AND ?
			GROUP BY d.upc");
		$dbc->exec_statement($insQ,array($date1.' 00:00:00',$date2.' 23:59:59'));

		$query = $dbc->prepare_statement("SELECT p.upc,p.description,d.dept_no,
			d.dept_name FROM products AS p LEFT JOIN
			departments AS d ON p.department=d.dept_no
			WHERE p.upc NOT IN (select upc FROM $tempName)
			AND p.department
			BETWEEN ? AND ?
			ORDER BY p.upc");
		$result = $dbc->exec_statement($query,array($dept1,$dept2));

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
			if ($this->report_format == 'html'){
				$record[] = sprintf('<a href="" id="del%s"
						onclick="backgroundDelete(\'%s\',\'%s\');return false;">
						Delete this item</a>',$row[0],$row[0],$row[1]);
			}
			else
				$record[] = '';
			$ret[] = $record;
		}

		$drop = $dbc->prepare_statement("DROP TABLE $tempName");
		$dbc->exec_statement($drop);
		return $ret;
	}
	
	function form_content()
    {
		global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
		$deptsQ = $dbc->prepare_statement("select dept_no,dept_name from departments order by dept_no");
		$deptsR = $dbc->exec_statement($deptsQ);
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
		               <input type=text size=25 id=date1 name=date1 onfocus="this.value='';showCalendarControl(this);">
		               </p>
		               <p>
		                <input type=text size=25 id=date2 name=date2 onfocus="this.value='';showCalendarControl(this);">
		         </p>
		       </td>

		</tr>
		<tr> 
			<th>
			<label for="excel">Excel</label>
			</th>
			<td>
			<input type=checkbox name=excel value=xls id="excel" />
			</td>
			</td>
			<td rowspan=3 colspan=2>
			<?php echo FormLib::date_range_picker(); ?>	                        
			</td>
		</tr>
		<tr>
			<th>
			<label for="netted">Netted</label>
			</th>
			<td>
			<input type=checkbox name=netted id="netted" />
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
</div>
<?php
	}
}

$obj = new NonMovementReport();
$obj->draw_page();
?>
