<?php
require_once(dirname(__FILE__).'/../../../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class ViewsheetPage extends FanniePage {

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
                $vacaP = $ts_db->prepare_statement($vacaQ);
                $vacaR = $ts_db->exec_statement($vacaP,$args);
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

        $query = $ts_db->prepare_statement("SELECT ROUND(SUM(hours), 2),
            date_format(t.tdate, '%a %b %D'),
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
                AND t.area <> 31
                AND t.periodID = ?
                AND (t.vacation IS NULL OR t.vacation = 0)
                GROUP BY t.tdate");

        $periodQ = $ts_db->prepare_statement("SELECT periodStart, periodEnd FROM 
            {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods 
            WHERE periodID = ?");
        $periodR = $ts_db->exec_statement($periodQ,array($periodID));
        list($periodStart, $periodEnd) = $ts_db->fetch_row($periodR);

        $weekoneQ = $ts_db->prepare_statement("SELECT ROUND(SUM(hours), 2)
                FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
                INNER JOIN {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods AS p
                ON (p.periodID = t.periodID)
                WHERE t.emp_no = ?
                AND t.periodID = ?
                AND t.area <> 31
                AND t.tdate >= DATE(p.periodStart)
                AND t.tdate < DATE(date_add(p.periodStart, INTERVAL 7 day))");
        $weekoneR = $ts_db->exec_statement($weekoneQ,array($emp_no, $periodID));

        $weektwoQ = $ts_db->prepare_statement("SELECT ROUND(SUM(hours), 2)
                FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
                INNER JOIN {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods AS p
                ON (p.periodID = t.periodID)
                WHERE t.emp_no = ?
                AND t.periodID = ?
                AND t.area <> 31
                AND t.tdate >= DATE(date_add(p.periodStart, INTERVAL 7 day)) AND t.tdate <= DATE(p.periodEnd)");
        $weektwoR = $ts_db->exec_statement($weektwoQ,array($emp_no, $periodID));

        $vacationQ = "SELECT ROUND(hours, 2), ID
            FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
            WHERE t.emp_no = $emp_no
            AND t.periodID = $periodID
            AND t.area = 31";

        $WageQ = $ts_db->prepare_statement("SELECT pay_rate FROM {$FANNIE_OP_DB}.employees WHERE emp_no = ?");

        $WageR = $ts_db->exec_statement($WageQ, array($emp_no));

        list($weekone) = $ts_db->fetch_row($weekoneR);
        if (is_null($weekone)) $weekone = 0;
        list($weektwo) = $ts_db->fetch_row($weektwoR);
        if (is_null($weektwo)) $weektwo = 0;

        $vacation=0;
        $vacationID=0;
        if ($ts_db->num_rows($vacationR) != 0) 
            list($vacation, $vacationID) = $ts_db->fetch_row($vacationR);

        list($Wage) = $ts_db->fetch_row($WageR);
        if (is_null($Wage)) $Wage = 0; 

        $nameQ = $ts_db->prepare_statement("SELECT firstName FROM {$FANNIE_OP_DB}.employees WHERE emp_no=?");
        $nameR = $ts_db->exec_statement($nameQ,array($emp_no));
        list($name) = $ts_db->fetch_row($nameR);

        /**
          I merged two sections a bit here rather than having separate
          sections for when $query finds rows and when it doesn't
        */

        echo "<p>Timesheet for $name from " . date_format(date_create($periodStart), 'F dS, Y') .
             " to " . date_format(date_create($periodEnd), 'F dS, Y') . ":  
            <span style='font-size:0.9em;'><a href='TsStaffMemReport.php?emp_no=$emp_no&period=$periodID&run=run'>View 
                Staff Member Totals</a></span>
            </p>
            <table cellpadding=\"4\">
            <tr><th>Date</th><th>Total Hours Worked</th><th></th></tr>\n";

        $result = $ts_db->exec_statement($query,array($emp_no,$periodID));
        
        $periodHours = 0.00;
        while ($row = $ts_db->fetch_array($result)) {
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
        include ('./includes/header.html');

        if ($this->display_func == 'ts_error')
            return $this->error_contents();
        elseif ($this->display_func == 'ts_show')
            return $this->show_sheet(FormLib::get_form_value('emp_no'),FormLib::get_form_value('period'));

        echo "<body onLoad='putFocus(0,0);'>";
        $query = $ts_db->prepare_statement("SELECT FirstName, LastName, emp_no 
            FROM {$FANNIE_OP_DB}.employees where EmpActive=1 ORDER BY FirstName ASC");
        $result = $ts_db->exec_statement($query);
        echo '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';

        if ($_SESSION['logged_in'] == True) {
            echo '<p>Name: <select name="emp_no">
                <option>Select staff member</option>';
            while ($row = $ts_db->fetch_array($result)) {
                echo "<option value='".$row[2]."'>" . ucwords($row[0]) . " " . ucwords(substr($row[1],0,1)) . ".</option>\n";
            }
            echo '</select></p>';
        } 
        else {
            echo "<p>Employee Number*: <input type='text' name='emp_no' size=4 autocomplete='off' /></p>";
        }
        $currentQ = $ts_db->prepare_statement("SELECT periodID 
            FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods 
            WHERE ".$ts_db->now()." BETWEEN periodStart AND periodEnd");
        $currentR = $ts_db->exec_statement($currentQ);
        list($ID) = $ts_db->fetch_row($currentR);

        $query = $ts_db->prepare_statement("SELECT date_format(periodStart, '%M %D, %Y'), 
            date_format(periodEnd, '%M %D, %Y'), periodID 
            FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods 
            WHERE periodStart < ".$ts_db->now()." ORDER BY periodID DESC");
        $result = $ts_db->exec_statement($query);

        echo '<p>Pay Period: <select name="period">
            <option>Please select a payperiod to view.</option>';

        while ($row = $ts_db->fetch_array($result)) {
            echo "<option value=\"$row[2]\"";
            if ($row[2] == $ID) { echo ' SELECTED';}
            echo ">($row[0] - $row[1])</option>";
        }
        echo '</select></p>';

        echo '<button name="submit" type="submit">Submit</button>
        <input type="hidden" name="submitted" value="TRUE" />
        </form>';
        if ($this->current_user){
            echo "<div class='log_btn'><a href='" . $FANNIE_URL . "auth/ui/loginform.php?logout=1'>logout</a></div>";
        } 
        else {
            echo "<div class='log_btn'><a href='" . $_SERVER["PHP_SELF"] . "?login=1'>Login</a></div>";     //   class='loginbox'
        }
    }
}

FannieDispatch::conditionalExec(false);

?>
