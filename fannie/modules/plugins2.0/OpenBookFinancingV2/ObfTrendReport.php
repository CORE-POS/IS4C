<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

    This file is part of IT CORE.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ObfTrendReport extends FannieReportPage
{
    protected $sortable = false;
    protected $no_sort_but_style = true;
    protected $required_fields = array('from', 'to');
    protected $title = 'OBF Trends';
    protected $header = 'OBF Trends';
    public $discoverable = false;

    public function fetch_report_data()
    {
        $dbc = ObfLibV2::getDB();
        $start = FormLib::get('from');
        $end = FormLib::get('to');
        $store = FormLib::get('store');

        $categoryP = $dbc->prepare('
            SELECT 
                c.name,
                c.obfCategoryID AS catID
            FROM ObfSalesCache AS s
                INNER JOIN ObfCategories AS c ON s.obfCategoryID=c.obfCategoryID
            WHERE s.obfWeekID BETWEEN ? AND ?
                AND c.storeID=?
            GROUP BY c.obfCategoryID, c.name');
        $salesP = $dbc->prepare('
            SELECT SUM(actualSales) AS actual,
                SUM(lastYearSales) AS ly,
                c.name,
                c.obfCategoryID AS catID,
                s.obfWeekID,
                MAX(w.startDate) AS start
            FROM ObfSalesCache AS s
                INNER JOIN ObfCategories AS c ON s.obfCategoryID=c.obfCategoryID
                INNER JOIN ObfWeeks AS w ON s.obfWeekID=w.obfWeekID
            WHERE s.obfWeekID BETWEEN ? AND ?
                AND s.obfCategoryID=?
            GROUP BY s.obfWeekID, c.obfCategoryID, c.name');
        $subP = $dbc->prepare('
            SELECT actualSales, 
                lastYearSales,
                m.super_name
            FROM ObfSalesCache AS s
                LEFT JOIN is4c_op.superDeptNames AS m ON s.superID=m.superID
            WHERE s.obfWeekID BETWEEN ? AND ?
                AND s.obfCategoryID=?
            ORDER BY m.super_name, s.obfWeekID');

        $laborP = $dbc->prepare('
            SELECT hours
            FROM ObfLabor
            WHERE obfWeekID BETWEEN ? AND ?
                AND obfCategoryID=?
            ORDER BY obfWeekID');

        $sales=$growth=$labor=$slph=array();
        $headers = array();
        $res = $dbc->execute($categoryP, array($start, $end, $store));
        $first = true;
        while ($row = $dbc->fetchRow($res)) {
            $catID = $row['catID'];
            $catName = $row['name'];
            $salesR = $dbc->execute($salesP, array($start, $end, $catID));
            $s_record = array($row['name'] . ' Sales');
            $g_record = array($row['name'] . ' Growth');
            if ($first) {
                $headers[] = '';
            }
            $ttl = array();
            while ($catW = $dbc->fetchRow($salesR)) {
                $ttl[] = $catW['actual'];
                $s_record[] = $catW['actual']; 
                $g_record[] = sprintf('%.2f%%', 100*($catW['actual']-$catW['ly']) / $catW['actual']); 
                if ($first) {
                    $headers[] = date('Y-m-d', strtotime($catW['start']));
                }
            }
            $first = false;
            $s_record['meta'] = FannieReportPage::META_BOLD;
            $g_record['meta'] = FannieReportPage::META_BOLD;
            $sales[] = $s_record;
            $growth[] = $g_record;

            $laborR = $dbc->execute($laborP, array($start, $end, $catID));
            $h_record = array($catName . ' Labor Hours');
            $p_record = array($catName . ' SPLH');
            while ($labW = $dbc->fetchRow($laborR)) {
                $wsales = array_shift($ttl);
                $h_record[] = $labW['hours'];
                $p_record[] = round($wsales / $labW['hours'], 2);
            }
            $labor[] = $h_record;
            $splh[] = $p_record;

            $super = null;
            $subSales = $dbc->execute($subP, array($start,$end,$catID));
            $s_record = $g_record = array();
            while ($subW = $dbc->fetchRow($subSales)) {
                if ($super === null) {
                    $s_record = array($subW['super_name'] . ' Sales');
                    $g_record = array($subW['super_name'] . ' Growth');
                    $super = $subW['super_name'];
                } elseif ($super != $subW['super_name']) {
                    $sales[] = $s_record;
                    $growth[] = $g_record;
                    $s_record = array($subW['super_name'] . ' Sales');
                    $g_record = array($subW['super_name'] . ' Growth');
                    $super = $subW['super_name'];
                }
                $s_record[] = $subW['actualSales'];
                $g_record[] = sprintf('%.2f%%', 100*($subW['actualSales'] - $subW['lastYearSales']) / $subW['actualSales']);
            }
            $sales[] = $s_record;
            $growth[] = $g_record;
        }
        $this->report_headers = $headers;

        $sttl = $this->sumSales($sales);
        $sales[] = $sttl;

        $noSalesP = $dbc->prepare('
            SELECT obfCategoryID, name
            FROM ObfCategories
            WHERE hasSales=0
                AND storeID=?');
        $nsR = $dbc->execute($noSalesP, array($store));
        while ($nsW = $dbc->fetchRow($nsR)) {
            $l_record = array($nsW['name'] . ' Labor Hours');
            $s_record = array($nsW['name'] . ' SPLH');
            $laborR = $dbc->execute($laborP, array($start, $end, $nsW['obfCategoryID']));
            $week = 1;
            while ($labW = $dbc->fetchRow($laborR)) {
                $l_record[] = $labW['hours'];
                $s_record[] = sprintf('%.2f', $sttl[$week]/$labW['hours']);
                $week++;
            }
            $labor[] = $l_record;
            $splh[] = $s_record;
        }


        $lttl = $this->sumLabor($labor);
        array_unshift($labor, $lttl);

        $pttl = $this->sumSPLH($sttl, $lttl);
        array_unshift($splh, $pttl);

        return array_merge($sales, $growth, $labor, $splh);
    }

    private function sumSPLH($sttl, $lttl)
    {
        $pttl = array('SPLH Total');
        foreach ($sttl as $id => $val) {
            if ($id !== 'meta' && $id !== 0) {
                $pttl[$id] = round($sttl[$id] / $lttl[$id], 2);
            }
        }
        $pttl['meta'] = FannieReportPage::META_BOLD;

        return $pttl;
    }

    private function sumSales($sales)
    {
        $sttl = array('Total Sales');
        // -1 because meta
        for ($i=1; $i<count($sales[0])-1; $i++) {
            $ssum = 0;
            for ($j=0; $j<count($sales); $j++) {
                if (!isset($sales[$j]['meta'])) {
                    continue;
                }
                $ssum += $sales[$j][$i];
            }
            $sttl[] = $ssum;
        }
        $sttl['meta'] = FannieReportPage::META_BOLD;
        
        return $sttl; 
    }

    private function sumLabor($labor)
    {
        $lttl = array('Total Labor');
        for ($i = 1; $i<count($labor[0]); $i++) {
            $lsum = 0;
            for ($j=0;$j<count($labor);$j++) {
                $lsum += $labor[$j][$i];
            }
            $lttl[] = $lsum;
        }
        $lttl['meta'] = FannieReportPage::META_BOLD;

        return $lttl;
    }

    public function form_content()
    {
        $dbc = ObfLibV2::getDB();
        $res = $dbc->query('
            SELECT c.obfWeekID, w.startDate
            FROM ObfSalesCache AS c
                INNER JOIN ObfWeeks AS w ON c.obfWeekID=w.obfWeekID
            WHERE c.actualSales > 0 AND c.actualSales IS NOT NULL
            GROUP BY c.obfWeekID, w.startDate
            ORDER BY w.startDate DESC');
        $weeks = array();
        while ($row = $dbc->fetchRow($res)) {
            $weeks[$row['obfWeekID']] = 'Week of: ' . date('Y-m-d', strtotime($row['startDate']));
        }

        $ret = '<form action="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '" method="get">';
        $ret .= '<div class="form-group">
                <label>From</label>
                <select name="from" class="form-control">';
        foreach ($weeks as $id => $name) {
            $ret .= '<option value="' . $id . '">' . $name . '</option>';
        }
        $ret .= '</select>
                </div>';
        $ret .= '<div class="form-group">
                <label>To</label>
                <select name="to" class="form-control">';
        foreach ($weeks as $id => $name) {
            $ret .= '<option value="' . $id . '">' . $name . '</option>';
        }
        $ret .= '</select>
                </div>';
        $stores = FormLib::storePicker();
        $ret .= '<div class="form-group">
                <label>Store</label>
                ' . $stores['html'] . '
                </div>
                <p>
                <button type="submit" class="btn btn-default">Submit</button>
                </p>
                </form>';

        return $ret;
    }

    public function report_content() 
    {
        $default = parent::report_content();
        if ($this->report_format == 'html') {
            $default .= '<div id="chartArea" style="border: 1px solid black;padding: 2em;">';
            $default .= 'Graph: <select id="grapher" onchange="showGraph(this.value);"></select>';
            $default .= '<div id="chartDiv"></div>';
            $default .= '</div>';
            $this->addScript('trend.js');
            $this->addOnloadCommand('addOptions();');
            $this->addOnloadCommand("showGraph(\$('tbody td.reportColumn0:first').text().trim());\n");
        }

        return $default;
    }

    public function preprocess()
    {
        parent::preprocess();
        // custom: needs graphing JS/CSS
        if ($this->content_function == 'report_content' && $this->report_format == 'html') {
            $this->addScript('../../../src/javascript/d3.js/d3.v3.min.js');
            $this->addScript('../../../src/javascript/d3.js/charts/singleline/singleline.js');
            $this->addCssFile('../../../src/javascript/d3.js/charts/singleline/singleline.css');
        }

        return true;
    }
}

FannieDispatch::conditionalExec();

