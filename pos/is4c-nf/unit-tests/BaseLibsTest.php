<?php
/**
 * @backupGlobals disabled
 */
class BaseLibsTest extends PHPUnit_Framework_TestCase
{

	public function testMiscLib(){
		global $CORE_LOCAL;

		$here = getcwd();
		chdir(dirname(__FILE__).'/../gui-modules/');
		$rel = MiscLib::baseURL();
		$this->assertEquals('../',$rel);
		chdir($here);

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
		$fail = Authenticate::checkPassword('9999');
		$this->assertEquals(False, $fail);

		Database::setglobalvalue('CashierNo',9999);
		$pass = Authenticate::checkPassword('9999');
		$this->assertEquals(True, $pass);

		Database::setglobalvalue('LoggedIn',0);
		Database::setglobalvalue('CashierNo',1);
		$pass = Authenticate::checkPassword('9999');
		$this->assertEquals(True, $pass);
	}

	public function testAutoLoader(){
		global $CORE_LOCAL;
		
		AutoLoader::loadMap();
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

		$mods = AutoLoader::listModules('Parser');
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
		$file = dirname(__FILE__).'/../graphics/WFC_Logo.bmp';

		$this->assertFileExists($file);
		$bitmap = $ph->RenderBitmapFromFile($file);
		$this->assertInternalType('string',$bitmap);
		$this->assertNotEmpty($bitmap);
	}

