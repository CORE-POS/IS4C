<?php
require_once('../includes/mysqli_connect.php');
mysqli_select_db($db_master, 'is4c_log');

if (isset($_GET['submitted']) && $_GET['confirm'] == 'confirm') {
    // Delete then redirect...
    $query = "DELETE FROM timesheet
        WHERE date='" . escape_data($_GET['date']) . "'
        AND emp_no=" . escape_data($_GET['emp_no']) . "
        AND periodID=" . escape_data($_GET['periodID']);
    $result = mysqli_query($db_master, $query);
    
    if ($result) {
        header("Location:" . $_SERVER['PHP_SELF'] . "?function=view&periodID={$_GET['periodID']}&emp_no={$_GET['emp_no']}");
    }
    
} elseif (isset($_GET['submitted']) && $_GET['confirm'] == 'skip') {
    // Redirect...
    header("Location:" . $_SERVER['PHP_SELF'] . "?function=view&periodID={$_GET['periodID']}&emp_no={$_GET['emp_no']}");
} else { // Draw the form...
    $query = "SELECT CASE area WHEN 0 THEN TIME_FORMAT(time_in, '%H:%i') ELSE TIME_FORMAT(time_in, '%h:%i %p') END,
                    CASE area WHEN 0 THEN time_out ELSE TIME_FORMAT(time_out, '%h:%i %p') END,
                    ShiftName,
                    area
                    ID
            FROM timesheet INNER JOIN shifts ON (shifts.ShiftID = timesheet.area)
            WHERE date='" . escape_data($_GET['date']) . "'
            AND emp_no=" . escape_data($_GET['emp_no']) . "
            AND periodID=" . escape_data($_GET['periodID']) . "
            ORDER BY ID asc";
    $result = mysqli_query($db_master, $query);
    if (!$result) echo '<p>' . mysqli_error($db_master) . '</p>';
    $empQ = "SELECT CONCAT(firstname, ' ', lastname), date_format('" . escape_data($_GET['date']) . "', '%M %D, %Y') FROM is4c_op.employees WHERE emp_no=" . escape_data($_GET['emp_no']);
    $empR = mysqli_query($db_master, $empQ);
    list($name, $date) = mysqli_fetch_row($empR);
    
    echo '<form action="' . $_SERVER['PHP_SELF'] . '" method="get">
            <input type="hidden" name="submitted" value="true" />
            <input type="hidden" name="function" value="delete" />
            <input type="hidden" name="emp_no" value="' . $_GET['emp_no'] . '" />
            <input type="hidden" name="periodID" value="' . $_GET['periodID'] . '" />
            <input type="hidden" name="date" value="' . $_GET['date'] . '" />
            <fieldset><legend>Hours for ' . $name . ' on ' . $date . '</legend>
            <ul>';
    // Fetch results...
    while ($row = mysqli_fetch_row($result)) {
        if ($row[3] == 0) {
            $hours = substr($row[0], 0, 2) == 0 ? "" : substr($row[0], 1, 1) . " hour(s), ";
            $msg = "<li>With a lunch of " . $hours . substr($row[0], 3, 2) .  " minutes.</li>";
        } else {
            echo "<li>From $row[0] to $row[1] as $row[2].</li>";
        }
    }
    
    echo $msg . '</ul>
            </fieldset>
                <button type="submit" name="confirm" value="confirm">Delete It!</button>
                <button type="submit" name="confirm" value="skip">I changed my mind!</button>
        </form>';
}


?>