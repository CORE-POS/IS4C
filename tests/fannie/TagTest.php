<?php

/**
 * @backupGlobals disabled
 */
class TagTest extends PHPUnit_Framework_TestCase
{
    public function testTagPDFs()
    {
        $dbc = FannieDB::forceReconnect(FannieConfig::config('OP_DB'));
        include(dirname(__FILE__) . '/../../fannie/admin/labels/FpdfWithBarcode.php');
        $obj = new FpdfWithBarcode();
        $obj->EAN13('123456789012', 1, 1);
        $obj->EAN13('1234567890128', 1, 1);
        $obj->UPC_A('1234567890', 1, 1);

        if (!function_exists('scan_layouts')) {
            include(dirname(__FILE__) . '/../../fannie/admin/labels/scan_layouts.php');
        }
        $layouts = scan_layouts();

        $sample = array(
            array(
                'normal_price' => 1.99,
                'description' => 'foo',
                'brand' => 'bar',
                'units' => 1,
                'size' => 1,
                'sku' => '12345',
                'pricePerUnit' => 1.99,
                'upc' => '0000000000000',
                'vendor' => 'baz',
                'scale' => 0,
                'numflag' => 0,
            ),
        );

        foreach ($layouts as $layout) {
            $func = str_replace(' ', '_', $layout);
            if (!function_exists($func)) {
                include(dirname(__FILE__) . '/../../fannie/admin/labels/pdf_layouts/' . $func . '.php');
            }
            $this->assertEquals(true, class_exists($func . '_PDF', false), 'Missing class for ' . $func);
            ob_start();
            $func($sample);
            ob_get_clean();
        }
    }

    public function testSigns()
    {
        $sample = array(
            array(
                'normal_price' => 1.99,
                'description' => 'foo',
                'posDescription' => 'bar',
                'brand' => 'bar',
                'units' => 1,
                'size' => 1,
                'sku' => '12345',
                'pricePerUnit' => 1.99,
                'upc' => '0000000000000',
                'vendor' => 'baz',
                'scale' => 0,
                'numflag' => 0,
                'startDate' => '2000-01-01',
                'endDate' => '2000-01-01',
                'nonSalePrice' => 2.99,
                'signMultiplier' => 1,
                'signCount' => 1,
                'originName' => '',
                'originShortName' => '',
            ),
        );
        $mods = FannieAPI::listModules('\COREPOS\Fannie\API\item\FannieSignage');
        COREPOS\Fannie\API\item\signage\LegacyWrapper::setWrapped('Fannie_Standard');
        foreach ($mods as $class) {
            $obj = new $class($sample, 'provided');
            ob_start();
            $obj->drawPDF();
            $this->assertNotEquals(0, strlen(ob_get_clean()));
        }
    }
}

