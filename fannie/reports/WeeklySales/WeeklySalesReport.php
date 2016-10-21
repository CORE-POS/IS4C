<?php
include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__) . '/../../classlib2.0/FannieAPI.php');
}

class WeeklySalesReport extends FannieReportPage
{

    public $themed = true;
    public $description = '[Weekly Sales] list one big total sales number per week';
    public $report_set = 'Sales Reports';
    protected $title = 'Weekly Sales';
    protected $header = 'Weekly Sales';
    protected $required_fields = array('date1', 'date2');
    protected $report_headers = array('Week', 'Sales');
    protected $report_cache = 'day';

    public function fetch_report_data()
    {
        $date1 = $this->form->date1;
        $date2 = $this->form->date2;

        $dlog = DTransactionsModel::selectDlog($date1, $date2);

        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $m = new MasterSuperDeptsModel($dbc);
        $m->superID(0);
        $dept_list = '?,';
        $args = array(0);
        foreach ($m->find() as $obj) {
            $dept_list .= '?,';
            $args[] = $obj->dept_ID();
        }
        $dept_list = substr($dept_list, 0, strlen($dept_list)-1);

        $prep = $dbc->prepare('
            SELECT SUM(d.total) AS ttl
            FROM ' . $dlog . ' AS d
            WHERE d.department NOT IN (' . $dept_list . ')
                AND d.trans_type IN (\'I\',\'D\')
                AND d.tdate BETWEEN ? AND ?');

        $start = strtotime($date1);
        $end = strtotime($date2);
        $data = array();
        $i = 0;
        while ($start <= $end) {
            $d1 = date('Y-m-d 00:00:00', $start);
            $d2 = date('Y-m-d 23:59:59', mktime(0,0,0,date('n',$start),date('j',$start)+6,date('Y',$start)));

            $record = array(
                date('Y-m-d', strtotime($d1))
                . ' to ' .
                date('Y-m-d', strtotime($d2)),
            );
            $week_args = array_merge($args, array($d1, $d2));
            $result = $dbc->execute($prep, $week_args);
            $row = $dbc->fetch_row($result);
            $record[] = sprintf('%.2f', $row['ttl']);
            $data[] = $record;

            $start = mktime(0,0,0,date('n',$start),date('j',$start)+7,date('Y',$start));
        }

        return $data;
    }

    public function form_content()
    {
        return '<form action="' . $_SERVER['PHP_SELF'] . '" method="get">
            <div class="row">
            <div class="col-sm-5">
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="text" id="date1" name="date1" class="form-control date-field" required />
                </div>
                <div class="form-group">
                    <label>End Date</label>
                    <input type="text" id="date2" name="date2" class="form-control date-field" required />
                </div>
            </div>
            <div class="col-sm-5">
                ' . FormLib::dateRangePicker() . '
            </div>
            </div>
            <p>
                <button type="submit" class="btn btn-default">Get Report</button>
            </p>
            </form>';
    }

    public function helpContent()
    {
        return '<p>
            Lists total sales for each week in the given date range.
            This is simply one (hopefully) big number per week.
            </p>';
    }
}

FannieDispatch::conditionalExec();

