<?php
include(dirname(__FILE__).'/../parser-class-lib/PreParser.php');
include(dirname(__FILE__).'/../parser-class-lib/Parser.php');

/**
 * @backupGlobals disabled
 */
class ParsersTest extends PHPUnit_Framework_TestCase
{
	/**
	  Check methods for getting available PreParser and Parser modules
	*/
	public function testStatics()
    {
		$chain = PreParser::get_preparse_chain();
		$this->assertInternalType('array',$chain);
		$this->assertNotEmpty($chain);
		foreach($chain as $class){
			$instance = new $class();
			$this->assertInstanceOf('PreParser',$instance);
		}

		$chain = Parser::get_parse_chain();
		$this->assertInternalType('array',$chain);
		$this->assertNotEmpty($chain);
		foreach($chain as $class){
			$instance = new $class();
			$this->assertInstanceOf('Parser',$instance);
		}
	}

	public function testPreParsers()
    {
		/* set any needed session variables */
		CoreLocal::set('runningTotal',1.99);
		CoreLocal::set('mfcoupon',0);
		CoreLocal::set('itemPD',0);
		CoreLocal::set('multiple',0);
		CoreLocal::set('quantity',0);
		CoreLocal::set('refund',0);
		CoreLocal::set('toggletax',0);
		CoreLocal::set('togglefoodstamp',0);
		CoreLocal::set('toggleDiscountable',0);
		CoreLocal::set('nd',0);
	
		/* inputs and expected outputs */
		$input_output = array(
			'MANUALCC'	=> '199CC',
			'5DI123'	=> '123',
			'7PD123'	=> '123',
			'2*4011'	=> '4011',
			'3*100DP10'	=> '100DP10',
			'text*blah'	=> 'text*blah',
			'RF123'		=> '123',
			'123RF'		=> '123',
			'RF3*100DP10'	=> '100DP10',
			'FN123'		=> '123',
			'DN123'		=> '123',
			'1TN123'	=> '123',
			'invalid'	=> 'invalid'
		);

		$chain = PreParser::get_preparse_chain();
		foreach($input_output as $input => $output){
			foreach($chain as $class){
				$obj = new $class();
				$chk = $obj->check($input);
				$this->assertInternalType('boolean',$chk);
				if ($chk){
					$input = $obj->parse($input);
				}
			}
			$this->assertEquals($output, $input);
		}

		/* verify correct session values */
		$this->assertEquals(7, CoreLocal::get('itemPD'));
		$this->assertEquals(1, CoreLocal::get('multiple'));
		$this->assertEquals(3, CoreLocal::get('quantity'));
		$this->assertEquals(1, CoreLocal::get('refund'));
		$this->assertEquals(1, CoreLocal::get('toggletax'));
		$this->assertEquals(1, CoreLocal::get('togglefoodstamp'));
		$this->assertEquals(1, CoreLocal::get('toggleDiscountable'));
	}

	function testParsers()
    {
		/* inputs and expected outputs */
		$input_output = array(
        'WillNotMatchAnythingEver' => array(),
		);

		$chain = Parser::get_parse_chain();
		foreach($input_output as $input => $output){
            $actual = $output;
			foreach($chain as $class){
				$obj = new $class();
				$chk = $obj->check($input);
				$this->assertInternalType('boolean',$chk);
				if ($chk){
					$actual = $obj->parse($input);
                    break;
				}
			}
            $this->assertEquals($output, $actual);
		}
	}

