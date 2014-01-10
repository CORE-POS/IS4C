<?php

/**
 * @backupGlobals disabled
 */
class FooterBoxesTest extends PHPUnit_Framework_TestCase
{
	public function testAll(){
		global $CORE_LOCAL;

		$defaults = array(
			'FooterBox',
			'EveryoneSales',
			'MemSales',
			'MultiTotal',
			'SavedOrCouldHave',
			'TransPercentDiscount'
		);

		$all = AutoLoader::ListModules('FooterBox',True);
		foreach($defaults as $d){
			$this->assertContains($d, $all);
		}

		foreach($all as $class){
			$obj = new $class();
			$this->assertInstanceOf('FooterBox',$obj);

			$this->assertObjectHasAttribute('header_css',$obj);
			$this->assertObjectHasAttribute('display_css',$obj);

			$header = $obj->header_content();
			$this->assertInternalType('string',$header);
			$display = $obj->display_content();
			$this->assertInternalType('string',$display);
		}

	}
}
