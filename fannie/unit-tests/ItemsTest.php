<?php

/**
 * @backupGlobals disabled
 */
class ItemsTest extends PHPUnit_Framework_TestCase
{
    public function testItems()
    {
        $items = FannieAPI::listModules('ItemModule', true);

        foreach($items as $item_class) {
            $obj = new $item_class();
        }
    }

}

