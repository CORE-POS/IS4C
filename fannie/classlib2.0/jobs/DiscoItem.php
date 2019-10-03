<?php

namespace COREPOS\Fannie\API\jobs;
use \FannieDB;
use \FannieConfig;
use \BarcodeLib;

class DiscoItem extends Job
{
    public function run()
    {
        if (!isset($this->data['upc'])) {
            echo "Error: no UPC specified" . PHP_EOL;
            return false;
        }
        if (!isset($this->data['store'])) {
            echo "Error: no store specified" . PHP_EOL;
            return false;
        }

        if (!isset($this->data['flag'])) {
            echo "Error: no store specified" . PHP_EOL;
            return false;
        }

        if ($this->data['flag']) {
            $config = FannieConfig::factory();
            $dbc = FannieDB::get($config->get('OP_DB'));
            $this->zeroPar($dbc, $this->data['upc'], $this->data['store']);
        }
    }

    private function zeroPar($dbc, $upc, $store)
    {
        echo "Zero par on {$upc}, {$store}" . PHP_EOL;
        $prep = $dbc->prepare("UPDATE InventoryCounts SET par=0 WHERE upc=? AND storeID=?");
        $dbc->execute($prep, array(BarcodeLib::padUPC($upc), $store));
    }
}

