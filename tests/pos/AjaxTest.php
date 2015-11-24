<?php
/**
 * @backupGlobals disabled
 */
class AjaxTest extends PHPUnit_Framework_TestCase
{
    public function testBase()
    {
        $obj = new AjaxCallback();
        $this->assertEquals('json', $obj->getEncoding());
        $obj->run();

        ob_start();
        CoreLocal::set('cabReference', '1-1-1');
        AjaxCallback::unitTest('AjaxCabReceipt');
        $output = ob_get_clean();
        $this->assertEquals('Done', $output);
        CoreLocal::set('cabReference', '');
        ob_start();
        AjaxCallback::unitTest('AjaxDecision');
        $output = ob_get_clean();
        $this->assertEquals('{"dest_page":"gui-modules/pos2.php","endorse":false,"cleared":true}', $output);
    }
    
    public function testParser()
    {
        $ajax = new AjaxParser();
        $ajax->enablePageDrawing(true);
        CoreLocal::set('strRemembered', 'invalidInput');
        CoreLocal::set('msgrepeat', 1);    
        $json = $ajax->ajax();

        $this->assertInternalType('array', $json);
        $this->assertEquals(false, $json['main_frame']);
        $this->assertEquals('.baseHeight', $json['target']);
        $this->assertNotEquals(0, strlen($json['output']));
    }

    public function testCabReceipt()
    {
        $ajax = new AjaxCabReceipt();
        $this->assertEquals('Done', $ajax->ajax(array('cab-reference'=>'9999-99-1')));
    }

    public function testDecision()
    {
        $ajax = new AjaxDecision();
        $json = $ajax->ajax();
        $this->assertInternalType('array', $json);
        $this->assertEquals(false, $json['endorse']);
        $this->assertEquals(true, $json['cleared']);
        $this->assertEquals('gui-modules/pos2.php', $json['dest_page']);
    }

    public function testEndorse()
    {
        $ajax = new AjaxEndorse();
        $this->assertEquals('Done', $ajax->ajax());
    }

    public function testScale()
    {
        $ajax = new AjaxScale();
        $this->assertEquals(' lb', $ajax->ajax());
    }
}

