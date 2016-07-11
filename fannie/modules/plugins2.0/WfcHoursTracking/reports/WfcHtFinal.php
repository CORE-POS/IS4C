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

class WfcHtFinal extends FannieReportPage
{
    public $discoverable = false; // access is very restricted; no need to list
                                  // as an available report
    protected $must_authenticate = true;
    protected $auth_classes = array('view_all_hours');
    protected $header = 'PTO Adjustment';
    protected $title = 'PTO Adjustment';

    protected $report_headers = array('Name', 'ADP ID#', 'Current PTO Level', 'Current PTO Available',
                                        '',
                                        'Next PTO Level', 'Next PTO Award', 'Hours Required',
                                        'Total Hours Worked', 'Hours Worked At Current Level',
                                        'Hours Between Levels', '% Progress',
                                        'Pro-Rated Award', '', 'Current + Pro-Rated');

    public function preprocess()
    {
        $ret = parent::preprocess();
        $this->content_function = 'report_content';

        return $ret;
    }

    public function fetch_report_data()
    {
        $sql = WfcHtLib::hours_dbconnect();

        $query = '
            SELECT e.name,
                e.adpID,
                e.PTOLevel,
                p.PTORemaining,
                \'\' AS spacer,
                l.LevelID,
                l.PTOHours,
                l.HoursWorked,
                h.totalHours,
                h.totalHours - c.HoursWorked AS hoursAtCurrentLevel,
                l.HoursWorked - c.HoursWorked AS levelspan,
                ((h.totalHours-c.HoursWorked) / (l.HoursWorked-c.HoursWorked)) * 100 as percentProgress,
                ((h.totalHours-c.HoursWorked) / (l.HoursWorked-c.HoursWorked)) * l.PTOHours as prorated,
                \'\' AS spacer2,
                p.PTORemaining + ((h.totalHours-c.HoursWorked) / (l.HoursWorked-c.HoursWorked)) * l.PTOHours as ttl
            FROM employees AS e
                LEFT JOIN pto AS p ON e.empID=p.empID
                LEFT JOIN PTOLevels AS l ON e.PTOLevel+1=l.LevelID
                LEFT JOIN PTOLevels AS c ON e.PTOLevel=c.LevelID
                LEFT JOIN hoursalltime AS h ON e.empID=h.empID
            WHERE e.deleted=0
                AND e.department < 900
            ORDER BY e.name';
        $result = $sql->query($query);

        $report = array();
        while($row = $sql->fetch_row($result)) {
            $record = array();
            for($i=0;$i<$sql->numFields($result);$i++) {
                if (is_numeric($row[$i]) && $row[$i] != ((int)$row[$i])) {
                    $row[$i] = number_format($row[$i], 2);
                }
                $record[] = $row[$i];
            }
            $report[] = $record;
        }

        return $report;
    }

    public function form_content()
    {
        return '<!-- no need -->';
    }
}

FannieDispatch::conditionalExec();

