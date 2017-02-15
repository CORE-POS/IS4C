<?php

class CommonTest extends PHPUnit_Framework_TestCase
{
    public function testPlugin()
    {
        $plugin = new \COREPOS\common\CorePlugin();

        $plugin->pluginEnable();
        $plugin->pluginDisable();
        $plugin->settingChange();
        $this->assertEquals(false, $plugin->pluginUrl());

        $dir = realpath(dirname(__FILE__) . '/../../common');
        $this->assertEquals($dir, $plugin->pluginDir());

        $file = dirname(dirname(__FILE__) . '/../../pos/is4c-nf/plugins/Paycards/lib/PaycardLib.php');
        $this->assertEquals('Paycards', \COREPOS\common\CorePlugin::memberOf($file));
        $this->assertEquals(false, \COREPOS\common\CorePlugin::memberOf(__FILE__));
        $this->assertEquals(false, \COREPOS\common\CorePlugin::isEnabled('foo'));
    }

    public function testContainers()
    {
        $v = new \COREPOS\common\mvc\ValueContainer();
        $v->one = 1;
        $v->two = 2;
        $this->assertEquals(1, $v->one);
        $this->assertEquals(true, isset($v->two));
        $this->assertEquals(false, isset($v->three));

        $this->assertEquals(1, $v->current());
        $this->assertEquals('one', $v->key());
        $this->assertEquals(true, $v->valid());
        $v->next();
        $this->assertEquals(2, $v->current());
        $this->assertEquals('two', $v->key());
        $this->assertEquals(true, $v->valid());
        $v->next();
        $this->assertEquals(false, $v->valid());
        $v->rewind();
        $this->assertEquals(1, $v->current());
        $this->assertEquals('one', $v->key());
        $this->assertEquals(true, $v->valid());
        unset($v->one);
        $this->assertEquals(2, $v->current());
        $this->assertEquals('two', $v->key());
        $this->assertEquals(true, $v->valid());
    }

    public function testPages()
    {
        $page = new \COREPOS\common\ui\CorePage();
        $this->assertEquals($page->bodyContent(), $page->body_content());
        ob_start();
        $page->drawPage();
        $this->assertNotEquals('', ob_get_clean());
        ob_start();
        $page->draw_page();
        $this->assertNotEquals('', ob_get_clean());
        $this->assertEquals(null, $page->errorContent());

        $router = new \COREPOS\common\ui\CoreRESTfulRouter();
        $router->unitTest($this);
        $router->addRoute('get<id><id2>');
        ob_start();
        $this->assertEquals(false, $router->handler($page));
        ob_get_clean();

        $page->baseTest($this);
    }

    public function testLogger()
    {
        $bl = new \COREPOS\common\BaseLogger();
        $this->assertEquals(false, $bl->verboseDebugging());
        $this->assertEquals('/dev/null', $bl->getLogLocation(0));
        $bl->log(0, ''); // interface method
        $bl->setRemoteSyslog('127.0.0.1');
    }

    public function testModel()
    {
        $m = new \COREPOS\common\BasicModel(null);
        $this->assertEquals(null, $m->db());
        $this->assertEquals('', $m->preferredDB());
        $m->setConfig(null);
        $m->setConnection(null);
        try {
            $m->foo();
        } catch (Exception $ex) {
            $this->assertEquals(true, $ex instanceof Exception);
        }
        $this->assertEquals(false, $m->load());
        $this->assertEquals(array(), $m->getColumns());
        $this->assertEquals(null, $m->getName());
        $this->assertEquals(null, $m->getFullQualifiedName());
        $m->setFindLimit(100);
        $this->assertEquals(false, $m->delete());

        ob_start();
        $this->assertEquals(false, $m->normalize('foo', 99));
        ob_get_clean();

        $here = getcwd();
        chdir(sys_get_temp_dir());
        $m->newModel('FooBarTestModel');
        $this->assertEquals(true, file_exists('FooBarTestModel.php'));
        include('FooBarTestModel.php');
        $this->assertEquals(true, class_exists('FooBarTestModel', false));
        //unlink('FooBarTestModel.php');
        chdir($here);

        $this->assertEquals('[]', $m->toJSON());
        $this->assertEquals('', $m->toOptions());
        $this->assertNotEquals(0, strlen($m->columnsDoc()));
        $this->assertEquals(array(), $m->getModels());
        $m->setConnectionByName('foo'); // interface method

        ob_start();
        $this->assertEquals(1, $m->cli(0, array()));
        ob_get_clean();
    }

