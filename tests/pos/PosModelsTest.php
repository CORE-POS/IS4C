<?php
/**
 * @backupGlobals disabled
 */
class PosModelsTest extends PHPUnit_Framework_TestCase
{
    public function testModels()
    {
        $models = AutoLoader::listModules('COREPOS\pos\lib\models\BasicModel');
        $dbc =  Database::pDataConnect();
        foreach ($models as $class) {
            $obj = new $class($dbc);
            // this just improves coverage; the doc method isn't
            // user-facing functionality
            $this->assertInternalType('string', $obj->doc());
            if (substr($class, 0, 26) == 'COREPOS\\pos\\lib\\models\\op\\') {
                $dbname = CoreLocal::get('pDatabase');
            } elseif (substr($class, 0, 29) == 'COREPOS\\pos\\lib\\models\\trans\\') {
                $dbname = CoreLocal::get('tDatabase');
            } else {
                continue;
            }
            ob_start();
            $this->assertEquals(0, $obj->normalize($dbname), "$class is not normalized");
            ob_get_clean();
        }
    }

    public function testTendersModel()
    {
        $obj = new COREPOS\pos\lib\models\op\TendersModel(Database::pDataConnect());
        $this->assertInternalType('array', $obj->getMap());
    }

    public function testParametersModel()
    {
        $obj = new COREPOS\pos\lib\models\op\ParametersModel(Database::pDataConnect());
        $obj->is_array(0);
        $obj->param_value('true');
        $this->assertEquals(true, $obj->materializeValue());
        $obj->param_value('false');
        $this->assertEquals(false, $obj->materializeValue());
        $obj->is_array(1);
        $obj->param_value('');
        $this->assertEquals(array(), $obj->materializeValue());
        $obj->param_value('1');
        $this->assertEquals(array(1), $obj->materializeValue());
        $obj->param_value('1,2');
        $this->assertEquals(array(1,2), $obj->materializeValue());
        $obj->param_value('one=>1');
        $this->assertEquals(array('one'=>1), $obj->materializeValue());
        $obj->param_value('one=>1,two=>2');
        $this->assertEquals(array('one'=>1,'two'=>2), $obj->materializeValue());
    }
}

