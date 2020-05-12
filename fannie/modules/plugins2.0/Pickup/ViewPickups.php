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
        $order = new PickupOrdersModel($this->connection);
        $order->pickupOrderID($this->id);
        $order->load();

        $pdf=new FPDF('P','mm','Letter'); //start new instance of PDF
        $pdf->Open(); //open new PDF Document
        $count = 0;
        $posX = 0;
        $posY = 0;
        $date = date("m/d/Y");
        for ($i=0; $i<$this->print; $i++) {
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
            $pdf->Cell(100,10,'DRY    COOL    FROZEN',0,1,'C');
            $pdf->Ln();
            $pdf->SetX($posX);
            $pdf->SetFont('Arial','B','24');
            $pdf->Cell(100,10,$order->name(),0,1,'C');
            $pdf->SetFont('Arial','','16');
            $pdf->SetX($posX);
            $oID = $order->orderNumber() ? $order->orderNumber() : $order->pickupOrderID();
            $pdf->Cell(100,10,$oID,0,1,'C');
            list($date,) = explode(' ', $order->pDate());
            $pdf->SetX($posX);
            $pdf->Cell(100,10,$date . ' ' . $order->pTime(),0,1,'C');
            /*
            $pdf->Ln();
            $pdf->SetX($posX);
            $pdf->Cell(100,10,($i+1) . '/' . $this->print,0,1,'C');
             */

            $count++;
        }
        $pdf->Output();

        return false;
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
<table class="table table-bordered table-striped">
    <tr>
        <th>Order Number</th>
        <td>{$order->orderNumber}</td>
    </tr>
    <tr>
        <th>Name</th>
        <td>{$order->name}</td>
    </tr>
    <tr>
        <th>Owner #</th>
        <td>{$order->cardNo}</td>
    </tr>
    <tr>
        <th>Phone</th>
        <td>{$order->phone}</td>
    </tr>
    <tr>
        <th>Vehicle Info</th>
        <td>{$order->vehicle}</td>
    </tr>
    <tr>
        <th>Pickup Date+Time</th>
        <td>{$order->pDate} {$order->pTime}</td>
    </tr>
    <tr>
        <th>Notes</th>
        <td>{$order->notes}</td>
    </tr>
</table>
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
            <input type="text" class="form-control" value="1" name="print" />
        </div>
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
        $stores = FormLib::storePicker('store', true, "location='ViewPickups.php?store=' + this.value");
        $prep = $this->connection->prepare("SELECT *
            FROM PickupOrders
            WHERE closed=0 AND deleted=0
                AND storeID=?
                AND status IN ('NEW', 'READY')");
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
                <td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                $row['pickupOrderID'],
                $row['orderNumber'], $row['name'], $dateTime, $row['status']);
        }
        return <<<HTML
<p>
    <a href="PickupOrders.php" class="btn btn-default">Main Menu</a>
</p>
<p>
    {$stores['html']}
</p>
<p>
<table class="table table-bordered table-striped">
<tr><th><th>Order Number</th><th>Name</th><th>Pickup Date+Time</th><th>Status</th></tr>
    {$table}
</table>
</p>
HTML;
    }
}

FannieDispatch::conditionalExec();

