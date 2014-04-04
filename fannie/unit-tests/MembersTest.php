<?php

/**
 * @backupGlobals disabled
 */
class MembersTest extends PHPUnit_Framework_TestCase
{
	public function testItems()
    {
        include_once(dirname(__FILE__) . '/../mem/MemberModule.php');
        $mems = FannieAPI::listModules('MemberModule', true);

        foreach($mems as $mem_class) {
            $obj = new $mem_class();
        }
    }

}

