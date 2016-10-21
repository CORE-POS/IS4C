<?php

/**
 * @backupGlobals disabled
 */
class WebServicesTest extends PHPUnit_Framework_TestCase
{
    public function testDeptLookup()
    {
        $ws = new COREPOS\Fannie\API\webservices\FannieDeptLookup();
        $args = new stdClass();
        $ret = $ws->run($args);
        $this->assertEquals(-32602, $ret['error']['code']);

        $args->type = 'settings';
        $ret = $ws->run($args);
        $this->assertEquals(-32602, $ret['error']['code']);

        $args->type = 'children';
        $ret = $ws->run($args);
        $this->assertEquals(-32602, $ret['error']['code']);

        $args->superID = array(1);
        $ret = $ws->run($args);
        $this->assertEquals(-32602, $ret['error']['code']);

        $args->type = 'invalid';
        $ret = $ws->run($args);
        $this->assertEquals(-32602, $ret['error']['code']);

        $args = new stdClass();
        $args->type = 'settings';
        $args->dept_no = 1;
        $ret = $ws->run($args);
        $this->assertArrayHasKey('tax', $ret);

        // coverage only at present
        $args->type = 'children';
        $ret = $ws->run($args);
        $args->dept_no = array(1,2);
        $ret = $ws->run($args);
        $args->superID = 1;
        unset($args->dept_no);
        $ret = $ws->run($args);
        $args->superID = array(1,2);
        $ret = $ws->run($args);
        $args->superID = -1;
        $ret = $ws->run($args);
        $args->superID = -2;
        $ret = $ws->run($args);
    }

    public function testItemInfo()
    {
        $ws = new COREPOS\Fannie\API\webservices\FannieItemInfo();
        $args = new stdClass();
        $ret = $ws->run($args);
        $this->assertEquals(-32602, $ret['error']['code']);

        $args->type = 'invalid';
        $ret = $ws->run($args);
        $this->assertEquals(-32602, $ret['error']['code']);

        $args->type = 'vendor';
        $ret = $ws->run($args);
        $this->assertEquals(-32602, $ret['error']['code']);

        $args->vendor_id = 1;
        $ret = $ws->run($args);
        $this->assertEquals(-32602, $ret['error']['code']);

        $args->upc = '0000000004011';
        $ret = $ws->run($args);
        $args->sku = '12345';
        $ret = $ws->run($args);
    }

    public function testLaneSync()
    {
        $ws = new COREPOS\Fannie\API\webservices\FannieItemLaneSync();
        $args = new stdClass();
        $ret = $ws->run($args);

        $args->upc = '0000000004011';
        $ret = $ws->run($args);
        $args->fast = 1;
        $ret = $ws->run($args);
    }

    public function testLaneStatus()
    {
        $ws = new COREPOS\Fannie\API\webservices\FannieLaneStatusService();
        $args = new stdClass();
        $args->upc = '0000000004011';
        $ret = $ws->run($args);
    }

    public function testMemLaneSync()
    {
        $ws = new COREPOS\Fannie\API\webservices\FannieMemberLaneSync();
        $args = new stdClass();
        $ret = $ws->run($args);

        $args->id = 1;
        $ret = $ws->run($args);
    }

    public function testAutoComplete()
    {
        $ws = new COREPOS\Fannie\API\webservices\FannieAutoComplete();
        $args = new stdClass();
        $ret = $ws->run($args);
        $this->assertEquals(-32602, $ret['error']['code']);

        $args->field = 'item';
        $args->search = '';
        $ret = $ws->run($args);
        $this->assertEquals(-32602, $ret['error']['code']);

        $args->search = '401';
        $ret = $ws->run($args);
        $args->search = 'pbana';
        $ret = $ws->run($args);
        $args->field = 'brand';
        $ret = $ws->run($args);
        $args->field = 'long_brand';
        $ret = $ws->run($args);
        $args->field = 'vendor';
        $ret = $ws->run($args);
        $args->field = 'mfirstname';
        $ret = $ws->run($args);
        $args->field = 'sku';
        $ret = $ws->run($args);
        $args->field = 'unit';
        $ret = $ws->run($args);
        $args->field = 'foo';
        $ret = $ws->run($args);
    }
}

