<?php
require_once(dirname(__FILE__).'/../../../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class TimesheetGeneralReport extends FannieReportPage {

    function body_content(){
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;
        $ts_db = FannieDB::get($FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']);

        ob_start();

        echo "<form action='report.php' method=GET>";

        $currentQ = $ts_db->prepare_statement("SELECT periodID FROM 
            {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods WHERE 
            ".$ts_db->now()." BETWEEN periodStart AND periodEnd");
        $currentR = $ts_db->exec_statement($currentQ);
        list($ID) = $ts_db->fetch_row($currentR);

        $query = $ts_db->prepare_statement("SELECT date_format(periodStart, '%M %D, %Y') as periodStart, 
            date_format(periodEnd, '%M %D, %Y') as periodEnd, periodID 
            FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods 
            WHERE periodStart < ".$ts_db->now()." ORDER BY periodID DESC");
        $result = $ts_db->exec_statement($query);

        echo '<p>Starting Pay Period: <select name="period">
            <option>Please select a starting pay period.</option>';

        while ($row = $ts_db->fetch_array($result)) {
            echo "<option value=\"" . $row['periodID'] . "\"";
            if ($row['periodID'] == $ID) { echo ' SELECTED';}
            echo ">(" . $row['periodStart'] . " - " . $row['periodEnd'] . ")</option>";
        }
        echo '</select><br /><button value="export" name="Export">Export</button></p></form>';

        if ($_GET['Export'] == 'export') {
            $periodID = $_GET['period'];
    
            $query = $ts_db->prepare_statement("SELECT s.ShiftID as id, 
                IF(s.NiceName='', s.ShiftName, s.NiceName) as area
                FROM (SELECT ShiftID, NiceName, ShiftName, ShiftOrder FROM ".
                $FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].".shifts WHERE visible = 1) s 
                GROUP BY s.ShiftID ORDER BY s.ShiftOrder");
            // echo $query;
            $result = $ts_db->exec_statement($query);

            $oneP = $ts_db->prepare_statement("SELECT SUM(IF(? = 31, t.vacation,t.hours)) as total 
                    FROM ". $FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].".timesheet t 
                    WHERE t.periodID = ? AND t.area = ?");
            $twoP = $ts_db->prepare_statement("SELECT SUM(e.pay_rate) as agg FROM ".
                    $FANNIE_OP_DB.$ts_db->sep()."employees e, ".
                    $FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].".timesheet t 
                    WHERE t.emp_no = e.emp_no AND t.periodID = ? AND t.area = ?");
            echo "<table cellpadding='5'><thead>\n<tr>
                <th>ID</th><th>Area</th><th>Total</th><th>wages</th></tr></thead>\n<tbody>\n";
            while ($row = $ts_db->fetch_row($result)) {

                echo "<tr><td>".$row['id']."</td><td>".$row['area']."</td><td align='right'>";
        
                // echo $query1;
                $result1 = $ts_db->exec_statement($oneP,array($row['id'],$periodID,$row['id']));
                $totHrs = $ts_db->fetch_row($result1);
                $tot = ($totHrs[0]) ? $totHrs[0] : 0;
        
                echo $tot . "</td>";
                
                // echo $query2;
                $result2 = $ts_db->exec_statement($twoP,array($periodID,$row['id']));
                $totAgg = $ts_db->fetch_row($result2);
                $agg = ($totAgg[0]) ? $totAgg[0] : 0;
        
                $wages = $tot * $agg;
                        
                echo "<td align='right'>" . money_format('%#8n', $wages) . "</td></tr>\n";
            }
            echo "</tbody></table>\n";
        }
        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec(false);

?>
