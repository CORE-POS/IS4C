<?php
if ($_GET['login'] == 1 || $_SESSION['logged_in'] == True) include("/pos/fannie/src/passwd.php");
// $loggedin = (isset($_COOKIE['verify'])) ? True : False;
$ft = 40;

require_once('../../../config.php');
$db_master = $dbc;


if ((isset($_POST['submitted']) && is_numeric($_POST['period'])) || (is_numeric($_GET['period']) && (is_numeric($_GET['emp_no']))) || (is_numeric($_POST['emp']) && is_numeric($_POST['period']))) { // If submitted or browsed to.


    if (is_numeric($_POST['emp_no'])) {$emp_no = $_POST['emp_no'];}
    elseif (is_numeric($_GET['emp_no'])) {$emp_no = $_GET['emp_no'];}
    else {$emp_no = FALSE;}
    if (is_numeric($_POST['period'])) {$periodID = $_POST['period'];}
    elseif (is_numeric($_GET['period'])) {$periodID = $_GET['period'];}

    if ($emp_no) {
        $header = 'Timesheet Management';
        $page_title = 'Fannie - Administration Module';
        include ('../src/header.php');
        include ('./includes/header.html');
        $query = "SELECT ROUND(SUM(hours), 2),
                date_format(t.date, '%a %b %D'),
                t.emp_no,
                e.FirstName,
                date_format(p.periodStart, '%M %D, %Y'),
                date_format(p.periodEnd, '%M %D, %Y'),
                t.date
            FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
                INNER JOIN is4c_op.employees AS e
                ON (t.emp_no = e.emp_no)
                INNER JOIN {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods AS p
                ON (t.periodID = p.periodID)
            WHERE t.emp_no = $emp_no
            AND t.area <> 31
            AND t.periodID = $periodID
	    AND (t.vacation IS NULL OR t.vacation = 0)
            GROUP BY t.date";

        $periodQ = "SELECT periodStart, periodEnd FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods WHERE periodID = $periodID";
        $periodR = mysql_query($periodQ, $db_master);
        list($periodStart, $periodEnd) = mysql_fetch_row($periodR);

        $weekoneQ = "SELECT ROUND(SUM(hours), 2)
            FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
            INNER JOIN {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods AS p
            ON (p.periodID = t.periodID)
            WHERE t.emp_no = $emp_no
            AND t.periodID = $periodID
            AND t.area <> 31
            AND t.date >= DATE(p.periodStart)
            AND t.date < DATE(date_add(p.periodStart, INTERVAL 7 day))";

        $weektwoQ = "SELECT ROUND(SUM(hours), 2)
            FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
            INNER JOIN {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods AS p
            ON (p.periodID = t.periodID)
            WHERE t.emp_no = $emp_no
            AND t.periodID = $periodID
            AND t.area <> 31
            AND t.date >= DATE(date_add(p.periodStart, INTERVAL 7 day)) AND t.date <= DATE(p.periodEnd)";

        $vacationQ = "SELECT ROUND(hours, 2), ID
            FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
            WHERE t.emp_no = $emp_no
            AND t.periodID = $periodID
            AND t.area = 31";

       $WageQ = "SELECT pay_rate FROM is4c_op.employees WHERE emp_no = $emp_no";

        $weekoneR = mysql_query($weekoneQ);
        $weektwoR = mysql_query($weektwoQ);
        $vacationR = mysql_query($vacationQ);
        $WageR = mysql_query($WageQ, $db_master);

        list($weekone) = mysql_fetch_row($weekoneR);
        if (is_null($weekone)) $weekone = 0;
        list($weektwo) = mysql_fetch_row($weektwoR);
        if (is_null($weektwo)) $weektwo = 0;

        if (mysql_num_rows($vacationR) != 0) 
            list($vacation, $vacationID) = mysql_fetch_row($vacationR);

        list($Wage) = mysql_fetch_row($WageR);
        if (is_null($Wage)) $Wage = 0; 

        $result = mysql_query($query,$db_master);
        if (mysql_num_rows($result) > 0) {
            $first = TRUE;
            $periodHours = 0;
            while ($row = mysql_fetch_array($result)) {
                if ($first == TRUE) {
                    echo "<h3>Timesheet for $row[3] from $row[4] to $row[5]</h3> <br />";
					// echo "<span style='font-size:0.9em;'><a href='report_staff_mem.php?emp_no=$emp_no&period=$periodID&run=run'>View Staff Member Totals</a></span>";
                    echo "<table cellpadding=4><tr><th>Date</th><th>Total Hours</th><th></th></tr>\n";
                }
                if ($row[0] > 24) {$fontopen = '<font color="red">'; $fontclose = '</font>';} else {$fontopen = NULL; $fontclose = NULL;}
                echo "<tr><td>$row[1]</td><td>$fontopen$row[0]$fontclose</td><td><a href=\"editdate.php?emp_no=$emp_no&date=$row[6]&periodID=$periodID\">(Edit)</a></td></tr>\n";
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
			echo number_format($vacation, 2) . "</td></tr>";
			$otime = ($ot1 + $ot2) * 1.5;
			$week1 = ($weekone > $ft) ? $ft : $weekone;
			$week2 = ($weektwo > $ft) ? $ft : $weektwo;
			$gw = $Wage * ($week1 + $week2 + $vacation + $otime);
			
            echo "<tr><td>Gross Wages (before taxes): </td><td>$" . number_format($gw, 2) . "</td></tr>";
			echo "</table></form><br />";

        } else {
			$periodHours = 0;
			$nameQ = "SELECT firstName FROM is4c_op.employees WHERE emp_no=$emp_no";
			$nameR = mysql_query($nameQ, $db_master);
			list($name) = mysql_fetch_row($nameR);

			echo "<p>Timesheet for $name from " . date_format(date_create($periodStart), 'F dS, Y') . " to " . date_format(date_create($periodEnd), 'F dS, Y') . ":  
				<span style='font-size:0.9em;'><a href='report_staff_mem.php?emp_no=$emp_no&period=$periodID&run=run'>View Staff Member Totals</a></span>
				</p>
				<table>
				<tr><th>Date</th><th>Total Hours Worked</th><th></th></tr>\n
				<tr><td colspan='3'>(No Hours Worked In This Pay Period)</td></tr>\n
				</table>
				<form action='viewsheet.php' method='POST'>
				<p>Total hours in this pay period: 0.00</p>
				</form><br />";
			echo "<form action='" .  $_SERVER['PHP_SELF'] . "' method='POST'>
            	<table border=0 cellpadding='5'><tr><td>Paid Time Off (PTO): ";
            if ($vacation > 0) {echo '<font color="red">'; $font = '</font>';} else {$font = NULL;}
            echo "<input type='text' name='vacation' size='5' maxlength='5' value='" . number_format($vacation, 2) . "' />" . $font . "
                <input type='hidden' name='vacationID' value='$vacationID' />
                <input type='hidden' name='period' value='$periodID' />
                <input type='hidden' name='emp' value='$emp_no' /></td>
				<!-- <td><button name='addvaca' type='submit'>Use PTO Hours</button></td> -->
				</tr>";
            echo "<tr><td>Gross Wages (before taxes): $" . number_format($Wage * ($periodHours + $vacation), 2) . "</td></tr>";
			echo "</table></form><br />";

        }

    } elseif (isset($_POST['addvaca'])) {
        $errors = array();
        $emp = $_POST['emp'];
		$date = date('Y-m-d');
        if (is_numeric($_POST['vacation'])) {
            $vaca = (float) $_POST['vacation'];

            $roundvaca = explode('.', number_format($vaca, 2));

            if ($roundvaca[1] < 13) {$roundvaca[1] = 00;}
            elseif ($roundvaca[1] >= 13 && $roundvaca[1] < 37) {$roundvaca[1] = 25;}
            elseif ($roundvaca[1] >= 37 && $roundvaca[1] < 63) {$roundvaca[1] = 50;}
            elseif ($roundvaca[1] >= 63 && $roundvaca[1] < 87) {$roundvaca[1] = 75;}
            elseif ($roundvaca[1] >= 87) {$roundvaca[1] = 00; $roundvaca[0]++;}

            $vaca = number_format($roundvaca[0] . '.' . $roundvaca[1], 2);

        } else {
            $errors[] = "Vacation hours to be used must be a number.";
            $vaca = FALSE;
        }

        if (is_numeric($_POST['vacationID']) && is_numeric($_POST['period'])) {
            $vacaID = (int) $_POST['vacationID'];
            $perID = (int) $_POST['period'];
            $vacaQ = "UPDATE {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet SET date = '$date', vacation = $vaca WHERE ID = $vacaID";

        } elseif ($_POST['vacationID'] == 'insert' && is_numeric($_POST['period'])) {
            $perID = (int) $_POST['period'];
            $vacaQ = "INSERT INTO {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet (emp_no, hours, area, vacation, date, periodID)
                VALUES ($emp, $vaca, 31, $vaca, '$date', $perID)";
        }

        if (empty($errors)) {
			// echo $vacaQ;
            $vacaR = mysql_query($vacaQ, $db_master);
            if ($vacaR) {
                $url = "viewsheet.php?emp_no=$emp&period=$perID";
                header("Location: $url");
                exit();
            } else {
                $header = 'Timesheet Management';
                $page_title = 'Fannie - Administration Module';
                include ('../../../src/header.php');
                include ('./includes/header.html');
                echo "<br /><br /><h3>The vacation hours could not be added due </h3><h3>to a system error, please try again later.</h3><br /><br /><br />";
                exit();
            }
        } else {
            $header = 'Timesheet Management';
            $page_title = 'Fannie - Administration Module';
            include ('../../../src/header.php');
            include ('./includes/header.html');
            echo "<br /><br /><h1>The following errors occurred:</h1><ul>";

            foreach ($errors as $msg) {
                echo "<p>- $msg</p>";
            }
            echo "</ul><br /><br /><br />";

            include ('includes/footer.html');
            exit();
        }

    } else {
		$header = 'Timesheet Management';
		$page_title = 'Fannie - Administration Module';
		include ('../../../src/header.php');
		include ('./includes/header.html');
		echo "<div id='alert'><h3>The following errors occurred:</h3><ul>
		<p>- You forgot to select your name.</p></ul></div>";
		include ('../../../src/footer.php');
		exit();
    }


} else {
    $header = 'Timesheet Management' . $_GET['login'];
    $page_title = 'Fannie - Administration Module';
    include ('../../../src/header.php');
    include ('./includes/header.html');
	echo "<body onLoad='putFocus(0,0);'>";
    $query = "SELECT FirstName, LastName, emp_no FROM is4c_op.employees where EmpActive=1 ORDER BY FirstName ASC";
    $result = mysql_query($query, $db_master);
    echo '<form action="viewsheet.php" method="POST">';

	if ($_SESSION['logged_in'] == True) {
    	echo '<p>Name: <select name="emp_no">
        	<option>Select staff member</option>';
    	while ($row = mysql_fetch_array($result)) {
            echo "<option value='".$row[2]."'>" . ucwords($row[0]) . " " . ucwords(substr($row[1],0,1)) . ".</option>\n";
    	}
		echo '</select></p>';
	} else {
		echo "<p>Employee Number*: <input type='text' name='emp_no' size=4 autocomplete='off' /></p>";
	}
    $currentQ = "SELECT periodID FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods WHERE now() BETWEEN periodStart AND periodEnd";
    $currentR = mysql_query($currentQ, $db_master);
    list($ID) = mysql_fetch_row($currentR);

    $query = "SELECT date_format(periodStart, '%M %D, %Y'), date_format(periodEnd, '%M %D, %Y'), periodID FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods WHERE periodStart < now() ORDER BY periodID DESC";
    $result = mysql_query($query, $db_master);

    echo '<p>Pay Period: <select name="period">
        <option>Please select a payperiod to view.</option>';

    while ($row = mysql_fetch_array($result)) {
        echo "<option value=\"$row[2]\"";
        if ($row[2] == $ID) { echo ' SELECTED';}
        echo ">($row[0] - $row[1])</option>";
    }
    echo '</select></p>';

    echo '<button name="submit" type="submit">Submit</button>
    <input type="hidden" name="submitted" value="TRUE" />
    </form>';
}
if ($_SESSION['logged_in'] == True) {
	echo "<div class='log_btn'><a href='" . $_SERVER["PHP_SELF"] . "?logout=1'>Logout</a></div>";
} else {
	echo "<div class='log_btn'><a href='" . $_SERVER["PHP_SELF"] . "?login=1'>Login</a></div>"; 	//	 class='loginbox'
}

include ('../../../src/footer.php');
?>
