<?php
/**
 * @backupGlobals disabled
 */
class BaseLibsTest extends PHPUnit_Framework_TestCase
{

	public function testMiscLib(){
		global $CORE_LOCAL;

		$here = getcwd();
		chdir(dirname(__FILE__).'/../../pos/is4c-nf/gui-modules/');
		$rel = MiscLib::base_url();
		$this->assertEquals('../',$rel);
		chdir($here);

		$this->assertEquals(5, MiscLib::int(5.1));
		$this->assertEquals(10, MiscLib::int("10"));

		$this->assertEquals(1, MiscLib::nullwrap(1));
		$this->assertEquals(1.5, MiscLib::nullwrap(1.5));
		$this->assertEquals('test', MiscLib::nullwrap('test'));
		$this->assertEquals(0, MiscLib::nullwrap(False));

		$this->assertEquals(1, MiscLib::truncate2(1));
		$this->assertEquals(1.99, MiscLib::truncate2(1.99));
		$this->assertEquals(1.99, MiscLib::truncate2("1.99"));
		$this->assertEquals(1.35, MiscLib::truncate2("1.345"));

		$hostCheck = MiscLib::pingport($CORE_LOCAL->get('localhost'),$CORE_LOCAL->get('DBMS'));
		$this->assertInternalType('integer', $hostCheck);

		$hostCheck = MiscLib::win32();
		$this->assertInternalType('integer', $hostCheck);

		$scale = MiscLib::scaleObject();
		if ($scale !== 0){
			$this->assertInstanceOf('ScaleDriverWrapper', $scale);
		}
	}

	public function testDatabase(){
		global $CORE_LOCAL;
		
		$db = Database::tDataConnect();
		$this->assertInstanceOf('SQLManager', $db);
		$this->assertEquals($CORE_LOCAL->get('tDatabase'), $db->default_db);
		$db = Database::pDataConnect();
		$this->assertInstanceOf('SQLManager', $db);
		$this->assertEquals($CORE_LOCAL->get('pDatabase'), $db->default_db);

		$this->assertEquals(1, Database::gettransno(-1)); // not a real emp_no

		$db = Database::tDataConnect();
		$matches = Database::localMatchingColumns($db, 'localtrans', 'localtemptrans');
		$this->assertInternalType('string', $matches);
		$this->assertRegExp('/(.+)/',$matches);

		$globals = array(
			'CashierNo' => 9999,
			'cashier' => 'TRAINING',
			'LoggedIn' => 0,
			'TransNo' => 1,
			'TTLFlag' => 0,
			'FntlFlag' => 0,
			'TaxExempt' => 0
		);
		Database::setglobalvalues($globals);
		$this->assertEquals(9999, $CORE_LOCAL->get('CashierNo'));
		$this->assertEquals('TRAINING', $CORE_LOCAL->get('cashier'));
		$this->assertEquals(0, $CORE_LOCAL->get('LoggedIn'));
		$this->assertEquals(1, $CORE_LOCAL->get('transno'));
		$this->assertEquals(0, $CORE_LOCAL->get('ttlflag'));
		$this->assertEquals(0, $CORE_LOCAL->get('fntlflag'));
		$this->assertEquals(0, $CORE_LOCAL->get('TaxExempt'));
		Database::loadglobalvalues(); // reload session from db. shouldn't change.
		$this->assertEquals(9999, $CORE_LOCAL->get('CashierNo'));
		$this->assertEquals('TRAINING', $CORE_LOCAL->get('cashier'));
		$this->assertEquals(0, $CORE_LOCAL->get('LoggedIn'));
		$this->assertEquals(1, $CORE_LOCAL->get('transno'));
		$this->assertEquals(0, $CORE_LOCAL->get('ttlflag'));
		$this->assertEquals(0, $CORE_LOCAL->get('fntlflag'));
		$this->assertEquals(0, $CORE_LOCAL->get('TaxExempt'));
		Database::setglobalvalue('TTLFlag',1);
		Database::loadglobalvalues();
		$this->assertEquals(1, $CORE_LOCAL->get('ttlflag'));
		Database::setglobalflags(0);
		Database::loadglobalvalues();
		$this->assertEquals(0, $CORE_LOCAL->get('ttlflag'));
		$this->assertEquals(0, $CORE_LOCAL->get('fntlflag'));
	}

	public function testAuthenticate(){
		global $CORE_LOCAL;
		$CORE_LOCAL->set('scaleDriver',''); // don't interact w/ scale

		Database::setglobalvalue('LoggedIn',1);
		Database::setglobalvalue('CashierNo',1);
		$fail = Authenticate::check_password('9999');
		$this->assertEquals(False, $fail);

		Database::setglobalvalue('CashierNo',9999);
		$pass = Authenticate::check_password('9999');
		$this->assertEquals(True, $pass);

		Database::setglobalvalue('LoggedIn',0);
		Database::setglobalvalue('CashierNo',1);
		$pass = Authenticate::check_password('9999');
		$this->assertEquals(True, $pass);
	}
}
?>
