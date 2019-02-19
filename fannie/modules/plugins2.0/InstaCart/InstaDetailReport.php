<?php

use COREPOS\Fannie\API\item\ItemText;

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class InstaDetailReport extends FannieReportPage
{
    protected $title = 'InstaCart Detail Report';
    protected $header = 'InstaCart Detail Report';
    public $description = '[InstaCart Detail Report] displays transaction details for a given order';
    protected $required_fields = array('date', 'order');
    protected $report_headers = array('UPC', 'Brand', 'Description', 'Qty', 'Online Total', 'Total');

    public function report_description_content()
    {
        $query = "SELECT * FROM " . FannieDB::fqn('InstaTransactions', 'plugin:InstaCartDB')
            . " WHERE orderDate BETWEEN ? AND ? AND deliveryID=?";
        $query = $this->connection->addSelectLimit($query, 1);
        $prep = $this->connection->prepare($query);
        $row = $this->connection->getRow($prep, array($this->form->date, $this->form->date . ' 23:59:59', $this->form->order));

        return array(
            'Order ID: ' . $row['orderID'],
            'Delivery ID: ' . $row['deliveryID'],
            'Store ID: ' . $row['storeID'],
            'Ordered: ' . $row['orderDate'],
            'Delivered: ' . $row['deliveryDate'],
        );
    }

    public function fetch_report_data()
    {
        $prep = $this->connection->prepare("
            SELECT 
                i.upc,
                i.quantity,
                i.onlineTotal,
                i.total,
                " . ItemText::longBrandSQL() . ",
                " . ItemText::longDescriptionSQL() . "
            FROM " . FannieDB::fqn('InstaTransactions', 'plugin:InstaCartDB') . " AS i
                LEFT JOIN Stores AS s ON i.storeID=s.storeID
                " . DTrans::joinProducts('i') . "
                LEFT JOIN productUser AS u ON i.upc=u.upc
            WHERE i.orderDate BETWEEN ? AND ?
                AND i.deliveryID=?");
            
        $res = $this->connection->execute($prep, array($this->form->date, $this->form->date . ' 23:59:59', $this->form->order));
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $data[] = array(
                $row['upc'],
                $row['brand'],
                $row['description'],
                sprintf('%.2f', $row['quantity']),
                sprintf('%.2f', $row['onlineTotal']),
                sprintf('%.2f', $row['total']),
            );
        }

        return $data;
    }

    public function calculate_footers($data)
    {
        $sums = array(0,0,0);
        foreach ($data as $row) {
            $sums[0] += $row[3];
            $sums[1] += $row[4];
            $sums[2] += $row[5];
        }

        return array('Total', '', '', $sums[0], $sums[1], $sums[2]);
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

