<?php

/**
 * @backupGlobals disabled
 */
class LocalStorageTest extends PHPUnit_Framework_TestCase
{
    public function testAll()
    {
        $defaults = array(
            'COREPOS\\pos\\lib\\LocalStorage\\SessionStorage',
            'COREPOS\\pos\\lib\\LocalStorage\\UnitTestStorage',
            'COREPOS\\pos\\lib\\LocalStorage\\WrappedStorage',
        );

        foreach ($defaults as $class) {
            $obj = new $class();
            $this->assertInstanceOf('COREPOS\\pos\\lib\\LocalStorage\\LocalStorage',$obj);

            $unk = $obj->get('unknownKey');
            $this->assertInternalType('string',$unk);
            $this->assertEquals('',$unk, 'Unknown key failed for ' . $class);

            $obj->set('testKey','testVal');
            $get = $obj->get('testKey');
            $this->assertInternalType('string',$get, 'String test failed for ' . $class);
            $this->assertEquals('testVal',$get, 'String equality failed for ' . $class);

            $obj->set('testInt',1);
            $get = $obj->get('testInt');
            $this->assertInternalType('integer',$get, 'Int test failed for ' . $class);
            $this->assertEquals(1, $get, 'Int equality failed for ' . $class);

            $obj->set('testBool',False);
            $get = $obj->get('testBool');
            $this->assertInternalType('boolean',$get, 'Bool test failed for ' . $class);
            $this->assertEquals(false, $get, 'Bool equality failed for ' . $class);

            $obj->set('testArray',array(1,2));
            $get = $obj->get('testArray');
            $this->assertInternalType('array',$get, 'Array test failed for ' . $class);
            $this->assertEquals(array(1, 2), $get, 'Array equality failed for ' . $class);

            foreach ($obj as $key => $val) {
                // is iterable
            }
        }

    }

    public function testCoreLocal()
    {
        CoreLocal::refresh();
        CoreLocal::migrateSettings();
        $json = CoreLocal::convertIniPhpToJson();
        $this->assertInternalType('array', json_decode($json, true));
    }
}
