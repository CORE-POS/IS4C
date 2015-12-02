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

        $val = new COREPOS\common\mvc\ValueContainer();
        $val->foo = 'bar';
        $this->assertEquals('bar', FormLib::extract($val, 'foo', 'baz'));
        $this->assertEquals('baz', FormLib::extract($val, 'notfoo', 'baz'));

        $this->assertEquals(false, FormLib::fieldJSONtoJavascript(5));
        $this->assertNotEquals(0, strlen(FormLib::fieldJSONtoJavascript('{"foo":"bar"}')));
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

        $signs = new \COREPOS\Fannie\API\item\FannieSignage(array(), 'shelftags', 1);
        $signs->setDB($dbc);
        $this->assertInternalType('array', $signs->loadItems());

        $signs = new \COREPOS\Fannie\API\item\FannieSignage(array(), 'batchbarcodes', 1);
        $signs->setDB($dbc);
        $this->assertInternalType('array', $signs->loadItems());

        $signs = new \COREPOS\Fannie\API\item\FannieSignage(array(), 'batch', 1);
        $signs->setDB($dbc);
        $this->assertInternalType('array', $signs->loadItems());

        foreach (range(0, 3) as $i) {
            $signs = new \COREPOS\Fannie\API\item\FannieSignage(array('0000000000111'), '', $i);
            $signs->setDB($dbc);
            $this->assertInternalType('array', $signs->loadItems());
        }
    }

    public function testConfig()
    {
        $config = FannieConfig::factory();
        $this->assertEquals($config->get('OP_DB'), $config->get('FANNIE_OP_DB'));
        $this->assertEquals($config->get('OP_DB'), FannieConfig::config('OP_DB'));
        $this->assertEquals($config->get('OP_DB'), $config->OP_DB);
        $json = $config->toJSON();
        $this->assertNotEquals(null, json_decode($json));
    }

    public function testTask()
    {
        $logger = new FannieLogger();
        $task = new FannieTask();
        $task->setThreshold(99);
        $task->setLogger($logger);
        $task->setConfig(FannieConfig::factory());
        $task->setOptions(null);
        $task->setArguments(null);
        $task->run();
        $task->cronMsg('foo');
        $argv = array('-v', '--verbose', '-h', '1', '--host=1', 'something', 'else');
        $opt = $task->lazyGetOpt($argv);
        $expect = array(
            'options' => array(
                'v' => true,
                'verbose' => true,
                'h' => '1',
                'host' => '1',
            ),
            'arguments' => array(
                'something',
                'else',
            ),
        );
        $this->assertEquals($expect, $opt);
    }

    public function testDTrans()
    {
        $out = DTrans::parameterize(array('emp_no'=>1,'trans_no'=>2));
        $expect = array(
            'columnString' => 'emp_no,trans_no',
            'valueString' => '?,?',
            'arguments' => array(1,2),
        );
        $this->assertEquals($expect, $out);

        $this->assertNotEquals(0, strlen(DTrans::isNotTesting('d')));
        $this->assertNotEquals(0, strlen(DTrans::isTesting('d')));
        $this->assertNotEquals(0, strlen(DTrans::isCanceled('d')));
        $this->assertNotEquals(0, strlen(DTrans::isValid('d')));
        $this->assertNotEquals(0, strlen(DTrans::isStoreID(0, 'd')));
        $this->assertNotEquals(0, strlen(DTrans::isStoreID(1, 'd')));
        $this->assertNotEquals(0, strlen(DTrans::sumQuantity('d')));
        $this->assertNotEquals(0, strlen(DTrans::joinProducts('d','p','left')));
        $this->assertNotEquals(0, strlen(DTrans::joinProducts('d','p','right')));
        $this->assertNotEquals(0, strlen(DTrans::joinProducts('d','p','inner')));
        $this->assertNotEquals(0, strlen(DTrans::joinDepartments('d','p')));
        $this->assertNotEquals(0, strlen(DTrans::joinCustomerAccount('d','p')));
        $this->assertNotEquals(0, strlen(DTrans::joinTenders('d','p')));
    }

    public function testDispatch()
    {
        $logger = new FannieLogger();
        FannieDispatch::setLogger($logger);
        FannieDispatch::errorHandler(1, 'foo');
        FannieDispatch::exceptionHandler(new Exception('foo'));
        FannieDispatch::catchFatal();
    }

    public function testMargin()
    {
        $this->assertEquals(108, COREPOS\Fannie\API\item\Margin::adjustedCost(100, 0.10, 0.20));
        $this->assertEquals(0, COREPOS\Fannie\API\item\Margin::toMargin(0, 0));
        $this->assertEquals(50, COREPOS\Fannie\API\item\Margin::toMargin(5, 10, array(100, 0)));
        $this->assertEquals('(0)', COREPOS\Fannie\API\item\Margin::toMarginSQL(0, 0));
        $this->assertEquals(1, COREPOS\Fannie\API\item\Margin::toPrice(1, 1));
        $this->assertEquals(10, COREPOS\Fannie\API\item\Margin::toPrice(5, 0.5));
        $this->assertEquals('(foo)', COREPOS\Fannie\API\item\Margin::toPriceSQL('foo', 1));
    }

    public function testBarcode()
    {
        $this->assertEquals('0123456789012', BarcodeLib::trimCheckDigit('1234567890128'));
        $this->assertEquals('0012345678901', BarcodeLib::trimCheckDigit('123456789012'));
        $this->assertEquals('0000000004011', BarcodeLib::trimCheckDigit('4011'));
    }

}

