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

class WfcHtCsvDump extends FannieReportPage
{
    public $discoverable = false; // access is very restricted; no need to list
                                  // as an available report

    protected $must_authenticate = true;
    protected $auth_classes = array('view_all_hours');
    protected $header = 'PTO/UTO Report';
    protected $title = 'PTO/UTO Report';

    protected $report_headers = array('Name', 'ADP ID#', 'PTO Level', 'PTO', 'UTO', 'Hours Worked');

    public function preprocess()
    {
        $this->window_dressing = false;
        $this->content_function = 'report_content';

        $this->formatCheck();

        return true;
    }

    public function fetch_report_data()
    {
        $sql = WfcHtLib::hours_dbconnect();

        $fetchQ = "select e.name,e.adpID,e.PTOLevel,
            h.totalHours,c.cusp,e.empID,
            p.ptoremaining,u.hours
            from employees as e left join hoursalltime as h on e.empID=h.empID
            left join cusping as c on e.empID=c.empID
            left join pto as p on e.empID=p.empID
            left join uto as u on e.empID=u.empID
            where deleted=0
            order by e.name";
        $fetchP = $sql->prepare($fetchQ);
        $fetchR = $sql->execute($fetchP);
        
        $report = array();
        while($fetchW = $sql->fetch_row($fetchR)) {
            $record = array(
                $fetchW['name'],
                $fetchW['adpID'],
                $fetchW['PTOLevel'],
                $fetchW['ptoremaining'],
                $fetchW['hours'],
                $fetchW['totalHours'],
            );
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

