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

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	* 03Apr13 AT incorporated changes below in class-based version
	*
	* 13Feb13 Eric Lee This report counts the number of transaction, not the
	*          number of, possibly unique, customers.
	*         Correct handling of date, memType, trans_num in various tables/views.
	*         Get member Type headings from table, except for "WFC"
	*         Add a report heading.
	*         Change WHERE trans_type from "in ('I','D')" to "= 'T'" because card_no,
	*          which is needed to derive memType is always available there but
	*          sometimes not for I and D items. Day and Grand totals are the same
	*          but member type counts more accurate with T.
*/

include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'src/select_dlog.php');
include($FANNIE_ROOT.'classlib2.0/FannieReportPage.php');
include($FANNIE_ROOT.'classlib2.0/lib/FormLib.php');

class CustomerCountReport extends FannieReportPage {

	var $memtypes;

	function preprocess(){
		global $dbc;
		/**
		  Set the page header and title, enable caching
		*/
		$this->title = "Fannie : Customer Report";
		$this->header = "Customer Report";
		$this->report_cache = 'day';

		if (isset($_REQUEST['date1'])){
			/**
			  Form submission occurred

			  Change content function, turn off the menus,
			  set up headers
			*/
			$this->content_function = "report_content";
			$this->has_menus(False);

			$typeQ = $dbc->prepare_statement("SELECT memtype,memDesc FROM memtype ORDER BY memtype");
			$typeR = $dbc->exec_statement($typeQ);
			$this->memtypes = array();
			$this->report_headers = array('Date');
			while($typeW = $dbc->fetch_row($typeR)){
				$this->report_headers[] = $typeW['memDesc'];
				$this->memtypes[$typeW['memtype']] = $typeW['memDesc'];
			}
			$this->report_headers[] = 'Total';
		
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
		$date1 = FormLib::get_form_value('date1',date('Y-m-d'));
		$date2 = FormLib::get_form_value('date2',date('Y-m-d'));

		$dlog = select_dlog($date1,$date2);
		$date1 .= ' 00:00:00';
		$date2 .= ' 23:59:59';

		$sales = "SELECT year(tdate) as year, month(tdate) as month,
			day(tdate) as day,max(memType) as memType,trans_num
			FROM $dlog as t
			WHERE 
			tdate BETWEEN ? AND ?
			and trans_type = 'T'
			AND upc <> 'RRR'
			group by year(tdate),month(tdate),day(tdate),trans_num
			order by year(tdate),month(tdate),day(tdate),max(memType)";
		$salesP = $dbc->prepare_statement($sales);
		$result = $dbc->exec_statement($salesP, array($date1, $date2));

		/**
		  Create result records based on date and increment them
		  when the same type is encountered again
		*/
		$ret = array();
		while ($row = $dbc->fetch_array($result)){
			$stamp = date("M j, Y",mktime(0,0,0,$row['month'],$row['day'],$row['year']));
			if (!isset($ret[$stamp])){ 
				$ret[$stamp] = array("date"=>$stamp);
				foreach($this->memtypes as $id=>$desc)
					$ret[$stamp][$id] = 0;
				$ret[$stamp]['ttl'] = 0;
			}
			$ret[$stamp]["ttl"]++;
			$ret[$stamp][$row['memType']]++;
		}
		$ret = $this->dekey_array($ret);
		return $ret;
	}
	
	/**
	  Sum the quantity and total columns
	*/
	function calculate_footers($data){
		$sum = 0;
		foreach($data as $row){
			$sum += $row[7];
		}
		return array('Grand Total',null,null,null,null,null,null,$sum);
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
<div id=main>	
<form action=CustomerCountReport.php method=get>
<table cellspacing=4 cellpadding=4>
<tr>
	<th>Start Date</th>
	<td><input type=text id=date1 name=date1 onclick="showCalendarControl(this);" value="<?php echo $lastMonday; ?>" /></td>
	<td rowspan="4">
	<?php echo FormLib::date_range_picker(); ?>
	</td>
</tr>
<tr>
	<th>End Date</th>
	<td><input type=text id=date2 name=date2 onclick="showCalendarControl(this);" value="<?php echo $lastSunday; ?>" /></td>
</tr>
<tr>
	<td>
	<label for="excel">Excel</label>
	<input type=checkbox name=excel id="excel" value=xls />
	</td>
<td><input type=submit name=submit value="Submit" /></td>
</tr>
</table>
</form>
</div>
<?php
	}
}

$obj = new CustomerCountReport();
$obj->draw_page();
?>
