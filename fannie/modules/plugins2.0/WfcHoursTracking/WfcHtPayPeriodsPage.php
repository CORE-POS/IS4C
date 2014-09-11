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

class WfcHtPayPeriodsPage extends FanniePage
{
    protected $must_authenticate = true;
    protected $auth_classes = array('view_all_hours');
    protected $header = 'Pay Periods';
    protected $title = 'Pay Periods';

    public $page_set = 'Plugin :: WFC Hours Tracking';
    public $description = '[Pay Periods] shows all pay periods.';

    public function css_content()
    {
        return '
.one {
    background: #ffffff;
}
.two {
    background: #ffffcc;
}
        ';
    }

    public function body_content()
    {
        $db = WfcHtLib::hours_dbconnect();

        $ppID = -1;
        if (FormLib::get('id') !== '') {
            $ppID = FormLib::get('id');
        }
        $order = "e.name";
        if (isset($_GET["order"])){
            switch(strtolower($_GET['order'])){
                case 'name':
                    $order = 'e.name';
                    break;
                case 'adpid':
                    $order = 'e.adpid';
                    break;
                case 'hours':
                    $order = 'i.hours';
                    break;
                case 'othours':
                    $order = 'i.othours';
                    break;
                case 'ptohours':
                    $order = 'i.ptohours';
                    break;
                case 'emergencyhours':
                    $order = 'i.emergencyhours';
                    break;
                case 'secondratehours':
                    $order = 'i.secondratehours';
                    break;
            }
        }
        $dir = "asc";
        if (isset($_GET["dir"])){
            switch(strtolower($_GET['dir'])){
                case 'asc':
                    $dir = 'asc';
                    break;
                case 'desc':
                    $dir = 'desc';
                    break;
            }
        }
        $otherdir = "desc";
        if ($dir == "desc") {
            $otherdir = "asc";
        }

        $ret = "<select onchange=\"top.location='{$_SERVER['PHP_SELF']}?id='+this.value;\">";
        $ppQ = "select periodID,dateStr from PayPeriods order by periodID desc";
        $ppR = $db->query($ppQ);
        while ($ppW = $db->fetch_row($ppR)) {
            $ret .= "<option value=$ppW[0]";
            if ($ppW[0] == $ppID) {
                $ret .= " selected";
            }
            if ($ppID == -1) {
                $ppID=$ppW[0];
            }
            $ret .= ">$ppW[1]</option>";
        }
        $ret .= "</select>";

        $ret .= "<table cellspacing=0 cellpadding=4 border=1>";
        $ret .= "<tr>";
        if ($order == "e.name")
            $ret .= "<th><a href={$_SERVER['PHP_SELF']}?id=$ppID&order=name&dir=$otherdir>Name</a></th>";
        else
            $ret .= "<th><a href={$_SERVER['PHP_SELF']}?id=$ppID&order=name&dir=asc>Name</a></th>";
        if ($order == "e.adpid")
            $ret .= "<th><a href={$_SERVER['PHP_SELF']}?id=$ppID&order=adpid&dir=$otherdir>ADP ID</a></th>";
        else
            $ret .= "<th><a href={$_SERVER['PHP_SELF']}?id=$ppID&order=adpid&dir=asc>ADP ID</a></th>";
        if ($order == "i.hours")
            $ret .= "<th><a href={$_SERVER['PHP_SELF']}?id=$ppID&order=hours&dir=$otherdir>Reg. Hours</a></th>";
        else
            $ret .= "<th><a href={$_SERVER['PHP_SELF']}?id=$ppID&order=hours&dir=asc>Reg. Hours</a></th>";
        if ($order == "i.othours")
            $ret .= "<th><a href={$_SERVER['PHP_SELF']}?id=$ppID&order=othours&dir=$otherdir>OT Hours</a></th>";
        else
            $ret .= "<th><a href={$_SERVER['PHP_SELF']}?id=$ppID&order=othours&dir=asc>OT Hours</a></th>";
        if ($order == "i.ptohours")
            $ret .= "<th><a href={$_SERVER['PHP_SELF']}?id=$ppID&order=ptohours&dir=$otherdir>PTO Hours</a></th>";
        else
            $ret .= "<th><a href={$_SERVER['PHP_SELF']}?id=$ppID&order=ptohours&dir=asc>PTO Hours</a></th>";
        if ($order == "i.emergencyhours")
            $ret .= "<th><a href={$_SERVER['PHP_SELF']}?id=$ppID&order=emergencyhours&dir=$otherdir>Emerg. Hours</a></th>";
        else
            $ret .= "<th><a href={$_SERVER['PHP_SELF']}?id=$ppID&order=emergencyhours&dir=asc>Emerg. Hours</a></th>";
        if ($order == "i.secondratehours")
            $ret .= "<th><a href={$_SERVER['PHP_SELF']}?id=$ppID&order=secondratehours&dir=$otherdir>Alt. Hours</a></th>";
        else
            $ret .= "<th><a href={$_SERVER['PHP_SELF']}?id=$ppID&order=secondratehours&dir=asc>Alt. Hours</a></th>";
        $ret .= "</tr>";

        $dataQ = "select e.name,e.adpid,i.hours,i.othours,i.ptohours,i.emergencyhours,i.secondratehours
            from ImportedHoursData as i left join employees as e on i.empID=e.empID
            where periodID=?
            order by $order $dir";
        $dataP = $db->prepare_statement($dataQ);
        $dataR = $db->exec_statement($dataQ, array($ppID));
        $class = array("one","two");
        $c = 1;
        while ($dataW = $db->fetch_row($dataR)){
            $ret .= "<tr class=$class[$c]>";

            $ret .= "<td>".$dataW['name']."</td>";
            $ret .= "<td>".$dataW['adpid']."</td>";
            $ret .= "<td>".$dataW['hours']."</td>";
            $ret .= "<td>".$dataW['othours']."</td>";
            $ret .= "<td>".$dataW['ptohours']."</td>";
            $ret .= "<td>".$dataW['emergencyhours']."</td>";
            $ret .= "<td>".$dataW['secondratehours']."</td>";

            $ret .= "</tr>";
            $c = ($c+1)%2;
        }
        $ret .= "</table>";

        return $ret;
    }
}

FannieDispatch::conditionalExec();

