<?php
require_once(dirname(__FILE__).'/../../../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class TimesheetExport extends FannieReportPage {

    function preprocess(){
        $this->title = "Timeclock - EXPORT";
        $this->header = "TimeclockExport";
        $this->report_cache = 'none';

        if (FormLib::get_form_value('Run') == 'run'){
            /**
              Form submission occurred

              Change content function, turn off the menus,
              set up headers
            */
            $this->content_function = "report_content";
            $this->has_menus(False);

            /**
              Check if a non-html format has been requested
            */
            if (isset($_REQUEST['excel']) && $_REQUEST['excel'] == 'xls')
                $this->report_format = 'xls';
            elseif (isset($_REQUEST['excel']) && $_REQUEST['excel'] == 'csv')
                $this->report_format = 'csv';
        }

        return True;
    }

    function form_content(){
        global $FANNIE_OP_DB, $FANNIE_PLUGIN_SETTINGS;
        $ts_db = FannieDB::get($FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']);

        include('./includes/header.html');

        echo "<form action='".$_SERVER['PHP_SELF']."' method=GET>";

        $currentQ = $ts_db->prepare_statement("SELECT periodID 
            FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods 
            WHERE ".$ts_db->now()." BETWEEN periodStart AND periodEnd");
        $currentR = $ts_db->exec_statement($currentQ);
        list($ID) = $ts_db->fetch_row($currentR);

        $query = $ts_db->prepare_statement("SELECT date_format(periodStart, '%M %D, %Y') as periodStart, 
            date_format(periodEnd, '%M %D, %Y') as periodEnd, periodID 
            FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods 
            WHERE periodStart < ".$ts_db->now()." ORDER BY periodID DESC");
        $result = $ts_db->exec_statement($query);

        echo '<p>Pay Period: <select name="period">
            <option>Please select a payperiod to view.</option>';

        while ($per = $ts_db->fetch_array($result)) {
            echo "<option value=\"" . $per['periodID'] . "\"";
            if ($per['periodID'] == ($ID)) { echo ' SELECTED';}
            echo ">(" . $per['periodStart'] . " - " . $per['periodEnd'] . ")</option>";
        }
        echo '</select><button value="run" name="Run">Run</button></p></form>';
    }

    function fetch_report_data(){
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;
        $ts_db = FannieDB::get($FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']);
        $periodID = FormLib::get_form_value('period',0);
        $_SESSION['periodID'] = $periodID;
        $perDatesQ = $ts_db->prepare_statement("SELECT * FROM ".
            $FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].".payperiods WHERE periodID = ?");
        $perDatesR = $ts_db->exec_statement($perDatesQ,array($periodID));
        $perDates = $ts_db->fetch_array($perDatesR);

        $dumpQ = $ts_db->prepare_statement("SELECT t.date, e.emp_no, e.LastName, e.FirstName, t.area, SUM(t.hours) AS hours 
            FROM (SELECT emp_no,FirstName, LastName FROM ".$FANNIE_OP_DB.".employees WHERE empActive = 1) e 
            LEFT JOIN ".$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].".timesheet t ON e.emp_no = t.emp_no 
            AND t.periodID = ? GROUP BY e.emp_no");
        $result = $ts_db->exec_statement($dumpQ,array($periodID));

        $data = array();
        $data[] = array("TC");
        $data[] = array("00001");
        $nonPTOtotalP = $ts_db->prepare_statement("SELECT SUM(hours) FROM ".
            $FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].".timesheet 
            WHERE periodID = ? AND area <> 31 AND emp_no = ?");
        $weekoneP = $ts_db->prepare_statement("SELECT ROUND(SUM(hours), 2)
                FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
                INNER JOIN {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods AS p
                ON (p.periodID = t.periodID)
                WHERE t.emp_no = ?
                AND t.periodID = ?
                AND t.area <> 31
                AND t.tdate >= DATE(p.periodStart)
                AND t.tdate < DATE(date_add(p.periodStart, INTERVAL 7 day))");
        $weektwoP = $ts_db->prepare_statement("SELECT ROUND(SUM(hours), 2)
                FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
                INNER JOIN {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods AS p
                ON (p.periodID = t.periodID)
                WHERE t.emp_no = ?
                AND t.periodID = ?
                AND t.area <> 31
                AND t.tdate >= DATE(date_add(p.periodStart, INTERVAL 7 day)) 
                AND t.tdate <= DATE(p.periodEnd)");
        $vacationP = $ts_db->prepare_statement("SELECT ROUND(SUM(hours), 2)
                FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
                WHERE t.emp_no = ?
                AND t.periodID = ?
                AND t.area = 31");
        while ($row = $ts_db->fetch_row($result)) {
            $nonPTOtotalr = $ts_db->exec_statement($nonPTOtotalP,array($periodID,$row['emp_no']));
            $nonPTOtotal = $ts_db->fetch_row($nonPTOtotalr);
            
            $nonPTOtot = $nonPTOtotal[0];
            $date = (is_null($row['date'])) ? 0 : $row['date'];
            $area = (is_null($row['area'])) ? 0 : $row['area'];
            $hours = (is_null($row['hours'])) ? 0 : $row['hours'];
        
            if ($hours > 0) {


                $weekoneR = $ts_db->exec_statement($weekoneP,array($row['emp_no'],$periodID));
                $weektwoR = $ts_db->exec_statement($weektwoP,array($row['emp_no'],$periodID));
                $vacationR = $ts_db->exec_statement($vacationP,array($row['emp_no'],$periodID));

                list($weekone) = $ts_db->fetch_row($weekoneR);
                if (is_null($weekone)) $weekone = 0;
                list($weektwo) = $ts_db->fetch_row($weektwoR);
                if (is_null($weektwo)) $weektwo = 0;
                list($pto) = $ts_db->fetch_row($vacationR);
                if (is_null($pto)) $pto = 0;

                $ft = 40;


                $otime1 = (($weekone - $ft) < 0) ? 0 : $weekone - $ft;
                $otime2 = (($weektwo - $ft) < 0) ? 0 : $weektwo - $ft;
                $otime = $otime1 + $otime2;
                $total = ($otime != 0) ? $ft + (($otime2 != 0) ? $ft : $weektwo) : $nonPTOtot;
                
                $record = array(strftime("%D",strtotime($date)),
                        $row['emp_no'],$row['LastName'], 
                        $row['FirstName'], "01", 
                        number_format($total,2));
                $data[] = $record;

                if ($weekone > $ft || $weektwo > $ft) {
                    $ot_record = array(strftime("%D",strtotime($date)),
                            $row['emp_no'],$row['LastName'], 
                            $row['FirstName'], "02", 
                            number_format($otime,2));
                    $data[] = $ot_record;
                }
                if ($pto != 0) {
                    $pto_record = array(strftime("%D",strtotime($date)),
                            $row['emp_no'],$row['LastName'], 
                            $row['FirstName'], "08", 
                            number_format($pto,2));
                    $data[] = $pto_record;
                }

            } 
            else {
                $null_record = array(strftime("%D",strtotime($row['periodEnd'])),
                            $row['emp_no'],$row['LastName'], 
                            $row['FirstName'], "01", "0.00");
                $data[] = $null_record; 
            }
        }
        return $data;
    }

    function earncode($val) {
        // Surepay earning codes:
        //      01 regular
        //      02 o/time  = >40 / week
        //      08 other h
        
        $area = "01";
        
        return $area;
    }
}

FannieDispatch::conditionalExec(false);

?>
