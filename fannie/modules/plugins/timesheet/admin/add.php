<?php

$max = 5; // Max number of entries.

// require_once ('../includes/mysqli_connect.php');
$db_master= mysql_connect ('192.168.123.100','miguel','SunF1ower');

mysql_select_db('is4c_log', $db_master);
// mysqli_select_db($db_slave, 'is4c_log');

if (isset($_GET['submitted'])) { // If the form has been submitted.
        // Validate the data.
        $errors = array();
       

	//2011-01-03 sdh - added field to select by year 
        if (checkdate($_GET['month'], $_GET['date'], $_GET['year'])) {
                $date = $_GET['year'] . '-' . str_pad($_GET['month'], 2, 0, STR_PAD_LEFT) . '-' . $_GET['date'];
        } else {
                $errors[] = 'The date you have entered is not a valid date.';
        }
        
        if (strtotime($date) > strtotime(date('Y-m-d'))) {
                $errors[] = 'You can\'t enter hours for a future date.';
        }
        
        // Make sure we're in a valid pay period.
        $query = "SELECT periodID FROM is4c_log.payperiods WHERE CURDATE() BETWEEN DATE(periodStart) AND DATE(periodEnd)";
        $result = mysqli_query($db_master, $query);
        list($periodID) = mysqli_fetch_row($result);
        
        if (!is_numeric($_GET['emp_no'])) {
                $errors[] = 'You didn\'t select your name.';
        } else {
                $emp_no = $_GET['emp_no'];
        }
        
        $entrycount = 0;
        for ($i = 1; $i <= $max; $i++) {
                if ((isset($_GET['in' . $i])) && (isset($_GET['out' . $i])) && (is_numeric($_GET['area' . $i]))) {
                        $entrycount++;
                }
        }
        
        $lunch = $_GET['lunch'];
        $hour = array();
        $area = array();
        
        if ($entrycount == 0) {
                $errors[] = "You didn't enter any hours.";
        } else {
                for ($i = 1; $i <= $max; $i++) {
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
                                        $errors[] = "You can't have gotten here after you finished work.</p><p>Or, you couldn't have finished work before you started work.";
                                }
                        }
                }
        }
        
        if (empty($errors)) { // All good.
                // First check to make sure they haven't already entered hours for this day.
                $query = "SELECT * FROM is4c_log.timesheet WHERE emp_no=$emp_no AND date='$date'";
                // echo $query;
                $result = mysqli_query($db_master, $query);
                if (mysqli_num_rows($result) == 0) { // Success.
                        $successcount = 0;
                        for ($i = 1; $i <= $entrycount; $i++) {
                                $query = "INSERT INTO is4c_log.timesheet (emp_no, time_in, time_out, area, date, periodID)
                                        VALUES ($emp_no, '{$timein[$i]}', '{$timeout[$i]}', {$area[$i]}, '$date', $periodID)";
                                $result = mysqli_query($db_master, $query);
                                if (mysqli_affected_rows($db_master) == 1) {$successcount++;}
                        }
                        if ($successcount == $entrycount) {
                                
                        } else {
                                $header = 'Timesheet Management';
                                $page_title = 'Fannie - Administration Module';
                                include ('../includes/header.html');
                                include ('./includes/header.html');
                                echo '<p>The entered hours could not be added, please try again later.</p>';
                                echo '<p>Error: ' . mysqli_error($db_master) . '</p>';
                                echo '<p>Query: ' . $query . '</p>';
                                include ('./includes/footer.html');
                                include ('../includes/footer.html');
                                exit();
                        }
                        $query = "INSERT INTO is4c_log.timesheet (emp_no, time_out, time_in, area, date, periodID)
                                VALUES  ($emp_no, '2008-01-01 00:00:00', '2008-01-01 " . $lunch . "', 0, '$date', $periodID)";
                        $result = mysqli_query($db_master, $query);
                        if (!$result) {
                                $header = 'Timesheet Management';
                                $page_title = 'Fannie - Administration Module';
                                include ('../includes/header.html');
                                include ('./includes/header.html');
                                echo '<p>The entered hours could not be added, please try again later.</p>';
                                echo '<p>Error: ' . mysqli_error($db_master) . '</p>';
                                echo '<p>Query: ' . $query . '</p>';
                                include ('./includes/footer.html');
                                include ('../includes/footer.html');
                                exit();
                        } else {
                                // Start the redirect.
                                ob_end_clean();
                                $url = "/timesheet/admin.php?function=view&emp_no=$emp_no&periodID=$periodID";
                                header("Location: $url");
                                exit();
                        }
                } else {
                        echo '<p>You have already entered hours for that day, please edit that day instead.</p>';
                }
                
        } else { // Report errors.                
                echo '<p><font color="red">The following error(s) occurred:</font></p>';
                foreach ($errors AS $message) {
                        echo "<p> - $message</p>";
                }
                echo '<p>Please try again.</p>';
        }
        
        
}
        
    $months = array(01=>'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
    
    echo '<form action="' . $_SERVER['PHP_SELF'] . '" method="GET"><input type="hidden" name="function" value="add" />
        <p>Name: <select name="emp_no">
            
            <option value="error">Who are You?</option>' . "\n";
    
    $query = "SELECT FirstName, emp_no FROM is4c_op.employees where EmpActive=1 ORDER BY FirstName ASC";
    $result = mysqli_query($db_slave, $query);
    while ($row = mysqli_fetch_array($result)) {
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
    echo '</select> Year: <select name="year">
	<option value="2011">2011</option>
	<option value="2010">2010</option>
	<option value="2009">2009</option>
	<option value="2008">2008</option>
	<option value="2007">2007</option>
	</select>';
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
    for ($i = 1; $i <= $max; $i++) {
            $query = "SELECT * FROM is4c_log.shifts WHERE ShiftID NOT IN (0, 13) ORDER BY ShiftID ASC";
            $result = mysql_query($query, $db_master);
            
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
        while ($row = mysqli_fetch_row($result)) {
            echo "<option value=\"$row[1]\">$row[0]</option>";
        }
        echo "</select></th></tr>\n";
    }
        echo '</table>
    <button name="submit" type="submit">Submit</button>
    <input type="hidden" name="submitted" value="TRUE" />
    </form>';

?>
