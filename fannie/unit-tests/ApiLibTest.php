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
        $keys = array_keys($_REQUEST);
        foreach ($keys as $k) {
            unset($_REQUEST[$k]);
        }

        $val = FormLib::get('someKey');
        $this->assertEquals('', $val);

        $val = FormLib::get('someKey', 'someVal');
        $this->assertEquals('someVal', $val);

        $_REQUEST['someKey'] = 'otherVal';
        $val = FormLib::get('otherVal', 'someVal');
        $this->assertEquals('someVal', $val);

        $val = FormLib::getDate('someKey');
        $this->assertEquals('', $val);

        $val = FormLib::getDate('someKey', '2000-01-01');
        $this->assertEquals('2000-01-01', $val);

        $_REQUEST['someKey'] = '2000-02-02';
        $val = FormLib::getDate('someKey', '2000-01-01');
        $this->assertEquals('2000-02-02', $val);

        $val = FormLib::getDate('someKey', '1/1/2000', 'n/j/Y');
        $this->assertEquals('2/2/2000', $val);
    }

}

