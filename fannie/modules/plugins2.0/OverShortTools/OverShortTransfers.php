<?php

include(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class OverShortTransfers extends FannieReportPage
{
    protected $title = 'Transfers Report';
    protected $header = 'Transfers Report';
    protected $report_headers = array('GL-ID', 'End Date', 'Account#', 'Debit', 'Credit', 'Note');
    public $description = '[Transfers Report] exports data about cross-store transfers.';
    public $report_set = 'Finance';
    protected $required_fields = array('date1', 'date2');
    protected $no_sort_but_style = true;

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
        $args[] = $this->form->date1 . ' 00:00:00';
        $args[] = $this->form->date2 . ' 23:59:59';
        $noteP = $this->connection->prepare("SELECT notes FROM PurchaseOrderNotes WHERE orderID=?");
        $prep = $this->connection->prepare("
            SELECT i.salesCode,
                o.storeID,
                SUM(CASE 
                    WHEN i.receivedTotalCost = 0 OR i.receivedTotalCost IS NULL THEN unitCost*caseSize*quantity 
                    ELSE receivedTotalCost 
                END) AS ttl,
                o.orderID
            FROM PurchaseOrder AS o
                INNER JOIN PurchaseOrderItems AS i ON o.orderID=i.orderID
            WHERE o.vendorID IN ({$inStr})
                AND (
                    i.receivedDate BETWEEN ? AND ?
                    OR
                    o.placedDate BETWEEN ? AND ?
                )
            GROUP BY i.salesCode,
                o.storeID,
                o.orderID
            ORDER BY
                ABS(SUM(CASE 
                    WHEN i.receivedTotalCost = 0 OR i.receivedTotalCost IS NULL THEN unitCost*caseSize*quantity 
                    ELSE receivedTotalCost 
                END)), ttl");
        $res = $this->connection->execute($prep, $args);
        $stamp = strtotime($this->form->date1);
        $glID = date('Ym01', $stamp);
        $endDate = date('n/t/y', $stamp);
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $code = $accounting::toPurchaseCode($row['salesCode']);
            $code = $accounting::extend($code, $row['storeID']);
            $note = $this->connection->getValue($noteP, array($row['orderID']));
            $note = $note ? $note : 'Product Transfer';
            $credit = $row['ttl'] <= 0 ? $row['ttl'] * -1 : '';
            $debit = $row['ttl'] > 0 ? $row['ttl'] : '';
            $data[] = array(
                $glID,
                $endDate,
                $code,
                sprintf('%.2f', $debit),
                sprintf('%.2f', $credit),
                $note,
            );
        }

        return $data;
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

