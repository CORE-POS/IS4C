<?php

/**
 * @backupGlobals disabled
 */
class KickersTest extends PHPUnit_Framework_TestCase
{
	public function testAll(){
		global $CORE_LOCAL;

		$defaults = array(
			'Kicker'
		);

		$all = AutoLoader::ListModules('Kicker',True);
		foreach($defaults as $d){
			$this->assertContains($d, $all);
		}

		foreach($all as $class){
			$obj = new $class();
			$this->assertInstanceOf('Kicker',$obj);

			$test1 = $obj->kickOnSignIn();
			$test2 = $obj->kickOnSignOut();
			$test3 = $obj->doKick('9999-99-1');
			$this->assertInternalType('boolean',$test1);
			$this->assertInternalType('boolean',$test2);
			$this->assertInternalType('boolean',$test3);
		}

	}
}
