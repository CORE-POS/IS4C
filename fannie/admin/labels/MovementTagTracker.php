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

    public function get_view()
    {
        $ret = "";
        $li = "<ul>";
        $tables = array();
        $stores = array(1=>'Hillside', 2=>'Denfeld');
        foreach ($stores as $id => $name) {
            $tables[] = $this->draw_table($name, $id);
            $li .= "<li><a href='#$name'>$name</a></li>";
        }
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

    private function get_replacetags($storeID, $volMin, $volMax, $posLimit, $negLimit)
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $args = array($storeID, $volMin, $volMax, $posLimit, $negLimit);
        $var = ($storeID == 1) ? 3 : 7;
        $prep = $dbc->prepare("SELECT
            p.upc,
            brand,
            description,
            ROUND(p.auto_par, 3) * $var AS auto_par,
            ROUND(m.lastPar, 3) AS lastPar,
            ROUND((p.auto_par * $var - m.lastPar), 3) AS diff,
            CONCAT(p.department, ' - ', d.dept_name) AS department
        FROM products AS p
            LEFT JOIN MovementTags AS m ON p.upc=m.upc AND p.store_id = m.storeID
            INNER JOIN MasterSuperDepts AS mast ON p.department = mast.dept_ID
            INNER JOIN departments AS d ON p.department=d.dept_no
        WHERE p.store_id = ?
            AND inUse = 1
            AND mast.superID NOT IN (0, 1, 3, 6, $var)
            AND m.lastPar BETWEEN ? AND ?
            AND ((p.auto_par * $var - m.lastPar) > ?
                OR (p.auto_par * $var - m.lastPar) < ?
            )
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
        $this->get_replacetags($storeID, 0, 2.99, 1, -1);
        $this->get_replacetags($storeID, 3, 4.99, 2, -2);
        $this->get_replacetags($storeID, 4.99, 999.99, 3, -3);

        $table = "";
        $thead = '';
        $colNames = array('upc', 'brand', 'description', 'department', 'lastPar', 'auto_par', 'diff');
        foreach ($colNames as $colName)
            $thead .= "<th>$colName</th>";
        $table .= "<h2 id='$storeName'>$storeName Tags</h2>
            <table class='table table-bordered table-condensed table-striped tablesorter tablesorter-bootstrap myTables' id='my-table-$storeID'><thead >$thead</thead><tbody>";
        foreach ($this->item as $row => $array) {
            $table .= "<tr>";
            foreach ($colNames as $colName) {
                $table .= "<td>{$array[$colName]}</td>";
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

