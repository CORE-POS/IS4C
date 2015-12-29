<?php 
include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class TimesheetPage extends FanniePage 
{
    public $page_set = 'Plugin :: TimesheetPlugin';

    protected $auth_classes = array('timesheet_access');

    private $display_func;
    private $errors;

    /**
      Preprocess runs before the page is displayed.
      It handles form input.
    */
    public function preprocess()
    {
        global $FANNIE_PLUGIN_SETTINGS;
        $ts_db = FannieDB::get($FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']);
        $this->header = 'Timeclock - Entry';
        $this->title = 'Fannie - Administration Module';
        $this->display_func = '';

        $max = ($_GET['max']) ? 10 : 10;  // Max number of entries.

        if (!$this->current_user && $_GET['login'] == 1 ) {
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
            $query = $ts_db->prepare("
                SELECT periodID, 
                    periodStart 
                FROM " . $FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'] . $ts_db->sep() . "payperiods 
                WHERE ? BETWEEN DATE(periodStart) AND DATE(periodEnd)"
            );

            $result = $ts_db->execute($query,array($date));
            $row = $ts_db->fetchRow($result);
            $periodID = $row['periodID'];
            $periodStart = $row['periodStart'];

            $query = $ts_db->prepare("
                SELECT DATEDIFF(CURDATE(), DATE(periodEnd)) 
                FROM " . $FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'] . $ts_db->sep() . "payperiods 
                WHERE periodID = ?");

            $result = $ts_db->execute($query,array($periodID));
            $row = $ts_db->fetchRow($result);
            $datediff = $row[0];
        
            $employee = new TimesheetEmployeesModel($ts_db);
            $employee->timesheetEmployeeID(FormLib::get('emp_no'));
    
            if ($_POST['emp_no'] && ($_POST['emp_no'] != '')) {
                if (!is_numeric($_POST['emp_no'])) {
                    $this->errors[] = 'Employee number entered is not numeric.';
                } elseif (!$employee->load()) {
                    $this->errors[] = 'Error finding that Employee Number.';
                } else {
                    $emp_no = $_POST['emp_no'];
                }
            } else {
                $this->errors[] = 'Please enter an Employee Number.';
            }
    
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
                $query = $ts_db->prepare("
                    SELECT * 
                    FROM " . $FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'] . $ts_db->sep() . "timesheet 
                    WHERE emp_no=? 
                        AND tdate BETWEEN ? AND ?
                        AND area <> 31");
        
                $result = $ts_db->execute($query,array($emp_no,$date . ' 00:00:00', $date . ' 23:59:59'));
                if ($ts_db->num_rows($result) == 0) { // Success.
                    $successcount = 0;
                    $insP = $ts_db->prepare("INSERT INTO ".
                        $FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].
                        ".timesheet (emp_no, hours, area, tdate, periodID)
                        VALUES (?,?,?,?,?)");
                    for ($i = 1; $i <= $entrycount; $i++) {
                        $result = $ts_db->execute($insP,array(
                            $emp_no, $_POST['hours'.$i],
                            $_POST['area'.$i],$date,$periodID
                        ));
                        if ($result) {
                            $successcount++;
                        }
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

        return true;
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

    function success_content()
    {
        echo "<div class='alert alert-success'>Success!</div>";
        echo '<p>If you like, you may <a href="'.$_SERVER['PHP_SELF'].'">add more hours</a> 
            or you can <a href="./ViewsheetPage.php">edit hours</a>.</p>';
    }

    function error_content()
    {
        echo '<div class="alert alert-danger">The following error(s) occurred:';
        foreach ($this->errors AS $message) {
            echo "<p> - $message</p>";
        }
        echo '</div><p><a href="'.$_SERVER['PHP_SELF'].'">Please try again.</a></p>';
    }

    function body_content()
    {
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

        echo '<form action="'.$_SERVER['PHP_SELF'].'" method="POST" name="timesheet" id="timesheet">';
        echo '<p><div class="form-inline">';
        if ($this->current_user) {
            echo '
                <div class="form-group">
                Name: <select name="emp_no" class="form-control">
                <option value="error">Select staff member</option>' . "\n";
        
            $model = new TimesheetEmployeesModel($ts_db);
            $model->active(1);
            foreach ($model->find('firstName') as $obj) {
                printf('<option value="%d">%s %s</option>',
                    $obj->timesheetEmployeeID(),
                    $obj->firstName(),
                    substr($obj->lastName(), 0, 1)
                );
            }
            echo '</select>*</div>';
        } else {
            echo "<div class=\"form-group\">
                Employee Number*: <input type='text' name='emp_no' class=\"form-control\"
                    value='".$_COOKIE['timesheet']."' size=4 autocomplete='off' />
                </div>";
        }
        echo '<div class="form-group">
            Date*: <input type="text" name="date" value="'. date('Y-m-d') .'" 
                class="form-control date-field"
                size=10 class="datepicker" alt="Tip: try cmd + arrow keys" />
            </div>
            </div></p>';
            
        echo '<table class="table table-bordered">';
        echo "<tr><td align='right'><b>Total Hours</b></td><td align='center'><strong>Labor Category</strong></td>";
        $queryP = $ts_db->prepare("SELECT IF(NiceName='', ShiftName, NiceName), ShiftID 
            FROM " . $FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'] . ".shifts 
            WHERE visible=true ORDER BY ShiftOrder ASC");
        $max = 5;
        for ($i = 1; $i <= $max; $i++) {
            echo "<tr><td align='right'><input class=\"form-control price-field\" 
                type='text' name='hours" . $i . "' size=6></input></td>";

            $result = $ts_db->execute($queryP);
            echo '<td><select class="form-control" name="area' . $i . '" id="area' . $i . '">
                <option>Please select an area of work.</option>';
            while ($row = $ts_db->fetch_row($result)) {
                echo "<option id =\"$i$row[1]\" value=\"$row[1]\">$row[0]</option>";
            }
            echo '</select></td></tr>' . "\n";
        }
        echo '</table>';
        echo '<p>
            <button name="submit" class="btn btn-default" type="submit">Submit</button>
            <input type="hidden" name="submitted" value="TRUE" />
            </p></form>';   
        if ($this->current_user){
            echo "<div class='log_btn'><a href='" . $FANNIE_URL . "auth/ui/loginform.php?logout=1'>logout</a></div>";
        } else {
            echo "<div class='log_btn'><a href='" . $_SERVER["PHP_SELF"] . "?login=1'>login</a></div>";  //   class='loginbox'
        }
    }
}

FannieDispatch::conditionalExec();

