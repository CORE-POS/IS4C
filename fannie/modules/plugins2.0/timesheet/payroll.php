<?php # payroll.php - Generates a bi-monthly statement from timesheet table.
$header = 'Timeclock - Payroll Summary';
$page_title = 'Fannie - Administration Module';
include ('../../../src/header.php');
include ('./includes/header.html');

echo '<SCRIPT TYPE="text/javascript">
<!--
function popup(mylink, windowname)
{
if (! window.focus)return true;
var href;
if (typeof(mylink) == \'string\')
   href=mylink;
else
   href=mylink.href;
window.open(href, windowname, \'width=650,height=600,scrollbars=yes,menubar=no,location=no,toolbar=no,dependent=yes\');
return false;
}
//-->
</SCRIPT>

</head>
<body>';


mysql_select_db('{$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}');


if (isset($_POST['submitted']) && is_numeric($_POST['period'])) { // If submitted.
    $periodID = $_POST['period'];
    $query = "SELECT ROUND(SUM(TIMESTAMPDIFF(MINUTE, t.time_in, t.time_out))/60, 2),
            t.emp_no,
            e.RealFirstName,
            date_format(p.periodStart, '%M %D, %Y'),
            date_format(p.periodEnd, '%M %D, %Y'),
            e.card_no,
	    e.LastName
        FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
            INNER JOIN is4c_op.employees AS e
            ON (t.emp_no = e.emp_no)
            INNER JOIN {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods AS p
            ON (t.periodID = p.periodID)
        WHERE t.periodID = $periodID
            AND t.area NOT IN (13, 14)
        GROUP BY t.emp_no
        ORDER BY e.RealFirstName ASC";

    $result = mysql_query($query, $db_master)
OR DIE ("query error" . mysql_error ());
    $periodQ = "SELECT periodStart, periodEnd FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods WHERE periodID = $periodID";
    $periodR = mysql_query($periodQ, $db_master);
    list($periodStart, $periodEnd) = mysql_fetch_row($periodR);

    if (mysql_num_rows($result) > 0) {
        $first = TRUE;

	// Counter variables.
	$totalPeriodHours = 0;
	$totalWeekOne = 0;
	$totalWeekTwo = 0;
	$totalVacation = 0;
	$totalPrevious = 0;
	$count = 0;
	$totalHouseCharge = 0;
	$hours = array();

        $bg = '#eeeeee';
        while ($row = mysql_fetch_array($result)) {
            $emp_no = $row[1];
            $cn = $row[5];

	    $yearStart = substr($periodStart, 0, 4);
	    $yearEnd = substr($periodEnd, 0, 4);

            $prevQ = "SELECT SUM(ROUND(TIMESTAMPDIFF(MINUTE, time_in, time_out)/60,2)), date, DAYOFWEEK(date)
                FROM timesheet
                WHERE emp_no = $emp_no
                AND periodID = $periodID
                AND date NOT BETWEEN '$periodStart' AND '$periodEnd'
                GROUP BY date";
            $prevR = mysql_query($prevQ, $db_master);

            if (mysql_num_rows($prevR) > 0) {
                $totalPHours[$emp_no] = 0;
                $totalPOT[$emp_no] = 0;

                while (list($pHours, $pDate, $pDay) = mysqli_fetch_row($prevR)) {
                    // Get a week range for the old payday.
                    $weekQ = "SELECT DATE_ADD('$pDate', INTERVAL (1-$pDay) DAY) AS weekStart, DATE_ADD('$pDate', INTERVAL (7-$pDay) DAY) AS weekEnd";
                    $weekR = mysqli_query($db_slave, $weekQ);
                    // echo "<p>" . mysql_error() . "</p>";

                    list($weekStart, $weekEnd) = mysqli_fetch_row($weekR);
                    // echo $weekStart . " & " . $weekEnd . " & " . $emp_no;
                    $Q = "SELECT SUM(ROUND(TIMESTAMPDIFF(MINUTE, time_in, time_out)/60,2))
                        FROM timesheet
                        WHERE emp_no = $emp_no
                        AND date BETWEEN '$weekStart' AND '$weekEnd'";
                    $R = mysqli_query($db_slave, $Q);

                    list($totalHours) = mysqli_fetch_row($R);

                    if (($totalHours > 40) && ($totalHours - $pHours < 40) && (!$week[$startWeek])) $prevOT = $totalHours - 40;
                    else $prevOT = NULL;

                    $week[$startWeek] = TRUE;

                    $totalPHours[$emp_no] += $pHours;
                    $totalPOT[$emp_no] += $prevOT;

                }
            }


            $weekoneQ = "SELECT ROUND(SUM(TIMESTAMPDIFF(MINUTE, t.time_in, t.time_out))/60, 2)
                FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
                INNER JOIN {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods AS p
                ON (p.periodID = t.periodID)
                WHERE t.emp_no = $emp_no
                AND t.periodID = $periodID
                AND t.date >= DATE(p.periodStart)
                AND t.date < DATE(date_add(p.periodStart, INTERVAL 7 day))
                AND t.area NOT IN (31)";

            $weektwoQ = "SELECT ROUND(SUM(TIMESTAMPDIFF(MINUTE, t.time_in, t.time_out))/60, 2)
                FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
                INNER JOIN {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods AS p
                ON (p.periodID = t.periodID)
                WHERE t.emp_no = $emp_no
                AND t.periodID = $periodID
                AND t.date >= DATE(date_add(p.periodStart, INTERVAL 7 day)) AND t.date <= DATE(p.periodEnd)
                AND t.area NOT IN (31)";

            $vacationQ = "SELECT ROUND(vacation, 2)
                FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
                INNER JOIN {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods AS p
                ON (p.periodID = t.periodID)
                WHERE t.emp_no = $emp_no
                AND t.periodID = $periodID
                AND t.area = 31";

            $oncallQ = "SELECT ROUND(SUM(TIMESTAMPDIFF(MINUTE, t.time_in, t.time_out))/60, 2)
                FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
                INNER JOIN {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods AS p
                ON (p.periodID = t.periodID)
                WHERE t.emp_no = $emp_no
                AND t.periodID = $periodID
                AND t.area =100";

	    if ($yearStart == $yearEnd) {
		$houseChargeQ = "SELECT ROUND(SUM(d.total),2)
		    FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.trans_$yearStart AS d
		    WHERE d.datetime BETWEEN '$periodStart' AND '$periodEnd'
		    AND d.trans_subtype = 'MI'
		    AND d.card_no = $cn
		    AND d.emp_no <> 9999 AND d.trans_status <> 'X'";
	    } else {
		$houseChargeQ = "SELECT ROUND(SUM(Total),2)
		    FROM (";

		$houseChargeQ .= "SELECT ROUND(SUM(d.total),2) AS Total
		    FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.trans_$yearStart AS d
		    WHERE d.datetime BETWEEN '$periodStart' AND '$periodEnd'
		    AND d.trans_subtype = 'MI'
		    AND d.card_no = $cn
		    AND d.emp_no <> 9999 AND d.trans_status <> 'X'";

		$houseChargeQ .= " UNION ALL SELECT ROUND(SUM(d.total),2) AS Total
		    FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.trans_$yearEnd AS d
		    WHERE d.datetime BETWEEN '$periodStart' AND '$periodEnd'
		    AND d.trans_subtype = 'MI'
		    AND d.card_no = $cn
		    AND d.emp_no <> 9999 AND d.trans_status <> 'X') AS yearSpan";
	    }

            $weekoneR = mysql_query($weekoneQ, $db_master);
            $weektwoR = mysql_query($weektwoQ, $db_master);
            $vacationR = mysql_query($vacationQ, $db_master);
            $oncallR = mysql_query($oncallQ, $db_master);
            // $houseChargeR = mysqli_query($db_slave, $houseChargeQ);

            $roundhour = explode('.', number_format($row[0], 2));

            if ($roundhour[1] < 13) {$roundhour[1] = 00;}
            elseif ($roundhour[1] >= 13 && $roundhour[1] < 37) {$roundhour[1] = 25;}
            elseif ($roundhour[1] >= 37 && $roundhour[1] < 63) {$roundhour[1] = 50;}
            elseif ($roundhour[1] >= 63 && $roundhour[1] < 87) {$roundhour[1] = 75;}
            elseif ($roundhour[1] >= 87) {$roundhour[1] = 00; $roundhour[0]++;}

            $row[0] = number_format($roundhour[0] . '.' . $roundhour[1], 2);

            list($weekone) = mysql_fetch_row($weekoneR);
            if (is_null($weekone)) $weekone = 0;
            list($weektwo) = mysql_fetch_row($weektwoR);
            if (is_null($weektwo)) $weektwo = 0;

            if (mysql_num_rows($vacationR) != 0) {
                list($vacation) = mysql_fetch_row($vacationR);
            } elseif (!isset($vacation) || is_null($vacation)) {
                $vacation = 0;
            } else {
                $vacation = 0;
            }

            list($oncall) = mysql_fetch_row($oncallR);
            if (is_null($oncall)) $oncall = 0;
            //  list($houseCharge) = mysql_fetch_row($houseChargeR);
            // $houseCharge = number_format($houseCharge * -1, 2);
            // if (is_null($houseCharge))
              $houseCharge = '0.00';

            if ($first == TRUE) {
                echo "<p>Payroll Summary for $row[3] to $row[4]:</p>";
                echo '<table border="1"><thead><tr><th>Employee</th><th>Total Hours Worked</th><th>Previous Pay Periods</th><th>Week One</th><th>Week Two</th><th>Vacation Hours</th><th>House Charges</th><th>Detailed View</th></tr></thead><tbody>';
            }
            $bg = ($bg == '#eeeeee' ? '#ffffff' : '#eeeeee'); // Switch the background color.
            if ($row[0] > 80 || (isset($totalPOT[$emp_no]) && $totalPOT[$emp_no] > 0) ) {$fontopen = '<font color="red">'; $fontclose = '</font>';} else {$fontopen = NULL; $fontclose = NULL;}
            printf("<tr bgcolor='$bg'><td>%s</td><td align='center'>%s%s", $row[2] . " " . substr($row[6], 0, 1), $fontopen, $row[0]);
            if ($oncall > 0) {echo '<font color="red"><br />(On Call: ' . $oncall . ')</font>';}
            echo "$fontclose</td>";

            if (isset($totalPOT[$emp_no]) && $totalPOT[$emp_no] > 0) {$fontopen = '<font color="red">'; $fontclose = '</font>';} else {$fontopen = NULL; $fontclose = NULL;}
            echo "<td align='center'>$fontopen" . (isset($totalPHours[$emp_no]) && $totalPHours[$emp_no] > 0 ? number_format($totalPHours[$emp_no], 2) : "N/A") . (isset($totalPOT[$emp_no]) && $totalPOT[$emp_no] > 0 ? "(" . number_format($totalPOT[$emp_no], 2) . ")" : NULL) . "$fontclose</td>";

            if ($weekone > 40) {$fontopen = '<font color="red">'; $fontclose = '</font>';} else {$fontopen = NULL; $fontclose = NULL;}
            echo "<td align='center'>$fontopen$weekone$fontclose</td>";
            if ($weektwo > 40) {$fontopen = '<font color="red">'; $fontclose = '</font>';} else {$fontopen = NULL; $fontclose = NULL;}
            echo "<td align='center'>$fontopen$weektwo$fontclose</td>";
            if ($vacation > 0) {$fontopen = '<font color="red">'; $fontclose = '</font>';} else {$fontopen = NULL; $fontclose = NULL;}
            echo "<td align='center'>$fontopen$vacation$fontclose</td>";
            echo "<td align='center'>$$houseCharge</td>";
            echo "<td><a href=\"admin/view.php?emp_no=$emp_no&periodID=$periodID&function=view\" onClick=\"return popup(this, 'payrolldetail')\">(Detailed View)</a></td></tr>";

            $first = FALSE;

            // Counter variables.
	    $totalPeriodHours += $row[0];
	    $totalWeekOne += $weekone;
	    $totalWeekTwo += $weektwo;
	    $totalVacation += $vacation;
	    $totalPrevious += (isset($totalPHours[$emp_no]) ? $totalPHours[$emp_no] : 0);
	    ++$count;
	    $totalHouseCharge += $houseCharge;
        }

        printf('</tbody><tfoot><tr style="font-weight: bold;">
	        <td align="left">Totals</td>
		<td align="center">%.2f</td>
		<td align="center">%s</td>
		<td align="center">%.2f</td>
		<td align="center">%.2f</td>
		<td align="center">%.2f</td>
		<td align="center">$%.2f</td>
		<td align="center">%u Employees</td>
	    </tr>
	    </tfoot>
	    </table><br />', $totalPeriodHours, $totalPrevious > 0 ? number_format($totalPrevious, 2) : "N/A", $totalWeekOne, $totalWeekTwo, $totalVacation, $totalHouseCharge, $count);
    } else {
        echo '<p>There is no timesheet available for that pay period.</p>';
    }

} else {
    $query = "SELECT FirstName, emp_no FROM employees WHERE EmpActive=1 ORDER BY FirstName ASC";
    $result = mysql_query($query, $db_master);
    echo '<form action="payroll.php" method="POST">';
    $currentQ = "SELECT periodID-1 FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods WHERE now() BETWEEN periodStart AND periodEnd";
    $currentR = mysql_query($currentQ, $db_master);
    list($ID) = mysql_fetch_row($currentR);

    $query = "SELECT DATE_FORMAT(periodStart, '%M %D, %Y'), DATE_FORMAT(periodEnd, '%M %D, %Y'), periodID FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods WHERE periodStart < now()";
    $result = mysql_query($query, $db_master);

    echo '<p>Pay Period: <select name="period">
        <option>Please select a payperiod to view.</option>';

    while ($row = mysql_fetch_array($result)) {
        echo "<option value=\"$row[2]\"";
        if ($row[2] == $ID) { echo ' SELECTED';}
        echo ">$row[0] - $row[1]</option>";
    }
    echo '</select></p>';

    echo '<button name="submit" type="submit">Submit</button>
    <input type="hidden" name="submitted" value="TRUE" />
    </form>';
}

// include ('./includes/footer.html');
include ('../../../src/footer.php');
?>
