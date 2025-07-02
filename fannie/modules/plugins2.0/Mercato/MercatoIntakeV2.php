<?php

class MercatoIntakeV2
{
    private $dbc = null;

    private $COL_ORDER_DATE = 7;
    private $COL_ORDER_FEES = 6;
    private $COL_ORDER_ID = 0;
    private $COL_AMT = 9;
    private $COL_UPC = 2;
    private $COL_QTY = 5;
    private $COL_ITEM = 3;

    public function __construct($dbc)
    {
        $this->dbc = $dbc;
    }

    public function process($filename)
    {
        $fp = fopen($filename, 'r');
        $currentOrder = array('id' => false, 'total' => 0, 'qty' => 0, 'fees' => 0, 'card_no' => 11, 'memType' => 0, 'tdate' => '', 'items' => array());
        $trans_id = 1;
        $storeID = 1;
        $itemP = $this->dbc->prepare("SELECT description, department, tax, cost, scale FROM products WHERE upc=?");

        // header
        while (!feof($fp)) {
            $data = fgetcsv($fp);
            if (!is_array($data)) {
                continue;
            }
            if ($data[0] == '[Header]') {
                $header = fgetcsv($fp);
                $currentOrder['id'] = $header[$this->COL_ORDER_ID];
                $currentOrder['tdate'] = date('Y-m-d H:i:s', strtotime($header[$this->COL_ORDER_DATE]));
                $currentOrder['fees'] = date('Y-m-d H:i:s', strtotime($header[$this->COL_ORDER_FEES]));
                $owner = $this->findOwner($this->dbc, $currentOrder['id'], $storeID);
                if ($owner != false) {
                    $currentOrder['card_no'] = $owner;
                    $currentOrder['memType'] = $this->getMemType($this->dbc, $owner);
                }
                break;
            }
        }

        // tax & PLUs
        // taxes are just skipped
        $skipping = true;
        while (!feof($fp)) {
            $data = fgetcsv($fp);
            if ($data[0] == '[PLUs]') {
                $skipping = false;
                continue;
            }
            if ($data[0] == '[Finalisers]') {
                break;
            }
            $currentOrder['total'] += $data[$this->COL_AMT] * 1.05;

            $dtrans = DTrans::defaults();
            $dtrans['store_id'] = $storeID;
            $dtrans['emp_no'] = '1001';
            $dtrans['register_no'] = 40;
            $dtrans['trans_no'] = $currentOrder['id'];
            $dtrans['trans_id'] = $trans_id;
            $dtrans['card_no'] = $currentOrder['card_no'];
            $dtrans['memType'] = $currentOrder['memType'];

            $upc = BarcodeLib::padUPC(str_replace('BOGO', '', $data[$this->COL_UPC]));
            if (strstr($upc, ",")) {
                list($first,$second) = explode(",", $upc, 2);
                $upc = BarcodeLib::padUPC(trim($second));
            }
            if ($upc == '0000000000000' && is_numeric($data[$this->COL_UPC - 1])) {
                $upc = BarcodeLib::padUPC($data[$this->COL_UPC - 1]);
            }
            $qty = $data[$this->COL_QTY];
            $total = $data[$this->COL_AMT] * 1.05;
            $item = $this->dbc->getRow($itemP, array($upc));
            if ($item === false) {
                $upc = '0' . substr($upc, 0, 12);
                $item = $this->dbc->getRow($itemP, array($upc));
            }
            $dtrans['upc'] = $upc;
            $dtrans['description'] = isset($item['description']) ? $item['description'] : '';
            $dtrans['trans_type'] = 'I';
            $dtrans['department'] = isset($item['department']) ? $item['department'] : 0;
            $dtrans['quantity'] = $qty;
            $dtrans['scale'] = isset($item['scale']) ? $item['scale'] : 0;
            $dtrans['cost'] = (isset($item['cost']) ? $item['cost'] : 0) * $qty;
            $dtrans['unitPrice'] = $qty == 0 ? 0 : $total / $qty;
            $dtrans['total'] = $total;
            $dtrans['regPrice'] = $qty == 0 ? 0 : $total / $qty;
            $dtrans['tax'] = isset($item['tax']) ? $item['tax'] : 0;
            $dtrans['ItemQtty'] = $qty;
            $prep = DTrans::parameterize($dtrans, 'datetime', "'" . $currentOrder['tdate'] . "'");
            $insP = $this->dbc->prepare("INSERT INTO " . FannieDB::fqn('dtransactions', 'trans') . " ({$prep['columnString']}) VALUES ({$prep['valueString']})");
            $this->dbc->execute($insP, $prep['arguments']);
            $trans_id++;
        }

        if ($currentOrder['total'] != 0) {

            $dtrans = DTrans::defaults();
            $dtrans['store_id'] = $storeID;
            $dtrans['emp_no'] = '1001';
            $dtrans['register_no'] = 40;
            $dtrans['trans_no'] = $currentOrder['id'];
            $dtrans['trans_id'] = $trans_id;
            $dtrans['card_no'] = $currentOrder['card_no'];
            $dtrans['memType'] = $currentOrder['memType'];
            $dtrans['upc'] = '0';
            $dtrans['description'] = 'Mercato Tender';
            $dtrans['trans_type'] = 'T';
            $dtrans['trans_subtype'] = 'ME';
            $dtrans['total'] = -1 * $currentOrder['total'];
            $prep = DTrans::parameterize($dtrans, 'datetime', "'" . $currentOrder['tdate'] . "'");
            $insP = $this->dbc->prepare("INSERT INTO " . FannieDB::fqn('dtransactions', 'trans') . " ({$prep['columnString']}) VALUES ({$prep['valueString']})");
            $this->dbc->execute($insP, $prep['arguments']);
            $trans_id++;

            $dtrans = DTrans::defaults();
            $dtrans['store_id'] = $storeID;
            $dtrans['emp_no'] = '1001';
            $dtrans['register_no'] = 40;
            $dtrans['trans_no'] = $currentOrder['id'];
            $dtrans['trans_id'] = $trans_id;
            $dtrans['card_no'] = $currentOrder['card_no'];
            $dtrans['memType'] = $currentOrder['memType'];
            $dtrans['upc'] = 'TAX';
            $dtrans['description'] = 'Tax';
            $dtrans['trans_type'] = 'A';
            $dtrans['total'] = 0;
            $prep = DTrans::parameterize($dtrans, 'datetime', "'" . $currentOrder['tdate'] . "'");
            $insP = $this->dbc->prepare("INSERT INTO " . FannieDB::fqn('dtransactions', 'trans') . " ({$prep['columnString']}) VALUES ({$prep['valueString']})");
            $this->dbc->execute($insP, $prep['arguments']);
            $trans_id++;
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
