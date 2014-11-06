<?php

/**
 * @backupGlobals disabled
 */
class InstallTest extends PHPUnit_Framework_TestCase
{
    public function testInstallOpDB()
    {
        $con = FannieDB::get('unit_test_op');
        if (!class_exists('InstallIndexPage')) {
            include_once(dirname(__FILE__) . '/../install/InstallIndexPage.php');
        }
        $page = new InstallIndexPage();
        $results = $page->create_op_dbs($con, 'unit_test_op');
        $this->assertNotEmpty($results,'create_delayed_dbs did not return an array');
        foreach ($results as $result) {

            $this->assertArrayHasKey('error',$result,'Invalid result entry');
            $this->assertArrayHasKey('error_msg',$result,'Invalid result entry');
            $this->assertArrayHasKey('db',$result,'Invalid result entry');
            $this->assertArrayHasKey('struct',$result,'Invalid result entry');
            $this->assertEquals(0,$result['error'],
                'Error creating '.$result['db'].'.'.$result['struct'].': '.$result['error_msg']);

            $exists = $con->tableExists($result['struct']);
            $this->assertEquals(true, $exists, 'Structure ' . $result['struct'] . ' was not created.');
        }
    }
}
