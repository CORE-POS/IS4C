<?php

/**
 * @backupGlobals disabled
 */
class ModelsTest extends PHPUnit_Framework_TestCase
{
    public function testModels()
    {
        $models = FannieAPI::listModules('BasicModel', true);

        foreach ($models as $model_class) {
            $obj = new $model_class(null);
            $rc = new ReflectionClass($obj);
            $columns = $rc->getProperty('columns');
            $columns->setAccessible(true);
            $columns = $columns->getValue($obj);

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

}

