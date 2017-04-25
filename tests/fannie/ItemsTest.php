<?php

/**
 * @backupGlobals disabled
 */
class ItemsTest extends PHPUnit_Framework_TestCase
{
    public function testItems()
    {
        $items = FannieAPI::listModules('COREPOS\Fannie\API\item\ItemModule', true);
        $conf = FannieConfig::factory();
        $con = FannieDB::forceReconnect(FannieConfig::config('OP_DB'));

        foreach($items as $item_class) {
            $obj = new $item_class();
            $obj->setConnection($con);
            $obj->setConfig($conf);
            $this->assertInternalType('string', $obj->showEditForm('0000000004011'));
            $this->assertInternalType('int', $obj->width());
            $this->assertInternalType('array', $obj->summaryRows('0000000004011'));
            $this->assertInternalType('string', $obj->getFormJavascript('0000000004011'));
        }
    }

    public function testBaseModule()
    {
        $config = FannieConfig::factory();
        $connection = FannieDB::get($config->OP_DB);
        $mod = new BaseItemModule();
        $mod->setConfig($config);
        $mod->setConnection($connection);
        $this->assertNotEquals(0, strlen($mod->showEditForm('0123456789012')));

        $form = new \COREPOS\common\mvc\ValueContainer();
        $form->store_id = array(1);
        $form->tax = array(0);
        $form->FS = array();
        $form->Scale = array();
        $form->QtyFrc = array();
        $form->discount = array(1);
        $form->price = array(1);
        $form->cost = array(0);
        $form->descript = array('unit test item');
        $form->manufacturer = array('unit test');
        $form->department = array(1);
        $form->subdept = array(0);
        $form->size = array('');
        $form->unitm = array('');
        $form->distributor = array('unit test');
        $mod->setForm($form);
        $mod->saveFormData('0123456789012');
    }

    public function testItemFlags()
    {
        $config = FannieConfig::factory();
        $connection = FannieDB::get($config->OP_DB);

        /**
          Setup preconditions for the test
        */
        $upc = BarcodeLib::padUPC('16');
        $product = new ProductsModel($connection);
        $product->upc($upc);
        $product->store_id(0);
        $product->load();
        if ($product->numflag() != 0) {
            $product->numflag(0);
        }
        $product->save();

        $module = new ItemFlagsModule();
        $module->setConnection($connection);
        $module->setConfig($config);

        $form = new \COREPOS\common\mvc\ValueContainer();
        $module->setForm($form);
        $saved = $module->saveFormData($upc);
        $this->assertEquals(true, $saved, 'Handled empty input');

        $product->reset();
        $product->upc($upc);
        $product->load();
        $this->assertEquals(0, $product->numflag(), 'Wrong numflag value ' . $product->numflag());

        /**
          Simulate real form input
        */
        $form = new \COREPOS\common\mvc\ValueContainer();
        $form->flags = array(1, 3); // 0b101 == 5
        $form->pf_attrs = array('a', 'b', 'c', 'd', 'e');
        $form->pf_bits = array(1, 2, 3, 4, 5);
        $module->setForm($form);
        $saved = $module->saveFormData($upc);
        $this->assertEquals(true, $saved, 'Saving item flags failed');

        $product->reset();
        $product->upc($upc);
        $product->load();
        $this->assertEquals(5, $product->numflag(), 'Wrong numflag value ' . $product->numflag());

        /* put record back to normal */
        $product->numflag(0);
        $product->save();

        $form = new \COREPOS\common\mvc\ValueContainer();
        $form->flags = 'not_an_array';
        $form->pf_attrs = array('a', 'b', 'c', 'd', 'e');
        $form->pf_bits = array(1, 2, 3, 4, 5);
        $module->setForm($form);
        $saved = $module->saveFormData($upc);
        $this->assertEquals(false, $saved, 'Accepted invalid input');
    }

    public function testItemMargin()
    {
        $config = FannieConfig::factory();
        $connection = FannieDB::get($config->OP_DB);
        $module = new ItemFlagsModule();
        $module->setConnection($connection);
        $module->setConfig($config);

        $form = new \COREPOS\common\mvc\ValueContainer();
        $form->price_rule_id = 1;
        $form->current_price_rule_id = 99;
        $module->setForm($form);
        $this->assertEquals(true, $module->saveFormData('0000000004011'));

        $form->price_rule_id = 2;
        $form->current_price_rule_id = 99;
        $module->setForm($form);
        $this->assertEquals(true, $module->saveFormData('0000000004011'));

        $form->price_rule_id = 2;
        $form->current_price_rule_id = 0;
        $module->setForm($form);
        $this->assertEquals(true, $module->saveFormData('0000000004011'));
    }

    public function testExtraInfo()
    {
        $config = FannieConfig::factory();
        $connection = FannieDB::get($config->OP_DB);
        $module = new ExtraInfoModule();
        $module->setConnection($connection);
        $module->setConfig($config);

        $form = new \COREPOS\common\mvc\ValueContainer();
        $form->deposit = 0;
        $form->local = 0;
        $form->inUse = 1;
        $form->idReq = 0;
        $module->setForm($form);
        $this->assertEquals(true, $module->saveFormData('0000000004011'));
    }

    public function testItemLinks()
    {
        $mod = new ItemLinksModule();
        $form = new COREPOS\common\mvc\ValueContainer();
        $form->newshelftag = 'tag';
        $mod->setForm($form);
        ob_start();
        $mod->saveFormData('4011');
        $this->assertNotEquals(0, strlen(ob_get_clean()));
    }

    public function testLikeCode()
    {
        $config = FannieConfig::factory();
        $connection = FannieDB::get($config->OP_DB);
        $module = new LikeCodeModule();
        $module->setConnection($connection);
        $module->setConfig($config);

        $form = new \COREPOS\common\mvc\ValueContainer();
        $form->likeCode = 1;
        $module->setForm($form);
        $this->assertEquals(true, $module->saveFormData('0000000004011'));
        $form->likeCode = -1;
        $module->setForm($form);
        $this->assertEquals(true, $module->saveFormData('0000000004011'));
    }

    public function testScaleLibs()
    {
        $connection = FannieDB::get($config->OP_DB);
        $obj = new ServiceScalesModel($connection); 
        $item_info = array(
            'RecordType' => 'WriteOneItem',
            'PLU' => '1234',
            'Description' => 'asdf asdf asdf asfd asdf asfd asdf asdf asdf',
            'ReportingClass' => '1',
            'Label' => '1',
            'Tare' => '0.05',
            'ShelfLife' => '7',
            'Price' => '1.99',
            'Type' => 'Random Weight',
            'NetWeight' => '1',
            'Graphics' => '1',
            'ExpandedText' => 'Ingredients go here',
            'MOSA' => 0,
            'OriginText' => '',
        );

        $this->assertInternalType('string', COREPOS\Fannie\API\item\EpScaleLib::getItemLine($item_info, $obj));
        $this->assertInternalType('string', COREPOS\Fannie\API\item\EpScaleLib::getIngredientLine($item_info, $obj));
        $this->assertInternalType('string', COREPOS\Fannie\API\item\HobartDgwLib::getItemLine($item_info, $obj));
        $item_info['RecordType'] = 'ChangeOneItem';
        $this->assertInternalType('string', COREPOS\Fannie\API\item\EpScaleLib::getItemLine($item_info, $obj));
        $this->assertInternalType('string', COREPOS\Fannie\API\item\HobartDgwLib::getItemLine($item_info, $obj));
    }

}

