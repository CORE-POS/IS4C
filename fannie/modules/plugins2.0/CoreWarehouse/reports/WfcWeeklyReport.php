<?php

include(__DIR__ . '/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../../classlib2.0/FannieAPI.php');
}

class WfcWeeklyReport extends FannieRESTfulPage
{
    protected $header = 'WFC Weekly Report';
    protected $title = 'WFC Weekly Report';

    protected function get_id_view()
    {
        $ts = strtotime($this->id);
        $startID = date('Ymd', $ts);
        $endID = date('Ymd', mktime(0,0,0,date('m',$ts),date('j',$ts)+6,date('Y',$ts)));
        $ret = '';

        $start = date('Y-m-d', strtotime($startID));
        $end = date('Y-m-d', strtotime($endID));
        $dlog = DTransactionsModel::selectDlog($start, $end);
        $excludeNabs = DTrans::memTypeIgnore($this->connection);

        $prep = $this->connection->prepare("SELECT YEAR(tdate), MONTH(tdate), DAY(tdate), SUM(total) AS total
            FROM {$dlog} AS t
                INNER JOIN " . FannieDB::fqn('MasterSuperDepts', 'op') . " AS m ON t.department=m.dept_ID
            WHERE m.superID <> 0
                AND tdate BETWEEN ? AND ?
                AND trans_type IN ('I','D')
                AND t.memType NOT IN {$excludeNabs}
            GROUP BY YEAR(tdate), MONTH(tdate), DAY(tdate)
            ORDER BY YEAR(tdate), MONTH(tdate), DAY(tdate)");
        $res = $this->connection->execute($prep, array($start, $end . ' 23:59:59'));
        $ret .= '<h3>Week of ' . date('D, M jS', strtotime($startID)) . '</h3>';
        $ret .= '<div class="row"><div class="col-sm-4"><h4>Co-op Wide</h4>
            <table class="table table-bordered">';
        $ttl = 0;
        while ($row = $this->connection->fetchRow($res)) {
            $row['date_id'] = date('Ymd', mktime(0,0,0,$row[1],$row[2],$row[0]));
            $ret .= sprintf('<tr><td>%s</td><td>$%s</td></tr>',
                date('D, M jS', strtotime($row['date_id'])),
                number_format($row['total'])
            );
            $ttl += $row['total'];
        }
        $ret .= sprintf('<tr><th>Total</th><th>$%s</th></tr>', number_format($ttl));
        $ret .= '</table></div>';

        $ret .= '<div class="col-sm-4"><h4>Record Days</h4>';
        $query = "SELECT date_id, SUM(total) AS total
            FROM " . FannieDB::fqn('WfcTopDays', 'plugin:WarehouseDatabase') . "
            WHERE category='ALL'
            GROUP BY date_id
            ORDER BY SUM(total) DESC";
        $query = $this->connection->addSelectLimit($query, 8);
        $res = $this->connection->query($query);
        $ret .= '<table class="table table-bordered">';
        while ($row = $this->connection->fetchRow($res)) {
            $ret .= sprintf('<tr><td>%s</td><td>$%s</td></tr>',
                date('D, M jS Y', strtotime($row['date_id'])),
                number_format($row['total'])
            );
        }
        $ret .= '</table></div>';

        $ret .= '<div class="col-sm-4"><h4>Record Weeks</h4>';
        $query = "SELECT date_id, SUM(total) AS total
            FROM " . FannieDB::fqn('WfcTopWeeks', 'plugin:WarehouseDatabase') . "
            WHERE category='ALL'
            GROUP BY date_id
            ORDER BY SUM(total) DESC";
        $query = $this->connection->addSelectLimit($query, 8);
        $res = $this->connection->query($query);
        $ret .= '<table class="table table-bordered">';
        while ($row = $this->connection->fetchRow($res)) {
            $ret .= sprintf('<tr><td>%s</td><td>$%s</td></tr>',
                date('D, M jS Y', strtotime($row['date_id'])),
                number_format($row['total'])
            );
        }
        $ret .= '</table></div></div>';

        foreach (array(1 => 'Hillside', 2 => 'Denfeld') as $storeID => $store) {
            $iter = array(
                'ALL' => $store,
                'GROCERY' => $store . ' Grocery',
                'DELI' => $store . ' Deli',
                'PRODUCE' => $store . ' Produce',
            );
            foreach ($iter as $cat => $title) {
                $superClause = 'm.superID <> 0';
                switch ($cat) {
                case 'DELI':
                    $superClause = 'm.superID IN (3)';
                    break;
                case 'PRODUCE':
                    $superClause = 'm.superID IN (6)';
                    break;
                case 'GROCERY':
                    $superClause = 'm.superID IN (1,4,5,7,8,9,13,17,18)';
                    break;
                }
                $prep = $this->connection->prepare("SELECT YEAR(tdate), MONTH(tdate), DAY(tdate), SUM(total) AS total
                    FROM {$dlog} AS t
                        INNER JOIN " . FannieDB::fqn('MasterSuperDepts', 'op') . " AS m ON t.department=m.dept_ID
                    WHERE {$superClause}
                        AND tdate BETWEEN ? AND ?
                        AND trans_type IN ('I','D')
                        AND store_id=?
                        AND t.memType NOT IN {$excludeNabs}
                    GROUP BY YEAR(tdate), MONTH(tdate), DAY(tdate)
                    ORDER BY YEAR(tdate), MONTH(tdate), DAY(tdate)");
                $res = $this->connection->execute($prep, array($start, $end . ' 23:59:59', $storeID));
                $ret .= '<div class="row"><div class="col-sm-4"><h4>' . $title . '</h4>
                    <table class="table table-bordered">';
                $ttl = 0;
                while ($row = $this->connection->fetchRow($res)) {
                    $row['date_id'] = date('Ymd', mktime(0,0,0,$row[1],$row[2],$row[0]));
                    $ret .= sprintf('<tr><td>%s</td><td>$%s</td></tr>',
                        date('D, M jS', strtotime($row['date_id'])),
                        number_format($row['total'])
                    );
                    $ttl += $row['total'];
                }
                $ret .= sprintf('<tr><th>Total</th><th>$%s</th></tr>', number_format($ttl));
                $ret .= '</table></div>';

                $ret .= '<div class="col-sm-4"><h4>Record Days</h4>';
                $query = "SELECT date_id, total
                    FROM " . FannieDB::fqn('WfcTopDays', 'plugin:WarehouseDatabase') . "
                    WHERE category=?
                        AND store_id=?
                    GROUP BY date_id
                    ORDER BY total DESC";
                $query = $this->connection->addSelectLimit($query, 8);
                $prep = $this->connection->prepare($query);
                $res = $this->connection->execute($prep, array($cat, $storeID));
                $ret .= '<table class="table table-bordered">';
                while ($row = $this->connection->fetchRow($res)) {
                    $ret .= sprintf('<tr><td>%s</td><td>$%s</td></tr>',
                        date('D, M jS Y', strtotime($row['date_id'])),
                        number_format($row['total'])
                    );
                    $ttl += $row['total'];
                }
                $ret .= '</table></div>';

                $ret .= '<div class="col-sm-4"><h4>Record Weeks</h4>';
                $query = "SELECT date_id, total
                    FROM " . FannieDB::fqn('WfcTopWeeks', 'plugin:WarehouseDatabase') . "
                    WHERE category=?
                        AND store_id=?
                    GROUP BY date_id
                    ORDER BY total DESC";
                $query = $this->connection->addSelectLimit($query, 8);
                $prep = $this->connection->prepare($query);
                $res = $this->connection->execute($prep, array($cat, $storeID));
                $ret .= '<table class="table table-bordered">';
                while ($row = $this->connection->fetchRow($res)) {
                    $ret .= sprintf('<tr><td>%s</td><td>$%s</td></tr>',
                        date('D, M jS Y', strtotime($row['date_id'])),
                        number_format($row['total'])
                    );
                    $ttl += $row['total'];
                }
                $ret .= '</table></div></div>';
            }
        }

        $ret .= '<div class="row">';
        foreach (array(1 => 'Hillside', 2 => 'Denfeld') as $storeID => $store) {
            $ret .= '<div class="col-sm-6"><h4>' . $store . ' Top Items</h4>
                <table class="table table-bordered">';
            $query = "SELECT upc, description, sum(total) as ttl
                FROM {$dlog}
                WHERE trans_type='I'
                    AND tdate BETWEEN ? AND ?
                    AND store_id=?
                    AND upc <> '0000000007000'
                GROUP BY upc, description
                ORDER BY SUM(total) DESC";
            $query = $this->connection->addSelectLimit($query, 15);
            $prep = $this->connection->prepare($query);
            $res = $this->connection->execute($prep, array($start, $end . ' 23:59:59', $storeID));
            while ($row = $this->connection->fetchRow($res)) {
                $ret .= sprintf('<tr><td>%s</td></tr>',
                    $row['description']
                );
            }
            $ret .= '</table></div>';
        }
        $ret .= '</div>';

        return $ret;
    }

    protected function get_view()
    {
        $monday = date('Y-m-d', strtotime('last monday'));
        if (date('N') != 1) {
            $ts = strtotime($monday);
            $monday = date('Y-m-d', mktime(0, 0, 0, date('n',$ts), date('j',$ts)-7,date('Y',$ts)));
        }
        return <<<HTML
<form method="get">
    <div class="form-group">
        <label>Week Start</label>
        <input type="text" name="id" value="{$monday}" class="form-control date-field" />
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Get Report</button>
    </div>
</form>
HTML;
    }

}

FannieDispatch::conditionalExec();

