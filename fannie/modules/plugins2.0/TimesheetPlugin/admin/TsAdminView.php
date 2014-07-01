<?php
require_once(dirname(__FILE__).'/../../../../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class TsAdminView extends FanniePage {
    protected $header = 'Timesheet Management';
    protected $title = 'Fannie - Administration Module';

    private $emp_no;
    private $periodID;
    private $errors = array();

    function javascript_content(){
        ob_start();
        ?>
    function toggleTable(id, obj) {
        if (id == 'all') {
            div = document.getElementById(id);
            tables = div.getElementsByTagName("table");
            for (var b=0; b < tables.length; b++) {
                rows = tables[b].getElementsByTagName("tr");
                header = rows[0].getElementsByTagName("th");
                anchor = header[0].getElementsByTagName("a");
                
                if (obj.innerHTML == 'Expand All') {
                    for (var i=1; i < rows.length; i++) {
                        rows[i].style.display = 'table-row';
                    }
                    anchor[0].innerHTML = '-';
                } else {
                    for (var i=1; i < rows.length; i++) {
                        rows[i].style.display = 'none';
                    }
                    anchor[0].innerHTML = '+';
                }
                
            }
            
            if (obj.innerHTML == 'Expand All') {
                obj.innerHTML = 'Collapse All';
            } else {
                obj.innerHTML = 'Expand All';
            }
            
        } else {
            rows = document.getElementById(id).getElementsByTagName("tr");
    
            header = rows[0].getElementsByTagName("th");
    
            anchor = header[0].getElementsByTagName("a");
    
            
            if (anchor[0].innerHTML == '-') {
                for (var i=1; i < rows.length; i++) {
                    rows[i].style.display = 'none';
                }
                anchor[0].innerHTML = '+';
                
            } else if (anchor[0].innerHTML == '+') {
                for (var i=1; i < rows.length; i++) {
                    rows[i].style.display = 'table-row';
                }
                
                anchor[0].innerHTML = '-';
            }
        }
    }
        <?php
        return ob_get_clean();
    }

    function preprocess(){
        global $FANNIE_OP_DB,$FANNIE_PLUGIN_SETTINGS;
        $ts_db = FannieDB::get($FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']);

        $this->emp_no = FormLib::get_form_value('emp_no',0);
        $this->periodID = FormLib::get_form_value('periodID',0);

        if ($this->emp_no == 0 || $this->periodID == 0 || $this->emp_no < 0){
            header('Location: TsAdminMain.php');
            return False;
        }

        if ($_GET['function'] == 'edit' && isset($_GET['submitted']) 
            && isset($_GET['emp_no']) && isset($_GET['periodID']) && isset($_GET['id'])) {

            $oneP = $ts_db->prepare_statement("UPDATE timesheet
                SET time_in=?, time_out=?, area=?
                WHERE ID=?");
            $twoP = $ts_db->prepare_statement("UPDATE timesheet
                SET time_in=?
                WHERE ID=?");
            foreach ($_GET['id'] AS $key => $id) {
        
                $area = (int) $_GET['area'][$id];
                $date = $_GET['date'][$id];
                $timein = $this->parseTime($_GET['time_in'][$id], $_GET['inmeridian'][$id]);
                $timeout = $this->parseTime($_GET['time_out'][$id], $_GET['outmeridian'][$id]);
            
                $result = False;
                if ($area != 0) {
                    $args = array(
                        $date.' '.$timein,
                        $date.' '.$timeout,
                        $area,
                        $id
                    );
                    $result = $ts_db->exec_statement($oneP,$args);
                } else {
                    $args = array(
                        '2008-01-01 '.$timein,
                        $id
                    );
                    $result = $ts_db->exec_statement($twoP,$args);
                }
            
                if (!$result) {
                    $this->errors[] = "<p>Query: $query</p>";
                    $this->errors[] = "<p>MySQL Error: " . $ts_db->error() . "</p>";
                }
            }
        }
        return True;
    }

    function body_content(){
        global $FANNIE_OP_DB,$FANNIE_PLUGIN_SETTINGS;
        $ts_db = FannieDB::get($FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']);

        $emp_no = (int) $this->emp_no;
        $periodID = (int) $this->periodID;
    
        $mainQ = $ts_db->prepare_statement("SELECT date, DATE_FORMAT(date, '%M %D'), 
            ROUND(SUM(TIMESTAMPDIFF(MINUTE, time_in, time_out))/60, 2)
            FROM timesheet
            WHERE emp_no = ?
            AND periodID = ?
            GROUP BY date");
        $mainR = $ts_db->exec_statement($mainQ, array($emp_no,$periodID));
        
        $nameQ = $ts_db->prepare_statement("SELECT firstname FROM ".
            $FANNIE_OP_DB.$ts_db->sep()."employees WHERE emp_no=?");
        $nameR = $ts_db->exec_statement($nameQ, array($emp_no));
        list($name) = $ts_db->fetch_row($nameR);
        
        $periodQ = $ts_db->prepare_statement("SELECT 
            date_format(periodStart, '%M %D, %Y'), date_format(periodEnd, '%M %D, %Y')
            FROM payperiods WHERE periodID=?");
        $periodR = $ts_db->exec_statement($periodQ, array($periodID));
        $period = $ts_db->fetch_row($periodR);

        $query = $ts_db->prepare_statement("SELECT CASE area WHEN 0 THEN TIME_FORMAT(time_in, '%H:%i') 
            ELSE TIME_FORMAT(time_in, '%r') END,
            CASE area WHEN 0 THEN time_out ELSE TIME_FORMAT(time_out, '%r') END,
            area,
            ID
            FROM timesheet
            WHERE emp_no = ?
            AND area <> 31
            AND periodID = ?
            AND date = ?");
        $shiftP = $ts_db->prepare_statement("SELECT * FROM shifts WHERE ShiftID 
                NOT IN (0,31) ORDER BY ShiftID ASC");
        
        ob_start();
        echo '<form action="' . $_SERVER['PHP_SELF'] . '" method="get"><fieldset>
            <legend>Timesheet For ' . $name . ' from ' . $period[0] . ' to ' . $period[1] . '</legend>
            <input type="hidden" name="function" value="edit" /><a href="#" id="mainAnchor" 
            onclick="toggleTable(\'all\', this)">Expand All</a><div id="all">';
        while ($mainRow = $ts_db->fetch_row($mainR)) {
            echo '<table cellpadding="3" cellspacing="3" id="' . $mainRow[0] . '">
                <tr class="header">
                <th align="left"><a href="#" onclick="toggleTable(\'' . $mainRow[0] . '\', this)">+</a></th>
                <th align="left">' . $mainRow[1] . '</th>
                <th><a href="TsAdminDelete?date=' . $mainRow[0] . 
                    '&emp_no=' . $emp_no . '&periodID=' . $periodID . '">Delete</a></th>
                <th align="right" colspan="2">' . $mainRow[2] . ' Hours</th>
            </tr>';
            $result = $ts_db->exec_statement($query, array($emp_no, $periodID, $mainRow[0]));
            if (!$result) echo "<p>Error!</p><p>Query: $query</p><p>" . $ts_db->error() . "</p>";
            while ($row = $ts_db->fetch_row($result)) {
                if ($row[2] == 0) {
                    $lunch = $row[0];
                    $lunchID = $row[3];
                    echo '<tr class="details" style="display:none">
                        <td>&nbsp;</td>
                        <td colspan="2" align="right">Lunch</td>
                        <td colspan="2" align="left">
                        <input type="hidden" name="date[' . $row[3] . ']" 
                        value="' . $mainRow[0] . '" />
                        <input type="hidden" name="area[' . $lunchID . ']" value="0" />
                        <input type="hidden" name="id[' . $lunchID . ']" 
                        value="' . $lunchID . '" />
                        <select name="time_in[' . $lunchID . ']">
                        <option value="00:00:00"';
                    if ($lunch == '00:00') echo ' SELECTED';
                    echo '>None</option>
                        <option value="00:15:00"';
                    if ($lunch == '00:15') echo ' SELECTED';
                    echo '>15 Minutes</option>
                        <option value="00:30:00"';
                    if ($lunch == '00:30') echo ' SELECTED';
                    echo '>30 Minutes</option>
                        <option value="00:45:00"';
                    if ($lunch == '00:45') echo ' SELECTED';
                    echo '>45 Minutes</option>
                        <option value="01:00:00"';
                    if ($lunch == '01:00') echo ' SELECTED';
                    echo '>1 Hour</option>
                        <option value="01:15:00"';
                    if ($lunch == '01:15') echo ' SELECTED';
                    echo '>1 Hour, 15 Minutes</option>
                        <option value="01:30:00"';
                    if ($lunch == '01:30') echo ' SELECTED';
                    echo '>1 Hour, 30 Minutes</option>
                        <option value="01:45:00"';
                    if ($lunch == '01:45') echo ' SELECTED';
                    echo '>1 Hour, 45 Minutes</option>
                        <option value="02:00:00"';
                    if ($lunch == '02:00') echo ' SELECTED';
                    echo '>2 Hours</option>
                        </select></td></tr>';
                } 
                else {
                    $in = substr($row[0], 9, 2);
                    $out = substr($row[1], 9, 2);

                    $shiftR = $ts_db->exec_statement($shiftP);
                    
                    echo '<tr class="details" style="display:none">
                        <td>
                        <input type="hidden" name="id[' . $row[3] . ']" 
                            value="' . $row[3] . '" />
                        <input type="hidden" name="date[' . $row[3] . ']" 
                            value="' . $mainRow[0] . '" /></td>
                        <td><input type="text" name="time_in[' . $row[3] . ']" 
                            size="5" maxlength="5" 
                            value="' . substr($row[0], 0, 5) . '" />
                        <select name="inmeridian[' . $row[3] . ']"><option value="AM"';
                    if ($in == 'AM') echo ' SELECTED';
                    echo '>AM</option><option value="PM"';
                    if ($in == 'PM') echo 'SELECTED';
                    echo '>PM</option></select>
                        </td>
                        <td><input type="text" name="time_out[' . $row[3] . ']" 
                            size="5" maxlength="5" 
                            value="' . substr($row[1], 0, 5) . '" />
                        <select name="outmeridian[' . $row[3] . ']"><option value="AM"';
                    if ($out == 'AM') echo ' SELECTED';
                    echo '>AM</option><option value="PM"';
                    if ($out == 'PM') echo 'SELECTED';
                    echo '>PM</option></select>
                        </td>
                        <td align="right"><select name="area[' . $row[3] . ']">';
                            
                    while ($shiftrow = $ts_db->fetch_row($shiftR)) {
                        echo "<option value=\"$shiftrow[1]\"";
                        if ($shiftrow[1] == $row[2]) {echo ' SELECTED';}
                        echo ">$shiftrow[0]</option>";
                    }
                    echo "</select>
                            </td>
                        </tr>";
                }
            }
            echo '</table>';
        }
        echo '</div>';
        
        $periodQ = $ts_db->prepare_statement("SELECT periodStart, periodEnd 
                    FROM payperiods WHERE periodID = ?");
        $periodR = $ts_db->exec_statement($periodQ, array($periodID));
        list($periodStart, $periodEnd) = $ts_db->fetch_row($periodR);
        
        $weekoneQ = $ts_db->prepare_statement("SELECT 
            ROUND(SUM(TIMESTAMPDIFF(MINUTE, t.time_in, t.time_out))/60, 2)
            FROM timesheet AS t
            INNER JOIN payperiods AS p
            ON (p.periodID = t.periodID)
            WHERE t.emp_no = ?
            AND t.periodID = ?
            AND t.area <> 31
            AND t.date >= DATE(p.periodStart)
            AND t.date < DATE(date_add(p.periodStart, INTERVAL 7 day))");
        
        $weektwoQ = $ts_db->prepare_statement("SELECT 
            ROUND(SUM(TIMESTAMPDIFF(MINUTE, t.time_in, t.time_out))/60, 2)
            FROM timesheet AS t
            INNER JOIN payperiods AS p
            ON (p.periodID = t.periodID)
            WHERE t.emp_no = ?
            AND t.periodID = ?
            AND t.area <> 31
            AND t.date >= DATE(date_add(p.periodStart, INTERVAL 7 day)) 
            AND t.date <= DATE(p.periodEnd)");

        $vacationQ = $ts_db->prepare_statement("SELECT ROUND(vacation, 2), ID
            FROM timesheet AS t
            WHERE t.emp_no = $emp_no
            AND t.periodID = $periodID
            AND t.area = 31");
            
        $WageQ = $ts_db->prepare_statement("SELECT pay_rate FROM ".
            $FANNIE_OP_DB.$ts_db->sep()."employees WHERE emp_no = ?");
        
        $weekoneR = $ts_db->exec_statement($weekoneQ, array($emp_no,$periodID));
        $weektwo = $ts_db->exec_statement($weektwoQ, array($emp_no,$periodID));
        $vacationR = $ts_db->exec_statement($vacationQ, array($emp_no,$periodID));
        $WageR = $ts_db->exec_statement($WageQ, array($emp_no));
        
        list($weekone) = $ts_db->fetch_row($weekoneR);
        if (is_null($weekone)) $weekone = 0;
        list($weektwo) = $ts_db->fetch_row($weektwoR);
        if (is_null($weektwo)) $weektwo = 0;
        
        $vacation = 0;
        $vacationID = 'insert';
        if ($ts_db->num_rows($vacationR) != 0) {
            list($vacation, $vacationID) = $ts_db->fetch_row($vacationR);
        }
        if (!isset($vacation) || is_null($vacation)) {
            $vacation = 0;
            $vacationID = 'insert';
        } 
        
        if (is_null($houseCharge)) $houseCharge = 0;
        list($Wage) = $ts_db->fetch_row($WageR);
        if (is_null($Wage)) $Wage = 0;
            
        echo "
        <p>Total hours in this pay period: " . number_format($weekone + $weektwo, 2) . "</p>
        <table cellpadding='5'><tr><td>Week One: ";
        if ($weekone > 40) {echo '<font color="red">'; $font = '</font>';} else {$font = NULL;}
        echo number_format($weekone, 2) . $font . "</td>";
        echo "<td>Gross Wages (before taxes): $" . number_format($Wage * ($weekone + $weektwo + $vacation), 2) . "</td></tr>";
        echo "<tr><td>Week Two: ";
        if ($weektwo > 40) {echo '<font color="red">'; $font = '</font>';} else {$font = NULL;}
        echo number_format($weektwo, 2) . $font . "</td>";
        echo "<td>Amount House Charged: $" . number_format($houseCharge, 2) . "</td></tr>";
        echo "<tr><td>Vacation Hours: ";
        if ($vacation > 0) {echo '<font color="red">'; $font = '</font>';} else {$font = NULL;}
        echo number_format($vacation, 2) . $font;
        
        echo '
            </td></tr></table><input type="hidden" name="submitted" value="true" />
            <input type="hidden" name="emp_no" value="' . $_GET['emp_no'] . '" />
            <input type="hidden" name="periodID" value="' . $_GET['periodID'] . '" />
            <button type="submit">Change This Stuff!</button>
            </fieldset></form>';
        
        return ob_get_clean();
    }

    private function roundTime($number) {
        // This function takes a two digit precision number and rounds it to the nearest quarter.
        
        $roundhour = explode('.', number_format($number, 2));
                      
        if ($roundhour[1] < 13) {$roundhour[1] = 00;}
        elseif ($roundhour[1] >= 13 && $roundhour[1] < 37) {$roundhour[1] = 25;}
        elseif ($roundhour[1] >= 37 && $roundhour[1] < 63) {$roundhour[1] = 50;}
        elseif ($roundhour[1] >= 63 && $roundhour[1] < 87) {$roundhour[1] = 75;}
        elseif ($roundhour[1] >= 87) {$roundhour[1] = 00; $roundhour[0]++;}
            
        return number_format($roundhour[0] . '.' . $roundhour[1], 2);
    }

    private function parseTime($time, $mer) {
        $hour = array();
        if (strlen($time) == 2 && is_numeric($time)) {
            $time = $time . ':00';
        } elseif (strlen($time) == 4 && is_numeric($time)) {
            $time = substr($time, 0, 2) . ':' . substr($time, 2, 2);
        } elseif (strlen($time) == 3 && is_numeric($time)) {
            $time = substr($time, 0, 1) . ':' . substr($time, 1, 2);
        }
        
        $in = explode(':', $time);
        
        if (($mer == 'PM') && ($in[0] < 12)) {
        $in[0] = $in[0] + 12;
        } elseif (($mer == 'AM') && ($in[0] == 12)) {
        $in[0] = 0;
        }
        
        return $in[0] . ':' . $in[1] . ':00';
    }
}

FannieDispatch::conditionalExec(false);

?>