    function testItemsEntry()
    {
		CoreLocal::set('mfcoupon',0);
		CoreLocal::set('itemPD',0);
		CoreLocal::set('multiple',0);
		CoreLocal::set('quantity',0);
		CoreLocal::set('refund',0);
		CoreLocal::set('toggletax',0);
		CoreLocal::set('togglefoodstamp',0);
		CoreLocal::set('toggleDiscountable',0);
		CoreLocal::set('nd',0);

        // test regular price item
        lttLib::clear();
        $u = new UPC();
        $this->assertEquals(true, $u->check('666'));
        $json = $u->parse('666');
        $this->assertInternalType('array', $json);
        $record = lttLib::genericRecord();
        $record['upc'] = '0000000000666';
        $record['description'] = 'EXTRA BAG';
        $record['trans_type'] = 'I';
        $record['trans_subtype'] = 'NA';
        $record['department'] = 13;
        $record['tax'] = 1;
        $record['quantity'] = 1;
        $record['unitPrice'] = 0.05;
        $record['total'] = 0.05;
        $record['regPrice'] = 0.05;
        $record['ItemQtty'] = 1;
        lttLib::verifyRecord(1, $record, $this);
        CoreLocal::set('currentid', 1);
        $v = new Void();
        $this->assertEquals(true, $v->check('VD'));
        $json = $v->parse('VD');
        $this->assertInternalType('array', $json);
        $record['total'] *= -1;
        $record['quantity'] *= -1;
        $record['ItemQtty'] *= -1;
        $record['voided'] = 1;
        $record['trans_status'] = 'V';
        lttLib::verifyRecord(2, $record, $this);

        // test quantity multiplier
        lttLib::clear();
        CoreLocal::set('quantity', 2);
        CoreLocal::set('multiple', 1);
        $u = new UPC();
        $this->assertEquals(true, $u->check('666'));
        $json = $u->parse('666');
        $this->assertInternalType('array', $json);
        $record = lttLib::genericRecord();
        $record['upc'] = '0000000000666';
        $record['description'] = 'EXTRA BAG';
        $record['trans_type'] = 'I';
        $record['trans_subtype'] = 'NA';
        $record['department'] = 13;
        $record['tax'] = 1;
        $record['quantity'] = 2;
        $record['unitPrice'] = 0.05;
        $record['total'] = 0.10;
        $record['regPrice'] = 0.05;
        $record['ItemQtty'] = 2;
        lttLib::verifyRecord(1, $record, $this);
        CoreLocal::set('currentid', 1);
        $v = new Void();
        $this->assertEquals(true, $v->check('VD'));
        $json = $v->parse('VD');
        $this->assertInternalType('array', $json);
        $record['total'] *= -1;
        $record['quantity'] *= -1;
        $record['ItemQtty'] *= -1;
        $record['voided'] = 1;
        $record['trans_status'] = 'V';
        lttLib::verifyRecord(2, $record, $this);

        // test refund
        lttLib::clear();
        CoreLocal::set('quantity', 0);
        CoreLocal::set('multiple', 0);
        CoreLocal::set('refund', 1);
        CoreLocal::set('refundComment', 'TEST REFUND');
        $u = new UPC();
        $this->assertEquals(true, $u->check('666'));
        $json = $u->parse('666');
        $this->assertInternalType('array', $json);
        $record = lttLib::genericRecord();
        $record['upc'] = '0000000000666';
        $record['description'] = 'EXTRA BAG';
        $record['trans_type'] = 'I';
        $record['trans_subtype'] = 'NA';
        $record['trans_status'] = 'R';
        $record['department'] = 13;
        $record['tax'] = 1;
        $record['quantity'] = -1;
        $record['unitPrice'] = 0.05;
        $record['total'] = -0.05;
        $record['regPrice'] = 0.05;
        $record['ItemQtty'] = 1;
        lttLib::verifyRecord(1, $record, $this);
        CoreLocal::set('currentid', 1);
        $v = new Void();
        $this->assertEquals(true, $v->check('VD'));
        $json = $v->parse('VD');
        $this->assertInternalType('array', $json);
        $record['total'] *= -1;
        $record['quantity'] *= -1;
        $record['ItemQtty'] *= -1;
        $record['voided'] = 1;
        $record['trans_status'] = 'V';
        lttLib::verifyRecord(2, $record, $this);

        // test sale item
        lttLib::clear();
        CoreLocal::set('refund', 0);
        CoreLocal::set('refundComment', '');
        $u = new UPC();
        $this->assertEquals(true, $u->check('4627'));
        $json = $u->parse('4627');
        $this->assertInternalType('array', $json);
        $record = lttLib::genericRecord();
        $record['upc'] = '0000000004627';
        $record['description'] = 'PKALE';
        $record['trans_type'] = 'I';
        $record['trans_subtype'] = 'NA';
        $record['department'] = 513;
        $record['foodstamp'] = 1;
        $record['discounttype'] = 1;
        $record['discountable'] = 1;
        $record['quantity'] = 1;
        $record['cost'] = 1.30;
        $record['unitPrice'] = 1.99;
        $record['total'] = 1.99;
        $record['regPrice'] = 2.29;
        $record['discount'] = 0.30;
        $record['ItemQtty'] = 1;
        $record['mixMatch'] = '943';
        lttLib::verifyRecord(1, $record, $this);
        $drecord = lttLib::genericRecord();
        $drecord['description'] = '** YOU SAVED $0.30 **';
        $drecord['trans_type'] = 'I';
        $drecord['department'] = 513;
        $drecord['trans_status'] = 'D';
        $drecord['voided'] = 2;
        lttLib::verifyRecord(2, $drecord, $this);
        CoreLocal::set('currentid', 1);
        $v = new Void();
        $this->assertEquals(true, $v->check('VD'));
        $json = $v->parse('VD');
        $this->assertInternalType('array', $json);
        $record['total'] *= -1;
        $record['quantity'] *= -1;
        $record['ItemQtty'] *= -1;
        $record['voided'] = 1;
        $record['trans_status'] = 'V';
        $record['discount'] *= -1;
        lttLib::verifyRecord(3, $record, $this);

        // test member sale
        lttLib::clear();
        CoreLocal::set('isMember', 1);
        $u = new UPC();
        $this->assertEquals(true, $u->check('0003049488122'));
        $json = $u->parse('0003049488122');
        $this->assertInternalType('array', $json);
        $record = lttLib::genericRecord();
        $record['upc'] = '0003049488122';
        $record['description'] = 'MINERAL WATER';
        $record['trans_type'] = 'I';
        $record['trans_subtype'] = 'NA';
        $record['department'] = 188;
        $record['foodstamp'] = 1;
        $record['discounttype'] = 2;
        $record['discountable'] = 1;
        $record['quantity'] = 1;
        $record['cost'] = 2.06;
        $record['unitPrice'] = 2.49;
        $record['total'] = 2.49;
        $record['regPrice'] = 3.15;
        $record['memDiscount'] = 0.66;
        $record['ItemQtty'] = 1;
        lttLib::verifyRecord(1, $record, $this);
        $drecord = lttLib::genericRecord();
        $drecord['description'] = '** YOU SAVED $0.66 **';
        $drecord['trans_type'] = 'I';
        $drecord['department'] = 188;
        $drecord['trans_status'] = 'D';
        $drecord['voided'] = 2;
        lttLib::verifyRecord(2, $drecord, $this);
        CoreLocal::set('currentid', 1);
        $v = new Void();
        $this->assertEquals(true, $v->check('VD'));
        $json = $v->parse('VD');
        $this->assertInternalType('array', $json);
        $record['total'] *= -1;
        $record['quantity'] *= -1;
        $record['ItemQtty'] *= -1;
        $record['voided'] = 1;
        $record['trans_status'] = 'V';
        $record['memDiscount'] *= -1;
        lttLib::verifyRecord(3, $record, $this);

        // test member sale as non-member
        lttLib::clear();
        CoreLocal::set('isMember', 0);
        $u = new UPC();
        $this->assertEquals(true, $u->check('0003049488122'));
        $json = $u->parse('0003049488122');
        $this->assertInternalType('array', $json);
        $record = lttLib::genericRecord();
        $record['upc'] = '0003049488122';
        $record['description'] = 'MINERAL WATER';
        $record['trans_type'] = 'I';
        $record['trans_subtype'] = 'NA';
        $record['department'] = 188;
        $record['foodstamp'] = 1;
        $record['discounttype'] = 2;
        $record['discountable'] = 1;
        $record['quantity'] = 1;
        $record['cost'] = 2.06;
        $record['unitPrice'] = 3.15;
        $record['total'] = 3.15;
        $record['regPrice'] = 3.15;
        $record['memDiscount'] = 0.66;
        $record['ItemQtty'] = 1;
        lttLib::verifyRecord(1, $record, $this);
        CoreLocal::set('currentid', 1);
        $v = new Void();
        $this->assertEquals(true, $v->check('VD'));
        $json = $v->parse('VD');
        $this->assertInternalType('array', $json);
        $record['total'] *= -1;
        $record['quantity'] *= -1;
        $record['ItemQtty'] *= -1;
        $record['voided'] = 1;
        $record['trans_status'] = 'V';
        $record['memDiscount'] *= -1;
        lttLib::verifyRecord(2, $record, $this);
    }

