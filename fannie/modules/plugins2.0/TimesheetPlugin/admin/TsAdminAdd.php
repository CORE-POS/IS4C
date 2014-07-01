<?php
require_once(dirname(__FILE__).'/../../../../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class TsAdminAdd extends FanniePage {
    protected $header = 'Timesheet Management';
    protected $title = 'Fannie - Administration Module';

    private $max = 5; // Max number of entries.
        private $errors = array();

    function preprocess(){
        global $FANNIE_OP_DB,$FANNIE_PLUGIN_SETTINGS;
        $ts_db = FannieDB::get($FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']);
        if (isset($_GET['submitted'])) { // If the form has been submitted.
            // Validate the data.
       
            //2011-01-03 sdh - added field to select by year 
            if (checkdate($_GET['month'], $_GET['date'], $_GET['year'])) {
                $date = $_GET['year'] . '-' . str_pad($_GET['month'], 2, 0, STR_PAD_LEFT) . '-' . $_GET['date'];
            } else {
                $this->errors[] = 'The date you have entered is not a valid date.';
            }
        
            if (strtotime($date) > strtotime(date('Y-m-d'))) {
                $this->errors[] = 'You can\'t enter hours for a future date.';
            }
        
            // Make sure we're in a valid pay period.
            $query = $ts_db->prepare_statement("SELECT periodID FROM payperiods 
                WHERE ".$ts_db->now()." BETWEEN DATE(periodStart) AND DATE(periodEnd)");
            $result = $ts_db->exec_statement($query);
            list($periodID) = $ts_db->fetch_row($result);
        
            $emp_no = $_GET['emp_no'];
            if (!is_numeric($_GET['emp_no'])) 
                $this->errors[] = 'You didn\'t select your name.';
        
            $entrycount = 0;
            for ($i = 1; $i <= $this->max; $i++) {
                if ((isset($_GET['in' . $i])) && (isset($_GET['out' . $i])) && (is_numeric($_GET['area' . $i]))) {
                    $entrycount++;
                }
            }
        
            $lunch = $_GET['lunch'];
            $hour = array();
            $area = array();
        
            if ($entrycount == 0) {
                $this->errors[] = "You didn't enter anys- hours.";
            } 
            else {
                for ($i = 1; $i <= $this->max; $i++) {
                    if ((isset($_GET['in' . $i])) && (isset($_GET['out' . $i])) && (is_numeric($_GET['area' . $i]))) {
                        if (strlen($_GET['in' . $i]) == 2 && is_numeric($_GET['in' . $i])) {
                            $_GET['in' . $i] = $_GET['in' . $i] . ':00';
                        } elseif (strlen($_GET['in' . $i]) == 4 && is_numeric($_GET['in' . $i])) {
                            $_GET['in' . $i] = substr($_GET['in' . $i], 0, 2) . ':' . substr($_GET['in' . $i], 2, 2);
                        } elseif (strlen($_GET['in' . $i]) == 3 && is_numeric($_GET['in' . $i])) {
                            $_GET['in' . $i] = substr($_GET['in' . $i], 0, 1) . ':' . substr($_GET['in' . $i], 1, 2);
                        } elseif (strlen($_GET['in' . $i]) == 1 && is_numeric($_GET['in' . $i])) {
                            $_GET['in' . $i] = $_GET['in' . $i] . ':00';
                        }
                                
                        if (strlen($_GET['out' . $i]) == 2 && is_numeric($_GET['out' . $i])) {
                            $_GET['out' . $i] = $_GET['out' . $i] . ':00';
                        } elseif (strlen($_GET['out' . $i]) == 4 && is_numeric($_GET['out' . $i])) {
                            $_GET['out' . $i] = substr($_GET['out' . $i], 0, 2) . ':' . substr($_GET['out' . $i], 2, 2);
                        } elseif (strlen($_GET['out' . $i]) == 3 && is_numeric($_GET['out' . $i])) {
                            $_GET['out' . $i] = substr($_GET['out' . $i], 0, 1) . ':' . substr($_GET['out' . $i], 1, 2);
                        } elseif (strlen($_GET['out' . $i]) == 1 && is_numeric($_GET['out' . $i])) {
                            $_GET['out' . $i] = $_GET['out' . $i] . ':00';
                        }
                                
                        $in = explode(':', $_GET['in' . $i]);
                        $out = explode(':', $_GET['out' . $i]);
                                
                        if (($_GET['inmeridian' . $i] == 'PM') && ($in[0] < 12)) {
                            $in[0] = $in[0] + 12;
                        } elseif (($_GET['inmeridian' . $i] == 'AM') && ($in[0] == 12)) {
                            $in[0] = 0;
                        }
                        if (($_GET['outmeridian' . $i] == 'PM') && ($out[0] < 12)) {
                            $out[0] = $out[0] + 12;
                        } elseif (($_GET['outmeridian' . $i] == 'AM') && ($out[0] == 12)) {
                            $out[0] = 0;
                        }
                                
                        $timein[$i] = $date . ' ' . $in[0] . ':' . $in[1] . ':00';
                        $timeout[$i] = $date . ' ' . $out[0] . ':' . $out[1] . ':00';
                        $area[$i] = $_GET['area' . $i];
                                
                        if (strtotime($timein[$i]) >= strtotime($timeout[$i])) {
                            $this->errors[] = "You can't have gotten here after you finished work.</p><p>Or, you couldn't have finished work before you started work.";
                        }
                    }
                }
            }
        
            if (empty($this->errors)) { // All good.
                // First check to make sure they haven't already entered hours for this day.
                $query = $ts_db->prepare_statement("SELECT * FROM timesheet WHERE emp_no=? AND date=?");
                $result = $ts_db->exec_statement($query,array($emp_no,$date));
                if ($ts_db->num_rows($result) == 0) { // Success.
                    $successcount = 0;
                    $query = $ts_db->prepare_statement("INSERT INTO timesheet 
                        (emp_no, time_in, time_out, area, date, periodID)
                        VALUES (?,?,?,?,?,?)");
                    for ($i = 1; $i <= $entrycount; $i++) {
                        $result = $ts_db->exec_statement($query,array(
                            $emp_no, $timein[$i], $timeout[$i],
                            $area[$i], $date, $periodID
                        ));
                        if ($ts_db->affected_rows() == 1) {$successcount++;}
                    }
                    if ($successcount != $entrycount) {
                        $this->errors[] = '<p>The entered hours could not be added, please try again later.</p>';
                        $this->errors[] = '<p>Error: ' . $ts_db->error() . '</p>';
                        $this->errors[] = '<p>Query: ' . $query . '</p>';
                        return True;
                    }
                    $query = $ts_db->prepare_statement("INSERT INTO timesheet 
                        (emp_no, time_out, time_in, area, date, periodID)
                        VALUES (?, '2008-01-01 00:00:00', ?, 0, ?, ?)");
                    $result = $ts_db->exec_statement($query, array($emp_no,
                            ('2008-01-01 '.$lunch), $date, $periodID));
                    if (!$result) {
                        $this->errors[] = '<p>The entered hours could not be added, please try again later.</p>';
                        $this->errors[] = '<p>Error: ' . $ts_db->error() . '</p>';
                        $this->errors[] = '<p>Query: ' . $query . '</p>';
                        return True;
                    } 
                    else {
                        // Start the redirect.
                        $url = sprintf('TsAdminView.php?emp_no=%d&periodID=%d',
                                $emp_no, $periodID);
                        header("Location: $url");
                        return False;
                    }
                } 
                else {
                    $this->errors[] = '<p>You have already entered hours for that day, please edit that day instead.</p>';
                }
            }
        }
        return True;    
    }

    function body_content(){
        global $FANNIE_OP_DB,$FANNIE_PLUGIN_SETTINGS;
        $ts_db = FannieDB::get($FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']);

        if (!empty($this->errors)){
            $msg = '<h3>Errors occurred</h3><ul>';
            foreach($this->errors as $e)
                $msg .= '<li>'.$e.'</li>';
            $msg .= '</ul>';
            return $msg;
        }
    
        $months = array(01=>'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
            
        ob_start();
        echo '<form action="' . $_SERVER['PHP_SELF'] . '" method="GET"><input type="hidden" name="function" value="add" />
            <p>Name: <select name="emp_no">
            <option value="error">Who are You?</option>' . "\n";
    
        $query = $ts_db->prepare_statement("SELECT FirstName, emp_no FROM ".
                $FANNIE_OP_DB.$ts_db->sep()."employees where EmpActive=1 ORDER BY FirstName ASC");
        $result = $ts_db->exec_statement($query);
        while ($row = $ts_db->fetch_array($result)) {
            echo "<option value=\"$row[1]\">$row[0]</option>\n";
        }
        echo '</select></p>
        <p>Month: <select name="month">';
        foreach ($months AS $value => $key) {
            echo "<option value=\"$value\"";
            if (date('m')==$value) echo ' SELECTED';
            echo ">$key</option>\n";
        }
        echo '</select>
            Date: <select name="date">';
        for ($i = 1; $i <= 31; $i++) {
            $i = str_pad($i, 2, 0, STR_PAD_LEFT);
            echo "<option value=\"$i\"";
            if (date('d') == $i) echo ' SELECTED';
            echo ">$i</option>\n";
        }
        echo '</select> Year: <select name="year">';
        for($y = date('Y'); $y > 1999; $y--)
            echo '<option>'.$y.'</option>';
        echo '</select>';
        echo '<br />(Today is ';
        echo date('l\, F jS, Y');
        echo ')</p>';
        echo '<p>Lunch? <select name="lunch">
                    <option value="00:00:00">None</option>
                    <option value="00:15:00">15 Minutes</option>
                    <option value="00:30:00">30 Minutes</option>
                    <option value="00:45:00">45 Minutes</option>
                    <option value="01:00:00">1 Hour</option>
                    <option value="01:15:00">1 Hour, 15 Minutes</option>
                    <option value="01:30:00">1 Hour, 30 Minutes</option>
                    <option value="01:45:00">1 Hour, 45 Minutes</option>
                    <option value="02:00:00">2 Hours</option>
            </select></p>';

        // echo "<p>Please use enter times in (HH:MM) format. For example 8:45, 12:30, etc.</p>";
        echo "<table><tr><th>Time In</th><th>Time Out</th><th>Area Worked</th></tr>\n";
        $query = $ts_db->prepare_statement("SELECT * FROM shifts 
            WHERE ShiftID NOT IN (0, 13) ORDER BY ShiftID ASC");
        for ($i = 1; $i <= $this->max; $i++) {
            $result = $ts_db->exec_statement($query);
            
            echo '<tr>
                <th><input type="text" name="in' . $i . '" size="5" maxlength="5">
                <select name="inmeridian' . $i . '">
                    <option value="AM">AM</option>
                    <option value="PM">PM</option>
                </select>
                </th>
                <th><input type="text" name="out' . $i . '" size="5" maxlength="5">
                <select name="outmeridian' . $i . '">
                    <option value="AM">AM</option>
                    <option value="PM" SELECTED>PM</option>
                </select>
                </th>
                <th><select name="area' . $i . '">
                <option>Please select an area of work.</option>';
            while ($row = $ts_db->fetch_row($result)) {
                echo "<option value=\"$row[1]\">$row[0]</option>";
            }
            echo "</select></th></tr>\n";
        }
        echo '</table>
            <button name="submit" type="submit">Submit</button>
            <input type="hidden" name="submitted" value="TRUE" />
            </form>';

        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec(false);

?>
