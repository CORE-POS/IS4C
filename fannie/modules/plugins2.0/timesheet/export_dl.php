<?php
include('../../../config.php');
// $periodID = $_GET['period'];
$periodID = $_SESSION['periodID'];
// $dates = mysql_query("SELECT * FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods WHERE periodID = $periodID");

$perDatesQ = "SELECT * FROM ".$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].".payperiods WHERE periodID = $periodID";
$perDatesR = mysql_query($perDatesQ);
$perDates = mysql_fetch_array($perDatesR);

$fn = "PeoplesHoursExport_" . strftime("%F",strtotime($perDates['periodEnd']));
header("Content-type: application/csv");
header("Content-Disposition: attachment; filename=$fn.csv");
header("Pragma: no-cache");
header("Expires: 0");

$dumpQ = "SELECT t.tdate, e.emp_no, e.LastName, e.FirstName, t.area, SUM(t.hours) AS hours 
	FROM (SELECT emp_no,FirstName, LastName FROM ".$FANNIE_OP_DB.".employees WHERE empActive = 1) e 
	LEFT JOIN ".$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].".timesheet t ON e.emp_no = t.emp_no 
	AND t.periodID = $periodID GROUP BY e.emp_no";

$result = mysql_query($dumpQ);

echo "TC\n00001\n";	//	surepay-specific

$br = ",";
	
while ($row = mysql_fetch_assoc($result)) {
	$nonPTOtotalq = "SELECT SUM(hours) FROM ".$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].".timesheet WHERE periodID = $periodID AND area <> 31 AND emp_no = " . $row['emp_no'];
	$nonPTOtotalr = mysql_query($nonPTOtotalq);
	$nonPTOtotal = mysql_fetch_row($nonPTOtotalr);
	
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
            AND t.tdate >= DATE(date_add(p.periodStart, INTERVAL 7 day)) AND t.tdate <= DATE(p.periodEnd)";

        $vacationQ = "SELECT ROUND(SUM(hours), 2)
            FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
            WHERE t.emp_no = " . $row['emp_no'] . "
            AND t.periodID = $periodID
            AND t.area = 31";

        $weekoneR = mysql_query($weekoneQ);
        $weektwoR = mysql_query($weektwoQ);
        $vacationR = mysql_query($vacationQ);

        list($weekone) = mysql_fetch_row($weekoneR);
        if (is_null($weekone)) $weekone = 0;
        list($weektwo) = mysql_fetch_row($weektwoR);
        if (is_null($weektwo)) $weektwo = 0;
		list($pto) = mysql_fetch_row($vacationR);
		if (is_null($pto)) $pto = 0;

		$ft = 40;


		$otime1 = (($weekone - $ft) < 0) ? 0 : $weekone - $ft;
		$otime2 = (($weektwo - $ft) < 0) ? 0 : $weektwo - $ft;
		$otime = $otime1 + $otime2;
		$total = ($otime != 0) ? $ft : $nonPTOtot;
		
		echo strftime("%D",strtotime($date)) . $br . $row['emp_no'] . $br . $row['LastName'] . $br . $row['FirstName'] . $br . "01" . $br . number_format($total,2) . "\n";
		$output .= array(strftime("%D",strtotime($date)),$row['emp_no'],$row['LastName'], $row['FirstName'], "01", number_format($total,2));

		if ($weekone > $ft || $weektwo > $ft) {
			echo strftime("%D",strtotime($date)) . $br . $row['emp_no'] . $br . $row['LastName'] . $br . $row['FirstName'] . $br . "02" . $br . number_format($otime,2) . "\n";
			$output .= array(strftime("%D",strtotime($date)),$row['emp_no'],$row['LastName'], $row['FirstName'], "02", number_format($otime,2));
		}
		if ($pto != 0) {
			echo strftime("%D",strtotime($date)) . $br . $row['emp_no'] . $br . $row['LastName'] . $br . $row['FirstName'] . $br . "08" . $br . number_format($pto,2) . "\n";
			$output .= array(strftime("%D",strtotime($date)),$row['emp_no'],$row['LastName'], $row['FirstName'], "08", number_format($pto,2));
		}

	} else {
		
		echo strftime("%D",strtotime($perDates['periodEnd'])) . $br . $row['emp_no'] . $br . $row['LastName'] . $br . $row['FirstName'] . $br . "01" . $br . "0.00" . "\n";
		$output .= array(strftime("%D",strtotime($row['periodEnd'])),$row['emp_no'],$row['LastName'], $row['FirstName'], "01", "0.00");
	
	}
}

function earncode($val) {
	$area = "01";
	
	return $area;
}
?>
