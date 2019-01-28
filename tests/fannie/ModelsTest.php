<?php

/**
 * @backupGlobals disabled
 */
class ModelsTest extends PHPUnit_Framework_TestCase
{
    public function testHooks()
    {
        $hook = new COREPOS\Fannie\API\data\hooks\BasicModelHook();
        $this->assertEquals(false, $hook->operatesOnTAble('products'));
        $this->assertEquals(null, $hook->onSave('products', null));
    }

    public function testModels()
    {
        $dbc = FannieDB::forceReconnect(FannieConfig::config('OP_DB'));
        $base = new BasicModel($dbc);
        $models = $base->getModels();

        $normalized = false;
        foreach ($models as $model_class) {
            $obj = new $model_class(null);
            $columns = $obj->getColumns();

            // check column definitions
            $this->assertInternalType('array', $columns);
            foreach ($columns as $column_name => $column_definition) {
                // must be array, must have a type
                $this->assertInternalType('array', $column_definition);
                $this->assertArrayHasKey('type', $column_definition, $model_class . ' missing type for ' . $column_name);

                // must have a get/set method for each collumn
                $val = rand();
                $obj->$column_name($val);
                $this->assertEquals($val, $obj->$column_name(), 'Get/set busted for ' . $model_class . ' :: ' . $column_name);
            }

            $this->assertInternalType('string', $obj->doc());

            if (!$normalized && $obj->preferredDB() === 'op') {
                $dbc = FannieDB::forceReconnect(FannieConfig::config('OP_DB'));
                $obj2 = new $model_class($dbc);
                ob_start();
                $obj2->normalize(FannieConfig::config('OP_DB'));
                ob_end_clean();
                $normalized = true;
            }
        }
    }

    public function testBreakdowns()
    {
        $dbc = FannieDB::get(FannieConfig::config('OP_DB'));
        $model = new VendorBreakdownsModel($dbc);

        $pair = $model->getSplit('4/12oz');
        $this->assertEquals($pair, array(4, '12OZ'));

        $pair = $model->getSplit('5 CT');
        $this->assertEquals($pair, array(5, ''));

        $pair = $model->getSplit('4PKT');
        $this->assertEquals($pair, array(4, ''));

        $pair = $model->getSplit('NonSense');
        $this->assertEquals($pair, array(false, ''));
    }

    public function testVendorItems()
    {
        $dbc = FannieDB::get(FannieConfig::config('OP_DB'));
        $model = new VendorItemsModel($dbc);
        $this->assertEquals(null, $model->createIfMissing('0000000000111', 99));
        $this->assertEquals(null, $model->updateCostByUPC('0000000000111', 1.00, 99));
    }

    public function testParameters()
    {
        $param = new ParametersModel(null);
        $param->is_array(0);
        $param->param_value('true');
        $this->assertEquals(true, $param->materializeValue());
        $param->param_value('false');
        $this->assertEquals(false, $param->materializeValue());
        $param->param_value('foo');
        $this->assertEquals('foo', $param->materializeValue());
        $param->param_value('');
        $param->is_array(1);
        $this->assertEquals(array(), $param->materializeValue());
        $param->param_value('foo=>bar,bar=>foo');
        $this->assertEquals(array('foo'=>'bar','bar'=>'foo'), $param->materializeValue());
    }

    public function testDeliveries()
    {
        $del = new VendorDeliveriesModel();
        $del->frequency('weekly');
        $del->monday(1);
        $del->autoNext();
        $this->assertNotEquals('', $del->nextDelivery());
        $this->assertNotEquals('', $del->nextNextDelivery());
    }

}

