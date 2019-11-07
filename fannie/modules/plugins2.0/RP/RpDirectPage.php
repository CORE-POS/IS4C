<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('RpOrderCategoriesModel')) {
    include(__DIR__ . '/RpOrderCategoriesModel.php');
}
if (!class_exists('RpOrderItemsModel')) {
    include(__DIR__ . '/RpOrderItemsModel.php');
}
if (!class_exists('RpSessionsModel')) {
    include(__DIR__ . '/RpSessionsModel.php');
}

class RpDirectPage extends FannieRESTfulPage
{
    protected $header = 'RP Direct Guide';
    protected $title = 'RP Direct Guide';
    protected $must_authenticate = true;

    public function preprocess()
    {
        $this->addRoute('get<searchVendor>', 'get<searchLC>', 'get<json>', 'get<date1><date2>');
        $this->userID = FannieAuth::getUID($this->current_user);

        return parent::preprocess();
    }

    protected function get_date1_date2_handler()
    {
        $prep = $this->connection->prepare("SELECT internalUPC, SUM(quantity) AS qty
            FROM PurchaseOrderItems AS i
                INNER JOIN PurchaseOrder AS o ON i.orderID=o.orderID
            WHERE o.vendorID=-2
                AND o.userID=-99
                AND o.placed=1
                AND o.storeID=?
                AND i.receivedDate IS NOT NULL
                AND i.receivedDate BETWEEN ? AND ?
            GROUP BY internalUPC");
        $date1 = date('Y-m-d', strtotime(FormLib::get('date1')));
        $date2 = date('Y-m-d', strtotime(FormLib::get('date2')));
        $qtys = $this->connection->getAllRows($prep, array(FormLib::get('store'), $date1, $date2));

        $ret = array();
        foreach ($qtys as $row) {
            $ret[] = array('upc' => $row['internalUPC'], 'qty' => $row['qty']);
        }
        echo json_encode($ret);

        return false;
    }

    protected function get_clear_handler()
    {
        unset($_SESSION['rpState']);
        $model = new RpSessionsModel($this->connection);
        $model->userID($this->userID);
        $model->delete();

        return 'RpDirectPage.php';
    }

    protected function get_json_handler()
    {
        $_SESSION['rpState'] = json_decode($this->json, true);
        $model = new RpSessionsModel($this->connection);
        $model->userID($this->userID);
        $model->data($this->json);
        $model->save();
        var_dump($_SESSION['rpState']);
        echo 'OK';

        return false;
    }
    
    protected function get_searchLC_handler()
    {
        $prep = $this->connection->prepare("SELECT * FROM likeCodes WHERE likeCodeDesc LIKE ?");
        $res = $this->connection->getAllRows($prep, array('%' . $this->searchLC . '%'));
        $ret = array();
        foreach ($res as $row) {
            $ret[] = array(
                'label' => $row['likeCodeDesc'],
                'value' => $row['likeCode'],
            );
        }

        echo json_encode($ret);

        return false;
    }

    protected function get_searchVendor_handler()
    {
        $query = "SELECT * FROM vendorItems WHERE (description LIKE ? OR brand LIKE ?)";
        $args = array('%' . $this->searchVendor . '%', '%' . $this->searchVendor . '%');
        if (FormLib::get('vendorID')) {
            $args[] = FormLib::get('vendorID');
            $query .= ' AND vendorID=?';
        } else {
            $query .= ' AND vendorID IN (292, 293, 136)';
        }

        $ret = array();
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, $args);
        while ($row = $this->connection->fetchRow($res)) {
            $label = $row['description'] . ' ' . $row['units'] . '/' . $row['size'] . ' ($' . $row['cost'] . ')';
            $value = array(
                'vendorID' => $row['vendorID'],
                'caseSize' => $row['units'],
                'sku' => $row['sku'],
                'upc' => $row['upc'],
                'item' => $row['description'],
            );
            $value = json_encode($value);
            $ret[] = array(
                'label' => $label,
                'value' => $value,
            );
        }

        echo json_encode($ret);

        return false;
    }

