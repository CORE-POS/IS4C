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

class WfcHtReport extends FannieReportPage
{
    public $discoverable = false; // access is very restricted; no need to list
                                  // as an available report
    protected $must_authenticate = true;
    protected $auth_classes = array('view_all_hours');
    protected $header = 'Hours Report';
    protected $title = 'Hours Report';

    protected $report_headers = array('Name', 'ADP ID#', 'Reg. Hours', 'OT Hours', 'Alt. Rate', 'PTO', 'UTO', 'Total');

    public function preprocess()
    {
        if (FormLib::get('startPeriod') !== '') {
            $this->window_dressing = false;
            $this->content_function = 'report_content';

            $this->formatCheck();
        }

        return true;
    }

    public function fetch_report_data()
    {
        $sql = WfcHtLib::hours_dbconnect();

        $sp = FormLib::get("startPeriod");
        $ep = FormLib::get("endPeriod");

        $query = "SELECT e.name,e.adpID,sum(i.hours),sum(i.OTHours),
                sum(i.SecondRateHours),
                sum(i.PTOHours),
                sum(i.UTOHours),
                sum(i.hours+i.OTHours+i.SecondRateHours)
                FROM ImportedHoursData as i
                LEFT JOIN employees as e
                ON i.empID = e.empID
                WHERE (i.periodID BETWEEN ? AND ?
                OR i.periodID BETWEEN ? AND ?)
                AND e.deleted = 0
                GROUP BY i.empID,e.name,e.adpID
                ORDER BY e.name";
        $prep = $sql->prepare($query);
        $result = $sql->execute($prep, array($sp, $ep, $ep, $sp));

        $report = array();
        while($row = $sql->fetch_row($result)) {
            $record = array();
            for($i=0;$i<8;$i++) {
                $record[] = $row[$i];
            }
            $report[] = $record;
        }

        return $report;
    }

    public function form_content()
    {
        $sql = WfcHtLib::hours_dbconnect();

        $periods = "";
        $periodQ = "SELECT periodID,dateStr from PayPeriods ORDER BY periodID desc";
        $periodR = $sql->query($periodQ);
        while($periodW = $sql->fetch_row($periodR)) {
            $periods .= "<option value=$periodW[0]>$periodW[1]</option>";
        }

        ob_start();
        ?>
<form method=get action="<?php echo $_SERVER['PHP_SELF']; ?>">
<table><tr>
<td>
<b>Starting pay period</b>:
</td><td>
<select name=startPeriod><?php echo $periods; ?></select>
</td></tr>
<tr><td>
<b>Ending pay period</b>:
</td><td>
<select name=endPeriod><?php echo $periods; ?></select>
</td></tr>
</table>
<input type=submit value=Submit />
</form>
        <?php
        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec();

