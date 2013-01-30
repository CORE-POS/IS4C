<?php
require_once('../../../config.php');
$db_master = $dbc;

$max = 10; // Max number of entries.

if (!isset($_POST['submitted']) && !isset($_GET['emp_no'])) {
    $header = 'Timesheet Management';
    $page_title = 'Fannie - Administration Module';
    echo '<p><font color="red">You have found this page mistakenly.</font></p>';
    exit();
}

if (isset($_POST['submitted'])) { // If the form has been submitted.
	if ($_POST['submit'] == 'delete') {
		$header = 'Timesheet Management';
		$page_title = 'Fannie - Administration Module';
		include ('../src/header.php');
		include ('./includes/header.html');
		$emp_no = $_POST['emp_no'];
		$date = $_POST['date'];
		$query = "DELETE FROM is4c_log.timesheet WHERE emp_no=$emp_no AND date='$date'";
		$result = mysql_query($query, $db_master);
		if ($result) {
		    echo '<p>The day has been removed from your timesheet.</p>';
		} else {
		    echo '<p><font color="red">The day could not be removed, please try again later.</font></p>';
		}
		include ('../../../src/footer.php');
		exit();
	} elseif ($_POST['submit'] == 'submit') {

		// Validate the data.
		$errors = array();

		$date = $_POST['date'];
		$emp_no = $_POST['emp_no'];

		$entrycount = 0;
		for ($i = 1; $i <= $max; $i++) {
			if ((isset($_POST['hours' . $i])) && (is_numeric($_POST['area' . $i]))) {
				$entrycount++;
			}
		}

		$periodID = $_POST['periodID'];
		$hours = array();
		$area = array();

		if ($entrycount == 0) {
			$errors[] = 'You didn\'t enter any hours.';
		} else {
			for ($i = 1; $i <= $entrycount; $i++) {
				if (((!$_POST['hours' . $i]) || (!$_POST['area' . $i])) && $_POST['hours' . $i] != 0) 
					$errors[] = "For entry $i: Either the Hours or the Labor Category were not set.";
			}
			for ($i = 1; $i <= $max; $i++) {
				if ((isset($_POST['hours' . $i])) && (is_numeric($_POST['area' . $i]))) {
					$hours[$i] = $_POST['hours' . $i];
					$area[$i] = $_POST['area' . $i];
					$ID[$i] = $_POST['ID' . $i];
				}
			}
		}
                 
		if (empty($errors)) { // All good.

            $successcount = 0;
                for ($i = 1; $i <= $entrycount; $i++) {
                    if (is_numeric($ID[$i])) {
                        $query = "UPDATE is4c_log.timesheet SET hours={$hours[$i]},area={$area[$i]}
                            WHERE emp_no=$emp_no AND date='$date' AND ID={$ID[$i]}";
                        // echo $query;
                        $result = mysql_query($query);
                        if ($result) {$successcount++;} else {echo '<p>Query: ' . $query . '</p><p>MySQL Error: ' . mysql_error() . '</p>';}
                    } elseif ($ID[$i] == 'insert') {
                        $query = "INSERT INTO is4c_log.timesheet (emp_no, hours, area, date, periodID)
                            VALUES ($emp_no, {$hours[$i]}, {$area[$i]}, '$date', $periodID)";
                        $result = mysql_query($query);
                        if ($result) {$successcount++;} else {echo '<p>Query: ' . $query . '</p><p>MySQL Error: ' . mysql_error() . '</p>';}
                    }
                }
                
				if ($successcount == $entrycount) {
                        // Start the redirect.
                        $url = "viewsheet.php?emp_no=$emp_no&period=$periodID";
                        header("Location: $url");
                        exit();
                } else {
                        $header = 'Timesheet Management';
                        $page_title = 'Fannie - Administration Module';
                       include ('../../../src/header.php');
                        include ('./includes/header.html');
                       echo '<p>The entered hours could not be updated, Unknown error.</p>';
                        echo '<p>Error: ' . mysql_error() . '</p>';
                        echo '<p>Query: ' . $query . '</p>';
                }
                    
            } else { // Report errors.
                $header = 'Timesheet Management';
                $page_title = 'Fannie - Administration Module';
                include ('../../../src/header.php');
                include ('./includes/header.html');
                echo '<p><font color="red">The following error(s) occurred:</font></p>';
			foreach ($errors AS $message) {
				echo "<p> - $message</p>";
			}
			echo '<form><p><a onClick="goBack()" style="cursor:pointer;">Please try again.</a></p></form>';
		}
	}
        
} elseif (isset($_GET['emp_no']) && is_numeric($_GET['emp_no'])) { // Display the form.
	$header = 'Timesheet Management';
	$page_title = 'Fannie - Administration Module';
	include ('../../../src/header.php');
	include ('./includes/header.html');
	$emp_no = $_GET['emp_no'];
	$date = $_GET['date'];
	$periodID = $_GET['periodID'];

	// Make sure we're in a valid pay period.       
	$query = "SELECT DATEDIFF(CURDATE(), DATE(periodEnd)) FROM is4c_log.payperiods WHERE periodID = $periodID";
	$result = mysql_query($query, $db_master);
	list($datediff) = mysql_fetch_row($result);

	if ($datediff > 1) { // Bad.
		echo "<br /><p>You can't edit hours more than a day after the pay period has ended.</p><br />";
		include ('../../../src/footer.php');
		exit();
	} else { // Good.

		$query = "SELECT CONCAT(FirstName,' ',LastName) FROM is4c_op.employees where emp_no=$emp_no";
		$result = mysql_query($query);
// echo $query;
		list($name) = mysql_fetch_row($result);
		echo "<form action='editdate.php' method='POST'>
			<input type='hidden' name='emp_no' value='$emp_no' />
			<input type='hidden' name='date' value='$date' />
			<input type='hidden' name='submitted' value='TRUE' />
			<p align='center'><button name='submit' type='submit' value='delete'>Remove this day from my timesheet.</button></p>
			</form>";

		echo "<form action='editdate.php' method='POST'>";
		echo "<table border=0 cellpadding=4><tr><td><p>Name: <strong>$name</strong></p></td><td><p>Date: <strong>". substr($date, 0, 4) . "-" . substr($date, 5, 2) . "-" . substr($date, 8, 2) . "</strong></p></td></tr>
			<input type='hidden' name='emp_no' value='$emp_no' />
			<input type='hidden' name='periodID' value='$periodID' />               
			<input type='hidden' name='date' value='$date' />";

		echo "<tr><td align='right'><b>Total Hours</b></td><td align='center'><strong>Labor Category</strong></td>
			<!--<td><strong>Remove</strong></td>--></tr>\n";

		for ($i = 1; $i <= $max; $i++) {
			$inc = $i - 1;
			$query = "SELECT hours, area, ID FROM ".DB_LOGNAME.".timesheet WHERE emp_no = $emp_no AND date = '$date' ORDER BY ID ASC LIMIT ".$inc.",1";
			// echo $query;
			$result = mysql_query($query);
			$num = mysql_num_rows($result);
					
			if ($row = mysql_fetch_row($result)) {
				$hours = ($row[0])?$row[0]:'';
				$area = $row[1];
				$ID = $row[2];
			} else {
				$hours = '';
				$area = NULL;
				$ID = "insert";
			}

			echo "<tr><td align='right'><input type='text' name='hours" . $i . "' value='$hours' size=6></input></td>";
			$query = "SELECT IF(NiceName='', ShiftName, NiceName), ShiftID FROM " . DB_LOGNAME . ".shifts WHERE visible=true ORDER BY ShiftOrder ASC";
			$result = mysql_query($query);
			echo '<td><select name="area' . $i . '" id="area' . $i . '"><option>Please select an area of work.</option>';
			while ($row = mysql_fetch_row($result)) {
				echo "<option id =\"$i$row[1]\" value=\"$row[1]\" ";
				if ($row[1] == $area) echo "SELECTED";
				echo ">$row[0]</option>";
			}
	        echo "</select><input type='hidden' name='ID" . $i . "' value='$ID' /></td>";
			echo "</tr>\n";

		}
		echo '<tr><td colspan=2 align="center"><button name="submit" type="submit" value="submit"';
		// echo "onclick='confirm('Do you really want to DELETE hours?')' ";
		echo'>Submit</button>
			<input type="hidden" name="submitted" value="TRUE" /></td></tr>';
		echo '</table></form>';
	}
}
include ('../src/footer.php');
?>