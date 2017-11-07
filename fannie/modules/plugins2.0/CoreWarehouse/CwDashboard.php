<?php

use COREPOS\Fannie\API\item\ItemText;

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class CwDashboard extends FannieRESTfulPage
{
    protected $title = 'Core Warehouse : Dashboard';
    protected $header = 'Core Warehouse : Dashboard';
    public $description = '[Data Warehouse Dashboard] shows an overview of sales data';

    protected $store_id = 0;
    protected $dStart = false;
    protected $dEnd = false;

    public function preprocess()
    {
        if (FormLib::get('bare')) {
            $this->window_dressing = false;
            $this->addJQuery();
            $this->addBootstrap();
        }

        return parent::preprocess();
    }

    protected function get_id_view()
    {
        $this->store_id = $this->id;
        if (FormLib::get('dStart') && FormLib::get('dEnd')) {
            $this->dStart = FormLib::get('dStart');
            $this->dEnd = FormLib::get('dEnd');
        }

        return $this->get_view();
    }

    protected function get_view()
    {
        $this->addScript($this->config->get('URL') . 'src/javascript/Chart.min.js');
        $this->addScript('cwDash.js');
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $prefix = $settings['WarehouseDatabase'] . $this->connection->sep();

        $dateStart = date('Ymd', strtotime($this->dStart ? $this->dStart : '8 days ago'));
        $dateEnd = date('Ymd', strtotime($this->dEnd ? $this->dEnd : 'yesterday'));
        $storeOp = $this->store_id == 0 ? '<>' : '=';

        $query = "SELECT SUM(s.quantity) AS qty,
                " . ItemText::longBrandSQL() . ",
                " . ItemText::longDescriptionSQL() . "
            FROM {$prefix}sumUpcSalesByDay AS s
                LEFT JOIN products AS p ON s.upc=p.upc AND s.store_id=p.store_id
                LEFT JOIN productUser AS u ON s.upc=u.upc
            WHERE date_id BETWEEN ? AND ?
                AND s.store_id {$storeOp} ?
                AND s.total > 0
            GROUP BY s.upc, brand, description
            ORDER BY SUM(s.quantity) DESC";
        $query = $this->connection->addSelectLimit($query, 25);
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, array($dateStart, $dateEnd, $this->store_id));
        $top25 = '';
        $rank = 1;
        while ($row = $this->connection->fetchRow($res)) {
            $top25 .= sprintf('<tr><td>#%d</td><td>%s</td><td>%.2f</td></tr>',
                $rank, $row['brand'] . ' ' . $row['description'], $row['qty']);
            $rank++;
        }

        $query = "SELECT date_id,
            " . $this->connection->hour('end_time') . " AS hr,
            COUNT(*) AS trans
            FROM {$prefix}transactionSummary
            WHERE date_id BETWEEN ? AND ?
                AND store_id {$storeOp} ?
                AND tenderTotal <> 0
            GROUP BY date_id, hr
            ORDER BY date_id, COUNT(*)";
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, array($dateStart, $dateEnd, $this->store_id));
        $busy = array();
        while ($row = $this->connection->fetchRow($res)) {
            $date = $row['date_id'];
            if (!isset($busy[$date])) {
                $busy[$date] = array('first'=>0, 'second'=>0);
            }
            if ($row['trans'] > $busy[$date]['first']) {
                $busy[$date]['second'] = $busy[$date]['first'];
                $busy[$date]['first'] = $row['hr'];
            }
        }
        $first = array();
        $second = array();
        foreach ($busy as $date => $info) {
            if (!isset($first[$info['first']])) {
                $first[$info['first']] = 0;
            }
            $first[$info['first']]++;
            if (!isset($second[$info['second']])) {
                $second[$info['second']] = 0;
            }
            $second[$info['second']]++;
        }
        $niceHour = function($hour) {
            if ($hour == 12) return "12pm";
            return $hour > 12 ? ($hour-12) . "pm" : $hour . "am";
        };
        $firstPie = array('labels'=>array(), 'data'=>array(), 'title' => 'Busiest Hour');
        foreach ($first as $hour => $tally) {
            $firstPie['labels'][] = $niceHour($hour);
            $firstPie['data'][] = $tally;
        }
        $secondPie = array('labels'=>array(), 'data'=>array(), 'title' => '2nd Busiest');
        foreach ($second as $hour => $tally) {
            $secondPie['labels'][] = $niceHour($hour);
            $secondPie['data'][] = $tally;
        }

        $query = "SELECT date_id,
            SUM(retailTotal) AS sales,
            COUNT(trans_num) AS transactions,
            SUM(CASE WHEN m.custdataType='PC' THEN 1 ELSE 0 END) AS members
            FROM {$prefix}transactionSummary AS t
                LEFT JOIN memtype AS m ON t.memType=m.memtype
            WHERE date_id BETWEEN ? AND ?
                AND store_id {$storeOp} ?
                AND (tenderTotal <> 0)
            GROUP BY date_id
            ORDER BY date_id";
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, array($dateStart, $dateEnd, $this->store_id));

        $salesData = array('labels' => array(), 'points' => array());
        $custData = array('labels' => array(), 'total' => array(), 'members' => array());
        while ($row = $this->connection->fetchRow($res)) {
            $date = date('Y-m-d', strtotime($row['date_id']));
            $salesData['labels'][] = $date;
            $salesData['points'][] = array('x'=>$date, 'y'=>$row['sales']);
            $custData['labels'][] = $date;
            $custData['total'][] = $row['transactions'];
            $custData['members'][] = $row['members'];
        }

        $salesData = json_encode($salesData);
        $custData = json_encode($custData);
        $firstPie = json_encode($firstPie);
        $secondPie = json_encode($secondPie);
        $this->addOnloadCommand("cwDash.salesChart({$salesData});");
        $this->addOnloadCommand("cwDash.customersChart({$custData});");
        $this->addOnloadCommand("cwDash.pieChart('busyHour', {$firstPie});");
        $this->addOnloadCommand("cwDash.pieChart('nextHour', {$secondPie});");
        $stores = FormLib::storePicker('id');

        $bareHead = FormLib::get('bare') ? '<html><head><link rel="stylesheet" type="text/css" href="../../../src/javascript/composer-components/bootstrap/css/bootstrap.min.css"></head><body><div class="container-fluid">' : '';
        $bareTail = FormLib::get('bare') ? '</div></body></html>' : '';

        return <<<HTML
{$bareHead}
<div class="row">
    <div class="col-sm-5">
        <canvas id="recentSales"></canvas>
    </div>
    <div class="col-sm-5">
        <canvas id="customerCount"></canvas>
    </div>
</div>
<div class="row">
    <div class="col-sm-5">
        <canvas id="busyHour"></canvas>
    </div>
    <div class="col-sm-5">
        <canvas id="nextHour"></canvas>
    </div>
</div>
<div class="row">
    <div class="col-sm-10">
        <table class="table table-striped table-bordered">
        <thead><tr><th class="text-center" colspan="3">Top Sellers (by quantity)</th></tr></thead>
        <tbody>
            {$top25}
        </tbody>
        </table>
    </div>
</div>
<div class="panel panel-default">
    <div class="panel-heading">Change View</div>
    <div class="panel-body">
        <form method="get">
            <div class="form-group">
                <label>Store</label>
                {$stores['html']}
            </div>
            <div class="form-group">
                <label>Start</label>
                <input type="text" name="dStart" class="form-control date-field " />
            </div>
            <div class="form-group">
                <label>End</label>
                <input type="text" name="dEnd" class="form-control date-field " />
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-default">View</button>
            </div>
        </form>
    </div>
</div>
{$bareTail}
HTML;
    }
}

FannieDispatch::conditionalExec();

