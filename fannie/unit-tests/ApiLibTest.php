<?php

/**
 * @backupGlobals disabled
 */
class ApiLibTest extends PHPUnit_Framework_TestCase
{
    public function testBarcodeLib()
    {
        $pad = BarcodeLib::padUPC('1');
        $this->assertEquals('0000000000001', $pad, 'BarcodeLib::padUPC failed');

        $checks = array(
            '12345678901' => '2',
            '123456789012' => '8',
            '1234567890123' => '1',
        );

        foreach ($checks as $barcode => $check_digit) {
            $calc = BarcodeLib::getCheckDigit($barcode);
            $this->assertEquals($check_digit, $calc, 'Failed check digit calculation for ' . $barcode);

            $with_check = $barcode . $check_digit;
            $without_check = $barcode . (($check_digit+1) % 10);
            $this->assertEquals(true, BarcodeLib::verifyCheckdigit($with_check));
            $this->assertEquals(false, BarcodeLib::verifyCheckdigit($without_check));
        }

        $upc_a = BarcodeLib::UPCACheckDigit('12345678901');
        $this->assertEquals('123456789012', $upc_a, 'Failed UPC A check digit calculation');

        $ean_13 = BarcodeLib::EAN13CheckDigit('123456789012');
        $this->assertEquals('1234567890128', $ean_13, 'Failed EAN 13 check digit calculation');

        $norm = BarcodeLib::normalize13('12345678901');
        $this->assertEquals('0123456789012', $norm, 'Failed normalizing UPC-A to 13 digits');

        $norm = BarcodeLib::normalize13('123456789012');
        $this->assertEquals('1234567890128', $norm, 'Failed normalizing EAN-13 to 13 digits');
    }

    public function testFormLib()
    {
        $val = FormLib::get('someKey');
        $this->assertEquals('', $val);

        $val = FormLib::get('someKey', 'someVal');
        $this->assertEquals('someVal', $val);

        $val = FormLib::get('otherVal', 'someVal');
        $this->assertEquals('someVal', $val);

        $val = FormLib::getDate('someKey');
        $this->assertEquals('', $val);

        $val = FormLib::getDate('someKey', '2000-01-01');
        $this->assertEquals('2000-01-01', $val);

        $val = FormLib::getDate('someKey', '1/1/2000', 'n/j/Y');
        $this->assertEquals('1/1/2000', $val);
    }

    public function testStats()
    {
        $this->assertEquals(0, \COREPOS\Fannie\API\lib\Stats::percentGrowth(50, 0));
        $this->assertEquals(100.0, \COREPOS\Fannie\API\lib\Stats::percentGrowth(50, 25));
        
        $points = array(
            array(1, 1),
            array(2, 2),
            array(3, 3),
            array(4, 4),
            array(5, 5),
        );
        $res = \COREPOS\Fannie\API\lib\Stats::removeOutliers($points);
        $this->assertEquals(array(array(2,2), array(3,3), array(4,4)), $res);
        $this->assertEquals(array(), \COREPOS\Fannie\API\lib\Stats::removeOutliers(array()));

        $lsq = \COREPOS\Fannie\API\lib\Stats::leastSquare($points);
        $this->assertEquals(array('slope'=>1, 'y_intercept'=>0), $lsq);

        $exp = \COREPOS\Fannie\API\lib\Stats::exponentialFit($points);
        $this->assertInternalType('object', $exp);
    }

    public function testFannieSignage()
    {
        $dbc = FannieDB::get(FannieConfig::config('OP_DB'));
        $dbc->throwOnFailure(true);

        $signs = new \COREPOS\Fannie\item\FannieSignage(array(), 'shelftags', 1);
        $signs->setDB($dbc);
        $this->assertInternalType('array', $signs->loadItems());

        $signs = new \COREPOS\Fannie\item\FannieSignage(array(), 'batchbarcodes', 1);
        $signs->setDB($dbc);
        $this->assertInternalType('array', $signs->loadItems());

        $signs = new \COREPOS\Fannie\item\FannieSignage(array(), 'batch', 1);
        $signs->setDB($dbc);
        $this->assertInternalType('array', $signs->loadItems());

        foreach (range(0, 3) as $i) {
            $signs = new \COREPOS\Fannie\item\FannieSignage(array('0000000000111'), '', $i);
            $signs->setDB($dbc);
            $this->assertInternalType('array', $signs->loadItems());
        }
    }

}

