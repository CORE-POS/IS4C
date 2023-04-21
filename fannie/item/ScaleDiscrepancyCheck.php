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
    include(__DIR__ . '/../classlib2.0/FannieAPI.php');
}

class ScaleDiscrepancyCheck extends FannieRESTfulPage
{

    public $description = '[Scale Discrepancy Check] scans ePlum DBMS for 
        discrepancies between POS and the scales.';
    public $title = 'Scale Discrepancy Check';
    public $header = 'Scale Discrepancy Check';

    public function preprocess()
    {
        return parent::preprocess();
    }

    private function getItemList()
    {
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $upcs = array();

        $pre = $dbc->prepare("SELECT upc FROM products AS p LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID where upc like '%000000' AND upc LIKE '002%' AND super_name IN ('GROCERY', 'MEAT') AND inUse = 1 GROUP BY upc;");
        $res = $dbc->execute($pre);
        while ($row = $dbc->fetchRow($res)) {
            $upcs[] = $row['upc'];
        }

        return $upcs; 
    }

    public function get_view()
    {
        $config = FannieConfig::factory();
        $settings = $config->get('PLUGIN_SETTINGS');
        $inStr = '';
        $upcs = $this->getItemList();

        $dbc = new SQLManager('192.168.1.8,1433','pdo_sqlsrv','ePLUMSql',$settings['CTUser'],$settings['CTPassword']);

        foreach ($upcs as $k => $upc) {
            $inStr .= $upc;
            if (array_key_exists($k+1, $upcs))
                $inStr .= ',';
        }
        // deptno = the scale to look at
        $query = "SELECT  * FROM ePLUM.PLUMaster WHERE upc IN ($inStr) AND deptno=3 order by upc, deptno";
        $res = $dbc->query($query);
        $td = '';
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $upc = $row['upc'];
            $price = $row['currup'] / 100;
            $dept = $row['deptno'];
            $desc = array($row['desc1'], $row['desc2'], $row['desc3'], $row['desc4']);

            $data[$upc]['price'] = $price;
            $data[$upc]['desc'] = $desc;
        }
        echo "<h4>" . $dbc->error() . "</h4>";

        global $FANNIE_OP_DB, $FANNIE_TRANS_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        list($inStr, $args) = $dbc->safeInClause($upcs);
        $prep = $dbc->prepare("SELECT upc, description, normal_price FROM products WHERE upc IN ($inStr)");
        $res = $dbc->execute($prep, $args);
        $diffsExist = false;
        while ($row = $dbc->fetchRow($res)) {
            $upc = $row['upc'];
            $price = $row['normal_price'];
            $desc = $row['description'];

            $scalePrice = $data[$upc]['price'];
            $diff = $price - $scalePrice;
            if ($diff != 0) {
                $diffsExist = true;
                $td .= "<tr>";
                $td .= "<td>$upc</td>";
                $td .= "<td>$desc</td>";
                $td .= "<td>$price</td>";
                $td .= "<td>$scalePrice</td>";
                $td .= "<td>$diff</td>";
                $td .= "</tr>";
            }

        };

        $ret = '';
        if (!$diffsExist) {
            $ret .= "<div class=\"well\">There are no discrepancies at this time.</div>";
        } else {
            $ret .= "<p>The following discrepancies were found between the scale and POS.</p>";
        }
        $ret .= "<table class=\"table table-bordered\">
            <thead><th>UPC</th><th>Description</th><th>POS</th><th>Scale</th><th>Difference</th></thead>
            <tbody>$td</tbody></table>";

        return $ret;
    }
}

FannieDispatch::conditionalExec();

