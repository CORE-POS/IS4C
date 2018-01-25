<?php

use COREPOS\Fannie\API\data\SyncSpecial;

/**
 * @backupGlobals disabled
 */
class SyncTest extends PHPUnit_Framework_TestCase
{
    public function testLaneSync()
    {
        $config = FannieConfig::factory();
        $sync = new SyncSpecial($config);
        $stat = $sync->push('foo', $config->get('OP_DB'));
        $this->assertEquals(true, $stat['success']);

        $builtins = FannieAPI::listModules('COREPOS\Fannie\API\data\SyncSpecial');
        foreach ($builtins as $class) {
            $obj = new $class($config);
            $stat = $obj->push('departments', $config->get('OP_DB'));
            $this->assertEquals(true, $stat['success'], "Sync failed on {$class}: {$stat['details']}");
        }
    }
}

