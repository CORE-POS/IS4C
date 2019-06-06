<?php

include(__DIR__ . '/../../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__).'/../../../../../classlib2.0/FannieAPI.php');
}

class YOYDailySales extends COREPOS\Fannie\API\FannieGraphReportPage
{
    protected $header = 'Year Over Year Department Sales Report';
    protected $title = 'Year Over Year Department Sales Report';
    public $discoverable = true;
    public $description = '[Year over Year Daily Sales] compares several years\' sales by day';
    public $report_set = 'Sales Reports';
    protected $required_fields = array('date1', 'date2', 'years', 'super', 'store');
    protected $report_headers = array();
    protected $new_tablesorter = true;

    public function report_description_content()
    {
        $ts1 = strtotime(FormLib::get('date1'));
        $ts2 = strtotime(FormLib::get('date2'));
        $ret = array();
        for ($i=0; $i<FormLib::get('years'); $i++) {
            if ($i == 0) {
                $line = 'This year: ';
            } elseif ($i == 1) {
                $line = '1 year ago: ';
            } else {
                $line = $i . ' years ago: ';
            }
            $line .= date('F j, Y', mktime(0,0,0,date('n',$ts1),date('j',$ts1),date('Y',$ts1)-$i))
                 . ' to '
                 . date('F j, Y', mktime(0,0,0,date('n',$ts2),date('j',$ts2),date('Y',$ts2)-$i));
            $ret[] = $line;
        }

        return $ret;
    }

    public function fetch_report_data()
    {
        try {
            $store = $this->form->store;
            $super = $this->form->super;
            $years = $this->form->years;
            $date1 = $this->form->date1;
            $date2 = $this->form->date2;
        } catch (Exception $ex) {
            return array();
        }

        $ts1 = strtotime($date1);
        $ts2 = strtotime($date2);
        $warehouse = $this->config->get('PLUGIN_SETTINGS');
        $warehouse = $warehouse['WarehouseDatabase'];
        $warehouse .= $this->connection->sep();

        $query = "SELECT 
            d.date_id,
            sum(d.total) as total, SUM(d.quantity) AS qty
        FROM {$warehouse}sumDeptSalesByDay AS d
            INNER JOIN MasterSuperDepts AS m ON d.department=m.dept_ID
        WHERE d.date_id BETWEEN ? AND ?
            AND " . DTrans::isStoreID($store, 'd') . "
            AND m.superID=?
        GROUP BY d.date_id";
        $prep = $this->connection->prepare($query);
        $depts = array();
        $reports = array();
        for ($i=0; $i<$years; $i++) {
            $idStart = date('Ymd', mktime(0,0,0,date('n',$ts1),date('j',$ts1),date('Y',$ts1)-$i));
            $idEnd = date('Ymd', mktime(0,0,0,date('n',$ts2),date('j',$ts2),date('Y',$ts2)-$i));
            $res = $this->connection->execute($prep, array($idStart, $idEnd, $store, $super));
            $report = array();
            while ($row = $this->connection->fetchRow($res)) {
                $report[] = array(
                    date('Y-m-d', strtotime($row['date_id'])),
                    sprintf('%.2f', $row['qty']),
                    sprintf('%.2f', $row['total']),
                );
            }
            $reports[] = $report;
            $this->report_headers[] = 'Date';
            $this->report_headers[] = 'Qty';
            $this->report_headers[] = '$';
        }

        $realData = $reports[0];
        for ($i=1; $i<count($reports); $i++) {
            for ($j=0; $j<count($realData); $j++) {
                $realData[$j][] = $reports[$i][$j][0];
                $realData[$j][] = $reports[$i][$j][1];
                $realData[$j][] = $reports[$i][$j][2];
            }
        }

        return $realData;
    }

    public function calculate_footers($data)
    {
        $sumCol = function($arr, $i) {
            $ret = 0;
            foreach ($arr as $a) {
                $ret += $a[$i];
            }
            return $ret;
        };
        $ret = array('Total', $sumCol($data, 1), $sumCol($data, 2));

        $i = 3;
        while (isset($data[0][$i])) {
            $ret[] = '';
            $ret[] = $sumCol($data, $i+1);
            $ret[] = $sumCol($data, $i+2);
            $i += 3;
        }

        return $ret;
    }

    public function graphHTML()
    {
        $this->addOnloadCommand('reDrawGraph();');
        return <<<HTML
<div class="col-sm-11">
    <p>
        <canvas id="graphCanvas"></canvas>
    </p>
</div>
HTML;
    }

    public function graphJS()
    {
        return <<<JAVASCRIPT
function reDrawGraph() {
    var points = [];
    var xLabels = [];
    var labels = [];
    $('td.reportColumn0').each(function() {
        xLabels.push($(this).text());
    });
    var i = 2;
    var count = 0;
    while (true) {
        var next = $('td.reportColumn'+i);
        if (next.length == 0) {
            break;
        }
        var dataSet = [];
        $('td.reportColumn'+i).each(function() {
            dataSet.push($(this).text());
        });
        points.push(dataSet);

        if (count == 0) {
            labels.push('This year');
        } else if (count == 1) {
            labels.push('1 year ago');
        } else {
            labels.push(count + ' years ago');
        }

        i += 3;
        count++;
    }
    CoreChart.lineChart('graphCanvas', xLabels, points, labels);
}
JAVASCRIPT;
    }

    public function form_content()
    {
        $stores = FormLib::storePicker();
        $model = new MasterSuperDeptsModel($this->connection);
        $mOpts = $model->toOptions();
        $dates = FormLib::standardDateFields();
        return <<<HTML
<form method="get">
    <div class="col-sm-5">
        <div class="form-group">
            <label>Store</label>
            {$stores['html']}
        </div>
        <div class="form-group">
            <label>Super Department</label>
            <select name="super" class="form-control">{$mOpts}</select>
        </div>
        <div class="form-group">
            <label>Years</label>
            <select name="years" class="form-control">
                <option>3</option>
                <option>4</option>
                <option>5</option>
            </select>
        </div>
        <div class="form-group">
            <input type="hidden" name="year" value="0" />
            <input type="hidden" name="month" value="0" />
            <button type="submit" class="btn btn-default">Get Report</button>
        </div>
    </div>
    {$dates}
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

