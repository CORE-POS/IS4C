<?php
include(dirname(__FILE__).'/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class TsAdminMain extends FanniePage {
    public $page_set = 'Plugin :: TimesheetPlugin';
    protected $title = 'Timesheet Admin';
    protected $header = 'Timesheet Admin';

    function preprocess(){
        $submit = FormLib::get_form_value('function','');
        if ($submit == 'add'){
            header('Location: TsAdminAdd.php');
            return False;
        }
        else if ($submit == 'view'){
            $e = FormLib::get_form_value('emp_no');
            $p = FormLib::get_form_value('periodID');
            $url = sprintf('TsAdminView.php?emp_no=%d&periodID=%d',$e,$p);
            header('Location: '.$url);
            return False;
        }
        return True;    
    }

    function body_content(){
        global $FANNIE_OP_DB,$FANNIE_PLUGIN_SETTINGS;
        $ts_db = FannieDB::get($FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']);
        echo '<form action="TsAdminMain.php" method="get">
            <p>
            <label>
            <input type="radio" name="function" value="view" id="view" checked="checked" />View/Edit Sheets</label>
            </p>';
            
        $query = $ts_db->prepare("SELECT FirstName, emp_no FROM ".
                $FANNIE_OP_DB.$ts_db->sep()."employees where EmpActive=1 ORDER BY FirstName ASC");
        $result = $ts_db->execute($query);
        echo '<div class="form-group">';
        echo '<label>Name</label><select class="form-control" name="emp_no">
        <option value="0">Whose sheet?</option>';
        while ($row = $ts_db->fetchRow($result)) {
            echo "<option value=\"$row[1]\">$row[0]</option>\n";
        }
        echo '</select></div>';
        $currentQ = $ts_db->prepare("SELECT periodID FROM payperiods WHERE "
                .$ts_db->now()." BETWEEN periodStart AND periodEnd");
        $currentR = $ts_db->execute($currentQ);
        $row = $ts_db->fetch_row($currentR);
        $ID = $row[0];

        $query = $ts_db->prepare("SELECT date_format(periodStart, '%M %D, %Y'), 
                date_format(periodEnd, '%M %D, %Y'), periodID 
                FROM payperiods WHERE periodStart < ".$ts_db->now()." ORDER BY periodID DESC");
        $result = $ts_db->execute($query);

        echo '<div class="form-group">';
        echo '<label>Pay Period</label><select class="form-control" name="periodID">
            <option>Please select a payperiod to view.</option>';

        while ($row = $ts_db->fetchRow($result)) {
            echo "<option value=\"$row[2]\"";
            if ($row[2] == $ID) { echo ' SELECTED';}
                echo ">($row[0] - $row[1])</option>";
        }
        echo '</select></div>';
        echo '<p>
            <label>
            <input type="radio" name="function" value="add" id="add" />
            Add Hours Posthumously</label>
            </p>
            <div class="form-group">
            <button type="submit" class="btn btn-default">Master the Sheets of Time!</button>
            </div>
            </form>';
        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec();

