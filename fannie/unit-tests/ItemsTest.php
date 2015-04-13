<?php

/**
 * @backupGlobals disabled
 */
class ItemsTest extends PHPUnit_Framework_TestCase
{
    public function testItems()
    {
        $items = FannieAPI::listModules('ItemModule', true);

        foreach($items as $item_class) {
            $obj = new $item_class();
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

}

