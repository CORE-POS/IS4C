<?php

include(__DIR__ . '/../../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__).'/../../../../../classlib2.0/FannieAPI.php');
}

class YOYDeptSales extends COREPOS\Fannie\API\FannieGraphReportPage
{
    protected $header = 'Year Over Year Department Sales Report';
    protected $title = 'Year Over Year Department Sales Report';
    public $discoverable = true;
    public $description = '[Year over Year Department Sales] compares several years\' sales by department';
    public $report_set = 'Sales Reports';
    protected $required_fields = array('date1', 'date2', 'years', 'super', 'store');
    protected $report_headers = array('Dept#', 'Dept');
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
            d.department,
            t.dept_name,
            sum(d.total) as total, SUM(d.quantity) AS qty
        FROM {$warehouse}sumDeptSalesByDay AS d
            INNER JOIN MasterSuperDepts AS m ON d.department=m.dept_ID
            LEFT JOIN departments AS t ON d.department=t.dept_no
        WHERE d.date_id BETWEEN ? AND ?
            AND " . DTrans::isStoreID($store, 'd') . "
            AND m.superID=?
        GROUP BY d.department, t.dept_name";
        $prep = $this->connection->prepare($query);
        $data = array();
        $depts = array();
        for ($i=0; $i<$years; $i++) {
            $idStart = date('Ymd', mktime(0,0,0,date('n',$ts1),date('j',$ts1),date('Y',$ts1)-$i));
            $idEnd = date('Ymd', mktime(0,0,0,date('n',$ts2),date('j',$ts2),date('Y',$ts2)-$i));
            $res = $this->connection->execute($prep, array($idStart, $idEnd, $store, $super));
            while ($row = $this->connection->fetchRow($res)) {
                $key = $row['department'] . '::' . $i;
                $depts[$row['department']] = true;
                $data[$key] = array(
                    $row['department'],
                    $row['dept_name'],
                    sprintf('%d', $row['qty']),
                    sprintf('%.2f', $row['total']),
                );
            }
            if ($i == 0) {
                $this->report_headers[] = 'Qty This Year';
                $this->report_headers[] = '$ This Year';
            } elseif ($i == 1) {
                $this->report_headers[] = 'Qty 1 Year Ago';
                $this->report_headers[] = '$ 1 Year Ago';
            } else {
                $this->report_headers[] = "Qty {$i} Years Ago";
                $this->report_headers[] = "\$ {$i} Years Ago";
            }
        }
        $realData = array();
        foreach ($depts as $dept => $nothing) {
            $record = array(
                $dept,
                isset($data[$dept . '::0'][1]) ? $data[$dept . '::0'][1] :  '??',
            );
            for ($i=0; $i<$years; $i++) {
                $record[] = isset($data[$dept . '::' . $i][2]) ? $data[$dept . '::' . $i][2] : 0;
                $record[] = isset($data[$dept . '::' . $i][3]) ? $data[$dept . '::' . $i][3] : 0;
            }
            $realData[] = $record;
        }

        return $realData;
    }

    public function calculate_footers($data)
    {
        $sums = array();
        for ($i=0; $i<count($data[0])-2; $i++) {
            $sums[] = 0;
        } 
        foreach ($data as $row) {
            for ($i=2; $i<count($row); $i++) {
                $sums[$i-2] += $row[$i];
            }
        }

        return array_merge(array('Total', ''), $sums);
    }

    public function graphHTML()
    {
        $this->addOnloadCommand('initSelect();');
        $this->addOnloadCommand('reDrawGraph();');
        $this->addOnloadCommand("\$('tbody td').click(clickTable);");
        return <<<HTML
<div class="col-sm-11">
    <p>
        <select id="graphRowSelect" onchange="reDrawGraph();"></select>
    </p>
    <p>
        <canvas id="graphCanvas"></canvas>
    </p>
</div>
HTML;
    }

    public function graphJS()
    {
        return <<<JAVASCRIPT
function initSelect() {
    var opts = '';
    $('tbody td.reportColumn1').each(function() {
        opts += '<option>' + $(this).html() + '</option>';
    });
    $('#graphRowSelect').html(opts);
}
function reDrawGraph() {
    var selected = $('#graphRowSelect').val();
    var elem = false;
    $('tbody td.reportColumn1').each(function() {
        if ($(this).html().trim() == selected) {
            elem = $(this).closest('tr').get(0);
        }
    });
    if (elem) {
        var points = [];
        var xLabels = [];
        var i = 3;
        var year = 0;
        while (true) {
            var next = $(elem).find('td.reportColumn'+i);
            if (next.length == 0) {
                break;
            }
            points.push(Number(next.html().trim()));
            i += 2;
            if (year == 0) {
                xLabels.push('This year');
            } else if (year == 1) {
                xLabels.push('1 year ago');
            } else {
                xLabels.push(year + ' years ago');
            }
            year++;
        }
        var labels = [selected];
        CoreChart.lineChart('graphCanvas', xLabels, [points], labels);
    } else {
        $('#graphCanvas').html('');
    }
}

function clickTable(ev) {
    var elem = ev.target;
    var dept = $(elem).closest('tr').find('td.reportColumn1').html().trim();
    $('#graphRowSelect').val(dept);
    reDrawGraph();
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

