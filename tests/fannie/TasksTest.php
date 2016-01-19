<?php

/**
 * @backupGlobals disabled
 */
class TasksTest extends PHPUnit_Framework_TestCase
{
    public function testTasks()
    {
        $dbc = FannieDB::forceReconnect(FannieConfig::config('OP_DB'));
        $tasks = FannieAPI::listModules('FannieTask', true);

        foreach($tasks as $task_class) {
            $obj = new $task_class();
        }
    }

    public function testTransactionArchiving()
    {
        $config = FannieConfig::factory();
        $logger = new FannieLogger();
        $task = new TransArchiveTask();
        $task->setConfig($config);
        $task->setLogger($logger);

        $GLOBALS['FANNIE_ARCHIVE_METHOD'] = 'tables';

        /**
          Put a record in dtransactions that should trigger
          a new monthly table & view
        */
        $dtrans = new DTransactionsModel(FannieDB::get($config->get('TRANS_DB')));
        $dtrans->datetime('1901-01-01 00:00:00');
        $dtrans->save();

        ob_start();
        $task->run();
        ob_end_clean();

        /**
          Verify the task created new monthly table & view
        */
        $archive_db = FannieDB::get($config->get('ARCHIVE_DB'));
        $archive_table_exists = $archive_db->tableExists('transArchive190101');
        $archive_dlog_exists = $archive_db->tableExists('dlog190101');
        $this->assertEquals(true, $archive_table_exists, 'Monthly archive table not created');
        $this->assertEquals(true, $archive_dlog_exists, 'Monthly dlog view not created');

        /**
          Verify dtransactions was cleared
        */
        $trans_db = FannieDB::get($config->get('TRANS_DB'));
        $records = $trans_db->query('SELECT * FROM dtransactions');
        $this->assertEquals(0, $trans_db->num_rows($records), 'dtransactions not cleared');
    }

    public function testPatronageChecks()
    {
        $config = FannieConfig::factory();
        $task = new PatronageCheckTask();

        $dbc = FannieDB::get($config->get('OP_DB'));
        $dbc->query('TRUNCATE TABLE patronage');
        $p = new PatronageModel($dbc);
        $p->cardno(1);
        $p->FY(2000);
        $p->check_number(1);
        $p->cashed_date(null);
        $p->cashed_here(0);
        $p->save();

        $dbc = FannieDB::get($config->get('TRANS_DB'));
        $dbc->query('TRUNCATE TABLE dlog_15');
        $d = new DLog15Model($dbc);
        $d->tdate('2000-01-01 00:00:00');
        $d->trans_type('T');
        $d->description('REBATE CHECK');
        $d->total(1.23);
        $d->card_no(1);
        $d->save();

        $task->run();

        $dbc = FannieDB::get($config->get('OP_DB'));
        $p->reset();
        $p->cardno(1);
        $p->FY(2000);

        $loaded = $p->load();
        $this->assertEquals(true, $loaded, 'Failed to load patronage record');
        $this->assertEquals('2000-01-01 00:00:00', $p->cashed_date(), 'Cashed date missing');
        $this->assertEquals(1, $p->cashed_here(), 'Not marked as cashed');
    }

    public function testArHistory()
    {
        $config = FannieConfig::factory();
        $logger = new FannieLogger();
        $task = new ArHistoryTask();
        $task->setConfig($config);
        $task->setLogger($logger);
        ob_start();
        $task->run();
        ob_end_clean();
    }

    public function testAutoPars()
    {
        $config = FannieConfig::factory();
        $logger = new FannieLogger();
        $task = new AutoParsTask();
        $task->setConfig($config);
        $task->setLogger($logger);
        $task->testMode(true);
        $task->run();
    }

    public function testEquityHistory()
    {
        $config = FannieConfig::factory();
        $config->set('FANNIE_EQUITY_DEPARTMENTS', '1 2');
        $logger = new FannieLogger();
        $trans_db = $config->get('TRANS_DB');
        $dbc = FannieDB::get($trans_db);

        // create two test rows in dlog_15
        $today = date('Y-m-d');
        $trans_num = '1-1-1';
        $dlog = new DLog15Model($dbc);
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
        $dbc->selectDB($trans_db);
        $query = 'SELECT SUM(stockPurchase), COUNT(*) FROM stockpurchases WHERE card_no=1';
        $res = $dbc->query($query);
        $row = $dbc->fetchRow($res);
        $this->assertEquals(20, $row[0]);
        $this->assertEquals(2, $row[1]);

        // add a third test row
        $dlog->department(2);
        $dlog->trans_id(3);
        $dlog->save();
        $task->run();

        // verify only the new row is logged
        $dbc->selectDB($trans_db);
        $query = 'SELECT SUM(stockPurchase), COUNT(*) FROM stockpurchases WHERE card_no=1';
        $res = $dbc->query($query);
        $row = $dbc->fetchRow($res);
        $this->assertEquals(30, $row[0]);
        $this->assertEquals(3, $row[1]);
    }