    public function testModelInstance()
    {
        include(dirname(__FILE__) . '/../../fannie/config.php');
        $dbc = new \COREPOS\common\SQLManager($FANNIE_SERVER, $FANNIE_SERVER_DBMS, $FANNIE_OP_DB, $FANNIE_SERVER_USER, $FANNIE_SERVER_PW, true);
        if (!class_exists('CommonTestModel', false)) {
            include(dirname(__FILE__) . '/TestModel.php');
        }
        $obj = new CommonTestModel($dbc);
        $obj->unitTest($this, $dbc);
    }

    public function testSQL()
    {
        include(dirname(__FILE__) . '/../../fannie/config.php');
        $dbc = new \COREPOS\common\SQLManager($FANNIE_SERVER, $FANNIE_SERVER_DBMS, $FANNIE_OP_DB, $FANNIE_SERVER_USER, $FANNIE_SERVER_PW, true);
        $dbc->throwOnFailure(true);
        $this->assertEquals($FANNIE_OP_DB, $dbc->defaultDatabase());

        $this->assertEquals(false, $dbc->addConnection($FANNIE_SERVER, '', $FANNIE_TRANS_DB, $FANNIE_SERVER_USER, $FANNIE_SERVER_PW));
        $this->assertEquals(true, $dbc->addConnection($FANNIE_SERVER, $FANNIE_SERVER_DBMS, $FANNIE_TRANS_DB, $FANNIE_SERVER_USER, $FANNIE_SERVER_PW));

        $this->assertEquals(true, $dbc->isConnected());
        $this->assertEquals(true, $dbc->isConnected($FANNIE_TRANS_DB));
        $this->assertEquals(false, $dbc->isConnected('foo'));

        $this->assertNotEquals('unknown', $dbc->connectionType());
        $this->assertEquals('unknown', $dbc->connectionType('foo'));

        $this->assertEquals(false, $dbc->setDefaultDB('foo'));
        $this->assertEquals(true, $dbc->setDefaultDB($FANNIE_TRANS_DB));

        $res = $dbc->queryAll('SELECT 1 AS one');
        $this->assertNotEquals(false, $res);
        $this->assertEquals(1, $dbc->numRows($res));
        $this->assertEquals(false, $dbc->numRows(false));
        $this->assertEquals(true, $dbc->dataSeek($res, 0));

        $res = $dbc->query('SELECT ' . $dbc->curtime() . ' AS val');
        $this->assertNotEquals(false, $res);

        $dbc->startTransaction();
        $dbc->query('SELECT 1 AS one');
        $dbc->commitTransaction();
        $dbc->startTransaction();
        $dbc->query('SELECT 1 AS one');
        $dbc->rollbackTransaction();

        $query = 'SELECT * FROM dlog WHERE trans_id=?';
        $arg_sets = array(array(1), array(2), array(3));
        $this->assertEquals(true, $dbc->executeAsTransaction($query, $arg_sets));

        $res = $dbc->query('SELECT ' . $dbc->week($dbc->now()) . ' AS val');
        $this->assertNotEquals(false, $res);

        $this->assertEquals(true, $dbc->isView('dlog'));
        $this->assertNotEquals(0, strlen($dbc->getViewDefinition('dlog')));

        $this->assertEquals(false, $dbc->tableDefinition('not_real_table'));
        $this->assertEquals(false, $dbc->detailedDefinition('not_real_table'));

        $tables = $dbc->getTables();
        $this->assertInternalType('array', $tables);

        $this->assertEquals($FANNIE_TRANS_DB, $dbc->defaultDatabase());

        $prep = $dbc->prepare('SELECT 1 AS one');
        $this->assertEquals(1, $dbc->getValue($prep));
        $this->assertNotEquals(0, count($dbc->getRow($prep)));
        $this->assertNotEquals(0, count($dbc->matchingColumns('dtransactions', 'suspended')));

        $badDef = array('not'=>'real');
        $this->assertEquals(true, $dbc->cacheTableDefinition('dtransactions', $badDef));
        $this->assertEquals($badDef, $dbc->tableDefinition('dtransactions'));
        $this->assertEquals(true, $dbc->clearTableCache());
        $this->assertNotEquals($badDef, $dbc->tableDefinition('dtransactions'));

        $this->assertNotEquals(false, $dbc->getMatchingColumns('dlog', $FANNIE_TRANS_DB, 'dlog', $FANNIE_TRANS_DB));
        $this->assertEquals(false, $dbc->getMatchingColumns('SpecialOrderDeptMap', $FANNIE_TRANS_DB, 'dlog', $FANNIE_TRANS_DB));
    }

