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

class WfcHtListPage extends FanniePage
{
    protected $must_authenticate = true;
    protected $header = 'List';
    protected $title = 'List';

    public $page_set = 'Plugin :: WFC Hours Tracking';
    public $description = '[List] tracked employees.';
    public $themed = true;

    private $dept_restrict = "WHERE deleted=0 ";
    private $dept_args = array();
    private $selected_dept = "";
    private $dept_list = '';
    private $list_args = array();

    public function preprocess()
    {
        if (FormLib::get('showdept') !== '') {
            $this->selected_dept = FormLib::get('showdept');
            if (!empty($this->selected_dept) && $this->selected_dept != -1){
                $this->dept_restrict = " WHERE deleted=0 AND department=? ";
                $this->dept_args = array($this->selected_dept);
            } elseif ($this->selected_dept == -1){
                $this->dept_restrict = " WHERE deleted=1 ";
            }
        }

        if (FormLib::get('action') !== '') {
            $sql = WfcHtLib::hours_dbconnect();
            switch (FormLib::get('action')) {
                case 'update':
                    $name = FormLib::get("name");
                    $id = FormLib::get("id");
                    $adpid = FormLib::get("adpid");
                    $dept = FormLib::get("dept");
                    if (empty($adpid)) $adpid=NULL;
                    if (empty($dept)) $dept=NULL;

                    $upQ = $sql->prepare("update employees set adpid=?,name=?,department=? where empID=?");
                    $upR = $sql->execute($upQ, array($adpid, $name, $dept, $id));
                    break;
                case 'delete':
                    $id = FormLib::get('id');
                    $upQ = $sql->prepare("update employees set deleted=1 where empID=?");
                    $upR = $sql->execute($upQ, array($id));
                    break;
                case 'undelete':
                    $id = FormLib::get('id');
                    $upQ = $sql->prepare("update employees set deleted=0 where empID=?");
                    $upR = $sql->execute($upQ, array($id));
                    break;
            }
        }

        return true;
    }

    /**
      Overriding standard auth check to set up
      conditional behavior
    */
    public function checkAuth()
    { 
        $ALL = FannieAuth::validateUserQuiet('view_all_hours');
        if (!$ALL){
            $valid_depts = array(10,11,12,13,20,21,30,40,50,60,998);
            $validated = false;
            $this->dept_list = "(";
            $this->list_args = array();
            $good_restrict = false;
            foreach ($valid_depts as $d) {
                if (FannieAuth::validateUserQuiet('view_all_hours',$d)) {
                    $validated = true;
                    $this->dept_list .= "?,";
                    $this->list_args[] = $d;
                    if (FormLib::get('showdept') !== '' && $d == FormLib::get('showdept')) {
                        $good_restrict = true;
                    }
                }
            }

            if (!$validated){
                return false;
            } else {
                $this->dept_list = substr($this->dept_list,0,strlen($this->dept_list)-1).")";
                if (!$good_restrict) {
                    $this->dept_restrict = " WHERE deleted=0 AND department IN {$this->dept_list} ";
                    $this->dept_args = $this->list_args;
                }
                return true;
            }
        } else {
            return true;
        }

        return false;
    }

    public function css_content()
    {
        return '
        tr.pre td {
            color: #ffffff;
            background-color: #00cc00;
        }
        tr.post td {
            color: #ffffff;
            background-color: #cc0000;
        }
        tr.post a {
            color: #cccccc;
        }
        tr.earned td {
            color: #ffffff;
            background-color: #000000;
        }
        tr.earned a {
            color: #aaaaaa;
        }
        td {
            color: #000000;
            background-color: #ffffff;
        }
        ';
    }

    private function getSort()
    {
        if (FormLib::get('sort') !== '') {
            switch(strtolower(FormLib::get('sort'))) {
                case 'name':
                    return 'e.name';
                case 'adpid':
                    return 'e.adpid';
                case 'ptolevel':
                    return 'e.ptolevel';
                case 'ptoremaining':
                    return 'p.ptoremaining';
                case 'hours':
                    return 'u.hours';
                case 'totalhours':
                    return 'h.totalhours';
            }
        }

        return 'e.name';
    }

    private function getDirections()
    {
        if (FormLib::get('dir') !== '') {
            switch(strtolower(FormLib::get('dir'))) {
                case 'asc':
                    return array('asc', 'desc');
                case 'desc':
                    return array('desc', 'asc');
            }
        }

        return array('asc', 'desc');
    }

