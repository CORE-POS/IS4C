<?php

include(dirname(__FILE__).'/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../../classlib2.0/FannieAPI.php');
}

class CWMemberProfile extends FannieRESTfulPage
{
    public $description = '[Shopper Profile] lists information about purchase history.
        Requires CoreWarehouse plugin.';
    protected $header = 'Shopper Profile';
    protected $title = 'Shopper Profile';

    protected function post_id_view()
    {
        return $this->get_id_view();
    }

    protected function get_id_view()
    {
        $doAvg = FormLib::get('doAvg', 1);
        $doTop = FormLib::get('doTop', 1);
        $ids = is_array($this->id) ? $this->id : explode("\n", $this->id);
        $ids = array_map('trim', $ids);
        $ids = array_filter($ids, 'is_numeric');
        list($inStr, $args) = $this->connection->safeInClause($ids);

        $maxP = $this->connection->prepare('SELECT MAX(yearTotalSpendingRank)
            FROM ' . FannieDB::fqn('MemberSummary', 'plugin:WarehouseDatabase') . '
            WHERE yearTotalSpending > 0');
        $max = $this->connection->getValue($maxP);
        $top25 = array();
        $allP = $this->connection->prepare('SELECT d.card_no
            FROM ' . FannieDB::fqn('MemberSummary', 'plugin:WarehouseDatabase') . ' AS d
                LEFT JOIN ' . FannieDB::fqn('custdata', 'op') . ' AS c ON d.card_no=c.CardNo AND c.personNum=1
            WHERE c.Type=\'PC\'
                AND d.yearTotalSpendingRank <= ?');
        $max = (int)($max*.25);
        $allR = $this->connection->execute($allP, array($max));
        while ($allW = $this->connection->fetchRow($allR)) {
            $top25[] = $allW['card_no'];
        }

        $endts = strtotime('last month');
        $startts = mktime(0, 0, 0, date('n', $endts)-11, 1, date('Y', $endts));
        $data = array();
        $prep = $this->connection->prepare('
            SELECT COUNT(DISTINCT card_no) AS shoppers,
                SUM(retailTotal) AS ttl,
                SUM(transCount) AS visits
            FROM ' . FannieDB::fqn('sumMemSalesByDay', 'plugin:WarehouseDatabase') . '
            WHERE date_id BETWEEN ? AND ?
                AND card_no IN (' . $inStr . ')');
        $avgQ = 'SELECT COUNT(DISTINCT card_no) AS shoppers,
                SUM(retailTotal) AS ttl,
                SUM(transCount) AS visits
            FROM ' . FannieDB::fqn('sumMemSalesByDay', 'plugin:WarehouseDatabase') . ' AS d
                LEFT JOIN ' . FannieDB::fqn('custdata', 'op') . ' AS c ON d.card_no=c.CardNo and c.personNum=1
            WHERE date_id BETWEEN ? AND ?
                AND c.Type=\'PC\'';
        $avgP = $this->connection->prepare($avgQ);
        list($in25, $args25) = $this->connection->safeInClause($top25);
        $topP = $this->connection->prepare('
            SELECT COUNT(DISTINCT card_no) AS shoppers,
                SUM(retailTotal) AS ttl,
                SUM(transCount) AS visits
            FROM ' . FannieDB::fqn('sumMemSalesByDay', 'plugin:WarehouseDatabase') . '
            WHERE date_id BETWEEN ? AND ?
                AND card_no IN (' . $in25 . ')');
        while ($startts < $endts) {
            $startID = date('Ymd', mktime(0,0,0,date('n',$startts),1,date('Y',$startts)));
            $endID = date('Ymt', mktime(0,0,0,date('n',$startts),1,date('Y',$startts)));
            $key = date('Y-m', $startts);

            $realArgs = $args;
            array_unshift($realArgs, $endID);
            array_unshift($realArgs, $startID);
            $row = $this->connection->getRow($prep, $realArgs);
            $top = array('ttl'=>0, 'visits'=>0, 'shoppers'=>1);
            $data[$key] = array(
                'ttl' => $row['ttl']/$row['shoppers'],
                'visits' => $row['visits']/$row['shoppers'],
                'basket' => $row['ttl'] / $row['visits'],
            );
            if ($doAvg) {
                $avg = $this->connection->getRow($avgP, array($startID, $endID));
                $data[$key]['avgTTL'] = $avg['ttl'] / $avg['shoppers'];
                $data[$key]['avgVisits'] = $avg['visits'] / $avg['shoppers'];
                $data[$key]['avgBasket'] = $avg['ttl'] / $avg['visits'];
            }
            if ($doTop) {
                $realArgs = $args25;
                array_unshift($realArgs, $endID);
                array_unshift($realArgs, $startID);
                $top = $this->connection->getRow($topP, $realArgs);
                $data[$key]['topTTL'] = $top['ttl'] / $top['shoppers'];
                $data[$key]['topVisits'] = $top['visits'] / $top['shoppers'];
                $data[$key]['topBasket'] = $top['ttl'] / $top['visits'];
            }

            $startts = mktime(0, 0, 0, date('n',$startts)+1, 1, date('Y',$startts));
        }

        $ret = '<table class="table table-bordered table-striped">
            <tr><th>Month</th><th>Spending ($)</th><th>Visits</th><th>Avg. Basket</th></tr>';
        foreach ($data as $month => $info) {
            $ret .= sprintf('<tr><th>%s</th><td>%.2f</td><td>%.2f</td><td>%.2f</td></tr>',
                $month, $info['ttl'], $info['visits'], $info['basket']);
        }
        $ret .= '</table>';

        $spend = array_map(function ($i) { return $i['ttl']; }, $data);
        $avgSpend = array_map(function ($i) { return $i['avgTTL']; }, $data);
        $spend25 = array_map(function ($i) { return $i['topTTL']; }, $data);
        $spendJSON = json_encode(array(
            'labels'=>array_keys($spend),
            'values'=>array_values($spend),
            'avg'=>array_values($avgSpend),
            'top'=>array_values($spend25),
        ));
        $visit = array_map(function ($i) { return $i['visits']; }, $data);
        $avgVisit = array_map(function ($i) { return $i['avgVisits']; }, $data);
        $topVisit = array_map(function ($i) { return $i['topVisits']; }, $data);
        $visitJSON = json_encode(array(
            'labels'=>array_keys($visit),
            'values'=>array_values($visit),
            'avg'=>array_values($avgVisit),
            'top'=>array_values($topVisit),
        ));
        $basket = array_map(function ($i) { return $i['basket']; }, $data);
        $avgBasket = array_map(function ($i) { return $i['avgBasket']; }, $data);
        $topBasket = array_map(function ($i) { return $i['topBasket']; }, $data);
        $basketJSON = json_encode(array(
            'labels'=>array_keys($basket),
            'values'=>array_values($basket),
            'avg'=>array_values($avgBasket),
            'top'=>array_values($topBasket),
        ));
        $this->addScript('../../../../src/javascript/Chart.min.js');
        $this->addScript('profile.js');
        $this->addOnloadCommand("cwProfile.drawChart({$spendJSON},{$visitJSON},{$basketJSON});");
        $ret .= <<<HTML
<div class="row">
    <div class="col-sm-6"><canvas id="spendCanvas"></canvas></div>
    <div class="col-sm-6"><canvas id="visitCanvas"></canvas></div>
</div>
<div class="row">
    <div class="col-sm-6"><canvas id="basketCanvas"></canvas></div>
</div>
HTML;

        return $ret;
    }

    protected function get_view()
    {
        return <<<HTML
<form method="get" action="CWMemberProfile.php">
    <div class="form-group">
        <label>Shopper #(s)</label>
        <textarea name="id" rows="10" class="form-control"></textarea>
    </div>
    <div class="form-group">
        <label><input type="checkbox" name="doAvg" value="0" /> Omit average owner</label>
    </div>
    <div class="form-group">
        <label><input type="checkbox" name="doTop" value="0" /> Omit top 25% owner</label>
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Submit</button>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

