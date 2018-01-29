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

    public function testSpoLib()
    {
        if (!class_exists('SpecialOrderLib')) {
            include(__DIR__ . '/../../fannie/ordering/SpecialOrderLib.php');
        }
        $conf = FannieConfig::factory();
        $lib = new SpecialOrderLib(FannieDB::get($conf->get('OP_DB')), $conf);
        $this->assertEquals(true, is_numeric($lib->createEmptyOrder()));
    }

    public function testExporters()
    {
        $exp = array(
            'ChefTecExport',
            'DefaultCsvPoExport',
            'DefaultPdfPoExport',
            'ReceivingTagsExport',
            'Unfi7DigitCsvExport',
            'WfcPoExport.php',
        );
        foreach ($exp as $e) {
            if (!class_exists($e)) {
                include(__DIR__ . '/../../fannie/purchasing/exporters/' . $e . '.php');
            }
            $obj = new $e();
            ob_start();
            $e->export_order(1);
            ob_end_clean();
        }
    }
}

