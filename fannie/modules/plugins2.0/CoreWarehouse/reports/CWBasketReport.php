<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

    This file is part of CORE-POS.

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

include(dirname(__FILE__).'/../../../../config.php');
if (!class_exists('\\FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class CWBasketReport extends FannieReportPage 
{
    public $description = '[Basket Report] lists weekly statistics about
        basket size for members, non-members, and both combined.
        Requires CoreWarehouse plugin.';
    public $themed = true;
    public $report_set = 'Transaction Reports';

    protected $header = 'Basket Report';
    protected $title = 'Basket Report';
    protected $required_fields = array('date1', 'date2');
    protected $report_headers = array(
        'Date Range',
        'Mem Trans',
        'Non Trans',
        'Total Trans',
        'Mem Qty',
        'Non Qty',
        'Total Qty',
        'Mem $',
        'Non $',
        'Total $',
        'Mem Avg $',
        'Non Avg $',
        'Total Avg $',
    );

    public function preprocess()
    {
        $ret = parent::preprocess();
        // custom: needs graphing JS/CSS
        if ($this->content_function == 'report_content' && $this->report_format == 'html') {
            $this->add_script('../../../../src/javascript/d3.js/d3.v3.min.js');
            $this->add_script('../../../../src/javascript/d3.js/charts/singleline/singleline.js');
            $this->add_css_file('../../../../src/javascript/d3.js/charts/singleline/singleline.css');
        }

        return $ret;
    }

    public function report_content() 
    {
        $default = parent::report_content();

        if ($this->report_format == 'html') {
            $default .= '<div id="chartArea" style="border: 1px solid black;padding: 2em;">';
            $default .= 'Graph: <select onchange="showGraph(this.value);">';
            $default .= '<option value="1"># of Transactions</option>';
            $default .= '<option value="4"># of Items</option>';
            $default .= '<option value="7">Total Spending</option>';
            $default .= '<option value="10">Average Spending</option>';
            $default .= '</select>';
            $default .= '<div id="chartDiv"></div>';
            $default .= '</div>';

            $this->add_onload_command('showGraph(1)');
        }

        return $default;
    }

    public function fetch_report_data()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc = FannieDB::get($settings['WarehouseDatabase']);

        $dates = array(
            date('Ymd', strtotime(FormLib::get('date1'))),
            date('Ymd', strtotime(FormLib::get('date2'))),
        );

        $query = '
            SELECT MIN(t.date_id) AS start,
                MAX(t.date_id) AS end,
                COUNT(*) AS numTrans,
                SUM(retailTotal) AS retailTotal,
                SUM(retailQty) AS retailQty,
                SUM(nonRetailTotal) AS nonRetailTotal,
                SUM(nonRetailQty) AS nonRetailQty,
                CASE WHEN custdataType=\'PC\' THEN 1 ELSE 0 END AS isMember
            FROM transactionSummary AS t
                INNER JOIN WarehouseDates AS w ON t.date_id=w.warehouseDateID
                LEFT JOIN ' . $this->config->get('OP_DB') . $dbc->sep() . 'memtype AS m ON m.memtype=t.memType
            WHERE t.date_id BETWEEN ? AND ?
            GROUP BY custdataType,
                w.year,
                w.isoWeekNumber 
            ORDER BY MIN(t.date_id),
                CASE WHEN custdataType=\'PC\' THEN 1 ELSE 0 END DESC';
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, $dates);

        $data = array();
        while ($w = $dbc->fetchRow($result)) {
            if ($w['end'] - $w['start'] < 6) {
                continue;
            }
            $span = date('Y-m-d', strtotime($w['start']))
                . ' - '
                . date('Y-m-d', strtotime($w['end']));
            if (!isset($data[$span])) {
                $data[$span] = array(
                    'MemCount' => 0,
                    'NonMemCount' => 0,
                    'MemRetail' => 0.0,
                    'NonMemRetail' => 0.0,
                    'MemRetailQty' => 0.0,
                    'NonMemRetailQty' => 0.0,
                    'MemTotal' => 0.0,
                    'NonMemTotal' => 0.0,
                    'MemTotalQty' => 0.0,
                    'NonMemTotalQty' => 0.0,
                );
            }
            if ($w['isMember']) {
                $data[$span]['MemCount'] = $w['numTrans'];
                $data[$span]['MemRetail'] = $w['retailTotal'];
                $data[$span]['MemRetailQty'] = $w['retailQty'];
                $data[$span]['MemTotal'] = $w['retailTotal'] + $w['nonRetailTotal'];
                $data[$span]['MemTotalQty'] = $w['retailQty'] + $w['nonRetailQty'];
            } else {
                $data[$span]['NonMemCount'] = $w['numTrans'];
                $data[$span]['NonMemRetail'] = $w['retailTotal'];
                $data[$span]['NonMemRetailQty'] = $w['retailQty'];
                $data[$span]['NonMemTotal'] = $w['retailTotal'] + $w['nonRetailTotal'];
                $data[$span]['NonMemTotalQty'] = $w['retailQty'] + $w['nonRetailQty'];
            }
        }

        $report = array();
        foreach ($data as $span => $info) {
            $record = array(
                $span,
                $info['MemCount'],
                $info['NonMemCount'],
                $info['MemCount'] + $info['NonMemCount'],
                sprintf('%.2f',$info['MemRetailQty']),
                sprintf('%.2f',$info['NonMemRetailQty']),
                sprintf('%.2f',$info['MemRetailQty']+$info['NonMemRetailQty']),
                sprintf('%.2f',$info['MemRetail']),
                sprintf('%.2f',$info['NonMemRetail']),
                sprintf('%.2f',$info['MemRetail']+$info['NonMemRetail']),
                sprintf('%.2f', $info['MemRetail'] / $info['MemCount']),
                sprintf('%.2f', $info['NonMemRetail'] / $info['NonMemCount']),
                sprintf('%.2f', ($info['MemRetail']+$info['NonMemRetail'])/($info['MemCount']+$info['NonMemCount'])),
            );
            $report[] = $record;
        }

        return $report;
    }

    public function calculate_footers($data)
    {
        $sums = array();
        $count = 0;
        foreach ($data as $row) {
            for ($i=1; $i<count($row); $i++) {
                if (!isset($sums[$i])) {
                    $sums[$i] = 0;
                }
                $sums[$i] += $row[$i];
            }
            $count++;
        }
        $ret = array('Averages');
        foreach ($sums as $s) {
            $ret[] = sprintf('%.2f', $s/$count);
        }
        return $ret;
    }

    public function form_content()
    {
        $ret = '<form method="get">'
            . FormLib::standardDateFields()
            . '<div class="form-group">
                <button type="submit" class="btn btn-default">Submit</button>
               </div>
            </form>';

        return $ret;
    }
    
    public function javascript_content()
    {
        if ($this->report_format != 'html') {
            return;
        }

        ob_start();
        ?>
function showGraph(colset) {
    var ymin = 999999999;
    var ymax = 0;

    var ydata = Array();
    $('td.reportColumn'+colset).each(function(){
        var y = Number($(this).html());
        ydata.push(y);
        if (y > ymax) {
            ymax = y;
        }
        if (y < ymin) {
            ymin = y;
        }
    });

    var y2data = Array();
    $('td.reportColumn'+(Number(colset)+1)).each(function(){
        var y = Number($(this).html());
        y2data.push(y);
        if (y > ymax) {
            ymax = y;
        }
        if (y < ymin) {
            ymin = y;
        }
    });

    var y3data = Array();
    $('td.reportColumn'+(Number(colset)+2)).each(function(){
        var y = Number($(this).html());
        y3data.push(y);
        if (y > ymax) {
            ymax = y;
        }
        if (y < ymin) {
            ymin = y;
        }
    });

    var xdata = Array();
    var x = 0;
    $('td.reportColumn0').each(function(){
        xdata.push(x);
        x++;
    });
    xmin = xdata[0];
    xmax = xdata[xdata.length-1];

    var data = Array();
    var data2 = Array();
    var data3 = Array();
    for (var i=0; i < xdata.length; i++) {
        data.push(Array(xdata[i], ydata[i]));
        data2.push(Array(xdata[i], y2data[i]));
        data3.push(Array(xdata[i], y3data[i]));
    }

    $('#chartDiv').html('');
    singleline(data, Array(xmin, xmax), Array(ymin, ymax), '#chartDiv');
    addsingleline(data2, Array(xmin, xmax), Array(ymin, ymax), '#chartDiv', 'red');
    addsingleline(data3, Array(xmin, xmax), Array(ymin, ymax), '#chartDiv', 'green');
}
        <?php
        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>
            This report lists <em>weekly</em> statistics
            about basket size and spending for members, 
            non-members, and both combined. The web version
            of the report also includes a graph where the
            blue line is members, the red line is non-members,
            and the green line is both combined.
            </p>';
    }
}

FannieDispatch::conditionalExec();

