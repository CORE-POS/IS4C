<?php
// require_once('../includes/mysqli_connect.php');
$db_master=mysql_connect ('192.168.123.100', 'miguel', 'SunF1ower');
mysql_select_db('is4c_op', $db_master);

echo '<form action="admin.php" method="get">
    <div id="box">
        <p><input type="radio" name="function" value="view" id="view" checked="checked" /><label for="view">View/Edit Sheets</label></p>';
    
    $query = "SELECT FirstName, emp_no FROM is4c_op.employees where EmpActive=1 ORDER BY FirstName ASC";
    $result = mysql_query($query, $db_master);
    echo '<p>Name: <select name="emp_no">
        <option value="0">Whose sheet?</option>';
    while ($row = mysqli_fetch_array($result)) {
            echo "<option value=\"$row[1]\">$row[0]</option>\n";
    }
    echo '</select></p>';
    $currentQ = "SELECT periodID FROM is4c_log.payperiods WHERE now() BETWEEN periodStart AND periodEnd";
    $currentR = mysql_query($currentQ, $db_master);
    $row = mysql_fetch_row($currentR);
    $ID = $row[0];
    
    $query = "SELECT date_format(periodStart, '%M %D, %Y'), date_format(periodEnd, '%M %D, %Y'), periodID FROM is4c_log.payperiods WHERE periodStart < now() ORDER BY periodID DESC";
    $result = mysql_query($query, $db_master);
    
    echo '<p>Pay Period: <select name="periodID">
        <option>Please select a payperiod to view.</option>';
        
    while ($row = mysqli_fetch_array($result)) {
        echo "<option value=\"$row[2]\"";
        if ($row[2] == $ID) { echo ' SELECTED';}
        echo ">($row[0] - $row[1])</option>";
    }
    echo '</select></p>';
    echo    '<p><input type="radio" name="function" value="add" id="add" /><label for="add">Add Hours Posthumously</label></p>
    <br /><button type="submit">Master the Sheets of Time!</button>
    </div>
</form>';

?>
