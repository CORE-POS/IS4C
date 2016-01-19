<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Community Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

require(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI'))
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class WicableByDepartment extends FannieRESTfulPage 
{
    protected $header = 'Change Department to Wicable';
    protected $title = 'Change Department to Wicable';

    public $description = '[Wic Items by Department] selects all items within a department as Wic-able.';

    protected $must_authenticate = true;

    public function preprocess()
    {
        $this->__routes[] = 'get<id><confirm>';
        return parent::preprocess();
    }
    
    public function get_id_confirm_view()
    {
        $dbc = $this->connection;
        $prep = $dbc->prepare('UPDATE products
                            SET wicable = 1
                            WHERE department = ?
                            ');
        $res = $dbc->execute($prep, $_GET['id']);
        $row = $dbc->fetchRow($res);
        if (mysql_errno() > 0) {
            echo mysql_errno() . ": " . mysql_error(). "<br>";
        } else {
            echo "Items for department " . $_GET['id'] . " are now wicable.";
        }
    }
    
    public function get_id_view()
    {
        $dbc = $this->connection;
        $prep = $dbc->prepare('SELECT dept_no, dept_name FROM departments WHERE dept_no = ?;');
        $res = $dbc->execute($prep, $this->id);
        $row = $dbc->fetchRow($res);

        return '<div class="alert alert-warning">Mark all items in this department as WIC?</div>
            <p>' . 
            $row['dept_no'] . ' - ' . $row['dept_name'] . 
            '</p>
            <p>
            <a href="?confirm=1&id=' . $this->id . '" class="btn btn-default">Yes, make items WIC</a>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <a href="WicableByDepartment.php" class="btn btn-default">No, keep items as they are</a>
            </p>';
    }
    
    public function get_view()
    {
        $this->addOnloadCommand('$(\'input:first\').focus();');
        $ret = "
        <form method=\"get\">
        <div class=\"form-group\">
            <label>Department</label>
            <select name=\"id\" class=\"form-control\" />";

        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $query = "SELECT dept_no, dept_name FROM departments GROUP BY dept_no ORDER BY dept_no;";
        $result = $dbc->query($query);
        while ($row = $dbc->fetch_row($result)) {
            $dept_no[] = $row['dept_no'];
            $dept_name[] = $row['dept_name'];
        }     
        for ($i=0; $i<count($dept_no); $i++) {
            $ret .= "<option value=\"{$dept_no[$i]}\">{$dept_no[$i]} - {$dept_name[$i]}</option>";
        }
        
        $ret .= "
        </select>
        </div>
        <div class=\"form-group\">
            <button type=\"submit\" class=\"btn btn-default\">Make Department Wicable</button>
        </div>
        </form>
        ";
        return $ret;
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
        $this->id = 1;
        $phpunit->assertNotEquals(0, strlen($this->get_id_view()));
    }
}

FannieDispatch::conditionalExec();
