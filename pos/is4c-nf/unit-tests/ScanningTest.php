<?php

/**
 * @backupGlobals disabled
 */
class ScanningTest extends PHPUnit_Framework_TestCase
{
	public function testDiscountType()
    {
		$defaults = array(
			'NormalPricing',
			'EveryoneSale',
			'MemberSale',
			'StaffSale',
			'SlidingMemSale',
			'PercentMemSale',
			'CasePriceDiscount'
		);

		$all = AutoLoader::ListModules('DiscountType',False);
		foreach($defaults as $d){
			$this->assertContains($d, $all);
		}

		foreach($all as $class){
			$obj = new $class();
			$this->assertInstanceOf('DiscountType',$obj);
			
			$this->assertInternalType('boolean',$obj->isSale());
			$this->assertInternalType('boolean',$obj->isMemberOnly());
			$this->assertInternalType('boolean',$obj->isMemberSale());
			$this->assertEquals($obj->isMemberOnly(),$obj->isMemberSale());
			$this->assertInternalType('boolean',$obj->isStaffOnly());
			$this->assertInternalType('boolean',$obj->isStaffSale());
			$this->assertEquals($obj->isStaffSale(),$obj->isStaffOnly());
		}

		CoreLocal::set('itemPD',0);
		$row = array('normal_price'=>1.99,'special_price'=>'1.49',
				'specialpricemethod'=>0,
				'specialquantity'=>0, 'line_item_discountable'=>1);

		$norm = new NormalPricing();
		$info = $norm->priceInfo($row, 1);
		$this->assertInternalType('array',$info);
		$this->assertArrayHasKey('regPrice',$info);
		$this->assertArrayHasKey('unitPrice',$info);
		$this->assertArrayHasKey('discount',$info);
		$this->assertArrayHasKey('memDiscount',$info);
		$this->assertEquals(1.99,$info['regPrice']);
		$this->assertEquals(1.99,$info['unitPrice']);
		$this->assertEquals(0,$info['discount']);
		$this->assertEquals(0,$info['memDiscount']);
		$this->assertEquals(False,$norm->isSale());
		$this->assertEquals(False,$norm->isMemberOnly());
		$this->assertEquals(False,$norm->isStaffOnly());

		$norm = new EveryoneSale();
		$info = $norm->priceInfo($row, 1);
		$this->assertInternalType('array',$info);
		$this->assertArrayHasKey('regPrice',$info);
		$this->assertArrayHasKey('unitPrice',$info);
		$this->assertArrayHasKey('discount',$info);
		$this->assertArrayHasKey('memDiscount',$info);
		$this->assertEquals(1.99,$info['regPrice']);
		$this->assertEquals(1.49,$info['unitPrice']);
		$this->assertEquals(0.50,$info['discount']);
		$this->assertEquals(0,$info['memDiscount']);
		$this->assertEquals(True,$norm->isSale());
		$this->assertEquals(False,$norm->isMemberOnly());
		$this->assertEquals(False,$norm->isStaffOnly());

		CoreLocal::set('isMember',1);
		$norm = new MemberSale();
		$info = $norm->priceInfo($row, 1);
		$this->assertInternalType('array',$info);
		$this->assertArrayHasKey('regPrice',$info);
		$this->assertArrayHasKey('unitPrice',$info);
		$this->assertArrayHasKey('discount',$info);
		$this->assertArrayHasKey('memDiscount',$info);
		$this->assertEquals(1.99,$info['regPrice']);
		$this->assertEquals(1.49,$info['unitPrice']);
		$this->assertEquals(0,$info['discount']);
		$this->assertEquals(0.50,$info['memDiscount']);
		$this->assertEquals(True,$norm->isSale());
		$this->assertEquals(True,$norm->isMemberOnly());
		$this->assertEquals(False,$norm->isStaffOnly());

		CoreLocal::set('isStaff',1);
		$norm = new StaffSale();
		$info = $norm->priceInfo($row, 1);
		$this->assertInternalType('array',$info);
		$this->assertArrayHasKey('regPrice',$info);
		$this->assertArrayHasKey('unitPrice',$info);
		$this->assertArrayHasKey('discount',$info);
		$this->assertArrayHasKey('memDiscount',$info);
		$this->assertEquals(1.99,$info['regPrice']);
		$this->assertEquals(1.49,$info['unitPrice']);
		$this->assertEquals(0,$info['discount']);
		$this->assertEquals(0.50,$info['memDiscount']);
		$this->assertEquals(True,$norm->isSale());
		$this->assertEquals(False,$norm->isMemberOnly());
		$this->assertEquals(True,$norm->isStaffOnly());

		$row['special_price'] = 0.10;
		$norm = new SlidingMemSale();
		$info = $norm->priceInfo($row, 1);
		$this->assertInternalType('array',$info);
		$this->assertArrayHasKey('regPrice',$info);
		$this->assertArrayHasKey('unitPrice',$info);
		$this->assertArrayHasKey('discount',$info);
		$this->assertArrayHasKey('memDiscount',$info);
		$this->assertEquals(1.99,$info['regPrice']);
		$this->assertEquals(1.89,$info['unitPrice']);
		$this->assertEquals(0,$info['discount']);
		$this->assertEquals(0.10,$info['memDiscount']);
		$this->assertEquals(True,$norm->isSale());
		$this->assertEquals(True,$norm->isMemberOnly());
		$this->assertEquals(False,$norm->isStaffOnly());

		$norm = new PercentMemSale();
		$info = $norm->priceInfo($row, 1);
		$this->assertInternalType('array',$info);
		$this->assertArrayHasKey('regPrice',$info);
		$this->assertArrayHasKey('unitPrice',$info);
		$this->assertArrayHasKey('discount',$info);
		$this->assertArrayHasKey('memDiscount',$info);
		$this->assertEquals(1.99,$info['regPrice']);
		$this->assertEquals(0.20,$info['unitPrice']);
		$this->assertEquals(0,$info['discount']);
		$this->assertEquals(1.79,$info['memDiscount']);
		$this->assertEquals(True,$norm->isSale());
		$this->assertEquals(True,$norm->isMemberOnly());
		$this->assertEquals(False,$norm->isStaffOnly());

		CoreLocal::set('isMember',0);
		CoreLocal::set('isStaff',0);
		$row['special_price'] = 1.49;

		$norm = new MemberSale();
		$info = $norm->priceInfo($row, 1);
		$this->assertEquals(1.99,$info['regPrice']);
		$this->assertEquals(1.99,$info['unitPrice']);
		$this->assertEquals(0,$info['discount']);
		$this->assertEquals(0.50,$info['memDiscount']);

		$norm = new StaffSale();
		$info = $norm->priceInfo($row, 1);
		$this->assertEquals(1.99,$info['regPrice']);
		$this->assertEquals(1.99,$info['unitPrice']);
		$this->assertEquals(0,$info['discount']);
		$this->assertEquals(0.50,$info['memDiscount']);

		$row['special_price'] = 0.10;
		$norm = new SlidingMemSale();
		$info = $norm->priceInfo($row, 1);
		$this->assertEquals(1.99,$info['regPrice']);
		$this->assertEquals(1.99,$info['unitPrice']);
		$this->assertEquals(0,$info['discount']);
		$this->assertEquals(0.10,$info['memDiscount']);

		$norm = new PercentMemSale();
		$info = $norm->priceInfo($row, 1);
		$this->assertEquals(1.99,$info['regPrice']);
		$this->assertEquals(1.99,$info['unitPrice']);
		$this->assertEquals(0,$info['discount']);
		$this->assertEquals(1.79,$info['memDiscount']);

		CoreLocal::set('casediscount',10);
		$norm = new CasePriceDiscount();
		$info = $norm->priceInfo($row, 1);
		$this->assertInternalType('array',$info);
		$this->assertArrayHasKey('regPrice',$info);
		$this->assertArrayHasKey('unitPrice',$info);
		$this->assertArrayHasKey('discount',$info);
		$this->assertArrayHasKey('memDiscount',$info);
		$this->assertEquals(1.79,$info['regPrice']);
		$this->assertEquals(1.79,$info['unitPrice']);
		$this->assertEquals(0,$info['discount']);
		$this->assertEquals(0,$info['memDiscount']);
		$this->assertEquals(False,$norm->isSale());
		$this->assertEquals(False,$norm->isMemberOnly());
		$this->assertEquals(False,$norm->isStaffOnly());
	}

