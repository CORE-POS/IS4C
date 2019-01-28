<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

use COREPOS\Fannie\API\lib\Store;

include(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class SearchCheckList extends FannieReportPage 
{
    public $discoverable = false; // not directly runnable; must start from search

    protected $title = "Fannie : Search Check List";
    protected $header = "Search Check List";

    protected $report_headers = array('UPC', 'Brand', 'Description', 'Location(s)', 'Notes');
    protected $required_fields = array('u');
    protected $sort_column = 3;

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $upcs = FormLib::get('u', array());
        list($in, $args) = $dbc->safeInClause($upcs);
        $prep = $dbc->prepare("SELECT
                p.upc, p.brand, p.description, f.sections
            FROM products AS p
                LEFT JOIN FloorSectionsListView AS f ON p.upc=f.upc AND p.store_id=f.storeID
            WHERE p.upc IN ({$in})
                AND p.store_id=?
            ORDER BY f.sections,
                p.brand,
                p.description");
        $store = Store::getIdByIp();
        $args[] = $store;
        $res = $dbc->execute($prep, $args);
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $data[] = array(
                $row['upc'],
                $row['brand'] ? $row['brand'] : '',
                $row['description'],
                $row['sections'] ? $row['sections'] : '',
                '',
            );
        }

        return $data;
    }

    public function calculate_footers($data)
    {
        return array();
    }

    public function form_content()
    {
        global $FANNIE_URL;
        return "Use <a href=\"{$FANNIE_URL}item/AdvancedItemSearch.php\">Search</a> to
            select items for this report";;
    }
}

FannieDispatch::conditionalExec();

