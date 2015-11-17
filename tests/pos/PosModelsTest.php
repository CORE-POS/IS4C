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
        }
    }
}

