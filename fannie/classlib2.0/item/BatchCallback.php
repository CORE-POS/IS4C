<?php

namespace COREPOS\Fannie\API\item;

class BatchCallback
{
    public function run($batchID)
    {
        $log = new \FannieLogger();
        $log->debug('In the parent for some reason');
    }
}