    function testOpenRings()
    {
        lttLib::clear();
        $d = new DeptKey();
        $this->assertEquals(true, $d->check('100DP10'));
        $json = $d->parse('100DP10');
        $this->assertInternalType('array', $json);
        $record = lttLib::genericRecord();
        $record['upc'] = '1DP1';
        $record['description'] = 'BBAKING';
        $record['trans_type'] = 'D';
        $record['department'] = 1;
        $record['quantity'] = 1;
        $record['foodstamp'] = 1;
        $record['unitPrice'] = 1.00;
        $record['total'] = 1.00;
        $record['regPrice'] = 1.00;
        $record['ItemQtty'] = 1;
        lttLib::verifyRecord(1, $record, $this);
        CoreLocal::set('currentid', 1);
        $v = new Void();
        $this->assertEquals(true, $v->check('VD'));
        $json = $v->parse('VD');
        $this->assertInternalType('array', $json);
        $record['total'] *= -1;
        $record['quantity'] *= -1;
        $record['ItemQtty'] *= -1;
        $record['voided'] = 1;
        $record['trans_status'] = 'V';
        lttLib::verifyRecord(2, $record, $this);

        lttLib::clear();
        CoreLocal::set('refund', 1);
        CoreLocal::set('refundComment', 'TEST REFUND');
        $d = new DeptKey();
        $this->assertEquals(true, $d->check('100DP10'));
        $json = $d->parse('100DP10');
        $this->assertInternalType('array', $json);
        $record = lttLib::genericRecord();
        $record['upc'] = '1DP1';
        $record['description'] = 'BBAKING';
        $record['trans_type'] = 'D';
        $record['trans_status'] = 'R';
        $record['department'] = 1;
        $record['quantity'] = -1;
        $record['foodstamp'] = 1;
        $record['unitPrice'] = 1.00;
        $record['total'] = -1.00;
        $record['regPrice'] = 1.00;
        $record['ItemQtty'] = 1;
        lttLib::verifyRecord(1, $record, $this);
        CoreLocal::set('currentid', 1);
        $v = new Void();
        $this->assertEquals(true, $v->check('VD'));
        $json = $v->parse('VD');
        $this->assertInternalType('array', $json);
        $record['total'] *= -1;
        $record['quantity'] *= -1;
        $record['ItemQtty'] *= -1;
        $record['voided'] = 1;
        $record['trans_status'] = 'V';
        lttLib::verifyRecord(2, $record, $this);
    }
}
