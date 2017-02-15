<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of IT CORE.

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
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (!class_exists('WfcHtLib')) {
    require(dirname(__FILE__).'/../WfcHtLib.php');
}

class WfcPtoStudy extends FannieReportPage
{
    public $discoverable = false; // access is very restricted; no need to list
                                  // as an available report

    protected $must_authenticate = true;
    protected $auth_classes = array('view_all_hours');
    protected $header = 'PTO/UTO Report';
    protected $title = 'PTO/UTO Report';

    protected $sortable = false;
    protected $no_sort_but_style = true;

    protected $report_headers = array('Pay Period', 'Emp#', 'Name', 'New PTO Level', 'Current Threshold', 'Current Award', 'Alt Award');

    public function preprocess()
    {
        $this->window_dressing = false;
        $this->content_function = 'report_content';

        $this->formatCheck();

        return true;
    }

    public function fetch_report_data()
    {
        $dbc = FannieDB::get('HoursTracking');
        
        // get level info
        $cutoffs = array();
        $awards = array();
        if (!class_exists('WfcHtPTOLevelsModel')) {
            include(dirname(__FILE__) . '/../models/WfcHtPTOLevelsModel.php');
        }
        $model = new WfcHtPTOLevelsModel($dbc);
        foreach($model->find('LevelID') as $obj) {
            $cutoffs[$obj->LevelID()] = $obj->HoursWorked();
            $awards[$obj->LevelID()] = $obj->PTOHours();
        }

        // get employees hours & levels before the study period
        $start = 'SELECT s.empID, totalHours, name FROM studyPre as s
                left join employees AS e on s.empID=e.empID';
        $result =  $dbc->query($start);
        $employees = array();
        while($row = $dbc->fetch_row($result)) {
            $employees[$row['empID']] = array('hours'=>$row['totalHours'],'level'=>0, 'name'=>$row['name']); 
        }
        foreach($employees as $id => $info) {
            foreach($cutoffs as $level => $limit) {
                if ($employees[$id]['hours'] > $limit) {
                    $employees[$id]['level'] = $level;
                }
            }
        }

        // add zero entries for employees hired during the period
        $extra = 'SELECT i.empID,name FROM ImportedHoursData 
                AS i left join employees as e on i.empID=e.empID
                WHERE periodID BETWEEN 121 AND 144
                GROUP BY i.empID, name';
        $result = $dbc->query($extra);
        while($row = $dbc->fetch_row($result)) {
            if (isset($employees[$row['empID']])) {
                continue;
            }
            $employees[$row['empID']] = array('hours'=>0.0,'level'=>0,'name'=>$row['name']); 
        }

        $report = array();
        $prep = $dbc->prepare('SELECT empID, MAX(dateStr) as ds, 
                        SUM(hours+OTHours+SecondRateHours+EmergencyHours) as totalHours
                        FROM ImportedHoursData AS i LEFT JOIN PayPeriods AS p
                        ON i.periodID=p.periodID WHERE i.periodID=? GROUP BY empID');
        for($i=121; $i<=144; $i++) {
            $result = $dbc->execute($prep, array($i));
            $dateStr = '';
            // add one payperiod to employees
            while($row = $dbc->fetch_row($result)) {
                $employees[$row['empID']]['hours'] += $row['totalHours'];
                $dateStr = $row['ds'];
            }

            // check cutoffs
            foreach($employees as $id => $info) {
                $curHours = $employees[$id]['hours'];
                $curLevel = $employees[$id]['level'];
                if ($curHours > $cutoffs[$curLevel+1]) {
                    // level jump!
                    $record = array(
                        $dateStr,
                        $id,
                        $employees[$id]['name'],
                        $curLevel+1,
                        $cutoffs[$curLevel+1],
                        $awards[$curLevel+1],
                        0,
                    );
                    $report[] = $record;

                    $employees[$id]['level'] = $curLevel+1;
                }
            }
        }

        return $report;
    }

    public function form_content()
    {
        return '<!-- no need -->';
    }
}

FannieDispatch::conditionalExec();

