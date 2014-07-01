<?php
require_once(dirname(__FILE__).'/../../../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class TsWagesReport extends FanniePage {

    function preprocess(){
        $this->header = "Timeclock - Department Totals Report";
        $this->title = "Timeclock - Department Totals Report";
        setlocale(LC_MONETARY, 'en_US');
        return True;
    }

    function body_content(){
        global $FANNIE_OP_DB, $FANNIE_PLUGIN_SETTINGS;
        $ts_db = FannieDB::get($FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']);

        include('./includes/header.html');
        //  FULL TIME: Number of hours per week
        $ft = 40;

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

        echo '<p>Starting Pay Period: <select name="period">
            <option>Please select a starting pay period.</option>';

        while ($row = $ts_db->fetch_array($result)) {
            echo "<option value=\"" . $row['periodID'] . "\"";
            if ($row['periodID'] == $ID) { echo ' SELECTED';}
            echo ">(" . $row['periodStart'] . " - " . $row['periodEnd'] . ")</option>";
        }

        echo "</select><br />";
        echo '<p>Ending Pay Period: <select name="end">
            <option value=0>Please select an ending pay period.</option>';
        $result = $ts_db->exec_statement($query);
        while ($row = $ts_db->fetch_array($result)) {
            echo "<option value=\"" . $row['periodID'] . "\"";
            if ($row['periodID'] == $ID) { echo ' SELECTED';}
            echo ">(" . $row['periodStart'] . " - " . $row['periodEnd'] . ")</option>";
        }
        echo '</select><button value="export" name="Export">Export</button></p></form>';

        if (FormLib::get_form_value('Export') == 'export') {
            $periodID = FormLib::get_form_value('period',0);
            $end = FormLib::get_form_value('end',$periodID);
            if ($end == 0) $end = $periodID;
            
            // BEGIN TITLE
            // 
            $query1 = $ts_db->prepare_statement("SELECT date_format(periodStart, '%M %D, %Y') as periodStart, periodID 
                FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods WHERE periodID = ?");
            $result1 = $ts_db->exec_statement($query1,array($periodID));
            $periodStart = $ts_db->fetch_row($result1);

            $query2 = $ts_db->prepare_statement("SELECT date_format(periodEnd, '%M %D, %Y') as periodEnd, periodID 
                FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods WHERE periodID = ?");
            $result2 = $ts_db->exec_statement($query2,array($end));
            $periodEnd = $ts_db->fetch_row($result2);
    
            // $periodct = ($end !== $periodID) ? $end - $periodID : 1;
            $p = array();
            $periodct = 0;  
            for ($i = $periodStart[1]; $i <= $periodEnd[1]; $i++) {
                // echo $i;
                $periodct++;
                $p[] = $i;
            }
    
            echo "<br />";
            echo "<h3>" . $periodStart[0] . " &mdash; " . $periodEnd[0] . "</h3>";
            echo "Number of payperiods: " . $periodct;
            // 
            // END TITLE
    
            $query = $ts_db->prepare_statement("SELECT s.ShiftID as id, 
                IF(s.NiceName='', s.ShiftName, s.NiceName) as area
                FROM (SELECT ShiftID, NiceName, ShiftName, ShiftOrder 
                FROM ".$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].".shifts WHERE visible = 1 AND ShiftID <> 31) s 
                GROUP BY s.ShiftID ORDER BY s.ShiftOrder");
            // echo $query;
            $result = $ts_db->exec_statement($query);
        
            echo "<table cellpadding='5'><thead>\n<tr>
                <th>ID</th><th>Area</th><th>Total Hrs</th><!--<th>agg</th>--><th>wages</th></tr></thead>\n<tbody>\n";   
    
            $queryP = $ts_db->prepare_statement("SELECT SUM(t.hours) as total 
                FROM ". $FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].".timesheet t 
                WHERE t.periodID >= ? AND t.periodID <= ? AND t.area = ?");
            $query2P = $ts_db->prepare_statement("SELECT SUM(e.pay_rate) as agg FROM ".$FANNIE_OP_DB.".employees e, ".
                $FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].".timesheet t 
                WHERE t.emp_no = e.emp_no AND t.periodID >= ?
                AND t.periodID <= ? AND t.area = ?");
            while ($row = $ts_db->fetch_row($result)) {

                echo "<tr><td>".$row['id']."</td><td>".$row['area']."</td><td align='right'>";

                $result1 = $ts_db->exec_statement($queryP,array($periodID,$end,$row['id']));
                $totHrs = $ts_db->fetch_row($result1);
                $tot = ($totHrs[0]) ? $totHrs[0] : 0;
        
                echo $tot . "</td>";
        
                $totArray[] = $tot;
                // $totArray = array();
                // array_push($totArray, $tot);
                // foreach ($tot as $t) {
                //  $totArray[] = $t;
                // }
        
                $result2 = $ts_db->exec_statement($query2P,array($periodID,$end,$row['id']));
                $totAgg = $ts_db->fetch_row($result2);
                $agg = ($totAgg[0]) ? $totAgg[0] : 0;
        
                // echo "<td align='right'>$agg</td><td align='right'>";
        
                $wages = $tot * $agg;
                
                echo "<td align='right'>" . money_format('%n', $wages) . "</td></tr>\n";

                $wageArray[] = $wages;
                // $wageArray = array();
                // array_push($wageArray, $wages);
                // foreach ($wages as $w) {
                //  $wageArray[] = $w;
                // }
                
                if ($row['id'] == "31") $csvwages .= ""; // Hide PTO from copy&paste output
                else $csvwages .= $wages . "\t";
        
                if ($row['id'] == "31") $csvhours .= "";
                else $csvhours .= $tot . "\t";
            }
            // print_r($totArray);

            echo "<tr><td colspan=4><hr /></td></tr>";
            echo "<tr><td>&nbsp;</td><td><b>TOTALS</b></td>
                <td align=right><b>" . number_format(array_sum($totArray),2) . "</b></td>
                <td align=right><b>" . number_format(array_sum($wageArray),2) . "</b></td></tr>";
            // 
            //  OVERTIME
            // 
            $OT1 = array();
            $OT2 = array();
            $empP = $ts_db->prepare_statement("SELECT emp_no FROM employees WHERE EmpActive = 1");
            $weekoneP = $ts_db->prepare_statement("SELECT ROUND(SUM(hours), 2) 
                FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
                INNER JOIN {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods AS p 
                ON (p.periodID = t.periodID)
                WHERE t.emp_no = ?
                AND t.periodID = ?
                AND t.area <> 31
                AND t.tdate >= DATE(p.periodStart)
                AND t.tdate < DATE(date_add(p.periodStart, INTERVAL 7 day))");
            $weektwoQ = $ts_db->prepare_statement("SELECT ROUND(SUM(hours), 2)
                FROM {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.timesheet AS t
                INNER JOIN {$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase']}.payperiods AS p
                ON (p.periodID = t.periodID)
                WHERE t.emp_no = ?
                AND t.periodID = ?
                AND t.area <> 31
                AND t.tdate >= DATE(date_add(p.periodStart, INTERVAL 7 day)) 
                AND t.tdate <= DATE(p.periodEnd)");
            foreach ($p as $v) {
                $empR = $ts_db->exec_statement($empP);
                while ($row = $ts_db->fetch_array($empR)) {

                    $weekoneR = $ts_db->exec_statement($weekoneP,array($row['emp_no'],$v));
                    $weektwoR = $ts_db->exec_statement($weektwoP,array($row['emp_no'],$v));

                    list($weekone) = $ts_db->fetch_row($weekoneR);
                    if (is_null($weekone)) $weekone = 0;
                    list($weektwo) = $ts_db->fetch_row($weektwoR);
                    if (is_null($weektwo)) $weektwo = 0;

                    if ($weekone > $ft) $otime1 = $weekone - $ft;
                    if ($weektwo > $ft) $otime2 = $weektwo - $ft;
                    // $otime = $otime + $otime1 + $otime2;
                    $OT1[] = $otime1;
                    $OT2[] = $otime2;
                    $otime1 = 0;
                    $otime2 = 0;
                }
            }
            // print_r($OT1);
            $OT = array_sum($OT1) + array_sum($OT2);
            $OTTOT = number_format($OT,2);
    
            echo "<tr><td>&nbsp;</td><td>OT Total</td><td align='right'>$OTTOT</td></tr>";
            //  END OVERTIME
    
            //  PTO REQUESTED
            $ptoQ = $ts_db->prepare_statement("SELECT SUM(t.hours) as total FROM ". 
                $FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].".timesheet t 
                WHERE t.periodID >= ? AND t.periodID <= ? AND t.area = 31");
            $ptoR = $ts_db->exec_statement($ptoQ,array($periodID,$end));
            $pto = $ts_db->fetch_row($ptoR);
            $PTOREQ = number_format($pto[0],2);
            echo "<tr><td>&nbsp;</td><td>PTO Requested</td><td align='right'>$PTOREQ</td></tr>";
            //  END PTO REQUESTED
    
            //  PTO NEW
            $empQ = $ts_db->prepare_statement("SELECT emp_no FROM employees 
                WHERE EmpActive = 1 AND JobTitle = 'STAFF'");
            $empR = $ts_db->exec_statement($empQ);
            $nonPTOtotalP = $ts_db->prepare_statement("SELECT SUM(hours) 
                FROM ".$FANNIE_PLUGIN_SETTINGS['TimesheetDatabase'].".timesheet 
                WHERE periodID >= ? AND periodID <= ? AND area <> 31 
                AND emp_no = ?");
            $PTOnew = array();
            while ($row = $ts_db->fetch_array($empR)) {
                $nonPTOtotalr = $ts_db->exec_statement($nonPTOtotalP,array($periodID,$end,$row['emp_no']));
                $nonPTOtotal = $ts_db->fetch_row($nonPTOtotalr);
                $ptoAcc = $nonPTOtotal[0] * 0.075;
                $PTOnew[] = $ptoAcc;
            }
            // print_r($PTOnew);
            $PTONEW = number_format(array_sum($PTOnew),2);
            echo "<tr><td>&nbsp;</td><td>PTO New</td><td align='right'>$PTONEW</td></tr>";
            //  END PTO NEW
    
            echo "</tbody></table>\n";

            echo "<br />";
            echo "<a id='copyLink2'>Copy</a> & Paste <b>Hours</b> data (columns C:AB):";
            echo "<table border=0><tr><td><textarea id='copyMe2' cols=50 rows=3>" . $csvhours . "</textarea></td></tr></table>";
            echo "<a id='copyLink3'>Copy</a> & Paste the OT/PTO <b>Hours</b> data (columns AG:AI):";
            echo "<table border=0><tr><td><textarea id='copyMe3' cols=50 rows=1>$OTTOT\t$PTOREQ\t$PTONEW</textarea></td></tr></table>";
            // echo "<br />";
            echo "<a id='copyLink'>Copy</a> & Paste <b>Wages</b> data:";
            echo "<table border=0><tr><td><textarea id='copyMe' cols=50 rows=5>" . $csvwages . "</textarea></td></tr></table>";
        }
    }

    function javascript_content(){
        ob_start();
        ?>
        ZeroClipboard.setMoviePath( '../src/ZeroClipboard10.swf' );
        var clip = new ZeroClipboard.Client();
        clip.setText( '' ); // will be set later on mouseDown
        clip.setHandCursor( true );
        clip.setCSSEffects( true );

        clip.addEventListener( 'load', function(client) {
            // alert( "movie is loaded" );
        });
        clip.addEventListener( 'complete', function(client, text) {
            alert("Copied text to clipboard: " + text );
        });
        clip.addEventListener( 'mouseOver', function(client) {
            // alert("mouse over"); 
        });
        clip.addEventListener( 'mouseOut', function(client) { 
            // alert("mouse out"); 
        });
        clip.addEventListener( 'mouseDown', function(client) { 
            // set text to copy here
            clip.setText( document.getElementById('copyMe').value );
        
            // alert("mouse down"); 
        });
        clip.addEventListener( 'mouseUp', function(client) { 
            // alert("mouse up"); 
        });

        clip.glue( 'copyLink' ); 
        
        
        var clip1 = new ZeroClipboard.Client();
        clip1.setText( '' ); // will be set later on mouseDown
        clip1.setHandCursor( true );
        clip1.setCSSEffects( true );

        clip1.addEventListener( 'load', function(client) {
            // alert( "movie is loaded" );
        });
        clip1.addEventListener( 'complete', function(client, text) {
            alert("Copied text to clip1board: " + text );
        });
        clip1.addEventListener( 'mouseOver', function(client) {
            // alert("mouse over"); 
        });
        clip1.addEventListener( 'mouseOut', function(client) { 
            // alert("mouse out"); 
        });
        clip1.addEventListener( 'mouseDown', function(client) { 
            // set text to copy here
            clip1.setText( document.getElementById('copyMe2').value );
        
            // alert("mouse down"); 
        });
        clip1.addEventListener( 'mouseUp', function(client) { 
            // alert("mouse up"); 
        });

        clip1.glue( 'copyLink2' );     
    
        var clip2 = new ZeroClipboard.Client();
        clip2.setText( '' ); // will be set later on mouseDown
        clip2.setHandCursor( true );
        clip2.setCSSEffects( true );

        clip2.addEventListener( 'load', function(client) {
            // alert( "movie is loaded" );
        });
        clip2.addEventListener( 'complete', function(client, text) {
            alert("Copied text to clip2board: " + text );
        });
        clip2.addEventListener( 'mouseOver', function(client) {
            // alert("mouse over"); 
        });
        clip2.addEventListener( 'mouseOut', function(client) { 
            // alert("mouse out"); 
        });
        clip2.addEventListener( 'mouseDown', function(client) { 
            // set text to copy here
            clip2.setText( document.getElementById('copyMe3').value );
        
            // alert("mouse down"); 
        });
        clip2.addEventListener( 'mouseUp', function(client) { 
            // alert("mouse up"); 
        });

        clip2.glue( 'copyLink3' );
        <?php
        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec(false);

