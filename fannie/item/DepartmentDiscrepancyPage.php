<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op, Duluth, MN

    This file is part of CORE-POS.

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

require(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class DepartmentDiscrepancyPage extends FannieRESTfulPage {

    protected $header = 'Discrepancies Within Departments';
    protected $title = 'Discrepancies Within Departments';

    public $description = '[Department Discrepancies] scan for tax, food-stamp, 
        and wic discrepancies by departments.';
    public $themed = true;
    
    function preprocess()
    {   
        $this->__routes[] = 'get<dept>';
        $this->__routes[] = 'post<dept><update>';
        return parent::preprocess();
    }
    
    public function post_dept_update_view()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $upcs = FormLib::get('upc');
        $depts = FormLib::get('deptno');
        $taxes = FormLib::get('tax');
        $foods = FormLib::get('foodstamp');
        $wics = FormLib::get('wic');
        $ret = array('error'=>NULL,'error_msg'=>NULL);
        $prodP = $dbc->prepare('
            UPDATE products
            SET department=?,
                tax=?,
                foodstamp=?,
                wicable=?,
                modified=' . $dbc->now() . '
            WHERE upc=?');
        for ($i=0; $i<count($upcs); $i++) {
            if ($ret['error'] == 0) {
                $args = array(
                    $depts[$i],
                    $taxes[$i],
                    $foods[$i],
                    $wics[$i],
                    $upcs[$i],
                );
                $saved = $dbc->execute($prodP, $args);
                if (!$saved) {
                    $ret['error'] = 1;
                    $ret['error_msg'] = 'Save failed';
                } else {
                    echo '<span class="text-success">' . $upcs[$i] . ' successfully updated.</span><br>';
                }
            } else {
                echo '<span class="text-danger">Data Did Not Save</span>';
            }
        }
        
        echo '<a class="btn btn-default" href="DepartmentDiscrepancyPage.php">Back</a><br><br>';

        $update = new ProdUpdateModel($dbc);
        $update->logManyUpdates($upcs, 'EDIT');
    }
    
    public function get_dept_view()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $dept_no = FormLib::get('dept_no');
        $dept_name = FormLib::get('dept_name');
        $ret = '<h4 align="center">Department #' . $dept_no . ' - ' . $dept_name . '</h4>';
        
        //  Check for Department Discrapancies between stores
        if ($msg = self::getDeptDiscrepancies($dbc) != "") {
            $ret .= '
                <div class="panel panel-warning>Department discrepancies discovered between stores.
                    <br>Please fix discrepancies before updating departments.
                    <br>' . $msg . '</div>';
        }
        
        //  Get list of Departments
        $query = $dbc->prepare('SELECT dept_no, dept_name FROM departments GROUP BY dept_no ORDER BY dept_no;');
        $result = $dbc->query($query);
        $dept = array();
        while ($row = $dbc->fetch_row($result)) {
            //$dept_no[] = $row['dept_no'];
            //$dept_name[] = $row['dept_name'];
            $dept[$row['dept_no']] = $row['dept_name'];
        }
        
        //  Get Product Info.
        if (FormLib::get('order') == 'department') {
            $order = 'p.brand';
        } elseif (FormLib::get('order') == 'tax') {
            $order = 'p.tax';
        } elseif (FormLib::get('order') == 'foodstamp') {
            $order = 'p.foodstamp';
        } elseif (FormLib::get('order') == 'wic') {
            $order = 'p.wicable';
        }
        
        $args = array($dept_no);
        $query = $dbc->prepare('
            SELECT 
                p.upc, 
                p.description,
                p.brand,
                u.description AS longDesc,
                u.brand AS longBrand,
                p.tax,
                p.foodstamp,
                p.wicable
            FROM products AS p
            LEFT JOIN productUser AS u ON p.upc=u.upc
            WHERE p.department = ?
                AND p.store_id = 1
            ORDER BY ' . $order . '
        ');
        $result = $dbc->execute($query, $args);
        $ret .= '
            <table class="table table-condensed small"><form method="post" class="form-inline">
                <thead>
                    <th>UPC</th>
                    <th>Brand</th>
                    <th>Description</th>
                    <th>Department</th>
                    <th>Tax</th>
                    <th>Food Stamp</th>
                    <th>WIC</th>
                </thead>
        ';
        while ($row = $dbc->fetch_row($result)) {
            $upc[] = $row['upc'];
            $ret .= '<tr>';
            $ret .= '<td><a href="ItemEditorPage.php?searchupc=' 
                . $row['upc'] . '" target="_blank">' . $row['upc'] . '</a></td>';
            
            if (isset($row['longBrand']) && $row['longBrand'] != '') {
                $ret .= '<td>' . $row['longBrand'] . '</td>';                
            } else {
                $ret .= '<td>' . $row['brand'] . '</td>';                
            }
            if (isset($row['longDesc']) && $row['longDesc'] != '') {
                $ret .= '<td>' . $row['longDesc'] . '</td>';
            } else {
                $ret .= '<td>' . $row['description'] . '</td>';
            }
            
            $ret .= '<td><select style="height:20px; width:150px" name="deptno[]">';
            
            foreach ($dept as $department_no => $department_name) {
                $ret .= '<option value="' . $department_no . '"';
                
                if (($department_no) == $dept_no) {
                    $ret .= 'selected>' . $department_no . ' - ' . $department_name . '</option>';
                } else {
                    $ret .= '>' . $department_no . ' - ' . $department_name . '</option>';
                }                
            }
            $ret .= '</select></td>';
            
            $tax = $row['tax'];
            if ($tax == 0) {
                $color = 'default';
            } elseif ($tax == 1) {
                $color = 'warning';
            } else {
                $color = 'info';
            }
            $ret .= '<td><select style="height:20px;width:150px" class="alert-' . $color . '" name="tax[]">';
            $ret .= '<option value="0"';
            if ($tax == 0) {
                $ret .= ' selected>No Tax</option>';
            } else {
                $ret .= '>No Tax</option>';                
            }
            $ret .= '<option value="1"';
            if ($tax == 1) {
                $ret .= ' selected>Regular</option>';
            } else {
                $ret .= '>Regular</option>';                
            }
            $ret .= '<option value="2"';
            if ($tax == 2) {
                $ret .= ' selected>Deli</option>';
            } else {
                $ret .= '>Deli</option>';                
            }
            $ret .= '</select></td>';
            
            $foodstamp = $row['foodstamp'];
            if ($foodstamp == 0) {
                $color = 'danger';
            } else {
                $color = 'success';
            }
            $ret .= '<td><select style="height:20px;width:150px" class="alert-' . $color . '" name="foodstamp[]">';
            $ret .= '<option value="0"';
            if ($foodstamp == 0) {
                $ret .= ' selected>No - foodstamp</option>';
            } else {
                $ret .= '>No - foodstamp</option>';                
            }
            $ret .= '<option value="1"';
            if ($foodstamp == 1) {
                $ret .= '" selected>Yes - foodstamp</option>';
            } else {
                $ret .= '>Yes - <i>foodstamp</option>';                
            }
            $ret .= '</select></td>';
            
            
            $wic = $row['wicable'];
            if ($wic == 0) {
                $color = 'danger';
            } else {
                $color = 'success';
            }
            $ret .= '<td><select style="height:20px;width:150px" class="alert-' . $color . '" name="wic[]">';
            $ret .= '<option value="0"';
            if ($wic == 0) {
                $ret .= '" selected>No - wic</option>';
            } else {
                $ret .= '>No - wic</option>';                
            }
            $ret .= '<option value="1"';
            if ($wic == 1) {
                $ret .= '" selected>Yes - wic</option>';
            } else {
                $ret .= '>Yes - wic</option>';                
            }
            $ret .= '</select></td>';
            $ret .= '<td><input type="hidden" name="upc[]" value="' . $row['upc'] . '"></td>';
              
        }
        $ret .= '<a href="DepartmentDiscrepancyPage.php">Back</a><br><br>';
        $ret .= '</tr><tr><td><input type="submit" class="btn btn-default" 
            value="Update Products"></td>';
        $ret .= '</table>
            <input type="hidden" name="update" value="1"></td>
            <input type="hidden" name="dept" value="' . $dept_no . '">
            </form>';
        
        return $ret;
    }
    
    public function get_view()
    { 
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $ret = '';
        $query = $dbc->prepare('
            SELECT dept_no, dept_name 
            FROM departments 
            GROUP BY dept_no ORDER BY dept_no;
        ');
        $result = $dbc->query($query);
        $ret .= '
            <table class="table table-striped">
                <thead>
                    <th style="width:150px;">Department</th>
                    <th style="width:150px;">Tax (0,R,D)</th>
                    <th style="width:150px;">Foodstamp (FALSE/TRUE)</th>
                    <th style="width:150px;">Wic (FALSE/TRUE)</th>
                </thead>
        ';
        $dept = array();
        while ($row = $dbc->fetch_row($result)) {
            $dept[$row['dept_no']] = $row['dept_name'];
        }
        foreach ($dept as $dept_no => $dept_name) {
            $ret .= '<tr>';
            $ret .= '<td><a href="DepartmentDiscrepancyPage.php?dept=1&dept_no='
                . $dept_no . '&dept_name=' . $dept_name . '&order=department">' . $dept_no . ' ' . $dept_name . '</a></td>';            
            $queryD = $dbc->prepare('
                SELECT 
                    sum(CASE WHEN tax=0 THEN 1 ELSE 0 END) as noTax,
                    sum(CASE WHEN tax=1 THEN 1 ELSE 0 END) as regTax,
                    sum(CASE WHEN tax=2 THEN 1 ELSE 0 END) as delTax,
                    sum(CASE WHEN foodstamp=0 THEN 1 ELSE 0 END) as noFoodstamp,
                    sum(CASE WHEN foodstamp=1 THEN 1 ELSE 0 END) as yesFoodstamp,
                    sum(CASE WHEN wicable=0 THEN 1 ELSE 0 END) as noWic,
                    sum(CASE WHEN wicable=1 THEN 1 ELSE 0 END) as yesWic
                FROM products 
                WHERE inUse = 1 
                    AND store_id = 1
                    AND department = ?;
            ');
            $resultD = $dbc->execute($queryD, $dept_no);
            while ($row = $dbc->fetch_row($resultD)) {
                $i = $row['noTax'];
                $j = $row['regTax'];
                $k = $row['delTax'];
                //  Find the lower / lowest number
                if ($i>0 && $j>0 || $j>0 && $k>0 || $i>0 && $k>0) {
                    if ($i>0) $min = $i;
                    if ($j>0 && $j<$min) $min = $j;
                    if ($k>0 && $k<$min) $min = $k;
                    if ($min == $i) {
                        $i = '<span class="redcircle"><b>' . $i . '</b></span>';
                    } elseif ($min == $j) {
                        $j = '<span class="redcircle"><b>' . $j . '</b></span>';
                    } elseif ($min == $k) {
                        $k = '<span class="redcircle"><b>' . $k . '</b></span>';
                    }
                }
                $ret .= '<td><a href="DepartmentDiscrepancyPage.php?dept=1&dept_no=' 
                    . $dept_no . '&dept_name=' . $dept_name . '&order=tax">' . $i . ' ' . $j . ' ' . $k . '</a></td>'; //Tax
                
                unset($k);                    
                $i = $row['noFoodstamp'];
                $j = $row['yesFoodstamp'];
                if ($i>0 && $j>0) {
                    if ($i>0) $min = $i;
                    if ($j>0 && $j<$min) $min = $j;
                }
                if ($min == $i && $j != 0) {
                    $i = '<span class="redcircle"><b>' . $i . '</b></span>';
                } elseif ($min == $j && $i != 0) {
                    $j = '<span class="redcircle"><b>' . $j . '</b></span>';
                }
                $ret .= '<td><a href="DepartmentDiscrepancyPage.php?dept=1&dept_no=' 
                    . $dept_no . '&dept_name=' . $dept_name . '&order=foodstamp">' . $i . ' ' . $j . '</a></td>'; //Foodstamp
                
                unset($k);                    
                $i = $row['noWic'];
                $j = $row['yesWic'];
                if ($i>0 && $j>0) {
                    if ($i>0) $min = $i;
                    if ($j>0 && $j<$min) $min = $j;
                }
                if ($min == $i && $j != 0) {
                    $i = '<span class="redcircle"><b>' . $i . '</b></span>';
                } elseif ($min == $j && $i != 0) {
                    $j = '<span class="redcircle"><b>' . $j . '</b></span>';
                }
                $ret .= '<td><a href="DepartmentDiscrepancyPage.php?dept=1&dept_no=' 
                    . $dept_no . '&dept_name=' . $dept_name . '&order=wic">' . $i . ' ' . $j . '</a></td>'; //Wic
                   
            }
            
            
            
        }
        $ret .= '</table>';
        
        foreach ($dept as $no => $name) {
        }
        
        return $ret;
    }
    
    private function getDeptDiscrepancies($dbc)
    {
        $itemA = array();
        $itemB = array();

        $queryA = $dbc->prepare('
            SELECT upc, department 
            FROM products 
                WHERE store_id=1
                    AND department NOT BETWEEN 508 AND 998
                    AND department NOT BETWEEN 250 AND 259
                    AND department NOT BETWEEN 225 AND 234
                    AND department NOT BETWEEN 1 AND 25
                    AND department NOT BETWEEN 61 AND 78
                    AND department != 46
                    AND department != 150
                    AND department != 208
                    AND department != 235
                    AND department != 240
                    AND department != 500
        ');
        $resultA = $dbc->execute($queryA);
        while ($row = $dbc->fetch_row($resultA))  {
            $itemA[$row[0]] = $row[1];
        }
        
        $queryB = $dbc->prepare('
            SELECT upc, department 
            FROM products 
            WHERE store_id=2
                AND department NOT BETWEEN 508 AND 998
                    AND department NOT BETWEEN 250 AND 259
                    AND department NOT BETWEEN 225 AND 234
                    AND department NOT BETWEEN 1 AND 25
                    AND department NOT BETWEEN 61 AND 78
                    AND department != 46
                    AND department != 150
                    AND department != 208
                    AND department != 235
                    AND department != 240
                    AND department != 500
        ');
        $resultb = $dbc->execute($queryB);
        while ($row = $dbc->fetch_row($resultB))  {
            $itemB[$row[0]] = $row[1];
        }
        
        $count = 0;
        foreach ($itemA as $upc => $department)  {
            if (isset($itemB[$upc])) {
                if ($department != $itemB[$upc]) $count++;
            }
        }

        $msg = "";
        if ($count > 0 ) {
            $msg = $count . " department discrepancies were discovered<br>";
            foreach ($itemA as $upc => $department)  {
                $link = "<a href=ItemEditorPage.php?searchupc=" . $upc . "&nbsp";
                if ($department != $itemB[$upc]) {
                    $msg .=  $link . $department . "&nbsp" . $itemB[$upc] . "</a><br>";
                }
            }
            
        }
        
        return $msg;
    }
    
    public function css_content()
    {
        return '
            a {}
            a:hover {
                text-decoration: none;
                color: lightblue;
            }
            /*
            span.redcircle {
                border: 1px dotted tomato;
                border-radius: 100%;
                padding: 3px;
            }
            */
            span.redcircle {
                background-color: tomato;
                color: white;
            }
        ';
    }
    
    public function helpContent()
    {
        return '<p>
            This page scans for the tax, foodstamp, and wic status of 
            all products that are currently in use. Click on a link to 
            view & update product information.
            <ul>
                <li>Click on a <b>department</b> name to view a list of products sorted by brand. </li>
                <li>Click the <b>tax statistics</b> to view a list sorted by tax status. </li>
                <li>Click <b>foodstamp</b> to view a list sorted by food-stamp-ability. </li>
                <li>Click <b>WIC</b> to view a list sorted by WIC-ability. </li>
            </ul>
            </p>';
    }

}

FannieDispatch::conditionalExec();

