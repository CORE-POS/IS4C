<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of Fannie.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include(dirname(__FILE__).'/../../../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

/**
  @class SaHandheldPage
*/
class SaOrderingPage extends FanniePage 
{
    protected $window_dressing = False;
    private $section=0;
    private $current_item_data=array();
    private $linea_ios_mode = False;

    public $page_set = 'Plugin :: Shelf Audit';
    public $description = '[Ordering] lists information about recent item sales
    and purchases to assist in ordering.';

    private function linea_support_available(){
        global $FANNIE_ROOT;
        if (file_exists($FANNIE_ROOT.'src/javascript/linea/cordova-2.2.0.js')
        && file_exists($FANNIE_ROOT.'src/javascript/linea/ScannerLib-Linea-2.0.0.js'))
            return True;
        else
            return False;
    }

    public function preprocess()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB, $FANNIE_URL, $FANNIE_TRANS_DB;

        $this->add_script($FANNIE_URL.'src/javascript/jquery.js');
        $this->add_script($FANNIE_URL.'src/javascript/jquery-ui.js');

        if (FormLib::get('upc_in') !== '') {
            $upc = BarcodeLib::padUPC(FormLib::get('upc_in'));
            $this->current_item_data['upc'] = $upc;
            $dbc = FannieDB::get($FANNIE_OP_DB);
            $model = new ProductsModel($dbc);
            $model->upc($upc);
            $vendorID = 0;
            if ($model->load()) {
                $this->current_item_data['desc'] = $model->brand() . ' ' . $model->description();
                $this->current_item_data['par'] = $model->auto_par();
                $vendorID = $model->default_vendor_id();
            }

            $model = new VendorsModel($dbc);
            $model->vendorID($vendorID);
            if ($model->load()) {
                $this->current_item_data['vendor'] = $model->vendorName();
                $schedule = new VendorDeliveriesModel($dbc);
                $schedule->vendorID($vendorID);
                if ($schedule->load() && $schedule->regular() == 1) {
                    $this->current_item_data['nextDelivery'] = date('D, M jS', strtotime($schedule->nextDelivery()))
                        . ' & ' . date('D, M jS', strtotime($schedule->nextNextDelivery()));
                    $nd = new DateTime(date('Y-m-d', strtotime($schedule->nextDelivery())));
                    $nnd = new DateTime(date('Y-m-d', strtotime($schedule->nextNextDelivery())));
                    $this->current_item_data['deliverySpan'] = $nnd->diff($nd)->format('%a');
                }

                $items = new VendorItemsModel($dbc);
                $items->vendorID($vendorID);
                $items->upc($upc);
                $this->current_item_data['cases'] = array();
                foreach ($items->find('units') as $item) {
                    $this->current_item_data['cases'][] = $item->units();
                }
            }
            
            $saleNow = 'SELECT b.batchName, b.startDate, b.endDate
                        FROM batchList AS l
                            INNER JOIN batches AS b ON b.batchID=l.batchID
                        WHERE l.upc=?
                            AND b.discounttype <> 0
                            AND b.startDate <= ' . $dbc->now() . '
                            AND b.endDate >= ' . $dbc->now();
            $saleNow = $dbc->prepare($saleNow);
            $saleNow = $dbc->execute($saleNow, array($upc));
            if ($dbc->num_rows($saleNow) > 0) {
                $row = $dbc->fetch_row($saleNow);
                $this->current_item_data['onSale'] = $row['batchName'] . ' thru ' . date('D, M jS', strtotime($row['endDate']));
            }

            $saleNext = 'SELECT b.batchName, b.startDate, b.endDate
                        FROM batchList AS l
                            INNER JOIN batches AS b ON b.batchID=l.batchID
                        WHERE l.upc=?
                            AND b.discounttype <> 0
                            AND b.startDate >= ' . $dbc->now() . '
                            AND b.endDate >= ' . $dbc->now();
            $saleNext = $dbc->prepare($saleNext);
            $saleNext = $dbc->execute($saleNext, array($upc));
            if ($dbc->num_rows($saleNext) > 0) {
                $row = $dbc->fetch_row($saleNext);
                $this->current_item_data['soonSale'] = $row['batchName'] . ' on ' . date('D, M jS', strtotime($row['startDate']));
            }

            $ordersQ = 'SELECT v.vendorName,
                            o.placedDate,
                            i.quantity,
                            i.caseSize
                        FROM PurchaseOrderItems AS i
                            INNER JOIN PurchaseOrder AS o ON i.orderID=o.orderID
                            INNER JOIN vendors AS v ON o.vendorID=v.vendorID
                        WHERE i.internalUPC = ?
                        ORDER BY o.placedDate DESC';
            $ordersQ = $dbc->add_select_limit($ordersQ, 10);
            $ordersP = $dbc->prepare($ordersQ);
            $ordersR = $dbc->execute($ordersP, array($upc));
            $orders = array();
            while ($w = $dbc->fetch_row($ordersR)) {
                $orders[] = $w;
            }
            $this->current_item_data['orders'] = $orders;

            $salesQ = 'SELECT ' . DTrans::sumQuantity('d') . ' AS qty,
                        MIN(tdate) as day
                       FROM ' . $FANNIE_TRANS_DB . $dbc->sep() . 'dlog_15 AS d
                       WHERE upc = ?
                       GROUP BY YEAR(tdate), MONTH(tdate), DAY(tdate)
                       ORDER BY YEAR(tdate) DESC, MONTH(tdate) DESC, DAY(tdate) DESC';
            $salesP = $dbc->prepare($salesQ);
            $salesR = $dbc->execute($salesP, array($upc));
            $sales = array();
            while ($w = $dbc->fetch_row($salesR)) {
                $sales[] = $w;
            }
            $this->current_item_data['sales'] = $sales;
        }

        $this->linea_ios_mode = $this->linea_support_available();
        if ($this->linea_ios_mode) {
            $this->add_script($FANNIE_URL.'src/javascript/linea/cordova-2.2.0.js');
            $this->add_script($FANNIE_URL.'src/javascript/linea/ScannerLib-Linea-2.0.0.js');
        }
        $this->add_script($FANNIE_URL.'src/javascript/tablesorter/jquery.tablesorter.js');
        $this->add_css_file($FANNIE_URL.'src/javascript/tablesorter/themes/blue/style.css');
        $this->add_css_file($FANNIE_URL.'src/javascript/jquery-ui.css');
        
        return true;
    }

    function css_content(){
        ob_start();
        ?>
.saleInfo {
    color: green;
}
input.addButton {
    width: 60px;
    height: 50px;
    background-color: #090;
    color: #fff;
    font-weight: bold;
    font-size: 135%;
}
input.subButton {
    width: 60px;
    height: 50px;
    background-color: #900;
    color: #fff;
    font-weight: bold;
    font-size: 135%;
}
input#cur_qty {
    width: 60px;
    height: 50px;
    font-size: 135%;
    font-weight: bold;
}
input.focused {
    background: #ffeebb;
}
        <?php
        return ob_get_clean();
    }

    function javascript_content(){
        ob_start();
        ?>
function paint_focus(elem){
    if (elem == 'upc_in'){
        $('#upc_in').addClass('focused');
    }
    else {
        $('#upc_in').removeClass('focused');
    }
}
        <?php if ($this->linea_ios_mode){ ?>
Device = new ScannerDevice({
    barcodeData: function (data, type){
        var upc = data.substring(0,data.length-1);
        if ($('#upc_in').length > 0){
            $('#upc_in').val(upc);
            $('#goBtn').click();
        }
    },
    magneticCardData: function (track1, track2, track3){
    },
    magneticCardRawData: function (data){
    },
    buttonPressed: function (){
    },
    buttonReleased: function (){
    },
    connectionState: function (state){
    }
});
ScannerDevice.registerListener(Device);
        <?php } ?>

        <?php
        return ob_get_clean();
    }

    function body_content(){
        ob_start();
        $elem = '#upc_in';
        ?>
<html>
<head>
    <title>Order Info</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
<form action="SaOrderingPage.php" method="get" id="upcScanForm">
<div style="float: left;">
<a href="SaMenuPage.php">Menu</a><br />
<b>UPC</b>: <input type="number" size="10" name="upc_in" id="upc_in" 
onfocus="paint_focus('upc_in');"
<?php echo ($elem=='#upc_in')?'class="focused"':''; ?> 
/>
</div>
<div style="float: left;">
<input type="submit" value="Go" class="addButton" id="goBtn" />
</div>
<div style="clear:left;"></div>
</form>
<hr />
        <?php
        if (isset($this->current_item_data['upc'])){
            if (!isset($this->current_item_data['desc'])){
                echo '<span class="error">Item not found (';
                echo $this->current_item_data['desc'];
                echo ')</span>';
            } else {
                echo '<span class="itemInfo">';
                echo $this->current_item_data['upc'];
                echo ' ';
                echo $this->current_item_data['desc'];
                echo '</span>';
                echo '<br />';
                if (isset($this->current_item_data['vendor'])) {
                    echo '<span class="itemInfo">Vendor: ' . $this->current_item_data['vendor'] . '</span><br />';
                    echo '<span class="itemInfo">Next Deliveries: ' . (isset($this->current_item_data['nextDelivery'])
                        ? $this->current_item_data['nextDelivery'] : 'unknown' ) . '</span><br />';
                    if (isset($this->current_item_data['par'])) {
                        if (isset($this->current_item_data['nextDelivery'])) {
                            printf('<span class="itemInfo">Auto Par: %.2f</span><br />', $this->current_item_data['par']);
                            printf('<span class="itemInfo">Daily Avg: %.2f</span><br />', 
                                $this->current_item_data['par'] / $this->current_item_data['deliverySpan']);
                        } else {
                            printf('<span class="itemInfo">Daily Avg: %.2f</span><br />', $this->current_item_data['par']);
                        }
                    }
                    echo '<span class="itemInfo">Case Size(s): ';
                    foreach ($this->current_item_data['cases'] as $case) {
                        echo $case . ' ';
                    }
                    echo '</span><br />';
                }
                if (isset($this->current_item_data['onSale'])) {
                    echo '<span class="saleInfo">On Sale: ' . $this->current_item_data['onSale'] . '</span><br />';
                }
                if (isset($this->current_item_data['soonSale'])) {
                    echo '<span class="saleInfo">Upcoming Sale: ' . $this->current_item_data['soonSale'] . '</span><br />';
                }
                echo '<hr />';
                echo '<div id="tabs">';
                echo '<ul>
                    <li><a href="#ordersTable">Orders</a></li>
                    <li><a href="#salesTable">Sales</a></li>
                    </ul>';
                echo '<div id="ordersTable">';
                echo '<table class="tablesorter">
                    <thead>
                    <tr>
                        <th>Date Ordered</th><th>Vendor</th><th>Cases</th><th>Units</th>
                    </tr>
                    </thead>
                    <tbody>';
                foreach ($this->current_item_data['orders'] as $order) {
                    printf('<tr><td>%s</td><td>%s</td><td>%d</td><td>%d</td></tr>',
                            date('Y-m-d', strtotime($order['placedDate'])),
                            $order['vendorName'],
                            $order['quantity'],
                            $order['quantity'] * $order['caseSize']);
                }
                if (count($this->current_item_data['orders']) == 0) {
                    echo '<tr><td colspan="4">None</td></tr>';
                }
                echo '</tbody>
                    </table>';
                echo '</div>';

                echo '<div id="salesTable">';
                echo '<table class="tablesorter">
                    <thead>
                    <tr>
                        <th>Date Sold</th><th>Qty Sold</th>
                    </tr>
                    </thead>
                    <tbody>';
                foreach ($this->current_item_data['sales'] as $sale) {
                    printf('<tr><td>%s</td><td>%.2f</td></tr>',
                            date('Y-m-d', strtotime($sale['day'])),
                            $sale['qty']);
                }
                if (count($this->current_item_data['sales']) == 0) {
                    echo '<tr><td colspan="2">None</td></tr>';
                }
                echo '</tbody>
                    </table>';
                echo '</div>';
                echo '</div>';

                $this->add_onload_command("\$('.tablesorter').tablesorter({sortList: [[0, 1]], widgets: ['zebra']});");
                $this->add_onload_command('$(\'#tabs\').tabs();');
            }
        }
        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec(false);

