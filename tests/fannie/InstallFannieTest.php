<?php

/**
 * @backupGlobals disabled
 */
class InstallFannieTest extends PHPUnit_Framework_TestCase
{
    public function testInstallOpDB()
    {
        $dbc = FannieDB::forceReconnect(FannieConfig::config('OP_DB'));
        $op_db = FannieConfig::config('OP_DB');
        $con = FannieDB::get($op_db);
        if (!class_exists('InstallIndexPage')) {
            include_once(dirname(__FILE__) . '/../../fannie/install/InstallIndexPage.php');
        }
        $page = new InstallIndexPage();
        $results = $page->create_op_dbs($con, $op_db);
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
        $op_db = FannieConfig::config('OP_DB');
        $trans_db = FannieConfig::config('TRANS_DB');
        $con = FannieDB::get($trans_db);
        if (!class_exists('InstallIndexPage')) {
            include_once(dirname(__FILE__) . '/../../fannie/install/InstallIndexPage.php');
        }
        $page = new InstallIndexPage();
        $results = $page->create_trans_dbs($con, $trans_db, $op_db);
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
        $arch_db = FannieConfig::config('ARCHIVE_DB');
        $con = FannieDB::get($arch_db);
        if (!class_exists('InstallIndexPage')) {
            include_once(dirname(__FILE__) . '/../../fannie/install/InstallIndexPage.php');
        }
        $page = new InstallIndexPage();
        $results = $page->create_archive_dbs($con, $arch_db, 'partitions');
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
        $results = $page->create_archive_dbs($con, $arch_db, 'tables');
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
        $op_db = FannieConfig::config('OP_DB');
        $con = FannieDB::get($op_db);

        $samples = array(
            'batchType',
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

        $con->query('DELETE FROM departments WHERE dept_no > 10');
        $con->query('DELETE FROM originCountry WHERE countryID > 5');
        $con->query('DELETE FROM originStateProv WHERE stateProvID > 5');
        $con->query('DELETE FROM custdata WHERE CardNo > 1000');
    }
}
