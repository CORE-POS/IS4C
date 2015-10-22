<?php

/**
 * @backupGlobals disabled
 */
class TotalActionsTest extends PHPUnit_Framework_TestCase
{
    public function testAll()
    {
        $defaults = array(
            'AutoCoupon',
            'TotalAction',
        );

        $all = AutoLoader::ListModules('TotalAction', true);
        foreach ($defaults as $d){
            $this->assertContains($d, $all);
        }

        foreach ($all as $class) {
            $obj = new $class();
            $this->assertInstanceOf('TotalAction', $obj, $class . ' is not a TotalAction');

            $result = $obj->apply();
            $this->assertInternalType('boolean', $result, $class . ' apply() does not return boolean');
        }

    }
}
