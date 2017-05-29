<?php

include(__DIR__ . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
}

class FairTradeSalesReport extends FannieReportPage
{
    protected $header = 'Fair Trade Sales Report';
    protected $title = 'Fair Trade Sales Report';
    protected $required_fields = array('date1', 'date2');
    protected $report_headers = array('Dept#', 'Dept Name', 'Fair Trade Sales', 'Fair Trade %', 'All Sales');
    public $description = '[Fair Trade Sales] lists fair trade sales volume and percent of total sales';
    public $report_set = 'Operational Data';

    function fetch_report_data()
    {
        $dbc = $this->connection;
        $group = FormLib::get('group', 'Super Department');
        switch ($group) {
            case 'Super Department':
            default:
                $cols = 'm.superID, m.super_name';
                break;
            case 'Department':
                $cols = 't.department, d.dept_name';
                break;
        }
        $date1 = $this->form->date1;
        $date2 = $this->form->date2;
        $store = FormLib::get('store');
        $dlog = DTransactionsModel::selectDlog($date1, $date2);

        // get all organic UPCs
        $upcR = $dbc->query("
            SELECT upc
            FROM products
            WHERE 1 & numflag <> 0
            GROUP BY upc");
        $upcs = array();
        while ($row = $dbc->fetchRow($upcR)) {
            $upcs[] = $row['upc'];
        }

        list($inStr, $args) = $dbc->safeInClause($upcs);
        $args[] = $date1 . ' 00:00:00';
        $args[] = $date2 . ' 23:59:59';
        $args[] = $store;

        $query = "
            SELECT {$cols},
                SUM(t.total) AS ttl,
                SUM(CASE WHEN t.upc IN ({$inStr}) THEN total ELSE 0 END) AS ftTTL
            FROM {$dlog} AS t "
                . DTrans::joinProducts() . "
                LEFT JOIN departments AS d ON d.dept_no=t.department
                INNER JOIN MasterSuperDepts AS m ON m.dept_ID=t.department
            WHERE tdate BETWEEN ? AND ?
                AND " . DTrans::isStoreID($store, 't') . " 
                AND t.trans_type IN ('I', 'D')
                AND m.superID > 0
            GROUP BY {$cols}";
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $args);
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $record = array();
            $record[] = $row[0];
            $record[] = $row[1];
            $record[] = sprintf('%.2f', $row['ftTTL']);
            $record[] = sprintf('%.2f%%', $row['ftTTL'] / $row['ttl'] * 100);
            $record[] = sprintf('%.2f', $row['ttl']);
            $data[] = $record;
        }

        return $data;
    }

    function calculate_footers($data)
    {
        $sums = array(0, 0);
        foreach ($data as $row) {
            $sums[0] += $row[2];
            $sums[1] += $row[4];
        }

        return array('Total', null, sprintf('%.2f', $sums[0]), sprintf('%.2f%%', $sums[0] / $sums[1] * 100), sprintf('%.2f', $sums[1]));
    }

    function form_content()
    {
        $dates = FormLib::standardDateFields();
        $stores = FormLib::storePicker();
return <<<HTML
<form method="get">
<div class="row">
    <div class="col-sm-6">
        <div class="form-group">
            <label>Group by</label>
            <select name="group" class="form-control">
                <option>Super Department</option>
                <option>Department</option>
            </select>
        </div>
        <div class="form-group">
            <label>Store</label>
            {$stores['html']}
        </div>
    </div>
    {$dates}
</div>
<p>
    <button type="submit" class="btn btn-default btn-core">Submit</button>
</p>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

