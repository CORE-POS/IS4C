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

    public function testBridge()
    {
        if (!class_exists('SoPoBridge')) {
            include(__DIR__ . '/../../fannie/ordering/SoPoBridge.php');
        }
        $conf = FannieConfig::factory();
        $bridge = new SoPoBridge(FannieDB::get($conf->get('OP_DB')), $conf);
        
        $this->assertEquals(false, $bridge->canPurchaseOrder(999, 9));
        $this->assertEquals(false, $bridge->addItemToPurchaseOrder(999, 9, 1));
        $this->assertEquals(false, $bridge->findPurchaseOrder(999, 9, 1));
        $this->assertEquals(false, $bridge->removeItemFromPurchaseOrder(999, 9, 1));
        $bridge->markAsPlaced(999, 9);
    }

    public function testExporters()
    {
        $exp = array(
            'ChefTecExport',
            'DefaultCsvPoExport',
            'DefaultPdfPoExport',
            'ReceivingTagsExport',
            'Unfi7DigitCsvExport',
            'WfcPoExport',
            'WfcPdfExport',
        );
        foreach ($exp as $e) {
            if ($e == 'DefaultPdfPoExport' || $e == 'WfcPdfExport') { // FPDF die()s because it can't ouput
                continue;
            }
            if (!class_exists($e)) {
                include(__DIR__ . '/../../fannie/purchasing/exporters/' . $e . '.php');
            }
            $obj = new $e();
            ob_start();
            $obj->export_order(1);
            ob_end_clean();
        }

        foreach ($exp as $e) {
            if (!class_exists($e)) {
                include(__DIR__ . '/../../fannie/purchasing/exporters/' . $e . '.php');
            }
            $obj = new $e();
            $str = $obj->exportString(1);
        }
    }
}

