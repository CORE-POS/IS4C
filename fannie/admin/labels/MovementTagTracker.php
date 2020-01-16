<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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
require(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class MovementTagTracker extends FannieRESTfulPage
{

    protected $header = 'Movement Shelf-Tag Tracker';
    protected $title = 'Movement Shelf-Tag Tracker';
    public $description = '[Movement Shelf-Tag Tracker] Find tags to reprint based
        on change in sales volume.';
    private $item = array();
    protected $must_authenticate = true;
    protected $stores = array(1=>'Hillside', 2=>'Denfeld');

    public function preprocess()
    {
        $this->__routes[] = "get<addRange>";
        $this->__routes[] = "get<delete>";
        $this->__routes[] = "get<exclusionName>";
        $this->__routes[] = "post<upcs>";
        $this->__routes[] = "get<data>";
        $this->__routes[] = "get<expired>";

        return parent::preprocess();
    }

    public function get_expired_view()
    {
        $li = "<ul>";
        $li .= "<li><a href=\"MovementTagTracker.php?data=view\">Data</a></li>";
        $li .= "<li><a href='?id=config'>Settings</a></li>";
        $li .= "<li><a href='ShelfTagIndex.php'>Shelftags Index</a></li>";
        $li .= "<li><a href='MovementTagTracker.php'>Tracker</a></li>";
        $li .= "</ul>";

        return <<<HTML
$li
{$this->draw_expired_table()}
HTML;
    }

    public function get_data_view()
    {
        $dbc = Fanniedb::get($this->config->get('op_db'));
        $storeID = FormLib::get('store');

        $args = array($storeID);
        $td = '';
        $th = '';
        $cols = array('count', 'upc', 'brand', 'description', 'dept', 'storeID',
            'auto_par', 'adjustment', 'modified', 'loc');
        foreach ($cols as $col) {
            $th.= "<th>$col</th>";
        }
        $prep = $dbc->prepare("SELECT m.*, DATE(m.modified) as modified, m.updateID as count,
                p.brand, p.description,
                CONCAT(d.dept_no, ' ', d.dept_name) AS dept,
                f.name AS loc
            FROM MovementUpdate AS m
                LEFT JOIN products AS p ON m.upc=p.upc
                LEFT JOIN departments AS d ON p.department=d.dept_no
                LEFT JOIN FloorSectionProductMap AS fspm ON m.upc=fspm.upc
                LEFT JOIN FloorSections AS f ON fspm.floorSectionID=f.floorSectionID
            GROUP BY m.upc, DATE(m.modified)
            ORDER BY m.updateID DESC
            ");
        $res = $dbc->execute($prep, $args);
        while ($row = $dbc->fetchRow($res)) {
            $td .= "<tr>";
            foreach ($cols as $col) {
                $td .= "<td data-column=\"$col\">{$row[$col]}</td>";
            }
            $td .= "</tr>";
        }

        $stores = FormLib::storePicker();
        $this->addOnloadCommand("$('#datepicker1').datepicker({dateFormat: 'yy-mm-dd'});");
        $this->addOnloadCommand("$('#datepicker2').datepicker({dateFormat: 'yy-mm-dd'});");
        $this->addScript("movementTag.js");
        $this->addOnloadcommand("movementTableFilter.filter_table();");
        $this->addScript('../../src/javascript/tablesorter-2.22.1/js/jquery.tablesorter.js');
        $this->addScript('../../src/javascript/tablesorter-2.22.1/js/jquery.tablesorter.widgets.js');
        $this->addOnloadCommand("$('#mu-table').tablesorter({theme:'bootstrap', headerTemplate: '{content} {icon}', widgets: ['uitheme','zebra']});");

        return <<<HTML
<h2>Shelftag Replacement History</h2>
<div class="row">
    <ul><li><a href="ShelfTagIndex.php">Shelftag Index</a></li></ul>
    <div class="col-lg-2"><div class="form-group">{$stores['html']}</div></div>
    <div class="col-lg-2"><div class="form-group">
        <input class="form-control" id="datepicker1" data-var="from"  type="text" placeholder="From"/>
    </div></div>
    <div class="col-lg-2"><div class="form-group">
        <input class="form-control" id="datepicker2" data-var="to"  type="text" placeholder="To"/>
    </div></div>
    <div class="col-lg-2"><div class="form-group">
        <input class="form-control" id="upc-filter" data-var="upc" type="text" placeholder="UPC"/>
    </div></div>
    <div class="col-lg-2"><div class="form-group">
        <input class="form-control" id="brand-filter" data-var="brand"  type="text" placeholder="Brand"/>
    </div></div>
    <div class="col-lg-2"><div class="form-group">
        <input class="form-control" id="desc-filter" data-var="desc"  type="text" placeholder="Description"/>
    </div></div>
</div>
<div class="row">
    <div class="col-lg-2"><div class="form-group">
        <button class="btn btn-default" style="width: 45%">Submit</button>
        <button class="btn btn-default" style="width: 45%" onclick="
            $('input').each(function(){ $(this).val(''); $(this).trigger('change') });
            $('#dept-filter').val($('#dept-filter option:first').val());
            $('#dept-filter').trigger('change');
            $('#loc-filter').val($('#loc-filter option:first').val());
            $('#loc-filter').trigger('change');
            "
        >Clear</button>
    </div></div>
    <div class="col-lg-2"><div class="form-group">
        <select class="form-control" name="dept-filter" id="dept-filter" data-var="dept">
        </select>
    </div></div>
    <div class="col-lg-2"><div class="form-group">
        <select class="form-control" name="loc-filter" id="loc-filter" data-var="loc">
        </select>
    </div></div>
</div>
<table id="mu-table" class="table table-bordered table-sm small"><thead>$th</thead><tbody>$td</tbody></table>
HTML;
    }

    public function post_upcs_handler()
    {
        $dbc = fanniedb::get($this->config->get('op_db'));
        $upcs = FormLib::get('upcs');
        $adjustments = FormLib::get('adjustments');
        $auto_pars = FormLib::get('auto_par');
        $storeID = FormLib::get('storeID');
        $ret = '';
        $today = new DateTime();

        $args = array($storeID);
        $prep = $dbc->prepare("SELECT DATE(MAX(modified)) FROM MovementUpdate
            WHERE storeID = ?");
        if ($dbc->getValue($prep, $args) == $today->format('Y-m-d')) {
        } else {
            foreach ($upcs as $k => $upc) {
                $ret .= "<div>$upc, {$adjustments[$k]}</div>";
                $args = array($upc, $storeID, $adjustments[$k], $auto_pars[$k]);
                $prep = $dbc->prepare("INSERT INTO MovementUpdate
                    (upc, storeID, adjustment, auto_par, modified) VALUES (?, ?, ?, ?, NOW())");
                $res = $dbc->execute($prep, $args);
                if ($er = $dbc->error()) $ret .= $er;
            }
            $valA = array($storeID);
            $valP = $dbc->prepare("SELECT * FROM MovementUpdate
                WHERE storeID = ? AND DATE(modified) = DATE(NOW());");
            $valR = $dbc->execute($valP, $valA);
            $valCount = $dbc->numRows($valR);
            if ($valCount == count($upcs)) {
                return header('location: MovementTagTracker.php?status=success');
            } else {
                return header('location: MovementTagTracker.php?status=fail&err='.$ret);
            }
        }

        return header('location: MovementTagTracker.php?status=uptodate');

    }

    public function get_exclusionName_handler()
    {
        $dbc = fanniedb::get($this->config->get('op_db'));
        $name = FormLib::get('exclusionName');
        $dept = FormLib::get('addDepartment');
        $brand = FormLib::get('addBrand');
        $upc = FormLib::get('addProduct');
        $upc = BarcodeLib::padUPC($upc);
        $args = array();
        switch ($name) {
            case 'Product':
                $args[] = $name;
                $args[] = $upc;
                break;
            case 'Dept':
                $args[] = $name;
                $args[] = $dept;
                break;
            case 'Brand':
                $args[] = $name;
                $args[] = $brand;
                break;
        }
        $prep = $dbc->prepare("
            INSERT INTO MovementTrackerParams (parameter, value) VALUES (?, ?)");
        $res = $dbc->execute($prep, $args);

        $alerts = "";
        if ($er = $dbc->error()) {
            $alerts .= "<div class='alert alert-danger'>$er</div>";
            return header('location: ?id=config&save=danger');
        } else {
            return header('location: ?id=config&save=success');

        }
    }

    public function get_delete_handler()
    {
        $id = FormLib::get('delete');
        $uid = FormLib::get('uid');
        $dbc = fanniedb::get($this->config->get('op_db'));

        $args = array($id);
        $idType = ($uid > 0) ? 'id' : 'parID';
        $query = "DELETE FROM MovementTrackerParams WHERE $idType = ?";
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $args);
        $alerts = "";
        if ($er = $dbc->error()) {
            $alerts .= "<div class='alert alert-danger'>$er</div>";
            return header('location: ?id=config?save=danger');
        } else {
            return header('location: ?id=config&save=success');

        }
    }

    public function get_addRange_handler()
    {
        $start = FormLib::get('start');
        $end = FormLib::get('end');
        $change = FormLib::get('change');
        $store = FormLib::get('store');

        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $prep = $dbc->prepare("SELECT MAX(parID) AS parID FROM MovementTrackerParams");
        $res = $dbc->execute($prep);
        $parID = $dbc->fetchRow($res);
        $parID = $parID['parID'] + 1;

        $mArgs = array(
            array($parID, 'start', $start),
            array($parID, 'end', $end),
            array($parID, 'change', $change),
            array($parID, 'type', 'range'),
            array($parID, 'store', $store),
        );
        $query = "INSERT INTO MovementTrackerParams (parID, parameter, value) VALUES(?, ?, ?);";
        $dbc->startTransaction();
        foreach ($mArgs as $args) {
            $prep = $dbc->prepare($query);
            $res = $dbc->execute($prep, $args);
        }
        $dbc->commitTransaction();
        $alerts = "";
        if ($er = $dbc->error()) {
            $alerts .= "<div class='alert alert-danger'>$er</div>";
            return header('location: ?id=config?save=danger');
        } else {
            return header('location: ?id=config&save=success');

        }

        return false;
    }

    private function draw_expired_table()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $storeID = COREPOS\Fannie\API\lib\Store::getIdByIp();
        $date = new DateTime();
        $date->sub(new DateInterval('P1Y'));

        $args = array($storeID, $date->format('Y-m-d'), $storeID);
        $prep = $dbc->prepare("SELECT p.upc, DATE(m.modified) AS modified, p.brand, p.description, f.name
            FROM MovementTags AS m
                LEFT JOIN products AS p ON p.upc=m.upc
                    AND p.store_id=m.storeID
                LEFT JOIN MasterSuperDepts AS s ON p.department=s.dept_ID
                LEFT JOIN FloorSectionProductMap AS fs ON fs.upc=p.upc
                LEFT JOIN FloorSections AS f ON f.storeID=p.store_id
                    AND f.floorSectionID=fs.floorSectionID
            WHERE m.storeID = ?
                AND m.modified < ?
                " . ($this->config->get('COOP_ID') == 'WFC_Duluth' ? 
                    "AND m.upc NOT IN (SELECT upc FROM woodshed_no_replicate.doNotTrack WHERE method = 'getMissingMovementTags')"
                    : '') . "
                AND s.superID NOT IN (0, 1, 3, 6)
                AND p.store_id = ?
                AND p.inUse = 1
                AND f.name IS NOT NULL
                AND p.upc NOT IN (SELECT value FROM MovementTrackerParams WHERE parameter = 'Product')
                AND p.numflag & (1 << 19) = 0
                AND not numflag & (1 << 1)
            GROUP BY p.upc, DATE(m.modified), p.brand, p.description, f.name
            ORDER BY f.name
        ");
        $cols = array('upc', 'brand', 'description', 'modified', 'name');
        $res = $dbc->execute($prep, $args);
        $table = '<h2 id="expired-heading">Expired Tags for '.$this->stores[$storeID].'</h2>
            <table class="table table-bordered table-striped table-condensed">
            <thead><th>upc</th><th>brand</th><th>description</th><th>last replaced</th>
            <th>location</th></thead><tbody>';
        while ($row = $dbc->fetchRow($res)) {
            $table .= "<tr>";
            foreach ($cols as $col) {
                $table .= "<td>{$row[$col]}</td>";
            }
            $table .= "</tr>";
        }
        $table .= "</tbody></table>";

        return $table;
    }

    public function get_view()
    {
        $storeID = COREPOS\Fannie\API\lib\Store::getIdByIp();
        $ret = "";

        $li = "<ul>";
        $tables = array();
        $tables[] = $this->draw_table($this->stores[$storeID], $storeID);
        $li .= "<li><a href='?expired=1'>Expired Tags</a></li>";
        $li .= "<li><a href='ShelfTagIndex.php'>Shelftags Index</a></li>";
        $li .= "<li><a href='?id=config'>Settings</a></li>";
        $li .= "<li><a href=\"MovementTagTracker.php?data=view\">Data</a></div></li>";
        $li .= "</ul>";
        foreach ($tables as $table) {
            $ret .= $table;
        }
        $status = FormLib::get('status');
        $alert = '';
        if ($status == 'success') {
            $alert = "<div class=\"alert alert-success\">Movement has been recorded</div>";
        } elseif ($status == 'fail') {
            $alert = "<div class=\"alert alert-danger\">Something went wrong, movement was not recorded</div>";
        } elseif ($status == 'uptodate') {
            $alert = "<div class=\"alert alert-warning\">Movement for the selected store is already up to date</div>";
        }
        $this->addScript('../../src/javascript/tablesorter-2.22.1/js/jquery.tablesorter.js');
        $this->addScript('../../src/javascript/tablesorter-2.22.1/js/jquery.tablesorter.widgets.js');
        $this->addOnloadCommand("$('#my-table-1').tablesorter({theme:'bootstrap', headerTemplate: '{content} {icon}', widgets: ['uitheme','zebra']});");
        $this->addOnloadCommand("$('#my-table-2').tablesorter({theme:'bootstrap', headerTemplate: '{content} {icon}', widgets: ['uitheme','zebra']});");

        return "<div align=\"center\">$alert</div>" . $li . $ret . <<<HTML
HTML;
    }

    public function get_id_view()
    {
        $storepicker = FormLib::storePicker();
        $save = FormLib::get('save');
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $params = array();
        $prep = $dbc->prepare("SELECT * FROM MovementTrackerParams");
        $res = $dbc->execute($prep);
        while ($row = $dbc->fetchRow($res)) {
            $id = $row['parID'];
            $param = $row['parameter'];
            $params[$id][$param] = $row['value'];
        }
        $keyTable = "<h4>Key</h4>
            <table class='table table-condensed table-bordered'>
            <thead><th>Column</th><th>Description</th><tbody>
            <tr><td>Start</td><td>Check items with pars within a range starting from this number</td></tr>
            <tr><td>End</td><td>Check items with pars within range ending at this number</td></tr>
            <tr><td>Change At</td><td>Reprint shelftag if par deviates from the last printed par by this amount</td></tr></tbody></table>
            ";
        $table = "<h4>List of Active Ranges</h4>
            <table class='table table-condensed table-bordered'>
            <thead><th>Store</th><th>Start</th><th>End</th><th>Change At</th>
            <th><span class='glyphicon glyphicon-trash'></span></th><tbody>";
        foreach ($params as $id => $row) {
            $table .= "<tr>";
            if (isset($params[$id]['type']) && $params[$id]['type'] == 'range') {
                $storeName = $storepicker['names'][$row['store']];
                $param = key($params[$id]);
                $table .= "<td>$storeName</td>";
                $table .= "<td data-pid='$id' data-param='$param' class='editable'>{$row['start']}</td>";
                $table .= "<td>{$row['end']}</td>";
                $table .= "<td>{$row['change']}</td>";
                $table .= "<td style='width: 20px'>
                    <a href='?delete=$id' class='btn btn-danger btn-sm glyphicon glyphicon-trash'></a></td>";
            }
            $table .= "</tr>";
        }
        $table .= "</tbody></table>";

        $alert = "";
        if ($save == "success") {
            $alert = "<div class='alert alert-success'>Saved!</div>";
        } elseif ($alert == "danger") {
            $alert = "<div class='alert alert-danger'>Changes could not be saved</div>";
        }

        $deptP = $dbc->prepare("SELECT dept_no, dept_name FROM departments ORDER BY dept_no");
        $deptR = $dbc->execute($deptP);
        $depts = "";
        $departments = array();
        while ($row = $dbc->fetchRow($deptR)) {
            $no = $row['dept_no'];
            $name = $row['dept_name'];
            $depts .= "<option value=$no>$name [$no]</option>";
            $departments[$no] = $name;
        }

        $brandP = $dbc->prepare("SELECT brand FROM products GROUP BY brand");
        $brandR = $dbc->execute($brandP);
        $brands = "";
        while ($row = $dbc->fetchRow($brandR)) {
            $brand = $row['brand'];
            $brands .= "<option val='$brand'>$brand</option>";
        }

        $tableB = "<h4>Products, Brands & Departments<br/> to Exclude</h4>";
        $tableB .= "
                <form name='addProductForm'>
                <input type='hidden' name='exclusionName' value='Product'/>
                <ul class='list-group'>
                    <li  class='list-group-item' >Exclude <strong>Product</strong></li>
                    <ul class='list-group'>
                        <li  class='list-group-item' class='collapse' id='addProductForm'>
                            <div class='form-group'>
                                <input type='text' class='form-control' name='addProduct' placeholder='Enter UPC'/>
                            </div>
                            <div class='form-group'>
                                <button type='submit' class='form-control btn btn-default'>Submit Product</button>
                            </div>
                        </li>
                    </ul>
                </ul>
                </form>
                <form name='addBrandForm'>
                <input type='hidden' name='exclusionName' value='Brand'/>
                <ul class='list-group'>
                    <li  class='list-group-item' >Exclude <strong>Brand</strong></li>
                    <ul class='list-group'>
                        <li  class='list-group-item' class='collapse' id='addBrandForm'>
                            <div class='form-group'>
                                <select class='form-control' name='addBrand'>
                                    <option value='null'> </option>
                                    $brands
                                </select>
                            </div>
                            <div class='form-group'>
                                <button type='submit' class='form-control btn btn-default'>Submit Brand</button>
                            </div>
                        </li>
                    </ul>
                </ul>
                </form>
                <ul class='list-group'>
                <form name='addDeptForm'>
                    <input type='hidden' name='exclusionName' value='Dept'/>
                    <li  class='list-group-item' >Exclude <strong>Department</strong></li>
                    <ul class='list-group'>
                        <li  class='list-group-item' class='collapse' id='addDepartmentForm'>
                            <div class='form-group'>
                                <select class='form-control' name='addDepartment'>
                                    <option value='null'> </option>
                                    $depts
                                </select>
                            </div>
                            <div class='form-group'>
                                <button type='submit' class='form-control btn btn-default'>Submit Dept.</button>
                            </div>
                        </li>
                    </ul>
                </ul>
                </form>
        ";

        $tableC = "
            <div align='center'>
                <ul class='list-group'>
                    <li class='list-group-item'>Back to <a href='ShelfTagIndex.php'>Shelftags Index</a></li>
                    <li class='list-group-item'>Back to <a href='?page=main' >Tracker</a></li>
                </ul>
            </div>
            <h4>List of Excluded items</h4>
            <div>
            <table class='table table-small table-bordered'>
            <thead><th>Exclusion Type</th><th>Value</th><th>
            <span class='glyphicon glyphicon-trash'></span></th></thead><tbody>";
        $exclP = $dbc->prepare("SELECT * FROM MovementTrackerParams
            WHERE parameter IN ('Product', 'Brand', 'Dept') ORDER BY parameter, value;");
        $exclR = $dbc->execute($exclP);
        while ($row = $dbc->fetchRow($exclR)) {
            $v = $row['value'];
            $p = $row['parameter'];
            $id = $row['id'];
            if ($p == 'Dept') {
                $v = $v .  " - " . $departments[$v];
            }
            $tableC .= "<tr><td>$p</td><td>{$v}</td>
                <td><a href='?delete=$id&uid=$id' class='btn btn-danger btn-sm glyphicon glyphicon-trash'></a>
                </td></tr>";
        }
        $tableC .= "</tbody></table></div>";

        return <<<HTML
<div class="row">
    <div class="col-md-12">$alert</div>
    <div class="col-md-5">
        $tableC
    </div>
    <div class="col-md-2 middle-col">
        $tableB
    </div>
    <div class="col-md-5">
        <ul class='list-group'>
            <li class='list-group-item'><a href='#' data-target='#rangeForm' data-toggle='collapse'>Add New Range</a></li>
        </ul>
        <form>
            <div id="rangeForm" class="collapse">
                <table class="table table-condensed table-bordered"><thead>
                    <th>Store</th><th>Start</th><th>End</th><th>Change At</th></thead><tbody>
                <td>{$storepicker['html']}</td>
                <td><input class="form-control" type="text" name="start" id="start"/></td>
                <td><input class="form-control" type="text" name="end" id="end"/></td>
                <td><input class="form-control" type="text" name="change" id="change"/></td>
                <td><input class="btn btn-default" type="submit" name="submit" id="submit" value="Save"/></td>
                <input type="hidden" name="addRange" value="config"/>
                </tbody></table>
            </div>
        </form>
        $keyTable
        $table
    </div>
</div>
HTML;
    }

    private function get_replacetags($storeID, $volMin, $volMax, $posLimit, $negLimit)
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $date = new DateTime();
        $date->sub(new DateInterval('P1M'));
        $prevMonth = $date->format('Y-m-d 00:00:00') . '<br/>';
        $args = array($storeID, $volMin, $volMax, $posLimit, $negLimit, $prevMonth);
        $var = ($storeID == 1) ? 3 : 7;
        $prep = $dbc->prepare("SELECT
            p.upc,
            brand,
            description,
            ROUND(p.auto_par, 3) * $var AS auto_par,
            ROUND(m.lastPar, 3) AS lastPar,
            ROUND((p.auto_par * $var - m.lastPar), 3) AS diff,
            CONCAT(p.department, ' - ', d.dept_name) AS department,
            f.name
        FROM products AS p
            LEFT JOIN MovementTags AS m ON p.upc=m.upc AND p.store_id = m.storeID
            LEFT JOIN FloorSectionProductMap AS fs ON fs.upc=p.upc
            LEFT JOIN FloorSections AS f ON f.storeID=p.store_id
                AND f.floorSectionID=fs.floorSectionID
            INNER JOIN MasterSuperDepts AS mast ON p.department = mast.dept_ID
            INNER JOIN departments AS d ON p.department=d.dept_no
        WHERE p.store_id = ?
            AND inUse = 1
            AND mast.superID NOT IN (0, 1, 3, 6, $var)
            AND m.lastPar BETWEEN ? AND ?
            AND ((p.auto_par * $var - m.lastPar) > ?
                OR (p.auto_par * $var - m.lastPar) < ?
            )
            AND f.name IS NOT NULL
            AND p.auto_par <> 0
            AND p.numflag & (1 << 19) = 0
            AND not numflag & (1 << 1)
            AND p.created < ?
            AND p.upc NOT IN (SELECT value FROM MovementTrackerParams WHERE parameter = 'Product')
            AND p.department NOT IN (SELECT value FROM MovementTrackerParams WHERE parameter = 'Dept')
            AND p.brand NOT IN (SELECT value FROM MovementTrackerParams WHERE parameter = 'Brand')
        ;");
        $res = $dbc->execute($prep, $args);
        if ($er = $dbc->error())
            return "<div class='alert alert-danger'>$er</div>";
        while ($row = $dbc->fetchRow($res)) {
            $this->item[] = $row;
        }

        return false;
    }

    private function draw_table($storeName, $storeID)
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $params = array();
        $prep = $dbc->prepare("SELECT * FROM MovementTrackerParams");
        $res = $dbc->execute($prep);
        while ($row = $dbc->fetchRow($res)) {
            $id = $row['parID'];
            $param = $row['parameter'];
            $params[$id][$param] = $row['value'];
        }
        foreach ($params as $id => $row) {
            if (isset($params[$id]['type']) && $params[$id]['type'] == 'range'
            && $params[$id]['store'] == $storeID) {
                $start = $row['start'];
                $end = $row['end'];
                $change = $row['change'];
                $this->get_replacetags($storeID, $start, $end, $change, -$change);
            }
        }

        $table = "";
        $thead = '';
        $colNames = array('upc', 'brand', 'description', 'department', 'lastPar', 'auto_par', 'diff', 'name');
        foreach ($colNames as $colName)
            $thead .= "<th>$colName</th>";
        $table .= "<h2 id='$storeName'>$storeName Tags</h2>
            <table class='table table-bordered table-condensed table-striped tablesorter tablesorter-bootstrap myTables' id='my-table-$storeID'><thead >$thead</thead><tbody>";
        if (isset($this->item) && is_array($this->item)) {
            foreach ($this->item as $row => $array) {
                $table .= "<tr>";
                foreach ($colNames as $colName) {
                    if ($colName == 'upc') {
                        $table .= "<td><a href='../../item/ItemEditorPage.php?searchupc={$array[$colName]}'
                            target='_blank'>{$array[$colName]}</a></td>";
                    } else {
                        $table .= "<td>{$array[$colName]}</td>";
                    }
                }
                $table .= "</tr>";
            }
        }
        $table .= "</tbody></table>";

        $form = '<form method="post"><input type="hidden" name="storeID" value="'.$storeID.'"/>';
        if (isset($this->item) && is_array($this->item)) {
            foreach ($this->item as $k => $row) {
                $form .= sprintf("<input type=\"hidden\" name=\"upcs[]\" value=\"%s\" />
                    <input type=\"hidden\" name=\"adjustments[]\" value=\"%f\" />
                    <input type=\"hidden\" name=\"auto_par[]\" value=\"%f\" />
                    ",
                    $row['upc'],
                    $row['diff'],
                    $row['auto_par']
                );
            }
        }

        $authorized = false;
        if (FannieAuth::validateUserQuiet('admin')) {
            $authorized = true;
        }
        $form .= "<div class=\"form-group\" align=\"right\">
            <li><button type=\"submit\" class=\"submitData btn btn-info btn-xs\">Submit $storeName Data</button></form></li>
            </div>";
        if ($authorized === true) {
            $table = $form . $table;
        }

        if (isset($this->item) && count($this->item) > 0) {
            unset($this->item);
            return $table;
        } else {
            unset($this->item);
            return "<h2>$storeName</h2><div class='well'>No shelf-tags to print at this time.</div>";
        }
    }

    public function css_content()
    {
        return <<<HTML
.middle-col {
    background: #fafafa;
    background: linear-gradient(#F2F2F2, #FAFAFA);
    border-radius: 5px;
}
HTML;
    }

    public function javascript_content()
    {
        return <<<JAVASCRIPT
$('.glyphicon').click(function()
{
    var c = confirm("Delete row?");
    if (c == true) {
        return true;
    } else {
        return false;
    }
});
$('.submitData').click(function()
{
    var c = confirm("Submit movement adjustments to database?");
    if (c == true) {
        return true;
    } else {
        return false;
    }
});
JAVASCRIPT;
    }

    public function helpContent()
    {
        return <<<HTML
<ul>
    <li><strong>auto_par</strong> is the average par of the product
        at the time the tag is updated.</li>
    <li><strong>adjustment</strong> is the change in par-average made to the
        tag that is on the sales floor and is being replaced.
        A positive adjustment denotes that sales have increased, while negative
        means the opposite.<br/><u>Example</u>
        <table class="table table-bordered">
            <tr><td>upc</th><th>description</th><th>auto_par</th><th>adjustment</th>
            <tr><td>0004114849148</td><td>LIBERT  YOGURT,OG2,WM,VANILLA BN</td><td>4.56</td><td>4.56</td>
        </table>
        <p>At the time of re-printing this tag, the average par of the product was 4.56.
            The adjustment from the tag on the shelf was 4.56, meaning the tag on the shelf
            did not yet have a par on it (this was likely a new or returning item).
    </li>
HTML;
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
    }
}

FannieDispatch::conditionalExec();

