<?php
/*******************************************************************************

    Copyright 2022 Whole Foods Community Co-op

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

use COREPOS\Fannie\API\lib\FannieUI;

require(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../classlib2.0/FannieAPI.php');
}

class MultiMerchEditor extends FannieRESTfulPage
{
    protected $header = 'Multi Merch Editor Page';
    protected $title = 'Multi Merch Editor';
    protected $sortable = true;

    public $description = '[Multi Merch Editor Page] Review products located in
        more than one physical location.';
    public $has_unit_tests = true;

    function preprocess()
    {
        return parent::preprocess();
    }

    public function getView()
    {

        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $storeID = COREPOS\Fannie\API\lib\Store::getIdByIp();
        if ($storeID == false) 
            $storeID = 2;
        $storeName = ($storeID == 1) ? 'Hillside' : 'Denfeld';
        $td = "";
        $edit = FannieUI::editIcon();
        $tcount = 0;

        $sDef = $dbc->tableDefinition('SignProperties');
        $sTable = (isset($sDef['signCount'])) ? 'SignProperties' : 'productUser';
        $sAddOn = (isset($sDef['signCount'])) ? ' AND u.storeID = p.store_id' : '';

        $args = array($storeID);
        $prep = $dbc->prepare("SELECT p.upc, p.brand, p.description, v.sections, u.signCount, v.storeID,
            DATE(p.last_sold) AS lastSold 
            FROM products AS p
                LEFT JOIN FloorSectionProductMap AS m ON m.upc=p.upc
                INNER JOIN FloorSections AS s ON s.floorSectionID=m.floorSectionID
                LEFT JOIN $sTable AS u ON u.upc=p.upc $sAddOn
                LEFT JOIN FloorSectionsListView AS v ON v.upc=p.upc AND v.storeID=p.store_id
            WHERE sections like '%,%'
                AND v.storeID = ?
                AND p.inUse = 1 
            GROUP BY p.upc, p.store_id
            ORDER BY v.sections
        ");

        $res = $dbc->execute($prep, $args);
        while ($row = $dbc->fetchRow($res)) {
            $upc = $row['upc'];
            $count = $row['signCount'];
            $brand = $row['brand'];
            $description = $row['description'];
            $sections = $row['sections'];
            $lastSold = $row['lastSold'];
            $id = $row['storeID'];
            $td .= "<tr>";
            $td .= "<td>$upc</td>";
            $td .= "<td>$brand</td>";
            $td .= "<td>$description</td>";
            $td .= "<td>$count</td>";
            $td .= "<td>$lastSold</td>";
            $td .= "<td><a href='ProdLocationEditor.php?store_id=&upc=$upc&searchupc=Update+Locations+by+UPC' target='_blank'>$edit</a> $sections</td>";
            $td .= "</tr>";
            $tcount++;
        }

        return <<<HTML
<h4>Manage Merchandise With Multiple Physical Locations for $storeName</h4>
<p>$tcount multi-merched products found.
<table class="table table-bordered">
    <thead>
        <th>UPC</th>
        <th>Brand</th>
        <th>Description</th>
        <th title="Sign Count">SC</th>
        <th>Last Sold</th>
        <th>Locations</th>
    </thead>
    <tbody>$td</tbody></table>
HTML;
    }
}

FannieDispatch::conditionalExec();

