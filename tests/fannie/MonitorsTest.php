<?php

/**
 * @backupGlobals disabled
 */
class MonitorsTest extends PHPUnit_Framework_TestCase
{
    public function testMonitors()
    {
        $mods = FannieAPI::listModules('\COREPOS\Fannie\API\monitor\Monitor', true);
        $conf = FannieConfig::factory();
        foreach ($mods as $class) {
            $obj = new $class($conf);
            $json = $obj->check();
            $this->assertInternalType('boolean', $obj->escalate($json));
            $this->assertInternalType('string', $obj->display($json));
        }
    }
}

