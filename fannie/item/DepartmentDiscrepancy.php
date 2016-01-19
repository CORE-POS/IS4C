<?php
/*******************************************************************************

    Copyright 2009,2013 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class DepartmentDiscrepancy extends FanniePage 
{
    protected $title = "Department Discrepancy";
    protected $header = "Department Discrepancy Page";

    public $description = 'Checks for tax and foodstamp discrepancies by department';
    protected $must_authenticate = true;
    private $mode = 'form';

    public function preprocess()
    {
        if (FormLib::get_form_value('dept') !== '') {
            $this->mode = 'get_dept';
        } elseif (FormLib::get_form_value('tax0') !== '') {
            $this->mode = 'get_tax_change';
        } elseif (FormLib::get_form_value('fs0') !== '') {
            $this->mode = 'get_fs_change';
        }
        
        return true;
    }
    
    public function body_content()
    {
        if ($this->mode == 'form') {
            return $this->form_content();
        } elseif ($this->mode == 'get_dept') {
            return $this->get_dept_content();
        } elseif ($this->mode == 'get_tax_change') {
            return $this->get_tax_change_content();
        } elseif ($this->mode == 'get_fs_change') {
            return $this->get_fs_change_content();
        }
    }
    
    public function get_fs_change_content() 
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        
        for ($i=0; $i<FormLib::get('iFs'); $i++) {
            if (FormLib::get('fs' . $i) != 'noChange') {
                $query = $dbc->prepare("UPDATE products SET foodstamp = ? WHERE upc=?");
                $args = array();
                if (FormLib::get('fs' . $i) == 'fs0') {
                    $args[] = 0;
                } else {
                    $args[] = 1;
                } 
                $args[] = FormLib::get('plu'  . $i);
                $result = $dbc->execute($query, $args);
            }
        }
        
        echo FormLib::get('iFs') . " selected items have been updated.";
        echo "<p><a href=\"DepartmentDiscrepancy.php\" role=\"button\">Back to Select Department</a></p>";
    }
    
    public function get_tax_change_content() 
    {   
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        
        for ($i=0; $i<FormLib::get('iTax'); $i++) {
            if (FormLib::get('tax' . $i) != 'noChange') {
                $query = $dbc->prepare("UPDATE products SET tax = ? WHERE upc=?");
                $args = array();
                if (FormLib::get('tax' . $i) == 'tax1') {
                    $args[] = 1;
                } elseif (FormLib::get('tax' . $i) == 'tax2') {
                    $args[] = 2;
                } else {
                    $args[] = 0;
                } 
                $args[] = FormLib::get('upc' . $i);
                $result = $dbc->execute($query, $args);
            }
        }
        echo FormLib::get('iTax') . " selected items have been updated.";
        echo "<p><a href=\"DepartmentDiscrepancy.php\" role=\"button\">Back to Select Department</a></p>";
    }

    public function get_dept_content() 
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        
        $query = "SELECT dept_no, dept_name FROM departments GROUP BY dept_no ORDER BY dept_no;";
        $result = $dbc->query($query);
        while ($row = $dbc->fetch_row($result)) {
            $dp_no[] = $row['dept_no'];
        }
        $key = array_search(FormLib::get('dept'), $dp_no);
        
        echo "<a href=\"http://key/git/fannie/item/DepartmentDiscrepancy.php?dept=";
        echo $dp_no[$key - 1];
        echo "\" class=\"btn btn-default\">PREV </a>&nbsp;";
        
        echo "<a href=\"http://key/git/fannie/item/DepartmentDiscrepancy.php?dept=";
        echo $dp_no[$key + 1];
        echo "\" class=\"btn btn-default\">NEXT </a><br>";
        
        $query = "SELECT department FROM products GROUP BY department";
        $result = $dbc->query($query);
        while ($row = $dbc->fetch_row($result)) {
            $department[] = $row['department'];
        }
        $query = $dbc->prepare("select SUM(CASE WHEN tax=0 THEN 1 ELSE 0 END) as tax0,  
                    SUM(CASE WHEN tax=1 THEN 1 ELSE 0 END) as tax1,     
                    SUM(CASE WHEN tax=2 THEN 1 ELSE 0 END) as tax2,     
                    SUM(CASE WHEN foodstamp=0 THEN 1 ELSE 0 END) as fs0,
                    SUM(CASE WHEN foodstamp=1 THEN 1 ELSE 0 END) as fs1 
                FROM products where department=?
                    AND inUse=1
                    AND store_id=1");
        $result = $dbc->execute($query, array(FormLib::get('dept')));
        while ($row = $dbc->fetch_row($result)) {
            $tax0 = $row['tax0'];
            $tax1 = $row['tax1'];
            $tax2 = $row['tax2'];
            $fs0 = $row['fs0'];
            $fs1 = $row['fs1'];
        }
        $query = $dbc->prepare("select dept_name from departments where dept_no=?");
        $dept_name = $dbc->getValue($query, array(FormLib::get('dept')));
        
        echo "In Department <b>" . FormLib::get('dept') . " - " . $dept_name . "</b><br>" . 
            "<div class=\"container\">" . 
            $tax0 . " items found with no tax<br>" .
            $tax1 . " items found with regular tax<br>" .
            $tax2 . " items found with deli tax tax<br><br>" .
            $fs0 . " items non-foodstamp-able<br>" .
            $fs1 . " items foodstamp-able<br>" . 
            "</div>";
            
        //* Check for items not following tax pattern
        $query = $dbc->prepare("select * from products where department=? and tax=?");
        $args = array(FormLib::get('dept'));
        if ( ($tax0 < $tax1 && $tax0 != 0) || ($tax0 < $tax2 && $tax0 != 0) ) {
            $args[] = 0;
            $taxType = "No Tax";
        } elseif ( ($tax1 < $tax0 && $tax1 != 0) || ($tax1 < $tax2 && $tax1 != 0) ) {
            $args[] = 1;
            $taxType = "Regular Tax";
        } elseif ( ($tax2 < $tax0 && $tax2 != 0) || ($tax2 < $tax1 && $tax2 != 0) ) {
            $args[] = 2;
            $taxType = "Deli Tax";
        }
        $result = $dbc->execute($query, $args);
        while ($row = $dbc->fetch_row($result)) {
            $upc[] = $row['upc'];
            $desc[] = $row['description'];
            $tax[] = $row['tax'];
        }
        
        $ret .= "<form method=\"get\">";
        $ret .= "<table class=\"table table-striped\">
            <th>UPC</th>
            <th>Description</th>
            <th>Current Tax Type</th>
            <th>Change Tax Type To</th>
            <th></th>
            <th></th>";
        for ($i=0; $i<count($upc); $i++) {
            $ret .= "<tr><td><a href=\"ItemEditorPage.php?searchupc={$upc[$i]}\">{$upc[$i]}</a></td>";
            $ret .= "<td>" . $desc[$i] . "</td>";
            $ret .= "<td><b>" . $taxType . "</b></td>";
            $ret .= "<td><input type=\"radio\" name=\"tax{$i}\" id=\"tax{$i}\" value=\"tax0\" required>no tax </td>";
            $ret .= "<td><input type=\"radio\" name=\"tax{$i}\" id=\"tax{$i}\" value=\"tax1\" required>regular tax </td>";
            $ret .= "<td><input type=\"radio\" name=\"tax{$i}\" id=\"tax{$i}\" value=\"tax2\" required>deli tax </td>";
            $ret .= "<td><input type=\"radio\" name=\"tax{$i}\" id=\"tax{$i}\" value=\"noChange\" required>do not change </tr>";
            $ret .= "<td><input type=\"hidden\" name=\"upc{$i}\" value=\"{$upc[$i]}\">";
        }
        $ret .= "</table>";
        $ret .= "<input type=\"hidden\" name=\"iTax\" id=\"iTax\" value=\" " . count($upc) . " \">";
        $ret .= "<input class=\"btn btn-default\" type=\"submit\" value=\"Update Tax Values\">";
        $ret .= "</form>";
        $ret .= "<br>";
        
        //* Check for items not following Foodstamp pattern
        $query = $dbc->prepare("select * from products where department=? and foodstamp=?");
        $args = array(FormLib::get('dept'));
        if ($fs0 < $fs1) {
            $args[] = 0;
            $fsType = "Not Foodstamp able";
        } else if ($fs1 < $fs0 ) {
            $args[] = 1;
            $fsType = "Is Foodstamp albe";
        }
        $result = $dbc->execute($query, $args);
        while ($row = $dbc->fetch_row($result)) {
            $upc2[] = $row['upc'];
            $desc2[] = $row['description'];
            $fs2[] = $row['foodstamp'];
        }
        
        $ret .= "<form method=\"get\">";
        $ret .= "<table class=\"table table-striped\">
            <th>UPC</th>
            <th>Description</th>
            <th>Current Foodstamp Type</th>
            <th>Change Foodstamp Type To</th>
            <th></th>
            <th></th>";
        for ($i=0; $i<count($upc2); $i++) {
            $ret .= "<tr><td><a href=\"ItemEditorPage.php?searchupc={$upc2[$i]}\">{$upc2[$i]}</a></td>";
            $ret .= "<td>" . $desc2[$i] . "</td>";
            $ret .= "<td><b>" . $fsType . "</b></td>";
            $ret .= "<td><input type=\"radio\" name=\"fs{$i}\" id=\"fs{$i}\" value=\"fs0\"required>not foodstamp-able </td>";
            $ret .= "<td><input type=\"radio\" name=\"fs{$i}\" id=\"fs{$i}\" value=\"fs1\"required>make foodstamp-able </td>";
            $ret .= "<td><input type=\"radio\" name=\"fs{$i}\" id=\"fs{$i}\" value=\"noChange\"required>do not change </tr>";
            $ret .= "<td><input type=\"hidden\" name=\"plu{$i}\" value=\"{$upc2[$i]}\">";
        }
        $ret .= "</table>";
        $ret .= "<input type=\"hidden\" name=\"iFs\" id=\"iFs\" value=\" " . count($upc2) . " \">";
        $ret .= "<input class=\"btn btn-default\" type=\"submit\" value=\"Update Foodstamp Values\">";
        $ret .= "</form>";
        $ret .= "<p><a href=\"DepartmentDiscrepancy.php\" role=\"button\">Back to Select Department</a></p>";
        
        return $ret;
    }

    public function form_content() 
    {
        $this->addOnloadCommand('$(\'input:first\').focus();');
            $ret = "
            <form method=\"get\">
            <div class=\"form-group\">
                <label>Department</label>
                <select name=\"dept\" id=\"dept\" class=\"form-control\" />";

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
                <button type=\"submit\" class=\"btn btn-default\">Select Department</button>
            </div>
            </form>
            ";
        return $ret;
    }
    
    public function helpContent()
    {
        return '<p>Select a department to compare the number
        of items set to differing tax and foodstamp settings.</p>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->body_content()));
    }
}
    
FannieDispatch::conditionalExec();