    public function testInventory()
    {
        $config = FannieConfig::factory();
        $logger = new FannieLogger();
        $task = new InventoryTask();
        $task->setConfig($config);
        $task->setLogger($logger);
        $task->run();
    }

    public function testLastSold()
    {
        $config = FannieConfig::factory();
        $logger = new FannieLogger();
        $task = new LastSoldTask();
        $task->setConfig($config);
        $task->setLogger($logger);
        $task->run();
    }

    public function testNabs()
    {
        $config = FannieConfig::factory();
        $logger = new FannieLogger();
        $task = new NabsTask();
        $task->setConfig($config);
        $task->setLogger($logger);
        $task->run();
    }

    public function testNotInUse()
    {
        $config = FannieConfig::factory();
        $logger = new FannieLogger();
        $task = new NotInUseTask();
        $task->setConfig($config);
        $task->setLogger($logger);
        $task->run();
    }

    public function testOrderGen()
    {
        $config = FannieConfig::factory();
        $logger = new FannieLogger();
        $task = new OrderGenTask();
        $task->setConfig($config);
        $task->setLogger($logger);
        $task->run();
    }

    public function testPriceChange()
    {
        $config = FannieConfig::factory();
        $logger = new FannieLogger();
        $task = new PriceBatchTask();
        $task->setConfig($config);
        $task->setLogger($logger);
        ob_start();
        $task->run();
        ob_end_clean();
    }

    public function testProdUpdate()
    {
        $config = FannieConfig::factory();
        $logger = new FannieLogger();
        $task = new ProdUpdateMaintenanceTask();
        $task->setConfig($config);
        $task->setLogger($logger);
        $task->testMode(true);
        ob_start();
        $task->run();
        ob_end_clean();
    }

    public function testDataCache()
    {
        $config = FannieConfig::factory();
        $logger = new FannieLogger();
        $task = new ReportDataCacheTask();
        $task->setConfig($config);
        $task->setLogger($logger);
        $task->run();
    }

    public function testSameDay()
    {
        $config = FannieConfig::factory();
        $logger = new FannieLogger();
        $task = new SameDayReportingTask();
        $task->setConfig($config);
        $task->setLogger($logger);
        $task->run();
    }

    public function testSetDates()
    {
        $config = FannieConfig::factory();
        $logger = new FannieLogger();
        $task = new SetMemDatesTask();
        COREPOS\Fannie\API\member\MemberREST::testMode(true);
        $task->setConfig($config);
        $task->setLogger($logger);
        $task->run();
        COREPOS\Fannie\API\member\MemberREST::testMode(false);
    }

    public function testSO()
    {
        $config = FannieConfig::factory();
        $logger = new FannieLogger();
        $task = new SpecialOrdersTask();
        $task->setConfig($config);
        $task->setLogger($logger);
        ob_start();
        $task->run();
        ob_end_clean();
    }

    public function testSnapshot()
    {
        $config = FannieConfig::factory();
        $logger = new FannieLogger();
        $task = new TableSnapshotTask();
        $task->setConfig($config);
        $task->setLogger($logger);
        $task->run();
    }

    public function testVoid()
    {
        $config = FannieConfig::factory();
        $logger = new FannieLogger();
        $task = new VoidHistoryTask();
        $task->setConfig($config);
        $task->setLogger($logger);
        ob_start();
        $task->run();
        ob_end_clean();
    }

    public function testWeekSummarize()
    {
        $config = FannieConfig::factory();
        $logger = new FannieLogger();
        $task = new ProductSummarizeLastQuarter();
        $task->setConfig($config);
        $task->setLogger($logger);
        $task->testMode(true);
        ob_start();
        $task->run();
        ob_end_clean();
    }

    public function testSalesBatches()
    {
        $config = FannieConfig::factory();
        $logger = new FannieLogger();
        $task = new SalesBatchTask();
        $task->setConfig($config);
        $task->setLogger($logger);
        $task->testMode(true);
        ob_start();
        $task->run();
        ob_end_clean();
    }
}

