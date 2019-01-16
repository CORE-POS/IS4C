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

class CustomerSalesReport extends FannieReportPage 
{
    public $discoverable = false; // not directly runnable; must start from search

    protected $title = "Fannie : Search Check List";
    protected $header = "Search Check List";

    protected $report_headers = array('UPC', 'Brand', 'Description', 'Qty', '$');
    protected $required_fields = array('id');
    protected $sort_column = 4;
    protected $sort_direction = 1;
    protected $new_tablesorter = true;

    function report_description_content()
    {
        $FANNIE_URL = $this->config->get('URL');
        $ret = array();
        if ($this->report_format == 'html') {
            $date1 = FormLib::get('date1', date('Y-m-d'));
            $date2 = FormLib::get('date2', date('Y-m-d'));
            $ret[] = '<p><form action="CustomerSalesReport.php" method="get" class="form-inline">';
            $ret[] = "<span style=\"color:black; display:inline;\">
                    From: 
                    <input type=\"text\" name=\"date1\" size=\"10\" value=\"$date1\" id=\"date1\" />
                    to: 
                    <input type=\"text\" name=\"date2\" size=\"10\" value=\"$date2\" id=\"date2\" />
                    </span><input type=\"submit\" value=\"Change Dates\" />
                    <style type=\"text/css\">
                    .ui-datepicker {
                        z-index: 999 !important;
                    }
                    </style>";
            foreach(FormLib::get('id', array()) as $id) {
                $ret[] = sprintf('<input type="hidden" name="id[]" value="%d" />', $id);
            }
            $ret[] = '</form></p>';
            $this->addOnloadCommand("\$('#date1').datepicker({dateFormat:'yy-mm-dd'});");
            $this->addOnloadCommand("\$('#date2').datepicker({dateFormat:'yy-mm-dd'});");
            $ids = FormLib::get('id', array());
            $whTable = FannieDB::fqn('transactionSummary', 'plugin:WarehouseDatabase');
            if ($this->connection->tableExists($whTable)) {
                list($inStr, $args) = $this->connection->safeInClause($ids);
                $args[] = date('Ymd', strtotime($date1));
                $args[] = date('Ymd', strtotime($date2));
                $prep = $this->connection->prepare("
                    SELECT COUNT(*) AS visits,
                        AVG(retailTotal) AS basket
                    FROM {$whTable}
                    WHERE card_no IN ({$inStr})
                        AND date_id BETWEEN ? AND ?");
                $info = $this->connection->getRow($prep, $args);
                $ret[] = '<h3># of Visits: ' . $info['visits'] . '</h3>';
                $ret[] = '<h3>Avg. Basket: $' . round($info['basket'], 2) . '</h3>';
            }
            $ret[] = '<div class="row">
                <div class="col-sm-5"><h3 id="hQty">Quantity Distribution</h3><canvas id="canvas1"></canvas></div>
                <div class="col-sm-5"><h3 id="hSales">Sales Distribution</h3><canvas id="canvas2"></canvas></div>
                </div>';
        }

        return $ret;
    }

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $date1 = FormLib::get('date1', date('Y-m-d'));
        $date2 = FormLib::get('date2', date('Y-m-d'));
        $dlog = DTransactionsModel::selectDlog($date1, $date2);

        $ids = FormLib::get('id', array());
        list($inStr, $args) = $dbc->safeInClause($ids);
        $args[] = $date1;
        $args[] = $date2 . ' 23:59:59';
        $qtyMix = array();
        $saleMix = array();
        $prep = $dbc->prepare("SELECT
                d.upc, p.brand, p.description, m.super_name, SUM(d.total) AS ttl,
                " . DTrans::sumQuantity('d') . " AS qty
            FROM {$dlog} AS d
                " . DTrans::joinProducts('d', 'p') . "
                INNER JOIN MasterSuperDepts AS m ON d.department=m.dept_ID
            WHERE d.card_no IN ({$inStr})
                AND d.tdate BETWEEN ? AND ?
                AND d.trans_type in ('I', 'D')
                AND m.superID <> 0
            GROUP BY d.upc, p.brand, p.description, m.super_name");
        $res = $dbc->execute($prep, $args);
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $data[] = array(
                $row['upc'],
                $row['brand'] ? $row['brand'] : '',
                $row['description'],
                sprintf('%.2f', $row['qty']),
                sprintf('%.2f', $row['ttl']),
            );
            if (!isset($qtyMix[$row['super_name']])) {
                $qtyMix[$row['super_name']] = 0;
            }
            $qtyMix[$row['super_name']] += $row['qty'];
            if (!isset($saleMix[$row['super_name']])) {
                $saleMix[$row['super_name']] = 0;
            }
            $saleMix[$row['super_name']] += $row['ttl'];
        }

        if ($this->report_format == 'html') {
            $this->addScript('../../../src/javascript/Chart.min.js');
            $this->addScript('../../../src/javascript/CoreChart.js');
            krsort($qtyMix);
            $labels = json_encode(array_keys($qtyMix));
            $slices = array_values($qtyMix);
            $total = array_sum($slices);
            $slices = array_map(function ($i) use ($total) { return sprintf('%.2f', $i / $total * 100); }, $slices);
            $slices = json_encode($slices);
            $this->addOnloadCommand("CoreChart.pieChart('canvas1', {$labels}, {$slices});");
            krsort($saleMix);
            $labels = json_encode(array_keys($saleMix));
            $slices = array_values($saleMix);
            $total = array_sum($slices);
            $slices = array_map(function ($i) use ($total) { return sprintf('%.2f', $i / $total * 100); }, $slices);
            $slices = json_encode($slices);
            $this->addOnloadCommand("CoreChart.pieChart('canvas2', {$labels}, {$slices});");
        }

        return $data;
    }

    public function calculate_footers($data)
    {
        $qty = array_sum(array_map(function ($i) { return $i[3]; }, $data));
        $ttl = array_sum(array_map(function ($i) { return $i[4]; }, $data));
        if ($this->report_format == 'html') {
            $str = sprintf('Quantity Distribution - %% of %.2f', $qty);
            $this->addOnloadCommand("\$('#hQty').html('{$str}');");
            $str = sprintf('Sales Distribution - %% of $%.2f', $ttl);
            $this->addOnloadCommand("\$('#hSales').html('{$str}');");
        }

        return array('Total', '', '', $qty, $ttl);
    }

    public function form_content()
    {
        global $FANNIE_URL;
        return "Use <a href=\"{$FANNIE_URL}mem/AdvancedMemSearch.php\">Search</a> to
            select customers for this report";;
    }
}

FannieDispatch::conditionalExec();

