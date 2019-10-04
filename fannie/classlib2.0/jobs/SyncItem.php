<?php

namespace COREPOS\Fannie\API\jobs;

class SyncItem extends Job
{
    public function run()
    {
        if (!isset($this->data['upc'])) {
            echo "Error: no UPC specified" . PHP_EOL;
            return false;
        }

        \COREPOS\Fannie\API\data\ItemSync::sync($this->data['upc']);
    }
}

