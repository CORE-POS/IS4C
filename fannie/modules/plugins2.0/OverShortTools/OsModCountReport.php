<?php

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class OsModCountReport extends FannieReportPage 
{
    // 10Nov13 EL Added title and header
    protected $title = 'Over/Short MOD Count Report';
    protected $header = 'Over/Short MOD Count Report';
    protected $auth_classes = array('overshorts');
    public $page_set = 'Plugin :: Over/Shorts';
    public $description = '[MOD Count Report] shows spot check counts';
    public $report_set = 'Finance';
    protected $required_fields = array('date1', 'date2');
    protected $new_tablesorter = true;
    protected $report_headers = array('Date', 'Emp#', 'Name', 'Count');

    public function fetch_report_data()
    {
        $startID = date('Ymd', strtotime($this->form->date1));
        $endID = date('Ymd', strtotime($this->form->date2));
        $store = FormLib::get('store');

        $query = "SELECT d.dropAmount, d.empNo, e.FirstName, d.dateID
            FROM " . FannieDB::fqn('DailyEmployeeCounts', 'plugin:OverShortDatabase') . " AS d
                LEFT JOIN employees AS e on d.empNo=e.emp_no
            WHERE d.dateID BETWEEN ? AND ?
                AND countType='MOD' ";
        $args = array($startID, $endID);
        if ($store) {
            $query .= " AND d.storeID=? ";
            $args[] = $store;
        }
        $query .= " ORDER BY dateID";
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, $args);
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $data[] = array(
                date('Y-m-d', strtotime($row['dateID'])),
                $row['empNo'],
                $row['FirstName'],
                sprintf('%.2f', $row['dropAmount']),
            );
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
            <button class="btn btn-default btn-core">Submit</button>
        </div>
    </div>
    {$dates}
</form>
HTML;
    }

}

FannieDispatch::conditionalExec();

