<?php

use COREPOS\Fannie\API\data\DataCache;

include(dirname(__FILE__).'/../../../../config.php');
if (!class_exists('\\FannieAPI')) {
    include(__DIR__ . '/../../../../classlib2.0/FannieAPI.php');
}

class EstMarginReport extends FannieRESTfulPage 
{
    protected $title = 'Estimated Margin Report';
    protected $header = 'Estimated Margin Report';

    protected function get_id_view()
    {
        $deptP = $this->connection->prepare("SELECT dept_ID FROM superdepts WHERE superID=?");
        $depts = $this->connection->getAllValues($deptP, array($this->id));
        list($inStr, $args) = $this->connection->safeInClause($depts);
        $args[] = date('Ymd', strtotime(FormLib::get('date1')));
        $args[] = date('Ymd', strtotime(FormLib::get('date2')));
        $args[] = FormLib::get('store');

        $prep = $this->connection->prepare("
            SELECT t.*, d.dept_name
            FROM " . FannieDB::fqn('EstMarginByDay', 'plugin:WarehouseDatabase') . " AS t
                LEFT JOIN departments AS d ON t.deptID=d.dept_no
            WHERE deptID IN ({$inStr})
                AND dateID BETWEEN ? AND ?
                AND storeID=?
            ORDER BY dateID");
        $all = array(
            'cost' => 0,
            'total' => 0,
            'nocostSales' => 0,
            'margin' => 0,
            'perDay' => array(),
        );
        $perDept = array();
        $res = $this->connection->execute($prep, $args);
        while ($row = $this->connection->fetchRow($res)) {
            $deptID = $row['deptID'];
            $dateID = $row['dateID'];
            if (!isset($perDept[$deptID])) {
                $perDept[$deptID] = array(
                    'name' => $deptID . ' ' . $row['dept_name'],
                    'cost' => 0,
                    'total' => 0,
                    'nocostSales' => 0,
                    'margin' => 0,
                    'perDay' => array(),
                );
            }
            if (!isset($all['perDay'][$dateID])) {
                $all['perDay'][$dateID] = array(
                    'cost' => 0,
                    'total' => 0,
                    'nocostSales' => 0,
                    'margin' => 0,
                );
            }
            $all['cost'] += $row['costTotal'];
            $all['total'] += $row['retailTotal'];
            $all['nocostSales'] += $row['noCostTotal'];

            $all['perDay'][$dateID]['cost'] += $row['costTotal'];
            $all['perDay'][$dateID]['total'] += $row['retailTotal'];
            $all['perDay'][$dateID]['nocostSales'] += $row['noCostTotal'];

            $perDept[$deptID]['cost'] += $row['costTotal'];
            $perDept[$deptID]['total'] += $row['retailTotal'];
            $perDept[$deptID]['nocostSales'] += $row['noCostTotal'];
            $perDept[$deptID]['perDay'][$dateID] = array(
                'cost' => $row['costTotal'],
                'total' => $row['retailTotal'],
                'nocostSales' => $row['noCostTotal'],
                'margin' => $row['margin'] * 100,
            );
        }
        $all['margin'] = round(($all['total'] - $all['cost']) / $all['total'] * 100, 2);
        $depts = array_keys($perDept);
        foreach ($depts as $d) {
            $perDept[$d]['margin'] = ($perDept[$d]['total'] - $perDept[$d]['cost']) / $perDept[$d]['total'] * 100;
            if (is_nan($perDept[$d]['margin'])) {
                $perDept[$d]['margin'] = 0;
            }
        }
        $days = array_keys($all['perDay']);
        foreach ($days as $d) {
            $all['perDay'][$d]['margin'] = ($all['perDay'][$d]['total'] - $all['perDay'][$d]['cost']) / $all['perDay'][$d]['total'] * 100;
            if (is_nan($all['perDay'][$d]['margin'])) {
                $all['perDay'][$d]['margin'] = 0;
            }
        }

        $table = '';
        foreach ($perDept as $id => $row) {
            $css = '';
            if ($row['nocostSales'] > $row['total']) {
                $css = 'class="danger"';
            } elseif ($row['nocostSales'] != 0) {
                $css = 'class="info"';
            }
            $table .= sprintf('<tr><td>%s</td><td>%.2f%%</td><td>%.2f</td><td>%.2f</td><td %s>%.2f</td>
                    <td><input type="checkbox" class="graphDept" value="%d" onchange="estMargin.reChart();" /></td></tr>',
                $row['name'],
                $row['margin'],
                $row['cost'],
                $row['total'],
                $css,
                $row['nocostSales'],
                $id
            );
        }
        $allJSON = json_encode($all['perDay']);
        $deptJSON = json_encode($perDept);

        $all['cost'] = number_format($all['cost'], 2);
        $costlessShare = $all['nocostSales'] / ($all['total'] + $all['nocostSales']);
        $all['total'] = number_format($all['total'], 2);
        $all['nocostSales'] = number_format($all['nocostSales'], 2) . sprintf(' (%.2f%%)', $costlessShare * 100);

        $this->addScript('../../../../src/javascript/tablesorter/jquery.tablesorter.js');
        $this->addScript('../../../../src/javascript/Chart.min.js');
        $this->addScript('../../../../src/javascript/CoreChart.js');
        $this->addScript('estMargin.js?date=20190315');
        $this->addOnloadCommand("estMargin.init({$allJSON}, {$deptJSON});");
        $this->addOnloadCommand("\$('#deptTable').tablesorter();");
        return <<<HTML
<h3>Summary</h3>
<table class="table table-bordered">
    <tr><th>Est. Margin</th><td>{$all['margin']}%</td></tr>
    <tr><th>Cost</th><td>{$all['cost']}</td></tr>
    <tr><th>Total</th><td>{$all['total']}</td></tr>
    <tr><th>Cost-less Total</th><td>{$all['nocostSales']}</td></tr>
</table>
<p id="chartPara"></p>
<h3>By Department</h3>
<table class="table table-bordered table-striped small" id="deptTable">
    <thead>
    <tr>
        <th>Name</th>
        <th>Est. Margin</th>
        <th>Cost</th>
        <th>Total</th>
        <th>Cost-less Total</th>
    </tr>
    </thead>
    <tbody>
    {$table}
    </tbody>
</table>
HTML;
    }

    public function css_content()
    {
        return '
                #deptTable thead th {
                    cursor: hand;
                    cursor: pointer;
                }
            ';
    }
    
    protected function get_view()
    {
        $model = new SuperDeptNamesModel($this->connection);
        $opts = $model->toOptions();
        $stores = FormLib::storePicker();
        $dates = FormLib::standardDateFields();

        return <<<HTML
<form method="get">
    <div class="col-sm-5">
        <label>Super Department</label>
        <select class="form-control" name="id">{$opts}</select>
        <label>Store</label>
        {$stores['html']}
        <br />
        <button type="submit" class="btn btn-default">Submit</button>
    </div>
    {$dates}
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

