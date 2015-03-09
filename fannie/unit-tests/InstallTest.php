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
        $this->assertNotEmpty($results,'create_op_dbs did not return an array');
        foreach ($results as $result) {

            $this->assertArrayHasKey('error',$result,'Invalid result entry');
            $this->assertArrayHasKey('error_msg',$result,'Invalid result entry');
            $this->assertArrayHasKey('db',$result,'Invalid result entry');
            $this->assertArrayHasKey('struct',$result,'Invalid result entry');
            $this->assertEquals(0,$result['error'],
                'Error creating '.$result['db'].'.'.$result['struct'].': '.$result['error_msg']);

            $exists = $con->tableExists($result['struct']);
            if (isset($result['deprecated']) && $result['deprecated']) {
                $this->assertEquals(false, $exists, 'Deprecated structure ' . $result['struct'] . ' was not removed.');
            } else {
                $this->assertEquals(true, $exists, 'Structure ' . $result['struct'] . ' was not created.');
            }
        }
    }

    public function testInstallTransDB()
    {
        $con = FannieDB::get('unit_test_trans');
        if (!class_exists('InstallIndexPage')) {
            include_once(dirname(__FILE__) . '/../install/InstallIndexPage.php');
        }
        $page = new InstallIndexPage();
        $results = $page->create_trans_dbs($con, 'unit_test_trans', 'unit_test_op');
        $this->assertNotEmpty($results,'create_trans_dbs did not return an array');
        foreach ($results as $result) {

            $this->assertArrayHasKey('error',$result,'Invalid result entry');
            $this->assertArrayHasKey('error_msg',$result,'Invalid result entry');
            $this->assertArrayHasKey('db',$result,'Invalid result entry');
            $this->assertArrayHasKey('struct',$result,'Invalid result entry');
            $this->assertEquals(0,$result['error'],
                'Error creating '.$result['db'].'.'.$result['struct'].': '.$result['error_msg']);

            $exists = $con->tableExists($result['struct']);
            if (isset($result['deprecated']) && $result['deprecated']) {
                $this->assertEquals(false, $exists, 'Deprecated structure ' . $result['struct'] . ' was not removed.');
            } else {
                $this->assertEquals(true, $exists, 'Structure ' . $result['struct'] . ' was not created.');
            }
        }
    }

    public function testInstallArchiveDB()
    {
        $con = FannieDB::get('unit_test_archive');
        if (!class_exists('InstallIndexPage')) {
            include_once(dirname(__FILE__) . '/../install/InstallIndexPage.php');
        }
        $page = new InstallIndexPage();
        $results = $page->create_archive_dbs($con, 'unit_test_archive', 'partitions');
        $this->assertNotEmpty($results,'create_trans_dbs did not return an array');
        foreach ($results as $result) {

            $this->assertArrayHasKey('error',$result,'Invalid result entry');
            $this->assertArrayHasKey('error_msg',$result,'Invalid result entry');
            $this->assertArrayHasKey('db',$result,'Invalid result entry');
            $this->assertArrayHasKey('struct',$result,'Invalid result entry');
            $this->assertEquals(0,$result['error'],
                'Error creating '.$result['db'].'.'.$result['struct'].': '.$result['error_msg']);

            $exists = $con->tableExists($result['struct']);
            if (isset($result['deprecated']) && $result['deprecated']) {
                $this->assertEquals(false, $exists, 'Deprecated structure ' . $result['struct'] . ' was not removed.');
            } else {
                $this->assertEquals(true, $exists, 'Structure ' . $result['struct'] . ' was not created.');
            }
        }

        // run a second time so both archive methods are tested
        $results = $page->create_archive_dbs($con, 'unit_test_archive', 'tables');
        $this->assertNotEmpty($results,'create_trans_dbs did not return an array');
        foreach ($results as $result) {

            $this->assertArrayHasKey('error',$result,'Invalid result entry');
            $this->assertArrayHasKey('error_msg',$result,'Invalid result entry');
            $this->assertArrayHasKey('db',$result,'Invalid result entry');
            $this->assertArrayHasKey('struct',$result,'Invalid result entry');
            $this->assertEquals(0,$result['error'],
                'Error creating '.$result['db'].'.'.$result['struct'].': '.$result['error_msg']);

            $exists = $con->tableExists($result['struct']);
            if (isset($result['deprecated']) && $result['deprecated']) {
                $this->assertEquals(false, $exists, 'Deprecated structure ' . $result['struct'] . ' was not removed.');
            } else {
                $this->assertEquals(true, $exists, 'Structure ' . $result['struct'] . ' was not created.');
            }
        }
    }

    public function testSampleData()
    {
        $con = FannieDB::get('unit_test_op');

        $samples = array(
            'custdata',
            'departments',
            'employees',
            'memtype',
            'originCountry',
            'originStateProv',
            'products',
            'superdepts',
            'superDeptNames',
            'tenders',
        );

        foreach ($samples as $sample) {
            $con->query('TRUNCATE TABLE ' . $con->identifierEscape($sample));
            ob_start();
            $loaded = \COREPOS\Fannie\API\data\DataLoad::loadSampleData($con, $sample);
            $output = ob_get_clean();

            $this->assertEquals(true, $loaded, 'Error loading ' . $sample . ' (' . $output . ')');
        }
    }
}
