<?php
include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class TsEmployeesReport extends FanniePage {
    public $page_set = 'Plugin :: TimesheetPlugin';

    function preprocess(){
        $this->header = "Timeclock - Employees Report";
        $this->title = "Timeclock - Employees Report";
        return True;
    }

    function body_content(){
        global $FANNIE_OP_DB, $FANNIE_PLUGIN_SETTINGS;
        $ts_db = FannieDB::get($FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']);

        include('./includes/header.html');
        //  FULL TIME: Number of hours per week
        $ft = 40;

        echo "<form action='".$_SERVER['PHP_SELF']."' method=GET class=\"form-horizontal\">";

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

        echo '<div class="row form-group">
            <label class="col-sm-2">Starting Pay Period</label>
            <div class="col-sm-5">
            <select class="form-control" name="period">
            <option>Please select a starting pay period.</option>';

        while ($row = $ts_db->fetchRow($result)) {
            echo "<option value=\"" . $row['periodID'] . "\"";
            if ($row['periodID'] == $ID) { echo ' SELECTED';}
            echo ">(" . $row['periodStart'] . " - " . $row['periodEnd'] . ")</option>";
        }

        echo "</select></div></div>";
        echo '<div class="row form-group">
            <label class="col-sm-2">Ending Pay Period</label>
            <div class="col-sm-5">
            <select class="form-control" name="end">
            <option value=0>Please select an ending pay period.</option>';
        $result = $ts_db->execute($query);
        while ($row = $ts_db->fetchRow($result)) {
            echo "<option value=\"" . $row['periodID'] . "\"";
            if ($row['periodID'] == $ID) { echo ' SELECTED';}
            echo ">(" . $row['periodStart'] . " - " . $row['periodEnd'] . ")</option>";
        }
        echo '</select></div></div>
            <p>
                <button class="btn btn-default" value="run" name="run">Run</button>
            </p>
            </form>';
        if (FormLib::get_form_value('run') == 'run') {
            $periodID = FormLib::get_form_value('period',0);
            $end = FormLib::get_form_value('end',$periodID);
            if ($end == 0) $end = $periodID;
    
            $employees = new TimesheetEmployeesModel($ts_db);
            $employees->active(1);
            $areasq = $ts_db->prepare("SELECT ShiftName, ShiftID 
                FROM ".$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].".shifts 
                WHERE visible = 1 AND ShiftID <> 31 ORDER BY ShiftOrder");
            $areasr = $ts_db->execute($areasq);
            $shiftInfo = array();
            while($row = $ts_db->fetch_row($areasr)){
                $shiftInfo[$row['ShiftID']] = $row['ShiftName'];
            }
    
            $query1 = $ts_db->prepare("SELECT date_format(periodStart, '%M %D, %Y') as periodStart, 
                periodID as pid 
                FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods 
                WHERE periodID = ?");
            $result1 = $ts_db->execute($query1,array($periodID));
            $periodStart = $ts_db->fetch_row($result1);

            $query2 = $ts_db->prepare("SELECT date_format(periodEnd, '%M %D, %Y') as periodEnd, 
                periodID as pid 
                FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods 
                WHERE periodID = ?");
            $result2 = $ts_db->execute($query2,array($end));
            $periodEnd = $ts_db->fetch_row($result2);
    
            // $periodct = ($end !== $periodID) ? $end - $periodID : 1;
            $periodct = 0;
            $p = array();
            for ($i = $periodStart[1]; $i <= $periodEnd[1]; $i++) {
                // echo $i;
                $periodct++;
                $p[] = $i;
            }
            echo "<br />";
            echo "<h3>" . $periodStart[0] . " &mdash; " . $periodEnd[0] . "</h3>\n";
            echo "Number of payperiods: " . $periodct . "\n";
            // 
            // END TITLE    
            echo "<br />";
    

            echo "<table class=\"table table-bordered table-striped\"><thead>\n<tr><th>Name</th><th>Wage</th>";
            foreach ($shiftInfo as $sID => $sName) {
                echo "<div id='vth'><th>" . substr($sName,0,6) . "</th></div>";  // -- TODO vertical align th, static col width
            }
            echo "</th><th>OT</th><th>PTO used</th><th>PTO new</th><th>Total</th></tr></thead>\n<tbody>\n";
            $PTOnew = array();
            $totalP = $ts_db->prepare("SELECT SUM(hours) FROM ".
                $FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].".timesheet 
                WHERE periodID >= ? AND periodID <= ? AND emp_no = ?");
            $depttotP = $ts_db->prepare("SELECT SUM(t.hours) FROM 
                {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet t 
                WHERE t.periodID >= ? AND t.periodID <= ?
                AND t.emp_no = ? AND t.area = ?");
            $weekoneQ = $ts_db->prepare("SELECT ROUND(SUM(hours), 2) 
                FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
                INNER JOIN {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods AS p 
                ON (p.periodID = t.periodID)
                WHERE t.emp_no = ?
                AND t.periodID = ?
                AND t.area <> 31
                AND t.tdate >= DATE(p.periodStart)
                AND t.tdate < DATE(date_add(p.periodStart, INTERVAL 7 day))");
            $weektwoQ = $ts_db->prepare("SELECT ROUND(SUM(hours), 2)
                FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
                INNER JOIN {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods AS p
                ON (p.periodID = t.periodID)
                WHERE t.emp_no = ?
                AND t.periodID = ?
                AND t.area <> 31
                AND t.tdate >= DATE(date_add(p.periodStart, INTERVAL 7 day)) 
                AND t.tdate <= DATE(p.periodEnd)");
            $usedP = $ts_db->prepare("SELECT SUM(hours) FROM ".
                $FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].".timesheet 
                WHERE periodID >= ? AND periodID <= ? AND 
                emp_no = ? AND area = 31");
            $nonPTOtotalP = $ts_db->prepare("SELECT SUM(hours) FROM ".
                $FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].".timesheet 
                WHERE periodID >= ? AND periodID <= ?
                AND area <> 31 AND emp_no = ?");
            foreach ($employees->find('lastName') as $employee) {
                $emp_no = $employee->timesheetEmployeeID();
        
                $totalr = $ts_db->execute($totalP,array($periodID,$end,$emp_no));
                $total = $ts_db->fetch_row($totalr);
                $color = ($total[0] > (80 * $periodct)) ? "FF0000" : "000000";
                echo "<tr><td>".ucwords($employee->firstName())." - " . ucwords(substr($employee->firstName(),0,1)) . ucwords(substr($employee->lastName(),0,1)) . "</td><td align='right'>$" . $employee->wage() . "</td>";
                $total0 = (!$total[0]) ? 0 : number_format($total[0],2);
                //
                //  LABOR DEPARTMENT TOTALS
        
                foreach($shiftInfo as $area => $shiftName){
                    // echo $depttotq;
                    $depttotr = $ts_db->execute($depttotP,array($periodID,$end,$emp_no,$area));
                    $depttot = $ts_db->fetch_row($depttotr);
                    $depttotal = (!$depttot[0]) ? 0 : number_format($depttot[0],2);
                    echo "<td align='right'>" . $depttotal . "</td>";
                }
                //  END LABOR DEPT. TOTALS
        
                // 
                //  OVERTIME
                // 
                $otime = 0;
                $otime1 = 0;
                $otime2 = 0;
                foreach ($p as $v) {


                    $weekoneR = $ts_db->execute($weekoneQ,array($emp_no,$v));
                    $weektwoR = $ts_db->execute($weektwoQ,array($emp_no,$v));

                    list($weekone) = $ts_db->fetch_row($weekoneR);
                    if (is_null($weekone)) $weekone = 0;
                    list($weektwo) = $ts_db->fetch_row($weektwoR);
                    if (is_null($weektwo)) $weektwo = 0;

                    if ($weekone > $ft) $otime1 = $weekone - $ft;
                    if ($weektwo > $ft) $otime2 = $weektwo - $ft;
                    $otime = $otime + $otime1 + $otime2;
                }
                $OT[] = $otime;
                echo "<td align='right'>" . $otime . "</td>";
                //  END OVERTIME

                //
                //  PTO USED
                $usedR = $ts_db->execute($usedP,array($periodID,$end,$emp_no));
                $ptoused = $ts_db->fetch_row($usedR);
                $PTOuse = (!$ptoused[0]) ? 0 : number_format($ptoused[0],2);
                echo "<td align='right'>$PTOuse</td>";
        
                //
                //  PTO CALC
                $nonPTOtotalr = $ts_db->execute($nonPTOtotalP,array($periodID,$end,$emp_no));
                $nonPTOtotal = $ts_db->fetch_row($nonPTOtotalr);
                $ptoAcc = ($employee->primaryShiftID()) ? $nonPTOtotal[0] * 0.075 : 0;
                echo "<td align='right'>" . number_format($ptoAcc,2) . "</td>";
                $PTOnew[] = $ptoAcc;
        
                //
                //  TOTAL       
                echo "<td align='right'><font style='color: $color; font-weight:bold;'>" . $total0 . "</font></td>";        
        
                echo "</tr>";
            }
            echo "<tr><td colspan=2><b>TOTALS</b></td>";

            $areasr = $ts_db->execute($areasq);
            $TOT = array();
            $query = $ts_db->prepare("SELECT ROUND(SUM(t.hours),2) 
                FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet t 
                WHERE t.periodID BETWEEN ? AND ?
                AND t.area = ?");
            foreach($shiftInfo as $area => $shiftName){
                // echo $query;
                $totsr = $ts_db->execute($query,array($periodID,$end,$area));
                $tots = $ts_db->fetch_row($totsr);
                $tot = (!$tots[0] || $tots[0] == '') ? '0' : $tots[0];
                echo "<td align='right'><b>$tot</b></td>";
                $TOT[] = $tot;
            }

            $ptoq = $ts_db->prepare("SELECT ROUND(SUM(t.hours),2) 
                FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet t 
                WHERE t.periodID BETWEEN ? AND ?
                AND t.area = 31");
            $ptor = $ts_db->execute($ptoq,array($periodID,$end));
            $pto = $ts_db->fetch_row($ptor);

            $OTTOT = number_format(array_sum($OT),2);
            echo "<td><b>$OTTOT</b></td>";

            $PTOUSED = (!$pto[0] || $pto[0] == '') ? '0' : $pto[0];
            echo "<td><b>$PTOUSED</b></td>";
    
            $PTOTOT = number_format(array_sum($PTOnew),2);
            echo "<td><b>$PTOTOT</b></td>";
    
            $TOTAL = number_format(array_sum($TOT),2);
            echo "<td><b>$TOTAL</b></td>";
    
            echo"</tr>";
            echo "</tbody></table>\n";
        } // end 'run' button
    }
}

FannieDispatch::conditionalExec(false);

