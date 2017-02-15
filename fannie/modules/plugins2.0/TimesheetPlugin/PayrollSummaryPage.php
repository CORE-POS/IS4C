<?php # payroll.php - Generates a bi-monthly statement from timesheet table.
include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class PayrollSummaryPage extends FanniePage {
    public $page_set = 'Plugin :: TimesheetPlugin';

    public function preprocess()
    {
        $this->header = 'Timeclock - Payroll Summary';
        $this->title = 'Fannie - Administration Module';

        return true;
    }

    function javascript_content(){
        ob_start();
        ?>
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
        <?php
        return ob_get_clean();
    }


    function body_content()
    {
        global $FANNIE_OP_DB, $FANNIE_PLUGIN_SETTINGS;
        $ts_db = FannieDB::get($FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']);

        include ('./includes/header.html');
        $submitted = FormLib::get_form_value('submitted','');
        $periodID = FormLib::get_form_value('period','');
        if (!empty($submitted) && is_numeric($periodID)) { // If submitted.
            $periodID = $_POST['period'];
            $query = $ts_db->prepare("
            SELECT ROUND(SUM(TIMESTAMPDIFF(MINUTE, t.time_in, t.time_out))/60, 2),
                t.emp_no,
                e.FirstName,
                date_format(p.periodStart, '%M %D, %Y'),
                date_format(p.periodEnd, '%M %D, %Y'),
                e.card_no,
                e.LastName
            FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
                INNER JOIN {$FANNIE_OP_DB}.employees AS e ON (t.emp_no = e.emp_no)
                INNER JOIN {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods AS p ON (t.periodID = p.periodID)
            WHERE t.periodID = ?
                AND t.area NOT IN (13, 14)
            GROUP BY t.emp_no
            ORDER BY e.FirstName ASC");

            $result = $ts_db->execute($query,array($periodID));
            var_dump($ts_db->error());

            $periodQ = $ts_db->prepare("SELECT periodStart, periodEnd 
                FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods 
                WHERE periodID = ?");
            $periodR = $ts_db->execute($periodQ,array($periodID));
            list($periodStart, $periodEnd) = $ts_db->fetch_row($periodR);

            if ($ts_db->num_rows($result) > 0) {
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
                $prevP = $ts_db->prepare("SELECT 
                    SUM(ROUND(TIMESTAMPDIFF(MINUTE, time_in, time_out)/60,2)), 
                    tdate, DAYOFWEEK(date)
                    FROM timesheet
                    WHERE emp_no = ?
                    AND periodID = ?
                    AND tdate NOT BETWEEN ? AND ?
                    GROUP BY date");
                $weekP = $ts_db->prepare("SELECT 
                    DATE_ADD(?, INTERVAL (1-?) DAY) AS weekStart, 
                    DATE_ADD(?, INTERVAL (7-?) DAY) AS weekEnd)");
                $tsP = $ts_db->prepare("SELECT 
                    SUM(ROUND(TIMESTAMPDIFF(MINUTE, time_in, time_out)/60,2))
                    FROM timesheet
                    WHERE emp_no = ?
                    AND tdate BETWEEN ? AND ?");
                $weekoneP = $ts_db->prepare("SELECT 
                    ROUND(SUM(TIMESTAMPDIFF(MINUTE, t.time_in, t.time_out))/60, 2)
                    FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
                    INNER JOIN {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods AS p
                    ON (p.periodID = t.periodID)
                    WHERE t.emp_no = ?
                    AND t.periodID = ?
                    AND t.tdate >= DATE(p.periodStart)
                    AND t.tdate < DATE(date_add(p.periodStart, INTERVAL 7 day))
                    AND t.area NOT IN (31)");
                $weektwoP = $ts_db->prepare("SELECT 
                    ROUND(SUM(TIMESTAMPDIFF(MINUTE, t.time_in, t.time_out))/60, 2)
                    FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
                    INNER JOIN {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods AS p
                    ON (p.periodID = t.periodID)
                    WHERE t.emp_no = ?
                    AND t.periodID = ?
                    AND t.tdate >= DATE(date_add(p.periodStart, INTERVAL 7 day)) 
                    AND t.tdate <= DATE(p.periodEnd)
                    AND t.area NOT IN (31)");
                $vacationP = $ts_db->prepare("SELECT ROUND(vacation, 2)
                    FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
                    INNER JOIN {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods AS p
                    ON (p.periodID = t.periodID)
                    WHERE t.emp_no = ?
                    AND t.periodID = ?
                    AND t.area = 31");
                $oncallP = $ts_db->prepare("SELECT 
                    ROUND(SUM(TIMESTAMPDIFF(MINUTE, t.time_in, t.time_out))/60, 2)
                    FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
                    INNER JOIN {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods AS p
                    ON (p.periodID = t.periodID)
                    WHERE t.emp_no = ?
                    AND t.periodID = ?
                    AND t.area =100");
                while ($row = $ts_db->fetchRow($result)) {
                    $emp_no = $row[1];
                    $cn = $row[5];

                    $yearStart = substr($periodStart, 0, 4);
                    $yearEnd = substr($periodEnd, 0, 4);

                    $prevR = $ts_db->execute($prevP,array($emp_no,$periodID,
                        $periodStart,$periodEnd));

                    if ($ts_db->num_rows($prevR) > 0) {
                        $totalPHours[$emp_no] = 0;
                        $totalPOT[$emp_no] = 0;

                        while (list($pHours, $pDate, $pDay) = $ts_db->fetch_row($prevR)) {
                            // Get a week range for the old payday.
                            $weekR = $ts_db->execute($weekP,array(
                                $pDate,$pDay,$pDate,$pDay));

                            list($weekStart, $weekEnd) = $ts_db->fetch_row($weekR);
                            // echo $weekStart . " & " . $weekEnd . " & " . $emp_no;
                            $R = $ts_db->execute($tsP,array($emp_no,$weekStart,$weekEnd));

                            list($totalHours) = $ts_db->fetch_row($R);

                            $prevOT = NULL;
                            if (($totalHours > 40) && ($totalHours - $pHours < 40) && (!$week[$startWeek])) 
                                $prevOT = $totalHours - 40;

                            $week[$startWeek] = TRUE;

                            $totalPHours[$emp_no] += $pHours;
                            $totalPOT[$emp_no] += $prevOT;

                        }
                    }


                    if ($yearStart == $yearEnd) {
                        $houseChargeQ = "SELECT ROUND(SUM(d.total),2)
                            FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.trans_$yearStart AS d
                            WHERE d.datetime BETWEEN '$periodStart' AND '$periodEnd'
                            AND d.trans_subtype = 'MI'
                            AND d.card_no = $cn
                            AND d.emp_no <> 9999 AND d.trans_status <> 'X'";
                    } 
                    else {
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

                    $weekoneR = $ts_db->execute($weekoneP,array($emp_no,$periodID));
                    $weektwoR = $ts_db->execute($weektwoP,array($emp_no,$periodID));
                    $vacationR = $ts_db->execute($vacationP,array($emp_no,$periodID));
                    $oncallR = $ts_db->execute($oncallP,array($emp_no,$periodID));

                    $roundhour = explode('.', number_format($row[0], 2));

                    if ($roundhour[1] < 13) {$roundhour[1] = 00;}
                    elseif ($roundhour[1] >= 13 && $roundhour[1] < 37) {$roundhour[1] = 25;}
                    elseif ($roundhour[1] >= 37 && $roundhour[1] < 63) {$roundhour[1] = 50;}
                    elseif ($roundhour[1] >= 63 && $roundhour[1] < 87) {$roundhour[1] = 75;}
                    elseif ($roundhour[1] >= 87) {$roundhour[1] = 00; $roundhour[0]++;}

                    $row[0] = number_format($roundhour[0] . '.' . $roundhour[1], 2);

                    list($weekone) = $ts_db->fetch_row($weekoneR);
                    if (is_null($weekone)) $weekone = 0;
                    list($weektwo) = $ts_db->fetch_row($weektwoR);
                    if (is_null($weektwo)) $weektwo = 0;

                    if ($ts_db->num_rows($vacationR) != 0) {
                    list($vacation) = $ts_db->fetch_row($vacationR);
                    } elseif (!isset($vacation) || is_null($vacation)) {
                    $vacation = 0;
                    } else {
                    $vacation = 0;
                    }

                    list($oncall) = $ts_db->fetch_row($oncallR);
                    if (is_null($oncall)) $oncall = 0;
                    //  list($houseCharge) = $ts_db->fetch_row($houseChargeR);
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
                    </table><br />', $totalPeriodHours, 
                    $totalPrevious > 0 ? number_format($totalPrevious, 2) : "N/A", 
                    $totalWeekOne, $totalWeekTwo, $totalVacation, 
                    $totalHouseCharge, $count);
            } 
            else {
                echo '<p>There is no timesheet available for that pay period.</p>';
            }
        } else {
            $query = $ts_db->prepare("SELECT FirstName, emp_no FROM "
                .$FANNIE_OP_DB.$ts_db->sep()."employees 
                WHERE EmpActive=1 ORDER BY FirstName ASC");
            $result = $ts_db->execute($query);
            echo '<form action="'.$_SERVER['PHP_SELF'].'" method="get">';
            $currentQ = $ts_db->prepare("SELECT periodID-1 FROM 
                {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods 
                WHERE ".$ts_db->now()." BETWEEN periodStart AND periodEnd");
            $currentR = $ts_db->execute($currentQ);
            list($ID) = $ts_db->fetch_row($currentR);

            $query = $ts_db->prepare("SELECT DATE_FORMAT(periodStart, '%M %D, %Y'), 
                DATE_FORMAT(periodEnd, '%M %D, %Y'), periodID 
                FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods 
                WHERE periodStart < ".$ts_db->now());
            $result = $ts_db->execute($query);

            echo '<div class="form-group">
                <label>Pay Period</label>
                <select class="form-control" name="period">
                <option>Please select a payperiod to view.</option>';

            while ($row = $ts_db->fetchRow($result)) {
                echo "<option value=\"$row[2]\"";
                if ($row[2] == $ID) { echo ' SELECTED';}
                echo ">$row[0] - $row[1]</option>";
            }
            echo '</select></div>';

            echo '<div class="form-group">';
            echo '<button name="submit" class="btn btn-default" type="submit">Submit</button>
                <input type="hidden" name="submitted" value="TRUE" />
                </div>
                </form>';
        }
    }

}

FannieDispatch::conditionalExec(false);

