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

class WfcHtSalaryUploadPage extends FanniePage
{
    protected $must_authenticate = true;
    protected $auth_classes = array('upload_hours_data');
    protected $header = 'Import Salary PTO';
    protected $title = 'Import Salary PTO';

    public $page_set = 'Plugin :: WFC Hours Tracking';
    public $description = '[Salary Upload] import PTO usage for salaried employees.';
    public $themed = true;

    public function body_content()
    {
        $sql = WfcHtLib::hours_dbconnect();

        if (FormLib::get('month') !== '') {
            $ids = FormLib::get('ids', array());
            $days = FormLib::get('days');
            $datestamp = FormLib::get('year')."-".str_pad(FormLib::get('month'),2,'0',STR_PAD_LEFT)."-01";
            $insQ = $sql->prepare("INSERT INTO salaryHours VALUES (?, ?, ?)");
            for ($i=0; $i < count($ids); $i++) {
                $sql->execute($insQ, array($ids[$i], $datestamp, $days[$i]));
            }

            return '<div class="alert alert-success">Salary PTO added</div>';
        } else {
            $ret = "<form action=\"{$_SERVER['PHP_SELF']}\" method=post>";

            $fetchQ = "select empID,name from employees where department >= 998 
                and deleted=0 order by name";
            $fetchR = $sql->query($fetchQ);

            $ret .= '<div class="row"><div class="col-sm-5">';
            $ret .= "<table class=\"table\">";
            $ret .= "<tr><th>Employee</th><th>Days taken</th></tr>";
            while($fetchW = $sql->fetch_row($fetchR)) {
                $ret .= "<tr><td>{$fetchW['name']}</td>";
                $ret .= "<td><input type=number name=days[] class=\"form-control\" required value=0 /></td>";
                $ret .= "<input type=hidden name=ids[] value={$fetchW['empID']} /></tr>";
            }

            $ret .= "<tr><th>Month</th><th>Year</th></tr>";
            $ret .= "<tr><td><select name=month class=\"form-control\">";
            for ($i=1;$i<=12;$i++) {
                $stamp = mktime(0,0,0,$i,1);
                $mname = date('F',$stamp);
                $ret .= "<option value=$i>$mname</option>";
            }
            $ret .= "</select></td><td>";
            $ret .= "<input type=number class=\"form-control\" required name=year value=".date("Y")." /></td></tr>";
            $ret .= "</table>";
            $ret .= "</div></div>";
            $ret .= '<p><button type="submit" class="btn btn-default">Submit</button></p>';
            $ret .= "</form>";

            return $ret;
        }
    }
}

FannieDispatch::conditionalExec();

