<?php

use COREPOS\Fannie\API\jobs\ArUpdate;
use COREPOS\Fannie\API\jobs\Job;
use COREPOS\Fannie\API\jobs\QueueManager;
use COREPOS\Fannie\API\jobs\SqlUpdate;

class JobsTest extends PHPUnit_Framework_TestCase
{
    public function testBase()
    {
        $job = new Job(array());
        ob_start();
        $job->run();
        ob_end_clean();
    }

    public function testAR()
    {
        $job = new ArUpdate(array());
        $this->assertEquals(false, $job->run());
        $job = new ArUpdate(array('id'=>-1));
        $this->assertEquals(false, $job->run());
        $job = new ArUpdate(array('id'=>1));
        $job->run();
    }

    public function testQM()
    {
        $qm = new QueueManager();
        $job = array(
            'class' => 'COREPOS\\Fannie\\API\\jobs\\ArUpdate',
            'data' => array(
                'id' => 1,
            ),
        );
        $this->assertEquals(false, $qm->add($job));
    }

    public function testSQL()
    {
        $data = array(
            'table' => 'products',
            'set' => array(
                'description' => 'QUEUED CHANGE',
            ),
            'where' => array(
                'upc' => '1234567890123',
            ),
        );
        $job = new SqlUpdate(array());
        $this->assertEquals(false, $job->run());
        $job = new SqlUpdate(array('table'=>'faketable'));
        $this->assertEquals(false, $job->run());
        $job = new SqlUpdate($data);
        $job->run();
    }
}

