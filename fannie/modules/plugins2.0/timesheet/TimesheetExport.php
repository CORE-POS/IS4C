<?php
require_once('../../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieReportPage.php');
include($FANNIE_ROOT.'classlib2.0/data/FannieDB.php');
include($FANNIE_ROOT.'classlib2.0/lib/FormLib.php');

$ts_db = FannieDB::get($FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']);

class TimesheetExport extends FannieReportPage {

	function preprocess(){
		$this->title = "Timeclock - EXPORT";
		$this->header = "TimeclockExport";
		$this->report_cache = 'none';

		if (FormLib::get_form_value('Run') == 'run'){
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

		return True;
	}

	function form_content(){
		global $ts_db, $FANNIE_OP_DB, $FANNIE_PLUGIN_SETTINGS;
		include('./includes/header.html');

		echo "<form action='".$_SERVER['PHP_SELF']."' method=GET>";

		$currentQ = "SELECT periodID FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods WHERE now() BETWEEN periodStart AND periodEnd";
		$currentR = $ts_db->query($currentQ);
		list($ID) = $ts_db->fetch_row($currentR);

		$query = "SELECT date_format(periodStart, '%M %D, %Y') as periodStart, 
			date_format(periodEnd, '%M %D, %Y') as periodEnd, periodID 
			FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods 
			WHERE periodStart < now() ORDER BY periodID DESC";
		$result = $ts_db->query($query);

		echo '<p>Pay Period: <select name="period">
		    <option>Please select a payperiod to view.</option>';

		while ($per = $ts_db->fetch_array($result)) {
			echo "<option value=\"" . $per['periodID'] . "\"";
			if ($per['periodID'] == ($ID)) { echo ' SELECTED';}
			echo ">(" . $per['periodStart'] . " - " . $per['periodEnd'] . ")</option>";
		}
		echo '</select><button value="run" name="Run">Run</button></p></form>';
	}

	function fetch_report_data(){
		global $ts_db, $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;
		$periodID = FormLib::get_form_value('period',0);
		$_SESSION['periodID'] = $periodID;
		$perDatesQ = "SELECT * FROM ".$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].".payperiods WHERE periodID = $periodID";
		$perDatesR = $ts_db->query($perDatesQ);
		$perDates = $ts_db->fetch_array($perDatesR);

		$dumpQ = "SELECT t.date, e.emp_no, e.LastName, e.FirstName, t.area, SUM(t.hours) AS hours 
			FROM (SELECT emp_no,FirstName, LastName FROM ".$FANNIE_OP_DB.".employees WHERE empActive = 1) e 
			LEFT JOIN ".$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].".timesheet t ON e.emp_no = t.emp_no 
			AND t.periodID = $periodID GROUP BY e.emp_no";
		
		$result = $ts_db->query($dumpQ);

		$data = array();
		$data[] = array("TC");
		$data[] = array("00001");
		while ($row = $ts_db->fetch_row($result)) {
			$nonPTOtotalq = "SELECT SUM(hours) FROM ".$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].".timesheet 
				WHERE periodID = $periodID AND area <> 31 AND emp_no = " . $row['emp_no'];
			$nonPTOtotalr = $ts_db->query($nonPTOtotalq);
			$nonPTOtotal = $ts_db->fetch_row($nonPTOtotalr);
			
			$nonPTOtot = $nonPTOtotal[0];
			$date = (is_null($row['date'])) ? 0 : $row['date'];
			$area = (is_null($row['area'])) ? 0 : $row['area'];
			$hours = (is_null($row['hours'])) ? 0 : $row['hours'];
		
			if ($hours > 0) {
				$weekoneQ = "SELECT ROUND(SUM(hours), 2)
					    FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
					    INNER JOIN {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods AS p
					    ON (p.periodID = t.periodID)
					    WHERE t.emp_no = " . $row['emp_no'] . "
					    AND t.periodID = $periodID
					    AND t.area <> 31
					    AND t.tdate >= DATE(p.periodStart)
					    AND t.tdate < DATE(date_add(p.periodStart, INTERVAL 7 day))";

				$weektwoQ = "SELECT ROUND(SUM(hours), 2)
					    FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
					    INNER JOIN {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods AS p
					    ON (p.periodID = t.periodID)
					    WHERE t.emp_no = " . $row['emp_no'] . "
					    AND t.periodID = $periodID
					    AND t.area <> 31
					    AND t.tdate >= DATE(date_add(p.periodStart, INTERVAL 7 day)) 
				 	    AND t.tdate <= DATE(p.periodEnd)";

				$vacationQ = "SELECT ROUND(SUM(hours), 2)
					    FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
					    WHERE t.emp_no = " . $row['emp_no'] . "
					    AND t.periodID = $periodID
					    AND t.area = 31";

				$weekoneR = $ts_db->query($weekoneQ);
				$weektwoR = $ts_db->query($weektwoQ);
				$vacationR = $ts_db->query($vacationQ);

				list($weekone) = $ts_db->fetch_row($weekoneR);
				if (is_null($weekone)) $weekone = 0;
				list($weektwo) = $ts_db->fetch_row($weektwoR);
				if (is_null($weektwo)) $weektwo = 0;
				list($pto) = $ts_db->fetch_row($vacationR);
				if (is_null($pto)) $pto = 0;

				$ft = 40;


				$otime1 = (($weekone - $ft) < 0) ? 0 : $weekone - $ft;
				$otime2 = (($weektwo - $ft) < 0) ? 0 : $weektwo - $ft;
				$otime = $otime1 + $otime2;
				$total = ($otime != 0) ? $ft + (($otime2 != 0) ? $ft : $weektwo) : $nonPTOtot;
				
				$record = array(strftime("%D",strtotime($date)),
						$row['emp_no'],$row['LastName'], 
						$row['FirstName'], "01", 
						number_format($total,2));
				$data[] = $record;

				if ($weekone > $ft || $weektwo > $ft) {
					$ot_record = array(strftime("%D",strtotime($date)),
							$row['emp_no'],$row['LastName'], 
							$row['FirstName'], "02", 
							number_format($otime,2));
					$data[] = $ot_record;
				}
				if ($pto != 0) {
					$pto_record = array(strftime("%D",strtotime($date)),
							$row['emp_no'],$row['LastName'], 
							$row['FirstName'], "08", 
							number_format($pto,2));
					$data[] = $pto_record;
				}

			} 
			else {
				$null_record = array(strftime("%D",strtotime($row['periodEnd'])),
							$row['emp_no'],$row['LastName'], 
							$row['FirstName'], "01", "0.00");
				$data[] = $null_record;	
			}
		}
		return $data;
	}

	function earncode($val) {
		// Surepay earning codes:
		// 		01 regular
		// 		02 o/time  = >40 / week
		// 		08 other h
		
		$area = "01";
		
		return $area;
	}
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)){
	$obj = new TimesheetExport();
	$obj->draw_page();
}

?>
