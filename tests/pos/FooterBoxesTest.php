<?php

use COREPOS\pos\lib\FooterBoxes\FooterBox;
use COREPOS\pos\lib\FooterBoxes\TransPercentDiscount;
use COREPOS\pos\lib\FooterBoxes\SavedOrCouldHave;
use COREPOS\pos\lib\FooterBoxes\PatronagePts;
use COREPOS\pos\lib\FooterBoxes\MemSales;
use COREPOS\pos\lib\FooterBoxes\MultiTotal;
use COREPOS\pos\lib\LocalStorage\WrappedStorage;

/**
 * @backupGlobals disabled
 */
class FooterBoxesTest extends PHPUnit_Framework_TestCase
{
    public function testAll()
    {
        $defaults = array(
            'COREPOS\\pos\\lib\\FooterBoxes\\FooterBox',
            'COREPOS\\pos\\lib\\FooterBoxes\\EveryoneSales',
            'COREPOS\\pos\\lib\\FooterBoxes\\MemSales',
            'COREPOS\\pos\\lib\\FooterBoxes\\MultiTotal',
            'COREPOS\\pos\\lib\\FooterBoxes\\SavedOrCouldHave',
            'COREPOS\\pos\\lib\\FooterBoxes\\TransPercentDiscount'
        );

        $all = AutoLoader::ListModules('COREPOS\pos\lib\FooterBoxes\FooterBox',True);
        foreach($defaults as $d){
            $this->assertContains($d, $all);
        }

        foreach($all as $class){
            $obj = FooterBox::factory($class);
            $this->assertInstanceOf('COREPOS\pos\lib\FooterBoxes\FooterBox',$obj);

            $this->assertObjectHasAttribute('header_css',$obj);
            $this->assertObjectHasAttribute('display_css',$obj);

            $header = $obj->header_content();
            $this->assertInternalType('string',$header);
            $display = $obj->display_content();
            $this->assertInternalType('string',$display);
        }

        $session = new WrappedStorage();

        $obj = new TransPercentDiscount($session);
        CoreLocal::set('percentDiscount', 10);
        CoreLocal::set('transDiscount', 10);
        $this->assertEquals('10% Discount', $obj->header_content());
        $this->assertEquals('10.00', $obj->display_content());
        CoreLocal::set('percentDiscount', 0);
        CoreLocal::set('transDiscount', 0);
        $this->assertEquals('% Discount', $obj->header_content());
        $this->assertEquals('n/a', $obj->display_content());

        $obj = new SavedOrCouldHave($session);
        CoreLocal::set('isMember', 1);
        CoreLocal::set('memSpecial', 10);
        CoreLocal::set('discounttotal', 10);
        $this->assertEquals('You Saved', $obj->header_content());
        $this->assertEquals('20.00', $obj->display_content());
        CoreLocal::set('isMember', 0);
        $this->assertEquals('Could Have Saved', $obj->header_content());
        $this->assertEquals('10.00', $obj->display_content());
        CoreLocal::set('memSpecial', 0);
        CoreLocal::set('discounttotal', 0);

        $obj = new PatronagePts($session);
        CoreLocal::set('isMember', 1);
        CoreLocal::set('discountableTotal', 10);
        $this->assertEquals('10.00', $obj->display_content());
        CoreLocal::set('isMember', 0);
        CoreLocal::set('discountableTotal', 0);
        $this->assertEquals('n/a', $obj->display_content());

        $obj = new MemSales($session);
        CoreLocal::set('isMember', 1);
        CoreLocal::set('memSpecial', 10);
        $this->assertEquals('10.00', $obj->display_content());
        CoreLocal::set('isMember', 0);
        CoreLocal::set('memSpecial', 0);
        $this->assertEquals('n/a', $obj->display_content());

        $obj = new MultiTotal($session);
        CoreLocal::set('ttlflag', 1);
        CoreLocal::set('End', 0);
        CoreLocal::set('fntlflag', 1);
        CoreLocal::set('fsEligible', 10);
        $this->assertEquals('fs Amount Due', $obj->header_content());
        $this->assertEquals('10.00', $obj->display_content());
        CoreLocal::set('fntlflag', 0);
        CoreLocal::set('fsEligible', 0);
        CoreLocal::set('runningTotal', 10);
        $this->assertEquals('Amount Due', $obj->header_content());
        $this->assertEquals('10.00', $obj->display_content());
        CoreLocal::set('End', 1);
        $this->assertEquals('Change', $obj->header_content());
        $this->assertEquals('10.00', $obj->display_content());
        CoreLocal::set('ttlflag', 0);
        CoreLocal::set('End', 0);
        CoreLocal::set('runningTotal', 0);
    }
}

