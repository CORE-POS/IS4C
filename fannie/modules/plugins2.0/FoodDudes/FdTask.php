<?php

if (!class_exists('FoodDudesUnofficial\\Client')) {
    include(__DIR__ . '/noauto/FoodDudesUnofficial/src/Client.php');
}

class FdTask extends FannieTask
{
    private function getOrders($start, $end)
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        try {
            $client = new FoodDudesUnofficial\Client($settings['FdUser'], $settings['FdPasswd']);
            return $client->getOrders($start, $end);
        } catch (Exception $ex) {
            return array();
        }
    }

    private function findOwner($tel)
    {
        $area = substr($tel, 0, 3);
        $mid = substr($tel, 3, 3);
        $last4 = substr($tel, -4);
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $prep = $dbc->prepare('SELECT CardNo AS card_no, memType, staff FROM custdata
            AS c INNER JOIN meminfo AS m ON c.CardNo=m.meminfo
            WHERE c.personNum=1
                AND (m.phone LIKE ? OR m.email_2 LIKE ?)');
        $match = $dbc->getRow($prep, array($area . '%' . $mid . '%' . $last4));
        if ($match) {
            return $match;
        }
        $match = $dbc->getRow($prep, array($mid . '%' . $last4));
        if ($match) {
            return $match;
        }

        return array('card_no'=>11, 'memType'=>0, 'staff'=>0);
    }

    private function getOrderInfo($order)
    {
        $date = $order['orders_info']['date_deliver'];
        $dateParts = explode(' ', $date);
        preg_match('/(\d+)\D+(\d+)/', $dateParts[0], $matches);
        $month = $matches[1];
        $day = $matches[1];
        $year = $month < date('n') ? date('Y') : date('Y') + 1;
        preg_match('/(\d+):(\d+)(.+)/', $dateParts[1], $matches);
        $min = $matches[2];
        $hour = $matches[1];
        if (strtolower($matches[3]) == 'pm' && $hour < 12) {
            $hour += 12;
        } elseif (strtolower($matches[3]) == 'am' && $hour == 12) {
            $hour = 0;
        }

        return array(
            'id' => $order['orders_info']['orders_id'],
            'total' => trim($order['orders_info']['order_total'], '$'),
            'name' => $order['orders_info']['customers_name'],
            'phone' => $order['orders_info']['customers_telephone'],
            'date' => date('Y-m-d H:i:s', mktime($hour, $min, 0, $month, $day, $year)),
            'mem' => $this->findOwner($order['orders_info']['customers_telephone']),
        );
    }

    private function getOrderItems($order)
    {
        $ret = array();
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $findP = $dbc->prepare('SELECT transUPC, description, department
            FROM FoodDudesMap AS f
                LEFT JOIN products AS p ON f.transUPC=p.upc AND p.store_id=1
            WHERE foodDudesSKU=?');
        foreach ($order['products'] as $item) {
            $sku = $item['orders_products_id'];
            $debug = $item['products_name'];
            $qty = $item['products_quantity'];
            $price = $item['products_price'];
            if (isset($item['attributes'])) {
                foreach ($item['attributes'] as $attr) {
                    if (isset($attr['options_values_price'])) {
                        $price += $attr['options_values_price'];
                        $debug .= ', ' . $attr['products_options_values'];
                    }
                }
            }
            $prod = $dbc->getRow($findP, array($sku));
            $ret[] = array(
                'sku' => $sku,
                'debug' => $debug,
                'qty' => $qty,
                'price' => $price,
                'upc' => $prod['transUPC'],
                'description' => $prod['description'],
                'department' => $prod['department'],
            );
        }

        return $ret;
    }

    private function sumItems($items)
    {
        $sum = 0;
        foreach ($items as $i) {
            $sum += $i['qty'] * $i['price'];
        }

        return $sum;
    }

    public function run()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $dtrans = $this->config->get('TRANS_DB') . $dbc->sep() . 'dtransactions';
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $yesterday = date('Y-m-d', strtotime('yesterday'));
        $orders = $this->getOrders($yesterday, $yesterday);
        foreach ($orders as $o) {
            $base = $this->getOrderInfo($o);
            $items = $this->getOrderItems($o);
            $check = $this->sumItems($items);
            if (abs($check - $base['total']) > 0.005) {
                $this->cronMsg("Pricing mismatch with order {$base['id']}");
            }
            $transNo = DTrans::getTransNo($dbc, $settings['FdEmpNo'], $settings['FdRegNo']);
            $transID = 1;

            foreach ($items as $item) {
                $param = DTrans::defaults();
                $param['datetime'] = $base['date'];
                $param['emp_no'] = $settings['FdEmpNo'];
                $param['register_no'] = $settings['FdRegNo'];
                $param['trans_no'] = $transNo;
                $param['upc'] = $item['upc'];
                $param['description'] = $item['description'];
                $param['trans_type'] = 'I';
                $param['department'] = $item['department'];
                $param['quantity'] = $item['qty'];
                $param['ItemQtty'] = $item['qty'];
                $param['total'] = $item['price'];
                $param['unitPrice'] = $item['price'] / $item['qty'];
                $param['regPrice'] = $item['price'] / $item['qty'];
                $param['card_no'] = $base['mem']['card_no'];
                $param['memType'] = $base['mem']['memType'];
                $param['staff'] = $base['mem']['staff'];
                $param['trans_id'] = $transID;

                $parts = DTrans::parameterize($param);
                $prep = $dbc->prepare("INSERT INTO {$dtrans} ({$parts['columnString']}) VALUES ({$parts['valueString']})");
                $dbc->execute($prep, $parts['arguments']);
                $transID++;
            }

            $param = DTrans::defaults();
            $param['datetime'] = $base['date'];
            $param['emp_no'] = $settings['FdEmpNo'];
            $param['register_no'] = $settings['FdRegNo'];
            $param['trans_no'] = $transNo;
            $param['upc'] = '0';
            $param['description'] = 'FOOD DUDES';
            $param['trans_type'] = 'T';
            $param['total'] = -1 * $base['total'];
            $param['card_no'] = $base['mem']['card_no'];
            $param['memType'] = $base['mem']['memType'];
            $param['staff'] = $base['mem']['staff'];
            $param['trans_id'] = $transID;
            $parts = DTrans::parameterize($param);
            $prep = $dbc->prepare("INSERT INTO {$dtrans} ({$parts['columnString']}) VALUES ({$parts['valueString']})");
            $dbc->execute($prep, $parts['arguments']);
        }
    }
}

