<?php 
require_once('../../../config.php');
$db_master = $dbc;
$max = ($_GET['max']) ? 10 : 10;  // Max number of entries.

if ($_GET['login'] == 1 || $_SESSION['logged_in'] == True) include("/pos/fannie/src/passwd.php");

if (isset($_POST['submitted'])) { // If the form has been submitted.
	// Validate the data.
	$errors = array();
	$date = $_POST['date'];

	if (strtotime($date) > strtotime(date('Y-m-d'))) {
		$errors[] = 'You can\'t enter hours for a future date.';
	}

	// Make sure we're in a valid pay period.
	$query = "SELECT periodID, periodStart FROM ".DB_LOGNAME.".payperiods WHERE '$date' BETWEEN DATE(periodStart) AND DATE(periodEnd)";

	$result = mysql_query($query, $db_master);
	list($periodID, $periodStart) = mysql_fetch_row($result);

	$query = "SELECT DATEDIFF(CURDATE(), DATE(periodEnd)) FROM ".DB_LOGNAME.".payperiods WHERE periodID = $periodID";

	$result = mysql_query($query, $db_master);
	list($datediff) = mysql_fetch_row($result);
	
	$empnoChkQ = "SELECT * FROM employees WHERE emp_no = " . $_POST['emp_no'];
	$empnoChkR = mysql_query($empnoChkQ,$db_master);
	
	if ($_POST['emp_no'] && ($_POST['emp_no'] != '')) {
		if (!is_numeric($_POST['emp_no'])) {
        	$errors[] = 'Employee number entered is not numeric.';
		} elseif (mysql_num_rows($empnoChkR) != 1) { 
			$errors[] = 'Error finding that Employee Number.';
		} else {
        	$emp_no = $_POST['emp_no'];
		}
	} else {
		$errors[] = 'Please enter an Employee Number.';
	}
	
	// if ($datediff > 1) { // Bad.
	// 	$errors[] = 'You can\'t add hours more than a day after the pay period has ended.';
	// 	$date = NULL;
	// }
	$entrycount = 0;
	for ($i = 1; $i <= $max; $i++) {
		if (($_POST['hours' . $i]) && (is_numeric($_POST['area' . $i]))) {
			$entrycount++;
		}	
	}
// echo $entrycount;
	$lunch = $_POST['lunch'];
	$hour = array();
	$area = array();
	if ($entrycount == 0) {
		$errors[] = "You didn't enter any hours or labor categories.";
	} else {
		for ($i = 1; $i <= $max; $i++) {
			if ((isset($_POST['hours' . $i])) && (is_numeric($_POST['area' . $i]))) {
				$hours[$i] = $_POST['hours' . $i];
				$area[$i] = $_POST['area' . $i];
			} 
		}
	}
		function debug_p($var, $title) 
		{
		    print "<p>$title</p><pre>";
		    print_r($var);
			mysql_error();
		    print "</pre>";
		}  

	if (empty($errors)) { // All good.
		
		setcookie("timesheet", $emp_no, time()+60*3);
		
		// First check to make sure they haven't already entered hours for this day.
		$query = "SELECT * FROM ".DB_LOGNAME.".timesheet WHERE emp_no=$emp_no AND date='$date' and area <> 31";
		
        $result = mysql_query($query, $db_master);
        if (mysql_num_rows($result) == 0) { // Success.
			// if (strtotime($date) < strtotime($periodStart)) {
			// 	echo "Previous Pay period!!!";
			// 	exit;
			// }	
			$successcount = 0;
			for ($i = 1; $i <= $entrycount; $i++) {
				$query = "INSERT INTO ".DB_LOGNAME.".timesheet (emp_no, hours, area, date, periodID)
					VALUES ($emp_no, ".$_POST['hours' . $i].", ".$_POST['area' . $i].", '$date', $periodID)";
				$result = mysql_query($query, $db_master);
				if (mysql_affected_rows($db_master) == 1) {$successcount++;}
			}
			if ($successcount == $entrycount) {

				$header = 'Timeclock - Timesheet Manager';
				$page_title = 'Fannie - Administration Module';
				include ('../../../src/header.php');
				include ('./includes/header.html');
				echo "<div id='alert'><h1>Success!</h1>";
				echo "<p>If you like, you may <a href='./timesheet.php'>add more hours</a> or you can <a href='./viewsheet.php'>edit hours</a>.</p></div>";
				include ('../src/footer.php');
				exit();

			} else {
				$header = 'Timeclock - Timesheet Manager';
				$page_title = 'Fannie - Administration Module';
				include ('../../../src/header.php');
				include ('./includes/header.html');
				echo '<div id="alert"><p>ERR01: The entered hours could not be added, please try again later.</p>';
				echo '<p>Error: ' . mysql_error($db_master) . '</p>';
				echo '<p>Query: ' . $query . '</p></div>';
				// include ('./includes/footer.html');
				include ('../src/footer.php');
				exit();
			}

		} else {
			$header = 'Timeclock - Timesheet Manager';
			$page_title = 'Fannie - Administration Module';
			include ('../../../src/header.php');
			include ('./includes/header.html');
			echo "<div id='alert'><p>You have already entered hours for that day, please edit that day instead.</p></div>";
		}
	} else { // Report errors.
		$header = 'Timeclock - Timesheet Manager';
		$page_title = 'Fannie - Administration Module';
		include ('../../../src/header.php');
		include ('./includes/header.html');
		echo '<div id="alert"><p><font color="red">The following error(s) occurred:</font></p>';
		foreach ($errors AS $message) {
	        echo "<p> - $message</p>";
		}
		echo '<p><a href="timesheet.php">Please try again.</a></p></div>';
	}
} else { // Otherwise display the form.
    echo '<script type="text/javascript" language="javascript">
		window.onload = initAll;
		function initAll() {
		for (var i = 1; i <= 5 ; i++) {
		document.getElementById(i + "14").disabled = true;
		}
		}
		//this function was used by Matthaus (#7012) to hide certain Categories
		function updateshifts(sIndex) {
		if (sIndex == 7012) {
		for (var i = 1; i <= 5 ; i++) {
		document.getElementById(i + "14").disabled = false;
		}
		} else {
		for (var i = 1; i <= 5 ; i++) {
		document.getElementById(i + "14").disabled = true;
		}
		}
		}
		</script>';
	$header = 'Timeclock - Entry';
	$page_title = 'Fannie - Administration Module';
	include ('../../../src/header.php');
	include ('./includes/header.html');

	echo "<body onLoad='putFocus(0,0);'>";
	echo '<form action="timesheet.php" method="POST" name="timesheet" id="timesheet">';
	echo '<table border=0 cellpadding=4><tr>';
	if ($_SESSION['logged_in'] == True) {
		echo '<td><p>Name: <select name="emp_no">
			<option value="error">Select staff member</option>' . "\n";
		
		$query = "SELECT FirstName, IF(LastName='','',CONCAT(SUBSTR(LastName,1,1),\".\")), emp_no FROM ".DB_NAME.".employees where EmpActive=1 ORDER BY FirstName ASC";
		$result = mysql_query($query, $db_master);
		while ($row = mysql_fetch_array($result)) {
			echo "<option value=\"$row[2]\">$row[0] $row[1]</option>\n";
		}
		echo '</select>&nbsp;&nbsp;*</p></td>';
	} else {
		echo "<td><p>Employee Number*: <input type='text' name='emp_no' value='".$_COOKIE['timesheet']."' size=4 autocomplete='off' /></p></td>";
	}
	echo '<td><p>Date*: <input type="text" name="date" value="'. date('Y-m-d') .'" size=10 class="datepicker" alt="Tip: try cmd + arrow keys" />
		<!--<font size=1>Tip: try cmd + arrow keys</font>--></p></td></tr>';
	echo "<tr><td><br /></td></tr>";
	echo "<tr><td align='right'><b>Total Hours</b></td><td align='center'><strong>Labor Category</strong></td>";
	for ($i = 1; $i <= $max; $i++) {
		echo "<tr><td align='right'><input type='text' name='hours" . $i . "' size=6></input></td>";

		$query = "SELECT IF(NiceName='', ShiftName, NiceName), ShiftID FROM " . DB_LOGNAME . ".shifts WHERE visible=true ORDER BY ShiftOrder ASC";
		$result = mysql_query($query);
		echo '<td><select name="area' . $i . '" id="area' . $i . '"><option>Please select an area of work.</option>';
		while ($row = mysql_fetch_row($result)) {
			echo "<option id =\"$i$row[1]\" value=\"$row[1]\">$row[0]</option>";
		}
		echo '</select></td></tr>' . "\n";

	}
	echo '<tr><td><br /></td></tr>
		<tr><td colspan=2 align="center">
		<button name="submit" type="submit">Submit</button>
		<input type="hidden" name="submitted" value="TRUE" /></td></tr>
		</table></form>';	
}
if ($_SESSION['logged_in'] == True) {
	echo "<div class='log_btn'><a href='" . $_SERVER["PHP_SELF"] . "?logout=1'>logout</a></div>";
} else {
	echo "<div class='log_btn'><a href='" . $_SERVER["PHP_SELF"] . "?login=1'>login</a></div>";  //   class='loginbox'
}

include ('../../../src/footer.php');
?>
