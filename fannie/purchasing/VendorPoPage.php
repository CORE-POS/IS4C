<?php

include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../classlib2.0/FannieAPI.php');
}

class VendorPoPage extends FannieRESTfulPage 
{
    protected $header = 'Vendor Purchase Orders';
    protected $title = 'Vendor Purchase Orders';

    public $description = '[Vendor Purchase Orders] lists pending orders and completed invoices for a given vendor.';
    public $discoverable = false;

    protected $must_authenticate = true;

    protected function get_id_view()
    {
        $pager = FormLib::get('pager', '');

        $query = 'SELECT p.orderID, p.vendorID, MIN(creationDate) as creationDate,
                MIN(placedDate) as placedDate, COUNT(i.orderID) as records,
                SUM(i.unitCost*i.caseSize*i.quantity) as estimatedCost,
                SUM(i.receivedTotalCost) as receivedCost, v.vendorName,
                MAX(i.receivedDate) as receivedDate,
                MAX(p.vendorInvoiceID) AS vendorInvoiceID,
                MAX(s.description) AS storeName,
                MAX(p.placed) AS placed,
                SUM(CASE WHEN isSpecialOrder THEN i.quantity ELSE 0 END) AS soFlag
            FROM PurchaseOrder as p
                LEFT JOIN PurchaseOrderItems AS i ON p.orderID = i.orderID
                LEFT JOIN vendors AS v ON p.vendorID=v.vendorID
                LEFT JOIN Stores AS s ON p.storeID=s.storeID
            WHERE p.vendorID=?
            GROUP BY p.orderID, p.vendorID, v.vendorName ';
        $args = array($this->id);
        if ($pager != '') {
            $query .= ' HAVING MIN(creationDate) < ? ';
            $args[] = $pager;
        }
        $query .= 'ORDER BY MIN(creationDate) DESC';
        $query = $this->connection->addSelectLimit($query, 100);
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, $args);

        $ret = '<table class="table table-striped table-bordered tablesorter table-float">';
        $ret .= '<thead style="background: #fff;"><tr>
            <th class="thead">Created</th>
            <th class="thead hidden-xs">Invoice#</th>
            <th class="thead hidden-xs">Store</th>
            <th class="thead">Vendor</th>
            <th class="thead"># Items</th>
            <th class="thead hidden-xs">Est. Cost</th>
            <th class="thead hidden-xs">Placed</th>
            <th class="thead hidden-xs">Received</th>
            <th class="thead hidden-xs">Rec. Cost</th></tr></thead><tbody>';
        $nextPage = false;
        $count = 0;
        while ($row = $this->connection->fetchRow($res)) {
            list($date, $time) = explode(' ', $row['creationDate']);
            $ret .= sprintf('<tr %s><td><a href="ViewPurchaseOrders.php?id=%d">%s <span class="hidden-xs">%s</span></a></td>
                    <td class="hidden-xs">%s</td>
                    <td class="hidden-xs">%s</td>
                    <td>%s</td><td>%d</td><td class="hidden-xs">%.2f</td>
                    <td class="hidden-xs">%s</td><td class="hidden-xs">%s</td><td class="hidden-xs">%.2f</td></tr>',
                    ($row['soFlag'] ? 'class="success" title="Contains special order(s)" ' : ''),
                    $row['orderID'],
                    $date, $time, $row['vendorInvoiceID'], $row['storeName'], $row['vendorName'], $row['records'],
                    $row['estimatedCost'],
                    ($row['placed'] == 1 ? $row['placedDate'] : '&nbsp;'),
                    (!empty($row['receivedDate']) ? $row['receivedDate'] : '&nbsp;'),
                    (!empty($row['receivedCost']) ? $row['receivedCost'] : 0.00)
            );
            $nextPage = $row['creationDate'];
            $count++;
        }
        $ret .= '</tbody></table>';
        if ($count == 100 && $nextPage) {
            $ret .= sprintf('<p><a href="VendorPoPage.php?id=%d&pager=%s">Next</a></p>',
                $this->id, urlencode($nextPage));
        }

        return $ret;
    }
}

FannieDispatch::conditionalExec();

