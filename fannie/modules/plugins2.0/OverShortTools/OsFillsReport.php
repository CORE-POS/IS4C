<?php

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class OsFillsReport extends FannieReportPage 
{
    // 10Nov13 EL Added title and header
    protected $title = 'Over/Short Fills Report';
    protected $header = 'Over/Short Fills Report';
    protected $auth_classes = array('overshorts');
    public $page_set = 'Plugin :: Over/Shorts';
    public $description = '[Fills Report] shows over/short fills info over time';
    public $report_set = 'Finance';
    protected $required_fields = array('date1', 'date2');
    protected $report_headers = array('Date (Latter)', 'Amount');

    public function fetch_report_data()
    {
        try {
            $date1 = $this->form->date1;
            $date2 = $this->form->date2;
            $store = $this->form->store;
        } catch (Exception $ex) {
            return array();
        }
        
        $table = $this->config->get('TRANS_DB') . $this->connection->sep() . 'dailyDeposit';
        $query = "SELECT dateStr, amt
            FROM {$table}
            WHERE rowName='atm'
                AND denomination='fill'";
        $query .= (FormLib::get('store') == 0) ? 'AND storeID <> ?' : 'AND storeID=?';
        $query .= "
                AND UNIX_TIMESTAMP(RIGHT(dateStr, 10)) BETWEEN ? AND ?";
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, array(
            $store,
            strtotime($date1),
            strtotime($date2),
        ));
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $date = substr($row['dateStr'], -10);
            $data[] = array(
                substr($row['dateStr'], -10),
                sprintf('%.2f', $row['amt']),
            );
        }

        return $data;
    }

    public function calculate_footers($data) 
    {
        $sum = array_reduce($data, function($c, $i) { return $c + $i[1]; });
        return array('Total', number_format($sum,2));
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
            <button type="submit" class="btn btn-default btn-core">Submit</button>
        </div>
    </div>
    {$dates}
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

