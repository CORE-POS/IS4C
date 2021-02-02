<?php

class MOrderBotTask extends FannieTask 
{
    public function run()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');

        $user = $settings['MercatoBotUser'];
        $pass = $settings['MercatoBotPw'];
        $dsn = 'mysql://' 
            . $this->config->get('SERVER_USER') . ':'
            . $this->config->get('SERVER_PW') . '@'
            . $this->config->get('SERVER') . '/'
            . $this->config->get('OP_DB');

        chdir(__DIR__ . '/noauto');
        $cmd = './morders.py'
            . ' ' . escapeshellarg('-u')
            . ' ' . escapeshellarg($user)
            . ' ' . escapeshellarg('-p')
            . ' ' . escapeshellarg($pass)
            . ' ' . escapeshellarg('-d')
            . ' ' . escapeshellarg($dsn);

        $ret = exec($cmd, $output);
        echo implode("\n", $output) . "\n";

        if ($ret != 0) {
            $this->cronMsg("Mercato Bot errored\n" . implode("\n", $output) . "\n", FannieLogger::ALERT);
        }

        $fp = fopen('/tmp/mc/store-orders.csv', 'r');
        $storeID = $this->config->get('STORE_ID');
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $chkP = $dbc->prepare("SELECT mercatoOrderID FROM MercatoOrders WHERE orderID=? AND storeID=?");
        $upP = $dbc->prepare("UPDATE MercatoOrders SET status=? WHERE mercatoOrderID=?");
        $insP = $dbc->prepare("INSERT INTO MercatoOrders (orderID, storeID, name, type, status, pdate)
            VALUES (?, ?, ?, ?, ?, ?)");
        $count = 0;
        while (!feof($fp)) {
            $data = fgetcsv($fp);
            $orderID = $data[0];
            if (!is_numeric($orderID)) {
                continue;
            }
            $status = $data[10];
            $realID = $dbc->getValue($chkP, array($orderID, $storeID));
            if ($realID) {
                $dbc->execute($upP, array($status, $realID));
            } else {
                $name = $data[5];
                $type = $data[7];
                $pdate = date('Y-m-d H:i:s', strtotime($data[11] . ' ' . $data[12]));
                $dbc->execute($insP, array($orderID, $storeID, $name, $type, $status, $pdate));
            }
            $count++;
            if ($count > 50) {
                break;
            }
        }
        fclose($fp);
        $fp = fopen('/tmp/mc/phones.csv', 'r');
        $upP = $dbc->prepare("UPDATE MercatoOrders SET phone=? WHERE orderID=? AND storeID=?");
        $count = 0;
        while (!feof($fp)) {
            $data = fgetcsv($fp);
            $orderID = $data[0];
            $phone = $data[1];
            $phone = str_replace('(', '', $phone);
            $phone = str_replace(')', '', $phone);
            $phone = str_replace('-', ' ', $phone);
            $dbc->execute($upP, array($phone, $orderID, $storeID));
            $count++;
            if ($count > 50) {
                break;
            }
        }
        fclose($fp);
    }
}

