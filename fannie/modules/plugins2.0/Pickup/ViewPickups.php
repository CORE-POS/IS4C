<?php

use COREPOS\Fannie\API\lib\Store;

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('PickupOrdersModel')) {
    include(__DIR__ . '/models/PickupOrdersModel.php');
}

class ViewPickups extends FannieRESTfulPage
{
    protected $header = 'Pickup Orders';
    protected $title = 'Pickup Orders';

    public function preprocess()
    {
        $this->addRoute('post<id><status>', 'post<id><print>');

        return parent::preprocess();
    }

    protected function post_id_print_handler()
    {

        $pdf=new FPDF('P','mm','Letter'); //start new instance of PDF
        $pdf->Open(); //open new PDF Document
        $count = 0;
        $posX = 0;
        $posY = 0;
        $date = date("m/d/Y");
        $multi = false;
        if (!is_array($this->id)) {
            $multi = FormLib::get('multi', 1);
            $arr = array();
            for ($i=0; $i<$multi; $i++) {
                $arr[] = $this->id;
            }
            $this->id = $arr;
        }
        $oiP = $this->connection->prepare("SELECT u.brand, u.description, quantity
            FROM PickupOrderItems AS i
                LEFT JOIN productUser AS u ON i.upc=u.upc
            WHERE pickupOrderID=?
                AND i.upc NOT IN ('', '0', 'TAX')
            ORDER BY brand DESC, description");
        foreach ($this->id as $id) {
            $order = new PickupOrdersModel($this->connection);
            $order->pickupOrderID($id);
            $order->load();
            if ($count % 4 == 0){ 
                $pdf->AddPage();
                $pdf->SetDrawColor(0,0,0);
                $pdf->Line(108,0,108,279);
                $pdf->Line(0,135,215,135);
            }

            $posX = $count % 2 == 0 ? 5 : 115;
            $posY = ($count/2) % 2 == 0 ? 10 : 145;
            $pdf->SetXY($posX,$posY);
            $pdf->SetFont('Arial','','16');

            if ($order->curbside()) {
                $pdf->SetXY($posX,$posY + 12);
                $pdf->Cell(6, 6, 'C', 1, 0);
                $pdf->SetXY($posX,$posY);
            }

            $pdf->SetX($posX);
            $pdf->SetFont('Arial','B','24');
            $pdf->Cell(100,10,$order->name(),0,1,'C');
            $pdf->SetFont('Arial','','16');
            $pdf->SetX($posX);
            $oID = $order->orderNumber() ? $order->orderNumber() : $order->pickupOrderID();
            $pdf->Cell(100,10,'Order #: ' . $oID,0,1,'C');
            list($date,) = explode(' ', $order->pDate());
            $ts = strtotime($date);
            $date = date('D, M j', $ts);
            $pdf->SetX($posX);
            $pdf->Cell(100,10,'Pickup: ' . $date . ' ' . $order->pTime(),0,1,'C');
            $pdf->SetX($posX);
            $pdf->Cell(100,10,'Phone: ' . $order->phone(), 0, 1, 'C');

            $pdf->SetFont('Arial','','12');
            $oiR = $this->connection->execute($oiP, array($id));
            while ($oiW = $this->connection->fetchRow($oiR)) {
                $pdf->SetX($posX);
                $pdf->Cell(20, 7, $oiW['quantity'], 0, 0, 'L');
                $pdf->MultiCell(75, 7, $oiW['brand'] . ' ' . $oiW['description']);
            }

            if ($multi > 1) {
                $pdf->SetXY($posX, $posY + 100);
                $pdf->Cell(100,10,($count+1) . '/' . $multi,0,1,'C');
            }

            $count++;
        }
        $pdf->Output('PickupOrder.pdf', 'I');

        return false;
    }

    protected function post_id_handler()
    {
        $model = new PickupOrdersModel($this->connection);
        $model->pickupOrderID($this->id);
        $model->load();

        $name = trim(FormLib::get('name'));
        $phone = trim(FormLib::get('phone'));
        $email = trim(FormLib::get('email'));
        $pickupDT = trim(FormLib::get('pickupDT'));
        list($pDate, $pTime) = explode(' ', $pickupDT, 2);

        if ($model->name() != $name || $model->phone() != $phone || $model->email() != $email || $model->pDate() != $pDate . ' 00:00:00' || $model->pTime() != $pTime) {
            $prep = $this->connection->prepare("INSERT INTO PickupHistoryOrders (modified, pickupOrderID, orderNumber, name, phone, email, vehicle,
                pDate, pTime, notes, closed, deleted, storeID, status, cardNo, placedDate, curbside)
                SELECT NOW(), p.* FROM PickupOrders AS p WHERE pickupOrderID=?");
            $this->connection->execute($prep, array($this->id));

            $model->name($name);
            $model->email($email);
            $model->phone($phone);
            $model->pDate($pDate);
            $model->pTime($pTime);
            $model->save();
        }

        return 'ViewPickups.php?id=' . $this->id;
    }

    protected function post_id_status_handler()
    {
        $order = new PickupOrdersModel($this->connection);
        $order->pickupOrderID($this->id);
        $order->status(strtoupper($this->status));
        $order->save();

        return 'ViewPickups.php?id=' . $this->id;
    }

    private function pickList($orderID, $store)
    {
        $prep = $this->connection->prepare("
            SELECT p.upc, p.brand, p.description, p.quantity, q.size, q.scale
            FROM PickupOrderItems AS p
                LEFT JOIN products AS q ON p.upc=q.upc AND q.store_id=1
            WHERE p.pickupOrderID=?
        ");

        $locateP = $this->connection->prepare("
            SELECT f.name, s.subSection
            FROM FloorSectionProductMap AS m
                INNER JOIN FloorSections AS f ON m.floorSectionID=f.floorSectionID
                LEFT JOIN FloorSubSections AS s ON m.floorSectionID=s.floorSectionID AND m.upc=s.upc
            WHERE m.upc=?
                AND f.storeID=?");

        $res = $this->connection->execute($prep, array($orderID));
        $ret = array();
        while ($row = $this->connection->fetchRow($res)) {
            $locations = $this->connection->getAllRows($locateP, array($row['upc'], $store));
            $row['location'] = '';
            foreach ($locations as $l) {
                if ($row['location'] != '') {
                    $row['location'] .= ', ';
                }
                $row['location'] .= $l['name'] . ' ' . $l['subSection'];
            }
            $ret[] = $row;
        }

        return $ret;
    }

    private function sortList($list)
    {
        $sort = function ($a, $b) {
            if ($a['location'] != '' && $a['location'] < $b['location']) {
                return -1;
            } elseif ($a['location'] == '' && $a['location'] < $b['location']) {
                return 1;
            } elseif ($a['location'] > $b['location']) {
                return 1;
            }
            return 0;
        };
        usort($list, $sort);

        return $list;
    }

    private function pickListToHTML($list)
    {
        $ret = '<table class="table table-bordered table-striped">
            <thead><tr><th>Location</th><th>UPC</th><th>Brand</th><th>Item</th><th>Quantity</th></thead>
            <tbody>';
        foreach ($list as $l) {
            if ($l['size']) {
                $l['description'] .= ' ' . $l['size'];
            }
            $ret .= sprintf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%.2f %s</td></tr>',
                $l['location'], $l['upc'], $l['brand'], $l['description'], $l['quantity'],
                ($l['scale'] ? 'lb' : '')
            );
        }
        $ret .= '</tbody></table>';

        return $ret;
    }

    protected function get_id_view()
    {
        $order = new PickupOrdersModel($this->connection);
        $order->pickupOrderID($this->id);
        $order->load();
        $order = $order->toStdClass();
        list($order->pDate,) = explode(' ', $order->pDate);

        $pickList = $this->pickList($this->id, $order->storeID);
        $pickList = $this->sortList($pickList);
        $listHTML = $this->pickListToHTML($pickList);
        if (!$order->orderNumber) {
            $order->orderNumber = $order->pickupOrderID;
        }

        $status = array('NEW', 'READY', 'COMPLETE', 'CANCELLED');
        $radios = '';
        foreach ($status as $s) {
            $radios .= sprintf('<label><input %s type="radio" name="status" value="%s"> %s</label> | ',
                ($order->status == $s ? 'checked' : ''),
                $s, $s);
        }
        $order->notes = nl2br($order->notes); 

        return <<<HTML
<p>
    <a href="ViewPickups.php" class="btn btn-default">List All Orders</a>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <a href="PickupOrders.php" class="btn btn-default">Main Menu</a>
</p>
<div class="col-sm-6">
<form action="ViewPickups.php" method="post">
<input type="hidden" name="id" value="{$this->id}" />
<table class="table table-bordered table-striped">
    <tr>
        <th>Order Number</th>
        <td>{$order->orderNumber}</td>
    </tr>
    <tr>
        <th>Name</th>
        <td><input type="text" name="name" class="form-control" value="{$order->name}" /></td>
    </tr>
    <tr>
        <th>Owner #</th>
        <td>{$order->cardNo}</td>
    </tr>
    <tr>
        <th>Phone</th>
        <td><input type="text" name="phone" class="form-control" value="{$order->phone}" /></td>
    </tr>
    <tr>
        <th>Email</th>
        <td><input type="text" name="email" class="form-control" value="{$order->email}" /></td>
    </tr>
    <tr>
        <th>Vehicle Info</th>
        <td>{$order->vehicle}</td>
    </tr>
    <tr>
        <th>Pickup Date+Time</th>
        <td><input type="text" name="pickupDT" class="form-control" value="{$order->pDate} {$order->pTime}" /></td>
    </tr>
    <tr>
        <th>Notes</th>
        <td>{$order->notes}</td>
    </tr>
</table>
<p>
    <button type="submit" class="btn btn-default">Update Order Info</button>
</p>
</form>
</div>
<p>
{$listHTML}
</p>
<p class="hidden-print">
<strong>Current Status</strong>: {$order->status}
</p>
<p class="hidden-print">
<form method="post" action="ViewPickups.php">
    <div class="form-group">
        <input type="hidden" name="id" value="{$this->id}" />
        Change status to: {$radios}
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Save New Status</button>
    </div>
</form>
</p>
<p class="hidden-print">
<form method="post" action="ViewPickups.php">
    <div class="form-group">
        <input type="hidden" name="id" value="{$this->id}" />
        <div class="input-group">
            <span class="input-group-addon"># of tags</span>
            <input type="text" class="form-control" name="multi" value="1" />
        </div>
        <input type="hidden" class="form-control" value="1" name="print" />
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Print</button>
    </div>
</form>
</p>
HTML;
    }

    protected function get_view()
    {
        $store = FormLib::get('store', false);
        if ($store === false) {
            $store = Store::getIdByIp();
        }
        $stores = FormLib::storePicker('store', true, "location='ViewPickups.php?' + \$('select.form-control').serialize();");
        $stat = FormLib::get('status', 'NEW + READY');
        $status = array('NEW + READY', 'COMPLETE', 'CANCELLED');
        $stats = '';
        foreach ($status as $s) {
            $stats .= sprintf('<option %s value="%s">%s</option>',
                ($s == $stat ? 'selected' : ''), $s, $s);
        }
        $statClause = " status in ('NEW', 'READY') ";
        if ($stat == 'COMPLETE') {
            $statClause = " status in ('COMPLETE') ";
        } elseif ($stat == 'CANCELLED') {
            $statClause = " status in ('CANCELLED') ";
        }
        $prep = $this->connection->prepare("SELECT *
            FROM PickupOrders
            WHERE closed=0 AND deleted=0
                AND storeID=?
                AND {$statClause}
            ORDER BY pickupOrderID DESC");
        $res = $this->connection->execute($prep, array($store));
        $table = '';
        while ($row = $this->connection->fetchRow($res)) {
            list($dateTime,) = explode(' ', $row['pDate']);
            $dateTime .= ' ' . $row['pTime'];
            if (!$row['orderNumber']) {
                $row['orderNumber'] = $row['pickupOrderID'];
            }
            $table .= sprintf('<tr>
                <td><a href="ViewPickups.php?id=%d" class="btn btn-default">View</td>
                <td>%s</td><td>%s</td><td>%s</td><td>%s</td>
                <td><input type="checkbox" name="print" value="%d" /></tr>',
                $row['pickupOrderID'],
                $row['orderNumber'], $row['name'], $dateTime, $row['status'],
                $row['pickupOrderID']);
        }
        return <<<HTML
<p>
    <a href="PickupOrders.php" class="btn btn-default">Main Menu</a>
</p>
<p>
    {$stores['html']}
</p>
<p>
    <select name="status" class="form-control"
        onchange="location='ViewPickups.php?' + $('select.form-control').serialize();">
        {$stats}
    </select>
</p>
<p>
<table class="table table-bordered table-striped">
<tr><th><th>Order Number</th><th>Name</th><th>Pickup Date+Time</th><th>Status</th><th>Print</th></tr>
    {$table}
</table>
<div>
<button type="button" class="btn btn-default" onclick="printAll();">Print</button>
</div>
</p>
<script>
function printAll() {
    var form = '<form id="printAllForm" method="post"><input type="hidden" name="print" value="1" />';
    $('input[name=print]').each(function() {
        if ($(this).prop('checked')) {
            form += '<input name="id[]" type="hidden" value="' + $(this).val() + '" />';
        }
    });
    form += '</form>';
    $('body').append(form);
    $('#printAllForm').submit();
}

var lastChecked = null;
var i = 0;
var indexCheckboxes = function() {
    var upcCheckBoxes = document.getElementsByName("print"); 
    for (var i = 0; i < upcCheckBoxes.length; i++) {
        upcCheckBoxes.item(i).setAttribute("data-index", i);
    }
};
document.addEventListener("click", function (event) {
    indexCheckboxes();
});
document.addEventListener("click", function (event) {
        if (lastChecked && event.shiftKey) {
            var i = parseInt(lastChecked.getAttribute("data-index"));
            var j = parseInt(event.target.getAttribute("data-index"));
            var checked = event.target.checked;

            var low = i;
            var high = j;
            if (i > j){
                var low = j;
                var high = i;
            }

            for(var c = low; c < high; c++) {
                if (c != low && c!= high) {
                    var check = checked ? true : false;
                    curbox = document.querySelectorAll('[data-index="'+c+'"]');
                    box = curbox.item(0);
                    box.checked = check;
                }
            }
        }
        lastChecked = event.target; 
});
</script>
HTML;
    }
}

FannieDispatch::conditionalExec();

