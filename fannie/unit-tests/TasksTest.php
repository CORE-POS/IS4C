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
        $dtrans->datetime('1901-01-01 00:00:00');
        $dtrans->save();

        $task->run();

        /**
          Verify the task created new monthly table & view
        */
        $archive_db = FannieDB::get('unit_test_archive');
        $archive_table_exists = $archive_db->tableExists('transArchive190101');
        $archive_dlog_exists = $archive_db->tableExists('dlog190101');
        $this->assertEquals(true, $archive_table_exists, 'Monthly archive table not created');
        $this->assertEquals(true, $archive_dlog_exists, 'Monthly dlog view not created');

        /**
          Verify dtransactions was cleared
        */
        $trans_db = FannieDB::get('unit_test_trans');
        $records = $trans_db->query('SELECT * FROM dtransactions');
        $this->assertEquals(0, $trans_db->num_rows($records), 'dtransactions not cleared');
    }

    public function testPatronageChecks()
    {
        $task = new PatronageCheckTask();

        $dbc = FannieDB::get('unit_test_op');
        $dbc->query('TRUNCATE TABLE patronage');
        $p = new PatronageModel($dbc);
        $p->cardno(1);
        $p->FY(2000);
        $p->check_number(1);
        $p->cashed_date(null);
        $p->cashed_here(0);
        $p->save();

        $dbc = FannieDB::get('unit_test_trans');
        $dbc->query('TRUNCATE TABLE dlog_15');
        $d = new DLog15Model($dbc);
        $d->tdate('2000-01-01 00:00:00');
        $d->trans_type('T');
        $d->description('REBATE CHECK');
        $d->total(1.23);
        $d->card_no(1);
        $d->save();

        /**
          Point references at the unit test databases
        */
        $GLOBALS['FANNIE_OP_DB'] = 'unit_test_op';
        $GLOBALS['FANNIE_TRANS_DB'] = 'unit_test_trans';
        $task->run();

        $dbc = FannieDB::get('unit_test_op');
        $p->reset();
        $p->cardno(1);
        $p->FY(2000);

        $loaded = $p->load();
        $this->assertEquals(true, $loaded, 'Failed to load patronage record');
        $this->assertEquals('2000-01-01 00:00:00', $p->cashed_date(), 'Cashed date missing');
        $this->assertEquals(1, $p->cashed_here(), 'Not marked as cashed');
    }

    public function testEquityHistory()
    {
        $config = FannieConfig::factory();
        $config->set('FANNIE_EQUITY_DEPARTMENTS', '1 2');
        $logger = new FannieLogger();
        $dbc = FannieDB::get('unit_test_op');

        // create two test rows in dlog_15
        $today = date('Y-m-d');
        $trans_num = '1-1-1';
        $dlog = new Dlog15Model($dbc);
        $dlog->tdate($today); 
        $dlog->trans_num($trans_num);
        $dlog->department(1);
        $dlog->total(10);
        $dlog->card_no(1);
        $dlog->trans_id(1);
        $dlog->save();
        $dlog->trans_id(2);
        $dlog->save();

        $task = new EquityHistoryTask();
        $task->setConfig($config);
        $task->setLogger($logger);
        $task->run();

        // verify test rows were logged
        $query = 'SELECT SUM(stockPurchase), COUNT(*) FROM stockpurchases WHERE card_no=1';
        $res = $dbc->query($query);
        $row = $dbc->fetchRow($res);
        $this->assertEquals(20, $row[0]);
        $this->assertEquals(2, $row[1]);

        // add a third test row
        $dlog->department(2);
        $dlog->trans_id(3);
        $dlog->save();

        // verify only the new row is logged
        $query = 'SELECT SUM(stockPurchase), COUNT(*) FROM stockpurchases WHERE card_no=1';
        $res = $dbc->query($query);
        $row = $dbc->fetchRow($res);
        $this->assertEquals(30, $row[0]);
        $this->assertEquals(3, $row[1]);
    }

}

