<?php

use COREPOS\pos\lib\AjaxCallback;
use COREPOS\pos\lib\LocalStorage\WrappedStorage;

use COREPOS\common\mvc\ValueContainer;

/**
 * @backupGlobals disabled
 */
class AjaxTest extends PHPUnit_Framework_TestCase
{
    public function testBase()
    {
        $obj = new AjaxCallback(new WrappedStorage(), new ValueContainer());
        $this->assertEquals('json', $obj->getEncoding());
        $obj->run();

        ob_start();
        CoreLocal::set('cabReference', '1-1-1');
        AjaxCallback::unitTest('COREPOS\\pos\\ajax\\AjaxCabReceipt');
        $output = ob_get_clean();
        $this->assertEquals('Done', $output);
        CoreLocal::set('cabReference', '');
        ob_start();
        AjaxCallback::unitTest('COREPOS\\pos\\ajax\\AjaxDecision');
        $output = ob_get_clean();
        $this->assertEquals('{"dest_page":"gui-modules\\/pos2.php","endorse":false,"cleared":true}', $output);
    }
    
    public function testParser()
    {
        $ajax = new COREPOS\pos\ajax\AjaxParser(new WrappedStorage(), new ValueContainer());
        $ajax->enablePageDrawing(true);
        CoreLocal::set('strRemembered', 'invalidInput');
        CoreLocal::set('msgrepeat', 1);    
        $json = $ajax->ajax();
        $this->assertInternalType('array', $json);
        $this->assertEquals(true, substr($json['main_frame'], -9) == 'login.php');

        CoreLocal::set('strRemembered', 'invalidInput');
        CoreLocal::set('msgrepeat', 1);    
        CoreLocal::set('CashierNo', 1);
        $json = $ajax->ajax();
        $this->assertEquals(false, $json['main_frame']);
        $this->assertEquals('.baseHeight', $json['target']);
        $this->assertNotEquals(0, strlen($json['output']));

        CoreLocal::set('strRemembered', 'CL');
        CoreLocal::set('msgrepeat', 1);
        $json = $ajax->ajax();
        $this->assertNotEquals(false, strstr(json_encode($json), 'pos2.php'));

        $vals = new ValueContainer();
        $vals->repeat = 1;
        $vals->reginput = 'RF1234567890123';
        $ajax = new COREPOS\pos\ajax\AjaxParser(new WrappedStorage(), $vals);
        $json = $ajax->ajax();
        $this->assertEquals('gui-modules/refundComment.php', $json['main_frame']);

        CoreLocal::set('CashierNo', '');
    }

    public function testCabReceipt()
    {
        $vals = new ValueContainer();
        $vals->input = '9999-99-1';
        $ajax = new COREPOS\pos\ajax\AjaxCabReceipt(new WrappedStorage(), $vals);
        $this->assertEquals('Done', $ajax->ajax());
    }

    public function testDecision()
    {
        $ajax = new COREPOS\pos\ajax\AjaxDecision(new WrappedStorage(), new ValueContainer());
        $json = $ajax->ajax();
        $this->assertInternalType('array', $json);
        $this->assertEquals(false, $json['endorse']);
        $this->assertEquals(true, $json['cleared']);
        $this->assertEquals('gui-modules/pos2.php', $json['dest_page']);
    }

    public function testEnd()
    {
        // default case: full receipt w/ reprint
        $vals = new ValueContainer();
        $vals->receiptType = 'full';
        $vals->ref = '1-1-1';
        $ws = new WrappedStorage();
        $ws->set('autoReprint', 1);
        $ajax = new COREPOS\pos\ajax\AjaxEnd($ws, $vals);
        $this->assertEquals(array(), $ajax->ajax());

        // test receipt number not provided
        $vals->ref = '';
        $ajax = new COREPOS\pos\ajax\AjaxEnd($ws, $vals);
        $this->assertEquals(array(), $ajax->ajax());

        // test receipt type not provided
        $vals->receiptType = '';
        $ajax = new COREPOS\pos\ajax\AjaxEnd($ws, $vals);
        $this->assertEquals(array(), $ajax->ajax());

        // test disabling cancel receipt
        $vals->ref = '1-1-1';
        $vals->receiptType = 'cancelled';
        $ws->set('CancelReceipt', 0);
        $ajax = new COREPOS\pos\ajax\AjaxEnd($ws, $vals);
        $this->assertEquals(array(), $ajax->ajax());

        // test disabling suspend receipt
        $vals->receiptType = 'suspended';
        $ws->set('SuspendReceipt', 0);
        $ajax = new COREPOS\pos\ajax\AjaxEnd($ws, $vals);
        $this->assertEquals(array(), $ajax->ajax());

        // test disabling shrink receipt
        $vals->receiptType = 'ddd';
        $ws->set('ShrinkReceipt', 0);
        $ajax = new COREPOS\pos\ajax\AjaxEnd($ws, $vals);
        $this->assertEquals(array(), $ajax->ajax());

        // test receipt that doesn't end transaction
        $vals->receiptType = 'ccSlip';
        $ajax = new COREPOS\pos\ajax\AjaxEnd($ws, $vals);
        $this->assertEquals(array(), $ajax->ajax());
    }

    public function testEndorse()
    {
        $vals = new ValueContainer();
        $vals->amount = 1.00;
        foreach (array('check', 'giftcert', 'stock', 'classreg', 'unknown') as $type) {
            $vals->type = $type;
            $ajax = new COREPOS\pos\ajax\AjaxEndorse(new WrappedStorage(), $vals);
            $this->assertEquals('Done', $ajax->ajax());
        }
    }

    public function testScale()
    {
        $ajax = new COREPOS\pos\ajax\AjaxScale(new WrappedStorage(), new ValueContainer());
        $this->assertEquals(' lb', $ajax->ajax());
        $ajax = new COREPOS\pos\ajax\AjaxScale(new WrappedStorage(), new ValueContainer());
        $this->assertInternalType('string', $ajax->ajax());
    }

    public function testPoll()
    {
        $ajax = new COREPOS\pos\ajax\AjaxPollScale(new WrappedStorage(), new ValueContainer());
        $this->assertEquals('{}', $ajax->ajax());
    }
}

