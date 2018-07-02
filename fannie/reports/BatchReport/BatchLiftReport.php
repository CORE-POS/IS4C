<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class BatchLiftReport extends FannieReportPage 
{
    protected $header = "Lift Report";
    protected $title = "Fannie :: Batch Lift Report";
    protected $report_cache = 'none';
    protected $report_headers = array('UPC','Date', 'Qty', '$', 'Previous', 'Qty', '$');
    protected $required_fields = array('upc', 'id', 'store');

    public $discoverable = false;
    public $report_set = 'Batches';
    protected $new_tablesorter = true;

    function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $store = FormLib::get('store', false);
        $upc = FormLib::get('upc');
        $batchID = FormLib::get('id');

        $nameP = $dbc->prepare('SELECT brand, description FROM products WHERE upc=?');
        $name = $dbc->getRow($nameP, array($upc));
        $this->header .= ': ' . $name['brand'] . ' ' . $name['description'];

        $lineData = array(
            array(),
            array(),
        );
        $lineLabels = array('Promo Sales', 'Previous Sales');
        $xLabels = array();

        $prep = $dbc->prepare('SELECT upc,
                saleDate, saleQty, saleTotal,
                compareDate, compareQty, compareTotal
            FROM SalesLifts
            WHERE upc=?
                AND batchID=?
                AND storeID=?
            ORDER BY saleDate');
        $res = $dbc->execute($prep, array($upc, $batchID, $store));
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            list($sDate,) = explode(' ', $row['saleDate']);
            list($cDate,) = explode(' ', $row['compareDate']);
            $data[] = array(
                $row['upc'],
                $sDate,
                sprintf('%.2f', $row['saleQty']), 
                sprintf('%.2f', $row['saleTotal']), 
                $cDate,
                sprintf('%.2f', $row['compareQty']), 
                sprintf('%.2f', $row['compareTotal']), 
            );
            $xLabels[] = $sDate;
            $lineData[0][] = $row['saleQty'] == null ? 0 : $row['saleQty'];
            $lineData[1][] = $row['compareQty'] == null ? 0 : $row['compareQty'];
        }

        $xLabels = json_encode($xLabels);
        $lineData = json_encode($lineData);
        $lineLabels = json_encode($lineLabels);
        $this->addScript($this->config->get('URL') . 'src/javascript/Chart.min.js');
        $this->addScript($this->config->get('URL') . 'src/javascript/CoreChart.js');
        $this->addOnloadCommand("CoreChart.lineChart('liftChart', {$xLabels}, {$lineData}, {$lineLabels});");

        return $data;
    }

    function calculate_footers($data)
    {
        $sums = array(0, 0, 0, 0);
        foreach ($data as $row) {
            $sums[0] += $row[2];
            $sums[1] += $row[3];
            $sums[2] += $row[5];
            $sums[3] += $row[6];
        }

        return array('Total', '', $sums[0], $sums[1], '', $sums[2], $sums[3]);
    }

    public function report_content()
    {
        $default = parent::report_content();
        if ($this->report_format == 'html') {
            $default .= '<div class="row">
                <div class="col-sm-10"><canvas id="liftChart"></canvas></div>
                </div>';
        }

        return $default;
    }

    public function form_content()
    {
        return '<!-- Intetionally blank -->';
    }
}

FannieDispatch::conditionalExec();

