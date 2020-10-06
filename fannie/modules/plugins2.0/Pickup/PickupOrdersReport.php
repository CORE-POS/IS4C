<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class PickupOrdersReport extends FannieReportPage
{
    protected $header = 'Pickup Production List';
    protected $title = 'Pickup Production List';

    protected $required_fields = array('date1', 'date2');
    protected $new_tablesorter = true;
    protected $report_headers = array('Category', 'Item', 'Quantity', 'Closest Due Date');

    public function fetch_report_data()
    {
        try {
            $date1 = $this->form->date1;
            $date2 = $this->form->date2;
            $store = FormLib::get('store');
        } catch (Exception $ex) {
            return array();
        }

        $prep = $this->connection->prepare("
            SELECT d.dept_name, u.brand, u.description, sum(i.quantity) AS qty,
                MIN(CASE WHEN o.pDate >= " . $this->connection->curdate() . " THEN o.pDate ELSE '2099-12-31' END) AS soonest
            FROM PickupOrders AS o
                INNER JOIN PickupOrderItems AS i ON o.pickupOrderID=i.pickupOrderID
                " . DTrans::joinProducts('i', 'p') . "
                LEFT JOIN departments AS d ON p.department=d.dept_no
                LEFT JOIN productUser AS u ON i.upc=u.upc
            WHERE o.placedDate BETWEEN ? AND ?
                AND o.storeID=?
                AND o.status in ('NEW')
            GROUP BY d.dept_name, u.brand, u.description
        ");
        $res = $this->connection->execute($prep, array($date1, $date2 . ' 23:59:59', $store));
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            list($due) = explode(' ', $row['soonest'], 2);
            $due = date('D, M j', strtotime($due));
            $data[] = array(
                $row['dept_name'],
                ($row['brand'] ? $row['brand'] . ' ' : '') . $row['description'],
                sprintf('%.2f', $row['qty']),
                $due,
            );
        }

        return $data;
    }

    public function form_content()
    {
        $stores = FormLib::storePicker();
        $dates = FormLib::standardDateFields();

        return <<<HTML
<form method="get">
    <div class="col-sm-5">
        <div class="form-group">
            <label>Store</label>
            {$stores['html']}
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-default">Get List</button>
        </div>
    </div>
    {$dates}
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

