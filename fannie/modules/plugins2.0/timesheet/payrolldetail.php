<html>
<head>
<Title>Payroll Detail</Title>
<link rel="stylesheet" href="./includes/style.css" type="text/css" />
</head>
<body>
<?php # payrolldetail.php - Gives a detailed view of a selected employee in a given pay period.

mysql_select_db('{$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}');


if (is_numeric($_GET['periodID']) && is_numeric($_GET['emp_no'])) { // If submitted.
    $emp_no = $_GET['emp_no'];
    $periodID = $_GET['periodID'];
    $query = "SELECT ROUND(SUM(TIMESTAMPDIFF(MINUTE, t.time_in, t.time_out))/60, 2),
            date_format(t.date, '%a %b %D'),
            t.emp_no,
            e.FirstName,
            date_format(p.periodStart, '%M %D, %Y'),
            date_format(p.periodEnd, '%M %D, %Y'),
            t.date
        FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
            INNER JOIN {$FANNIE_OP_DB}.employees AS e
            ON (t.emp_no = e.emp_no)
            INNER JOIN {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods AS p
            ON (t.periodID = p.periodID)
        WHERE t.emp_no = $emp_no
        AND t.periodID = $periodID
        AND t.area <> 13
        GROUP BY t.date";
    
    $weekoneQ = "SELECT ROUND(SUM(TIMESTAMPDIFF(MINUTE, t.time_in, t.time_out))/60, 2)
        FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
        INNER JOIN {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods AS p
        ON (p.periodID = t.periodID)
        WHERE t.emp_no = $emp_no
        AND t.periodID = $periodID
        AND t.area <> 13
        AND t.date >= DATE(p.periodStart)
        AND t.date < DATE(date_add(p.periodStart, INTERVAL 7 day))";
    
    $weektwoQ = "SELECT ROUND(SUM(TIMESTAMPDIFF(MINUTE, t.time_in, t.time_out))/60, 2)
        FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
        INNER JOIN {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods AS p
        ON (p.periodID = t.periodID)
        WHERE t.emp_no = $emp_no
        AND t.periodID = $periodID
        AND t.area <> 13
        AND t.date >= DATE(date_add(p.periodStart, INTERVAL 7 day)) AND t.date <= DATE(p.periodEnd)";
        
    $vacationQ = "SELECT ROUND(vacation, 2)
        FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
        WHERE t.emp_no = $emp_no
        AND t.periodID = $periodID
        AND t.area = 13";
    
    $weekoneR = mysqli_query($db_slave, $weekoneQ);
    $weektwoR = mysqli_query($db_slave, $weektwoQ);
    $vacationR = mysqli_query($db_slave, $vacationQ);
    
    list($weekone) = mysqli_fetch_row($weekoneR);
    if (is_null($weekone)) $weekone = 0;
    list($weektwo) = mysqli_fetch_row($weektwoR);
    if (is_null($weektwo)) $weektwo = 0;
    if (mysqli_num_rows($vacationR) != 0) {
        list($vacation) = mysqli_fetch_row($vacationR);
    } elseif (is_null($vacation)) {
        $vacation = 0;
    } else {
        $vacation = 0;
    }
    
    $result = mysqli_query($db_slave, $query);
    if (mysqli_num_rows($result) > 0) {
        $first = TRUE;
        $periodHours = 0;
        while ($row = mysqli_fetch_array($result)) {
            if ($first == TRUE) {
                echo "<p>Timesheet for $row[3] from $row[4] to $row[5]:</p>";
                echo '<table><tr><th>Date</th><th>Total Hours Worked</th><th></th></tr>';
            }
            if ($row[0] > 24) {$fontopen = '<font color="red">'; $fontclose = '</font>';} else {$fontopen = NULL; $fontclose = NULL;}
            echo "<tr><td>$row[1]</td><td>$fontopen$row[0]$fontclose</td><td><a target=\"_blank\" href=\"EditTimesheetDatePage.php?emp_no=$emp_no&periodID=$periodID&date=$row[6]\">(Edit)</a></td></tr>";
            $first = FALSE;
            $periodHours += $row[0];
        }
        
        $roundhour = explode('.', number_format($periodHours, 2));
                                
            if ($roundhour[1] < 13) {$roundhour[1] = 00;}
            elseif ($roundhour[1] >= 13 && $roundhour[1] < 37) {$roundhour[1] = 25;}
            elseif ($roundhour[1] >= 37 && $roundhour[1] < 63) {$roundhour[1] = 50;}
            elseif ($roundhour[1] >= 63 && $roundhour[1] < 87) {$roundhour[1] = 75;}
            elseif ($roundhour[1] >= 87) {$roundhour[1] = 00; $roundhour[0]++;}
            
        $periodHours = number_format($roundhour[0] . '.' . $roundhour[1], 2);
        
        echo "</table>
        <p>Total hours in this pay period: $periodHours</p>
        <p>Week One: ";
        if ($weekone > 40) {echo '<font color="red">'; $font = '</font>';} else {$font = NULL;}
        echo "$weekone" . $font . "</p>
        <p>Week Two: ";
        if ($weektwo > 40) {echo '<font color="red">'; $font = '</font>';} else {$font = NULL;}
        echo "$weektwo" . $font . "</p>
        <p>Vacation Pay: ";
        if ($vacation > 0) {echo '<font color="red">'; $font = '</font>';} else {$font = NULL;}
        echo "$vacation" . $font . "</p>";
    } else {
        echo '<p>There is no timesheet available for that pay period.</p>';
    }

}

?>
