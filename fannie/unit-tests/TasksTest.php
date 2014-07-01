<?php

/**
 * @backupGlobals disabled
 */
class TasksTest extends PHPUnit_Framework_TestCase
{
    public function testTasks()
    {
        $tasks = FannieAPI::listModules('FannieTask', true);

        foreach($tasks as $task_class) {
            $obj = new $task_class();
        }
    }

}

