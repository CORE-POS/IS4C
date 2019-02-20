<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class InstaTransactionReport extends FannieReportPage
{
    protected $title = 'InstaCart Transaction Report';
    protected $header = 'InstaCart Transaction Report';
    public $description = '[InstaCart Transaction Report] displays transaction data for a given date range';
    protected $required_fields = array('date1', 'date2');
    protected $report_headers = array('Order ID', '$ Total', '# Items', 'Store', 'Ordered', 'Delivered', 'User ID', 'Owner #', 'Name', 'Original Zip', 'Order Zip', 'Platform');

    public function fetch_report_data()
    {
        $prep = $this->connection->prepare("
            SELECT MAX(userID) AS userID,
                deliveryID AS orderID,
                MAX(orderDate) AS orderDate,
                MAX(deliveryDate) AS deliveryDate,
                SUM(quantity) AS qty,
                SUM(total) AS total,
                MAX(i.cardNo) AS owner,
                MAX(signupZip) AS signupZip,
                MAX(deliveryZip) AS deliveryZip,
                MAX(platform) AS platform,
                MAX(s.description) AS store,
                MAX(c.LastName) AS ln,
                MAX(c.FirstName) AS fn
            FROM " . FannieDB::fqn('InstaTransactions', 'plugin:InstaCartDB') . " AS i
                LEFT JOIN Stores AS s ON i.storeID=s.storeID
                LEFT JOIN custdata AS c ON i.cardNo=c.CardNo AND c.personNum=1
            WHERE i.orderDate BETWEEN ? AND ?
            GROUP BY i.deliveryID");
            
        $res = $this->connection->execute($prep, array($this->form->date1, $this->form->date2 . ' 23:59:59'));
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $link = sprintf('<a href="InstaDetailReport.php?date=%s&order=%s">%s</a>',
                date('Y-m-d', strtotime($row['orderDate'])), $row['orderID'], $row['orderID']);
            $data[] = array(
                $link,
                sprintf('%.2f', $row['total']),
                sprintf('%.2f', $row['qty']),
                $row['store'],
                $row['orderDate'],
                $row['deliveryDate'],
                $row['userID'],
                $row['owner'],
                $row['fn'] . ' ' . $row['ln'],
                $row['signupZip'],
                $row['deliveryZip'],
                $row['platform'],
            );
        }

        return $data;
    }

    public function calculate_footers($data)
    {
        $total = 0;
        $orders = 0;
        $qty = 0;
        $owners = 0;
        $userMap = array();
        foreach ($data as $row) {
            $total += $row[1];
            $qty += $row[2];
            $orders++;
            $owners += ($row[7] != 11 ? 1 : 0);
            $userMap[$row[6]] = true;
        }

        $totals = array('Total', sprintf('%.2f', $total), sprintf('%.2f', $qty), '', '', '', count($userMap), $owners, '', '', '', '');
        $avg = array('Average', sprintf('%.2f', $orders ? $total/$orders : 0), sprintf('%.2f', $orders ? $qty/$orders : 0), '', '', '', '', '', '', '', '', '');

        return array($totals, $avg);
    }

    public function form_content()
    {
        $dates = FormLib::standardDateFields();
        return <<<HTML
<form method="get">
    <p>
    {$dates}
    <div class="row"></div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Get Report</button>
    </div>
    </p>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

