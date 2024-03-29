<?php
include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class TsAreasReport extends FanniePage {
    public $page_set = 'Plugin :: TimesheetPlugin';

    function preprocess(){
        $this->header = "Timeclock - Labor Category Totals";
        $this->title = "Timeclock - Labor Category Totals";
        return True;
    }

    function body_content(){
        global $FANNIE_OP_DB, $FANNIE_PLUGIN_SETTINGS;
        $ts_db = FannieDB::get($FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']);

        include('./includes/header.html');

        echo "<form action='".$_SERVER['PHP_SELF']."' method=GET>";

        $currentQ = $ts_db->prepare("SELECT periodID 
            FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods 
            WHERE ".$ts_db->now()." BETWEEN periodStart AND periodEnd");
        $currentR = $ts_db->execute($currentQ);
        list($ID) = $ts_db->fetch_row($currentR);

        $query = $ts_db->prepare("SELECT date_format(periodStart, '%M %D, %Y') as periodStart, 
            date_format(periodEnd, '%M %D, %Y') as periodEnd, periodID 
            FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods 
            WHERE periodStart < ".$ts_db->now()." ORDER BY periodID DESC");
        $result = $ts_db->execute($query);

        echo '<div class="form-group">
            <label>Starting Pay Period</label>
            <select name="period" class="form-control">
            <option>Please select a starting pay period.</option>';

        while ($row = $ts_db->fetchRow($result)) {
            echo "<option value=\"" . $row['periodID'] . "\"";
            if ($row['periodID'] == $ID) { echo ' SELECTED';}
            echo ">(" . $row['periodStart'] . " - " . $row['periodEnd'] . ")</option>";
        }

        echo '</select></div>
            <div class="form-group">
            <button value="export" class="btn btn-default" name="Export">Run</button>
            </div></form>';

        if (FormLib::get_form_value('Export') == 'export') {
            $periodID = FormLib::get_form_value('period',0);
    
            $query = $ts_db->prepare("SELECT s.ShiftID as id, 
                CASE WHEN s.NiceName='' OR s.NiceName IS NULL THEN s.ShiftName
                ELSE s.NiceName END as area
                FROM (SELECT ShiftID, NiceName, ShiftName, ShiftOrder 
                FROM ".$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].".shifts WHERE visible = 1) s 
                GROUP BY s.ShiftID ORDER BY s.ShiftOrder");
            // echo $query;
            $result = $ts_db->execute($query);
            echo "<table class=\"table table-bordered table-striped\"><thead>\n<tr>
                <th>ID</th><th>Area</th><th>Total</th><th>wages</th></tr></thead>\n<tbody>\n";
            $queryP = $ts_db->prepare("
                SELECT SUM(IF(ID = 31, t.vacation,t.hours)) as total 
                FROM ". $FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].".timesheet t 
                WHERE t.periodID = ? 
                    AND t.area = ?");
            $query2P = $ts_db->prepare("
                SELECT SUM(e.wage) as agg 
                FROM ".$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].".TimesheetEmployees e, ".
                    $FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].".timesheet t 
                WHERE t.emp_no = e.timesheetEmployeeID AND t.periodID = ? AND t.area = ?");
            $nfm = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
            while ($row = $ts_db->fetch_row($result)) {

                echo "<tr><td>".$row['id']."</td><td>".$row['area']."</td><td align='right'>";
        
                // echo $query1;
                $result1 = $ts_db->execute($queryP,array($periodID,$row['id']));
                $totHrs = $ts_db->fetch_row($result1);
                $tot = ($totHrs[0]) ? $totHrs[0] : 0;
        
                echo $tot . "</td>";
                
                // echo $query2;
                $result2 = $ts_db->execute($query2P,array($periodID,$row['id']));
                $totAgg = $ts_db->fetch_row($result2);
                $agg = ($totAgg[0]) ? $totAgg[0] : 0;
        
                // echo "<td align='right'>$agg</td>";
        
                $wages = $tot * $agg;
                
                echo "<td align='right'>" . $nfm->format($wages) . "</td></tr>\n";
            }
        }
        echo "</tbody></table>\n";
    }
}

FannieDispatch::conditionalExec(false);

