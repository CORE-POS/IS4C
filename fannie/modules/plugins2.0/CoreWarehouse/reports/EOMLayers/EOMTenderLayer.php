<?php

include(__DIR__ . '/../../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__).'/../../../../../classlib2.0/FannieAPI.php');
}

class EOMTenderLayer extends FannieReportPage
{
    protected $header = 'Tender Report';
    protected $title = 'Tender Report';
    public $discoverable = false;
    protected $required_fields = array('month', 'year', 'store', 'tender');
    protected $report_headers = array('Date', 'Tender', 'Amount', 'Qty');

    public function fetch_report_data()
    {
        try {
            $month = $this->form->month;
            $year = $this->form->year;
            $store = $this->form->store;
            $tender = $this->form->tender;
        } catch (Exception $ex) {
            return array();
        }

        $tstamp = mktime(0,0,0,$month,1,$year);
        $start = date('Y-m-01', $tstamp);
        $end = date('Y-m-t', $tstamp);
        $idStart = date('Ym01', $tstamp);
        $idEnd = date('Ymt', $tstamp);
        if (FormLib::get('date1') && FormLib::get('date2')) {
            $idStart = date('Ymd', strtotime(FormLib::get('date1')));
            $idEnd = date('Ymd', strtotime(FormLib::get('date2')));
        }
        $warehouse = $this->config->get('PLUGIN_SETTINGS');
        $warehouse = $warehouse['WarehouseDatabase'];
        $warehouse .= $this->connection->sep();

        $query2 = "SELECT 
            d.date_id,
            t.TenderName,
            -sum(d.total) as total, SUM(d.quantity) AS qty
        FROM {$warehouse}sumTendersByDay AS d
            left join tenders as t ON d.trans_subtype=t.TenderCode
        WHERE d.date_id BETWEEN ? AND ?
            AND " . DTrans::isStoreID($store, 'd') . "
            AND d.trans_subtype = ?
        GROUP BY d.date_id, t.TenderName";
        $prep = $this->connection->prepare($query2);
        $res = $this->connection->execute($prep, array($idStart, $idEnd, $store, $tender));
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $date = date('Y-m-d', strtotime($row['date_id']));
            $link = sprintf('<a href="EOMTenderDetail.php?date=%s&store=%d&tender=%s">%s</a>',
                $date, $store, $tender, $date);
            $data[] = array(
                $link,
                $row['TenderName'],
                sprintf('%.2f', $row['total']),
                sprintf('%d', $row['qty']),
            );
        }

        return $data;
    }

    public function calculate_footers($data)
    {
        $sums = array(0, 0);
        foreach ($data as $row) {
            $sums[0] += $row[2];
            $sums[1] += $row[3];
        }

        return array('Total', '', $sums[0], $sums[1]);
    }

    public function form_content()
    {
        $stores = FormLib::storePicker();
        $tenders = new TendersModel($this->connection);
        $tOpts = '';
        foreach ($tenders->find() as $t) {
            $tOpts .= sprintf('<option value="%s">%s</option>', $t->TenderCode(), $t->TenderName());
        }
        $dates = FormLib::standardDateFields();
        return <<<HTML
<form method="get">
    <div class="col-sm-5">
        <div class="form-group">
            <label>Store</label>
            {$stores['html']}
        </div>
        <div class="form-group">
            <label>Tender Type</label>
            <select name="tender" class="form-control">{$tOpts}</select>
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

