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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class BatchReport extends FannieReportPage 
{
    protected $header = "Select batch(es)";
    protected $title = "Fannie :: Batch Report";
    protected $report_cache = 'none';
    protected $report_headers = array('UPC','SKU','Brand','Description','$','Qty','Rings','Location');
    protected $required_fields = array('batchID');

    public $description = '[Batch Report] lists sales for items in a sales batch (or group of sales batches).';
    public $report_set = 'Batches';
    protected $new_tablesorter = true;

    /**
      Ajax callback:
      Get daily sales totals for a given item
    */
    private function ajaxItemSales()
    {
        $upc = BarcodeLib::padUPC(FormLib::get('upc'));
        $date1 = FormLib::get('date1');
        $date2 = FormLib::get('date2');
        $store = FormLib::get('store');
        $dlog = DTransactionsModel::selectDlog($date1, $date2);
        $dataP = $this->connection->prepare("
            SELECT YEAR(tdate),
                MONTH(tdate),
                DAY(tdate),
                SUM(total),
                MAX(description) AS descript
            FROM {$dlog} AS d
            WHERE upc=?
                AND " . DTrans::isStoreID($store, 'd') . "
                AND tdate BETWEEN ? AND ?
            GROUP BY YEAR(tdate),
                MONTH(tdate),
                DAY(tdate)
            ORDER BY YEAR(tdate),
                MONTH(tdate),
                DAY(tdate)
        ");
        $json = array('dates'=>array(), 'totals'=>array(), 'min'=>99999, 'max'=>0);
        $dataR = $this->connection->execute($dataP, array($upc, $store, $date1 . ' 00:00:00', $date2 . ' 23:59:59'));
        $points = array();
        while ($row = $this->connection->fetchRow($dataR)) {
            $date = date('Y-m-d', mktime(0,0,0, $row[1], $row[2], $row[0]));
            $total = sprintf('%.2f', $row[3]);
            $points[$date] = $total;
            if ($total < $json['min']) {
                $json['min'] = $total;
            }
            if ($total > $json['max']) {
                $json['max'] = $total;
            }
            $json['description'] = $row['descript'];
        }
        $json['min'] = 0.95 * $json['min'];
        $json['max'] = 1.05 * $json['max'];

        // fill in zeroes for any days without sales
        $start = new DateTime($date1);
        $end = new DateTime($date2);
        $p1d = new DateInterval('P1D');
        while ($start <= $end) {
            $str = $start->format('Y-m-d');
            $json['dates'][] = $str;
            $json['totals'][] = isset($points[$str]) ? $points[$str] : 0.00;
            $start->add($p1d);
            if (!isset($points[$str]) && $json['min'] > 0) {
                $json['min'] = 0;
            }
        }
 
        return $json;
    }

    function preprocess()
    {
        $ret = parent::preprocess();
        // ajax callback: get daily item sales
        if (FormLib::get('upc', false) !== false) {
            echo json_encode($this->ajaxItemSales());

            return false;
        }

        $this->addScript('../../src/javascript/Chart.min.js');
        $this->addScript('batchReport.js');
        $this->addOnloadCommand('batchReport.init();');

        return $ret;
    }

    function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $bStart = FormLib::get_form_value('date1','');
        $bEnd = FormLib::get_form_value('date2','');
        $store = FormLib::get('store', false);
        $model = new BatchesModel($dbc);

        if ($store === false && is_array($this->config->get('STORE_NETS'))) {
            $clientIP = filter_input(INPUT_SERVER, 'REMOTE_ADDR');
            $ranges = $this->config->get('STORE_NETS');
            foreach ($ranges as $storeID => $range) {
                if (
                    class_exists('\\Symfony\\Component\\HttpFoundation\\IpUtils')
                    && \Symfony\Component\HttpFoundation\IpUtils::checkIp($clientIP, $range)
                    ) {
                    $store = $storeID;
                }
            }
            if ($store === false) {
                $store = 0;
            }
        }

        /**
          Assemble argument array and appropriate string
          for an IN clause in a prepared statement
        */
        $batchID = $this->form->batchID;
        if (!is_array($batchID)) {
            $batchID = array($batchID);
        }
        $upcs = array();
        foreach ($batchID as $bID) {
            $upcs = array_merge($upcs, $model->getUPCs($bID));
        }
        $upcs = array_unique($upcs);
        list($bName, $bStart, $bEnd) = $this->getNameAndDates($batchID, $bStart, $bEnd);
        
        $dlog = DTransactionsModel::selectDlog($bStart,$bEnd);
        $bStart .= ' 00:00:00';
        $bEnd .= ' 23:59:59';
        $reportArgs = array($bStart, $bEnd);
        list($in_sql, $reportArgs) = $dbc->safeInClause($upcs, $reportArgs);
        $reportArgs[] = $store;

        $salesBatchQ ="
            SELECT d.upc, 
                p.brand,
                p.description, 
                p.default_vendor_id,
                lv.sections AS location,
                vi.sku,
                SUM(d.total) AS sales, "
                . DTrans::sumQuantity('d') . " AS quantity, 
                SUM(CASE WHEN trans_status IN('','0','R') THEN 1 WHEN trans_status='V' THEN -1 ELSE 0 END) as rings
            FROM $dlog AS d "
                . DTrans::joinProducts('d', 'p', 'INNER') . "
                LEFT JOIN FloorSectionsListView as lv on d.upc=lv.upc AND lv.storeID=d.store_id
                LEFT JOIN vendorItems AS vi ON (p.upc = vi.upc AND p.default_vendor_id = vi.vendorID)
            WHERE d.tdate BETWEEN ? AND ?
                AND d.upc IN ($in_sql)
                AND " . DTrans::isStoreID($store, 'd') . "
                AND d.charflag <> 'SO'
            GROUP BY d.upc, 
                p.description
            ORDER BY d.upc";
        $salesBatchP = $dbc->prepare($salesBatchQ);
        $salesBatchR = $dbc->execute($salesBatchP, $reportArgs);

        /**
          Simple report
        
          Issue a query, build array of results
        */
        $ret = array();
        while ($row = $dbc->fetchRow($salesBatchR)) {
            $ret[] = $this->rowToRecord($row);
        }
        return $ret;
    }

    private function rowToRecord($row)
    {
        $record = array();
        $record[] = $row['upc'];
        if ($row['upc'] == $row['sku'] || $row['sku'] == NULL) {
            $record[] = '<div align="right"><i class="text-warning">
            &nbsp;no sku on record</i></div>';
        } else {
            $record[] = $row['sku'];
        }
        $record[] = $row['brand'];
        $record[] = $row['description'];
        $record[] = sprintf('%.2f',$row['sales']);
        $record[] = sprintf('%.2f',$row['quantity']);
        $record[] = $row['rings'];
        $record[] = $row['location'] === null ? '' : $row['location'];

        return $record;
    }
    
    /**
      Sum the quantity and total columns
    */
    function calculate_footers($data)
    {
        $sumQty = 0.0;
        $sumSales = 0.0;
        $sumRings = 0.0;
        foreach ($data as $row) {
            $sumQty += $row[5];
            $sumSales += $row[4];
            $sumRings += $row[6];
        }

        return array('Total',null,null,null,$sumSales,$sumQty, $sumRings, '');
    }

    private function getBatches($dbc, $filter1, $filter2)
    {
        $batchQ = "
            SELECT b.batchID,
                batchName 
            FROM batches AS b
            WHERE 1=1 ";
        $args = array();
        if ($filter1 !== "") {
            $batchQ .= " AND batchType=? ";
            $args[] = $filter1;
        }
        if ($filter2 !== "") {
            $batchQ .= " AND owner=? ";
            $args[] = $filter2;
        }
        $batchQ .= "ORDER BY b.batchID desc";
        $batchP = $dbc->prepare($batchQ);
        $batchR = $dbc->execute($batchP, $args);

        return $batchR;
    }

    function form_content()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $filter1 = FormLib::get('btype','');
        $filter2 = FormLib::get('owner','');

        $ownerQ = $dbc->prepare("
            SELECT super_name 
            FROM superDeptNames 
            WHERE superID > 0
            ORDER BY superID");
        $ownerR = $dbc->execute($ownerQ);
        $o_opts = "<option value=\"\">Select owner</option>";
        while ($ownerW = $dbc->fetchRow($ownerR)) {
            $o_opts .= sprintf("<option %s>%s</option>",
                (($filter2==$ownerW[0])?'selected':''),
                $ownerW[0]);
        }

        $types = new BatchTypeModel($dbc);
        $t_opts = '<option value="">Select type</option>' . $types->toOptions($filter1);

        ob_start();

        echo '<div class="form-inline">';
        echo "<label>Filters</label> ";
        echo '<select id="typef" class="form-control"
            onchange="location=\'BatchReport.php?btype=\'+$(\'#typef\').val()+\'&owner=\'+escape($(\'#ownerf\').val());">';
        echo $t_opts;
        echo '</select>';
        echo '&nbsp;&nbsp;&nbsp;&nbsp;';
        echo '<select id="ownerf" class="form-control"
            onchange="location=\'BatchReport.php?btype=\'+$(\'#typef\').val()+\'&owner=\'+escape($(\'#ownerf\').val());">';
        echo $o_opts;
        echo '</select>';
        echo '</div>';

        echo '<hr />';

        echo '<form action="BatchReport.php" method="get">';
        echo '<div class="row">';
        echo '<div class="col-sm-5">';
        echo '<select size="15" multiple name=batchID[] class="form-control" required>';
        $batchR = $this->getBatches($dbc, $filter1, $filter2);
        while ($batchW = $dbc->fetchRow($batchR)) {
            printf('<option value="%d">%s</option>',
                $batchW['batchID'],$batchW['batchName']);
        }
        echo '</select>';
        echo '</div>';

        $rightCol = <<<HTML
<div class="col-sm-7">
    <p>
        <label>Start Date</label>
        <input class="form-control date-field" name="date1" id="date1" />
    </p>
    <p>
        <label>End Date</label>
        <input class="form-control date-field" name="date2" id="date2" />
    </p>
    <p>
        <label>Store(s)
        {{STORES}}
    </p>
    <p>
        <label>Excel 
        <input type="checkbox" name="excel" value="xls" />
        </label>
    </p>
    <p>
        <button type="submit" class="btn btn-default">Run Report</button>
    </p>
</div>
</div>
HTML;
        $stores = FormLib::storePicker();
        echo str_replace('{{STORES}}', $stores['html'], $rightCol);

        echo '</form>';

        return ob_get_clean();
    }

    private function getNameAndDates($batchID, $bStart, $bEnd)
    {
        $dbc = $this->connection;
        list($inClause, $inArgs) = $dbc->safeInClause($batchID);
        $batchInfoQ = $dbc->prepare("
            SELECT batchName,
                startDate AS startDate,
                endDate AS endDate 
            FROM batches 
            WHERE batchID IN ($inClause)");
        $batchInfoR = $dbc->execute($batchInfoQ,$inArgs);
        $bName = "";
        while ($batchInfoW = $dbc->fetchRow($batchInfoR)) {
            $bName .= $batchInfoW['batchName'] . ' ';
            if (empty($bStart) && strtotime($batchInfoW['startDate'])) {
                $bStart = date('Y-m-d', strtotime($batchInfoW['startDate']));
            }
            if (empty($bEnd) && strtotime($batchInfoW['endDate'])) {
                $bEnd = date('Y-m-d', strtotime($batchInfoW['endDate']));
            }
        }

        return array($bName, $bStart, $bEnd);
    }

    function report_description_content()
    {
        $FANNIE_URL = $this->config->get('URL');
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $ret = array();
        $bStart = FormLib::get('date1','');
        $bEnd = FormLib::get('date2','');
        $batchID = $this->form->batchID;
        if (!is_array($batchID)) {
            $batchID = array($batchID);
        }
        list($bName, $bStart, $bEnd) = $this->getNameAndDates($batchID, $bStart, $bEnd);
        $ret[] = '<br /><span style="font-size:150%;">'.$bName.'</span>';
        if ($this->report_format == 'html') {
            $store = FormLib::storePicker();
            $ret[] = '<p><form action="BatchReport.php" method="get" class="form-inline">';
            $ret[] = "<span style=\"color:black; display:inline;\">
                    Store: {$store['html']} 
                    From: 
                    <input type=\"text\" name=\"date1\" size=\"10\" value=\"$bStart\" id=\"date1\" />
                    to: 
                    <input type=\"text\" name=\"date2\" size=\"10\" value=\"$bEnd\" id=\"date2\" />
                    </span><input type=\"submit\" value=\"Change Dates\" />";
            $this->add_onload_command("\$('#date1').datepicker({dateFormat:'yy-mm-dd'});");
            $this->add_onload_command("\$('#date2').datepicker({dateFormat:'yy-mm-dd'});");
            foreach($batchID as $bID) {
                $ret[] = sprintf('<input type="hidden" name="batchID[]" value="%d" />', $bID);
            }
            $ret[] = '</form></p>';
        } else {
            $ret[] = "<span style=\"color:black\">From: $bStart to: $bEnd</span>";
        }

        return $ret;
    }

    public function helpContent()
    {
        return '<p>Show per-item sales data for items in a batch or set
            of batches over the given date range. The filters just narrow
            down the list of batches. You still have to make selections in
            the list.</p>';
    }

    public function unitTest($phpunit)
    {
        $data = array('upc'=>'4011', 'brand'=>'test', 'description'=>'test',
            'sales'=>1, 'quantity'=>1, 'rings'=>1, 'location'=>'test', 'sku'=>'123');
        $phpunit->assertInternalType('array', $this->rowToRecord($data));
        $phpunit->assertInternalType('array', $this->calculate_footers($this->dekey_array(array($data))));
    }
}

FannieDispatch::conditionalExec();

