<?php
include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ViewsheetPage extends FanniePage {
    public $page_set = 'Plugin :: TimesheetPlugin';

    protected $auth_classes = array('timesheet_access');

    private $errors;
    private $display_func;

    function preprocess(){
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;
        $ts_db = FannieDB::get($FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']);

        if (!$this->current_user && $_GET['login'] == 1 ){
            $this->loginRedirect();
            return False;
        }
        // $loggedin = (isset($_COOKIE['verify'])) ? True : False;

        $this->header = 'Timesheet Management' . $_GET['login'];
        $this->title = 'Fannie - Administration Module';
        $this->errors = array();
        $this->display_func = '';

        $emp_no = FormLib::get_form_value('emp_no','');
        $periodID = FormLib::get_form_value('period','');
        $submitted = FormLib::get_form_value('submitted',False);
        $addvaca = FormLib::get_form_value('addvaca',False);

        if ($submitted && $emp_no && is_numeric($emp_no) && $periodID && is_numeric($periodID)) {
            $this->display_func = 'ts_show';
        }
        elseif (isset($_POST['addvaca'])) {
            $errors = array();
            $emp = FormLib::get_form_value('emp','');
            $date = date('Y-m-d');
            $vaca = FormLib::get_form_value('vaca',0);
            $vacaID = FormLib::get_form_value('vacationID','');
            $perID = FormLib::get_form_value('period','');
            if (is_numeric($vaca)){
                $vaca = (float) $vaca;

                $roundvaca = explode('.', number_format($vaca, 2));

                if ($roundvaca[1] < 13) {$roundvaca[1] = 00;}
                elseif ($roundvaca[1] >= 13 && $roundvaca[1] < 37) {$roundvaca[1] = 25;}
                elseif ($roundvaca[1] >= 37 && $roundvaca[1] < 63) {$roundvaca[1] = 50;}
                elseif ($roundvaca[1] >= 63 && $roundvaca[1] < 87) {$roundvaca[1] = 75;}
                elseif ($roundvaca[1] >= 87) {$roundvaca[1] = 00; $roundvaca[0]++;}

                $vaca = number_format($roundvaca[0] . '.' . $roundvaca[1], 2);

            } 
            else {
                $errors[] = "Vacation hours to be used must be a number.";
                $vaca = False;
            }

            $vacaQ = '';
            $args = array();
            if ($vaca !== False && is_numeric($vacaID) && is_numeric($perID)) {
                $vacaID = (int) $vacaID;
                $perID = (int) $perID;
                $vacaQ = "UPDATE {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet 
                    SET tdate = ?, vacation = ? WHERE ID = ?";
                $args = array($date, $vaca, $vacaID);
            } 
            elseif ($vaca !== False && $vacaID == 'insert' && is_numeric($perID)) {
                $perID = (int) $perID;
                $vacaQ = "INSERT INTO {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet 
                    (emp_no, hours, area, vacation, tdate, periodID)
                    VALUES (?, ?, 31, ?, ?, ?)";
                $args = array($emp, $vaca, $vaca, $date, $perID);
            }

            if (empty($errors)) {
                $vacaP = $ts_db->prepare($vacaQ);
                $vacaR = $ts_db->execute($vacaP,$args);
                if ($vacaR) {
                    $url = $_SERVER['PHP_SELF']."?emp_no=$emp&period=$perID";
                    header("Location: $url");
                    return False;
                } 
                else {
                    $this->errors[] = 'The vacation hours could not be added due to a system error, please try again later.';
                    $this->display_func = 'ts_error';
                }
            } 
            else {
                $this->display_func = 'ts_error';
            }
        } 
        else if ($submitted) {
            $this->errors[] = 'You forgot to select your name.';
            $this->display_func = 'ts_error';
        }

        return True;
    }

    function error_contents(){
        include ('./includes/header.html');
        echo "<br /><br /><h1>The following errors occurred:</h1><ul>";

        foreach ($this->errors as $msg) {
            echo "<p>- $msg</p>";
        }
        echo "</ul><br /><br /><br />";
    }

    function show_sheet($emp_no, $periodID){
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;
        $ts_db = FannieDB::get($FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']);
        include ('./includes/header.html');

        $ft = 40;

        $query = $ts_db->prepare("
            SELECT ROUND(SUM(hours), 2),
                date_format(t.tdate, '%a %b %D'),
                t.emp_no,
                e.firstName,
                date_format(p.periodStart, '%M %D, %Y'),
                date_format(p.periodEnd, '%M %D, %Y'),
                t.tdate
            FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
                INNER JOIN {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.TimesheetEmployees AS e ON (t.emp_no = e.timesheetEmployeeID)
                INNER JOIN {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods AS p ON (t.periodID = p.periodID)
                WHERE t.emp_no = ?
                    AND t.area <> 31
                    AND t.periodID = ?
                    AND (t.vacation IS NULL OR t.vacation = 0)
                GROUP BY t.tdate");

        $periodQ = $ts_db->prepare("SELECT periodStart, periodEnd FROM 
            {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods 
            WHERE periodID = ?");
        $periodR = $ts_db->execute($periodQ,array($periodID));
        list($periodStart, $periodEnd) = $ts_db->fetch_row($periodR);

        $weekoneQ = $ts_db->prepare("
            SELECT ROUND(SUM(hours), 2)
            FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
                INNER JOIN {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods AS p ON (p.periodID = t.periodID)
            WHERE t.emp_no = ?
                AND t.periodID = ?
                AND t.area <> 31
                AND t.tdate >= DATE(p.periodStart)
                AND t.tdate < DATE(date_add(p.periodStart, INTERVAL 7 day))");
        $weekoneR = $ts_db->execute($weekoneQ,array($emp_no, $periodID));

        $weektwoQ = $ts_db->prepare("
            SELECT ROUND(SUM(hours), 2)
            FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
                INNER JOIN {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods AS p ON (p.periodID = t.periodID)
            WHERE t.emp_no = ?
                AND t.periodID = ?
                AND t.area <> 31
                AND t.tdate >= DATE(date_add(p.periodStart, INTERVAL 7 day)) 
                AND t.tdate <= DATE(p.periodEnd)");
        $weektwoR = $ts_db->execute($weektwoQ,array($emp_no, $periodID));

        $vacationQ = "
            SELECT ROUND(hours, 2), 
                ID
            FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
            WHERE t.emp_no = $emp_no
                AND t.periodID = $periodID
                AND t.area = 31";

        $employee = new TimesheetEmployeesModel($ts_db);
        $employee->timesheetEmployeeID($emp_no);
        $employee->load();
        $Wage = $employee->wage();
        if (is_null($Wage)) $Wage = 0; 
        $name = $employee->firstName();

        list($weekone) = $ts_db->fetch_row($weekoneR);
        if (is_null($weekone)) $weekone = 0;
        list($weektwo) = $ts_db->fetch_row($weektwoR);
        if (is_null($weektwo)) $weektwo = 0;

        $vacation=0;
        $vacationID=0;
        if ($ts_db->num_rows($vacationR) != 0) 
            list($vacation, $vacationID) = $ts_db->fetch_row($vacationR);

        /**
          I merged two sections a bit here rather than having separate
          sections for when $query finds rows and when it doesn't
        */

        echo "<p>Timesheet for $name from " . date_format(date_create($periodStart), 'F dS, Y') .
             " to " . date_format(date_create($periodEnd), 'F dS, Y') . ":  
            <span style='font-size:0.9em;'><a href='TsStaffMemReport.php?emp_no=$emp_no&period=$periodID&run=run'>View 
                Staff Member Totals</a></span>
            </p>
            <table class=\"table table-striped table-bordered\">
            <tr><th>Date</th><th>Total Hours Worked</th><th></th></tr>\n";

        $result = $ts_db->execute($query,array($emp_no,$periodID));
        $periodHours = 0.00;
        while ($row = $ts_db->fetchRow($result)) {
            if ($row[0] > 24) {
                $fontopen = '<font color="red">'; $fontclose = '</font>';
            } 
            else {
                $fontopen = NULL; 
                $fontclose = NULL;
            }
            echo "<tr><td>$row[1]</td><td>$fontopen$row[0]$fontclose</td><td>
                <a href=\"EditTimesheetDatePage.php?emp_no=$emp_no&date=$row[6]&periodID=$periodID\">(Edit)</a></td></tr>\n";
            $first = FALSE;
            $periodHours += $row[0];
        }
        if ($periodHours == 0.00)
            echo '<tr><td colspan="3">No hours this period</td></tr>';

        $roundhour = explode('.', number_format($periodHours, 2));
        if ($roundhour[1] < 13) {$roundhour[1] = 00;}
        elseif ($roundhour[1] >= 13 && $roundhour[1] < 37) {$roundhour[1] = 25;}
        elseif ($roundhour[1] >= 37 && $roundhour[1] < 63) {$roundhour[1] = 50;}
        elseif ($roundhour[1] >= 63 && $roundhour[1] < 87) {$roundhour[1] = 75;}
        elseif ($roundhour[1] >= 87) {$roundhour[1] = 00; $roundhour[0]++;}

        $periodHours = number_format($roundhour[0] . '.' . $roundhour[1], 2);

        echo "</table>
        <form action='" .  $_SERVER['PHP_SELF'] . "' method='POST'>
        <p>Total hours in this pay period: " . number_format($periodHours, 2) . "</p>
        <table border=0 cellpadding='5'><tr><td>Week One: </td><td>";
        if ($weekone > $ft) {echo '<font color="red">'; $font = '</font>';} else {$font = NULL;}
        echo number_format($weekone, 2) . $font . "</td>";
        $ot1 = (($weekone - $ft) > 0) ? $weekone - $ft : 0;
        if ($ot1 > 0) echo "<td>OT: $ot1</td>";
        echo "</tr>";
        echo "<tr><td>Week Two: </td><td>";
        if ($weektwo > $ft) {echo '<font color="red">'; $font = '</font>';} else {$font = NULL;}
        echo number_format($weektwo, 2) . $font . "</td>";
        $ot2 = (($weektwo - $ft) > 0) ? $weektwo - $ft : 0;
        if ($ot2 > 0) echo "<td>OT: $ot2</td>";
        echo "</tr>";
        // echo "<td><b>Coming Soon-</b>Amount House Charged: $" . number_format($houseCharge, 2) . "</td></tr>";
        echo "</tr><tr><td>Paid Time Off (PTO): </td><td>";
        echo "<input type='text' name='vacation' size='5' maxlength='5' value='" . number_format($vacation, 2) . "' />" . $font . "
            <input type='hidden' name='vacationID' value='$vacationID' />
            <input type='hidden' name='period' value='$periodID' />
            <input type='hidden' name='emp' value='$emp_no' /></td>
            <!-- <td><button name='addvaca' type='submit'>Use PTO Hours</button></td> -->
            </tr>";
        $otime = ($ot1 + $ot2) * 1.5;
        $week1 = ($weekone > $ft) ? $ft : $weekone;
        $week2 = ($weektwo > $ft) ? $ft : $weektwo;
        $gw = $Wage * ($week1 + $week2 + $vacation + $otime);
        
        echo "<tr><td>Gross Wages (before taxes): </td><td>$" . number_format($gw, 2) . "</td></tr>";
        echo "</table></form><br />";
    }

    function body_content(){
        global $FANNIE_OP_DB, $FANNIE_PLUGIN_SETTINGS, $FANNIE_URL;
        $ts_db = FannieDB::get($FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']);

        if ($this->display_func == 'ts_error')
            return $this->error_contents();
        elseif ($this->display_func == 'ts_show')
            return $this->show_sheet(FormLib::get_form_value('emp_no'),FormLib::get_form_value('period'));

        include ('./includes/header.html');
        echo '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';

        echo '<div class="form-group">';
        if ($this->session->logged_in == True) {
            echo '<label>Name</label><select name="emp_no" class="form-control">
                <option>Select staff member</option>';
            $model = new TimesheetEmployeesModel($ts_db);
            $model->active(1);
            foreach ($model->find('firstName') as $obj) {
                printf('<option value="%d">%s %s</option>',
                    $obj->timesheetEmployeeID(),
                    $obj->firstName(),
                    substr($obj->lastName(), 0, 1)
                );
            }
            echo '</select>';
        } else {
            echo "<label>Employee Number*</label>
                <input type='text' name='emp_no' size=4 
                class=\"form-control\" autocomplete='off' />";
        }
        echo '</div>';

        $currentQ = $ts_db->prepare("SELECT periodID 
            FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods 
            WHERE ".$ts_db->now()." BETWEEN periodStart AND periodEnd");
        $currentR = $ts_db->execute($currentQ);
        list($ID) = $ts_db->fetch_row($currentR);

        $query = $ts_db->prepare("SELECT date_format(periodStart, '%M %D, %Y'), 
            date_format(periodEnd, '%M %D, %Y'), periodID 
            FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods 
            WHERE periodStart < ".$ts_db->now()." ORDER BY periodID DESC");
        $result = $ts_db->execute($query);

        echo '<div class="form-group">';
        echo '<label>Pay Period</label><select name="period" class="form-control">
            <option>Please select a payperiod to view.</option>';

        while ($row = $ts_db->fetchRow($result)) {
            echo "<option value=\"$row[2]\"";
            if ($row[2] == $ID) { echo ' SELECTED';}
            echo ">($row[0] - $row[1])</option>";
        }
        echo '</select></div>';

        echo '<div class="form-group">';
        echo '<button name="submit" type="submit" class="btn btn-default">Submit</button>
        <input type="hidden" name="submitted" value="TRUE" />
        </div></form>';
        if ($this->current_user){
            echo "<div class='log_btn'><a href='" . $FANNIE_URL . "auth/ui/loginform.php?logout=1'>logout</a></div>";
        } 
        else {
            echo "<div class='log_btn'><a href='" . $_SERVER["PHP_SELF"] . "?login=1'>Login</a></div>";     //   class='loginbox'
        }
    }
}

FannieDispatch::conditionalExec(false);

