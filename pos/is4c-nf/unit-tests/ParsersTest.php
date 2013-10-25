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
	public function testStatics(){
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

	public function testPreParsers(){
		global $CORE_LOCAL;

		/* set any needed session variables */
		$CORE_LOCAL->set('runningTotal',1.99);
		$CORE_LOCAL->set('mfcoupon',0);
		$CORE_LOCAL->set('itemPD',0);
		$CORE_LOCAL->set('multiple',0);
		$CORE_LOCAL->set('quantity',0);
		$CORE_LOCAL->set('refund',0);
		$CORE_LOCAL->set('toggletax',0);
		$CORE_LOCAL->set('togglefoodstamp',0);
		$CORE_LOCAL->set('toggleDiscountable',0);
		$CORE_LOCAL->set('nd',0);
	
		/* inputs and expected outputs */
		$input_output = array(
			'CC'		=> 'QM1',
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
		$this->assertEquals(7, $CORE_LOCAL->get('itemPD'));
		$this->assertEquals(1, $CORE_LOCAL->get('multiple'));
		$this->assertEquals(3, $CORE_LOCAL->get('quantity'));
		$this->assertEquals(1, $CORE_LOCAL->get('refund'));
		$this->assertEquals(1, $CORE_LOCAL->get('toggletax'));
		$this->assertEquals(1, $CORE_LOCAL->get('togglefoodstamp'));
		$this->assertEquals(1, $CORE_LOCAL->get('toggleDiscountable'));
	}

	function testParsers(){
		global $CORE_LOCAL;

		/* inputs and expected outputs */
		$input_output = array(
		);

		$chain = Parser::get_parse_chain();
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
	}
}
