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
        
        $this->__routes[] = 'get<id1>';
        $this->__routes[] = 'post<save>';
        return parent::preprocess();
    }
    
    function post_save_view()
    {
        
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $store_id = $_POST['store_id'];
        $id1 = $_GET['id1'];
        $id2 = $_GET['id2'];
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
                where b.batchID >= ' . $id1 . '
                    and b.batchID <= ' . $id2 . ' 
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
                $ret .= 'Something went wrong, there are still ' . $count . ' products
                    missing locations in the selected range of batches.';
            } else {
                $ret .= '<div class="text-success"><h3>You have successfully updated the products in the declared 
                    batch range.</h3></div>';
            }
        
        return $ret;
    }

    function get_id1_view()
    {
        
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        
        $id1 = $_GET['id1'];
        $id2 = $_GET['id2'];
        $store_id = $_GET['store_id'];
            
            $query = $dbc->prepare('
                select 
                    p.upc, 
                    p.description as pdesc, 
                    p.department,
                    pu.description as pudesc,
                    pu.brand,
                    d.dept_name
                from products as p 
                    left join prodPhysicalLocation as pp on pp.upc=p.upc 
                    left join batchList as bl on bl.upc=p.upc 
                    left join batches as b on b.batchID=bl.batchID 
                    left join productUser as pu on pu.upc=p.upc
                    left join departments as d on d.dept_no=p.department
                where b.batchID >= ' . $id1 . '
                    and b.batchID <= ' . $id2 . ' 
                    and p.store_id= ' . $store_id . '
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
            $result = $dbc->execute($query);
            $item = array();
            while($row = $dbc->fetch_row($result)) {
                $item[$row['upc']]['upc'] = $row['upc'];
                $item[$row['upc']]['dept'] = $row['department'];
                $item[$row['upc']]['desc'] = $row['pudesc'];
                $item[$row['upc']]['brand'] = $row['brand'];
                $item[$row['upc']]['dept_name'] = $row['dept_name'];
            }
            if (mysql_errno() > 0) {
                echo mysql_errno() . ": " . mysql_error(). "<br>";
            } 

            $query = $dbc->prepare('SELECT
                    floorSectionID,
                    name
                FROM FloorSections;');
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
                <form method="post">
                    <input type="hidden" name="save" value="1">
                    <input type="hidden" name="id1" value="' . $id1 . '">
                    <input type="hidden" name="id2" value="' . $id2 . '">
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
                        $ret .= '<option value="' . $fs_key . '" name="' . $key . '">' . $fs_value . '</option>';
                    }
                            
                    $ret .= '</tr>';
            }
            
            $ret .= '<tr><td><input type="submit" class="form-control" value="Update Locations"></td></table>
                </form>';   
            
        
        return $ret;
    }
    
    function get_view()
    {
        $ret = "";
        $ret .= '
            <div class="col-md-2"><form method="get">
            <p> 
                Enter range of batchIDs to check for items missing location.
            </p>
                <select class="form-control inline" name="store_id" required>
                <option value="1">Hillside</option>
                <option value="2">Denfeld</option>
                <input type="text" class="form-control inline" name="id1" autofocus required>
                <input type="text" class="form-control inline" name="id2" required>
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

