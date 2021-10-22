<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('RpOrderCategoriesModel')) {
    include(__DIR__ . '/models/RpOrderCategoriesModel.php');
}
if (!class_exists('RpOrderItemsModel')) {
    include(__DIR__ . '/models/RpOrderItemsModel.php');
}
if (!class_exists('RpSessionsModel')) {
    include(__DIR__ . '/models/RpSessionsModel.php');
}
if (!class_exists('RpFarmsModel')) {
    include(__DIR__ . '/models/RpFarmsModel.php');
}

class RpFloralPage extends FannieRESTfulPage
{
    protected $header = 'Daily Floral Guide';
    protected $title = 'Daily Floral Guide';
    protected $must_authenticate = true;

    public function preprocess()
    {
        $this->addRoute('get<searchVendor>', 'get<searchLC>', 'get<json>', 'get<date1><date2>', 'get<clear>', 'post<json>');
        $this->userID = FannieAuth::getUID($this->current_user);

        return parent::preprocess();
    }

    protected function get_date1_date2_handler()
    {
        $date1 = date('Y-m-d', strtotime(FormLib::get('date1')));
        $date2 = date('Y-m-d', strtotime(FormLib::get('date2')));
        $args = array(FormLib::get('store'), $date1, $date2);
        $ignore = 'CASE WHEN receivedDate < ' . $this->connection->curdate() . ' THEN 1 ELSE 0 END AS ignored';

        /**
         * Check for past dates. Since the purpose it to view future expected
         * deliveries, selected weekdays that are in the past should be pushed
         * forward into the subsequent week
         */
        $ts1 = strtotime($date1);
        $ts2 = strtotime($date2);
        $today = strtotime(date('Y-m-d'));
        if ($ts1 < $today) {
            $ts1 = mktime(0, 0, 0, date('n', $ts1), date('j', $ts1) + 7, date('Y', $ts1));
        }
        if ($ts2 < $today) {
            $ts2 = mktime(0, 0, 0, date('n', $ts2), date('j', $ts2) + 7, date('Y', $ts2));
        }
        if ($ts1 > $ts2) {
            $swap = $ts2;
            $ts2 = $ts1;
            $ts1 = $swap;
        }
        $date1 = date('Y-m-d', $ts1);
        $date2 = date('Y-m-d', $ts2);

        $date3 = false;
        $extra = FormLib::get('date3');
        $d3ts = strtotime($extra);
        if ($d3ts && $extra) {
            $date3 = date('Y-m-d', $d3ts);
            // safe embed because it's a formatted date string or false
            // for any user input
            $ignore = "CASE WHEN receivedDate > {$date2} OR receivedDate < " . $this->connection->curdate() . " 
                THEN 1 ELSE 0 END AS ignored";
            $args[2] = $date3;
        }

        $prep = $this->connection->prepare("SELECT receivedDate, internalUPC AS upc, brand, quantity AS qty, caseSize,
                {$ignore}
            FROM PurchaseOrderItems AS i
                INNER JOIN PurchaseOrder AS o ON i.orderID=o.orderID
            WHERE (o.vendorID=-2 OR o.vendorInvoiceID LIKE 'PREBOOK %')
                AND o.userID=-99
                AND o.placed=1
                AND o.storeID=?
                AND i.receivedDate IS NOT NULL
                AND (
                    i.receivedDate BETWEEN ? AND ?
                    OR i.receivedDate = " . $this->connection->curdate() . "
                )
            ORDER BY i.receivedDate
            ");
        $qtys = $this->connection->getAllRows($prep, $args);

        $ret = array();
        foreach ($qtys as $row) {
            if (!isset($ret[$row['upc']])) {
                $ret[$row['upc']] = array('upc' => $row['upc'], 'qty' => 0, 'text' => '');
            }
            if ($row['caseSize'] > 1) {
                $row['qty'] *= $row['caseSize'];
            }
            if ($row['ignored'] != 1) {
                $ret[$row['upc']]['qty'] += $row['qty'];
            }
            if ($ret[$row['upc']]['text'] != '') {
                $ret[$row['upc']]['text'] .= '<br />';
            }
            $ts = strtotime($row['receivedDate']);
            $text = date('n/j', $ts) . ' ' . $row['qty'] . ' ' . $row['brand'];
            $ret[$row['upc']]['text'] .= $text;
        }
        $dekey = array();
        foreach ($ret as $upc => $row) {
            $dekey[] = $row;
        }
        echo json_encode($dekey);

        return false;
    }

    protected function get_clear_handler()
    {
        $_SESSION['rpState'] = 'false';
        $model = new RpSessionsModel($this->connection);
        $model->dataType('FLORAL');
        $model->userID($this->userID);
        $model->delete();

        return 'RpFloralPage.php';
    }

    protected function post_json_handler()
    {
        return $this->get_json_handler();
    }

    protected function get_json_handler()
    {
        $_SESSION['rpState'] = json_decode($this->json, true);
        $model = new RpSessionsModel($this->connection);
        $model->userID($this->userID);
        $model->dataType('FLORAL');
        $model->data($this->json);
        $model->save();
        echo 'OK';

        return false;
    }
    
    protected function delete_id_handler()
    {
        // forcing sequential requests here
        $_SESSION['appendingOrder'] = true;

        list($upc, $store, $vendor) = explode(',', $this->id);
        $findP = $this->connection->prepare("SELECT orderID
            FROM PurchaseOrder WHERE placed=0 AND storeID=? AND vendorID=? AND userID=-99");
        $orderID = $this->connection->getValue($findP, array($store, $vendor));

        $delQ = "DELETE FROM PurchaseOrderItems
            WHERE orderID=? AND internalUPC=?";
        $args = array($orderID, $upc);
        $delP= $this->connection->prepare($delQ);
        $this->connection->execute($delP, $args);
        $this->logger->debug($upc . '-' . $store . '-' . $vendor);

        echo json_encode(array('unlink' => false));

        return false;
    }

    protected function post_id_handler()
    {
        // forcing sequential requests here
        $_SESSION['appendingOrder'] = true;

        list($upc, $store, $vendor) = explode(',', $this->id);
        $findP = $this->connection->prepare("SELECT orderID
            FROM PurchaseOrder WHERE placed=0 AND storeID=? AND vendorID=? AND userID=-99");
        $orderID = $this->connection->getValue($findP, array($store, $vendor));
        if (!$orderID) {
            $model = new PurchaseOrderModel($this->connection);
            $model->storeID($store);
            $model->vendorID($vendor);
            $model->creationDate(date('Y-m-d H:i:s'));
            $model->userID(-99);
            $orderID = $model->save();
        }

        $sub = str_replace('SUB', '', $upc);
        $itemP = $this->connection->prepare("SELECT subdept_name FROM subdepts WHERE subdept_no=?");
        $item = $this->connection->getValue($itemP, array($sub));

        $poi = new PurchaseOrderItemsModel($this->connection);
        $poi->orderID($orderID);
        $poi->sku($upc);
        $poi->quantity(FormLib::get('qty'));
        $poi->unitCost(0);
        $poi->caseSize(1);
        $poi->unitSize(1);
        $poi->brand('');
        $poi->description($item);
        $poi->internalUPC($upc);
        $poi->save();

        $vendP = $this->connection->prepare("SELECT vendorName FROM vendors WHERE vendorID=?");
        $vend = $this->connection->getValue($vendP, array($vendor));

        echo json_encode(array('orderID' => $orderID, 'name'=>$vend));

        $_SESSION['appendingOrder'] = false;

        return false;
    }

    protected function isMobile()
    {
        $userAgent = strtolower(filter_input(INPUT_SERVER, 'HTTP_USER_AGENT'));
        if (strstr($userAgent, 'android')) {
            return true;
        } elseif (strstr($userAgent, 'iphone os')) {
            return true;
        }

        return false;
    }

    protected function get_view()
    {
        $this->addScript('rpFloral.js?date=20200708');
        $store = FormLib::get('store');
        if (!$store) {
            $store = COREPOS\Fannie\API\lib\Store::getIdByIp();
        }
        $sSelect = FormLib::storePicker();
        $sSelect['html'] = str_replace('<select', '<select onchange="location=\'RpFloralPage.php?store=\' + this.value;"', $sSelect['html']);
        $jsState = isset($_SESSION['rpState']) ? json_encode($_SESSION['rpState']) : "false";
        if ($jsState === "false") {
            $sModel = new RpSessionsModel($this->connection);
            $sModel->dataType('FLORAL');
            $sModel->userID($this->userID);
            if ($sModel->load()) {
                $jsState = $sModel->data();
                $_SESSION['rpState'] = json_decode($sModel->data(), true);
            }
        }
        $this->addOnloadCommand("rpOrder.initState({$jsState});");

        $fieldType = $this->isMobile() ? 'type="number" min="0" max="99999" step="0.01"' : 'type="text"';

        $ordersP = $this->connection->prepare("
            SELECT o.orderID, v.vendorName
            FROM PurchaseOrder AS o
                LEFT JOIN vendors AS v ON o.vendorID=v.vendorID
            WHERE o.storeID=?
                AND o.placed=0
                AND o.userID=-99");
        $orderLinks = '';
        $orderIDs = array();
        $orders = $this->connection->getAllRows($ordersP, array($store));
        foreach ($orders as $o) {
            $orderIDs[] = $o['orderID'];
            $orderLinks .= sprintf('<li id="link%d"><a href="../../../purchasing/ViewPurchaseOrders.php?id=%d">%s</a></li>',
                $o['orderID'], $o['orderID'], $o['vendorName']);
        }
        $printLink = '';
        if (count($orderIDs) > 0) {
            $printLink = '<a href="RpPrintOrders.php?id=' . implode(',', $orderIDs) . '">Print these</a>';
        }
        list($ioStr, $ioArgs) = $this->connection->safeInClause($orderIDs);
        $inOrderP = $this->connection->prepare("SELECT vendorID, quantity FROM PurchaseOrder AS o
            INNER JOIN PurchaseOrderItems AS i ON o.orderID=i.orderID
            WHERE o.orderID IN ({$ioStr}) AND i.internalUPC=?");

        $farmOpts = "<option value=218>Duluth Flower Farm</option>
                <option value=\"-2\">Direct</option>";
        $opt1 = '<option value=""></option>' . $farmOpts;

        $prep = $this->connection->prepare("
            SELECT s.subdept_no, s.subdept_name,
                SUM(t.quantity) AS qty,
                MIN(tdate) AS firstSale,
                MAX(tdate) AS lastSale
            FROM " . FannieDB::fqn('dlog_15', 'trans') . " AS t
                INNER JOIN products AS p ON t.upc=p.upc AND t.store_id=p.store_id
                INNER JOIN subdepts AS s ON p.subdept=s.subdept_no
            WHERE p.department=500
                AND t.store_id=?
            GROUP BY s.subdept_no, s.subdept_name
            ORDER BY s.subdept_name
        ");
        $res = $this->connection->execute($prep, array($store));
        $tables = '';
        $tables .= '<h3>Floral</h3>';
        $tables .= '<table class="table table-bordered table-striped small">
            <tr><th>LC</th><th>Primary</th>
            <th>On Hand</th><th>Par</th><th colspan="2">Order</th></tr>';
        while ($row = $this->connection->fetchRow($res)) {
            $price = 0;
            $upc = 'SUB' . $row['subdept_no'];

            list($first,) = explode(' ', $row['firstSale'], 2);
            list($last,) = explode(' ', $row['lastSale'], 2);
            $par = $row['qty'];
            $start = new DateTime($first);
            $end = new DateTime($last);
            $diff = abs($start->diff($end)->format("%a"));
            if ($diff != 0) {
                $par = $par / $diff;
            }
            $orderAmt = '';
            $inOrder = $this->connection->getRow($inOrderP, array_merge($ioArgs, array($upc)));
            if ($inOrder) {
                $orderAmt = $inOrder['quantity'];
            }
            $highlight = '';
            $tooltip = '';
            $nextRow = sprintf('<tr>
                <td class="upc">%s %s</td>
                <td><select class="primaryFarm form-control input-sm" id="pf%s" onchange="rpOrder.updateFarm(this);">%s</option></td>
                <td style="display:none;" class="caseSize">1</td>
                <td style="display:none;" class="realSize">1</td>
                <td><input %s class="form-control input-sm onHand" value="" 
                    style="width: 5em;" id="onHand%s" data-incoming="0"
                    onchange="rpOrder.reCalcRow($(this).closest(\'tr\')); rpOrder.updateOnHand(this);"
                    onfocus="this.select();" onkeyup="rpOrder.onHandKey(event);" />
                    <span class="incoming-notice"></span></td>
                <input type="hidden" class="price" value="%.2f" />
                <input type="hidden" class="basePar" value="%.2f" />
                <td class="parCell">%.2f</td>
                <td class="form-inline %s">
                    <input %s style="width: 5em;"class="form-control input-sm orderAmt"
                        id="orderAmt%s" onkeyup="rpOrder.orderKey(event); rpOrder.updateOrder(this);"
                        onfocus="this.select();" value="%s" />
                    <label><input type="checkbox" onchange="rpOrder.placeOrder(this);" value="%s,%d," %s 
                        class="orderPri" tabindex="-1" /> Pri</label>
                </td>
                </tr>',
                $upc, $row['subdept_name'],
                $upc, $opt1,
                $fieldType,
                $upc,
                $price,
                $par,
                $par,
                ($inOrder ? 'info' : ''),
                $fieldType,
                $upc,
                $orderAmt,
                $upc, $store,
                ($inOrder ? 'checked' : '')
            );
            $tables .= $nextRow;
        }
        $tables .= '</table>';

        $mStamp = date('N') == 1 ? strtotime('today') : strtotime('last monday');
        $dateIDs = array();
        for ($i=0; $i<7; $i++) {
            $dateIDs[] = date('Ymd', mktime(0,0,0,date('n',$mStamp),date('j',$mStamp)+$i,date('Y',$mStamp)));
        }

        return <<<HTML
<div class="row">
<div class="col-sm-6">
<p class="form-inline">
    <label>Store</label>: {$sSelect['html']}
    &nbsp;&nbsp;&nbsp;&nbsp;
    <fieldset>
        <label><input type="checkbox" class="daycheck"
            onchange="rpOrder.updateDays();" /> Monday</label>
        <label><input type="checkbox" class="daycheck"
            onchange="rpOrder.updateDays();" /> Tuesday</label>
        <label><input type="checkbox" class="daycheck"
            onchange="rpOrder.updateDays();" /> Wednesday</label>
        <label><input type="checkbox" class="daycheck"
            onchange="rpOrder.updateDays();" /> Thursday</label>
        <label><input type="checkbox" class="daycheck"
            onchange="rpOrder.updateDays();" /> Friday</label>
        <label><input type="checkbox" class="daycheck"
            onchange="rpOrder.updateDays();" /> Saturday</label>
        <label><input type="checkbox" class="daycheck"
            onchange="rpOrder.updateDays();" /> Sunday</label>
    </fieldset>
    <div class="form-inline">
        <div class="input-group">
            <span class="input-group-addon">Set Vendor</span>
            <select onchange="rpOrder.defaultFarm(this.value);" class="form-control">
                <option></option>
                {$farmOpts}
            </select>
        </div>
    </div>
    <p>
        <ul id="openOrders">{$orderLinks}</ul>
        <span id="printLink">{$printLink}</span>
    </p>
</p>
</div>
</div>
<p>
    <div class="form-group">
        <label>
            <input type="checkbox" checked id="autoOrderCheck" />
            Auto-fill order amounts
        </label>
    </div>
    <button class="btn btn-default orderAll" onclick="rpOrder.orderAll();">Order All</button>
    <div class="progress collapse">
        <div class="progress-bar progress-bar-striped active"  role="progressbar" 
            aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
            <span class="sr-only">Searching</span>
        </div>
    </div>
</p>
<p>
{$tables}
</p>
<p>
    <button class="btn btn-default orderAll" onclick="rpOrder.orderAll();">Order All</button>
    <div class="progress collapse">
        <div class="progress-bar progress-bar-striped active"  role="progressbar" 
            aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
            <span class="sr-only">Searching</span>
        </div>
    </div>
    <br />
</p>
<p>
    <ul id="altOpenOrders">{$orderLinks}</ul>
    <span id="altPrintLink">{$printLink}</span>
</p>
<hr />
<p>
    <a href="RpFloralPage.php?clear=1" class="btn btn-default">Clear Session Data</a>
</p>
HTML;
    }

    protected function css_content()
    {
        return <<<CSS
.rp-success {
    background-color: #f772d2;
}
.table-striped>tbody>tr:nth-child(odd)>td.rp-success {
    background-color: #f772d2;
}
.incoming-notice {
    font-weight: bold;
    color: #15AF23;
}
CSS;
    }


}

FannieDispatch::conditionalExec();

