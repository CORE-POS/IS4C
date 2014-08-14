<?php 
require_once(dirname(__FILE__).'/../../../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class TimesheetPage extends FanniePage {

    protected $auth_classes = array('timesheet_access');

    private $display_func;
    private $errors;

    /**
      Preprocess runs before the page is displayed.
      It handles form input.
    */
    public function preprocess(){
        global $FANNIE_PLUGIN_SETTINGS;
        $ts_db = FannieDB::get($FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']);
        $this->header = 'Timeclock - Entry';
        $this->title = 'Fannie - Administration Module';
        $this->display_func = '';

        $max = ($_GET['max']) ? 10 : 10;  // Max number of entries.

        if (!$this->current_user && $_GET['login'] == 1 ){
            $this->loginRedirect();
            return False;
        }

        if (isset($_POST['submitted'])) { // If the form has been submitted.
            // Validate the data.
            $this->errors = array();
            $date = $_POST['date'];

            if (strtotime($date) > strtotime(date('Y-m-d'))) {
                $this->errors[] = 'You can\'t enter hours for a future date.';
            }

            // Make sure we're in a valid pay period.
            $query = $ts_db->prepare_statement("SELECT periodID, periodStart FROM ".
                $FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].
                ".payperiods WHERE ? BETWEEN DATE(periodStart) AND DATE(periodEnd)");

            $result = $ts_db->exec_statement($query,array($date));
            list($periodID, $periodStart) = $ts_db->fetch_row($result);

            $query = $ts_db->prepare_statement("SELECT DATEDIFF(CURDATE(), DATE(periodEnd)) FROM ".
                $FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].".payperiods WHERE periodID = ?");

            $result = $ts_db->exec_statement($query,array($periodID));
            list($datediff) = $ts_db->fetch_row($result);
        
            $empnoChkQ = $ts_db->prepare_statement("SELECT * FROM employees WHERE emp_no = ?");
            $empnoChkR = $ts_db->exec_statement($empnoChkQ,array($_POST['emp_no']));
    
            if ($_POST['emp_no'] && ($_POST['emp_no'] != '')) {
                if (!is_numeric($_POST['emp_no'])) {
                    $this->errors[] = 'Employee number entered is not numeric.';
                } elseif ($ts_db->num_rows($empnoChkR) != 1) { 
                    $this->errors[] = 'Error finding that Employee Number.';
                } else {
                    $emp_no = $_POST['emp_no'];
                }
            } else {
                $this->errors[] = 'Please enter an Employee Number.';
            }
    
            // if ($datediff > 1) { // Bad.
            //  $this->errors[] = 'You can\'t add hours more than a day after the pay period has ended.';
            //  $date = NULL;
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
                $this->errors[] = "You didn't enter any hours or labor categories.";
            } else {
                for ($i = 1; $i <= $max; $i++) {
                    if ((isset($_POST['hours' . $i])) && (is_numeric($_POST['area' . $i]))) {
                        $hours[$i] = $_POST['hours' . $i];
                        $area[$i] = $_POST['area' . $i];
                    } 
                }
            }

            if (empty($this->errors)) { // All good.
        
                setcookie("timesheet", $emp_no, time()+60*3);
        
                // First check to make sure they haven't already entered hours for this day.
                $query = $ts_db->prepare_statement("SELECT * FROM ".
                    $FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].
                    ".timesheet WHERE emp_no=? AND tdate=? and area <> 31");
        
                $result = $ts_db->exec_statement($query,array($emp_no,$date));
                if ($ts_db->num_rows($result) == 0) { // Success.
                        // if (strtotime($date) < strtotime($periodStart)) {
                        //  echo "Previous Pay period!!!";
                    //  exit;
                    // }    
                    $successcount = 0;
                    $insP = $ts_db->prepare_statement("INSERT INTO ".
                        $FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].
                        ".timesheet (emp_no, hours, area, tdate, periodID)
                        VALUES (?,?,?,?,?)");
                    for ($i = 1; $i <= $entrycount; $i++) {
                        $result = $ts_db->exec_statement($insP,array(
                            $emp_no, $_POST['hours'.$i],
                            $_POST['area'.$i],$date,$periodID
                        ));
                        if ($ts_db->affected_rows() == 1) {$successcount++;}
                    }
                    if ($successcount == $entrycount) {
                        $this->display_func = 'ts_success';
                    } else {
                        $this->errors[] = 'ERR01: The entered hours could not be added, please try again later.';
                        $this->errors[] = 'Error: ' . $ts_db->error();
                        $this->errors[] = 'Query: ' . $query;
                        $this->display_func = 'ts_error';
                    }

                } else {
                    $this->errors[] = 'You have already entered hours for that day, please edit that day instead.';
                    $this->display_func = 'ts_error';
                }
            } else { // Report errors.
                $this->display_func = 'ts_error';
            }
        }
        return True;
    }

    function javascript_content(){
        ob_start();
        ?>
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
        <?php
        return ob_get_clean();
    }

    function success_content(){
        include ('./includes/header.html');
        echo "<div id='alert'><h1>Success!</h1>";
        echo '<p>If you like, you may <a href="'.$_SERVER['PHP_SELF'].'">add more hours</a> 
            or you can <a href="./ViewsheetPage.php">edit hours</a>.</p></div>';
    }

    function error_content(){
        include ('./includes/header.html');
        echo '<div id="alert"><p><font color="red">The following error(s) occurred:</font></p>';
        foreach ($this->errors AS $message) {
            echo "<p> - $message</p>";
        }
        echo '<p><a href="'.$_SERVER['PHP_SELF'].'">Please try again.</a></p></div>';
    }

    function body_content(){
        global $FANNIE_OP_DB, $FANNIE_URL, $FANNIE_PLUGIN_SETTINGS;
        $ts_db = FannieDB::get($FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']);
        include ('./includes/header.html');
        /**
          if preprocess() changed the setting for display_func 
          based on form input, show that content instead of
          the default form
        */
        if ($this->display_func == 'ts_success')
            return $this->success_content();
        elseif ($this->display_func == 'ts_error')
            return $this->error_content();

        echo "<body onLoad='putFocus(0,0);'>";
        echo '<form action="'.$_SERVER['PHP_SELF'].'" method="POST" name="timesheet" id="timesheet">';
        echo '<table border=0 cellpadding=4><tr>';
        if ($this->current_user){
            echo '<td><p>Name: <select name="emp_no">
                <option value="error">Select staff member</option>' . "\n";
        
            $query = $ts_db->prepare_statement("SELECT FirstName, 
                CASE WHEN LastName='' OR LastName IS NULL THEN ''
                ELSE ".$ts_db->concat('LEFT(LastName,1)',"'.'")." END,
                emp_no FROM ".$FANNIE_OP_DB.".employees where EmpActive=1 ORDER BY FirstName ASC");
            $result = $ts_db->exec_statement($query);
            while ($row = $ts_db->fetch_array($result)) {
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
        $queryP = $ts_db->prepare_statement("SELECT IF(NiceName='', ShiftName, NiceName), ShiftID 
            FROM " . $FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'] . ".shifts 
            WHERE visible=true ORDER BY ShiftOrder ASC");
        for ($i = 1; $i <= $max; $i++) {
            echo "<tr><td align='right'><input type='text' name='hours" . $i . "' size=6></input></td>";

            $result = $ts_db->exec_statement($queryP);
            echo '<td><select name="area' . $i . '" id="area' . $i . '"><option>Please select an area of work.</option>';
            while ($row = $ts_db->fetch_row($result)) {
                echo "<option id =\"$i$row[1]\" value=\"$row[1]\">$row[0]</option>";
            }
            echo '</select></td></tr>' . "\n";
        }
        echo '<tr><td><br /></td></tr>
            <tr><td colspan=2 align="center">
            <button name="submit" type="submit">Submit</button>
            <input type="hidden" name="submitted" value="TRUE" /></td></tr>
            </table></form>';   
        if ($this->current_user){
            echo "<div class='log_btn'><a href='" . $FANNIE_URL . "auth/ui/loginform.php?logout=1'>logout</a></div>";
        } else {
            echo "<div class='log_btn'><a href='" . $_SERVER["PHP_SELF"] . "?login=1'>login</a></div>";  //   class='loginbox'
        }
    }
}

FannieDispatch::conditionalExec(false);

?>
