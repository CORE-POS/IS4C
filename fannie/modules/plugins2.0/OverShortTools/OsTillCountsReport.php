<?php

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class OsTillCountsReport extends FannieReportPage 
{
    protected $must_authenticate = true;

    protected $header = 'Daily Till Counts Report';
    protected $title = 'Daily Till Counts Report';

    protected $required_fields = array('date1', 'date2');
    protected $report_headers = array('Date', 'Drop Count', 'Deposit Count', 'POS');

    public function fetch_report_data()
    {
        $ts1 = strtotime($this->form->date1);
        $ts2 = strtotime($this->form->date2);
        $store = FormLib::get('store');

        $dayP = $this->connection->prepare("SELECT SUM(dropAmount) AS ttl
            FROM " . FannieDB::fqn('DailyTillCounts', 'plugin:OverShortDatabase') . "
            WHERE dateID=? AND " . (str_replace('store_id', 'storeID', DTrans::isStoreID($store))));
        $reconP = $this->connection->prepare("SELECT SUM(amt) AS ttl
            FROM " . FannieDB::fqn('dailyDeposit', 'plugin:OverShortDatabase') . "
            WHERE rowName=? AND " . (str_replace('store_id', 'storeID', DTrans::isStoreID($store))));
        $dlog = DTransactionsModel::selectDlog($this->form->date1, $this->form->date2);
        $dlogP = $this->connection->prepare("SELECT SUM(-1 * total) FROM {$dlog}
            WHERE tdate BETWEEN ? AND ? AND " . DTrans::isStoreID($store) . "
                AND trans_type='T' AND (trans_subtype='CA' OR (trans_subtype='CK' AND description='Check'))");
        $data = array();
        while ($ts1 <= $ts2) {
            $dateID = date('Ymd', $ts1);
            $date = date('Y-m-d', $ts1);
            $day = $this->connection->getValue($dayP, array($dateID, $store));
            $recon = $this->connection->getValue($reconP, array('drop' . $dateID, $store));
            $dlogAmt = $this->connection->getValue($dlogP, array($date, $date . ' 23:59:59', $store));
            $data[] = array(
                $date,
                $day,
                $recon ? $recon : '',
                sprintf('%.2f', $dlogAmt),
            );
            $ts1 = mktime(0,0,0,date('n',$ts1), date('j',$ts1)+1, date('Y',$ts1));
        }

        return $data;
    }

    public function form_content()
    {
        $dates = FormLib::standardDateFields();
        $stores = FormLib::storePicker();

        return <<<HTML
<form method="get">
<div class="col-sm-5">
    <div class="form-group">
    <label>Store</label>
    {$stores['html']}
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Run Report</button>
    </div>
</div>
{$dates}
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