    protected function delete_id_handler()
    {
        // forcing sequential requests here
        $_SESSION['appendingOrder'] = true;

        list($upc, $store, $vendor) = explode(',', $this->id);
        $vendor = -2;
        $findP = $this->connection->prepare("SELECT orderID
            FROM PurchaseOrder WHERE placed=0 AND storeID=? AND vendorID=? AND userID=-99");
        $orderID = $this->connection->getValue($findP, array($store, $vendor));

        $delP = $this->connection->prepare("DELETE FROM PurchaseOrderItems
            WHERE orderID=? AND internalUPC=?");
        $this->connection->execute($delP, array($orderID, $upc));

        echo json_encode(array('unlink' => false));

        return false;
    }

    protected function post_id_handler()
    {
        // forcing sequential requests here
        $_SESSION['appendingOrder'] = true;

        list($upc, $store, $vendor) = explode(',', $this->id);
        $vendor = -2;
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
    
        $itemP = $this->connection->prepare("SELECT * FROM RpOrderItems WHERE upc=? AND storeID=?");
        $item = $this->connection->getRow($itemP, array($upc, $store));
        if (substr($upc, 0, 2) == "LC") {
            $prodP = $this->connection->prepare("SELECT p.brand, p.size, p.cost FROM upcLike AS u
                    INNER JOIN products AS p ON u.upc=p.upc WHERE u.likeCode=?");
            $prod = $this->connection->getRow($prodP, array(substr($upc, 2)));
        } else {
            $prodP = $this->connection->prepare("SELECT brand, size, cost FROM products WHERE upc=?");
            $prod = $this->connection->getRow($prodP, array($upc));
        }
        $mapP = $this->connection->prepare("SELECT sku FROM RpFixedMaps WHERE likeCode=?");
        $mapped = $this->connection->getValue($mapP, array(str_replace('LC', '', $upc)));
        if ($mapped) {
            $item['vendorSKU'] = $mapped;
        }
        if (!$item['vendorSKU']) {
            $item['vendorSKU'] = $upc;
        }
        if (!$item['backupSKU']) {
            $item['backupSKU'] = $upc;
        }

        $sku = $vendor == $item['backupID'] ? $item['backupSKU'] : $item['vendorSKU'];
        if ($sku == 'DIRECT') {
            $sku = $upc;
        }

        $poi = new PurchaseOrderItemsModel($this->connection);
        $poi->orderID($orderID);
        $poi->sku($sku);
        $poi->quantity(FormLib::get('qty'));
        $poi->unitCost($prod['cost']);
        $poi->caseSize(1);
        $poi->unitSize($prod['size']);
        $poi->brand(FormLib::get('farm'));
        $poi->description($vendor == $item['backupID'] ? $item['backupItem'] : $item['vendorItem']);
        $poi->internalUPC($upc);
        $poi->save();

        $vendP = $this->connection->prepare("SELECT vendorName FROM vendors WHERE vendorID=?");
        $vend = $this->connection->getValue($vendP, array($vendor));

        echo json_encode(array('orderID' => $orderID, 'name'=>$vend));

        $_SESSION['appendingOrder'] = false;

        return false;
    }

    protected function post_view()
    {
        $model = new RpOrderItemsModel($this->connection);
        $model->storeID(FormLib::get('store'));
        $model->categoryID(FormLib::get('catID'));
        $model->addedBy(1);
        $model->caseSize(FormLib::get('caseSize'));
        $model->vendorID(FormLib::get('vendor'));
        $model->vendorSKU(FormLib::get('sku'));
        $model->vendorItem(FormLib::get('item'));

        $lc = FormLib::get('lc');
        $upc = BarcodeLib::padUPC(FormLib::get('upc'));
        if ($lc) {
            $model->upc('LC' . $lc);
        } elseif ($upc != '0000000000000') {
            $model->upc($upc);
        } elseif (FormLib::get('sku')) {
            $model->upc(FormLib::get('sku'));
        } else {
            $model->upc(uniqid());
        }

        $saved = $model->save();
        $ret = '';
        if ($saved) {
            $ret .= '<div class="alert alert-success">Added item</div>';
        } else {
            $ret .= '<div class="alert alert-danger">Error adding item</div>';
        }

        return $ret . $this->get_view();
    }

    protected function get_view()
    {
        $this->addScript('rpDirect.js?date=20191107');
        $this->addOnloadCommand('rpOrder.initAutoCompletes();');
        $store = FormLib::get('store');
        if (!$store) {
            $store = COREPOS\Fannie\API\lib\Store::getIdByIp();
        }
        $sSelect = FormLib::storePicker();
        $sSelect['html'] = str_replace('<select', '<select onchange="location=\'RpOrderPage.php?store=\' + this.value;"', $sSelect['html']);
        $jsState = isset($_SESSION['rpState']) ? json_encode($_SESSION['rpState']) : "false";
        if ($jsState === "false") {
            $sModel = new RpSessionsModel($this->connection);
            $sModel->userID($this->userID);
            if ($sModel->load()) {
                $jsState = $sModel->data();
                $_SESSION['rpState'] = json_decode($sModel->data(), true);
            }
        }
        $this->addOnloadCommand("rpOrder.initState({$jsState});");

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

        $parP = $this->connection->prepare("
            SELECT movement
            FROM " . FannieDB::fqn('Smoothed', 'plugin:WarehouseDatabase') . "
            WHERE storeID=?
                AND upc=?"); 

        $priceP = $this->connection->prepare("
            SELECT CASE WHEN p.special_price > 0 THEN p.special_price ELSE p.normal_price END AS price
            FROM upcLike AS u
                INNER JOIN products AS p ON p.upc=u.upc
            WHERE u.likeCode=?");
        $costP = $this->connection->prepare("SELECT cost, units FROM vendorItems WHERE vendorID=? and sku=?");
        $mapP = $this->connection->prepare("SELECT * FROM RpFixedMaps WHERE likeCode=?");
        $lcnameP = $this->connection->prepare("SELECT likeCodeDesc FROM likeCodes WHERE likeCode=?");

        $saleP = $this->connection->prepare("SELECT endDate
            FROM batchList AS l
                INNER JOIN batches AS b ON l.batchID=b.batchID
                INNER JOIN StoreBatchMap AS m ON l.batchID=m.batchID
            WHERE l.upc=? AND m.storeID=?
                AND " . $this->connection->curdate() . " BETWEEN startDate AND endDate
                AND b.discountType > 0");

        $ago = date('Y-m-d', strtotime('-3 days'));
        $ahead = date('Y-m-d', strtotime('+3 days'));
        $startingP = $this->connection->prepare("SELECT startDate
            FROM batchList AS l
                INNER JOIN batches AS b ON l.batchID=b.batchID
                INNER JOIN StoreBatchMap AS m ON l.batchID=m.batchID
            WHERE l.upc=? AND m.storeID=?
                AND b.startDate BETWEEN '{$ago}' AND '{$ahead}'
                AND b.discountType > 0");
        $endingP = $this->connection->prepare("SELECT endDate
            FROM batchList AS l
                INNER JOIN batches AS b ON l.batchID=b.batchID
                INNER JOIN StoreBatchMap AS m ON l.batchID=m.batchID
            WHERE l.upc=? AND m.storeID=?
                AND b.endDate BETWEEN '{$ago}' AND '{$ahead}'
                AND b.discountType > 0");
        $primaryP = $this->connection->prepare("SELECT rpFarmID FROM RpFarmSchedules
                WHERE ? BETWEEN startDate AND endDate
                    AND likeCode=?
                    AND isPrimary=1");
        $secondaryP = $this->connection->prepare("SELECT rpFarmID FROM RpFarmSchedules
                WHERE ? BETWEEN startDate AND endDate
                    AND likeCode=?
                    AND isPrimary=0");

        $directLCP = $this->connection->prepare("SELECT likeCode FROM RpLocalLCs");
        $directLCs = $this->connection->getAllValues($directLCP);
        $farmP = $this->connection->prepare("SELECT * FROM RpFarms");
        $farms = $this->connection->getAllRows($farmP);

        $prep = $this->connection->prepare("
            SELECT r.upc,
                r.categoryID,
                c.name,
                v.vendorName AS vendorName,
                b.vendorName AS backupVendor,
                r.vendorSKU,
                r.vendorItem,
                r.backupSKU,
                r.backupItem,
                r.caseSize,
                r.vendorID,
                r.backupID
            FROM RpOrderItems AS r
                LEFT JOIN RpOrderCategories AS c ON r.categoryID=c.rpOrderCategoryID
                LEFT JOIN vendors AS v ON r.vendorID=v.vendorID
                LEFT JOIN vendors AS b ON r.backupID=b.vendorID
            WHERE r.storeID=?
            ORDER BY c.seq, c.name, r.vendorItem");
        $res = $this->connection->execute($prep, array($store));
        $tables = '';
        $category = false;
        $hiddenItems = '';
        while ($row = $this->connection->fetchRow($res)) {
            $likeCode = substr($row['upc'], 2);
            $appendTables = true;
            if (!in_array($likeCode, $directLCs) && $row['vendorID'] != -2 && $row['backupID'] != -2) {
                $appendTables = false;
            }
            if ($row['categoryID'] !== $category && $appendTables) {
                if ($category !== false) {
                    $tables .= '</table>';
                }
                $catName = $row['categoryID'] ? $row['name'] : 'Uncategorized';
                $tables .= '<h3>' . $catName . '</h3>';
                $category = $row['categoryID'];
                $tables .= '<table class="table table-bordered table-striped small">
                    <tr><th>LC</th><th>Primary</th><th>Secondary</th><th>Item</th>
                    <th>On Hand</th><th>Par</th><th>Order</th></tr>';
            }
            $mapped = $this->connection->getRow($mapP, array(str_replace('LC', '', $row['upc'])));
            if ($mapped) {
                $row['vendorSKU'] = $mapped['sku'];
                $row['lookupID'] = $mapped['vendorID'];
            }
            $lcName = $this->connection->getValue($lcnameP, array(str_replace('LC', '', $row['upc'])));
            $par = $this->connection->getValue($parP, array($store, $row['upc']));
            if (($par / $row['caseSize']) < 0.1) {
                $par = 0.1 * $row['caseSize'];
            }
            // reset case size *after* setting min par at 0.1 standard cases
            $row['caseSize'] = 1;
            $price = $this->connection->getValue($priceP, array(substr($row['upc'], 2)));
            $cost = $this->connection->getRow($costP,
                array(isset($row['lookupID']) ? $row['lookupID'] : $row['vendorID'], $row['vendorSKU']));
            if ($cost['units'] > 1 && $cost['units'] != $row['caseSize']) {
                $cost['cost'] /= $cost['units'];
            }
            $onSale = $this->connection->getValue($saleP, array($row['upc'], $store));
            $startIcon = '';
            $starting = $this->connection->getValue($startingP, array($row['upc'], $store));
            if ($starting) {
                $startIcon = sprintf('<span class="glyphicon glyphicon-arrow-up" title="%s" />',
                    'Sale ' . ($onSale ? 'started' : 'starting') . ' on ' . $starting);
            }
            $endIcon = '';
            $ending = $this->connection->getValue($endingP, array($row['upc'], $store));
            if ($ending) {
                $endIcon = sprintf('<span class="glyphicon glyphicon-arrow-down" title="%s" />',
                    'Sale ' . ($onSale ? 'ending' : 'ended') . ' on ' . $ending);
            }
            $upc = $row['upc'];
            if (substr($row['upc'], 0, 2) == 'LC') {
                $row['upc'] = sprintf('<a href="../../../item/likecodes/LikeCodeEditor.php?start=%d">%s</a>',
                    substr($row['upc'], 2), $row['upc']);
            }
            $orderAmt = 0;
            /** no auto-fill on direct
            $start = $par;
            while ($start > (0.25 * $row['caseSize'])) {
                $orderAmt++;
                $start -= $row['caseSize'];
                if ($orderAmt > 500) {
                    echo $row['upc'] . ": $par, {$row['caseSize']}<br />";
                    break;
                }
            }
             */
            $inOrder = $this->connection->getRow($inOrderP, array_merge($ioArgs, array($upc)));
            if ($inOrder) {
                $orderAmt = $inOrder['quantity'];
            }
            $farm1 = $this->connection->getValue($primaryP, array(date('Y-m-d'), $likeCode));
            $opt1 = '<option></option>';
            foreach ($farms as $f) {
                $opt1 .= sprintf('<option %s value="%d">%s</option>',
                    ($farm1 && $farm1 == $f['rpFarmID'] ? 'selected' : ''),
                    $f['rpFarmID'], $f['name']);
            }
            $farm2 = $this->connection->getValue($secondaryP, array(date('Y-m-d'), $likeCode));
            $opt2 = '<option></option>';
            foreach ($farms as $f) {
                $opt2 .= sprintf('<option %s value="%d">%s</option>',
                    ($farm2 && $farm2 == $f['rpFarmID'] ? 'selected' : ''),
                    $f['rpFarmID'], $f['name']);
            }
            $row['vendorName'] = str_replace(' (Produce)', '', $row['vendorName']);
            $row['backupVendor'] = str_replace(' (Produce)', '', $row['backupVendor']);
            $nextRow = sprintf('<tr class="%s">
                <td class="upc %s">%s %s</td>
                <td class="%s"><select class="primaryFarm form-control input-sm">%s</option></td>
                <td class="%s"><select class="secondaryFarm form-control input-sm">%s</option></td>
                <td class="%s" title="%s">$%.2f %s %s %s%s</td>
                <td style="display:none;" class="caseSize">%s</td>
                <td><input type="text" class="form-control input-sm onHand" value="0" 
                    style="width: 5em;" id="onHand%s" data-incoming="0"
                    onchange="rpOrder.reCalcRow($(this).closest(\'tr\')); rpOrder.updateOnHand(this);"
                    onfocus="this.select();" onkeyup="rpOrder.onHandKey(event);" /></td>
                <input type="hidden" class="price" value="%.2f" />
                <input type="hidden" class="basePar" value="%.2f" />
                <td class="parCell">%.2f</td>
                <td class="form-inline %s">
                    <input type="text" style="width: 5em;"class="form-control input-sm orderAmt"
                        id="orderAmt%s" onkeyup="rpOrder.orderKey(event); rpOrder.updateOrder(this);"
                        onfocus="this.select();" value="%d" />
                    <button class="btn btn-success btn-sm" onclick="rpOrder.inc(this, 1);">+</button>
                    <button class="btn btn-danger btn-sm" onclick="rpOrder.inc(this, -1);">-</button>
                    <label><input type="checkbox" class="orderPri" onchange="rpOrder.placeOrder(this);" value="%s,%d,%d" %s /> Pri</label>
                    <label><input type="checkbox" onchange="rpOrder.placeOrder(this);" value="%s,%d,%d" %s /> Sec</label>
                </td>
                </tr>',
                (in_array($likeCode, $directLCs) && $row['vendorID'] != -2 && $row['backupID'] != -2) ? 'extraLocal' : '',
                ($onSale ? "success" : ''), $row['upc'], $lcName,
                ($onSale ? 'success' : ''),
                $opt1,
                ($onSale ? 'success' : ''),
                $opt2,
                ($onSale ? 'success' : ''),
                ($onSale ? "On sale through {$onSale}" : ''),
                $cost['cost'] * $row['caseSize'],
                ($row['vendorSKU'] ? '(' . $row['vendorSKU'] . ')' : ''),
                $row['vendorItem'],
                $startIcon, $endIcon,
                $row['caseSize'],
                $upc,
                $price,
                $par,
                $par / $row['caseSize'],
                ($inOrder ? 'info' : ''),
                $upc,
                $orderAmt,
                $upc, $store, $row['vendorID'],
                ($inOrder['vendorID'] == $row['vendorID'] ? 'checked' : ''),
                $upc, $store, $row['backupID'],
                ($inOrder['vendorID'] == $row['backupID'] ? 'checked' : '')
            );
            if ($appendTables) {
                $tables .= $nextRow;
            } else {
                $hiddenItems .= $nextRow;
            }
        }
        $tables .= '</table>';

        $tables .= '<table style="display:none;">'
            . $hiddenItems
            . '</table>';

        $ts = time();
        while (date('N', $ts) != 1) {
            $ts = mktime(0, 0, 0, date('n', $ts), date('j',$ts) - 1, date('Y', $ts));
        }
        $weekStart = date('Y-m-d', $ts);
        $weekP = $this->connection->prepare("SELECT * FROM RpSegments WHERE startDate=? AND storeID=?");
        $projected = 'n/a';
        $baseRetain = 60;
        $days = array(
            'Mon' => 'n/a',
            'Tue' => 'n/a',
            'Wed' => 'n/a',
            'Thu' => 'n/a',
            'Fri' => 'n/a',
            'Sat' => 'n/a',
            'Sun' => 'n/a',
        );
        $week = $this->connection->getRow($weekP, array($weekStart, $store));
        $modProj = 0;
        if ($week) {
            $projected = number_format($week['sales']);
            $baseRetain = $week['retention'];
            $days = json_decode($week['segmentation'], true);
            $days = array_map(function ($i) { return sprintf('%.2f%%', $i*100); }, $days);
            $thisYear = json_decode($week['thisYear'], true);
            $lastYear = json_decode($week['lastYear'], true);
            $sums = array('this' => 0, 'last' => 0);
            $dataPoints = 0;
            foreach ($thisYear as $key => $val) {
                if ($val > 0 && $lastYear[$key] > 0) {
                    $sums['this'] += $val;
                    $sums['last'] += $lastYear[$key];
                    $dataPoints++;
                }
            }
            $growth = ($sums['this'] - $sums['last']) / $sums['last'];
            $growth *= ($dataPoints / 7);
            foreach ($lastYear as $key => $val) {
                $modProj += ($val * (1 + $growth));
            }
            $modProj = round($modProj, 2);
        }

        $mStamp = date('N') == 1 ? strtotime('today') : strtotime('last monday');
        $dateIDs = array();
        for ($i=0; $i<7; $i++) {
            $dateIDs[] = date('Ymd', mktime(0,0,0,date('n',$mStamp),date('j',$mStamp)+$i,date('Y',$mStamp)));
        }

        $cats = new RpOrderCategoriesModel($this->connection);
        $catOpts = $cats->toOptions();

        return <<<HTML
<div class="row">
<div class="col-sm-6">
<p class="form-inline">
    <label>Store</label>: {$sSelect['html']}
    &nbsp;&nbsp;&nbsp;&nbsp;
    <label>Projected Sales this Week</label>:
    <a href="RpSegmentation.php" id="projSales">{$projected}</a>
    |
    <a href="RpFileManager.php">RP Data</a>
    <fieldset>
        <label title="{$days['Mon']}"><input type="checkbox" class="daycheck"
            data-dateid="{$dateIDs[0]}"
            onchange="rpOrder.updateDays();" value="{$days['Mon']}" /> Monday</label>
        <label title="{$days['Tue']}"><input type="checkbox" class="daycheck"
            data-dateid="{$dateIDs[1]}"
            onchange="rpOrder.updateDays();" value="{$days['Tue']}" /> Tuesday</label>
        <label title="{$days['Wed']}"><input type="checkbox" class="daycheck"
            data-dateid="{$dateIDs[2]}"
            onchange="rpOrder.updateDays();" value="{$days['Wed']}" /> Wednesday</label>
        <label title="{$days['Thu']}"><input type="checkbox" class="daycheck"
            data-dateid="{$dateIDs[3]}"
            onchange="rpOrder.updateDays();" value="{$days['Thu']}" /> Thursday</label>
        <label title="{$days['Fri']}"><input type="checkbox" class="daycheck"
            data-dateid="{$dateIDs[4]}"
            onchange="rpOrder.updateDays();" value="{$days['Fri']}" /> Friday</label>
        <label title="{$days['Sat']}"><input type="checkbox" class="daycheck"
            data-dateid="{$dateIDs[5]}"
            onchange="rpOrder.updateDays();" value="{$days['Sat']}" /> Saturday</label>
        <label title="{$days['Sun']}"><input type="checkbox" class="daycheck"
            data-dateid="{$dateIDs[6]}"
            onchange="rpOrder.updateDays();" value="{$days['Sun']}" /> Sunday</label>
    </fieldset>
    <label title="Based on sales growth so far this week">Modified Projection</label>:
    <span id="modProj">{$modProj}</span>
    <br />
    <label>Projected Sales these Days</label>:
    <span id="selectedSales">0</span>
    <br />
    <label>Retail x Expected Movement</label>:
    <span id="guessRetail">0</span>
    <br />
    <label>Adjustment</label>:
    <span id="adjDiff">0</span>
    <div class="form-inline">
        <div class="input-group">
            <span class="input-group-addon">Retention</span>
            <input type="number" value="{$baseRetain}" id="retention" class="form-control input-sm" />
            <span class="input-group-addon">%</span>
        </div> 
    </div> 
    <div class="form-group">
        <label title="Show only items currently set to DIRECT">
            <input type="checkbox" onchange="if (this.checked) $('tr.extraLocal').hide(); else $('tr.extraLocal').show();" />
            Limited List
        </label>
    </div>
    <p>
        <ul id="openOrders">{$orderLinks}</ul>
        <span id="printLink">{$printLink}</span>
    </p>
</p>
</div>
<div class="col-sm-6">
<div class="panel panel-default">
<div class="panel-heading">Add Item</div>
<div class="panel-body">
<form method="post">
    <input type="hidden" name="store" value="{$store}" />
    <div class="form-group input-group">
        <span class="input-group-addon">Vendor</span>
        <select name="vendor" class="form-control input-sm" required
            id="newVendor" onchange="rpOrder.setSearchVendor(this.value);">
            <option value=""></option>
            <option value="292">Alberts</option>
            <option value="293">CPW</option>
            <option value="136">RDW</option>
            <option value="1">UNFI</option>
            <option value="-2">Direct</option>
        </select>
    </div>
    <div class="form-group input-group">
        <span class="input-group-addon">Item</span>
        <input type="text" class="form-control input-sm" name="item" required id="newItem" />
    </div>
    <div class="col-sm-6">
        <div class="form-group input-group">
            <span class="input-group-addon">Likecode</span>
            <input type="text" class="form-control input-sm" name="lc" id="newLC" />
        </div>
    </div>
    <div class="col-sm-6">
        <div class="form-group input-group">
            <span class="input-group-addon">or UPC</span>
            <input type="text" class="form-control input-sm" name="upc" id="newUPC" />
        </div>
    </div>
    <div class="col-sm-6">
        <div class="form-group input-group">
            <span class="input-group-addon">SKU</span>
            <input type="text" class="form-control input-sm" name="sku" id="newSKU" />
        </div>
    </div>
    <div class="col-sm-6">
        <div class="form-group input-group">
            <span class="input-group-addon">Case Size</span>
            <input type="text" class="form-control input-sm" name="caseSize" required id="newCase" />
        </div>
    </div>
    <div class="form-group input-group">
        <span class="input-group-addon">Category</span>
        <select name="catID" class="form-control input-sm" required>{$catOpts}</select>
    </div>
    <button type="submit" class="btn btn-default">Add Item</button>
    <button type="reset" class="btn btn-default btn-reset">Clear</button>
</form>
</div>
</div>
</div>
</div>
<p>
    <button class="btn btn-default orderAll" onclick="rpOrder.orderAll();">Order All</button>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <a href="RpOrderPage.php" class="btn btn-default">Switch to Regular</a>
    <div class="progress collapse">
        <div class="progress-bar progress-bar-striped active"  role="progressbar" 
            aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
            <span class="sr-only">Searching</span>
        </div>
    </div>
</p>
{$tables}
<p>
    <button class="btn btn-default orderAll" onclick="rpOrder.orderAll();">Order All</button>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <a href="RpOrderPage.php" class="btn btn-default">Switch to Regular</a>
    <div class="progress collapse">
        <div class="progress-bar progress-bar-striped active"  role="progressbar" 
            aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
            <span class="sr-only">Searching</span>
        </div>
    </div>
    <br />
    <a href="RpDirectPage.php?clear=1" class="btn btn-default">Clear Session Data</a>
</p>
HTML;
    }
}

FannieDispatch::conditionalExec();

