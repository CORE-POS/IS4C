<?php

class MercatoConvert
{
    private $dbc = null;

    private $COL_STORE_ID = 3;
    private $COL_UTC_DATE = 1;
    private $COL_ROWTYPE = 5;
    private $COL_ORDER_ID = 6;
    private $COL_AMT = 14;
    private $COL_UPC = 9;
    private $COL_QTY = 12;
    private $COL_ITEM = 7;

    public function __construct($dbc)
    {
        $this->dbc = $dbc;
    }

    public function shift()
    {
        $this->COL_ORDER_ID = 7;
        $this->COL_AMT = 17;
        $this->COL_UPC = 10;
        $this->COL_QTY = 13;
        $this->COL_ITEM = 8;
    }

    public function process($filename)
    {
        $fp = fopen($filename, 'r');
        $currentOrder = array('id' => false, 'total' => 0, 'qty' => 0, 'fees' => 0, 'card_no' => 11, 'tdate' => '', 'items' => array());
        $storeID = 0;
        $mOrderID = 0;
        $local = false;
        $itemP = $this->dbc->prepare("SELECT description, department, tax, cost, scale FROM products WHERE upc=?");
        while (!feof($fp)) {
            $data = fgetcsv($fp);
            if (!is_array($data)) {
                continue;
            }
            if (!is_numeric($data[$this->COL_ORDER_ID])) {
                continue;
            }
            $mStoreID = $data[$this->COL_STORE_ID];
            $storeID = $mStoreID == 1692 ? 1 : 2;
            $utc = new DateTime($data[$this->COL_UTC_DATE] . ' UTC');
            $local = $utc->setTimeZone(new DateTimeZone('America/Chicago'));
            $mOrderID = $data[$this->COL_ORDER_ID];
            if ($mOrderID != $currentOrder['id']) {
                if ($currentOrder['total'] != 0) {
                    $orders[] = $currentOrder;
                } else {
                    echo "No total on transaction " . $currentOrder['id'] . "\n";
                }
                $currentOrder['id'] = $mOrderID;
                $currentOrder['total'] = 0;
                $currentOrder['qty'] = 0;
                $currentOrder['fees'] = 0;
                $currentOrder['card_no'] = 11;
                $currentOrder['memType'] = 0;
                $currentOrder['tdate'] = $local->format('Y-m-d H:i:s');
                $owner = $this->findOwner($this->dbc, $currentOrder['id'], $storeID);
                if ($owner != false) {
                    $currentOrder['card_no'] = $owner;
                    $currentOrder['memType'] = $this->getMemType($this->dbc, $owner);
                }
                $currentOrder['items'] = array();
            }
            $currentOrder['total'] += $data[$this->COL_AMT];

            switch (strtoupper($data[$this->COL_ROWTYPE])) {
                case 'SALE ITEM':
                    $upc = BarcodeLib::padUPC($data[$this->COL_UPC]);
                    if (strstr($upc, ",")) {
                        list($first,$second) = explode(",", $upc, 2);
                        $upc = BarcodeLib::padUPC(trim($second));
                    }
                    if ($upc == '0000000000000' && is_numeric($data[$this->COL_UPC - 1])) {
                        $upc = BarcodeLib::padUPC($data[$this->COL_UPC - 1]);
                    }
                    $qty = $data[$this->COL_QTY];
                    $total = $data[$this->COL_AMT];
                    // adjust total upward for Mercato's share of the item's value
                    $total += $data[$this->COL_AMT + 1];
                    $currentOrder['total'] += $data[$this->COL_AMT + 1];
                    $currentOrder['qty'] += (((int)$qty) == $qty) ? $qty : 1;
                    $item = $this->dbc->getRow($itemP, array($upc));
                    if ($item === false) {
                        $upc = '0' . substr($upc, 0, 12);
                        $item = $this->dbc->getRow($itemP, array($upc));
                    }
                    $item = array(
                        'upc' => $upc,
                        'description' => $item['description'],
                        'qty' => $qty,
                        'total' => $total,
                        'cost' => $item['cost'],
                    );
                    $currentOrder['items'][] = $item;
                    break;
                case 'SALE FEE': // intentional fallthrough
                case 'PROCESSING FEE':
                case 'PER ORDER FEE':
                case 'PROMO CODE':
                case 'SALE REFUND':
                case 'SALE ADJUSTMENT':
                    $total = $data[$this->COL_AMT];
                    $currentOrder['fees'] += $total;
                    break;
            }
        }
        if ($currentOrder['total'] != 0) {
            $orders[] = $currentOrder;
        } else {
            echo "No total on transaction " . $currentOrder['id'] . "\n";
        }
        fclose($fp);

        foreach ($orders as $order) {
            $fp = fopen(__DIR__ . '/noauto/converted/'
                . date('Ymd', strtotime($order['tdate']))
                . '_'
                . $order['id']
                . '_Mercato.txt', 'w');
            fwrite($fp, "[Header]\r\n");
            fwrite($fp, $order['id'] . ",");
            fwrite($fp, date('M-d-Y H:i:s', strtotime($order['tdate'])) . ",");
            fwrite($fp, $order['qty'] . ",");
            fwrite($fp, sprintf('%.2f', $order['total']) . ",");
            fwrite($fp, "0.00,");
            fwrite($fp, $order['card_no'] . ",");
            fwrite($fp, sprintf('%.2f', $order['fees']) . ",");
            fwrite($fp, date('M-d-Y H:i:s', strtotime($order['tdate'])) . ",");
            fwrite($fp, "0.00,");
            fwrite($fp, "0.00,");
            fwrite($fp, "0.00");
            fwrite($fp, "\r\n");

            fwrite($fp, "[PLUs]\r\n");
            $i = 1;
            foreach ($order['items'] as $item) {
                fwrite($fp, $i . ",");
                fwrite($fp, ",");
                fwrite($fp, $item['upc'] . ",");
                fwrite($fp, '"' . $item['description'] . '",');
                fwrite($fp, sprintf('%.2f', $item['total'] / $item['qty']) . ",");
                fwrite($fp, $item['qty'] . ",");
                fwrite($fp, sprintf('%.2f', $item['cost']) . ",");
                fwrite($fp, "0.00,");
                fwrite($fp, "0.00,");
                fwrite($fp, sprintf('%.2f', $item['total']));
                fwrite($fp, "\r\n");
                $i++;
            }

            fwrite($fp, "[Finalisers]\r\n");
            fwrite($fp, $i . ",");
            fwrite($fp, "7,");
            fwrite($fp, $order['id'] . ",");
            fwrite($fp, sprintf('%.2f', $order['total'] + $order['fees']));
            fwrite($fp, "\r\n");

            fclose($fp);
        }

    }

