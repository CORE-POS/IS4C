<?php
include(dirname(__FILE__).'/../config.php');
include(dirname(__FILE__).'/../src/SQLManager.php');

/**
 * @backupGlobals disabled
 */
class InstallTest extends PHPUnit_Framework_TestCase
{
    public function testDoInstall(){
        ob_start();
        include(dirname(__FILE__).'/../install/index.php');
        $page = ob_get_clean();
        
        /* verify database structures */

        $con = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                $FANNIE_SERVER_USER,$FANNIE_SERVER_PW);
        $results = create_op_dbs($con);
        $this->assertNotEmpty($results,'create_op_dbs did not return an array');
        foreach($results as $result){
            $this->assertArrayHasKey('error',$result,'Invalid result entry');
            $this->assertArrayHasKey('error_msg',$result,'Invalid result entry');
            $this->assertArrayHasKey('db',$result,'Invalid result entry');
            $this->assertArrayHasKey('struct',$result,'Invalid result entry');
            $this->assertEquals(0,$result['error'],
                'Error creating '.$result['db'].'.'.$result['struct'].': '.$result['error_msg']);
        }

        $con = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                $FANNIE_SERVER_USER,$FANNIE_SERVER_PW);
        $results = create_trans_dbs($con);
        $this->assertNotEmpty($results,'create_trans_dbs did not return an array');
        foreach($results as $result){
            $this->assertArrayHasKey('error',$result,'Invalid result entry');
            $this->assertArrayHasKey('error_msg',$result,'Invalid result entry');
            $this->assertArrayHasKey('db',$result,'Invalid result entry');
            $this->assertArrayHasKey('struct',$result,'Invalid result entry');
            $this->assertEquals(0,$result['error'],
                'Error creating '.$result['db'].'.'.$result['struct'].': '.$result['error_msg']);
        }
        $results = create_dlogs($con);
        $this->assertNotEmpty($results,'create_dlogs did not return an array');
        foreach($results as $result){
            $this->assertArrayHasKey('error',$result,'Invalid result entry');
            $this->assertArrayHasKey('error_msg',$result,'Invalid result entry');
            $this->assertArrayHasKey('db',$result,'Invalid result entry');
            $this->assertArrayHasKey('struct',$result,'Invalid result entry');
            $this->assertEquals(0,$result['error'],
                'Error creating '.$result['db'].'.'.$result['struct'].': '.$result['error_msg']);
        }

        $con = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_ARCHIVE_DB,
                $FANNIE_SERVER_USER,$FANNIE_SERVER_PW);
        $results = create_archive_dbs($con);
        $this->assertNotEmpty($results,'create_archive_dbs did not return an array');
        foreach($results as $result){
            $this->assertArrayHasKey('error',$result,'Invalid result entry');
            $this->assertArrayHasKey('error_msg',$result,'Invalid result entry');
            $this->assertArrayHasKey('db',$result,'Invalid result entry');
            $this->assertArrayHasKey('struct',$result,'Invalid result entry');
            $this->assertEquals(0,$result['error'],
                'Error creating '.$result['db'].'.'.$result['struct'].': '.$result['error_msg']);
        }

        $results = create_delayed_dbs();
        $this->assertNotEmpty($results,'create_delayed_dbs did not return an array');
        foreach($results as $result){
            $this->assertArrayHasKey('error',$result,'Invalid result entry');
            $this->assertArrayHasKey('error_msg',$result,'Invalid result entry');
            $this->assertArrayHasKey('db',$result,'Invalid result entry');
            $this->assertArrayHasKey('struct',$result,'Invalid result entry');
            $this->assertEquals(0,$result['error'],
                'Error creating '.$result['db'].'.'.$result['struct'].': '.$result['error_msg']);
        }
    }
}
