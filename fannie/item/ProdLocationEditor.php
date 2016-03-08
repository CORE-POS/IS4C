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
    protected $header = 'Bad Scans';
    protected $title = 'Bad Scans';

    public $description = '[Bad Scan Tool] shows information about UPCs that were scanned
    at the lanes but not found in POS.';
    public $has_unit_tests = true;

    private $date_restrict = 1;

    function preprocess()
    {
        $this->__routes[] = 'get<id1>';
        return parent::preprocess();
    }

    function get_id1_view()
    {
        
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        //$dbc = $this->connection;
        
        if (isset($_GET['id1'])) {
            
            $id1 = $_GET['id1'];
            $id2 = $_GET['id2'];
            
            $query = $dbc->prepare('
                select 
                    p.upc, 
                    p.description, 
                    p.department 
                from products as p 
                    left join prodPhysicalLocation as pp on pp.upc=p.upc 
                    left join batchList as bl on bl.upc=p.upc 
                    left join batches as b on b.batchID=bl.batchID 
                where b.batchID >= ' . $id1 . '
                    and b.batchID <= ' . $id2 . ' 
                    and pp.floorSectionID is NULL 
                order by p.department;
            ');
            $result = $dbc->execute($query);
            echo var_dump($result);
            $item = array();
            while($row = $dbc->fetch_row($result)) {
                $item[$row['upc']]['upc'] = $row['upc'];
                $item[$row['upc']]['dept'] = $row['department'];
                $item[$row['upc']]['desc'] = $row['description'];
            }
            echo count($item);
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
            print '<table class="table">
                <form method="get">
                    <input type="hidden" name="locations_selected" value="1">
                    <input type="hidden" name="id1" value="' . $id1 . '">
                    <input type="hidden" name="id2" value="' . $id2 . '">
                ';
            foreach ($item as $key => $row) {
                $ret .= '
                    <tr><td>' . $key . '</td>
                    <td>' . $row['desc'] . '</td>
                    <td>' . $row['dept'] . '</td>
                    <td><Span class="collapse"> </span>
                        <select class="form-control input-sm" name="' . $key . '" value="" />
                            <option value="NULL">* no location selected *</option>';
                    
                    foreach ($floor_section as $fs_key => $fs_value) {
                        $ret .= '<option value="' . $fs_key . '" name="' . $key . '">' . $fs_value . '</option>';
                    }
                            
                    $ret .= '</tr>';
            }
            print $ret;
            print '<tr><td><input type="submit" class="form-control" value="Update Locations"></td></table>
                </form>';   
            
        }

        if(isset($_GET['locations_selected'])) {
            echo "<h1>you've pressed the submit button</h1>";
            
            foreach($_GET as $key => $value){
                if (strlen($key) == 13) {
                        $query = $dbc->prepare('UPDATE prodPhysicalLocation 
                        SET floorSectionID=' . $value . ' 
                        WHERE upc=' . $key . '
                    ');
                    $result = $dbc->execute($query);
                }
            }
            
        }
              
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

