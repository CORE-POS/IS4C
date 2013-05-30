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

	public function testAutoLoader(){
		global $CORE_LOCAL;
		
		AutoLoader::LoadMap();
		$class_map = $CORE_LOCAL->get('ClassLookup');
		$this->assertInternalType('array', $class_map);
		$this->assertNotEmpty($class_map);
		
		/**
		  Verify base classes and required libraries
		  were properly discovered
		*/
		$required_classes = array(
			'AutoLoader',
			'Authenticate',
			'PreParser',
			'Parser',
			'SQLManager',
			'BasicPage',
			'TenderModule',
			'DisplayLib',
			'ReceiptLib',
			'Database',
			'Kicker',
			'SpecialUPC',
			'SpecialDept',
			'DiscountType',
			'PriceMethod',
			'LocalStorage',
			'FooterBox',
			'Plugin',
			'PrintHandler'
		);

		foreach($required_classes as $class){
			$this->assertArrayHasKey($class, $class_map);
			$this->assertFileExists($class_map[$class]);
		}

		$mods = AutoLoader::ListModules('Parser');
		$this->assertInternalType('array',$mods);
		$this->assertNotEmpty($mods);
		foreach($mods as $m){
			$obj = new $m();
			$this->assertInstanceOf('Parser',$obj);
		}
	}

	public function testBitmap(){
		global $CORE_LOCAL;

		/**
		  Using PrintHandler::RenderBitmapFromFile
		  will call all the methods of the Bitmap class
		  that actually get used
		*/

		$ph = new PrintHandler();
		$file = dirname(__FILE__).'/../../pos/is4c-nf/graphics/WFC_Logo.bmp';

		$this->assertFileExists($file);
		$bitmap = $ph->RenderBitmapFromFile($file);
		$this->assertInternalType('string',$bitmap);
		$this->assertNotEmpty($bitmap);
	}

	public function testCoreState(){
		global $CORE_LOCAL;

		/**
		  This will trigger any syntax or run-time errors
		  Testing all the invidual values of CORE_LOCAL
		  might be worthwhile is anyone wants to write
		  all those tests out. They're mostly static values
		  so the test would only catch changes to the
		  defaults.
		*/
		CoreState::initiate_session();

		$str = CoreState::getCustomerPref('asdf');
		$this->assertInternalType('string',$str);
		$this->assertEquals('',$str);
	}

	public function testDisplayLib(){
		global $CORE_LOCAL;

		$footerb = DisplayLib::printfooterb();
		$this->assertInternalType('string',$footerb);
		$this->assertNotEmpty($footerb);

		$footer = DisplayLib::printfooter();
		$this->assertInternalType('string',$footer);
		$this->assertNotEmpty($footer);

		$pmsg = DisplayLib::plainmsg('test message');
		$this->assertInternalType('string',$pmsg);
		$this->assertNotEmpty($pmsg);
		$this->assertContains('test message',$pmsg);

		$mbox = DisplayLib::msgbox('test msgbox','',True);
		$this->assertInternalType('string',$mbox);
		$this->assertNotEmpty($mbox);
		$this->assertContains('test msgbox',$mbox);

		$xbox = DisplayLib::xboxMsg('test xboxMsg');
		$this->assertInternalType('string',$xbox);
		$this->assertNotEmpty($xbox);
		$this->assertContains('test xboxMsg',$xbox);

		$bmsg = DisplayLib::boxMsg('test boxMsg','',True);
		$this->assertInternalType('string',$bmsg);
		$this->assertNotEmpty($bmsg);
		$this->assertContains('test boxMsg',$bmsg);

		$unk = DisplayLib::inputUnknown();
		$this->assertInternalType('string',$unk);
		$this->assertNotEmpty($unk);

		$headerb = DisplayLib::printheaderb();
		$this->assertInternalType('string',$headerb);
		$this->assertNotEmpty($headerb);

		$item = DisplayLib::printitem('name','weight','1.99','T',1);
		$this->assertInternalType('string',$item);
		$this->assertNotEmpty($item);

		$itemC = DisplayLib::printitem('name','weight','1.99','T',2);
		$this->assertInternalType('string',$itemC);
		$this->assertNotEmpty($itemC);

		$itemH = DisplayLib::printitemcolorhilite('004080','name','weight','1.99','T');
		$this->assertInternalType('string',$itemH);
		$this->assertNotEmpty($itemH);

		$itemH2 = DisplayLib::printItemHilite('name','weight','1.99','T');
		$this->assertInternalType('string',$itemH2);
		$this->assertNotEmpty($itemH2);
		$this->assertEquals($itemH,$itemH2);

		$CORE_LOCAL->set('weight',0);
		$CORE_LOCAL->set('scale',0);
		$CORE_LOCAL->set('SNR',0);

		$basic = DisplayLib::scaledisplaymsg();
		$this->assertInternalType('string',$basic);
		$this->assertEquals('0.00 lb',$basic);

		$scale_in_out = array(
			'S11000' => '0.00 lb',
			'S11001' => '0.01 lb',
			'S11' => '_ _ _ _',
			'S141' => '_ _ _ _',
			'S143' => '0.00 lb',
			'S145' => 'err -0',
			'S142' => 'error',
			'ASDF' => '? ? ? ?',
			'S144000' => '0.00 lb',
			'S144002' => '0.02 lb'
		);

		foreach($scale_in_out as $input => $output){
			$test = DisplayLib::scaledisplaymsg($input);
			$this->assertInternalType('array',$test);
			$this->assertArrayHasKey('display',$test);
			$this->assertEquals($output, $test['display']);
		}

		$this->assertEquals(1, $CORE_LOCAL->get('scale'));
		$this->assertEquals(0.02, $CORE_LOCAL->get('weight'));

		$CORE_LOCAL->set('SNR','4011');
		$both = DisplayLib::scaledisplaymsg('S11050');
		$this->assertInternalType('array',$both);
		$this->assertArrayHasKey('display',$both);
		$this->assertArrayHasKey('upc',$both);
		$this->assertEquals('0.50 lb',$both['display']);
		$this->assertEquals('4011',$both['upc']);

		$term = DisplayLib::termdisplaymsg();
		$this->assertInternalType('string',$term);

		$list = DisplayLib::listitems(0,0);
		$this->assertInternalType('string',$list);

		$rf = DisplayLib::printReceiptFooter();
		$this->assertInternalType('string',$rf);

		$draw = DisplayLib::drawitems(0,11,0);
		$this->assertInternalType('string',$draw);

		$lp = DisplayLib::lastpage();
		$this->assertInternalType('string',$lp);

		$this->assertEquals($lp,$draw);
		$this->assertEquals($lp,$list);
	}

	public function testJsonLib(){
		global $CORE_LOCAL;

		$test = array(
			0 => array(1, 2, 3),
			1 => 'test string',
			2 => 5,
			3 => '0006',
			4 => 9.7,
			5 => True,
			6 => "bad\\char\tacters"
		);

		$json = JsonLib::array_to_json($test);
		$good = "[[1,2,3],\"test string\",5,6,9.7,true,\"bad\\\\char\\tacters\"]";
		$this->assertInternalType('string',$json);
		$this->assertEquals($good,$json);
	}

	public function testUdpComm(){
		global $CORE_LOCAL;
		UdpComm::udpSend('most likely no one is listening...');
	}
}
?>