    private function findOwner($dbc, $orderID, $storeID)
    {
        $prep = $dbc->prepare("SELECT phone FROM MercatoOrders WHERE storeID=? AND orderID=?"); 
        $phone = $dbc->getValue($prep, array($storeID, $orderID)); 
        if (!$phone) {
            return false;
        }

        $findP = $dbc->prepare("SELECT card_no FROM meminfo AS m
            WHERE (phone LIKE ? OR email_2 LIKE ?)");
        $arg = '%' . str_replace(' ', '%', $phone) . '%';
        $found = $dbc->getAllValues($findP, array($arg, $arg));
        if (count($found) == 0) {
            return false;
        } elseif (count($found) == 1) {
            return $found[0];
        }

        list($inStr, $args) = $dbc->safeInClause($found);
        $prep = $dbc->prepare("SELECT CardNo FROM custdata WHERE CardNo IN ({$inStr}) AND personNum=1 AND Type='PC'");
        $mult = $dbc->getAllValues($prep, $args);
        if (count($mult) == 1) {
            return $mult[0];
        }

        return false;
    }

    private function getMemType($dbc, $card)
    {
        $prep = $dbc->prepare("SELECT memType FROM custdata WHERE CardNo=? AND personNum=1");
        return $dbc->getValue($prep, array($card));
    }
}