	public function testSpecialUPCs() {
		global $CORE_LOCAL;

		$defaults = array(
			'CouponCode',
			'DatabarCoupon',
			'HouseCoupon',
			'MagicPLU',
			'SpecialOrder'
		);

		$all = AutoLoader::ListModules('SpecialUPC',False);
		foreach($defaults as $d){
			$this->assertContains($d, $all);
		}

		foreach($all as $class){
			$obj = new $class();
			$this->assertInstanceOf('SpecialUPC',$obj);
			$this->assertInternalType('boolean',$obj->is_special('silly nonsense input'));
		}

		$cc = new CouponCode();
		$this->assertEquals(True,$cc->is_special('0051234512345'));
		$this->assertEquals(True,$cc->is_special('0991234512345'));
		$this->assertEquals(False,$cc->is_special('0001234512345'));

		$dat = new DatabarCoupon();
		$this->assertEquals(True,$dat->is_special('811012345678901'));
		$this->assertEquals(False,$dat->is_special('8110123456790'));
		$this->assertEquals(False,$dat->is_special('0001234512345'));

		$hc = new HouseCoupon();
		$this->assertEquals(True,$hc->is_special('0049999900001'));
		$this->assertEquals(False,$hc->is_special('0001234512345'));

		$mp = new MagicPLU();
		$this->assertEquals(True,$mp->is_special('0000000008005'));
		$this->assertEquals(True,$mp->is_special('0000000008006'));
		$this->assertEquals(False,$mp->is_special('0001234512345'));

		$so = new SpecialOrder();
		$this->assertEquals(True,$so->is_special('0045400010001'));
		$this->assertEquals(False,$so->is_special('0001234512345'));
	}

