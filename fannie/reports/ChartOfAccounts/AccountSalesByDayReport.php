<?php

use COREPOS\Fannie\API\item\StandardAccounting;

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class AccountSalesByDayReport extends FannieReportPage 
{
    public $description = '[Chart of Account Sales by Day] lists daily sales for each chart of accounts number';
    public $report_set = 'Finance';
    protected $required_fields = array('date1', 'date2');

    protected $title = "Fannie : Chart of Account Sales by Day";
    protected $header = "Chart of Account Sales by Day";
    protected $report_headers = array('Date', 'Account#', '$');

    protected $queueable = true;

    public function report_description_content()
    {
        try {
            $noNabs = $this->form->noNabs;
        } catch (Exception $ex) {
            $noNabs = false;
        }

        if ($noNabs) {
            return array('Nabs excluded');
        }

        return array('Nabs included');
    }

    public function fetch_report_data()
    {
        try {
            $date1 = $this->form->date1;
            $date2 = $this->form->date2;
        } catch (Exception $ex) {
            return array();
        }
        try {
            $store = $this->form->store;
        } catch (Exception $ex) {
            $store = 0;
        }
        try {
            $noNabs = $this->form->noNabs;
        } catch (Exception $ex) {
            $noNabs = false;
        }

        $dlog = DTransactionsModel::selectDlog($date1, $date2);
        $query = "SELECT YEAR(tdate) AS y, MONTH(tdate) AS m, DAY(tdate) AS d, store_id, d.salesCode, SUM(t.total) AS ttl
            FROM $dlog AS t
                INNER JOIN departments AS d ON t.department=d.dept_no
                INNER JOIN MasterSuperDepts AS m ON t.department=m.dept_ID
            WHERE m.superID <> 0
                AND t.tdate BETWEEN ? AND ?
                AND trans_type IN ('I', 'D')
                AND " . DTrans::isStoreID($store, 't');
        if ($noNabs) {
            $ignore = DTrans::memTypeIgnore($this->connection);
            $query .= " AND t.memType NOT IN ({$ignore}) ";
        }
        $query .= " GROUP BY YEAR(tdate), MONTH(tdate), DAY(tdate), store_id, d.salesCode
            ORDER BY YEAR(tdate), MONTH(tdate), DAY(tdate), d.salesCode, store_id";
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, array($date1, $date2 . ' 23:59:59', $store));
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $data[] = array(
                date('Y-m-d', mktime(0, 0, 0, $row['m'], $row['d'], $row['y'])),
                StandardAccounting::extend($row['salesCode'], $row['store_id']),
                sprintf('%.2f', $row['ttl']),
            );
        }

        return $data;
    }

    function form_content()
    {
        $queue = FannieAuth::hasEmail(FannieAuth::getUID()) && QueueManager::available() ? '' : 'disabled';
        ob_start();
        ?>
        <form method=get>
        <div class="row">
            <div class="col-sm-5">
                <div class="form-group">
                    <?php $store = FormLib::storePicker(); echo $store['html']; ?>
                </div>
                <div class="form-group">
                    <label><input type="checkbox" name="noNabs" value="1" /> Remove Nabs</label>
                </div>
                <div class="form-group">
                    <label><input type="checkbox" <?php echo $queue; ?> name="queued" value="1" /> Email it to me</label>
                </div>
                <p>
                <button type=submit name=submit value="Submit"
                    class="btn btn-default">Submit</button>
                </p>
            </div>
            <?php echo FormLib::standardDateFields(); ?>
        </div>
        </form>
        <?php
        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec();

