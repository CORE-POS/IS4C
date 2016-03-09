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

        // not stocked, discountable, markdown
        $this->assertEquals(9, OrderItemLib::getUnitPrice($item, $is_member));
        $item['discountable'] = 0;
        // not stocked, not discountable
        $this->assertEquals(10, OrderItemLib::getUnitPrice($item, $is_member));
        $item['discountable'] = 1;
        $is_member['type'] = 'markup';
        // not stocked, discountable, markup
        $this->assertEquals(1.10, OrderItemLib::getUnitPrice($item, $is_member));
        $is_member['type'] = 'foo';
        // not stocked, discountable, bad type
        $this->assertEquals(10, OrderItemLib::getUnitPrice($item, $is_member));

        $item['stocked'] = 1;
        $item['discountable'] = 1;
        $is_member['type'] = 'markdown';
        // stocked, discountable, markdown
        $this->assertEquals(9, OrderItemLib::getUnitPrice($item, $is_member));
        $item['discountable'] = 0;
        // stocked, not discountable
        $this->assertEquals(10, OrderItemLib::getUnitPrice($item, $is_member));

        $item['discountable'] = 1;
        $item['special_price'] = 5;
        $item['discounttype'] = 1;
        // sale check fails because batch does not exist
        $this->assertEquals(9, OrderItemLib::getUnitPrice($item, $is_member));
        $item['discounttype'] = 2;
        $this->assertEquals(9, OrderItemLib::getUnitPrice($item, $is_member));
    }
}

