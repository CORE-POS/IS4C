<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class PickupEnabledPage extends FannieRESTfulPage
{
    protected $header = 'Pickup Items';
    protected $title = 'Pickup Items';

    protected function post_id_handler()
    {
        list($inStr, $args) = $this->connection->safeInClause($this->id);

        // save locally
        $this->connection->query("UPDATE PickupEnabled SET enabled=0");
        $prep = $this->connection->prepare("UPDATE PickupEnabled SET enabled=1 WHERE upc IN ({$inStr})");
        $this->connection->execute($prep, $args);

        require(__DIR__ . '/../../../src/Credentials/OutsideDB.tunneled.php');
        list($inStr, $args) = $dbc->safeInClause($this->id);

        $prep = $dbc->prepare("UPDATE productUser SET enableOnline=1, soldOut=0 WHERE upc IN ({$inStr})");
        $dbc->execute($prep, $args);
        $prep = $dbc->prepare("UPDATE productUser SET enableOnline=0, soldOut=1 WHERE upc NOT IN ({$inStr})");
        $dbc->execute($prep, $args);

        return true;
    }

    protected function post_id_view()
    {
        return '<div class="alert alert-success">Website Listings Updated</div>' . $this->get_view();
    }

    protected function get_view()
    {
        $res = $this->connection->query("SELECT e.upc, e.enabled,
            p.description
            FROM PickupEnabled AS e
                " . DTrans::joinProducts ('e', 'p') . "
            ORDER BY p.description");
        $body = '';
        while ($row = $this->connection->fetchRow($res)) {
            $body .= sprintf('<tr><td>%s</td><td>%s</td>
                <td><input type="checkbox" value="%s" name="id[]" %s /></td>
                </tr>',
                $row['upc'], $row['description'],
                $row['upc'], ($row['enabled'] ? 'checked' : '')
            );
        }

        return <<<HTML
<form method="post" action="PickupEnabledPage.php">
<p>
    <button type="submit" class="btn btn-default">Update Availability</button>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <a class="btn btn-default" href="PickupOrders.php">Main Menu</a>
</p>
<table class="table table-striped table-bordered">
    <tr><th>UPC</th><th>Item</th><th>Sell Online</th></tr>
    {$body}
</table>
<p>
    <button type="submit" class="btn btn-default">Update Availability</button>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <a class="btn btn-default" href="PickupOrders.php">Main Menu</a>
</p>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

