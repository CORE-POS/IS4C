<?php

/**
 * @backupGlobals disabled
 */
class LocalStorageTest extends PHPUnit_Framework_TestCase
{
	public function testAll(){
		global $CORE_LOCAL;

		$defaults = array(
			'SessionStorage',
			'UnitTestStorage'
		);

		if (function_exists('sqlite_open'))
			$defaults[] = 'SQLiteStorage';

		foreach($defaults as $class){
			$obj = new $class();
			$this->assertInstanceOf('LocalStorage',$obj);

			$unk = $obj->get('unknownKey');
			$this->assertInternalType('string',$unk);
			$this->assertEquals('',$unk);

			$obj->set('testKey','testVal');
			$get = $obj->get('testKey');
			$this->assertInternalType('string',$get);
			$this->assertEquals('testVal',$get);

			$obj->set('testInt',1);
			$get = $obj->get('testInt');
			$this->assertInternalType('integer',$get);
			$this->assertEquals(1,$get);

			$obj->set('testBool',False);
			$get = $obj->get('testBool');
			$this->assertInternalType('boolean',$get);
			$this->assertEquals(False,$get);

			$obj->set('testArray',array(1,2));
			$get = $obj->get('testArray');
			$this->assertInternalType('array',$get);
			$this->assertEquals(array(1,2),$get);

			$obj->set('imm','imm',True);
			$get = $obj->get('imm');
			$this->assertInternalType('string',$get);
			$this->assertEquals('imm',$get);

			$is = $obj->isImmutable('imm');
			$isNot = $obj->isImmutable('testArray');
			$this->assertInternalType('boolean',$is);
			$this->assertInternalType('boolean',$isNot);
			$this->assertEquals(True,$is);
			$this->assertEquals(False,$isNot);
		}

	}
}
