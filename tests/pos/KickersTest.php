<?php

/**
 * @backupGlobals disabled
 */
class KickersTest extends PHPUnit_Framework_TestCase
{
    public function testAll()
    {
        $defaults = array(
            'COREPOS\\pos\\lib\\Kickers\\Kicker',
            'COREPOS\\pos\\lib\\Kickers\\NoKick',
        );

        $all = AutoLoader::ListModules('COREPOS\\pos\\lib\\Kickers\\Kicker',True);
        foreach($defaults as $d){
            $this->assertContains($d, $all);
        }

        foreach($all as $class){
            $obj = new $class();
            $this->assertInstanceOf('COREPOS\\pos\\lib\\Kickers\\Kicker',$obj);

            $test1 = $obj->kickOnSignIn();
            $test2 = $obj->kickOnSignOut();
            $test3 = $obj->doKick('9999-99-1');
            $this->assertInternalType('boolean',$test1);
            $this->assertInternalType('boolean',$test2);
            $this->assertInternalType('boolean',$test3);
        }
    }

    public function testDefault()
    {
        $k = new COREPOS\pos\lib\Kickers\Kicker();
        CoreLocal::set('training', 1);
        $this->assertEquals(false, $k->doKick('1-1-1'));
        $this->assertEquals(false, $k->kickOnSignIn());
        $this->assertEquals(false, $k->kickOnSignOut());
        CoreLocal::set('training', 0);
        $this->assertEquals(true, $k->kickOnSignIn());
        $this->assertEquals(true, $k->kickOnSignOut());
        
        $k = new COREPOS\pos\lib\Kickers\NoKick();
        $this->assertEquals(false, $k->doKick('1-1-1'));
        $this->assertEquals(false, $k->kickOnSignIn());
        $this->assertEquals(false, $k->kickOnSignOut());
    }
}
