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

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (!class_exists('WfcHtLib')) {
    require(dirname(__FILE__).'/WfcHtLib.php');
}

class WfcHtViewSalaryPage extends FanniePage
{
    protected $must_authenticate = true;
    protected $window_dressing = false;
    public $page_set = 'Plugin :: WFC Hours Tracking';
    public $description = '[View Salary] shows information for a single salaried employee.';

    private $empID = 0;
    public function preprocess()
    {
        $this->empID = FormLib::get('id');

        if ($this->empID === '' || !is_numeric($this->empID)) {
            $this->empID = FannieAuth::getUID($this->current_user);
        }

        if (!FannieAuth::validateUserQuiet('view_all_hours')) {
            /* see if logged in user has access to any
               department. if so, see if the selected employee
               is in that department
            */
            $validated = false;
            $depts = array(10,11,12,13,20,21,30,40,41,50,60,998);
            $sql = WfcHtLib::hours_dbconnect();
            $checkQ = $sql->prepare_statement("select department from employees where empID=?");
            $checkR = $sql->exec_statement($checkQ, array($this->empID));
            $checkW = $sql->fetch_row($checkR);
            if (FannieAuth::validateUserQuiet('view_all_hours', $checkW['department'])){
                $validated = true;
            }

            /* no access permissions found, so only allow the
               logged in user to see themself
            */
            if (!$validated) {
                $this->empID = FannieAuth::getUID($this->current_user);
            }
        }

        $sql = WfcHtLib::hours_dbconnect();
        $deptQ = $sql->prepare_statement("select department from employees where empID=?");
        $deptR = $sql->exec_statement($deptQ, array($this->empID));
        $deptW = $sql->fetch_row($deptR);
        if ($deptW['department'] < 998){
            header("Location: WfcHtViewEmpPage.php?id=".$this->empID);
            return false;
        }

        return true;
    }
    
    public function body_content()
    {
        global $FANNIE_URL;
        $sql = WfcHtLib::hours_dbconnect();

        ob_start();
echo "<html><head><title>View</title>";
echo "<style type=text/css>
#payperiods {
    margin-top: 50px;
}

#payperiods td {
    text-align: right;
}

#payperiods th {
    text-align: center;
}

#payperiods td.left {
    text-align: left;
}

#payperiods th.left {
    text-align: left;
}

#payperiods th.right {
    text-align: right;
}

tr.one td {
    background: #ffffcc;
}
tr.one th {
    background: #ffffcc;
    text-align: right;
}

tr.two td {
    background: #ffffff;
}
tr.two th {
    background: #ffffff;
    text-align: right;
}
a {
    color: blue;
}

#temptable th {
    text-align: left;
}
#temptable td {
    text-align: right;
    padding-left: 2em;
}

#temptable {
    font-size: 125%;
}

#newtable th{
    text-align: left;
}
#newtable td{
    text-align: right;
}

</style>";
        echo "</head><body>";

        echo "<h3>Salary Employee PTO Status</h3>";

        $infoQ = $sql->prepare_statement("select e.name,e.adpID,
            s.totalTaken as daysTaken
            from employees as e left join
            salarypto_ytd as s on e.empID=s.empID
            where e.empID=?");
        $infoR = $sql->exec_statement($infoQ, array($this->empID));
        $infoW = $sql->fetch_row($infoR);

        echo "<h2>{$infoW['name']} [ <a href={$FANNIE_URL}auth/ui/loginform.php?logout=yes>Logout</a> ]</h2>";
        echo "<table cellspacing=0 cellpadding=4 border=1 id=newtable>";
        echo "<tr class=one><th>PTO Allocation</th><td>{$infoW['adpID']}</td></tr>";
        echo "<tr class=two><th>PTO Taken, YTD</th><td>{$infoW['daysTaken']}</td></tr>";
        echo "<tr class=one><th>PTO Remaining</th><td>".($infoW['adpID']-$infoW['daysTaken'])."</td></tr>";
        echo "</tr></table>";

        $periodsQ = $sql->prepare_statement("select daysUsed,month(dstamp),year(dstamp) 
                from salaryHours where empID=? order by dstamp DESC");
        $periodsR = $sql->exec_statement($periodsQ, array($this->empID));
        $class = array("one","two");
        $c = 0;
        echo "<table id=payperiods cellspacing=0 cellpadding=4 border=1>";
        echo "<tr><th>Month</th><th>PTO Taken</th></tr>";
        while ($row = $sql->fetch_row($periodsR)){
            echo "<tr class=\"$class[$c]\">";
            $dstr = date("F Y",mktime(0,0,0,$row[1],1,$row[2]));
            echo "<td>$dstr</td>";
            echo "<td>$row[0]</td>";
            echo "</tr>";   
            $c = ($c+1)%2;
        }

        echo "</table>";
        echo "<div id=disclaimer>
        <u>Please Note</u>: This web-base PTO Access Page is new. If you notice any problems,
        please contact Colleen or Andy.
        </div>";

        echo "</body></html>";

        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec();

