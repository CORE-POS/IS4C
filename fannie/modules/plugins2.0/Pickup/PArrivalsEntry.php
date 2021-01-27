<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class PArrivalsEntry extends FannieRESTfulPage
{
    protected $header = 'Pickup Arrivals';
    protected $title = 'Pickup Arrivals';

    protected function post_id_handler()
    {
        $notes = FormLib::get('notes');
        $store = COREPOS\Fannie\API\lib\Store::getIdByIp();

        $prep = $this->connection->prepare("INSERT INTO PickupArrivals
            (pickupOrderID, storeID, notes) VALUES (?, ?, ?)");
        $res = $this->connection->execute($prep, array($this->id, $store, $notes));

        echo 'Done';

        return false;
    }

    protected function get_id_handler()
    {
        $name = FormLib::get('name');
        $store = COREPOS\Fannie\API\lib\Store::getIdByIp();
        $results = array();
        if (!empty($name)) {
            $prep = $this->connection->prepare("
                SELECT pickupOrderID, orderNumber, name, pDate, pTime, curbside
                FROM PickupOrders
                WHERE name LIKE ? AND storeID=? AND status='NEW'");
            $res = $this->connection->execute($prep, array('%' . $name . '%', $store));
            while ($row = $this->connection->fetchRow($res)) {
                $results[$row['pickupOrderID']] = $row;
            }
        }
        if (!empty($this->id)) {
            $prep = $this->connection->prepare("
                SELECT pickupOrderID, orderNumber, name, pDate, pTime, curbside
                FROM PickupOrders
                WHERE orderNumber LIKE ? AND storeID=? AND status='NEW'");
            $res = $this->connection->execute($prep, array('%' . $this->id . '%', $store));
            while ($row = $this->connection->fetchRow($res)) {
                $results[$row['pickupOrderID']] = $row;
            }
        }

        echo '<table class="table table-bordered table-striped">';
        echo '<tr><th></th><th>Order#</th><th>Name</th><th>Date</th><th>Time</th><th>Curbside</th></tr>';
        foreach ($results as $id => $row) {
            $pDate = date('D, M j', strtotime($row['pDate']));
            printf('<tr><td><input type="radio" name="puID" value="%d" /></td>
                <td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                $id, $row['orderNumber'], $row['name'], $pDate, $row['pTime'],
                ($row['curbside'] ? 'Yes' : 'No'));
        }
        echo '</table>';

        return false;
    }

    protected function get_view()
    {
        return <<<HTML
<form>
<div class="row">
    <div class="col-sm-5">
        <input type="text" class="form-control" id="inpOrder" placeholder="Order Number" onkeyup="doLookup();" />
    </div>
    <div class="col-sm-5">
        <input type="text" class="form-control" id="inpName" placeholder="Name" onkeyup="doLookup();" />
    </div>
</div>
<br />
<div id="resultArea"></div>
<br />
<div class="row">
    <div class="col-sm-10">
    <b>Vehicle Info (for curbside)</b>:
    <textarea id="notes" class="form-control" rows="5"></textarea>
    </div>
</div>
<br />
<div class="row">
    <div class="col-sm-5">
        <button type="reset" class="btn btn-default">Reset</button>
    </div>
    <div class="col-sm-5">
        <button type="button" class="btn btn-default" onclick="doQueue();">Add to Queue</button>
    </div>
</div>
</form>
<script>
function doLookup() {
    var dstr = 'id=' + $('#inpOrder').val();
    dstr += '&name=' + $('#inpName').val();
    if (dstr.length > 12) {
        $.ajax({
            url: 'PArrivalsEntry.php',
            method: 'get',
            data: dstr
        }).done(function (resp) {
            $('#resultArea').html(resp);
        });
    } else {
        $('#resultArea').html('');
    }
}
function doQueue() {
    if ($('input[name=puID]:checked').length > 0) {
        var dstr = 'id=' + $('input[name=puID]:checked').val();
        dstr += '&notes=' + encodeURIComponent($('#notes').val());
        $.ajax({
            url: 'PArrivalsEntry.php',
            method: 'post',
            data: dstr
        }).done(function (resp) {
            $('#resultArea').html('');
            $('#notes').val('');
            $('#inpOrder').val('');
            $('#inpName').val('');
            showBootstrapAlert('#resultArea', 'success', 'Order queued for Deli');
        });
    }
}
</script>
HTML;
    }
}

FannieDispatch::conditionalExec();

