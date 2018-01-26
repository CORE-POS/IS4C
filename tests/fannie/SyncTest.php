<?php

use COREPOS\Fannie\API\data\SyncLanes;
use COREPOS\Fannie\API\data\SyncSpecial;

/**
 * @backupGlobals disabled
 */
class SyncTest extends PHPUnit_Framework_TestCase
{
    public function testSpecialSync()
    {
        $config = FannieConfig::factory();
        $sync = new SyncSpecial($config);
        $stat = $sync->push('foo', $config->get('OP_DB'));
        $this->assertEquals(false, $stat['success']);

        $builtins = FannieAPI::listModules('COREPOS\Fannie\API\data\SyncSpecial');
        foreach ($builtins as $class) {
            $obj = new $class($config);
            $stat = $obj->push('products', $config->get('OP_DB'));
            $this->assertEquals(true, $stat['success'], "Sync failed on {$class}: {$stat['details']}");
        }
    }

    public function testSyncLanes()
    {
        $sync = SyncLanes::pushTable('invalid table', 'badDB');
        $this->assertEquals(false, $sync['sending']);
        $sync = SyncLanes::pushTable('invalid table', 'op');
        $this->assertEquals(false, $sync['sending']);
        $sync = SyncLanes::pushTable('', 'op');
        $this->assertEquals(false, $sync['sending']);
        $sync = SyncLanes::pushTable('disableCoupon', 'op');
        $this->assertEquals(true, $sync['sending']);

        $sync = SyncLanes::pullTable('invalid table', 'badDB');
        $this->assertEquals(false, $sync['sending']);
        $sync = SyncLanes::pullTable('invalid table', 'op');
        $this->assertEquals(false, $sync['sending']);
        $sync = SyncLanes::pullTable('', 'op');
        $this->assertEquals(false, $sync['sending']);
        $sync = SyncLanes::pullTable('disableCoupon', 'op');
        $this->assertEquals(true, $sync['sending'], 'Pull failed w/ ' . $sync['messages']);
    }
}

