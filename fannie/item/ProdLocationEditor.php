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
        $this->__routes[] = 'get<batch>';
        $this->__routes[] = 'post<batch><save>';
        $this->__routes[] = 'post<list><save>';
        $this->__routes[] = 'post<upc><save>';
        $this->__routes[] = 'get<start>';
        $this->__routes[] = 'get<searchupc>';
        $this->__routes[] = 'post<newLocation>';
        $this->__routes[] = 'get<list>';
        return parent::preprocess();
    }

    function post_newLocation_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $upc = FormLib::get('upc');
        $upc = str_pad($upc, 13, '0', STR_PAD_LEFT);
        $newLocation = FormLib::get('newLocation');

        $args = array($upc, $newLocation);
        $prep = $dbc->prepare('
            INSERT INTO FloorSectionProductMap (upc, floorSectionID)
                values (?, ?)
        ');
        $dbc->execute($prep, $args);
        if (mysql_errno() > 0) {
            echo mysql_errno() . ": " . mysql_error(). "<br>";
        }

        $ret = '';
        if ($dbc->error()) {
            $ret .= '<div class="alert alert-danger">Save Failed</div>';
            $ret .= '<div class="alert alert-warning">Error: ' . $dbc->error() . '</div>';
        } else {
            $ret .= '<div class="alert alert-success">Product Location Saved</div>';
        }
        $ret .= '<a class="btn btn-default" href="ProdLocationEditor.php?searchupc=Update+Locations+by+UPC">Back</a>&nbsp;&nbsp;';
        $ret .= '<a class="btn btn-default" href="ProdLocationEditor.php">Home</a><br><br>';
        return $ret;
    }

    function post_upc_save_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $upc = FormLib::get('upc');
        $mapID = array();
        $secID = array();
        foreach ($_POST as $key => $value) {
            if(substr($key,0,7) == 'section') {
                $secID[substr($key,7)] = $value;
            }
        }
        $count = FormLib::get('numolocations');
        $newSection = array();
        $mapID = array();
        for ($i = 1; $i <= $count; $i++) {
            $curName = 'newSection' . $i;
            $oldName = 'mapID' . $i;
            $newSection[] = FormLib::get($curName);
        }
        foreach ($secID as $mapID => $sectionID) {
            $args = array($sectionID, $upc, $mapID);
            $prep = $dbc->prepare('
                UPDATE FloorSectionProductMap
                SET floorSectionID = ?
                WHERE upc = ?
                    AND floorSectionProductMapID = ?;
            ');
            $dbc->execute($prep, $args);
            if (mysql_errno() > 0) {
                echo mysql_errno() . ": " . mysql_error(). "<br>";
            }
        }

        $ret = '';
        if ($dbc->error()) {
            $ret .= '<div class="alert alert-danger">Save Failed</div>';
            $ret .= '<div class="alert alert-warning">Error: ' . $dbc->error() . '</div>';
        } else {
            $ret .= '<div class="alert alert-success">Product Location Saved</div>';
        }
        $ret .= '<a class="btn btn-default" href="ProdLocationEditor.php?searchupc=Update+Locations+by+UPC">Back</a>&nbsp;&nbsp;';
        $ret .= '<a class="btn btn-default" href="ProdLocationEditor.php">Home</a><br><br>';

        return $ret;
    }

    function post_batch_save_view()
    {

        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $store_id = FormLib::get('store_id');
        $start = FormLib::get('start');
        $end = FormLib::get('end');

        $ret = '';
        $item = array();
        foreach ($_POST as $upc => $section) {
            $upc = str_pad($upc, 13, '0', STR_PAD_LEFT);
            if ($section > 0) $item[$upc] = $section;
        }

        foreach ($item as $upc => $section) {
            $args = array($upc, $section );
            $prep = $dbc->prepare('
                INSERT INTO FloorSectionProductMap (upc, floorSectionID) values (?, ?);
            ');
            $dbc->execute($prep, $args);
        }
        if (mysql_errno() > 0) {
            echo mysql_errno() . ": " . mysql_error(). "<br>";
        } else {
            $ret .= '<div class="alert alert-success">Update Successful</div>';
        }

        $ret .= '<br><br><a class="btn btn-default" href="javascript:history.back()">Back</a><br><br>';
        $ret .= '<a class="btn btn-default" href="ProdLocationEditor.php">Return</a><br><br>';

        return $ret;
    }

    function post_list_save_view()
    {

        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $store_id = FormLib::get('store_id');
        $start = FormLib::get('start');
        $end = FormLib::get('end');
        $ret = '';
        $item = array();
        foreach ($_POST as $upc => $section) {
            $upc = str_pad($upc, 13, '0', STR_PAD_LEFT);
            if ($section > 0) $item[$upc] = $section;
        }

        foreach ($item as $upc => $section) {
            $store_location = COREPOS\Fannie\API\lib\Store::getIdByIp();
            $floorSectionRange = array();
            if ($store_location == 1) {
                $floorSectionRange[] = 1;
                $floorSectionRange[] = 19;
            } elseif ($store_location == 2) {
                $floorSectionRange[] = 30;
                $floorSectionRange[] = 47;
            }
            //updat to reflect floorSectionID range based on store_location
            $args = array($upc,$floorSectionRange[0],$floorSectionRange[1]);
            $prepZ = ("DELETE FROM FloorSectionProductMap WHERE upc = ? AND floorSectionID BETWEEN ? AND ?");
            $dbc->execute($prepZ,$args);

            $args = array($upc,$section);
            $prep = $dbc->prepare('
                INSERT INTO FloorSectionProductMap (upc, floorSectionID) values (?, ?);
            ');
            $dbc->execute($prep, $args);
        }
        if (mysql_errno() > 0) {
            echo mysql_errno() . ": " . mysql_error(). "<br>";
        } else {
            $ret .= '<div class="alert alert-success">Update Successful</div>';
        }

        $ret .= '<br><br><a class="btn btn-default" href="javascript:history.back()">Back</a><br><br>';
        $ret .= '<a class="btn btn-default" href="ProdLocationEditor.php">Return</a><br><br>';

        return $ret;
    }

    function get_start_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $store_location = COREPOS\Fannie\API\lib\Store::getIdByIp();

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
                    left join FloorSectionProductMap as pp on pp.upc=p.upc
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

            /*  Find suggestions for each item's location based on department.
             *    This needs to be updated now that there are multiple STORE_IDs being used
            */
            foreach ($item as $upc => $row) {
                $item[$upc]['sugDept'] = $this->getLocation($item[$upc]['dept'],$dbc);
            }

            $args = array($store_location);
            $query = $dbc->prepare('SELECT
                    floorSectionID,
                    name
                FROM FloorSections
                WHERE storeID = ?
                ORDER BY name;');
            $result = $dbc->execute($query,$args);
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
                    <td>Dept. No.</th>
                    <td>Department</th>
                    <td>Location</th>
                </thead>
                <form method="post">
                    <input type="hidden" name="save" value="1">
                    <input type="hidden" name="batch" value="1">
                    <input type="hidden" name="start" value="' . $start . '">
                    <input type="hidden" name="end" value="' . $end . '">
                    <input type="hidden" name="store_id" value="' . $store_id . '">
                ';
            foreach ($item as $key => $row) {
                $ret .= '
                    <tr><td><a href="ItemEditorPage.php?searchupc=' . $key . '" target="">' . $key . '</a></td>
                    <td>' . $row['brand'] . '</td>
                    <td>' . $row['desc'] . '</td>
                    <td>' . $row['dept'] . '</td>
                    <td>' . $row['dept_name'] . '</td>
                    <td><Span class="collapse"> </span>
                        <select class="form-control input-sm" name="' . $key . '" value="" />
                            <option value="0">* no location selected *</option>';

                    foreach ($floor_section as $fs_key => $fs_value) {
                        if ($fs_key == $item[$key]['sugDept']) {
                            $ret .= '<option value="' . $fs_key . '" name="' . $key . '" selected>' . $fs_value . '</option>';
                        } else {
                            $ret .= '<option value="' . $fs_key . '" name="' . $key . '">' . $fs_value . '</option>';
                        }
                    }

                    $ret .= '</tr>';
            }

        $ret .= '<tr><td><input type="submit" class="btn btn-default" value="Update Locations"></td>
            <td><a class="btn btn-default" href="ProdLocationEditor.php">Back</a><br><br></td></table>
            </form>';


        return $ret;
    }

    function get_list_view()
    {

        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $store_location = COREPOS\Fannie\API\lib\Store::getIdByIp();

        $ret = "";
        $ret .= '
            <form method="get" class="form-inline">
                <textarea class="form-control" style="width:170px" name="upcs"></textarea>
                <input type="hidden" name="list" value="1">
                <button type="submit" class="btn btn-default btn-xs">Submit</button>
            </form>
        ';

        if ($_GET['upcs']) {
            $upcs = $_GET['upcs'];
            $plus = array();
            $chunks = explode("\r\n", $upcs);
            foreach ($chunks as $key => $str) {
                $plus[] = str_pad($str, 13, '0', STR_PAD_LEFT);
            }
        }

        list($inClause,$args) = $dbc->safeInClause($plus);
        $args[] = $store_location;
        $qString = 'select
                p.upc,
                p.description as pdesc,
                p.department,
                pu.description as pudesc,
                p.brand,
                d.dept_name,
                fslv.sections
            from products as p
                left join FloorSectionProductMap as pp on pp.upc=p.upc
                left join FloorSectionsListView AS fslv ON fslv.upc=p.upc
                left join productUser as pu on pu.upc=p.upc
                left join departments as d on d.dept_no=p.department
            WHERE p.upc IN ('.$inClause.')
                AND fslv.storeID = ?
            order by p.department;';

        $query = $dbc->prepare($qString);
        $result = $dbc->execute($query, $args);

        //  Catch products that don't yet have prod maps.
        $upcsWithLoc = array();
        while ($row = $dbc->fetch_row($result)) {
            $upcsWithLoc[] = $row['upc'];
        }
        
        $upcsMissingLoc = array();
        foreach ($plus as $upc) {
            if (!in_array($upc,$upcsWithLoc)) {
                $upcsMissingLoc[] = $upc;
            }
        }
        list($inClauseB,$bArgs) = $dbc->safeInClause($upcsMissingLoc);
        $bArgs[] = $store_location;
        $bString = '
         select
            p.upc,
            p.description as pdesc,
            p.department,
            pu.description as pudesc,
            p.brand,
            d.dept_name
            from products as p
                left join productUser as pu on pu.upc=p.upc
                left join departments as d on d.dept_no=p.department
            WHERE p.upc IN ('.$inClauseB.')
                AND p.store_id = ?
            order by p.department;
        ';
        $bQuery = $dbc->prepare($bString);
        $bResult = $dbc->execute($bQuery,$bArgs);
    
        $item = array();
        $result = $dbc->execute($query,$args);

        $results = array($result,$bResult);
        foreach ($results as $res) {
            while($row = $dbc->fetch_row($res)) {
                $item[$row['upc']]['upc'] = $row['upc'];
                $item[$row['upc']]['dept'] = $row['department'];
                $item[$row['upc']]['desc'] = $row['pdesc'];
                $item[$row['upc']]['brand'] = $row['brand'];
                $item[$row['upc']]['dept_name'] = $row['dept_name'];
                $item[$row['upc']]['curSections'] = $row['sections'];
            }
            if ($dbc->error()) {
                echo '<div class="alert alert-danger">' . $dbc->error() . '</div>';
            }
        }

        /*  Find suggestions for each item's location based on department.
         *    This needs to be updated now that there are multiple STORE_IDs being used
        */

        foreach ($item as $upc => $row) {
            $item[$upc]['sugDept'] = $this->getLocation($item[$upc]['dept'],$dbc);
        }

        $args = array($store_location);
        $query = $dbc->prepare('SELECT
                floorSectionID,
                name
            FROM FloorSections
            WHERE storeID = ?
            ORDER BY name;');
        $result = $dbc->execute($query,$args);
        $floor_section = array();
        while($row = $dbc->fetch_row($result)) {
            $floor_section[$row['floorSectionID']] = $row['name'];
        }
        if ($er = $dbc->error()) {
            echo '<div class="alert alert-danger">'.$er.'</div>';
        }


        $ret .= '<table class="table">
            <thead>
                <th>UPC</th>
                <th>Brand</th>
                <th>Description</th>
                <th>Dept. No.</th>
                <th>Department</th>
                <th>Current Location(s)</th>
                <th>
                    Location
                    <div class="input-group">
                        <span class="input-group-addon">Change All</span>
                            <select class="form-control input-sm" onchange="updateAll(this.value, \'.locationSelect\');">

                        ';
        foreach ($floor_section as $fs_key => $fs_value) {
            if ($fs_key == $item[$key]['sugDept']) {
                $ret .= '<option value="' . $fs_key . '" name="' . $key . '" selected>' . $fs_value . '</option>';
            } else {
                $ret .= '<option value="' . $fs_key . '" name="' . $key . '">' . $fs_value . '</option>';
            }
        }
        $ret .= '
                    </select></div>
                </th>
            </thead>
            <form method="post">
                <input type="hidden" name="save" value="1">
            ';
        foreach ($item as $key => $row) {
            $ret .= '
                <tr><td><a href="ItemEditorPage.php?searchupc=' . $key . '" target="">' . $key . '</a></td>
                <td>' . $row['brand'] . '</td>
                <td>' . $row['desc'] . '</td>
                <td>' . $row['dept'] . '</td>
                <td>' . $row['dept_name'] . '</td>
                <td>' . $row['curSections'] . '</td>
                <td><Span class="collapse"> </span>
                    <select class="locationSelect form-control input-sm" name="' . $key . '" value="" />
                        <option value="0">* no location selected *</option>';

                foreach ($floor_section as $fs_key => $fs_value) {
                    if ($fs_key == $item[$key]['sugDept']) {
                        $ret .= '<option value="' . $fs_key . '" name="' . $key . '" selected>' . $fs_value . '</option>';
                    } else {
                        $ret .= '<option value="' . $fs_key . '" name="' . $key . '">' . $fs_value . '</option>';
                    }
                }

                $ret .= '</select></tr>';
        }

        $ret .= '<tr><td><input type="submit" class="btn btn-default" value="Update Locations"></td>
            <td><a class="btn btn-default" href="ProdLocationEditor.php">Back</a><br><br></td></table>
            </form>';


        return $ret;

    }

    function get_batch_view()
    {
        $ret = "";
        $ret .= '
            <form method="get"class="form-inline">

            <div class="input-group" style="width:200px;">
                <span class="input-group-addon">Batch#</span>
                <input type="text" class="form-control inline" name="start" autofocus required>
            </div>
            <div class="input-group" style="width:200px;">
                <span class="input-group-addon">to Batch#</span>
                <input type="text" class="form-control inline" name="end" required><br>
            </div><br><br>

                <input type="hidden" name="store_id" value="1" required>

                <input type="submit" class="btn btn-default" value="Find item locations">
            </form><br>
            <a class="btn btn-default" href="ProdLocationEditor.php">Back</a><br><br>
        ';

        return $ret;
    }

    function get_searchupc_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $store_location = COREPOS\Fannie\API\lib\Store::getIdByIp();

        $args = array($store_location);
        $query = $dbc->prepare('SELECT
                    floorSectionID,
                    name
                FROM FloorSections
                WHERE storeID = ?
                ORDER BY name;');
            $result = $dbc->execute($query,$args);
            $floor_section = array();
            while($row = $dbc->fetch_row($result)) {
                $floor_section[$row['floorSectionID']] = $row['name'];
            }
            if (mysql_errno() > 0) {
                echo mysql_errno() . ": " . mysql_error(). "<br>";
            }
            $floor_section['none'] = 'none';

        $ret = '';
        $ret .= '<div class="container"><div class="row"><div class="col-md-5">';
        $ret .= '

            <form class="form-inline" method="get">
                <input type="hidden" name="store_id" class="form-control">
                <br><br>
                <div class="input-group">
                    <span class="input-group-addon">UPC</span>
                    <input type="text" class="form-control" id="upc" style="width: 175px" name="upc" autofocus required>&nbsp;&nbsp;
                    <input type="hidden" class="btn btn-default" style="width: 300px" name="searchupc" value="Update Locations by UPC">
                    <input type="submit" class="btn btn-default" value="Go" style="width: 50">
                </div>
            </form><br>
        ';

        if ($upc = FormLib::get('upc')) {
            $upc = str_pad($upc, 13, '0', STR_PAD_LEFT);
            $store_id = FormLib::get('store_id');
            $args = array($upc,$store_location);
            $prep = $dbc->prepare('
                SELECT
                    p.upc,
                    p.description,
                    f.floorSectionID,
                    p.department,
                    d.dept_name,
                    p.brand,
                    f.floorSectionProductMapID
                FROM products AS p
                    left join FloorSectionProductMap as f on f.upc=p.upc
                    left join FloorSections AS fs ON fs.floorSectionID=f.floorSectionID
                    left join departments as d on d.dept_no=p.department
                WHERE p.upc = ?
                    AND fs.storeID = ?
                GROUP BY floorSectionProductMapID
            ');
            $result = $dbc->execute($prep, $args);
            $curLocation = array();
            $numRows = $dbc->numRows($result);
            if ($numRows < 1) {
                $model = new ProductsModel($dbc);
                $model->upc($upc);
                $model->store_id($store_location);
                $model->load();
                $brand = $model->brand();
                $description = $model->description();
                $sugLocation = $this->getLocation($model->department(),$dbc);
            } else {
                while ($row = $dbc->fetch_row($result)) {
                    $floorID = $row['floorSectionID'];
                    $brand = $row['brand'];
                    $department = $row['department'];
                    $dept_name = $row['dept_name'];
                    $description = $row['description'];
                    if(isset($row['floorSectionID'])) $curLocation[] = $row['floorSectionID'];
                    $sugLocation = $this->getLocation($row['department'],$dbc);
                    $primaryKey[] = $row['floorSectionProductMapID'];
                }
            }

            $ret .= '<div class="panel panel-default" style="width: 435px; border: none;">';
                $ret .= '<table class="table table-striped">';
                $ret .= '<tr><td><b>UPC: </b></td><td>' . $upc . '</td></tr>';
                $ret .= '<tr><td><b>Brand / Description: </b></td><td>' . $brand . ' - ' . $description . '</td></tr>';
                $ret .= '<tr><td><b>Department: </b></td><td>' . $department . ' - ' . $dept_name . '</td></tr>';
                $ret .= '<tr><td><b>Suggested Location: </b></td><td>' . $floor_section[$sugLocation] . '</td></tr>';
                $ret .= '</table></div>';

                $ret .= '
                    <h4>Add a New Physical Location</h4>
                    <form method="post">
                    <div class="input-group">
                            <span class="input-group-addon">Location</span>
                            <select name="newLocation" class="form-control" style="width: 200px;">';
                        foreach ($floor_section as $fs_key => $fs_value) {
                            $ret .= '<option value="' . $fs_key . '" name="' . $fs_key . '">' . $fs_value . '</option>';
                        }
                        $ret .= '
                            </select>&nbsp;&nbsp;
                            <input type="submit" value="Add Location" class="btn btn-default">
                            </form></div>
                        ';

                $ret .= '<br><a class="btn btn-default" href="ProdLocationEditor.php">Back</a><br><br>';
                $ret .= '</div><div class="col-md-5">'; //end of column A

                $ret .= '
                    <br>
                    <h4>Edit Current Physical Locations</h4>
                    <form method="post">
                    <input type="hidden" name="save" value="1">
                    <input type="hidden" name="upc" value="' . $upc . '">
                ';
                $count = 0;
                if (count($curLocation) == 0) $ret .= '<div class="alert alert-danger"
                    style="width: 265px;">No locations have been set for this product.</div>';
                foreach ($curLocation as $value) {
                    $count++;
                    $name = 'section' . $primaryKey[$count-1];
                    //$name = 'mapid' . $primaryKey;
                    $oldName = 'mapID' . ($primaryKey[$count-1]);
                    $ret .= '<input type="hidden" name="' . $oldName . '" value="' . $value . '">';
                    $ret .= '
                        <div class="input-group">
                            <span class="input-group-addon">Loc#' . $count . '</span>
                            <select name="' . $name . '" class="form-control" style="width: 200px;">';
                        foreach ($floor_section as $fs_key => $fs_value) {

                            //echo $fs_key . ' :: ' . $fs_value . '<br>'; --keys and values are correct.

                            $ret .= '<option value="' . $fs_key . '" name="' . $fs_key . '"';
                            if ($value == $fs_key) {
                                $ret .= ' selected';
                            }
                            $ret .= '>' . $fs_value . '</option>';
                        }
                        $ret .= '</select></div><br>';

                        if ($count == 0) $ret .= '<span class="alert-warning">There is currently no location set for this product.</span>';

                }
            $ret .= '
                <input type="submit" class="btn btn-default" style="width: 265px;" value="Update Locations"><br><br>
                <input type="hidden" name="numolocations" value="' . $count . '">
                </form>
            ';


        }

        $ret .= '</div></div></div>'; //<column B><row><container>

        return $ret;
    }

    function get_view()
    {
        return '
            <div class="container pull-left">
            <form class="form-inline" method="get">
                <input type="submit" class="btn btn-default" style="width: 300px" name="searchupc" value="Single UPC Update"><br><br>
                <input type="submit" class="btn btn-default" style="width: 300px" name="list" value="List of UPCs Update"><br><br>
                <input type="submit" class="btn btn-default" style="width: 300px" name="batch" value="Locations by BATCH"><br><br>
            </form>
            </div>
        ';
    }

    private function arrayToOpts($arr, $selected=-999, $id_label=false)
    {
        $opts = '';
        foreach ($arr as $num => $name) {
            if ($id_label === true) {
                $name = $num . ' ' . $name;
            }
            $opts .= sprintf('<option %s value="%d">%s</option>',
                                ($num == $selected ? 'selected' : ''),
                                $num, $name);
        }

        return $opts;
    }

   public function javascript_content()
   {
       ob_start();
       ?>
function toggleAll(elem, selector) {
    if (elem.checked) {
        $(selector).prop('checked', true);
    } else {
        $(selector).prop('checked', false);
    }
}
function updateAll(val, selector) {
    $(selector).val(val);
}
       <?php
       return ob_get_clean();
   }

    public function helpContent()
    {
        return '<p>
            Edit the physical sales-floor location
            of products found in batches that fall within a
            specified range of batch IDs.
            <lu>
                <li><b>Update by UPC</b> View and update location(s) for individual items.</li>
                <li><b>Update by a List of UPCs</b> Paste a list of UPCs to view/update. Updating by
                    List will DELETE all current locations and replace them with the selected
                    location.</li>
                <li><b>Update by BATCH I.D.</b> Update products within a batch range. Update by Batch I.D.
                    will only check products that do not currently have a location assigned.</li>
            </lu>
            </p>
            ';
    }

    public function getLocation($dept,$dbc)
    {
        $store = COREPOS\Fannie\API\lib\Store::getIdByIp();

        $args = array($dept,$store);
        $prep = $dbc->prepare("
            SELECT f.floorSectionID
            FROM FloorSectionProductMap AS f
            LEFT JOIN products AS p ON f.upc=p.upc
            LEFT JOIN departments AS d ON p.department=d.dept_no
            RIGHT JOIN FloorSections AS s ON f.floorSectionID=s.floorSectionID
            WHERE p.department = ?
                AND s.storeID = ?;
        ");
        $res = $dbc->execute($prep,$args);
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            if (!array_key_exists($row['floorSectionID'],$data)) {
                $data[$row['floorSectionID']] = 0;
            } else {
                $data[$row['floorSectionID']]++;
            }
        }
        if (empty($data)) {
            return 'none';
        } else {
            $maxs = array_keys($data, max($data));
            return $maxs[0];
        }

    }

}

FannieDispatch::conditionalExec();

