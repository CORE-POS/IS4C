<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class PArrivalsMonitor extends FannieRESTfulPage
{
    protected $header = 'Pickup Arrivals';
    protected $title = 'Pickup Arrivals';

    protected function get_id_handler()
    {
        $prep = $this->connection->prepare("UPDATE PickupArrivals SET acked=1 WHERE pickupArrivalID=?");
        foreach ($this->id as $id) {
            $this->connection->execute($prep, array($id));
        }

        return true;
    }

    protected function get_id_view()
    {
        return $this->get_view();
    }

    protected function get_view()
    {
        $store = COREPOS\Fannie\API\lib\Store::getIdByIp();
        $prep = $this->connection->prepare("SELECT a.notes,
            p.orderNumber, p.name, a.pickupArrivalID, p.curbside
            FROM PickupArrivals AS a
                INNER JOIN PickupOrders AS p ON a.pickupOrderID=p.pickupOrderID
            WHERE a.storeID=?
                AND acked=0
            ORDER BY pickupArrivalID");
        $res = $this->connection->execute($prep, array($store));
        $ret = '<form id="monitorForm" method="get" action="PArrivalsMonitor.php">
            <table class="table table-bordered table-striped">
            <tr><th>Order Number</th><th>Name</th><th>Notes</th><th>Curbside</th><th>Complete</th></tr>';
        while ($row = $this->connection->fetchRow($res)) {
            $ret .= sprintf('<tr>
                <td>%s</td><td>%s</td><td>%s</td><td>%s</td>
                <td><input type="checkbox" name="id[]" value="%d" /></td></tr>',
                $row['orderNumber'], $row['name'], $row['notes'],
                ($row['curbside'] ? 'Yes' : 'No'),
                $row['pickupArrivalID']);
        }
        $ret .= '</table></form>';

        $this->addOnloadCommand("setTimeout(function () { $('#monitorForm').submit(); }, 30000);");

        return $ret;
    }
}

FannieDispatch::conditionalExec();