    public function testHouseCoupons()
    {
		if (!class_exists('lttLib')) {
            include (dirname(__FILE__) . '/lttLib.php');
        }

        /**
          TEST 1: minType M, discountType Q
        */
        lttLib::clear();
        $upc = new UPC();
        $upc->parse('0000000000111');
        $upc->parse('0000000000234');

        $hc = new HouseCoupon();
        $hc->handle('0049999900001', array());
        $record = lttLib::genericRecord();
        $record['upc'] = '0049999900001';
        $record['description'] = 'MIXED+QUANTITY';
        $record['trans_type'] = 'I';
        $record['trans_subtype'] = 'IC';
        $record['trans_status'] = 'C';
        $record['quantity'] = 1;
        $record['ItemQtty'] = 1;
        $record['unitPrice'] = -2.39;
        $record['total'] = -2.39;
        $record['regPrice'] = -2.39;
		lttLib::verifyRecord(3, $record, $this);

        /**
          TEST 2: no minimum, discountType %D 
        */
        lttLib::clear();
        $upc = new UPC();
        $upc->parse('0000000001009');
        $upc->parse('0000000001011');

        $hc = new HouseCoupon();
        $hc->handle('0049999900002', array());
        $record = lttLib::genericRecord();
        $record['upc'] = '0049999900002';
        $record['description'] = '%DEPARTMENT';
        $record['trans_type'] = 'I';
        $record['trans_subtype'] = 'IC';
        $record['trans_status'] = 'C';
        $record['quantity'] = 1;
        $record['ItemQtty'] = 1;
        $record['unitPrice'] = -0.75;
        $record['total'] = -0.75;
        $record['regPrice'] = -0.75;
		lttLib::verifyRecord(3, $record, $this);

        /**
          TEST 3: minimum D, discountType F 
        */
        lttLib::clear();
        $dept = new DeptKey();
        $dept->parse('2300DP10');
        $dept->parse('200DP10');

        $hc = new HouseCoupon();
        $hc->handle('0049999900003', array());
        $record = lttLib::genericRecord();
        $record['upc'] = '0049999900003';
        $record['description'] = '5OFF25DEPT';
        $record['trans_type'] = 'I';
        $record['trans_subtype'] = 'IC';
        $record['trans_status'] = 'C';
        $record['quantity'] = 1;
        $record['ItemQtty'] = 1;
        $record['unitPrice'] = -5.00;
        $record['total'] = -5.00;
        $record['regPrice'] = -5.00;
		lttLib::verifyRecord(3, $record, $this);

        /**
          TEST 4: minimum MX, discountType F 
        */
        lttLib::clear();
        $dept = new DeptKey();
        $dept->parse('900DP10');
        $upc = new UPC();
        $upc->parse('0000000000234');

        $hc = new HouseCoupon();
        $hc->handle('0049999900004', array());
        $record = lttLib::genericRecord();
        $record['upc'] = '0049999900004';
        $record['description'] = 'MIXCROSS';
        $record['trans_type'] = 'I';
        $record['trans_subtype'] = 'IC';
        $record['trans_status'] = 'C';
        $record['quantity'] = 1;
        $record['ItemQtty'] = 1;
        $record['unitPrice'] = -1.00;
        $record['total'] = -1.00;
        $record['regPrice'] = -1.00;
		lttLib::verifyRecord(3, $record, $this);

        /**
          TEST 5: minType Q, discountType PI 
        */
        lttLib::clear();
        $upc = new UPC();
        $upc->parse('0000000000111');
        $upc->parse('0000000000234');

        $hc = new HouseCoupon();
        $hc->handle('0049999900005', array());
        $record = lttLib::genericRecord();
        $record['upc'] = '0049999900005';
        $record['description'] = 'PERITEM';
        $record['trans_type'] = 'I';
        $record['trans_subtype'] = 'IC';
        $record['trans_status'] = 'C';
        $record['quantity'] = 1;
        $record['ItemQtty'] = 1;
        $record['unitPrice'] = -1.00;
        $record['total'] = -1.00;
        $record['regPrice'] = -1.00;
		lttLib::verifyRecord(3, $record, $this);
    }

