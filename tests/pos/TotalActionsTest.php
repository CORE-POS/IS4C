<?php

/**
 * @backupGlobals disabled
 */
class TotalActionsTest extends PHPUnit_Framework_TestCase
{
    public function testAll()
    {
        $defaults = array(
            'COREPOS\\pos\\lib\\TotalActions\\AutoCoupon',
            'COREPOS\\pos\\lib\\TotalActions\\TotalAction',
        );

        $all = AutoLoader::ListModules('COREPOS\\pos\\lib\\TotalActions\\TotalAction', true);
        foreach ($defaults as $d){
            $this->assertContains($d, $all);
        }

        foreach ($all as $class) {
            $obj = new $class();
            $this->assertInstanceOf('COREPOS\\pos\\lib\\TotalActions\\TotalAction', $obj, $class . ' is not a TotalAction');

            $result = $obj->apply();
            $this->assertInternalType('boolean', $result, $class . ' apply() does not return boolean');
        }

    }
}
