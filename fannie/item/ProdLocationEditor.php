<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Community Co-op

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
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ProdLocationEditor extends FannieRESTfulPage
{
    protected $header = 'Product Location Update';
    protected $title = 'Product Location Update';

    public $description = '[Product Location Update] find and update products missing 
        floor section locations.';
    public $has_unit_tests = true;

    private $date_restrict = 1;
    private $data = array();

    function preprocess()
    {
        
        $this->__routes[] = 'get<start>';
        $this->__routes[] = 'post<save>';
        return parent::preprocess();
    }
    
    function post_save_view()
    {
        
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $store_id = $_POST['store_id'];
        $start = $_GET['start'];
        $end = $_GET['end'];
        $ret = '';
        $item = array();
        foreach ($_POST as $upc => $section) {
            if (strlen($upc) == 13) $item[$upc] = $section;
        }
            
        $model = new ProdPhysicalLocationModel($dbc);
        foreach ($item as $upc => $section) {
            $model->upc($upc);
            $model->store_id($store_id);
            $model->floorSectionID($section);
            $result = $model->save();
        }
        
        $query = $dbc->prepare('
                select 
                    p.upc,
                    pp.floorSectionID
                from products as p 
                    left join prodPhysicalLocation as pp on pp.upc=p.upc 
                    left join batchList as bl on bl.upc=p.upc 
                    left join batches as b on b.batchID=bl.batchID 
                where b.batchID >= ' . $start . '
                    and b.batchID <= ' . $end . ' 
                    and pp.floorSectionID is NULL 
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
                group by p.upc
                order by p.department;
            ');
            $result = $dbc->execute($query);
            $count = 0;
            while($row = $dbc->fetch_row($result)) {
                $count++;
                //$ret .= $row['upc'] . " location updated to " . $row['floorSectionID'] . "<br>";
            }
            if (mysql_errno() > 0) {
                echo mysql_errno() . ": " . mysql_error(). "<br>";
            } 
            if ($count > 0) {
                $ret .= 'There are still ' . $count . ' products
                    missing locations in the selected range of batches.';
            } else {
                $ret .= '<div class="text-success"><h3>You have successfully updated the products in the declared 
                    batch range.</h3></div>';
            }
        
        return $ret;
    }

    function get_start_view()
    {
        
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        
        $start = FormLib::get('start');
        $end = FormLib::get('end');
        $store_id = FormLib::get('store_id');
        $args = array($start, $end, $store_id);
            
            $query = $dbc->prepare('
                select 
                    p.upc, 
                    p.description as pdesc, 
                    p.department,
                    pu.description as pudesc,
                    p.brand,
                    d.dept_name
                from products as p 
                    left join prodPhysicalLocation as pp on pp.upc=p.upc 
                    left join batchList as bl on bl.upc=p.upc 
                    left join batches as b on b.batchID=bl.batchID 
                    left join productUser as pu on pu.upc=p.upc
                    left join departments as d on d.dept_no=p.department
                where b.batchID >= ?
                    and b.batchID <= ?
                    and p.store_id= ?
                    and (pp.floorSectionID is NULL OR pp.floorSectionID=0)
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
                order by p.department;
            ');
            $result = $dbc->execute($query, $args);
            $item = array();
            while($row = $dbc->fetch_row($result)) {
                $item[$row['upc']]['upc'] = $row['upc'];
                $item[$row['upc']]['dept'] = $row['department'];
                $item[$row['upc']]['desc'] = $row['pdesc'];
                $item[$row['upc']]['brand'] = $row['brand'];
                $item[$row['upc']]['dept_name'] = $row['dept_name'];
            }
            if (mysql_errno() > 0) {
                echo mysql_errno() . ": " . mysql_error(). "<br>";
            } 
            
            //  Find suggestions for each item's location based on department.
            foreach ($item as $key => $row) {
                //$item[$key]['sugDept'] = 
                if($row['dept']>=1 && $row['dept']<=17){
                    $item[$key]['sugDept'] = 16;
                }
                
                // Cool 1
                if ($row['dept']==39 || $row['dept']==40 || $row['dept']==44 || $row['dept']==45) {
                    $item[$key]['sugDept'] = 11;
                }
                
                // Cool 2
                if ($row['dept']==38 || $row['dept']==41 || $row['dept']==42){
                    $item[$key]['sugDept'] = 13;
                }
                
                // Cool 3
                if ($row['dept']==32 || $row['dept']==35 || $row['dept']==37 ) {
                    $item[$key]['sugDept'] = 14;
                }
                
                // Cool 4
                if ( ($row['dept']>=26 && $row['dept']<=31)
                    || ($row['dept']==34 ) ) {
                    $item[$key]['sugDept'] = 15;
                }
                
                // Grocery 1
                if( ($row['dept']>=170 && $row['dept']<=173)
                    || ($row['dept']==160 ) || ($row['dept']==169) ) {
                    $item[$key]['sugDept'] = 1;
                }
                
                // Grocery 2
                if( ($row['dept']==156) || ($row['dept']==161) || ($row['dept']==163)
                    || ($row['dept']==166) || ($row['dept']==172) || ($row['dept']==174)
                    || ($row['dept']==175) || ($row['dept']==177) ) {
                    $item[$key]['sugDept'] = 2;
                }
                
                // Grocery 3
                if( ($row['dept']==153) || ($row['dept']==157)
                    || ($row['dept']==164) || ($row['dept']==167) || ($row['dept']==168)
                    || ($row['dept']==176) ) {
                    $item[$key]['sugDept'] = 3;
                }
                
                // Grocery 4
                if( ($row['dept']==151) || ($row['dept']==152) ) {
                    $item[$key]['sugDept'] = 4;
                }
                
                // Grocery 5
                if( ($row['dept']==159) || ($row['dept']==155) ) {
                    $item[$key]['sugDept'] = 5;
                }
                
                // Grocery 6
                if($row['dept']==158) {
                    $item[$key]['sugDept'] = 6;
                }
                
                // Grocery 7
                if($row['dept']==165) {
                    $item[$key]['sugDept'] = 7;
                }
                
                // Grocery 8
                if($row['dept']==159 || $row['dept']==162 || $row['dept']==179) {
                    $item[$key]['sugDept'] = 8;
                }
                
                // Wellness 1
                if($row['dept']==88 || $row['dept']==90 || $row['dept']==95 ||
                    $row['dept']==96 || $row['dept']==98 || $row['dept']==99 ) {
                    $item[$key]['sugDept'] = 9;
                }
                
                // Wellness 2
                if($row['dept']==86 ||$row['dept']==87 || $row['dept']==89 ||
                    $row['dept']==90 || $row['dept']==90 || $row['dept']==91 ||
                    $row['dept']==94 || $row['dept']==97 || $row['dept']==102) {
                    $item[$key]['sugDept'] = 10;
                }
                
                // Wellness 3
                if($row['dept']==101 || ($row['dept']>=105 && $row['dept']<=109) ) {
                    
                    $item[$key]['sugDept'] = 12;
                }
                
            }

            $query = $dbc->prepare('SELECT
                    floorSectionID,
                    name
                FROM FloorSections
                ORDER BY name;');
            $result = $dbc->execute($query);
            $floor_section = array();
            while($row = $dbc->fetch_row($result)) {
                $floor_section[$row['floorSectionID']] = $row['name'];
            }
            if (mysql_errno() > 0) {
                echo mysql_errno() . ": " . mysql_error(). "<br>";
            }    
            
            $ret = "";
            $ret .= '<table class="table">
                <thead>
                    <th>UPC</th>
                    <td>Brand</th>
                    <td>Description</th>
                    <td>Department</th>
                    <td>Dept. No.</th>
                    <td>Location</th>
                </thead>
                <form method="post">
                    <input type="hidden" name="save" value="1">
                    <input type="hidden" name="start" value="' . $start . '">
                    <input type="hidden" name="end" value="' . $end . '">
                    <input type="hidden" name="store_id" value="' . $store_id . '">
                ';
            foreach ($item as $key => $row) {
                $ret .= '
                    <tr><td><a href="ItemEditorPage.php?searchupc=' . $key . '" target="">' . $key . '</a></td>
                    <td>' . $row['brand'] . '</td>
                    <td>' . $row['desc'] . '</td>
                    <td>' . $row['dept_name'] . '</td>
                    <td>' . $row['dept'] . '</td>
                    <td><Span class="collapse"> </span>
                        <select class="form-control input-sm" name="' . $key . '" value="" />
                            <option value="NULL">* no location selected *</option>';
                    
                    foreach ($floor_section as $fs_key => $fs_value) {
                        if ($fs_key == $item[$key]['sugDept']) {
                            $ret .= '<option value="' . $fs_key . '" name="' . $key . '" selected>' . $fs_value . '</option>';
                        } else {
                            $ret .= '<option value="' . $fs_key . '" name="' . $key . '">' . $fs_value . '</option>';
                        }
                    }
                            
                    $ret .= '</tr>';
            }
            
            $ret .= '<tr><td><input type="submit" class="btn btn-default" value="Update Locations"></td></table>
                </form>';   
            
        
        return $ret;
    }
    
    function get_view()
    {
        $ret = "";
        $ret .= '
            <div class="col-md-2"><form method="get">
            <p> 
                Enter range of batchIDs to check for items missing locations.
            </p>
                <select class="form-control inline" name="store_id" required>
                <option value="1">Hillside</option>
                <option value="2">Denfeld</option>
                <input type="text" class="form-control inline" name="start" autofocus required>
                <input type="text" class="form-control inline" name="end" required>
                <input type="submit" class="btn btn-default" value="Go">
            </form></div>
        ';
        
        return $ret;
    }

    public function helpContent()
    {
        return '<p>=
            This tool edits physical sales floor location
            of products found in batches that fall within a 
            specified range of batch IDs.
            </p>
            ';
    }
    
}

FannieDispatch::conditionalExec();

