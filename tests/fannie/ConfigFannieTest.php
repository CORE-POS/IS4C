<?php

/**
 * @backupGlobals disabled
 */
class ConfigFannieTest extends PHPUnit_Framework_TestCase
{
    private function initPage($obj)
    {
        $config = FannieConfig::factory();
        $logger = new FannieLogger();
        $op_db = $config->get('OP_DB');
        $dbc = FannieDB::get($op_db);
        $obj->setConfig($config);
        $obj->setLogger($logger);
        $obj->setConnection($dbc);

        return $obj;
    }

    public function testAuth()
    {
        if (!class_exists('InstallAuthenticationPage')) {
            include(dirname(__FILE__) . '/../../fannie/install/InstallAuthenticationPage.php');
        }
        $obj = new InstallAuthenticationPage();
        $obj = $this->initPage($obj);
        $obj->unitTest($this);
    }

    public function testMemMods()
    {
        if (!class_exists('InstallMemModDisplayPage')) {
            include(dirname(__FILE__) . '/../../fannie/install/InstallMemModDisplayPage.php');
        }
        $obj = new InstallMemModDisplayPage();
        $obj = $this->initPage($obj);
        $obj->unitTest($this);
    }

    public function testMem()
    {
        if (!class_exists('InstallMembershipPage')) {
            include(dirname(__FILE__) . '/../../fannie/install/InstallMembershipPage.php');
        }
        $obj = new InstallMembershipPage();
        $obj = $this->initPage($obj);
        $obj->unitTest($this);
    }

    public function testMenu()
    {
        if (!class_exists('InstallMenuPage')) {
            include(dirname(__FILE__) . '/../../fannie/install/InstallMenuPage.php');
        }
        $obj = new InstallMenuPage();
        $obj = $this->initPage($obj);
        $obj->unitTest($this);
    }

    public function testPlugins()
    {
        if (!class_exists('InstallPluginsPage')) {
            include(dirname(__FILE__) . '/../../fannie/install/InstallPluginsPage.php');
        }
        $obj = new InstallPluginsPage();
        $obj = $this->initPage($obj);
        $obj->unitTest($this);
    }

    public function testProducts()
    {
        if (!class_exists('InstallProductsPage')) {
            include(dirname(__FILE__) . '/../../fannie/install/InstallProductsPage.php');
        }
        $obj = new InstallProductsPage();
        $obj = $this->initPage($obj);
        $obj->unitTest($this);
    }

    public function testStores()
    {
        if (!class_exists('InstallStoresPage')) {
            include(dirname(__FILE__) . '/../../fannie/install/InstallStoresPage.php');
        }
        $obj = new InstallStoresPage();
        $obj = $this->initPage($obj);
        $obj->unitTest($this);
    }

    public function testTheme()
    {
        if (!class_exists('InstallThemePage')) {
            include(dirname(__FILE__) . '/../../fannie/install/InstallThemePage.php');
        }
        $obj = new InstallThemePage();
        $obj = $this->initPage($obj);
        $obj->unitTest($this);
    }

    public function testData()
    {
        if (!class_exists('InstallSampleDataPage')) {
            include(dirname(__FILE__) . '/../../fannie/install/sample_data/InstallSampleDataPage.php');
        }
        $obj = new InstallSampleDataPage();
        $obj = $this->initPage($obj);
        $obj->unitTest($this);
    }

    public function testIndex()
    {
        if (!class_exists('InstallIndexPage')) {
            include(dirname(__FILE__) . '/../../fannie/install/sample_data/InstallIndexPage.php');
        }
        $obj = new InstallIndexPage();
        $obj = $this->initPage($obj);
        $obj->unitTest($this);
    }
}

