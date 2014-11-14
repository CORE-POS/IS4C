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

    public function testTransactionArchiving()
    {
        $task = new TransArchiveTask();

        /**
          Point references at the unit test databases
        */
        $GLOBALS['FANNIE_OP_DB'] = 'unit_test_op';
        $GLOBALS['FANNIE_TRANS_DB'] = 'unit_test_trans';
        $GLOBALS['FANNIE_ARCHIVE_DB'] = 'unit_test_archive';
        $GLOBALS['FANNIE_ARCHIVE_METHOD'] = 'tables';

        /**
          Put a record in dtransactions that should trigger
          a new monthly table & view
        */
        $dtrans = new DTransactionsModel(FannieDB::get('unit_test_trans'));
        $dtrans->datetime('2999-01-01 00:00:00');
        $dtrans->save();

        $task->run();

        /**
          Verify the task created new monthly table & view
        */
        $archive_db = FannieDB::get('unit_test_archive');
        $archive_table_exists = $archive_db->tableExists('transArchive299901');
        $archive_dlog_exists = $archive_db->tableExists('dlog299901');
        $this->assertEquals('true', $archive_table_exists, 'Monthly archive table not created');
        $this->assertEquals('true', $archive_dlog_exists, 'Monthly dlog view not created');

        /**
          Verify dtransactions was cleared
        */
        $trans_db = FannieDB::get('unit_test_trans');
        $records = $db->query('SELECT * FROM dtransactions');
        $this->assertEquals(0, $trans_db->num_rows($records), 'dtransactions not cleared');
    }

}