    public function testWrapper()
    {
        include(dirname(__FILE__) . '/../../fannie/config.php');
        $dbc = new \COREPOS\common\SQLManager($FANNIE_SERVER, $FANNIE_SERVER_DBMS, $FANNIE_OP_DB, $FANNIE_SERVER_USER, $FANNIE_SERVER_PW, true);
        $wrap = new COREPOS\common\sql\ConnectionWrapper($dbc, $FANNIE_OP_DB);
        $res = $wrap->query('SELECT 1 AS one');
        $this->assertEquals(1, $wrap->numRows($res));
    }

    public function testSqlLib()
    {
        $this->assertInternalType('array', COREPOS\common\sql\Lib::getDrivers());
    }

    public function testAdapters()
    {
        $adapters = array('Mssql', 'Mysql', 'Pgsql', 'Sqlite');
        foreach ($adapters as $adapter) {
            $class = 'COREPOS\\common\\sql\\' . $adapter . 'Adapter';
            $obj = new $class();
            $this->assertInternalType('string', $obj->identifierEscape('foo'));
            $this->assertInternalType('string', $obj->defaultDatabase());
            $this->assertInternalType('string', $obj->temporaryTable('foo','bar'));
            $this->assertInternalType('string', $obj->sep());
            $this->assertInternalType('string', $obj->addSelectLimit('SELECT * FROM table', 5));
            $this->assertInternalType('string', $obj->currency());
            $this->assertInternalType('string', $obj->curtime());
            $this->assertInternalType('string', $obj->datediff('date1', 'date2'));
            $this->assertInternalType('string', $obj->monthdiff('date1', 'date2'));
            $this->assertInternalType('string', $obj->yeardiff('date1', 'date2'));
            $this->assertInternalType('string', $obj->weekdiff('date1', 'date2'));
            $this->assertInternalType('string', $obj->seconddiff('date1', 'date2'));
            $this->assertInternalType('string', $obj->dateymd('date1'));
            $this->assertInternalType('string', $obj->dayofweek('date1'));
            $this->assertInternalType('string', $obj->convert('date1','int'));
            $this->assertInternalType('string', $obj->locate('date1','te'));
            $this->assertInternalType('string', $obj->concat(array('1','2','3')));
        }
    }

    public function testCaches()
    {
        $caches = array(
            //'COREPOS\\common\\cache\\file\\CacheItemPool',
            'COREPOS\\common\\cache\\php\\CacheItemPool',
        );
        foreach ($caches as $class) {
            $pool = new $class('test.cache');
            $pool->clear();

            $item = $pool->getItem('foo');
            $this->assertEquals('foo', $item->getKey());
            $this->assertEquals(null, $item->get());
            $this->assertEquals(false, $item->isHit());

            $item->set('bar');
            $item->expiresAt(new DateTime());
            $item->expiresAt(null);
            $item->expiresAfter(0);
            $item->expiresAfter(null);
            $item->expiresAfter(new DateInterval('P1D'));
            $this->assertEquals(true, $pool->save($item));

            $item = $pool->getItem('foo');
            $this->assertEquals('foo', $item->getKey());
            $this->assertEquals('bar', $item->get());
            $this->assertEquals(true, $item->isHit());

            $items = $pool->getItems(array('foo'));
            $this->assertEquals($item, $items[0]);

            $this->assertEquals(true, $pool->hasItem('foo'));
            $this->assertEquals(true, $pool->deleteItem('foo'));
            $this->assertEquals(false, $pool->hasItem('foo'));

            $baz = $pool->getItem('baz');
            $baz->set('deferred');
            $this->assertEquals(true, $pool->saveDeferred($baz));
            $this->assertEquals(true, $pool->commit());
        }
    }
}

