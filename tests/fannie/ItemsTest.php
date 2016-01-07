<?php

/**
 * @backupGlobals disabled
 */
class ItemsTest extends PHPUnit_Framework_TestCase
{
    public function testItems()
    {
        $items = FannieAPI::listModules('ItemModule', true);
        $conf = FannieConfig::factory();
        $con = FannieDB::get($conf->get('OP_DB'));

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

    public function textExtraInfo()
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
        $form->idEnforced = 0;
        $module->setForm($form);
        $this->assertEquals(true, $module->saveFormData('0000000004011'));
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

}

