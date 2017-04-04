<?php

include(__DIR__ . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class CashierTransactionsReport extends FannieReportPage
{
    protected $required_fields = array('date1', 'date2');
    protected $title = 'Cashier Transactions Report';
    protected $header = 'Cashier Transactions Report';
    public $description = '[Cashier Transactions Report] lists the number of transactions by each cashier in a given time period';
    public $report_set = 'Cashiering';
    protected $report_headers = array('Emp#', 'Name', 'Transaction Count');

    function fetch_report_data()
    {
        try {
            $date1 = $this->form->date1;
            $date2 = $this->form->date2;
            $ts1 = strtotime($date1);
            $ts2 = strtotime($date2);
            $startH = $this->form->hour1;
            if ($startH == 12 && $this->form->ampm1 == 'AM') {
                $startH = 0;
            } elseif ($startH != 12 && $this->form->ampm1 == 'PM') {
                $startH += 12;
            } 
            $endH = $this->form->hour2;
            if ($endH == 12 && $this->form->ampm2 == 'AM') {
                $endH = 0;
            } elseif ($endH != 12 && $this->form->ampm2 == 'PM') {
                $endH += 12;
            } 
            $realStart = mktime($startH, $this->form->minute1, 0, date('n',$ts1), date('j',$ts1), date('Y',$ts1));
            $realEnd = mktime($endH, $this->form->minute2, 0, date('n',$ts2), date('j',$ts2), date('Y',$ts2));
        } catch (Exception $ex) {
            return array();
        }

        $dlog = DTransactionsModel::selectDlog($date1, $date2);
        $store = FormLib::get('store', 0);

        $query = $this->connection->prepare("
            SELECT e.FirstName,
                e.LastName,
                d.emp_no,
                YEAR(tdate),
                MONTH(tdate),
                DAY(tdate),
                trans_num
            FROM {$dlog} AS d
                LEFT JOIN employees AS e ON d.emp_no=e.emp_no
            WHERE tdate BETWEEN ? AND ?
                AND " . DTrans::isStoreID($store, 'd') . "
            GROUP BY 
                e.FirstName,
                e.LastName,
                YEAR(tdate),
                MONTH(tdate),
                DAY(tdate),
                trans_num");
        $emps = array();
        $args = array(
            date('Y-m-d H:i:s', $realStart),
            date('Y-m-d H:i:s', $realEnd),
            $store,
        );
        $res = $this->connection->execute($query, $args);
        $ttl = $this->connection->numRows($res);
        /**
          Marker is picker a winner 
          where each transaction is one entry in the
          virtual drawing
        */
        $mark = rand(1, $ttl);
        $counter = 1;
        while ($row = $this->connection->fetchRow($res)) {
            $eID = $row['emp_no'];
            if (!isset($emps[$eID])) {
                $emps[$eID] = array($eID, $row['FirstName'] . ' ' . $row['LastName'], 0);
            }
            $emps[$eID][2] += 1;
            if ($counter == $mark) {
                $emps[$eID][1] .= '***';
            }
            $counter++;
        }

        return $this->dekey_array($emps);
    }

    function form_content()
    {
        $startH = '';
        $endH = '';
        foreach (range(1, 12) as $h) {
            $startH .= sprintf('<option %s value="%d">%s</option>',
                ($h == 12 ? 'selected' : ''), $h, $h);
            $endH .= sprintf('<option %s value="%d">%s</option>',
                ($h == 11 ? 'selected' : ''), $h, $h);
        }
        $startM = '';
        $endM = '';
        foreach (range(0, 59) as $m) {
            $startM .= sprintf('<option %s value="%d">%02d</option>',
                ($m == 0 ? 'selected' : ''), $m, $m);
            $endM .= sprintf('<option %s value="%d">%02d</option>',
                ($m == 59 ? 'selected' : ''), $m, $m);
        }
        $startAP = '<option selected>AM</option><option>PM</option>';
        $endAP = '<option>AM</option><option selected>PM</option>';
        $picker = FormLib::dateRangePicker();
        $stores = FormLib::storePicker();

        return <<<HTML
<form method="get">
    <div class="col-sm-6">
        <div class="form-group">
            <label>Start</label>
            <div class="form-inline">
                <input type="text" class="form-control date-field" id="date1" name="date1" />
                <select name="hour1" class="form-control">{$startH}</select> 
                <select name="minute1" class="form-control">{$startM}</select> 
                <select name="ampm1" class="form-control">{$startAP}</select> 
            </div>
        </div>
        <div class="form-group">
            <label>End</label>
            <div class="form-inline">
                <input type="text" class="form-control date-field" id="date2" name="date2" />
                <select name="hour2" class="form-control">{$endH}</select> 
                <select name="minute2" class="form-control">{$endM}</select> 
                <select name="ampm2" class="form-control">{$endAP}</select> 
            </div>
        </div>
        <div class="form-group">
            <label>Store</label>
            {$stores['html']}
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-default btn-core">Submit</button>
        </div>
    </div>
    <div class="col-sm-6">
        {$picker}
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

