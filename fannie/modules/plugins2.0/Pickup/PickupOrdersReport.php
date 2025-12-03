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
            $format = FormLib::get('format');
        } catch (Exception $ex) {
            return array();
        }

        if ($format == 'Wide') {
            return $this->wideReportData($date1, $date2, $store);
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
                AND i.upc <> ''
                AND i.upc <> '0'
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

    private function wideReportData($date1, $date2, $store)
    {
        $itemP = $this->connection->prepare("SELECT u.brand, u.description, i.upc
                    FROM PickupOrders AS o
                        INNER JOIN PickupOrderItems AS i ON i.pickupOrderID=o.pickupOrderID
                        LEFT JOIN productUser AS u ON i.upc=u.upc
                    WHERE o.placedDate BETWEEN ? AND ?
                        AND o.storeID=?
                        AND o.status IN ('NEW')
                        AND i.upc <> ''
                        AND i.upc <> '0'
                        AND i.upc <> 'TAX'
                    GROUP BY u.brand, u.description
                    ORDER BY u.brand DESC, u.description");
        $items = $this->connection->getAllRows($itemP, array($date1, $date2 . ' 23:59:59', $store));
        $this->report_headers = array('Ordered', 'First Name', 'Last Name', 'Phone #', 'Order #', 'Location', 'Curbside', 'Pickup Date', 'Pickup Time');
        $itemStart = 9;
        $colMap = array();
        for ($i=0; $i<count($items); $i++) {
            $item = $items[$i]['brand'] . ' ' . $items[$i]['description'];
            $colMap[$item] = $itemStart + $i;
            $this->report_headers[] = $item;
        }

        $oiP = $this->connection->prepare("SELECT u.brand, u.description, quantity
            FROM PickupOrderItems AS i
                LEFT JOIN productUser AS u ON i.upc=u.upc
            WHERE pickupOrderID=?
                AND i.upc <> ''
                AND i.upc <> '0'
                AND i.upc <> 'TAX'
            ORDER BY brand DESC, description");
        $baseP = $this->connection->prepare("
            SELECT o.placedDate, o.name, o.phone, o.pickupOrderID, s.description, o.curbside,
                o.pDate, o.pTime, o.orderNumber
            FROM PickupOrders AS o
                INNER JOIN Stores AS s ON o.storeID=s.storeID
            WHERE o.placedDate BETWEEN ? AND ?
                AND o.status IN ('NEW')
                AND o.storeID=?
            ORDER BY o.placedDate");
        $baseR = $this->connection->execute($baseP, array($date1, $date2 . ' 23:59:59', $store));
        $data = array();
        while ($baseW = $this->connection->fetchRow($baseR)) {
            $name = strrev(trim($baseW['name']));
            $names = explode(' ', $name, 2);
            $names = array_map(function($i) { return strrev(trim($i)); }, $names);
            list($baseW['pDate'],) = explode(' ', $baseW['pDate']);
            $record = array(
                $baseW['placedDate'],
                $names[0],
                $names[1],
                $baseW['phone'],
                $baseW['orderNumber'],
                $baseW['description'],
                $baseW['curbside'] ? 'Yes' : 'No',
                $baseW['pDate'],
                $baseW['pTime'],
            );
            $pos = $itemStart;
            $oiR = $this->connection->execute($oiP, array($baseW['pickupOrderID']));
            while ($oiW = $this->connection->fetchRow($oiR)) {
                $item = $oiW['brand'] . ' ' . $oiW['description'];
                if ($colMap[$item] == $pos) {
                    $record[] = $oiW['quantity'];
                    $pos++;
                } else {
                    while ($colMap[$item] != $pos) {
                        $record[] = '';
                        $pos++;
                    }
                    $record[] = $oiW['quantity'];
                    $pos++;
                }
            }
            while ($pos < count($this->report_headers)) {
                $record[] = '';
                $pos++;
            }
            $data[] = $record;
        }

        return $data;
    }

    public function calculate_footers($data)
    {
        if (count($this->report_headers) == 4) {
            return array();
        }
        $sums = array();
        $itemStart = 9;
        for ($i=$itemStart; $i < count($this->report_headers); $i++) {
            $sums[] = 0;
        }
        foreach ($data as $row) {
            for ($i=0; $i<count($sums); $i++) {
                $val = $row[$i + $itemStart];
                if ($val) {
                    $sums[$i] += $val;
                }
            }
        }

        return array_merge(array('Total', null, null, null, null, null, null, null, null), $sums);
    }

    public function form_content()
    {
        $stores = FormLib::storePicker();
        $dates = FormLib::standardDateFields();
        $this->addOnloadCommand("\$('#date1').val('2025-10-01');");
        $this->addOnloadCommand("\$('#date2').val('2025-11-30');");

        return <<<HTML
<form method="get">
    <div class="col-sm-5">
        <div class="form-group">
            <label>Store</label>
            {$stores['html']}
        </div>
        <div class="form-group">
            <label>Format</label>
            <select class="form-control" name="format">
                <option>Wide</option>
                <option>Item Summary</option>
            </select>
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

