<?php
require_once(dirname(__FILE__).'/../../../../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class TsAdminDelete extends FanniePage {
    protected $header = 'Timesheet Management';
    protected $title = 'Fannie - Administration Module';

    function preprocess(){
        global $FANNIE_OP_DB,$FANNIE_PLUGIN_SETTINGS;
        $ts_db = FannieDB::get($FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']);
        if (isset($_GET['submitted']) && $_GET['confirm'] == 'confirm') {
            // Delete then redirect...
            $query = $ts_db->prepare_statement("DELETE FROM timesheet
                    WHERE date=?
                    AND emp_no=?
                    AND periodID=?");
            $result = $ts_db->exec_statement($query,array($_GET['date'],
                        $_GET['emp_no'],$_GET['periodID']));
    
            if ($result) {
                $url = sprintf('TsAdminView.php?emp_no=%d&periodID=%d',
                        $_GET['emp_no'],$_GET['periodID']);
                header('Location: '.$url);
                return False;
            }
    
        } elseif (isset($_GET['submitted']) && $_GET['confirm'] == 'skip') {
            // Redirect...
            $url = sprintf('TsAdminView.php?emp_no=%d&periodID=%d',
                    $_GET['emp_no'],$_GET['periodID']);
            header('Location: '.$url);
        }
        return True;
    }

    function body_content(){
        global $FANNIE_OP_DB,$FANNIE_PLUGIN_SETTINGS;
        $ts_db = FannieDB::get($FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']);
        $query = $ts_db->prepare_statement("SELECT 
            CASE area WHEN 0 THEN TIME_FORMAT(time_in, '%H:%i') ELSE TIME_FORMAT(time_in, '%h:%i %p') END,
                    CASE area WHEN 0 THEN time_out ELSE TIME_FORMAT(time_out, '%h:%i %p') END,
                    ShiftName,
                    area
                    ID
            FROM timesheet INNER JOIN shifts ON (shifts.ShiftID = timesheet.area)
            WHERE date=?
            AND emp_no=?
            AND periodID=?
            ORDER BY ID asc");
        $result = $ts_db->exec_statement($query,array($_GET['date'],$_GET['emp_no'],$_GET['periodID']));
        if (!$result) echo '<p>' . $ts_db->error() . '</p>';
        $empQ = $ts_db->prepare_statement("SELECT CONCAT(firstname, ' ', lastname), 
            date_format(?, '%M %D, %Y') FROM ".
            $FANNIE_OP_DB.$ts_db->sep()."employees WHERE emp_no=?");
        $empR = $ts_db->exec_statement($empQ,array($_GET['date'],$_GET['emp_no']));
        list($name, $date) = $ts_db->fetch_row($empR);
    
        ob_start();
        echo '<form action="' . $_SERVER['PHP_SELF'] . '" method="get">
            <input type="hidden" name="submitted" value="true" />
            <input type="hidden" name="function" value="delete" />
            <input type="hidden" name="emp_no" value="' . $_GET['emp_no'] . '" />
            <input type="hidden" name="periodID" value="' . $_GET['periodID'] . '" />
            <input type="hidden" name="date" value="' . $_GET['date'] . '" />
            <fieldset><legend>Hours for ' . $name . ' on ' . $date . '</legend>
            <ul>';
        // Fetch results...
        while ($row = $ts_db->fetch_row($result)) {
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
        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec(false);

?>
