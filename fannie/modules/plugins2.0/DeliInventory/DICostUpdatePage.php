<?php

use COREPOS\Fannie\API\lib\Operators as Op;

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class DICostUpdatePage extends FannieRESTfulPage
{
    protected $header = 'Deli Inventory';
    protected $title = 'Deli Inventory';

    protected function post_id_view()
    {
        $costs = FormLib::get('cost');
        $prep = $this->connection->prepare("UPDATE deliInventoryCat SET price=? WHERE id=?");
        $this->connection->startTransaction();
        for ($i=0; $i<count($costs); $i++) {
            $cost = trim($costs[$i]);
            if ($cost == '') {
                continue;
            }
            $id = $this->id[$i];
            $this->connection->execute($prep, array($cost, $id));
        }
        $this->connection->commitTransaction();

        return $this->get_view();
    }

    protected function get_view()
    {
        $store = FormLib::get('store');
        if (!$store) {
            $store = COREPOS\Fannie\API\lib\Store::getIdByIp();
        }

        $prep = $this->connection->prepare("
            SELECT i.item,
                i.size,
                i.units,
                i.price,
                i.id AS invID,
                v.vendorName,
                m.unitSize,
                m.caseSize,
                m.quantity,
                m.receivedQty,
                m.receivedDate,
                m.receivedTotalCost ,
                m.orderID
            FROM DeliInvLatestMap AS m
                INNER JOIN deliInventoryCat AS i ON i.vendorID=m.vendorID AND i.orderno=m.sku
                INNER JOIN vendors AS v ON m.vendorID=v.vendorID
            WHERE i.storeID=?
                AND m.receivedQty > 0
            ORDER BY v.vendorName, i.item");
        $res = $this->connection->execute($prep, array($store));
        $table = '';
        while ($row = $this->connection->fetchRow($res)) {
            $invUnitCost = Op::div($row['receivedTotalCost'], $row['receivedQty']);
            $invCaseCost = Op::div($row['receivedTotalCost'], $row['quantity']);
            $highlight = '';
            if (abs($invCaseCost - $row['price']) > 0.015) {
                $highlight = 'class="warning"';
            }
            $table .= sprintf('<tr %s>
                <td>%s</td><td>%s</td>
                <td>%s</td><td>%s</td><td>%.2f</td>
                <td>%s</td><td>%s</td>
                <td class="use-unit">%.2f</td>
                <td class="use-case">%.2f</td>
                <td><a href="../../../purchasing/ViewPurchaseOrders.php?id=%d">%s</a></td>
                <td><input type="text" class="form-control input-sm cost-field math-field" name="cost[]" />
                    <input type="hidden" name="id[]" value="%d" />
                </td>
                </tr>',
                $highlight,
                $row['vendorName'], $row['item'],
                $row['size'], $row['units'], $row['price'],
                $row['unitSize'], $row['caseSize'], $invUnitCost, $invCaseCost,
                $row['orderID'], $row['receivedDate'],
                $row['invID']
            );
        }
        $stores = FormLib::storePicker('store', false, "location='DICostUpdatePage.php?store='+this.value;");
        $this->addOnloadCommand("\$('td.use-unit').click(useThisValue);");
        $this->addOnloadCommand("\$('td.use-case').click(useThisValue);");

        return <<<HTML
{$stores['html']}
<form method="post" action="DICostUpdatePage.php">
<table class="table table-bordered table-striped small">
<tr>
    <th colspan="2"></th>
    <th class="text-center" colspan="3">Inventory</th>
    <th class="text-center" colspan="4">Invoice</th>
    <th colspan="2"></th>
</tr>
<tr>
    <th>Vendor</th>
    <th>Item</th>
    <th>Unit Size</th> 
    <th>Case Size</th> 
    <th>Case Cost</th> 
    <th>Unit Size</th> 
    <th>Case Size</th> 
    <th>Unit Cost</th> 
    <th>Case Cost</th> 
    <th>Order Date</th>
    <th>Update Cost</th>
</tr>
{$table}
</table>
<div class="form-group">
    <button type="submit" class="btn btn-default">Save Cost Updates</button>
    <input type="hidden" name="store" value="{$store}" />
</div>
</form>
<script>
function useThisValue(event) {
    var elem = event.target;
    var val = $(elem).html().trim();
    $(elem).parent('tr').find('input.cost-field').val(val);
    $(elem).parent('tr').removeClass('warning');
}
</script>
HTML;
    }

    public function helpContent()
    {
        return <<<HTML
<p>
This lists prepared inventory items as well as the most recent corresponding invoice.
For inventory items there's a unit size, case size, and nominal "case" cost. For the
invoice there's also unit size and case size as well as both unit and case costs. There
may be some adjustment necessary when the sizes don't agree.
</p>
<p>
To use the case cost from the invoice, click that value. To use the unit cost from the
invoice, click that value. You can also enter simple arithmetic (e.g., 19.99 / 5) in 
the "Update Cost" field and it'll do the calculation. When you're done selecting updates
click Save down at the bottom.
</p>
<p>
Click the inovoice date to view that invoice. Opening this in a new tab is suggested so
the current set of updates aren't lost.
</p>
HTML;
    }

}

FannieDispatch::conditionalExec();