    public function body_content()
    {
        $edit = FannieAuth::validateUserQuiet('edit_employees');

        $sort = $this->getSort();
        list($dir, $otherdir) = $this->getDirections();

        $sql = WfcHtLib::hours_dbconnect();

        $fetchQ = "select e.name,e.adpID,
            case when e.department>=998 then 'Salary' else e.PTOLevel end as PTOLevel,
            case when e.department>=998 then '&nbsp;' else h.totalHours end as totalHours,
            c.cusp,e.empID,
            case when s.totalTaken is null then p.ptoremaining else e.adpID-s.totalTaken end as ptoremaining,
            case when e.department>=998 then '&nbsp;' else u.hours end as hours
            from employees as e left join hoursalltime as h on e.empID=h.empID
            left join cusping as c on e.empID=c.empID
            left join pto as p on e.empID=p.empID
            left join uto as u on e.empID=u.empID
            left join salarypto_ytd s on e.empID=s.empID
            {$this->dept_restrict}
            order by $sort $dir";
        $fetchP = $sql->prepare($fetchQ);
        $fetchR = $sql->execute($fetchP, $this->dept_args);

        ob_start();

        if (FannieAuth::validateUserQuiet('view_all_hours')) {
            $sql = WfcHtLib::hours_dbconnect();
            $deptsQ = "select name,deptID from Departments order by name";
            $deptsR = $sql->query($deptsQ);
            $this->deptOptions($sql, $deptsR);
            if ($this->selected_dept == -1)
                echo "<option selected value=\"-1\">DELETED</option>";
            else
                echo "<option value=\"-1\">DELETED</option>";
            echo "</select>";
        } elseif (strlen($this->dept_list) > 4){
            $sql = WfcHtLib::hours_dbconnect();
            $deptsQ = "select name,deptID from Departments WHERE deptID IN {$this->dept_list} order by name";
            $deptsP = $sql->prepare($deptsQ);
            $deptsR = $sql->execute($deptsP, $this->list_args);
            $this->deptOptions($sql, $deptsR);
            echo "</select>";
        }

        echo "<table class=\"table\"><tr>";
        if ($sort == "e.name")
            echo "<th><a href=?sort=name&dir=$otherdir&showdept={$this->selected_dept}>Name</a></th>";
        else
            echo "<th><a href=?sort=name&dir=asc&showdept={$this->selected_dept}>Name</a></th>";
        if ($sort == "e.adpid")
            echo "<th><a href=?sort=adpid&dir=$otherdir&showdept={$this->selected_dept}>ADP ID</a></th>";
        else
            echo "<th><a href=?sort=adpid&dir=asc&showdept={$this->selected_dept}>ADP ID</a></th>";
        if ($sort == "e.ptolevel")
            echo "<th><a href=?sort=ptolevel&dir=$otherdir&showdept={$this->selected_dept}>PTO Level</a></th>";
        else
            echo "<th><a href=?sort=ptolevel&dir=asc&showdept={$this->selected_dept}>PTO Level</a></th>";
        if ($sort == "p.ptoremaining")
            echo "<th><a href=?sort=ptoremaining&dir=$otherdir&showdept={$this->selected_dept}>Avail. PTO</a></th>";
        else
            echo "<th><a href=?sort=ptoremaining&dir=desc&showdept={$this->selected_dept}>Avail. PTO</a></th>";
        if ($sort == "u.hours")
            echo "<th><a href=?sort=hours&dir=$otherdir&showdept={$this->selected_dept}>Avail. UTO</a></th>";
        else
            echo "<th><a href=?sort=hours&dir=desc&showdept={$this->selected_dept}>Avail. UTO</a></th>";
        if ($sort == "u.hours")
            echo "<th><a href=?sort=totalhours&dir=$otherdir&showdept={$this->selected_dept}>Total Hours</a></th>";
        else
            echo "<th><a href=?sort=totalhours&dir=desc&showdept={$this->selected_dept}>Total Hours</a></th>";
        echo "</tr>";

        while ($fetchW = $sql->fetch_row($fetchR)){
            if ($fetchW[4] == "PRE")
                echo "<tr class=\"pre\">";
            elseif ($fetchW[4] == "POST")
                echo "<tr class=\"post\">";
            elseif ($fetchW[4] == "!!!")
                echo "<tr class=\"earned\">";
            else
                echo "<tr>";
            echo "<td><a href=WfcHtViewEmpPage.php?id=$fetchW[5]>$fetchW[0]</a>";
            echo "</td>";
            echo "<td>$fetchW[1]</td>";
            echo "<td align=center>$fetchW[2]</td>";
            echo "<td align=right>".(is_numeric($fetchW[6])?sprintf("%.2f",$fetchW[6]):$fetchW[6])."</td>";
            echo "<td align=right>".(is_numeric($fetchW[7])?sprintf("%.2f",$fetchW[7]):$fetchW[7])."</td>";
            echo "<td align=right>".(is_numeric($fetchW[3])?sprintf("%.2f",$fetchW[3]):$fetchW[3])."</td>";
            if ($edit){
                echo "<td><a href=WfcHtEditPage.php?id=$fetchW[5]>Edit</a></td>";
                if ($this->selected_dept == "-1") echo "<td><a href=?action=undelete&id=$fetchW[5]>Undelete</a></td>";
                else echo "<td><a href=?action=delete&id=$fetchW[5]>Delete</a></td>";
            }
            echo "</tr>";
        }
        echo '</table>';

        return ob_get_clean();
    }

    private function deptOptions($sql, $deptsR)
    {
        echo "Show Department: ";
        echo "<select class=\"form-control\" onchange=\"top.location='?showdept='+this.value;\">";
        echo "<option value=\"\">All</option>";
        while ($deptsW = $sql->fetch_row($deptsR)) {
            if ($this->selected_dept == $deptsW[1]) {
                echo "<option value=$deptsW[1] selected>$deptsW[0]</option>";
            } else {
                echo "<option value=$deptsW[1]>$deptsW[0]</option>";
            }
        }
    }
}

FannieDispatch::conditionalExec();

