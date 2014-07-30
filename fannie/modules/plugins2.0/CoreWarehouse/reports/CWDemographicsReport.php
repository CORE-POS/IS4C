<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of Fannie.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include(dirname(__FILE__).'/../../../../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class CWDemographicsReport extends FannieReportPage {

    public $description = '[Demographics Report] lists information about customer participation.
        Requires CoreWarehouse plugin.';

    protected $multi_report_mode = True;

    protected $sortable = False;

    protected $content_function = 'report_content';

    protected $window_dressing = False;

    protected $title = 'Demographics Report';
    protected $report_cache = 'day';

    function fetch_report_data(){
        global $FANNIE_OP_DB, $FANNIE_PLUGIN_SETTINGS;

        $ret = array();

        $dbc = FannieDB::get($FANNIE_OP_DB);
        $totalQ = "SELECT 
            CASE WHEN (year(m.start_date) <= 1991 OR m.start_date IS NULL) THEN '1991 or earlier' ELSE YEAR(m.start_date) END as yearBucket,
            CASE WHEN c.Type='PC' THEN 1 ELSE 0 END as active,
            COUNT(*) AS numMembers
            FROM memDates AS m LEFT JOIN custdata AS c ON m.card_no=c.CardNo
            AND c.personNum=1 LEFT JOIN suspensions AS s ON s.cardno=m.card_no
            WHERE c.Type='PC' OR s.memtype2 = 'PC'
            GROUP BY 
            CASE WHEN (year(m.start_date) <= 1991 OR m.start_date IS NULL) THEN '1991 or earlier' ELSE YEAR(m.start_date) END,
            CASE WHEN c.Type='PC' THEN 1 ELSE 0 END
            ORDER BY 
            CASE WHEN (year(m.start_date) <= 1991 OR m.start_date IS NULL) THEN '1991 or earlier' ELSE YEAR(m.start_date) END";
        $totalP = $dbc->prepare_statement($totalQ);
        $totalR = $dbc->exec_statement($totalP);

        $report1 = array();
        $totalActiveMem = 0;
        $totalInactMem = 0;
        while($totalW = $dbc->fetch_row($totalR)){
            if (!isset($report1[$totalW['yearBucket']])){
                $report1[$totalW['yearBucket']] = array(
                    $totalW['yearBucket'], 0, 0, 100.0
                );
            }
            $report1[$totalW['yearBucket']][1] += $totalW['numMembers'];
            if ($totalW['active'] == 1){
                $report1[$totalW['yearBucket']][2] += $totalW['numMembers'];
                $totalActiveMem += $totalW['numMembers'];
            }
            else{
                $totalInactMem += $totalW['numMembers'];
            }
        
            if ($report1[$totalW['yearBucket']][1] != 0){
                $report1[$totalW['yearBucket']][3] = sprintf('%.2f%%',
                    100 * $report1[$totalW['yearBucket']][2] /
                    ($report1[$totalW['yearBucket']][1])
                );
            }
        }
        $report0 = array();
        $report0[] = array('Total Members',$totalActiveMem+$totalInactMem,'Active',
                $totalActiveMem, sprintf('%.2f%%',100*$totalActiveMem/($totalActiveMem+$totalInactMem)));
        $ret[] = $report0;

        $deindex = array();
        foreach($report1 as $row) $deindex[] = $row;
        $ret[] = $deindex;

        $lastmonth = date('Ymd', mktime(0,0,0,date('n')-1,date('j'),date('Y')));
        $lastqtr = date('Ymd', mktime(0,0,0,date('n')-3,date('j'),date('Y')));
        $lastyear = date('Ymd', mktime(0,0,0,date('n'),date('j'),date('Y')-1)); 

        $warehouseDB = $FANNIE_PLUGIN_SETTINGS['WarehouseDatabase'].$dbc->sep();
        $participationQ = "SELECT MAX(date_id) as id, w.card_no,
                SUM(transCount) as visits, sum(total) as spending,
                ".$dbc->monthdiff($dbc->now(),'m.start_date')." as months
                FROM
                {$warehouseDB}sumMemSalesByDay AS w
                LEFT JOIN custdata AS c ON w.card_no=c.CardNo
                AND c.personNum=1 LEFT JOIN suspensions AS s
                ON w.card_no=s.cardno LEFT JOIN memDates as m
                ON w.card_no=m.card_no
                WHERE w.date_id >= ? AND (c.Type='PC' OR s.memtype2 = 'PC')
                GROUP BY w.card_no";
        $participationP = $dbc->prepare_statement($participationQ);
        $participationR = $dbc->exec_statement($participationP, array($lastyear));
        $report2 = array(
            array('Last Month', 0, 0),  
            array('Last 3 Months', 0, 0),   
            array('Last 12 Months', 0, 0)
        );
        $report3 = array(
            array('More than 4', 0, 0),
            array('3-4', 0, 0),
            array('2-3', 0, 0),
            array('1-2', 0, 0),
            array('Less than 1', 0, 0),
        );
        $report4 = array(
            array('Over $5,000', 0, 0),
            array('$4,000.01 - $5,000', 0, 0),
            array('$3,000.01 - $4,000', 0, 0),
            array('$2,000.01 - $3,000', 0, 0),
            array('$1,000.01 - $2,000', 0, 0),
            array('$1,000 or less', 0, 0)
        );
        while($w = $dbc->fetch_row($participationR)){
            if ($w['id'] >= $lastmonth)
                $report2[0][1]++;
            if ($w['id'] >= $lastqtr)
                $report2[1][1]++;
            if ($w['id'] >= $lastyear)
                $report2[2][1]++;
            
            $md = $w['months'];
            if ($md == 0) $md = 1;
            if ($md > 12) $md = 12;
            $avg_visits = $w['visits'] / ((float)$md);
            if ($avg_visits > 4)
                $report3[0][1]++;
            elseif($avg_visits > 3)
                $report3[1][1]++;
            elseif($avg_visits > 2)
                $report3[2][1]++;
            elseif($avg_visits > 1)
                $report3[3][1]++;
            else
                $report3[4][1]++;

            if ($w['spending'] > 5000)
                $report4[0][1]++;
            elseif ($w['spending'] > 4000)
                $report4[1][1]++;
            elseif ($w['spending'] > 3000)
                $report4[2][1]++;
            elseif ($w['spending'] > 2000)
                $report4[3][1]++;
            elseif ($w['spending'] > 1000)
                $report4[4][1]++;
            else
                $report4[5][1]++;
        }

        for($i=0;$i<3;$i++){
            $report2[$i][2] = sprintf('%.2f%%', 100 * $report2[$i][1] / $totalActiveMem);
        }
        $ret[] = $report2;

        for($i=0;$i<5;$i++){
            $report3[$i][2] = sprintf('%.2f%%', 100 * $report3[$i][1] / $totalActiveMem);
        }
        $ret[] = $report3;

        for($i=0;$i<6;$i++){
            $report4[$i][2] = sprintf('%.2f%%', 100 * $report4[$i][1] / $totalActiveMem);
        }
        $ret[] = $report4;

        return $ret;
    }

    private $footer_count = 0;
    function calculate_footers($data){
        $ret = array();
        switch($this->footer_count){
        case 1:
            $ttl = array(0, 0);
            foreach($data as $row){
                $ttl[0] += $row[1];
                $ttl[1] += $row[2];
            }
            $ret = array('Total', $ttl[0], $ttl[1],
                sprintf('%.2f%%',100*$ttl[0]/($ttl[0]+$ttl[1]))
            );
            $this->report_headers = array('Activated', '', 'Still Active', '');
            break;
        case 2:
            $this->report_headers = array('Participation','Count','% (of active)');
            break;
        case 3:
            $this->report_headers = array('Avg Visits per Month','Count','% (of active)');
            break;
        case 4:
            $this->report_headers = array('Spending, 12 months','Count','% (of active)');
            break;
        }
        $this->footer_count++;
        return $ret;
    }
}

FannieDispatch::conditionalExec();

?>
