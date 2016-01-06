<?php

class SpecialOrderTests extends \COREPOS\Fannie\API\test\TestWrapper
{
    private $orderID = 0;

    public function testCreateOrder($page, $phpunit)
    {
        $form = new \COREPOS\common\mvc\ValueContainer();
        $form->_method = 'get';
        $page->setForm($form);
        $get = $this->runRESTfulPage($page, $form);
        $phpunit->assertEquals(false, $get);
    }

    public function testOrderView($page, $phpunit)
    {
        $form = new \COREPOS\common\mvc\ValueContainer();
        $form->_method = 'get';
        $form->orderID = 1;
        $page->setForm($form);
        $get = $this->runRESTfulPage($page, $form);
        $phpunit->assertNotEquals(0, strlen($get));
    }

    public function testSetCustomer($page, $phpunit)
    {
        $form = new \COREPOS\common\mvc\ValueContainer();
        $form->_method = 'get';
        $form->orderID = 1;
        $form->customer = 1;
        $form->memNum = 1;
        $page->setForm($form);
        ob_start();
        $this->runRESTfulPage($page, $form);
        $get = json_decode(ob_get_clean(), true);
        $phpunit->assertInternalType('array', $get);
        $phpunit->assertArrayHasKey('customer', $get);
        $phpunit->assertArrayHasKey('footer', $get);
    }

    public function testAddItem($page, $phpunit)
    {
        $form = new \COREPOS\common\mvc\ValueContainer();
        $form->_method = 'post';
        $form->orderID = 1;
        $form->memNum = 1;
        $form->upc = '0000000000111';
        $form->cases = 1;
        $page->setForm($form);
        ob_start();
        $get = $this->runRESTfulPage($page, $form);
        $out = ob_get_clean();
        $phpunit->assertEquals(false, $get);
        $phpunit->assertNotEquals(0, strlen($out));
    }

    public function testToggles($page, $phpunit)
    {
        $form = new \COREPOS\common\mvc\ValueContainer();
        $form->_method = 'post';
        $form->orderID = 1;
        $form->togglePrint = 1;
        $page->setForm($form);
        $get = $this->runRESTfulPage($page, $form);
        $phpunit->assertEquals(false, $get);

        $form = new \COREPOS\common\mvc\ValueContainer();
        $form->_method = 'post';
        $form->orderID = 1;
        $form->transID = 1;
        $form->toggleStaff = 1;
        $page->setForm($form);
        $get = $this->runRESTfulPage($page, $form);
        $phpunit->assertEquals(false, $get);

        $form = new \COREPOS\common\mvc\ValueContainer();
        $form->_method = 'post';
        $form->orderID = 1;
        $form->transID = 1;
        $form->toggleMemType = 1;
        $page->setForm($form);
        $get = $this->runRESTfulPage($page, $form);
        $phpunit->assertEquals(false, $get);
    }

    public function testEditItem($page, $phpunit)
    {
        $form = new \COREPOS\common\mvc\ValueContainer();
        $form->_method = 'post';
        $form->orderID = 1;
        $form->description = 'changed';
        $form->srp = 10.99;
        $form->actual = 9.99;
        $form->qty = 5;
        $form->dept = 99;
        $form->unitPrice = 1.00;
        $form->vendor = 'test';
        $form->transID = 1;
        $form->changed = 1;
        $page->setForm($form);
        ob_start();
        $get = $this->runRESTfulPage($page, $form);
        $out = ob_get_clean();
        $phpunit->assertEquals(false, $get);
        $phpunit->assertNotEquals(0, strlen($out));
    }

    public function testDeleteItem($page, $phpunit)
    {
        $form = new \COREPOS\common\mvc\ValueContainer();
        $form->_method = 'delete';
        $form->orderID = 1;
        $form->transID = 1;
        $page->setForm($form);
        ob_start();
        $get = $this->runRESTfulPage($page, $form);
        $out = ob_get_clean();
        $phpunit->assertEquals(false, $get);
        $phpunit->assertNotEquals(0, strlen($out));
    }

    public function testEditCustomer($page, $phpunit)
    {
        $form = new \COREPOS\common\mvc\ValueContainer();
        $form->_method = 'post';
        $form->orderID = 1;
        $form->noteDept = 1;
        $form->noteText = 'testing';
        $form->addr = '123 4th st';
        $form->addr2 = 'apt #3';
        $form->city = 'somewhere';
        $form->state = 'NY';
        $form->zip = '12345';
        $form->ph1 = '1234567890';
        $form->ph2 = '9876543210';
        $form->email = 'bill@example.com';
        $page->setForm($form);
        ob_start();
        $get = $this->runRESTfulPage($page, $form);
        $out = ob_get_clean();
        $phpunit->assertEquals(false, $get);
        $phpunit->assertNotEquals(0, strlen($out));
    }
}