	public function testSpecialDepts()
    {
		$defaults = array(
			'ArWarnDept',
			'AutoReprintDept',
			'EquityEndorseDept',
			'EquityWarnDept'
		);

		$all = AutoLoader::ListModules('SpecialDept',False);
		foreach($defaults as $d){
			$this->assertContains($d, $all);
		}

		$map = array();
		foreach($all as $class){
			$obj = new $class();
			$this->assertInstanceOf('SpecialDept',$obj);
			$map = $obj->register(1,$map);
			$this->assertInternalType('array',$map);
			$this->assertArrayHasKey(1,$map);
			$this->assertInternalType('array',$map[1]);
			$this->assertContains($class,$map[1]);
		}

		CoreLocal::set('msgrepeat',0);

		// first call should set warn vars
		$arwarn = new ArWarnDept();
		$json = $arwarn->handle(1,1.00,array('main_frame'=>''));
		$this->assertInternalType('array',$json);
		$this->assertArrayHasKey('main_frame',$json);
		$this->assertInternalType('string',$json['main_frame']);
		$this->assertNotEmpty($json['main_frame']);

		CoreLocal::set('msgrepeat',1);

		// second call should clear vars and proceed
		$json = $arwarn->handle(1,1.00,array('main_frame'=>''));
		$this->assertInternalType('array',$json);
		$this->assertArrayHasKey('main_frame',$json);
		$this->assertInternalType('string',$json['main_frame']);
		$this->assertEmpty($json['main_frame']);

		CoreLocal::set('autoReprint',0);
		$auto = new AutoReprintDept();
		$json = $auto->handle(1,1.00,array());
		$this->assertInternalType('array',$json);
		$this->assertEquals(1, CoreLocal::get('autoReprint'));	

		CoreLocal::set('msgrepeat',0);
		CoreLocal::set('memberID',0);

		// error because member is required
		$eEndorse = new EquityEndorseDept();
		$json = $eEndorse->handle(1,1.00,array('main_frame'=>''));
		$this->assertInternalType('array',$json);
		$this->assertArrayHasKey('main_frame',$json);
		$this->assertInternalType('string',$json['main_frame']);
		$this->assertNotEmpty($json['main_frame']);

		// show endorse warning screen
		CoreLocal::set('memberID',123);
		$json = $eEndorse->handle(1,1.00,array('main_frame'=>''));
		$this->assertInternalType('array',$json);
		$this->assertArrayHasKey('main_frame',$json);
		$this->assertInternalType('string',$json['main_frame']);
		$this->assertNotEmpty($json['main_frame']);

		// clear warning and proceed
		CoreLocal::set('memberID',123);
		CoreLocal::set('msgrepeat', 1);
		$json = $eEndorse->handle(1,1.00,array('main_frame'=>''));
		$this->assertInternalType('array',$json);
		$this->assertArrayHasKey('main_frame',$json);
		$this->assertInternalType('string',$json['main_frame']);
		$this->assertEmpty($json['main_frame']);

		CoreLocal::set('memberID',0);
		CoreLocal::set('msgrepeat', 0);

		// error because member is required
		$eWarn = new EquityWarnDept();
		$json = $eWarn->handle(1,1.00,array('main_frame'=>''));
		$this->assertInternalType('array',$json);
		$this->assertArrayHasKey('main_frame',$json);
		$this->assertInternalType('string',$json['main_frame']);
		$this->assertNotEmpty($json['main_frame']);

		CoreLocal::set('memberID',123);

		// show warning screen
		CoreLocal::set('memberID',123);
		$json = $eWarn->handle(1,1.00,array('main_frame'=>''));
		$this->assertInternalType('array',$json);
		$this->assertArrayHasKey('main_frame',$json);
		$this->assertInternalType('string',$json['main_frame']);
		$this->assertNotEmpty($json['main_frame']);

		// clear warning and proceed
		CoreLocal::set('memberID',123);
		CoreLocal::set('msgrepeat', 1);
		$json = $eWarn->handle(1,1.00,array('main_frame'=>''));
		$this->assertInternalType('array',$json);
		$this->assertArrayHasKey('main_frame',$json);
		$this->assertInternalType('string',$json['main_frame']);
		$this->assertEmpty($json['main_frame']);
	}
}
