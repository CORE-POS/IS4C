<?php

/**
 * @backupGlobals disabled
 */
class ScanningTest extends PHPUnit_Framework_TestCase
{
	public function testDiscountType(){
		global $CORE_LOCAL;

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

		$CORE_LOCAL->set('itemPD',0);
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

		$CORE_LOCAL->set('isMember',1);
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

		$CORE_LOCAL->set('isStaff',1);
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

		$CORE_LOCAL->set('isMember',0);
		$CORE_LOCAL->set('isStaff',0);
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

		$CORE_LOCAL->set('casediscount',10);
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

	public function testSpecialDepts(){
		global $CORE_LOCAL;

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

		$CORE_LOCAL->set('msgrepeat',0);

		// first call should set warn vars
		$arwarn = new ArWarnDept();
		$json = $arwarn->handle(1,1.00,array('main_frame'=>''));
		$this->assertInternalType('array',$json);
		$this->assertArrayHasKey('main_frame',$json);
		$this->assertInternalType('string',$json['main_frame']);
		$this->assertNotEmpty($json['main_frame']);

		$CORE_LOCAL->set('msgrepeat',1);

		// second call should clear vars and proceed
		$json = $arwarn->handle(1,1.00,array('main_frame'=>''));
		$this->assertInternalType('array',$json);
		$this->assertArrayHasKey('main_frame',$json);
		$this->assertInternalType('string',$json['main_frame']);
		$this->assertEmpty($json['main_frame']);

		$CORE_LOCAL->set('autoReprint',0);
		$auto = new AutoReprintDept();
		$json = $auto->handle(1,1.00,array());
		$this->assertInternalType('array',$json);
		$this->assertEquals(1,$CORE_LOCAL->get('autoReprint'));	

		$CORE_LOCAL->set('msgrepeat',0);
		$CORE_LOCAL->set('memberID',0);

		// error because member is required
		$eEndorse = new EquityEndorseDept();
		$json = $eEndorse->handle(1,1.00,array('main_frame'=>''));
		$this->assertInternalType('array',$json);
		$this->assertArrayHasKey('main_frame',$json);
		$this->assertInternalType('string',$json['main_frame']);
		$this->assertNotEmpty($json['main_frame']);

		// show endorse warning screen
		$CORE_LOCAL->set('memberID',123);
		$json = $eEndorse->handle(1,1.00,array('main_frame'=>''));
		$this->assertInternalType('array',$json);
		$this->assertArrayHasKey('main_frame',$json);
		$this->assertInternalType('string',$json['main_frame']);
		$this->assertNotEmpty($json['main_frame']);

		// clear warning and proceed
		$CORE_LOCAL->set('memberID',123);
		$CORE_LOCAL->set('msgrepeat', 1);
		$json = $eEndorse->handle(1,1.00,array('main_frame'=>''));
		$this->assertInternalType('array',$json);
		$this->assertArrayHasKey('main_frame',$json);
		$this->assertInternalType('string',$json['main_frame']);
		$this->assertEmpty($json['main_frame']);

		$CORE_LOCAL->set('memberID',0);
		$CORE_LOCAL->set('msgrepeat', 0);

		// error because member is required
		$eWarn = new EquityWarnDept();
		$json = $eWarn->handle(1,1.00,array('main_frame'=>''));
		$this->assertInternalType('array',$json);
		$this->assertArrayHasKey('main_frame',$json);
		$this->assertInternalType('string',$json['main_frame']);
		$this->assertNotEmpty($json['main_frame']);

		$CORE_LOCAL->set('memberID',123);

		// show warning screen
		$CORE_LOCAL->set('memberID',123);
		$json = $eWarn->handle(1,1.00,array('main_frame'=>''));
		$this->assertInternalType('array',$json);
		$this->assertArrayHasKey('main_frame',$json);
		$this->assertInternalType('string',$json['main_frame']);
		$this->assertNotEmpty($json['main_frame']);

		// clear warning and proceed
		$CORE_LOCAL->set('memberID',123);
		$CORE_LOCAL->set('msgrepeat', 1);
		$json = $eWarn->handle(1,1.00,array('main_frame'=>''));
		$this->assertInternalType('array',$json);
		$this->assertArrayHasKey('main_frame',$json);
		$this->assertInternalType('string',$json['main_frame']);
		$this->assertEmpty($json['main_frame']);
	}
}
