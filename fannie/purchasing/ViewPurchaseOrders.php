<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

use COREPOS\Fannie\API\lib\Store;

include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ViewPurchaseOrders extends FannieRESTfulPage 
{
    protected $header = 'Purchase Orders';
    protected $title = 'Purchase Orders';

    public $description = '[View Purchase Orders] lists pending orders and completed invoices.';

    protected $must_authenticate = true;

    private $show_all = true;

    public function preprocess()
    {
        $this->addRoute(
            'get<pending>',
            'get<placed>',
            'post<id><setPlaced>',
            'get<id><export>',
            'get<id><sendAs>',
            'get<id><receive>',
            'get<id><receiveAll>',
            'get<id><sku>',
            'get<id><recode>',
            'post<id><sku><recode>',
            'post<id><sku><qty><cost>',
            'post<id><sku><upc><brand><description><orderQty><orderCost><receiveQty><receiveCost>',
            'post<id><sku><qty><receiveAll>',
            'post<id><note>',
            'post<id><sku><isSO>',
            'post<id><sku><adjust>',
            'get<merge>'
        );
        if (FormLib::get('all') === '0')
            $this->show_all = false;
        return parent::preprocess();
    }

    /**
      Merge a set of purchase orders into one
      The highest ID order is retained. Items and notes from
      lower ID orders are added to the highest ID order then
      the lower ID orders are deleted.
    */
    protected function get_merge_handler()
    {
        $this->connection->selectDB($this->config->get('OP_DB'));
        $dbc = $this->connection;
        sort($this->merge);
        $mergeID = array_pop($this->merge);
        $moveP = $dbc->prepare('UPDATE PurchaseOrderItems SET orderID=? WHERE orderID=?');
        $noteP = $dbc->prepare('SELECT notes FROM PurchaseOrderNotes WHERE orderID=?');
        $delP = $dbc->prepare('DELETE FROM PurchaseOrder WHERE orderID=?');
        $delNoteP = $dbc->prepare('DELETE FROM PurchaseOrderNotes WHERE orderID=?');
        $mergeNotes = $dbc->getValue($noteP, array($mergeID));
        foreach ($this->merge as $orderID) {
            $moved = $dbc->execute($moveP, array($mergeID, $orderID));
            if ($moved) {
                $note = $dbc->getValue($noteP, array($orderID));
                $mergeNotes .= (strlen($mergeNotes) > 0 ? "\n" : "") . $note;
                $dbc->execute($delP, array($orderID));
                $dbc->execute($delNoteP, array($orderID));
            }
        }
        $upP = $dbc->prepare('UPDATE PurchaseOrderNotes SET notes=? WHERE orderID=?');
        $dbc->execute($upP, array($mergeNotes, $mergeID));

        return 'ViewPurchaseOrders.php?init=pending';
    }

    protected function post_id_sku_adjust_handler()
    {
        $this->connection->selectDB($this->config->get('OP_DB'));
        $halfP = $this->connection->prepare('
            SELECT halfCases FROM PurchaseOrder AS o INNER JOIN vendors AS v ON o.vendorID=v.vendorID WHERE o.orderID=?'
        );
        $halved = $this->connection->getValue($halfP, array($this->id));
        if ($halved) {
            $this->adjust /= 2;
        }
        $item = new PurchaseOrderItemsModel($this->connection);
        $item->orderID($this->id);
        $item->sku($this->sku);
        $item->load();
        $next = $item->quantity() + $this->adjust;
        if ($next < 0) {
            $next = 0;
        }
        $item->quantity($next);
        $item->save();
        echo json_encode(array('qty' => $next));

        return false;
    }

    /**
      Callback: save item's isSpecialOrder setting
    */
    protected function post_id_sku_isSO_handler()
    {
        $this->connection->selectDB($this->config->get('OP_DB'));
        $item = new PurchaseOrderItemsModel($this->connection);
        $item->orderID($this->id);
        $item->sku($this->sku);
        $item->isSpecialOrder($this->isSO);
        $item->save();

        return false;
    }

    /**
      Callback: save notes associated with order
    */
    protected function post_id_note_handler()
    {
        $this->connection->selectDB($this->config->get('OP_DB'));
        $note = new PurchaseOrderNotesModel($this->connection);
        $note->orderID($this->id);
        $note->notes(trim($this->note));
        if ($note->notes() === '') {
            $note->delete();
        } else {
            $note->save();
        }

        return false;
    }

    protected function get_id_export_handler()
    {
        if (!file_exists('exporters/'.$this->export.'.php'))
            return $this->unknown_request_handler();
        include_once('exporters/'.$this->export.'.php');    
        if (!class_exists($this->export))
            return $this->unknown_request_handler();

        $exportObj = new $this->export();
        $exportObj->send_headers();
        $exportObj->export_order($this->id);
        return false;
    }

    private function csvToHtml($csv)
    {
        $lines = explode("\r\n", $csv);
        $ret = "<table border=\"1\">\n";
        $para = '';
        foreach ($lines as $line) {
            $row = str_getcsv($line);
            if (count($row) == 1) {
                $para .= $row[0] . '<br />';
            } elseif (count($row) > 1) {
                $ret .= "<tr>\n";
                $rowEmpty = true;
                $trow = '';
                foreach ($row as $entry) {
                    if (trim($entry) !== '') {
                        $rowEmpty = false;
                    }
                    $trow .= '<td>' . trim($entry) . '</td>';
                }
                if (!$rowEmpty) {
                    $ret .= $trow;
                }
                $ret .= "</tr>\n";
            }
        }
        $ret .= "</table>\n";
        if (strlen($para) > 0) {
            $ret .= '<p>' . $para . '</p>';
        }

        return $ret;
    }

    protected function get_id_sendAs_handler()
    {
        if (!file_exists('exporters/'.$this->sendAs.'.php')) {
            return $this->unknownRequestHandler();
        }
        include_once('exporters/'.$this->sendAs.'.php');    
        if (!class_exists($this->sendAs)) {
            return $this->unknownRequestHandler();
        }

        ob_start();
        $exportObj = new $this->sendAs();
        $exportObj->export_order($this->id);
        $exported = ob_get_clean();

        $html = $this->csvToHtml($exported);
        $nonHtml = str_replace("\r", "", $exported);

        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $place = $dbc->prepare("UPDATE PurchaseOrder SET placed=1, placedDate=? WHERE orderID=?");

        $order = new PurchaseOrderModel($dbc);
        $order->orderID($this->id);
        $order->load();
        $vendor = new VendorsModel($dbc);
        $vendor->vendorID($order->vendorID());
        $vendor->load();
        if (!filter_var($vendor->email(), FILTER_VALIDATE_EMAIL)) {
            return $this->unknownRequestHandler();
        }

        $userP = $dbc->prepare("SELECT email, real_name FROM Users WHERE name=?");
        $userInfo = $dbc->getRow($userP, array($this->current_user));
        $userEmail = $userInfo['email'];
        $userRealName = $userInfo['real_name'];

        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->Host = '127.0.0.1';
        $mail->Port = 25;
        $mail->SMTPAuth = false;
        $mail->SMTPAutoTLS = false;
        $mail->From = $this->config->get('PO_EMAIL');
        $mail->FromName = $this->config->get('PO_EMAIL_NAME');
        $mail->isHTML = true;
        $mail->addAddress($vendor->email());
        if ($userEmail && filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
            $mail->addCC($userEmail);
            $mail->addReplyTo($userEmail);
            $mail->From = $userEmail;
            if (!empty($userRealName)) {
                $mail->FromName = $userRealName;
            }
        }
        $mail->Subject = 'Purchase Order ' . date('Y-m-d');
        $mail->Body = 'The same order information is also attached. Reply to this email to reach the person who sent it.';
        $mail->AltBody = $mail->Body;
        $mail->Body = '<p>' . $mail->Body . '</p>' . $html;
        $mail->AltBody .= $nonHtml;
        $mail->addStringAttachment(
            $exported,
            'Order ' . date('Y-m-d') . '.' . $exportObj->extension,
            'base64',
            $exportObj->mime_type
        );
        $sent = $mail->send();
        if ($sent) {
            $dbc->execute($place, array(date('Y-m-d H:i:s'), $this->id));
            $order->placed(1);
            $order->placedDate(date('Y-m-d H:i:s'));
            $order->save();
        } else {
            echo "Failed to send email! Do not assume the order was placed.";
            exit;
        }
    
        return 'ViewPurchaseOrders.php?id=' . $this->id;
    }

    protected function post_id_setPlaced_handler()
    {
        $this->connection->selectDB($this->config->get('OP_DB'));
        $model = new PurchaseOrderModel($this->connection);
        $model->orderID($this->id);
        $model->load();
        $model->placed($this->setPlaced);
        if ($this->setPlaced == 1) {
            $model->placedDate(date('Y-m-d H:m:s'));
        } else {
            $model->placedDate(0);
        }
        $model->save();

        $poi = new PurchaseOrderItemsModel($this->connection);
        $poi->orderID($this->id);
        $cache = new InventoryCacheModel($this->connection);
        if (!class_exists('SoPoBridge')) {
            include(__DIR__ . '/../ordering/SoPoBridge.php');
        }
        $bridge = new SoPoBridge($this->connection, $this->config);
        foreach ($poi->find() as $item) {
            $cache->recalculateOrdered($item->internalUPC(), $model->storeID());
            if ($this->setPlaced ==1 && $poi->isSpecialOrder()) {
                $soID = substr($poi->internalUPC(), 0, 9);
                $transID = substr($poi->internalUPC(), 9);
                $bridge->markAsPlaced($soID, $transID);
            }
        }
        echo ($this->setPlaced == 1) ? $model->placedDate() : 'n/a';

        return false;
    }

    protected function get_pending_handler()
    {
        echo $this->get_orders(0);
        return false;
    }

    protected function get_placed_handler()
    {
        echo $this->get_orders(1);
        return false;
    }

    protected function get_orders($placed, $store=0, $month=0, $year=0)
    {
        $dbc = $this->connection;
        $store = FormLib::get('store', $store);

        $month = FormLib::get('month', $month);
        $year = FormLib::get('year', $year);
        if ($month == 'Last 30 days') {
            $start = date('Y-m-d', strtotime('30 days ago'));
            $end = date('Y-m-d 23:59:59');
        } else {
            $start = date('Y-m-01 00:00:00', mktime(0, 0, 0, $month, 1, $year));
            $end = date('Y-m-t 23:59:59', mktime(0, 0, 0, $month, 1, $year));
        }
        $args = array($start, $end);
        
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
            WHERE creationDate BETWEEN ? AND ? ';
        if (!$this->show_all) {
            $query .= 'AND userID=? ';
        }
        if ($store != 0) {
            $query .= ' AND p.storeID=? ';
            $args[] = $store;
        }
        $query .= 'GROUP BY p.orderID, p.vendorID, v.vendorName 
                   ORDER BY MIN(creationDate) DESC';
        if (!$this->show_all) $args[] = FannieAuth::getUID($this->current_user);

        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, $args);

        $ret = '<ul class="nav nav-tabs" role="tablist">
                <li role="presentation" class="' . ($placed ? '' : 'active') . '">
                    <a href="#pending-pane" aria-controls="pending-pane" role="tab" data-toggle="tab">Pending</a>
                </li>
                <li role="presentation" class="' . ($placed ? 'active' : '') . '">
                    <a href="#placed-pane" aria-controls="placed-pane" role="tab" data-toggle="tab">Placed</a>
                </li>
                </ul>
                <div class="tab-content">';

        $tPending = '<div id="pending-pane" class="tab-pane table-responsive ' . ($placed ? '' : 'active') . '">
            <table class="table table-striped table-bordered tablesorter table-float">';
        $tPlaced = '<div id="placed-pane" class="tab-pane table-responsive ' . ($placed ? 'active' : '') . '">
            <table class="table table-striped table-bordered tablesorter table-float">';
        $headers = '<thead style="background: #fff;"><tr>
            <th class="thead">Created</th>
            <th class="thead">Invoice#</th>
            <th class="thead">Store</th>
            <th class="thead">Vendor</th>
            <th class="thead"># Items</th>
            <th class="thead">Est. Cost</th>
            <th class="thead">Placed</th>
            <th class="thead">Received</th>
            <th class="thead">Rec. Cost</th></tr></thead><tbody>';
        $tPending .= $headers;
        $tPlaced .= $headers;
        $mergable = array();
        while ($row = $dbc->fetchRow($result)) {
            if ($row['placed']) {
                $tPlaced .= $this->orderRowToTable($row, $placed);
            } else {
                $tPending .= $this->orderRowToTable($row, $placed);
                if (!isset($mergable[$row['vendorID']])) {
                    $mergable[$row['vendorID']] = array('orders'=>array(), 'name'=>$row['vendorName']);
                }
                $mergable[$row['vendorID']]['orders'][] = $row['orderID'];
            }
        }
        $tPlaced .= '</tbody></table></div>';
        $mergable = array_filter($mergable, function($i) { return count($i['orders']) > 1; });
        $tPending .= '</tbody></table>';
        foreach ($mergable as $m) {
            $idStr = implode('&', array_map(function($i) { return 'merge[]=' . $i; }, $m['orders']));
            $tPending .= sprintf('<a href="ViewPurchaseOrders.php?%s">Merge %s Orders</a><br />', $idStr, $m['name']);
        }
        $tPending .= '</div>';

        $ret .= $tPending . $tPlaced . '</div>';

        return $ret;
    }

    private function orderRowToTable($row, $placed)
    {
        return sprintf('<tr %s><td><a href="ViewPurchaseOrders.php?id=%d">%s</a></td>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td><td>%d</td><td>%.2f</td>
                <td>%s</td><td>%s</td><td>%.2f</td></tr>',
                ($row['soFlag'] ? 'class="success" title="Contains special order(s)" ' : ''),
                $row['orderID'],
                $row['creationDate'], $row['vendorInvoiceID'], $row['storeName'], $row['vendorName'], $row['records'],
                $row['estimatedCost'],
                ($placed == 1 ? $row['placedDate'] : '&nbsp;'),
                (!empty($row['receivedDate']) ? $row['receivedDate'] : '&nbsp;'),
                (!empty($row['receivedCost']) ? $row['receivedCost'] : 0.00)
        );
    }

    protected function delete_id_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $order = new PurchaseOrderModel($dbc);
        $order->orderID($this->id);
        $order->delete();

        $items = new PurchaseOrderItemsModel($dbc);
        $items->orderID($this->id);
        foreach ($items->find() as $item) {
            $item->delete();
        }

        echo 'deleted';

        return false;
    }

    protected function post_id_sku_qty_receiveAll_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $model = new PurchaseOrderItemsModel($dbc);
        $model->orderID($this->id);
        $re_date = FormLib::get('re-date', false);
        $uid = FannieAuth::getUID($this->current_user);
        for ($i=0; $i<count($this->sku); $i++) {
            $model->sku($this->sku[$i]);
            $model->load();
            $model->receivedQty($this->qty[$i]);
            $model->receivedBy($uid);
            $model->receivedTotalCost($model->receivedQty()*$model->unitCost());
            if ($model->receivedDate() === null || $re_date) {
                $model->receivedDate(date('Y-m-d H:i:s'));
            }
            $model->save();
        }

        $prep = $dbc->prepare('
            SELECT o.storeID, i.internalUPC
            FROM PurchaseOrder AS o
                INNER JOIN PurchaseOrderItems AS i ON o.orderID=i.orderID
            WHERE o.orderID=?');
        $res = $dbc->execute($prep, array($this->id));
        $cache = new InventoryCacheModel($dbc);
        while ($row = $dbc->fetchRow($res)) {
            $cache->recalculateOrdered($row['internalUPC'], $row['storeID']);
        }

        return 'ViewPurchaseOrders.php?id=' . $this->id;
    }

    protected function post_id_sku_recode_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $model = new PurchaseOrderItemsModel($dbc);
        $model->orderID($this->id);

        for ($i=0; $i<count($this->sku); $i++) {
            if (!isset($this->recode[$i])) {
                continue;
            }
            $model->sku($this->sku[$i]);
            $model->salesCode($this->recode[$i]);
            $model->save();
        }

        return filter_input(INPUT_SERVER, 'PHP_SELF') . '?id=' . $this->id;
    }

    protected function get_id_recode_view()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $model = new PurchaseOrderItemsModel($dbc);
        $model->orderID($this->id);

        $ret = '<form method="post" action="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '">
            <input type="hidden" name="id" value="' . $this->id . '" />
            <table class="table table-striped">
            <tr>
                <td><input type="text" placeholder="Change All" class="form-control" 
                    onchange="if (this.value != \'\') { $(\'.recode-sku\').val(this.value); }" /></td>
                <th>SKU</th>
                <th>UPC</th>
                <th>Brand</th>
                <th>Description</th>
            </tr>';
        $accounting = $this->config->get('ACCOUNTING_MODULE');
        if (!class_exists($accounting)) {
            $accounting = '\COREPOS\Fannie\API\item\Accounting';
        }
        foreach ($model->find() as $item) {
            $ret .= sprintf('<tr>
                <td><input class="form-control recode-sku" type="text" 
                    name="recode[]" value="%s" required /></td>
                <td>%s<input type="hidden" name="sku[]" value="%s" /></td>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                </tr>',
                $accounting::toPurchaseCode($item->salesCode()),
                $item->sku(), $item->sku(),
                $item->internalUPC(),
                $item->brand(),
                $item->description()
            );
        }
        $ret .= '</table>
            <p><button type="submit" class="btn btn-default">Save Codings</button>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <a href="ViewPurchaseOrders.php?id=' . $this->id . '" class="btn btn-default">Back to Order</a>
            </p>
        </form>';

        return $ret;
    }

    private $empty_vendor = array(
        'vendorName'=>'',
        'phone'=>'',
        'fax'=>'',
        'email'=>'',
        'address'=>'',
        'city'=>'',
        'state'=>'',
        'zip'=>'',
        'notes'=>'',
    );

    protected function get_id_view()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $order = new PurchaseOrderModel($dbc);
        $order->orderID($this->id);
        $order->load();
        $orderObj = $order->toStdClass();
        $orderObj->placedDate = $orderObj->placed ? $orderObj->placedDate : 'n/a';
        $placedCheck = $orderObj->placed ? 'checked' : '';
        $init = $orderObj->placed ? 'init=placed' : 'init=pending';
        $pendingOnlyClass = 'pending-only' . ($orderObj->placed ? ' collapse' : '');
        $placedOnlyClass = 'placed-only' . ($orderObj->placed ? '' : ' collapse');
        $sentDate = new DateTime($order->creationDate());
        $today = new DateTime();
        // ban adjustment to placed orders after 90 days
        if ($today->diff($sentDate)->format('%a') >= 90) {
            $placedOnlyClass .= ' collapse';
        }
    
        $notes = $dbc->prepare('SELECT notes FROM PurchaseOrderNotes WHERE orderID=?');
        $notes = $dbc->getValue($notes, $this->id);
        $vname = $dbc->prepare('SELECT * FROM vendors WHERE vendorID=?');
        $vendor = $dbc->getRow($vname, array($orderObj->vendorID));
        if ($vendor) {
            $vendor['notes'] = nl2br($vendor['notes']);
        } else {
            $vendor = $this->empty_vendor;
        }
        $sname = $dbc->prepare('SELECT description FROM Stores WHERE storeID=?');
        $sname = $dbc->getValue($sname, array($orderObj->storeID));

        $batchStart = date('Y-m-d', strtotime('+30 days'));
        $batchP = $dbc->prepare("
            SELECT b.batchName, b.startDate, b.endDate
            FROM batchList AS l
                INNER JOIN batches AS b ON l.batchID=b.batchID
                INNER JOIN StoreBatchMap AS m ON l.batchID=m.batchID
            WHERE l.upc=?
                AND m.storeID=?
                AND b.startDate <= ?
                AND b.endDate >= " . $dbc->curdate() . "
                AND b.discounttype > 0
        ");

        $exportOpts = '';
        foreach (COREPOS\Fannie\API\item\InventoryLib::orderExporters() as $class => $name) {
            $selected = $class === $this->config->get('DEFAULT_PO_EXPORT') ? 'selected' : '';
            $exportOpts .= '<option ' . $selected . ' value="'.$class.'">'.$name.'</option>';
        }
        $exportEmail = '';
        if (!$orderObj->placed && filter_var($vendor['email'], FILTER_VALIDATE_EMAIL)) {
            $exportEmail = '<button type="submit" class="btn btn-default btn-sm" onclick="doSend(' . $this->id . ');
                return false;" title="Email order to ' . $vendor['email'] . '" >Send via Email</button>';
        }
        $uname = FannieAuth::getName($order->userID());
        if (!$uname) {
            $uname = 'n/a';
        }
        $receivedP = $dbc->prepare("SELECT DISTINCT u.name FROM PurchaseOrderItems AS p INNER JOIN Users AS u ON p.receivedBy=u.uid WHERE p.orderID=?");
        $receivers = array();
        $receivedR = $dbc->execute($receivedP, array($this->id));
        while ($row = $dbc->fetchRow($receivedR)) {
            $receivers[] = $row['name'];
        }
        $uname .= count($receivers) > 0 ? '<br /><b>Received by</b>: ' . implode(',', $receivers) : '';

        $ret = <<<HTML
<p>
    <div class="form-inline">
        <b>Store</b>: {$sname}
        &nbsp;&nbsp;&nbsp;&nbsp;
        <b>Vendor</b>: <a href="../item/vendors/VendorIndexPage.php?vid={$orderObj->vendorID}">{$vendor['vendorName']}</a>
        &nbsp;&nbsp;&nbsp;&nbsp;
        <b>Created</b>: {$orderObj->creationDate}
        &nbsp;&nbsp;&nbsp;&nbsp;
        <b>Placed</b>: <span id="orderPlacedSpan">{$orderObj->placedDate}</span>
        <input type="checkbox" {$placedCheck} id="placedCheckbox"
                onclick="togglePlaced({$this->id});" />
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        Export as: <select id="exporterSelect" class="form-control input-sm">
            {$exportOpts}
        </select> 
        <button type="submit" class="btn btn-default btn-sm" onclick="doExport({$this->id});return false;">Export</button>
        {$exportEmail}
        &nbsp;&nbsp;&nbsp;
        <a type="button" class="btn btn-default btn-sm" 
            href="ViewPurchaseOrders.php?{$init}">All Orders</a>
    </div>
</p>
<div class="row">
    <div class="col-sm-6">
        <table class="table table-bordered small">
            <tr>
                <td><b>PO#</b>: {$orderObj->vendorOrderID}</td>
                <td><b>Invoice#</b>: {$orderObj->vendorInvoiceID}</td>
                <th colspan="2">Coding(s)</th>
            </tr>
            <tr> 
                <td rowspan="10" colspan="2">
                    <label>Notes</label>
                    <textarea class="form-control" 
                        onkeypress="autoSaveNotes({$this->id}, this);">{$notes}</textarea>
                </td>
            {{CODING}}
            <tr>
                <td><b>Created by</b>: {$uname}</td>
                <td>&nbsp;</td>
            </tr>
        </table>
    </div>
    <div class="col-sm-6">
    <p>
        <a class="btn btn-default btn-sm {$pendingOnlyClass}"
            href="EditOnePurchaseOrder.php?id={$this->id}">Add Items</a>
        <span class="{$pendingOnlyClass}">&nbsp;&nbsp;&nbsp;&nbsp;</span>
        <button class="btn btn-default btn-sm {$pendingOnlyClass}" 
            onclick="deleteOrder({$this->id}); return false;">Delete Order</button>
        <a class="btn btn-default btn-sm {$placedOnlyClass}"
            href="ManualPurchaseOrderPage.php?id={$orderObj->vendorID}&adjust={$this->id}">Edit Order</a>
        <span class="{$placedOnlyClass}">&nbsp;&nbsp;&nbsp;&nbsp;</span>
        <a class="btn btn-default btn-sm {$placedOnlyClass}" id="receiveBtn"
            href="ViewPurchaseOrders.php?id={$this->id}&receive=1">Receive Order</a>
        <span class="{$placedOnlyClass}">&nbsp;&nbsp;&nbsp;&nbsp;</span>
        <a class="btn btn-default btn-sm {$placedOnlyClass}" id="receiveBtn"
            href="TransferPurchaseOrder.php?id={$this->id}">Transfer Order</a>
        <span class="{$placedOnlyClass}">&nbsp;&nbsp;&nbsp;&nbsp;</span>
        <a class="btn btn-default btn-sm {$placedOnlyClass}"
            href="ViewPurchaseOrders.php?id={$this->id}&recode=1">Alter Codings</a>
    </p>
<div class="panel panel-default"><div class="panel-body">
Ph: {$vendor['phone']}<br />
Fax: {$vendor['fax']}<br />
Email: {$vendor['email']}<br />
{$vendor['address']}, {$vendor['city']}, {$vendor['state']} {$vendor['zip']}<br />
{$vendor['notes']}
</div></div>
HTML;
        $ret .= '</div></div>';

        $model = new PurchaseOrderItemsModel($dbc);
        $model->orderID($this->id);
        $codings = array();
        $accounting = $this->config->get('ACCOUNTING_MODULE');
        if (!class_exists($accounting)) {
            $accounting = '\COREPOS\Fannie\API\item\Accounting';
        }

        $ret .= '<table class="table tablesorter table-bordered small table-float"><thead style="background:#fff;">';
        $ret .= '<tr>
            <th class="thead">Coding</th>
            <th class="thead">SKU</th>
            <th class="thead">UPC</th>
            <th class="thead">Brand</th>
            <th class="thead">Description</th>
            <th class="thead">Unit Size</th>
            <th class="thead">Units/Case</th>
            <th class="thead">Cases</th>
            <th class="thead">Est. Cost</th>
            <th class="thead">Received</th>
            <th class="thead">Rec. Qty</th>
            <th class="thead">Rec. Cost</th>
            <th class="thead">SO</th></tr></thead><tbody>';
        $count = 0;
        foreach ($model->find() as $obj) {
            $css = $this->qtyToCss($order->placed(), $obj->quantity(),$obj->receivedQty());
            if (!$order->placed()) {
                $batchR = $dbc->execute($batchP, array($obj->internalUPC(), $orderObj->storeID, $batchStart));
                $title = '';
                while ($batchW = $dbc->fetchRow($batchR)) {
                    $title .= $batchW['batchName'] . ' (';
                    $title .= date('M j', strtotime($batchW['startDate'])) . ' - ';
                    $title .= date('M j', strtotime($batchW['endDate'])) . ') ';
                }
                if ($title) {
                    $css = 'class="info" title="' . $title . '"';
                }
            }
            if ($obj->isSpecialOrder()) {
                $css = 'class="success" title="Special order"';
            }
            if ($obj->salesCode() == '') {
                $code = $obj->guessCode();
                $obj->salesCode($code);
                $obj->save();
            }
            $coding = (int)$obj->salesCode();
            $coding = $accounting::toPurchaseCode($coding);
            if (!isset($codings[$coding])) {
                $codings[$coding] = 0.0;
            }
            $codings[$coding] += $obj->receivedTotalCost();
            $ret .= sprintf('<tr %s><td>%d</td><td>%s</td>
                    <td><a href="../item/ItemEditorPage.php?searchupc=%s">%s</a></td><td>%s</td><td>%s</td>
                    <td>%s</td><td>%s</td>
                    <td><span id="qty%d">%s</span> <span class="%s pull-right">
                        <a href="" onclick="itemInc(%d, \'%s\', %d); return false;"><span class="glyphicon glyphicon-chevron-up small" /></a>
                        <br />
                        <a href="" onclick="itemDec(%d, \'%s\', %d); return false;"><span class="glyphicon glyphicon-chevron-down small" /></a>
                        </span>
                    </td>
                    <td>%.2f</td>
                    <td>%s</td><td>%s</td><td>%.2f</td>
                    <td>
                        <select class="form-control input-sm" onchange="isSO(%d, \'%s\', this.value);">
                        %s
                        </select>
                    </tr>',
                    $css,
                    $accounting::toPurchaseCode($obj->salesCode()),
                    $obj->sku(),
                    $obj->internalUPC(), $obj->internalUPC(),
                    $obj->brand(),
                    $obj->description(),
                    $obj->unitSize(), $obj->caseSize(),
                    $count, $obj->quantity(), $pendingOnlyClass, $this->id, $obj->sku(), $count, $this->id, $obj->sku(), $count,
                    ($obj->quantity() * $obj->caseSize() * $obj->unitCost()),
                    strtotime($obj->receivedDate()) ? date('Y-m-d', strtotime($obj->receivedDate())) : 'n/a',
                    $obj->receivedQty(),
                    $obj->receivedTotalCost(),
                    $this->id, $obj->sku(), $this->specialOrderSelect($obj->isSpecialOrder())
            );
            $count++;
        }
        $ret .= '</tbody></table>';

        $coding_rows = '';
        foreach ($codings as $coding => $ttl) {
            $coding_rows .= sprintf('<tr><td>%d</td><td>%.2f</td></tr>',
                $coding, $ttl);
        }
        $ret = str_replace('{{CODING}}', $coding_rows, $ret);

        $this->add_script('js/view.js');
        $this->add_script('../src/javascript/tablesorter/jquery.tablesorter.min.js');
        $this->addScript($this->config->get('URL') . 'src/javascript/jquery.floatThead.min.js');
        $this->addOnloadCommand("\$('.tablesorter').tablesorter();\n");
        $this->addOnloadCommand("\$('.table-float').floatThead();\n");

        return $ret;
    }

    private function qtyToCss($placed, $ordered, $received)
    {
        if (!$placed) {
            return '';
        } elseif ($received == 0 && $ordered != 0) {
            return 'class="danger"';
        } elseif ($received < $ordered) {
            return 'class="warning"';
        } else {
            return '';
        }
    }

    private function specialOrderSelect($isSO)
    {
        if ($isSO) {
            return '<option value="1" selected>Yes</option><option value="0">No</option>';
        } else {
            return '<option value="1">Yes</option><option value="0" selected>No</option>';
        }
    }

    /**
      Receiving interface for processing enter recieved costs and quantities
      on an order
    */
    protected function get_id_receive_view()
    {
        $this->add_script('js/view.js');
        $ret = '
            <p>Receiving order #<a href="ViewPurchaseOrders.php?id=' . $this->id . '">' . $this->id . '</a></p>
            <p><div class="form-inline">
                <form onsubmit="receiveSKU(); return false;" id="receive-form">
                <label>SKU</label>
                <input type="text" name="sku" id="sku-in" class="form-control" />
                <input type="hidden" name="id" value="' . $this->id . '" />
                <button type="submit" class="btn btn-default">Continue</button>
                <a href="?id=' . $this->id . '&receiveAll=1" class="btn btn-default btn-reset">All</a>
                </form>
            </div></p>
            <div id="item-area">
            </div>';
        $this->addOnloadCommand("\$('#sku-in').focus();\n");

        return $ret;
    }

    protected function get_id_receiveAll_view()
    {
        $dbc = FannieDB::getReadOnly($this->config->get('OP_DB'));
        $poi = new PurchaseOrderItemsModel($dbc);
        $poi->orderID($this->id);
        $ret = '<form method="post">
            <input type="hidden" name="id" value="' . $this->id . '" />
            <input type="hidden" name="receiveAll" value="1" />
            <table class="table table-bordered table-striped">
            <tr>
                <th>SKU</th>
                <th>Brand</th>
                <th>Description</th>
                <th>Unit Size</th>
                <th>Qty Ordered</th>
                <th>Qty Receveived</th>
            </tr>';
        foreach ($poi->find() as $item) {
            $qty = $item->caseSize() * $item->quantity();
            $ret .= sprintf('<tr>
                <td><input type="hidden" name="sku[]" value="%s" />%s</td>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td>%.2f</td>
                <td><input type="text" class="form-control input-sm" name="qty[]" value="%.2f" /></td>
                </tr>',
                $item->sku(), $item->sku(),
                $item->brand(),
                $item->description(),
                $item->unitSize(),
                $qty,
                ($item->receivedQty() === null ? $qty : $item->receivedQty())
            );
        }
        $ret .= '</table>
            <p>
                <button type="submit" class="btn btn-default btn-core">Receive Order</button>
                <button type="reset" class="btn btn-default btn-reset">Reset</button>
                &nbsp;&nbsp;&nbsp;&nbsp;
                <label>Update Received Date <input type="checkbox" name="re-date" value="1" /></label>
            </p>
            </form>';

        return $ret;
    }

    /**
      Receiving AJAX callback. For items that were in
      the purchase order, just save the received quantity and cost
    */
    protected function post_id_sku_qty_cost_handler()
    {
        $dbc = $this->connection;
        $model = new PurchaseOrderItemsModel($dbc);
        $model->orderID($this->id);
        $model->sku($this->sku);
        $model->receivedQty($this->qty);
        $model->receivedTotalCost($this->cost);
        $model->receivedBy(FannieAuth::getUID($this->current_user));
        if ($model->receivedDate() === null) {
            $model->receivedDate(date('Y-m-d H:i:s'));
        }
        $model->save();

        return false;
    }

    /**
      Receiving AJAX callback. For items that were NOT in
      the purchase order, create a whole record for the
      item that showed up. 
    */
    protected function post_id_sku_upc_brand_description_orderQty_orderCost_receiveQty_receiveCost_handler()
    {
        $dbc = $this->connection;
        $model = new PurchaseOrderItemsModel($dbc);
        $model->orderID($this->id);
        $model->sku($this->sku);
        $model->internalUPC(BarcodeLib::padUPC($this->upc));
        $model->brand($this->brand);
        $model->description($this->description);
        $model->quantity($this->orderQty);
        $model->unitCost($this->orderCost);
        $model->caseSize(1);
        $model->receivedQty($this->receiveQty);
        $model->receivedTotalCost($this->receiveCost);
        $model->receivedDate(date('Y-m-d H:i:s'));
        $model->receivedBy(FannieAuth::getUID($this->current_user));
        $model->save();

        return false;
    }

    /**
      Receiving AJAX callback.
      Lookup item in the order and display form fields
      to enter required info 
    */
    protected function get_id_sku_handler()
    {
        $dbc = $this->connection;
        $model = new PurchaseOrderItemsModel($dbc);
        $model->orderID($this->id);
        $model->sku($this->sku);
        // lookup by SKU but if nothing is found
        // try using the value as a UPC instead
        $found = false;
        if ($model->load()) {
            $found = true;
        } else {
            $model->reset();
            $model->orderID($this->id);
            $model->internalUPC(BarcodeLib::padUPC($this->sku));
            $matches = $model->find();
            if (count($matches) == 1) {
                $model = $matches[0];
                $found = true;
            }
        }
        
        // item not in order. need all fields to add it.
        echo '<form onsubmit="saveReceive(); return false;">';
        if (!$found) {
            $this->receiveUnOrderedItem($dbc);
        } else {
            // item in order. just need received qty and cost
            $this->receiveOrderedItem($dbc, $model);
        }
        echo '</form>';

        return false;
    }

    private function receiveUnOrderedItem($dbc)
    {
        echo '<div class="alert alert-danger">SKU not found in order</div>';
        echo '<table class="table table-bordered">';
        echo '<tr><th>SKU</th><th>UPC</th><th>Brand</th><th>Description</th>
            <th>Qty Ordered</th><th>Cost (est)</th><th>Qty Received</th><th>Cost Received</th></tr>';
        $order = new PurchaseOrderModel($dbc);
        $order->orderID($this->id);
        $order->load();
        $item = new VendorItemsModel($dbc);
        $item->vendorID($order->vendorID());
        $item->sku($this->sku);
        $item->load();
        printf('<tr>
            <td>%s<input type="hidden" name="sku" value="%s" /></td>
            <td><input type="text" class="form-control" name="upc" value="%s" /></td>
            <td><input type="text" class="form-control" name="brand" value="%s" /></td>
            <td><input type="text" class="form-control" name="description" value="%s" /></td>
            <td><input type="text" class="form-control" name="orderQty" value="%s" /></td>
            <td><input type="text" class="form-control" name="orderCost" value="%.2f" /></td>
            <td><input type="text" class="form-control" name="receiveQty" value="%s" /></td>
            <td><input type="text" class="form-control" name="receiveCost" value="%.2f" /></td>
            <td><button type="submit" class="btn btn-default">Add New Item</button><input type="hidden" name="id" value="%d" /></td>
            </tr>',
            $item->sku(), $item->sku(),
            $item->upc(),
            $item->brand(),
            $item->description(),
            1,
            $item->cost() * $item->units(),
            0,
            0,
            $this->id
        );
        echo '</table>';
    }

    private function receiveOrderedItem($dbc, $model)
    {
        echo '<table class="table table-bordered">';
        echo '<tr><th>SKU</th><th>UPC</th><th>Brand</th><th>Description</th>
            <th>Qty Ordered</th><th>Cost (est)</th><th>Qty Received</th><th>Cost Received</th></tr>';
        $uid = FannieAuth::getUID($this->current_user);
        if ($model->receivedQty() === null) {
            $model->receivedQty($model->quantity());
            $model->receivedBy($uid);
        }
        if ($model->receivedTotalCost() === null) {
            $model->receivedTotalCost($model->quantity()*$model->unitCost()*$model->caseSize());
            $model->receivedBy($uid);
        }
        printf('<tr>
            <td>%s<input type="hidden" name="sku" value="%s" /></td>
            <td>%s</td>
            <td>%s</td>
            <td>%s</td>
            <td>%s</td>
            <td>%.2f</td>
            <td><input type="text" class="form-control" name="qty" value="%s" /></td>
            <td><input type="text" class="form-control" name="cost" value="%.2f" /></td>
            <td><button type="submit" class="btn btn-default">Save</button><input type="hidden" name="id" value="%d" /></td>
            </tr>',
            $model->sku(), $model->sku(),
            $model->internalUPC(),
            $model->brand(),
            $model->description(),
            $model->quantity(),
            $model->quantity() * $model->unitCost() * $model->caseSize(),
            $model->receivedQty(),
            $model->receivedTotalCost(),
            $this->id
        );
        echo '</table>';
    }

    protected function get_view()
    {
        $init = FormLib::get('init', 'placed');

        $monthOpts = '<option>Last 30 days</option>';
        for($i=1; $i<= 12; $i++) {
            $label = date('F', mktime(0, 0, 0, $i)); 
            $monthOpts .= sprintf('<option value="%d">%s</option>',
                        $i, $label);
        }

        $stores = FormLib::storePicker();
        $storeSelect = str_replace('<select ', '<select id="storeID" onchange="fetchOrders();" ', $stores['html']);

        $yearOpts = '';
        for ($i = date('Y'); $i >= 2013; $i--) {
            $yearOpts .= '<option>' . $i . '</option>';
        }

        $allSelected = $this->show_all ? 'selected' : '';
        $mySelected = !$this->show_all ? 'selected' : '';
        $ordersTable = $this->get_orders($init == 'placed' ? 1 : 0, Store::getIdByIp(), 'Last 30 days');

        $this->addScript('../src/javascript/tablesorter/jquery.tablesorter.min.js');
        $this->addScript($this->config->get('URL') . 'src/javascript/jquery.floatThead.min.js');
        $this->addScript('js/view.js');
        $this->addOnloadCommand("\$('.tablesorter').tablesorter();\n");
        $this->addOnloadCommand("\$('.table-float').floatThead();\n");

        return <<<HTML
<div class="form-group form-inline">
    <input type="hidden" id="orderStatus" value="{$init}" />
    <label>Showing</label> 
    <select id="orderShow" onchange="fetchOrders();" class="form-control">
        <option {$mySelected} value="0">My Orders</option><option {$allSelected} value="1">All Orders</option>
    </select>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    {$storeSelect}
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <label>During</label> 
    <select id="viewMonth" onchange="fetchOrders();" class="form-control">
        {$monthOpts}
    </select>
    &nbsp;
    <select id="viewYear" onchange="fetchOrders();" class="form-control">
        {$yearOpts}
    </select>
    &nbsp;
    <button class="btn btn-default" onclick="location='PurchasingIndexPage.php'; return false;">Home</button>
</div>
<hr />
<div id="ordersDiv">{$ordersTable}</div>
HTML;
    }

    public function css_content()
    {
        return '
            .tablesorter thead th {
                cursor: hand;
                cursor: pointer;
            }';
    }

    public function helpContent()
    {
        if (isset($this->receive)) {
            return '<p>Receive an order. First enter a SKU (or UPC) to see
            the quantities that were ordered. Then enter the actual quantities
            received as well as costs. If a received item was <b>not</b> on the
            original order, you will be prompted to provide additional information
            so the item can be added to the order.</p>';
        } elseif (isset($this->id)) {
            return '<p>Details of a Purchase Order. Coding(s) are driven by POS department
            <em>Sales Codes</em>. Export outputs the order data in various formats.
            Edit Order loads the order line-items into an editing interface where adjustments
            to all fields can be made. Receive Order is used to resolve a purchase order
            with actual quantities received.
            </p>';
        } else {
            return '<p>Click the date link to view a particular purchase order. Use
                the dropdowns to filter the list. The distinction between <em>All Orders</em>
                and <em>My Orders</em> only works if user authentication is enabled.</p>';
        }
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
        $this->id = '4011';
        $phpunit->assertNotEquals(0, strlen($this->get_id_view()));
        $this->recode = 1;
        $phpunit->assertNotEquals(0, strlen($this->get_id_recode_view()));
        $this->receive = 1;
        $phpunit->assertNotEquals(0, strlen($this->get_id_receive_view()));
    }
}

FannieDispatch::conditionalExec();

