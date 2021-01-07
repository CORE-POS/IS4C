<?php

class MercatoIntake
{
    private $dbc = null;

    public function __construct($dbc)
    {
        $this->dbc = $dbc;
    }

    public function process($filename)
    {
        $fp = fopen($filename, 'r');
        $currentOrder = array('id' => false, 'total' => 0, 'tax' => 0);
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

        print_r($rates);
        $itemP = $this->dbc->prepare("SELECT description, department, tax, cost, scale FROM products WHERE upc=?");
        while (!feof($fp)) {
            $data = fgetcsv($fp);
            if (!is_numeric($data[6])) {
                continue;
            }
            $mStoreID = $data[3];
            $storeID = $mStoreID == 1692 ? 1 : 2;
            $utc = new DateTime($data[1] . ' UTC');
            $local = $utc->setTimeZone(new DateTimeZone('America/Chicago'));
            $mOrderID = $data[6];
            if ($mOrderID != $currentOrder['id']) {
                if ($currentOrder['total'] != 0) {

                    $dtrans = DTrans::defaults();
                    $dtrans['store_id'] = $storeID;
                    $dtrans['emp_no'] = '1001';
                    $dtrans['register_no'] = 40;
                    $dtrans['trans_no'] = $currentOrder['id'];
                    $dtrans['trans_id'] = $trans_id;
                    $dtrans['card_no'] = 11;
                    $dtrans['upc'] = '0';
                    $dtrans['description'] = 'Mercato Tender';
                    $dtrans['trans_type'] = 'T';
                    $dtrans['trans_subtype'] = 'ME';
                    $dtrans['total'] = -1 * $currentOrder['total'];
                    $prep = DTrans::parameterize($dtrans, 'datetime', $local->format("'Y-m-d H:i:s'"));
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
                    $dtrans['card_no'] = 11;
                    $dtrans['upc'] = 'TAX';
                    $dtrans['description'] = 'Tax';
                    $dtrans['trans_type'] = 'A';
                    $dtrans['total'] = $actualOne + $actualTwo;
                    $prep = DTrans::parameterize($dtrans, 'datetime', $local->format("'Y-m-d H:i:s'"));
                    $insP = $this->dbc->prepare("INSERT INTO " . FannieDB::fqn('dtransactions', 'trans') . " ({$prep['columnString']}) VALUES ({$prep['valueString']})");
                    $this->dbc->execute($insP, $prep['arguments']);
                    $trans_id++;

                    $dtrans = DTrans::defaults();
                    $dtrans['store_id'] = $storeID;
                    $dtrans['emp_no'] = '1001';
                    $dtrans['register_no'] = 40;
                    $dtrans['trans_no'] = $currentOrder['id'];
                    $dtrans['trans_id'] = $trans_id;
                    $dtrans['card_no'] = 11;
                    $dtrans['upc'] = 'TAXLINEITEM';
                    $dtrans['description'] = sprintf('%.5f', $rates[1]['rate']*100) . ' ' . $rates[1]['description'];
                    $dtrans['trans_type'] = 'L';
                    $dtrans['trans_subtype'] = 'OG';
                    $dtrans['trans_status'] = 'D';
                    $dtrans['regPrice'] = $actualOne;
                    $prep = DTrans::parameterize($dtrans, 'datetime', $local->format("'Y-m-d H:i:s'"));
                    $insP = $this->dbc->prepare("INSERT INTO " . FannieDB::fqn('dtransactions', 'trans') . " ({$prep['columnString']}) VALUES ({$prep['valueString']})");
                    $this->dbc->execute($insP, $prep['arguments']);
                    $trans_id++;

                    $dtrans = DTrans::defaults();
                    $dtrans['store_id'] = $storeID;
                    $dtrans['emp_no'] = '1001';
                    $dtrans['register_no'] = 40;
                    $dtrans['trans_no'] = $currentOrder['id'];
                    $dtrans['trans_id'] = $trans_id;
                    $dtrans['card_no'] = 11;
                    $dtrans['upc'] = 'TAXLINEITEM';
                    $dtrans['description'] = sprintf('%.5f', $rates[2]['rate']*100) . ' ' . $rates[2]['description'];
                    $dtrans['trans_type'] = 'L';
                    $dtrans['trans_subtype'] = 'OG';
                    $dtrans['trans_status'] = 'D';
                    $dtrans['regPrice'] = $actualTwo;
                    $prep = DTrans::parameterize($dtrans, 'datetime', $local->format("'Y-m-d H:i:s'"));
                    $insP = $this->dbc->prepare("INSERT INTO " . FannieDB::fqn('dtransactions', 'trans') . " ({$prep['columnString']}) VALUES ({$prep['valueString']})");
                    $this->dbc->execute($insP, $prep['arguments']);
                    $trans_id++;
                }
                $currentOrder['id'] = $mOrderID;
                $currentOrder['total'] = 0;
                $currentOrder['tax'] = 0;
                $taxable = array(1 => 0, 2 => 0);
                $trans_id = 1;
            }
            $currentOrder['total'] += $data[14];

            $dtrans = DTrans::defaults();
            $dtrans['store_id'] = $storeID;
            $dtrans['emp_no'] = '1001';
            $dtrans['register_no'] = 40;
            $dtrans['trans_no'] = $mOrderID;
            $dtrans['trans_id'] = $trans_id;
            $dtrans['card_no'] = 11;

            switch (strtoupper($data[5])) {
                case 'SALE ITEM':
                    $upc = BarcodeLib::padUPC($data[9]);
                    $qty = $data[12];
                    $total = $data[14];
                    $item = $this->dbc->getRow($itemP, array($upc));
                    if ($item['tax']) {
                        echo "Tax on $mOrderID $upc is $total\n";
                        $taxable[$item['tax']] += $total;
                    }
                    $dtrans['upc'] = $upc;
                    $dtrans['description'] = $item['description'];
                    $dtrans['trans_type'] = 'I';
                    $dtrans['department'] = $item['department'];
                    $dtrans['quantity'] = $qty;
                    $dtrans['scale'] = $item['scale'];
                    $dtrans['cost'] = $item['cost'] * $qty;
                    $dtrans['unitPrice'] = $total / $qty;
                    $dtrans['total'] = $total;
                    $dtrans['regPrice'] = $total / $qty;
                    $dtrans['tax'] = $item['tax'];
                    $dtrans['ItemQtty'] = $qty;
                    $prep = DTrans::parameterize($dtrans, 'datetime', $local->format("'Y-m-d H:i:s'"));
                    $insP = $this->dbc->prepare("INSERT INTO " . FannieDB::fqn('dtransactions', 'trans') . " ({$prep['columnString']}) VALUES ({$prep['valueString']})");
                    $this->dbc->execute($insP, $prep['arguments']);
                    $trans_id++;
                    break;
                case 'SALE FEE': // intentional fallthrough
                case 'PROCESSING FEE':
                    $total = $data[14];
                    $dtrans['upc'] = $total . 'DP802';
                    $dtrans['description'] = substr($data[7], 0, 30);
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
                    $total = $data[14];
                    $currentOrder['tax'] += $total;
                    echo $currentOrder['tax'] . ' tax set to ' . $total . "\n";
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
            $dtrans['card_no'] = 11;
            $dtrans['upc'] = '0';
            $dtrans['description'] = 'Mercato Tender';
            $dtrans['trans_type'] = 'T';
            $dtrans['trans_subtype'] = 'ME';
            $dtrans['total'] = -1 * $currentOrder['total'];
            $prep = DTrans::parameterize($dtrans, 'datetime', $local->format("'Y-m-d H:i:s'"));
            $insP = $this->dbc->prepare("INSERT INTO " . FannieDB::fqn('dtransactions', 'trans') . " ({$prep['columnString']}) VALUES ({$prep['valueString']})");
            $this->dbc->execute($insP, $prep['arguments']);
            $trans_id++;

            $estOne = $taxable[1] * $rates[1]['rate'];
            $estTwo = $taxable[2] * $rates[2]['rate'];
                    print_r($taxable);
                    echo "$estOne $estTwo\n";
            $estTotal = $estOne + $estTwo;

            $actualOne = 0;
            $actualTwo = 0;
            if ($estTotal != 0) {
                $actualOne = round(($estOne / $estTotal) * $currentOrder['tax'], 2);
                $actualTwo = $currentOrder['tax'] - $actualOne;
            }
            echo "$actualOne $actualTwo\n";
            echo $currentOrder['id'] . ' ' . $currentOrder['tax'] . "\n";

            $dtrans = DTrans::defaults();
            $dtrans['store_id'] = $storeID;
            $dtrans['emp_no'] = '1001';
            $dtrans['register_no'] = 40;
            $dtrans['trans_no'] = $currentOrder['id'];
            $dtrans['trans_id'] = $trans_id;
            $dtrans['card_no'] = 11;
            $dtrans['upc'] = 'TAX';
            $dtrans['description'] = 'Tax';
            $dtrans['trans_type'] = 'A';
            $dtrans['total'] = $actualOne + $actualTwo;
            $prep = DTrans::parameterize($dtrans, 'datetime', $local->format("'Y-m-d H:i:s'"));
            $insP = $this->dbc->prepare("INSERT INTO " . FannieDB::fqn('dtransactions', 'trans') . " ({$prep['columnString']}) VALUES ({$prep['valueString']})");
            $this->dbc->execute($insP, $prep['arguments']);
            $trans_id++;

            $dtrans = DTrans::defaults();
            $dtrans['store_id'] = $storeID;
            $dtrans['emp_no'] = '1001';
            $dtrans['register_no'] = 40;
            $dtrans['trans_no'] = $currentOrder['id'];
            $dtrans['trans_id'] = $trans_id;
            $dtrans['card_no'] = 11;
            $dtrans['upc'] = 'TAXLINEITEM';
            $dtrans['description'] = sprintf('%.5f', $rates[1]['rate']*100) . ' ' . $rates[1]['description'];
            $dtrans['trans_type'] = 'L';
            $dtrans['trans_subtype'] = 'OG';
            $dtrans['trans_status'] = 'D';
            $dtrans['regPrice'] = $actualOne;
            $prep = DTrans::parameterize($dtrans, 'datetime', $local->format("'Y-m-d H:i:s'"));
            $insP = $this->dbc->prepare("INSERT INTO " . FannieDB::fqn('dtransactions', 'trans') . " ({$prep['columnString']}) VALUES ({$prep['valueString']})");
            $this->dbc->execute($insP, $prep['arguments']);
            $trans_id++;

            $dtrans = DTrans::defaults();
            $dtrans['store_id'] = $storeID;
            $dtrans['emp_no'] = '1001';
            $dtrans['register_no'] = 40;
            $dtrans['trans_no'] = $currentOrder['id'];
            $dtrans['trans_id'] = $trans_id;
            $dtrans['card_no'] = 11;
            $dtrans['upc'] = 'TAXLINEITEM';
            $dtrans['description'] = sprintf('%.5f', $rates[2]['rate']*100) . ' ' . $rates[2]['description'];
            $dtrans['trans_type'] = 'L';
            $dtrans['trans_subtype'] = 'OG';
            $dtrans['trans_status'] = 'D';
            $dtrans['regPrice'] = $actualTwo;
            $prep = DTrans::parameterize($dtrans, 'datetime', $local->format("'Y-m-d H:i:s'"));
            $insP = $this->dbc->prepare("INSERT INTO " . FannieDB::fqn('dtransactions', 'trans') . " ({$prep['columnString']}) VALUES ({$prep['valueString']})");
            $this->dbc->execute($insP, $prep['arguments']);
            $trans_id++;
        }
    }
}
