<?php

/**
 * @backupGlobals disabled
 */
class MembersTest extends PHPUnit_Framework_TestCase
{
    public function testItems()
    {
        $mems = FannieAPI::listModules('MemberModule', true);

        foreach($mems as $mem_class) {
            $obj = new $mem_class();
        }
    }

}

