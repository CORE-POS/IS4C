<?php

class MercatoIntake
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
        $currentOrder = array('id' => false, 'total' => 0, 'tax' => 0, 'card_no' => 11, 'memType'=>0, 'tdate' => '');
        $trans_id = 1;
        $taxable = array(1 => 0, 2 => 0);
        $storeID = 0;
        $mOrderID = 0;
        $local = false;
        $rates = array();
        $res = $this->dbc->query("SELECT id, rate, description FROM taxrates");
        while ($row = $this->dbc->fetchRow($res)) {
            $rates[$row['id']] = $row;
        }

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
                    $prep = DTrans::parameterize($dtrans, 'datetime', $currentOrder['tdate']->format("'Y-m-d H:i:s'"));
                    $insP = $this->dbc->prepare("INSERT INTO " . FannieDB::fqn('dtransactions', 'trans') . " ({$prep['columnString']}) VALUES ({$prep['valueString']})");
                    $this->dbc->execute($insP, $prep['arguments']);
                    $trans_id++;

                    $estOne = $taxable[1] * $rates[1]['rate'];
                    $estTwo = $taxable[2] * $rates[2]['rate'];
                    $estTotal = $estOne + $estTwo;

                    $actualOne = 0;
                    $actualTwo = 0;
                    if ($estTotal != 0) {
                        $actualOne = round(($estOne / $estTotal) * $currentOrder['tax'], 2);
                        $actualTwo = $currentOrder['tax'] - $actualOne;
                    }

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
                    $dtrans['total'] = $actualOne + $actualTwo;
                    $prep = DTrans::parameterize($dtrans, 'datetime', $currentOrder['tdate']->format("'Y-m-d H:i:s'"));
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
                    $dtrans['upc'] = 'TAXLINEITEM';
                    $dtrans['description'] = sprintf('%.5f', $rates[1]['rate']*100) . ' ' . $rates[1]['description'];
                    $dtrans['trans_type'] = 'L';
                    $dtrans['trans_subtype'] = 'OG';
                    $dtrans['trans_status'] = 'D';
                    $dtrans['regPrice'] = $actualOne;
                    $dtrans['numflag'] = 1;
                    $prep = DTrans::parameterize($dtrans, 'datetime', $currentOrder['tdate']->format("'Y-m-d H:i:s'"));
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
                    $dtrans['upc'] = 'TAXLINEITEM';
                    $dtrans['description'] = sprintf('%.5f', $rates[2]['rate']*100) . ' ' . $rates[2]['description'];
                    $dtrans['trans_type'] = 'L';
                    $dtrans['trans_subtype'] = 'OG';
                    $dtrans['trans_status'] = 'D';
                    $dtrans['regPrice'] = $actualTwo;
                    $dtrans['numflag'] = 2;
                    $prep = DTrans::parameterize($dtrans, 'datetime', $currentOrder['tdate']->format("'Y-m-d H:i:s'"));
                    $insP = $this->dbc->prepare("INSERT INTO " . FannieDB::fqn('dtransactions', 'trans') . " ({$prep['columnString']}) VALUES ({$prep['valueString']})");
                    $this->dbc->execute($insP, $prep['arguments']);
                    $trans_id++;
                } else {
                    echo "No total on transaction " . $currentOrder['id'] . "\n";
                }
                $currentOrder['id'] = $mOrderID;
                $currentOrder['total'] = 0;
                $currentOrder['tax'] = 0;
                $currentOrder['card_no'] = 11;
                $currentOrder['memType'] = 0;
                $currentOrder['tdate'] = $local;
                $owner = $this->findOwner($this->dbc, $currentOrder['id'], $storeID);
                if ($owner != false) {
                    $currentOrder['card_no'] = $owner;
                    $currentOrder['memType'] = $this->getMemType($this->dbc, $owner);
                }
                $taxable = array(1 => 0, 2 => 0);
                $trans_id = 1;
            }
            $currentOrder['total'] += $data[$this->COL_AMT];

            $dtrans = DTrans::defaults();
            $dtrans['store_id'] = $storeID;
            $dtrans['emp_no'] = '1001';
            $dtrans['register_no'] = 40;
            $dtrans['trans_no'] = $currentOrder['id'];
            $dtrans['trans_id'] = $trans_id;
            $dtrans['card_no'] = $currentOrder['card_no'];
            $dtrans['memType'] = $currentOrder['memType'];

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
                    $item = $this->dbc->getRow($itemP, array($upc));
                    if ($item === false) {
                        $upc = '0' . substr($upc, 0, 12);
                        $item = $this->dbc->getRow($itemP, array($upc));
                    }
                    if (isset($item['tax']) && $item['tax']) {
                        $taxable[$item['tax']] += $total;
                    }
                    $dtrans['upc'] = $upc;
                    $dtrans['description'] = isset($item['description']) ? $item['description'] : '';
                    $dtrans['trans_type'] = 'I';
                    $dtrans['department'] = isset($item['department']) ? $item['department'] : 0;
                    $dtrans['quantity'] = $qty;
                    $dtrans['scale'] = isset($item['scale']) ? $item['scale'] : 0;
                    $dtrans['cost'] = (isset($item['cost']) ? $item['cost'] : 0) * $qty;
                    $dtrans['unitPrice'] = $total / $qty;
                    $dtrans['total'] = $total;
                    $dtrans['regPrice'] = $total / $qty;
                    $dtrans['tax'] = isset($item['tax']) ? $item['tax'] : 0;
                    $dtrans['ItemQtty'] = $qty;
                    $prep = DTrans::parameterize($dtrans, 'datetime', $local->format("'Y-m-d H:i:s'"));
                    $insP = $this->dbc->prepare("INSERT INTO " . FannieDB::fqn('dtransactions', 'trans') . " ({$prep['columnString']}) VALUES ({$prep['valueString']})");
                    $this->dbc->execute($insP, $prep['arguments']);
                    $trans_id++;
                    break;
                case 'SALE FEE': // intentional fallthrough
                case 'PROCESSING FEE':
                case 'PER ORDER FEE':
                case 'PROMO CODE':
                case 'SALE REFUND':
                case 'SALE ADJUSTMENT':
                case 'SALE TIP':
                    $total = $data[$this->COL_AMT];
                    $dtrans['upc'] = $total . 'DP802';
                    $dtrans['description'] = substr($data[$this->COL_ITEM], 0, 30);
                    $dtrans['trans_type'] = 'D';
                    $dtrans['department'] = 802;
                    $dtrans['quantity'] = 1;
                    $dtrans['unitPrice'] = $total;
                    $dtrans['total'] = $total;
                    $dtrans['regPrice'] = $total;
                    $dtrans['ItemQtty'] = 1;
                    $prep = DTrans::parameterize($dtrans, 'datetime', $local->format("'Y-m-d H:i:s'"));
                    $insP = $this->dbc->prepare("INSERT INTO " . FannieDB::fqn('dtransactions', 'trans') . " ({$prep['columnString']}) VALUES ({$prep['valueString']})");
                    $this->dbc->execute($insP, $prep['arguments']);
                    $trans_id++;
                    break;
                case 'SALES TAX':
                    $total = $data[$this->COL_AMT];
                    $currentOrder['tax'] += $total;
                    break;
            }

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
            $prep = DTrans::parameterize($dtrans, 'datetime', $currentOrder['tdate']->format("'Y-m-d H:i:s'"));
            $insP = $this->dbc->prepare("INSERT INTO " . FannieDB::fqn('dtransactions', 'trans') . " ({$prep['columnString']}) VALUES ({$prep['valueString']})");
            $this->dbc->execute($insP, $prep['arguments']);
            $trans_id++;

            $estOne = $taxable[1] * $rates[1]['rate'];
            $estTwo = $taxable[2] * $rates[2]['rate'];
            $estTotal = $estOne + $estTwo;

            $actualOne = 0;
            $actualTwo = 0;
            if ($estTotal != 0) {
                $actualOne = round(($estOne / $estTotal) * $currentOrder['tax'], 2);
                $actualTwo = $currentOrder['tax'] - $actualOne;
            }

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
            $dtrans['total'] = $actualOne + $actualTwo;
            $prep = DTrans::parameterize($dtrans, 'datetime', $currentOrder['tdate']->format("'Y-m-d H:i:s'"));
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
            $dtrans['upc'] = 'TAXLINEITEM';
            $dtrans['description'] = sprintf('%.5f', $rates[1]['rate']*100) . ' ' . $rates[1]['description'];
            $dtrans['trans_type'] = 'L';
            $dtrans['trans_subtype'] = 'OG';
            $dtrans['trans_status'] = 'D';
            $dtrans['regPrice'] = $actualOne;
            $dtrans['numflag'] = 1;
            $prep = DTrans::parameterize($dtrans, 'datetime', $currentOrder['tdate']->format("'Y-m-d H:i:s'"));
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
            $dtrans['upc'] = 'TAXLINEITEM';
            $dtrans['description'] = sprintf('%.5f', $rates[2]['rate']*100) . ' ' . $rates[2]['description'];
            $dtrans['trans_type'] = 'L';
            $dtrans['trans_subtype'] = 'OG';
            $dtrans['trans_status'] = 'D';
            $dtrans['regPrice'] = $actualTwo;
            $dtrans['numflag'] = 2;
            $prep = DTrans::parameterize($dtrans, 'datetime', $local->format("'Y-m-d H:i:s'"));
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
