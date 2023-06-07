<?php

namespace COREPOS\Fannie\API\lib;

class EmployeeCallback
{
    public function run($upc)
    {
        $log = new \FannieLogger();
        $log->debug('In the parent for some reason');
    }
}
