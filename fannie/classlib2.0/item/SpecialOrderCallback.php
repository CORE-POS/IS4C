<?php

namespace COREPOS\Fannie\API\item;

class SpecialOrderCallback
{
    public function run($orderID, $transID)
    {
        $log = new \FannieLogger();
        $log->debug('In the parent for some reason');
    }
}

