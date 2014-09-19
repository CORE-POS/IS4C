<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class PercentageOfSalesReport extends FannieReportPage 
{
    public $discoverable = false; // not directly runnable; must start from search

    protected $title = "Fannie : Percentage Sales Report";
    protected $header = "Percentages Sales Report";

    protected $report_headers = array('UPC', 'Desc', 'Super', 'Dept');
    protected $required_fields = array('u');

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB, $FANNIE_ARCHIVE_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $upcs = FormLib::get('u', array());
        $in = '';
        $args = array();
        foreach($upcs as $u) {
            $in .= '?,';
            $args[] = BarcodeLib::padUPC($u);
        }
        $in = substr($in, 0, strlen($in)-1);

        $query = "SELECT p.upc, p.description, p.department,
                    d.dept_name, l.quantity, l.total,
                    l.percentageStoreSales, l.percentageSuperDeptSales,
                    l.percentageDeptSales, l.weekLastQuarterID as wID,
                    m.super_name, w.weekStart, w.weekEnd
                FROM products AS p
                    LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
                    LEFT JOIN departments AS d ON p.department=d.dept_no
                    LEFT JOIN " . $FANNIE_ARCHIVE_DB . $dbc->sep() . "productWeeklyLastQuarter AS l
                        ON p.upc=l.upc
                    LEFT JOIN " . $FANNIE_ARCHIVE_DB . $dbc->sep() . "weeksLastQuarter AS w
                        ON l.weekLastQuarterID=w.weekLastQuarterID 
                WHERE p.upc IN ($in)
                ORDER BY l.weekLastQuarterID, p.upc";
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, $args);

        $upc_data = array();
        $weeks = array();
        while($row = $dbc->fetch_row($result)) {
            if (!isset($upc_data[$row['upc']])) {
                $upc_data[$row['upc']] = array(
                    'desc' => $row['description'],
                    'dept' => $row['department'] . ' ' . $row['dept_name'],
                    'super' => $row['super_name'],
                    'weeks' => array()
                );
            }
            $upc_data[$row['upc']]['weeks'][$row['wID']] = array(
                'ttl' => $row['total'],
                'qty' => $row['quantity'],
                'st_percent' => $row['percentageStoreSales'],
                'su_percent' => $row['percentageSuperDeptSales'],
                'd_percent' => $row['percentageDeptSales'],
            );
            if (!empty($row['wID']) && !isset($weeks[$row['wID']])) {
                $weeks[$row['wID']] = array(
                    'start' => date('Y-m-d', strtotime($row['weekStart'])),
                    'end' => date('Y-m-d', strtotime($row['weekEnd'])),
                );
            }
        }

        $data = array();
        foreach($upc_data as $upc => $info) {
            $record = array(
                $upc,
                $info['desc'],
                $info['super'],
                $info['dept'],
            );
            foreach($weeks as $id => $dates) {
                if (isset($info['weeks'][$id])) {
                    $record[] = sprintf('%.2f', $info['weeks'][$id]['qty']);
                    $record[] = sprintf('%.2f', $info['weeks'][$id]['ttl']);
                    $record[] = sprintf('%.4f%%', $info['weeks'][$id]['st_percent']*100);
                    $record[] = sprintf('%.4f%%', $info['weeks'][$id]['su_percent']*100);
                    $record[] = sprintf('%.4f%%', $info['weeks'][$id]['d_percent']*100);
                } else {
                    $record[] = 0;
                    $record[] = 0;
                    $record[] = '0%';
                    $record[] = '0%';
                    $record[] = '0%';
                }
            }
            $data[] = $record;
        }

        foreach($weeks as $id => $dates) {
            $this->report_headers[] = 'Qty ' . $dates['start'];
            $this->report_headers[] = 'Ttl ' . $dates['start'];
            $this->report_headers[] = '%All ' . $dates['start'];
            $this->report_headers[] = '%Super ' . $dates['start'];
            $this->report_headers[] = '%Dept ' . $dates['start'];
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

