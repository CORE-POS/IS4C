<?php

use League\Flysystem\Sftp\SftpAdapter;
use League\Flysystem\Filesystem;

class InstaImportTask extends FannieTask 
{
    public $name = 'Import InstaCart data';

    public $description = 'Imports InstaCart transaction data via FTP';

    public function run()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc = FannieDB::get($settings['InstaCartDB']);

        if (class_exists('League\\Flysystem\\Sftp\\SftpAdapter')) {
            $settings = $this->config->get('PLUGIN_SETTINGS');
            $adapter = new SftpAdapter(array(
                'host' => 'sftp.instacart.com',
                'username' => $settings['InstaCartFtpUser'],
                'password' => $settings['InstaCartFtpPw'],
                'port' => 22,
            ));
            $filesystem = new Filesystem($adapter);
            $contents = $filesystem->listContents($settings['InstaCartImportPath'], false);
            foreach ($contents as $c) {
                if ($c['extension'] == 'csv') {
                    $filename = __DIR__ . '/noauto/tlogs/' . $c['basename'];
                    if (file_exists($filename)) {
                        continue; // already imported
                    }
                    $csv = $filesystem->read($c['path']);
                    file_put_contents($filename, $csv);
                    echo "Processing {$c['basename']}\n";
                    $fp = fopen($filename, 'r');
                    while (!feof($fp)) {
                        $data = fgetcsv($fp);
                        if (!is_numeric($data[0])) {
                            continue;
                        }
                        $model = new InstaTransactionsModel($dbc);
                        $model->userID($data[0]);
                        $model->orderID($data[2]);
                        $model->deliveryID($data[3]);
                        $model->orderDate($this->dateToLocal($data[4]));
                        $model->deliveryDate($this->dateToLocal($data[5]));
                        $model->itemID($data[6]);
                        $model->upc($this->fixUPC($data[7]));
                        $model->quantity($data[12]);
                        $model->retailPrice($data[10]);
                        $model->retailTotal($data[13]);
                        $model->onlinePrice($data[14]);
                        $model->onlineTotal($data[15]);
                        $model->tax($data[16] ? $data[16] : 0);
                        $model->deposit($data[17] ? $data[17] : 0);
                        $model->bagFee($data[18] ? $data[18] : 0);
                        $model->total($data[19]);
                        $model->cardNo($data[22] ? $this->findOwner($dbc, $data[22]) : 11);
                        $model->storeID($data[24]);
                        $model->signupZip($data[1]);
                        $model->deliveryZip($data[23]);
                        $model->fullfillmentType(substr($data[38], 0, 1));
                        $model->platform($data[40]);
                        $model->isExpress($data[41] == 'TRUE' ? 1 : 0);
                        $model->save();
                    }
                    fclose($fp);
                }
            }
        }
    }

    private $cardP = false;
    private function findOwner($dbc, $card)
    {
        if (!$this->cardP) {
            $this->cardP = $dbc->prepare("SELECT card_no FROM " . FannieDB::fqn('memberCards', 'op') . " WHERE upc LIKE ?");
        }
        if (strlen($card) < 10) {
            return $card;
        }
        echo "Checking against $card\n";
        $suffix = $dbc->getValue($this->cardP, array('%' . $card));
        if ($suffix !== false) {
            var_dump($suffix);
            return $suffix;
        }
        $nocheck = substr($card, 0, strlen($card) - 1);
        echo "Also checking $nocheck\n";
        $suffix = $dbc->getValue($this->cardP, array('%' . $nocheck));
        if ($suffix !== false) {
            var_dump($suffix);
            return $suffix;
        }

        return $card;
    }

    private function dateToLocal($str)
    {
        $stamp = strtotime($str);
        return date('Y-m-d H:i:s', $stamp);
    }

    private function fixUPC($str)
    {
        $str = ltrim($str, '0');
        if (strlen($str) < 7) {
            return BarcodeLib::padUPC($str);
        } elseif ($str[0] == 2 && substr($str, -6) == '000000') {
            return BarcodeLib::padUPC($str);
        }

        return BarcodeLib::padUPC(substr($str, 0, strlen($str) - 1));
    }
}

