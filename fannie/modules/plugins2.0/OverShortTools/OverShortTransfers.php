<?php

include(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class OverShortTransfers extends FannieReportPage
{
    protected $title = 'Transfers Report';
    protected $header = 'Transfers Report';
    protected $report_headers = array('Code', 'Total');
    public $description = '[Transfers Report] exports data about cross-store transfers.';
    public $report_set = 'Finance';
    protected $required_fields = array('date1', 'date2');

    function fetch_report_data()
    {
        $accounting = $this->config->get('ACCOUNTING_MODULE');
        if (!class_exists($accounting)) {
            $accounting = '\COREPOS\Fannie\API\item\Accounting';
        }
        
        $vendorsR = $this->connection->query("
            SELECT v.vendorID
            FROM vendors AS v
                INNER JOIN Stores AS s ON v.vendorName=s.description
            WHERE s.hasOwnItems=1");
        $vendors = array();
        while ($row = $this->connection->fetchRow($vendorsR)) {
            $vendors[] = $row['vendorID'];
        }
        
        list($inStr, $args) = $this->connection->safeInClause($vendors);
        $args[] = $this->form->date1 . ' 00:00:00';
        $args[] = $this->form->date2 . ' 23:59:59';
        $prep = $this->connection->prepare("
            SELECT i.salesCode,
                o.storeID,
                SUM(i.receivedTotalCost) AS ttl
            FROM PurchaseOrder AS o
                INNER JOIN PurchaseOrderItems AS i ON o.orderID=i.orderID
            WHERE o.vendorID IN ({$inStr})
                AND i.receivedDate BETWEEN ? AND ?
            GROUP BY i.salesCode,
                o.storeID");
        $res = $this->connection->execute($prep, $args);
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $code = $accounting::toPurchaseCode($row['salesCode']);
            $code = $accounting::extend($code, $row['storeID']);
            if (!isset($data[$code])) {
                $data[$code] = array($code, 0);
            }
            $data[$code][1] += $row['ttl'];
        }

        return $this->dekey_array($data);
    }

    function form_content()
    {
        $dates = FormLib::standardDateFields();
        return <<<HTML
<form method="get">
    <p>
        {$dates}
    </p>
    <p>
        <button class="btn btn-submit btn-core">Submit</button>
    </p>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

