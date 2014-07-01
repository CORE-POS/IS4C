<?php
require_once(dirname(__FILE__).'/../../../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class TsStaffMemReport extends FanniePage {

    protected $auth_classes = array('timesheet_access');

    function preprocess(){
        $this->title = "Timeclock - Staff Member Totals Report";
        $this->header = "Timeclock - Staff Member Totals Report";
        if (!$this->current_user && $_GET['login'] == 1 ){
            $this->loginRedirect();
            return False;
        }
        return True;
    }

    function css_content(){
        ob_start();
        ?>
        table th {
            font-size: 8px;
            text-transform: uppercase;
        }
        <?php
        return ob_get_clean();
    }

    function body_content(){
        global $FANNIE_OP_DB, $FANNIE_PLUGIN_SETTINGS, $FANNIE_URL;
        $ts_db = FannieDB::get($FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']);

        include ('./includes/header.html');
        //  FULL TIME: Number of hours per week
        $ft = 40;
        echo '<form action="'.$_SERVER['PHP_SELF'].'" method=GET>';
        $stored = ($_COOKIE['timesheet']) ? $_COOKIE['timesheet'] : '';
        if ($_SESSION['logged_in'] == True) {
            echo '<p>Name: <select name="emp_no">
            <option value="error">Select staff member</option>' . "\n";
    
            $query = $ts_db->prepare_statement("SELECT FirstName, 
                IF(LastName='','',CONCAT(SUBSTR(LastName,1,1),\".\")), emp_no 
                FROM ".$FANNIE_OP_DB.".employees where EmpActive=1 ORDER BY FirstName ASC");
            $result = $ts_db->exec_statement($query);
            while ($row = $ts_db->fetch_array($result)) {
                echo "<option value=\"$row[2]\">$row[0] $row[1]</option>\n";
            }
            echo '</select>&nbsp;&nbsp;*</p>';
        } 
        else {
            echo "<p>Employee Number*: <input type='text' name='emp_no' value='$stored' size=4 autocomplete='off' /></p>";
        }


        $currentQ = $ts_db->prepare_statement("SELECT periodID 
                FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods 
                WHERE ".$ts_db->now()." BETWEEN periodStart AND periodEnd");
        $currentR = $ts_db->exec_statement($currentQ);
        list($ID) = $ts_db->fetch_row($currentR);

        $query = $ts_db->prepare_statement("SELECT date_format(periodStart, '%M %D, %Y') as periodStart, 
            date_format(periodEnd, '%M %D, %Y') as periodEnd, periodID 
            FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods 
            WHERE periodStart < ".$ts_db->now()." ORDER BY periodID DESC");
        $result = $ts_db->exec_statement($query);

        echo '<p>Starting Pay Period: <select name="period">
            <option>Please select a starting pay period.</option>';

        while ($row = $ts_db->fetch_array($result)) {
            echo "<option value=\"" . $row['periodID'] . "\"";
            if ($row['periodID'] == $ID) { echo ' SELECTED';}
            echo ">(" . $row['periodStart'] . " - " . $row['periodEnd'] . ")</option>";
        }

        echo "</select><br />";
        echo '<p>Ending Pay Period: <select name="end">
            <option value=0>Please select an ending pay period.</option>';
        $result = $ts_db->exec_statement($query);
        while ($row = $ts_db->fetch_array($result)) {
            echo "<option value=\"" . $row['periodID'] . "\"";
            if ($row['periodID'] == $ID) { echo ' SELECTED';}
            echo ">(" . $row['periodStart'] . " - " . $row['periodEnd'] . ")</option>";
        }
        echo '</select><button value="run" name="run">Run</button></p></form>';
        if (FormLib::get_form_value('run','') == 'run') {
    
            $emp_no = FormLib::get_form_value('emp_no',0);
            $namesq = $ts_db->prepare_statement("SELECT e.emp_no, e.FirstName, e.LastName, e.pay_rate, JobTitle 
                FROM employees e WHERE e.emp_no = ? AND e.empActive = 1");
            $namesr = $ts_db->exec_statement($namesq,array($_GET['emp_no']));
    
            if (!$namesr) {
                echo "<div id='alert'><h1>Error!</h1><p>Incorrect, invalid, or inactive employee number entered.</p>
                    <p><a href='".$_SERVER['PHP_SELF']."'>Please try again</a></p></div>";
            } 
            else {
                $name = $ts_db->fetch_row($namesr);
        
                setcookie("timesheet", $emp_no, time()+60*3);
        
                $periodID = FormLib::get_form_value('period',0);
                $end = FormLib::get_form_value('end',$periodID);
                if ($end == 0) $end = $periodID;

                $query1 = $ts_db->prepare_statement("SELECT date_format(periodStart, '%M %D, %Y') as periodStart, 
                    periodID as pid 
                    FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods 
                    WHERE periodID = ?");
                $result1 = $ts_db->exec_statement($query1,array($periodID));
                $periodStart = $ts_db->fetch_row($result1);

                $query2 = $ts_db->prepare_statement("SELECT date_format(periodEnd, '%M %D, %Y') as periodEnd, 
                    periodID as pid 
                    FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods WHERE periodID = ?");
                $result2 = $ts_db->exec_statement($query2,array($end));
                $periodEnd = $ts_db->_fetch_row($result2);
                $p = array();
                for ($i = $periodStart[1]; $i < $periodEnd[1]; $i++) {
                    $p[] = $i;
                }

                $firstppP = $ts_db->prepare_statement("SELECT MIN(periodID) 
                    FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods 
                    WHERE YEAR(periodStart) = YEAR(".$ts_db->now().")");
                $firstppR = $ts_db->exec_statement($firstppP);
                $firstpp = $ts_db->fetch_row($firstppR);
                $y = array();
                for ($i = $firstpp[0]; $i <= $periodEnd[1]; $i++) {
                    $y[] = $i;
                }

                // $sql_incl = "";
                // $sql_excl = "AND emp_no <> 9999";
                $staffQ = $ts_db->prepare_statement("SELECT * FROM employees WHERE emp_no = ?");
                $staffR = $ts_db->exec_statement($staffQ,array($emp_no));
                $staff = $ts_db->fetch_row($staffR);

                echo "<h2>$emp_no &mdash; ".$staff['FirstName']." ". $staff['LastName']."</h2>";

                // BEGIN TITLE
                // 
                $query1 = $ts_db->prepare_statement("SELECT date_format(periodStart, '%M %D, %Y') as periodStart, 
                    periodID as pid, DATE(periodStart) 
                    FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods 
                    WHERE periodID = ?");
                $result1 = $ts_db->exec_statement($query1,array($periodID));
                $periodStart = $ts_db->fetch_row($result1);

                $query2 = $ts_db->prepare_statement("SELECT date_format(periodEnd, '%M %D, %Y') as periodEnd, 
                    periodID as pid, DATE(periodEnd) 
                    FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods 
                    WHERE periodID = ?");
                $result2 = $ts_db->exec_statement($query2,array($end));
                $periodEnd = $ts_db->fetch_row($result2);

                // $periodct = ($end !== $periodID) ? $end - $periodID : 1;
                for ($i = $periodStart[1]; $i <= $periodEnd[1]; $i++) {
                    // echo $i;
                    $periodct++;
                    $p[] = $i;
                }
                echo "<h3>" . $periodStart[0] . " &mdash; " . $periodEnd[0] . "</h3>\n";
                echo "Number of payperiods: " . $periodct . "\n";
                // 
                // END TITLE    
                echo "<br />";

                $areasq = $ts_db->prepare_statement("SELECT ShiftName, ShiftID 
                    FROM ".$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].".shifts 
                    WHERE visible = 1 ORDER BY ShiftOrder");
                $areasr = $ts_db->exec_statement($areasq);

                $shiftInfo = array();
                echo "<table border='1' cellpadding='5' cellspacing=0><thead>\n<tr><th>Week</th><th>Name</th><th>Wage</th>";
                while ($areas = $ts_db->fetch_array($areasr)) {
                    echo "<div id='vth'><th>" . substr($areas[0],0,6) . "</th></div>";  // -- TODO vertical align th, static col width
                    $shiftInfo[$areas['ShiftID']] = $areas['ShiftName'];
                }
                echo "</th><th>PTO new</th><th>Total</th><th>OT</th></tr></thead>\n<tbody>\n";
        
                $weekQ = $ts_db->prepare_statement("SELECT emp_no, area, tdate, periodID, 
                    hours, WEEK(tdate) as week_number 
                    FROM ".$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].".timesheet 
                    WHERE emp_no = ?
                    AND tdate >= ? AND tdate <= ?
                    GROUP BY WEEK(tdate)");
                $weekR = $ts_db->exec_statement($weekQ,array($emp_no,$periodStart[2],$periodEnd[2]));

                $totalP = $ts_db->prepare_statement("SELECT SUM(hours) FROM ".
                    $FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].".timesheet 
                    WHERE periodID >= ? AND periodID <= ? AND emp_no = ?");
                $depttotP = $ts_db->prepare_statement("SELECT SUM(t.hours) 
                    FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet t 
                    WHERE WEEK(t.tdate) = ? AND t.emp_no = ? AND t.area = ?");
                $nonPTOtotalP = $ts_db->prepare_statement("SELECT SUM(hours) FROM ".
                    $FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].".timesheet 
                    WHERE periodID >= ? AND periodID <= ? AND area <> 31 
                    AND emp_no = ?");
                $weekoneP = $ts_db->prepare_statement("SELECT ROUND(SUM(hours), 2) 
                    FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
                    INNER JOIN {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods 
                    AS p ON (p.periodID = t.periodID)
                    WHERE t.emp_no = ?
                    AND t.periodID = ?
                    AND t.area <> 31
                    AND t.tdate >= DATE(p.periodStart)
                    AND t.tdate < DATE(date_add(p.periodStart, INTERVAL 7 day))");
                $weektwoP = $ts_db->prepare_statement("SELECT ROUND(SUM(hours), 2)
                    FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
                    INNER JOIN {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods AS p
                    ON (p.periodID = t.periodID)
                    WHERE t.emp_no = ?
                    AND t.periodID = ?
                    AND t.area <> 31
                    AND t.tdate >= DATE(date_add(p.periodStart, INTERVAL 7 day)) 
                    AND t.tdate <= DATE(p.periodEnd)");
                while ($row = $ts_db->fetch_row($weekR)) {
                    $week_no = $row['week_number'];
                    $emp_no = $row['emp_no'];
            
                    $totalr = $ts_db->exec_statement($totalP,array($periodID,$end,$emp_no));
                    $total = $ts_db->fetch_row($totalr);
                    $color = ($total[0] > (80 * $periodct)) ? "FF0000" : "000000";
                    echo "<tr><td>$week_no</td>";
                    echo "<td>".ucwords($name['FirstName'])." - " . ucwords(substr($name['FirstName'],0,1)) . ucwords(substr($name['LastName'],0,1)) . "</td><td align='right'>$" . $name['pay_rate'] . "</td>";
                    $total0 = (!$total[0]) ? 0 : number_format($total[0],2);


                    //
                    //  LABOR DEPARTMENT TOTALS
                    foreach($shiftInfo as $area => $shiftName){ 
                        // echo $depttotq;
                        $depttotr = $ts_db->exec_statement($depttotq,array($week_no,$emp_no,$area));
                        $depttot = $ts_db->fetch_row($depttotr);
                        $depttotal = (!$depttot[0]) ? 0 : number_format($depttot[0],2);
                        echo "<td align='right'>" . $depttotal . "</td>";
                    }
                    //  END LABOR DEPT. TOTALS


                    //  TOTALS column
                    // echo "<td align='right'><font style='color: $color; font-weight:bold;'>" . $total0 . "</font></td>";

                    //
                    //  PTO CALC
                    $nonPTOtotalr = $ts_db->exec_statement($nonPTOtotalP,array($periodID,$end,$emp_no));
                    $nonPTOtotal = $ts_db->fetch_row($nonPTOtotalr);
                    $ptoAcc = ($row['JobTitle'] == 'STAFF') ? $nonPTOtotal[0] * 0.075 : 0;
                    echo "<td align='right'>" . number_format($ptoAcc,2) . "</td>";


                    echo "<td align='right'><font style='color: $color; font-weight:bold;'>" . $total0 . "</font></td>";

                    // 
                    //  OVERTIME
                    // 
                    $otime1 = array();
                    $otime2 = array();
                    foreach ($p as $v) {


                        $weekoneR = $ts_db->exec_statement($weekoneP,array($emp_no,$v));
                        $weektwoR = $ts_db->exec_statement($weektwoP,array($emp_no,$v));

                        list($weekone) = $ts_db->fetch_row($weekoneR);
                        if (is_null($weekone)) $weekone = 0;
                        list($weektwo) = $ts_db->fetch_row($weektwoR);
                        if (is_null($weektwo)) $weektwo = 0;

                        if ($weekone > $ft) $otime1[] = $weekone - $ft;
                        if ($weektwo > $ft) $otime2[] = $weektwo - $ft;
                        // $otime = $otime + $otime1 + $otime2;

                    }
                    $ot1 = array_sum($otime1);
                    $ot2 = array_sum($otime2);
                    $otime = $ot1 + $ot2;
                    // print_r($p);
                    echo "<td align='right'>" . $otime . "</td>";
                    $otime = 0;
                    $otime1 = array();
                    $otime2 = array();
                    //  END OVERTIME
                    echo "</tr>";

                }
                echo "</tbody></table>\n";
            }
    
        } // end 'run' button 

        if ($this->current_user){
            echo "<div class='log_btn'><a href='" . $FANNIE_URL . "auth/ui/loginform.php?logout=1'>logout</a></div>";
        } else {
            echo "<div class='log_btn'><a href='" . $_SERVER["PHP_SELF"] . "?login=1'>login</a></div>";  //   class='loginbox'
        }
    }


}

FannieDispatch::conditionalExec(false);

?>
