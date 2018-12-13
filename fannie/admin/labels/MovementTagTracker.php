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

    public function preprocess()
    {
        $this->__routes[] = "get<addRange>";
        $this->__routes[] = "get<delete>";
        
        return parent::preprocess();
    }

    public function get_delete_handler()
    {
        $id = FormLib::get('delete');
        $dbc = FannieDB::get($this->config->get('OP_DB'));

        $args = array($id);
        $query = "DELETE FROM MovementTrackerParams WHERE parID = ?";
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
        // change to handler, send back to get_id_view() 
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

    public function get_view()
    {
        $ret = "";
        $li = "<ul>";
        $tables = array();
        $stores = array(1=>'Hillside', 2=>'Denfeld');
        $li .= "<li><a href='ShelfTagIndex.php'>Back to Shelftags Index</a></li>";
        foreach ($stores as $id => $name) {
            $tables[] = $this->draw_table($name, $id);
            $li .= "<li><a href='#$name'>$name</a></li>";
        }
        $li .= "<li><a href='?id=config'>Settings</a></li>";
        $li .= "</ul>";
        foreach ($tables as $table) {
            $ret .= $table;
        }
        $this->addScript('../../src/javascript/tablesorter-2.22.1/js/jquery.tablesorter.js');
        $this->addScript('../../src/javascript/tablesorter-2.22.1/js/jquery.tablesorter.widgets.js');
        $this->addOnloadCommand("$('#my-table-1').tablesorter({theme:'bootstrap', headerTemplate: '{content} {icon}', widgets: ['uitheme','zebra']});");
        $this->addOnloadCommand("$('#my-table-2').tablesorter({theme:'bootstrap', headerTemplate: '{content} {icon}', widgets: ['uitheme','zebra']});");

        return $li . $ret;
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
            <thead><th>Store</th><th>Start</th><th>End</th><th>Change At</th><tbody>";
        foreach ($params as $id => $row) {
            $table .= "<tr>";
            if ($params[$id]['type'] == 'range') {
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
            $alert = "<div class='alert alert-success'>Saved</div>";
        } elseif ($alert == "danger") {
            $alert = "<div class='alert alert-danger'>Changes could not be saved</div>";
        }


        return <<<HTML
<div class="row">
    <div class="col-md-6">
        $alert
        <form>
            <div class="form-group">
                <ul>
                    <li><a href='ShelfTagIndex.php'>Back to Shelftags Index</a></li>
                    <li><a href="?page=main" >Back to Tracker</a></li>
                    <li><a href="#" data-target="#rangeForm" data-toggle="collapse">Add New Range</a></li>
                </ul>
            </div>
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
    <div class="col-md-6">
        <!-- future idea: add utility to exclude brands, individual items, etc -->
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
            if ($params[$id]['type'] == 'range' 
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
        foreach ($this->item as $row => $array) {
            $table .= "<tr>";
            foreach ($colNames as $colName) {
                if ($colName == 'upc') {
                    $table .= "<td>
                        <a href='../../item/ItemEditorPage.php?searchupc={$array[$colName]}' 
                        target='_blank'>{$array[$colName]}</a></td>";
                } else {
                    $table .= "<td>{$array[$colName]}</td>";
                }
            }
            $table .= "</tr>";
        }
        $table .= "</tbody></table>";

        if (count($this->item) > 0) {
            unset($this->item);
            return $table;
        } else {
            unset($this->item);
            return "<h2>$storeName</h2><div class='well'>No shelf-tags to print at this time.</div>";
        }
    }

    public function helpContent()
    {
        return <<<HTML
<p>This page lists products that should have
new movement tags printed for each store.</p>
HTML;
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
    }
}

FannieDispatch::conditionalExec();

