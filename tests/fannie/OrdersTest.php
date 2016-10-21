<?php

/**
 * @backupGlobals disabled
 */
class OrdersTest extends PHPUnit_Framework_TestCase
{
    public function testPricing()
    {
        $item = OrderItemLib::getItem('fakeItem');
        $this->assertInternalType('array', $item);
        $item['normal_price'] = 10;
        $item['cost'] = 1;
        $is_member = array(
            'type' => 'markdown',
            'amount' => 0.10,
            'isMember' => true,
        );

        // not stocked
        $this->assertEquals(10, OrderItemLib::getUnitPrice($item, $is_member));

        $item['stocked'] = 1;
        // stocked
        $this->assertEquals(10, OrderItemLib::getUnitPrice($item, $is_member));

        $item['special_price'] = 5;
        $item['discounttype'] = 1;
        // sale check fails because batch does not exist
        $this->assertEquals(10, OrderItemLib::getUnitPrice($item, $is_member));
        $item['discounttype'] = 2;
        $this->assertEquals(10, OrderItemLib::getUnitPrice($item, $is_member));
    }
}

