<?php
require_once(dirname(__FILE__).'/../../../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class PayrollDetail extends FanniePage {
    protected $title = 'Payroll Detail';
    protected $header = 'Payroll Detail';

    function preprocess(){
        $this->add_css_file('includes/style.css');
        return True;
    }   

    function body_content(){
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;
        $ts_db = FannieDB::get($FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']);
        $periodID = FormLib::get_form_value('periodID',False);
        $emp_no = FormLib::get_form_value('emp_no',False);
        if (is_numeric($periodID) && is_numeric($emp_no)) { // If submitted.
            $query = $ts_db->prepare_statement("SELECT ROUND(SUM(TIMESTAMPDIFF(MINUTE, t.time_in, t.time_out))/60, 2),
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
                WHERE t.emp_no = ?
                AND t.periodID = ?
                AND t.area <> 13
                GROUP BY t.date");

            $weekoneQ = $ts_db->prepare_statement("SELECT ROUND(SUM(TIMESTAMPDIFF(MINUTE, t.time_in, t.time_out))/60, 2)
                FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
                INNER JOIN {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods AS p
                ON (p.periodID = t.periodID)
                WHERE t.emp_no = ?
                AND t.periodID = ?
                AND t.area <> 13
                AND t.date >= DATE(p.periodStart)
                AND t.date < DATE(date_add(p.periodStart, INTERVAL 7 day))");

            $weektwoQ = $ts_db->prepare_statement("SELECT ROUND(SUM(TIMESTAMPDIFF(MINUTE, t.time_in, t.time_out))/60, 2)
                FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
                INNER JOIN {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods AS p
                ON (p.periodID = t.periodID)
                WHERE t.emp_no = ?
                AND t.periodID = ?
                AND t.area <> 13
                AND t.date >= DATE(date_add(p.periodStart, INTERVAL 7 day)) AND t.date <= DATE(p.periodEnd)");

            $vacationQ = $ts_db->prepare_statement("SELECT ROUND(vacation, 2)
                FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
                WHERE t.emp_no = ?
                AND t.periodID = ?
                AND t.area = 13");

            $weekoneR = $ts_db->exec_statement($weekoneQ,array($emp_no,$periodID));
            $weektwoR = $ts_db->exec_statement($weektwoQ,array($emp_no,$periodID));
            $vacationR = $ts_db->exec_statement($vacationQ,array($emp_no,$periodID));

            list($weekone) = $ts_db->fetch_row($weekoneR);
            if (is_null($weekone)) $weekone = 0;
            list($weektwo) = $ts_db->fetch_row($weektwoR);
            if (is_null($weektwo)) $weektwo = 0;
            if ($ts_db->num_rows($vacationR) != 0) {
            list($vacation) = $ts_db->fetch_row($vacationR);
            } elseif (is_null($vacation)) {
            $vacation = 0;
            } else {
                $vacation = 0;
            }
    
            $result = $ts_db->exec_statement($query,array($emp_no,$periodID));
            if ($ts_db->num_rows($result) > 0) {
                ob_start();
                $first = TRUE;
                $periodHours = 0;
                while ($row = $ts_db->fetch_row($result)) {
                    if ($first == TRUE) {
                        echo "<p>Timesheet for $row[3] from $row[4] to $row[5]:</p>";
                        echo '<table><tr><th>Date</th><th>Total Hours Worked</th><th></th></tr>';
                    }
                    if ($row[0] > 24) {$fontopen = '<font color="red">'; $fontclose = '</font>';} 
                    else {$fontopen = NULL; $fontclose = NULL;}
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
                return ob_get_clean();
            } 
            else {
                return '<p>There is no timesheet available for that pay period.</p>';
            }

        }

        return 'Error: no data provided';
    }
}

FannieDispatch::conditionalExec(false);

?>