	public function testCoreState(){
		global $CORE_LOCAL;

		// normal session init attempts to recover state
		// transaction info - e.g., after a browser crash
		// or reboot. Clear the table so that doesn't
		// happen
		$db = Database::tDataConnect();
		$db->query('TRUNCATE TABLE localtemptrans');

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

		$item = DisplayLib::printItem('name','weight','1.99','T',1);
		$this->assertInternalType('string',$item);
		$this->assertNotEmpty($item);

		$itemC = DisplayLib::printItemColor('004080','name','weight','1.99','T',2);
		$this->assertInternalType('string',$itemC);
		$this->assertNotEmpty($itemC);

		$itemH = DisplayLib::printItemColorHilite('004080','name','weight','1.99','T');
		$this->assertInternalType('string',$itemH);
		$this->assertNotEmpty($itemH);

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

		$list = DisplayLib::listItems(0,0);
		$this->assertInternalType('string',$list);

		$rf = DisplayLib::printReceiptFooter();
		$this->assertInternalType('string',$rf);

		$draw = DisplayLib::drawItems(0,11,0);
		$this->assertInternalType('string',$draw);

		$lp = DisplayLib::lastpage();
		$this->assertInternalType('string',$lp);

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

	public function testTransRecord(){
		global $CORE_LOCAL;

		if (!class_exists('lttLib')) include ('lttLib.php');
		lttLib::clear();

		$CORE_LOCAL->set('infoRecordQueue',array());
		TransRecord::addQueued('1234567890123','UNIT TEST',1,'UT',1.99);
		$queue = $CORE_LOCAL->get('infoRecordQueue');
		$this->assertInternalType('array',$queue);
		$this->assertEquals(1,count($queue));
		$this->assertArrayHasKey(0,$queue);
		$this->assertInternalType('array',$queue[0]);
		$this->assertArrayHasKey('upc',$queue[0]);
		$this->assertEquals('1234567890123',$queue[0]['upc']);
		$this->assertArrayHasKey('description',$queue[0]);
		$this->assertEquals('UNIT TEST',$queue[0]['description']);
		$this->assertArrayHasKey('numflag',$queue[0]);
		$this->assertEquals(1,$queue[0]['numflag']);
		$this->assertArrayHasKey('charflag',$queue[0]);
		$this->assertEquals('UT',$queue[0]['charflag']);
		$this->assertArrayHasKey('regPrice',$queue[0]);
		$this->assertEquals(1.99,$queue[0]['regPrice']);

		TransRecord::emptyQueue();
		$queue = $CORE_LOCAL->get('infoRecordQueue');
		$this->assertInternalType('array',$queue);
		$this->assertEquals(0,count($queue));
		$record = lttLib::genericRecord();
		$record['upc'] = '1234567890123';
		$record['description'] = 'UNIT TEST';
		$record['numflag'] = 1;
		$record['charflag'] = 'UT';
		$record['regPrice'] = 1.99;
		$record['trans_type'] = 'C';
		$record['trans_status'] = 'D';
		lttLib::verifyRecord(1, $record, $this);

		lttLib::clear();

		$CORE_LOCAL->set('taxTotal',1.23);
		TransRecord::addtax();
		$record = lttLib::genericRecord();
		$record['upc'] = 'TAX';
		$record['description'] = 'Tax';
		$record['trans_type'] = 'A';
		$record['total'] = 1.23;
		lttLib::verifyRecord(1, $record, $this);

		lttLib::clear();

		TransRecord::addtender('UT TENDER','UT',2.34);
		$record = lttLib::genericRecord();
		$record['description'] = 'UT TENDER';
		$record['trans_type'] = 'T';
		$record['trans_subtype'] = 'UT';
		$record['total'] = 2.34;
		lttLib::verifyRecord(1, $record, $this);

		lttLib::clear();

		TransRecord::addcomment('UNIT TEST COMMENT');
		$record = lttLib::genericRecord();
		$record['description'] = 'UNIT TEST COMMENT';
		$record['trans_type'] = 'C';
		$record['trans_subtype'] = 'CM';
		$record['trans_status'] = 'D';
		lttLib::verifyRecord(1, $record, $this);

		lttLib::clear();

		TransRecord::addchange(3.14,'UT');
		$record = lttLib::genericRecord();
		$record['description'] = 'Change';
		$record['trans_type'] = 'T';
		$record['trans_subtype'] = 'UT';
		$record['total'] = 3.14;
		$record['voided'] = 8;
		lttLib::verifyRecord(1, $record, $this);

		lttLib::clear();

		TransRecord::addfsones(3);
		$record = lttLib::genericRecord();
		$record['description'] = 'FS Change';
		$record['trans_type'] = 'T';
		$record['trans_subtype'] = 'FS';
		$record['total'] = 3;
		$record['voided'] = 8;
		lttLib::verifyRecord(1, $record, $this);

		lttLib::clear();

		TransRecord::adddiscount(5.45,25);
		$record = lttLib::genericRecord();
		$record['description'] = '** YOU SAVED $5.45 **';
		$record['trans_type'] = 'I';
		$record['trans_status'] = 'D';
		$record['department'] = 25;
		$record['voided'] = 2;
		lttLib::verifyRecord(1, $record, $this);

		lttLib::clear();

		TransRecord::addfsTaxExempt();
		$record = lttLib::genericRecord();
		$record['upc'] = 'FS Tax Exempt';
		$record['description'] = ' Fs Tax Exempt ';
		$record['trans_type'] = 'C';
		$record['trans_status'] = 'D';
		$record['voided'] = 17;
		lttLib::verifyRecord(1, $record, $this);

		lttLib::clear();

		TransRecord::discountnotify(5);
		$record = lttLib::genericRecord();
		$record['description'] = '** 5% Discount Applied **';
		$record['trans_status'] = 'D';
		$record['voided'] = 4;
		lttLib::verifyRecord(1, $record, $this);

		lttLib::clear();

		TransRecord::addTaxExempt();
		$record = lttLib::genericRecord();
		$record['description'] = '** Order is Tax Exempt **';
		$record['trans_status'] = 'D';
		$record['voided'] = 10;
		$record['tax'] = 9;
		lttLib::verifyRecord(1, $record, $this);
		$this->assertEquals(1, $CORE_LOCAL->get('TaxExempt'));

		lttLib::clear();

		TransRecord::reverseTaxExempt();
		$record = lttLib::genericRecord();
		$record['description'] = '** Tax Exemption Reversed **';
		$record['trans_status'] = 'D';
		$record['voided'] = 10;
		$record['tax'] = 9;
		lttLib::verifyRecord(1, $record, $this);
		$this->assertEquals(0, $CORE_LOCAL->get('TaxExempt'));

		lttLib::clear();

		$CORE_LOCAL->set('casediscount',7);
		TransRecord::addcdnotify();
		$record = lttLib::genericRecord();
		$record['description'] = '** 7% Case Discount Applied';
		$record['trans_status'] = 'D';
		$record['voided'] = 6;
		lttLib::verifyRecord(1, $record, $this);

		lttLib::clear();

		TransRecord::addCoupon('0051234512345',123,-1.23,1);
		$record = lttLib::genericRecord();
		$record['upc'] = '0051234512345';
		$record['description'] = ' * Manufacturers Coupon';
		$record['trans_type'] = 'I';
		$record['trans_subtype'] = 'CP';
		$record['trans_status'] = 'C';
		$record['department'] = 123;
		$record['unitPrice'] = -1.23;
		$record['total'] = -1.23;
		$record['regPrice'] = -1.23;
		$record['foodstamp'] = 1;
		$record['quantity'] = 1;
		$record['ItemQtty'] = 1;
		lttLib::verifyRecord(1, $record, $this);

		lttLib::clear();

		TransRecord::addhousecoupon('0049999912345',122,-1.22);
		$record = lttLib::genericRecord();
		$record['upc'] = '0049999912345';
		$record['description'] = ' * Store Coupon';
		$record['trans_type'] = 'I';
		$record['trans_subtype'] = 'IC';
		$record['trans_status'] = 'C';
		$record['department'] = 122;
		$record['unitPrice'] = -1.22;
		$record['total'] = -1.22;
		$record['regPrice'] = -1.22;
		$record['quantity'] = 1;
		$record['ItemQtty'] = 1;
		lttLib::verifyRecord(1, $record, $this);

		lttLib::clear();

		TransRecord::additemdiscount(345,3.45);
		$record = lttLib::genericRecord();
		$record['upc'] = 'ITEMDISCOUNT';
		$record['description'] = ' * Item Discount';
		$record['trans_type'] = 'I';
		$record['department'] = 345;
		$record['unitPrice'] = -3.45;
		$record['total'] = -3.45;
		$record['regPrice'] = -3.45;
		$record['quantity'] = 1;
		$record['ItemQtty'] = 1;
		lttLib::verifyRecord(1, $record, $this);

		lttLib::clear();

		TransRecord::addtare(5);
		$record = lttLib::genericRecord();
		$record['description'] = '** Tare Weight 0.05 **';
		$record['trans_status'] = 'D';
		$record['voided'] = 6;
		lttLib::verifyRecord(1, $record, $this);
		$this->assertEquals(0.05, $CORE_LOCAL->get('tare'));

		lttLib::clear();

		$CORE_LOCAL->set('transDiscount',3.24);
		TransRecord::addTransDiscount();
		$record = lttLib::genericRecord();
		$record['upc'] = 'DISCOUNT';
		$record['description'] = 'Discount';
		$record['trans_type'] = 'I';
		$record['quantity'] = 1;
		$record['ItemQtty'] = 1;
		$record['unitPrice'] = -3.24;
		$record['total'] = -3.24;
		lttLib::verifyRecord(1, $record, $this);

		lttLib::clear();

		TransRecord::addCashDrop('90.78');
		$record = lttLib::genericRecord();
		$record['upc'] = 'DROP';
		$record['description'] = 'Cash Drop';
		$record['trans_type'] = 'I';
		$record['trans_status'] = 'X';
		$record['quantity'] = 1;
		$record['ItemQtty'] = 1;
		$record['unitPrice'] = -90.78;
		$record['total'] = -90.78;
		$record['charflag'] = 'CD';
		lttLib::verifyRecord(1, $record, $this);

		lttLib::clear();

		$record = lttLib::genericRecord();
		$record['upc'] = 'UNITTEST';
		$record['description'] = 'Unit Test';
		$record['department'] = 5;
		$record['numflag'] = 4;
		$record['charflag'] = 'UT';
		$record['amount1'] = 1.23;
		$record['total'] = 1.23;
		$record['amount2'] = 1.24;
		$record['regPrice'] = 1.24;
		TransRecord::add_log_record($record);
		unset($record['amount1']); // not real column
		unset($record['amount2']); // not real column
		$record['trans_type'] = 'L';
		$record['trans_subtype'] = 'OG';
		$record['trans_status'] = 'X';
		lttLib::verifyRecord(1, $record, $this);

		lttLib::clear();
	}

	public function testPrehLib(){
		global $CORE_LOCAL;

		if (!class_exists('lttLib')) include ('lttLib.php');
		lttLib::clear();
		
		TransRecord::addcomment('peek');
		$peek = PrehLib::peekItem();
		$this->assertEquals('peek',$peek);

		lttLib::clear();

		$CORE_LOCAL->set('percentDiscount',5);
		$CORE_LOCAL->set('transDiscount',0.51);
		$CORE_LOCAL->set('taxTotal',1.45);
		$CORE_LOCAL->set('fsTaxExempt',1.11);
		$CORE_LOCAL->set('amtdue',9.55);
		// should add four records
		PrehLib::finalttl();

		// verify discount record
		$record = lttLib::genericRecord();
		$record['description'] = 'Discount';
		$record['trans_type'] = 'C';
		$record['trans_status'] = 'D';
		$record['unitPrice'] = -0.51;
		$record['voided'] = 5;
		lttLib::verifyRecord(1, $record, $this);

		// verify subtotal record
		$record = lttLib::genericRecord();
		$record['upc'] = 'Subtotal';
		$record['description'] = 'Subtotal';
		$record['trans_type'] = 'C';
		$record['trans_status'] = 'D';
		$record['unitPrice'] = 0.34;
		$record['voided'] = 11;
		lttLib::verifyRecord(2, $record, $this);

		// verify fs tax exempt record
		$record = lttLib::genericRecord();
		$record['upc'] = 'Tax';
		$record['description'] = 'FS Taxable';
		$record['trans_type'] = 'C';
		$record['trans_status'] = 'D';
		$record['unitPrice'] = 1.11;
		$record['voided'] = 7;
		lttLib::verifyRecord(3, $record, $this);

		// verify total record
		$record = lttLib::genericRecord();
		$record['upc'] = 'Total';
		$record['description'] = 'Total';
		$record['trans_type'] = 'C';
		$record['trans_status'] = 'D';
		$record['unitPrice'] = 9.55;
		$record['voided'] = 11;
		lttLib::verifyRecord(4, $record, $this);

		lttLib::clear();
	}

}
?>
