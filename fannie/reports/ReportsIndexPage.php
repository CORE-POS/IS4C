<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ReportsIndexPage extends FanniePage {

    protected $title = "Fannie : Reports";
    protected $header = "Reports";

    public $description = '[Reports Menu] lists all known reports.';

    public function body_content()
    {
        global $FANNIE_ROOT, $FANNIE_URL;

        ob_start();
        $terminology = '
<ul>
<li><span style="font-weight:bold;">Terminology (<a href="" onclick="$(\'#terminologyList\').toggle(); return false;">Show/Hide</a>)</span>
<ul style="display:none;" id="terminologyList">
<li>"Buyer" and "Super Department" usually refer to the same kind of thing:
a grouping of Departments. 
Some coops organize their Super Departments by Buyer, the person buying, but others do not.
A Department can be in more than one Super Department,
say by Buyer e.g. "Patricia" and by Category of Product e.g. "Oils and Vinegars",
so in that sense they are like tags.
However, a product (item) cannot be in more than one Department.
</li>
<li>"Vendor", "Distributor" and "Supplier" usually refer to the same kind of thing:
the organization from which the coop obtained, purchased, bought the product.
It may refer to the same organization that made it, the Manufacturer,
but the name used in IS4C may be different.
</li>
<li>"Manufacturer" and "Brand" usually refer to the same kind of thing:
the organization that made the product and often whose name is on it.
Real UPCs are usually supplied by the Manufacturer.
The first five digits, or six, if the first is not 0, digits of the UPC usually identify the
manufacturer, but this is less strict than it used to be,
so that sometimes more leading digits identify the manufacturer.
IS4C UPCs have 13 characters, padded on the left with zeroes, so there may be two or three zeroes
before the first significant digit.
You can usually enter numbers starting with the first non-zero, but zeroes at the end are not assumed.
<li>"UPC" and "PLU" usually refer to the same kind of thing:
The unique code for an item that is scanned or typed at the PoS terminal.
More strictly, PLUs are used on produce and UPCs on packaged goods.
</li>
<li>"Member", "Owner" and "Customer" refer to the same thing: the membership designated by the member number.
It is printed at the end of receipts.
The Membership Card number, the barcode on the card is different; it is used to find the member number.
All member-related things in IS4C are on the member number.
</li>
<li><span style="font-weight:bold;">Download</span>
    <ul>
    <li>"Excel", in newer reports, where "CSV" is also an option, refers to a file
    with formatting similar to that on the page.
    It is for further use in Excel or another similar spreadsheet program.
    In older reports, where there is no "CSV" option, it is more like CSV (raw data).
    </li>
    <li>"CSV" refers to a file of raw data, without formatting.
    Literally, "Comma-Separated Values".  Many applications, including Excel, can import this format.
    </li>
    </ul>
    </li>
    </ul>
</li>
<li><span style="font-weight:bold;">Note</span>
<br />While making these reports it can be useful to have the 
<a href="../item/ItemEditorPage.php" target="_itemEdit">Item Maintenance</a> application
open in a separate tab or window as a reference for Manufacturers and Vendors (Distributors).
</li>
</ul>';
        echo $terminology;

        $report_sets = array();
        $other_reports = array();

        $reports = FannieAPI::listModules('FannieReportPage');
        foreach($reports as $class) {
            $obj = new $class();
            if (!$obj->discoverable) {
                continue;
            }
            $reflect = new ReflectionClass($obj);
            $url = $FANNIE_URL . str_replace($FANNIE_ROOT, '', $reflect->getFileName());
            if ($obj->report_set != '') {
                if (!isset($report_sets[$obj->report_set])) {
                    $report_sets[$obj->report_set] = array();
                }
                $report_sets[$obj->report_set][] = array(
                    'url' => $url,
                    'info' => $obj->description,
                );
            } else {
                $other_reports[] = array(
                    'url' => $url,
                    'info' => $obj->description,
                );
            }
        }
        $tools = FannieAPI::listModules('FannieReportTool');
        foreach($tools as $class) {
            $obj = new $class();
            if (!$obj->discoverable) {
                continue;
            }
            $reflect = new ReflectionClass($obj);
            $url = $FANNIE_URL . str_replace($FANNIE_ROOT, '', $reflect->getFileName());
            if ($obj->report_set != '') {
                if (!isset($report_sets[$obj->report_set])) {
                    $report_sets[$obj->report_set] = array();
                }
                $report_sets[$obj->report_set][] = array(
                    'url' => $url,
                    'info' => $obj->description,
                );
            } else {
                $other_reports[] = array(
                    'url' => $url,
                    'info' => $obj->description,
                );
            }
        }
        echo '<ul>';
        $keys = array_keys($report_sets);
        sort($keys);
        foreach($keys as $set_name) {
            echo '<li>' . $set_name;
            echo '<ul>';
            $reports = $report_sets[$set_name];
            usort($reports, array('ReportsIndexPage', 'reportAlphabetize'));
            foreach($reports as $report) {
                $description = $report['info'];
                $url = $report['url'];
                $linked = preg_replace('/\[(.+)\]/', '<a href="' . $url . '">\1</a>', $description);
                if ($linked === $description) {
                    $linked .= ' (<a href="' . $url . '">Link</a>)';
                }
                echo '<li>' . $linked . '</li>';
            }
            echo '</ul></li>';
        }
        usort($other_reports, array('ReportsIndexPage', 'reportAlphabetize'));
        foreach($other_reports as $report) {
            $description = $report['info'];
            $url = $report['url'];
            $linked = preg_replace('/\[(.+)\]/', '<a href="' . $url . '">\1</a>', $description);
            if ($linked === $description) {
                $linked .= ' (<a href="' . $url . '">Link</a>)';
            }
            echo '<li>' . $linked . '</li>';
        }
        echo '</ul>';

        return ob_get_clean();
    }

    static private function reportAlphabetize($a, $b)
    {
        if ($a['info'] < $b['info']) {
            return -1;
        } else if ($a['info'] > $b['info']) {
            return 1;
        } else {
            return 0;
        }
    }

}

FannieDispatch::conditionalExec();

