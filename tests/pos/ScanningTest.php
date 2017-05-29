<?php

use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\Scanning\DiscountType;
use COREPOS\pos\lib\Scanning\DiscountTypes\NormalPricing;
use COREPOS\pos\lib\Scanning\DiscountTypes\EveryoneSale;
use COREPOS\pos\lib\Scanning\DiscountTypes\MemberSale;
use COREPOS\pos\lib\Scanning\DiscountTypes\StaffSale;
use COREPOS\pos\lib\Scanning\DiscountTypes\SlidingMemSale;
use COREPOS\pos\lib\Scanning\DiscountTypes\PercentMemSale;
use COREPOS\pos\lib\Scanning\PriceMethod;
use COREPOS\pos\lib\Scanning\PriceMethods\BasicPM;
use COREPOS\pos\lib\Scanning\PriceMethods\GroupPM;
use COREPOS\pos\lib\Scanning\PriceMethods\NoDiscOnSalesPM;
use COREPOS\pos\lib\Scanning\PriceMethods\QttyEnforcedGroupPM;
use COREPOS\pos\lib\Scanning\PriceMethods\SplitABGroupPM;
use COREPOS\pos\lib\Scanning\PriceMethods\ABGroupPM;
use COREPOS\pos\parser\parse\DeptKey;
use COREPOS\pos\parser\parse\UPC;
use COREPOS\pos\lib\Scanning\SpecialDept;
use COREPOS\pos\lib\Scanning\SpecialDepts\ArWarnDept;
use COREPOS\pos\lib\Scanning\SpecialDepts\AutoReprintDept;
use COREPOS\pos\lib\Scanning\SpecialDepts\EquityEndorseDept;
use COREPOS\pos\lib\Scanning\SpecialDepts\EquityWarnDept;
use COREPOS\pos\lib\Scanning\SpecialDepts\BottleReturnDept;
use COREPOS\pos\lib\Scanning\SpecialDepts\PaidOutDept;
use COREPOS\pos\lib\Scanning\VariableWeightReWrite;
use COREPOS\pos\lib\Scanning\VariableWeightReWrites\ItemNumberOnlyReWrite;
use COREPOS\pos\lib\Scanning\VariableWeightReWrites\ZeroedPriceReWrite;
use COREPOS\pos\lib\Scanning\SpecialUPC;
use COREPOS\pos\lib\Scanning\SpecialUPCs\CouponCode;
use COREPOS\pos\lib\Scanning\SpecialUPCs\DatabarCoupon;
use COREPOS\pos\lib\Scanning\SpecialUPCs\HouseCoupon;
use COREPOS\pos\lib\Scanning\SpecialUPCs\SpecialOrder;
use COREPOS\pos\lib\LocalStorage\WrappedStorage;

/**
 * @backupGlobals disabled
 */
class ScanningTest extends PHPUnit_Framework_TestCase
{
    public function testDiscountType()
    {
        $session = new WrappedStorage();
        $defaults = array(
            'COREPOS\\pos\\lib\\Scanning\\DiscountTypes\\NormalPricing',
            'COREPOS\\pos\\lib\\Scanning\\DiscountTypes\\EveryoneSale',
            'COREPOS\\pos\\lib\\Scanning\\DiscountTypes\\MemberSale',
            'COREPOS\\pos\\lib\\Scanning\\DiscountTypes\\StaffSale',
            'COREPOS\\pos\\lib\\Scanning\\DiscountTypes\\SlidingMemSale',
            'COREPOS\\pos\\lib\\Scanning\\DiscountTypes\\PercentMemSale',
        );

        $all = AutoLoader::ListModules('COREPOS\\pos\\lib\\Scanning\\DiscountType',False);
        foreach($defaults as $d){
            $this->assertContains($d, $all);
        }

        $all[] = 'COREPOS\\pos\\lib\\Scanning\\DiscountType';

        foreach($all as $class){
            $obj = new $class($session);
            $this->assertInstanceOf('COREPOS\\pos\\lib\\Scanning\\DiscountType',$obj);
            
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
                'specialquantity'=>0, 'line_item_discountable'=>1,
                'department'=>1);

        $norm = new DiscountType($session);
        $info = $norm->priceInfo($row, 1);
        $this->assertInternalType('array',$info);
        $this->assertArrayHasKey('regPrice',$info);
        $this->assertArrayHasKey('unitPrice',$info);
        $this->assertArrayHasKey('discount',$info);
        $this->assertArrayHasKey('memDiscount',$info);
        $norm->addDiscountLine();
        lttLib::clear();

        $norm = new NormalPricing($session);
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

        $norm = new EveryoneSale($session);
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

        $norm = new EveryoneSale($session);
        $row['specialquantity'] = 5;
        $row['upc'] = '0000000004011';
        $row['mixmatchcode'] = '';
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
        $row['specialquantity'] = 0;

        CoreLocal::set('isMember',1);
        $norm = new MemberSale($session);
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
        $norm = new StaffSale($session);
        $info = $norm->priceInfo($row, 1);
        $this->assertEquals($info, $norm->priceInfo($row, 1));
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
        $norm->addDiscountLine();
        lttLib::clear();

        $row['special_price'] = 0.10;
        $norm = new SlidingMemSale($session);
        $info = $norm->priceInfo($row, 1);
        $this->assertEquals($info, $norm->priceInfo($row, 1));
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
        $norm->addDiscountLine();
        lttLib::clear();

        $norm = new PercentMemSale($session);
        $info = $norm->priceInfo($row, 1);
        $this->assertEquals($info, $norm->priceInfo($row, 1));
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
        $norm->addDiscountLine();
        lttLib::clear();

        CoreLocal::set('isMember',0);
        CoreLocal::set('isStaff',0);
        $row['special_price'] = 1.49;

        $norm = new MemberSale($session);
        $info = $norm->priceInfo($row, 1);
        $this->assertEquals(1.99,$info['regPrice']);
        $this->assertEquals(1.99,$info['unitPrice']);
        $this->assertEquals(0,$info['discount']);
        $this->assertEquals(0.50,$info['memDiscount']);

        $norm = new StaffSale($session);
        $info = $norm->priceInfo($row, 1);
        $this->assertEquals(1.99,$info['regPrice']);
        $this->assertEquals(1.99,$info['unitPrice']);
        $this->assertEquals(0,$info['discount']);
        $this->assertEquals(0.50,$info['memDiscount']);

        $row['special_price'] = 0.10;
        $norm = new SlidingMemSale($session);
        $info = $norm->priceInfo($row, 1);
        $this->assertEquals(1.99,$info['regPrice']);
        $this->assertEquals(1.99,$info['unitPrice']);
        $this->assertEquals(0,$info['discount']);
        $this->assertEquals(0.10,$info['memDiscount']);

        $norm = new PercentMemSale($session);
        $info = $norm->priceInfo($row, 1);
        $this->assertEquals(1.99,$info['regPrice']);
        $this->assertEquals(1.99,$info['unitPrice']);
        $this->assertEquals(0,$info['discount']);
        $this->assertEquals(1.79,$info['memDiscount']);
    }

    public function testPriceMethods()
    {
        $session = new WrappedStorage();
        if (!class_exists('lttLib')) {
            include (dirname(__FILE__) . '/lttLib.php');
        }
        lttLib::clear();

        $pm = new PriceMethod($session);
        $this->assertEquals(true, $pm->addItem(array(), 1, array()));
        $this->assertEquals('', $pm->errorInfo());

        $db = Database::pDataConnect();
        $q = "SELECT * FROM products WHERE upc='0000000000111'";
        $r = $db->query($q);
        $row = $db->fetch_row($r);
        $discount = new NormalPricing($session);
        $discount->priceInfo($row, 1);

        $pm = new BasicPM($session);
        $this->assertEquals(false, $pm->addItem($row, 0, $discount));
        $pm->addItem($row, 1, $discount);
        $record = lttLib::genericRecord();
        $record['upc'] = '0000000000111';
        $record['description'] = 'WYNDMERE 5-8 DRAM BOTTLE';
        $record['trans_type'] = 'I';
        $record['trans_subtype'] = '';
        $record['trans_status'] = '';
        $record['department'] = 103;
        $record['quantity'] = 1;
        $record['ItemQtty'] = 1;
        $record['unitPrice'] = 1.65;
        $record['total'] = 1.65;
        $record['regPrice'] = 1.65;
        $record['tax'] = 1;
        $record['discountable'] = 1;
        $record['mixMatch'] = '499';
        lttLib::verifyRecord(1, $record, $this);

        lttLib::clear();

        $pm = new NoDiscOnSalesPM($session);
        $this->assertEquals(false, $pm->addItem($row, 0, $discount));
        $pm->addItem($row, 1, $discount);
        $record = lttLib::genericRecord();
        $record['upc'] = '0000000000111';
        $record['description'] = 'WYNDMERE 5-8 DRAM BOTTLE';
        $record['trans_type'] = 'I';
        $record['trans_subtype'] = '';
        $record['trans_status'] = '';
        $record['department'] = 103;
        $record['quantity'] = 1;
        $record['ItemQtty'] = 1;
        $record['unitPrice'] = 1.65;
        $record['total'] = 1.65;
        $record['regPrice'] = 1.65;
        $record['tax'] = 1;
        $record['discountable'] = 1;
        $record['mixMatch'] = '499';
        lttLib::verifyRecord(1, $record, $this);

        lttLib::clear();

        $row['pricemethod'] = 1;
        $row['groupprice'] = 2;
        $row['quantity'] = 2;
        $discount = new NormalPricing($session);
        $discount->priceInfo($row, 1);
        $pm = new GroupPM($session);
        $pm->addItem($row, 1, $discount);
        $record = lttLib::genericRecord();
        $record['upc'] = '0000000000111';
        $record['description'] = 'WYNDMERE 5-8 DRAM BOTTLE';
        $record['trans_type'] = 'I';
        $record['trans_subtype'] = '';
        $record['trans_status'] = '';
        $record['department'] = 103;
        $record['quantity'] = 1;
        $record['ItemQtty'] = 1;
        $record['unitPrice'] = 1.00;
        $record['total'] = 1.00;
        $record['regPrice'] = 1.65;
        $record['tax'] = 1;
        $record['discountable'] = 1;
        $record['mixMatch'] = '499';
        $record['volDiscType'] = 1;
        $record['volume'] = 2;
        $record['VolSpecial'] = 2.00;
        lttLib::verifyRecord(1, $record, $this);
        $pm->addItem($row, 1, $discount);
        $record = lttLib::genericRecord();
        $record['upc'] = '0000000000111';
        $record['description'] = 'WYNDMERE 5-8 DRAM BOTTLE';
        $record['trans_type'] = 'I';
        $record['trans_subtype'] = '';
        $record['trans_status'] = '';
        $record['department'] = 103;
        $record['quantity'] = 1;
        $record['ItemQtty'] = 1;
        $record['unitPrice'] = 1.00;
        $record['total'] = 1.00;
        $record['regPrice'] = 1.65;
        $record['tax'] = 1;
        $record['discountable'] = 1;
        $record['mixMatch'] = '499';
        $record['volDiscType'] = 1;
        $record['volume'] = 2;
        $record['VolSpecial'] = 2.00;
        lttLib::verifyRecord(2, $record, $this);

        lttLib::clear();

        $row['pricemethod'] = 2;
        $discount = new NormalPricing($session);
        $discount->priceInfo($row, 1);
        $pm = new QttyEnforcedGroupPM($session);
        $pm->addItem($row, 1, $discount);
        $record = lttLib::genericRecord();
        $record['upc'] = '0000000000111';
        $record['description'] = 'WYNDMERE 5-8 DRAM BOTTLE';
        $record['trans_type'] = 'I';
        $record['trans_subtype'] = '';
        $record['trans_status'] = '';
        $record['department'] = 103;
        $record['quantity'] = 1;
        $record['ItemQtty'] = 1;
        $record['unitPrice'] = 1.65;
        $record['total'] = 1.65;
        $record['regPrice'] = 1.65;
        $record['tax'] = 1;
        $record['discountable'] = 1;
        $record['mixMatch'] = '499';
        $record['volDiscType'] = 2;
        $record['volume'] = 2;
        $record['VolSpecial'] = 2.00;
        lttLib::verifyRecord(1, $record, $this);
        $pm->addItem($row, 1, $discount);
        $record = lttLib::genericRecord();
        $record['upc'] = '0000000000111';
        $record['description'] = 'WYNDMERE 5-8 DRAM BOTTLE';
        $record['trans_type'] = 'I';
        $record['trans_subtype'] = '';
        $record['trans_status'] = '';
        $record['department'] = 103;
        $record['quantity'] = 1;
        $record['ItemQtty'] = 1;
        $record['unitPrice'] = 0.35;
        $record['total'] = 0.35;
        $record['regPrice'] = 1.65;
        $record['tax'] = 1;
        $record['discountable'] = 1;
        $record['mixMatch'] = '499';
        $record['volDiscType'] = 2;
        $record['volume'] = 2;
        $record['VolSpecial'] = 2.00;
        $record['discount'] = 1.30;
        $record['matched'] = 2;
        lttLib::verifyRecord(2, $record, $this);

        lttLib::clear();

        $db = Database::pDataConnect();
        $item1 = '0027002000000';
        $r = $db->query("SELECT * FROM products WHERE upc='$item1'");
        $row1 = $db->fetch_row($r);
        $row1['normal_price'] = 9.99;
        $item2 = '0020140000000';
        $r = $db->query("SELECT * FROM products WHERE upc='$item2'");
        $row2 = $db->fetch_row($r);
        $row2['normal_price'] = 9.99;
        $discount1 = new NormalPricing($session);
        $discount1->priceInfo($row1, 1);
        $discount2 = new NormalPricing($session);
        $discount2->priceInfo($row2, 1);
        $pm = new SplitABGroupPM($session);
        $pm->addItem($row1, 1, $discount1);
        $record = lttLib::genericRecord();
        $record['upc'] = '0027002000000';
        $record['description'] = 'HALF ROAST BEEF AND CHEDDAR';
        $record['trans_type'] = 'I';
        $record['trans_subtype'] = '';
        $record['trans_status'] = '';
        $record['department'] = 226;
        $record['quantity'] = 1;
        $record['ItemQtty'] = 1;
        $record['unitPrice'] = 9.99;
        $record['total'] = 9.99;
        $record['regPrice'] = 9.99;
        $record['tax'] = 2;
        $record['foodstamp'] = 1;
        $record['discountable'] = 1;
        $record['mixMatch'] = '-2';
        $record['volDiscType'] = 3;
        $record['volume'] = 2;
        $record['VolSpecial'] = 0.50;
        lttLib::verifyRecord(1, $record, $this);
        $pm->addItem($row2, 1, $discount2);
        $record = lttLib::genericRecord();
        $record['upc'] = '0020140000000';
        $record['description'] = 'SOUP 16 OZ';
        $record['trans_type'] = 'I';
        $record['trans_subtype'] = '';
        $record['trans_status'] = '';
        $record['department'] = 66;
        $record['quantity'] = 1;
        $record['ItemQtty'] = 1;
        $record['unitPrice'] = 9.99;
        $record['total'] = 9.99;
        $record['regPrice'] = 9.99;
        $record['tax'] = 2;
        $record['foodstamp'] = 0;
        $record['discountable'] = 1;
        $record['mixMatch'] = '2';
        $record['volDiscType'] = 3;
        $record['volume'] = 2;
        $record['VolSpecial'] = 0.50;
        $record['matched'] = 2;
        lttLib::verifyRecord(2, $record, $this);
        $record = lttLib::genericRecord();
        $record['upc'] = 'ITEMDISCOUNT';
        $record['description'] = ' * Item Discount';
        $record['trans_type'] = 'I';
        $record['department'] = 66;
        $record['quantity'] = 1;
        $record['ItemQtty'] = 1;
        $record['unitPrice'] = -0.25;
        $record['total'] = -0.25;
        $record['regPrice'] = -0.25;
        lttLib::verifyRecord(3, $record, $this);
        $record = lttLib::genericRecord();
        $record['upc'] = 'ITEMDISCOUNT';
        $record['description'] = ' * Item Discount';
        $record['trans_type'] = 'I';
        $record['department'] = 226;
        $record['quantity'] = 1;
        $record['ItemQtty'] = 1;
        $record['unitPrice'] = -0.25;
        $record['total'] = -0.25;
        $record['regPrice'] = -0.25;
        lttLib::verifyRecord(4, $record, $this);

        lttLib::clear();

        $row1['pricemethod'] = 4;
        $row2['pricemethod'] = 4;
        $discount1 = new NormalPricing($session);
        $discount1->priceInfo($row1, 1);
        $discount2 = new NormalPricing($session);
        $discount2->priceInfo($row2, 1);
        $pm = new ABGroupPM($session);
        $pm->addItem($row2, 1, $discount2);
        $record = lttLib::genericRecord();
        $record['upc'] = '0020140000000';
        $record['description'] = 'SOUP 16 OZ';
        $record['trans_type'] = 'I';
        $record['trans_subtype'] = '';
        $record['trans_status'] = '';
        $record['department'] = 66;
        $record['quantity'] = 1;
        $record['ItemQtty'] = 1;
        $record['unitPrice'] = 9.99;
        $record['total'] = 9.99;
        $record['regPrice'] = 9.99;
        $record['tax'] = 2;
        $record['foodstamp'] = 0;
        $record['discountable'] = 1;
        $record['mixMatch'] = '2';
        $record['volDiscType'] = 4;
        $record['volume'] = 2;
        $record['VolSpecial'] = 0.50;
        lttLib::verifyRecord(1, $record, $this);
        $pm->addItem($row1, 1, $discount1);
        $record = lttLib::genericRecord();
        $record['upc'] = '0027002000000';
        $record['description'] = 'HALF ROAST BEEF AND CHEDDAR';
        $record['trans_type'] = 'I';
        $record['trans_subtype'] = '';
        $record['trans_status'] = '';
        $record['department'] = 226;
        $record['quantity'] = 1;
        $record['ItemQtty'] = 1;
        $record['unitPrice'] = 9.99;
        $record['total'] = 9.99;
        $record['regPrice'] = 9.99;
        $record['tax'] = 2;
        $record['discount'] = 0.50;
        $record['foodstamp'] = 1;
        $record['discountable'] = 1;
        $record['mixMatch'] = '-2';
        $record['volDiscType'] = 4;
        $record['volume'] = 2;
        $record['VolSpecial'] = 0.50;
        $record['matched'] = 2;
        lttLib::verifyRecord(2, $record, $this);
        $record = lttLib::genericRecord();
        $record['upc'] = 'ITEMDISCOUNT';
        $record['description'] = ' * Item Discount';
        $record['trans_type'] = 'I';
        $record['department'] = 226;
        $record['quantity'] = 1;
        $record['ItemQtty'] = 1;
        $record['unitPrice'] = -0.50;
        $record['total'] = -0.50;
        $record['regPrice'] = -0.50;
        $record['tax'] = 2;
        $record['foodstamp'] = 1;
        lttLib::verifyRecord(3, $record, $this);
    }

    public function testSpecialUPCs() 
    {
        $session = new WrappedStorage();
        $defaults = array(
            'COREPOS\\pos\\lib\\Scanning\\SpecialUPCs\\CouponCode',
            'COREPOS\\pos\\lib\\Scanning\\SpecialUPCs\\DatabarCoupon',
            'COREPOS\\pos\\lib\\Scanning\\SpecialUPCs\\HouseCoupon',
            'COREPOS\\pos\\lib\\Scanning\\SpecialUPCs\\SpecialOrder'
        );

        $all = AutoLoader::ListModules('COREPOS\\pos\\lib\\Scanning\\SpecialUPC',False);
        foreach($defaults as $d){
            $this->assertContains($d, $all);
        }

        foreach($all as $class){
            $obj = new $class($session);
            $this->assertInstanceOf('COREPOS\\pos\\lib\\Scanning\\SpecialUPC',$obj);
            $this->assertInternalType('boolean',$obj->isSpecial('silly nonsense input'));
        }

        $cc = new CouponCode($session);
        $this->assertEquals(True,$cc->isSpecial('0051234512345'));
        $this->assertEquals(True,$cc->isSpecial('0991234512345'));
        $this->assertEquals(False,$cc->isSpecial('0001234512345'));

        $dat = new DatabarCoupon($session);
        $this->assertEquals(True,$dat->isSpecial('811012345678901'));
        $this->assertEquals(False,$dat->isSpecial('8110123456790'));
        $this->assertEquals(False,$dat->isSpecial('0001234512345'));
        // just coverage; have not explored what this should do
        $dat->handle('8110100707340143853100110110', array());
        lttLib::clear();

        $hc = new HouseCoupon($session);
        $this->assertEquals(True,$hc->isSpecial('0049999900001'));
        $this->assertEquals(False,$hc->isSpecial('0001234512345'));

        $so = new SpecialOrder($session);
        $this->assertEquals(true, $so->isSpecial('0045400010001'));
        $this->assertEquals(false, $so->isSpecial('0001234512345'));
        $out = $so->handle('0045400000000', array());
        $this->assertNotEquals(0, strlen($out['output']));
        $out = $so->handle('0045400010001', array());
        $this->assertNotEquals(0, strlen($out['output']));

        $s = new SpecialUPC($session);
        $this->assertEquals(false, $s->isSpecial('foo'));
        $this->assertEquals(false, $s->isSpecial('foo'));
        $this->assertEquals(null, $s->handle('foo', array()));
    }

    public function testCouponCode()
    {
        $session = new WrappedStorage();
        if (!class_exists('lttLib')) {
            include (dirname(__FILE__) . '/lttLib.php');
        }

        $cc = new CouponCode($session);
        $out = $cc->handle('0051234512345', array());
        $expected_error = DisplayLib::boxMsg(
            _("product not found")."<br />"._("in transaction"),
            '',
            true,
            DisplayLib::standardClearButton()
        );

        $this->assertArrayHasKey('output', $out);
        $this->assertEquals($out['output'], $expected_error);

        lttLib::clear();
        $out = $cc->handle('0051234599210', array());
        $record = lttLib::genericRecord();
        $record['upc'] = '0051234599210';
        $record['description'] = ' * Manufacturers Coupon';
        $record['trans_type'] = 'I';
        $record['trans_subtype'] = 'CP';
        $record['trans_status'] = 'C';
        $record['quantity'] = 1;
        $record['ItemQtty'] = 1;
        $record['unitPrice'] = -0.10;
        $record['total'] = -0.10;
        $record['regPrice'] = -0.10;
        lttLib::verifyRecord(1, $record, $this);
        
        lttLib::clear();
        $db = Database::tDataConnect();
        $db->query('TRUNCATE TABLE couponApplied');
        $u = new UPC($session);
        $u->parse('0001101312028');

        $out = $cc->handle('0051101399901', array());
        $record = lttLib::genericRecord();
        $record['upc'] = '0051101399901';
        $record['description'] = ' * Manufacturers Coupon';
        $record['trans_type'] = 'I';
        $record['trans_subtype'] = 'CP';
        $record['trans_status'] = 'C';
        $record['department'] = 181;
        $record['quantity'] = 1;
        $record['ItemQtty'] = 1;
        $record['discountable'] = 1;
        $record['unitPrice'] = -4.59;
        $record['total'] = -4.59;
        $record['regPrice'] = -4.59;
        lttLib::verifyRecord(2, $record, $this);
    }

    public function testHouseCoupons()
    {
        if (!class_exists('lttLib')) {
            include (dirname(__FILE__) . '/lttLib.php');
        }
        $session = new WrappedStorage();

        /**
          TEST 1: minType M, discountType Q
        */
        lttLib::clear();
        $upc = new UPC($session);
        $upc->parse('0000000000111');
        $upc->parse('0000000000234');

        $hc = new HouseCoupon($session);
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
        $record['discountable'] = 1;
        lttLib::verifyRecord(3, $record, $this);

        /**
          TEST 2: no minimum, discountType %D 
        */
        lttLib::clear();
        $upc = new UPC($session);
        $upc->parse('0000000001009');
        $upc->parse('0000000001011');

        $hc = new HouseCoupon($session);
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
        $record['discountable'] = 1;
        lttLib::verifyRecord(3, $record, $this);

        /**
          TEST 3: minimum D, discountType F 
        */
        lttLib::clear();
        $dept = new DeptKey($session);
        $dept->parse('2300DP10');
        $dept->parse('200DP10');

        $hc = new HouseCoupon($session);
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
        $record['discountable'] = 0;
        lttLib::verifyRecord(3, $record, $this);

        /**
          TEST 4: minimum MX, discountType F 
        */
        lttLib::clear();
        $dept = new DeptKey($session);
        $dept->parse('900DP10');
        $upc = new UPC($session);
        $upc->parse('0000000000234');

        $hc = new HouseCoupon($session);
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
        $record['discountable'] = 0;
        lttLib::verifyRecord(3, $record, $this);

        /**
          TEST 5: minType Q, discountType PI 
        */
        lttLib::clear();
        $upc = new UPC($session);
        $upc->parse('0000000000111');
        $upc->parse('0000000000234');

        $hc = new HouseCoupon($session);
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
        $record['discountable'] = 1;
        lttLib::verifyRecord(3, $record, $this);

        /**
          TEST 6: dept qty minimum, discountType %D 
        */
        lttLib::clear();
        $upc = new UPC($session);
        $upc->parse('0000000001009');
        $upc->parse('0000000001011');

        $hc = new HouseCoupon($session);
        $hc->handle('0049999900006', array());
        $record = lttLib::genericRecord();
        $record['upc'] = '0049999900006';
        $record['description'] = 'DEPTQTY';
        $record['trans_type'] = 'I';
        $record['trans_subtype'] = 'IC';
        $record['trans_status'] = 'C';
        $record['quantity'] = 1;
        $record['ItemQtty'] = 1;
        $record['unitPrice'] = -0.75;
        $record['total'] = -0.75;
        $record['regPrice'] = -0.75;
        $record['discountable'] = 1;
        lttLib::verifyRecord(3, $record, $this);

        lttLib::clear();
    }

    public function testSpecialDepts()
    {
        $session = new WrappedStorage();
        $sd = new SpecialDept($session);
        $this->assertEquals('Documentation Needed!', $sd->help_text());
        $arr = $sd->register(1, 'not-array');
        $expect = array(1 => array('COREPOS\\pos\\lib\\Scanning\\SpecialDept'));
        $this->assertEquals($expect, $arr);
        $this->assertEquals(array(), $sd->handle(1, 1, array()));

        $defaults = array(
            'COREPOS\\pos\\lib\\Scanning\\SpecialDepts\\ArWarnDept',
            'COREPOS\\pos\\lib\\Scanning\\SpecialDepts\\AutoReprintDept',
            'COREPOS\\pos\\lib\\Scanning\\SpecialDepts\\EquityEndorseDept',
            'COREPOS\\pos\\lib\\Scanning\\SpecialDepts\\EquityWarnDept',
            'COREPOS\\pos\\lib\\Scanning\\SpecialDepts\\BottleReturnDept',
            'COREPOS\\pos\\lib\\Scanning\\SpecialDepts\\PaidOutDept',
        );

        $all = AutoLoader::ListModules('COREPOS\\pos\\lib\\Scanning\\SpecialDept',False);
        foreach($defaults as $d){
            $this->assertContains($d, $all);
        }

        $map = array();
        foreach($all as $class){
            $obj = new $class($session);
            $this->assertInstanceOf('COREPOS\\pos\\lib\\Scanning\\SpecialDept',$obj);
            $map = $obj->register(1,$map);
            $this->assertInternalType('array',$map);
            $this->assertArrayHasKey(1,$map);
            $this->assertInternalType('array',$map[1]);
            $this->assertContains($class,$map[1]);
            $this->assertNotEquals(0, strlen($obj->help_text()));
        }

        CoreLocal::set('msgrepeat',0);

        // first call should set warn vars
        $arwarn = new ArWarnDept($session);
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
        $auto = new AutoReprintDept($session);
        $json = $auto->handle(1,1.00,array());
        $this->assertInternalType('array',$json);
        $this->assertEquals(1, CoreLocal::get('autoReprint'));    

        CoreLocal::set('msgrepeat',0);
        CoreLocal::set('memberID',0);

        // error because member is required
        $eEndorse = new EquityEndorseDept($session);
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
        $eWarn = new EquityWarnDept($session);
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

        CoreLocal::set('memberID', '0');

        $brd = new BottleReturnDept($session);
        CoreLocal::set('msgrepeat', 0);
        CoreLocal::set('strEntered', '100DP10');
        $json = $brd->handle(10, 1, array());
        $this->assertEquals('-100DP10', CoreLocal::get('strEntered'));
        $this->assertEquals('?autoconfirm=1', substr($json['main_frame'], -14));
        CoreLocal::set('msgrepeat', 0);
        CoreLocal::set('strEntered', '');

        $brd = new PaidOutDept($session);
        CoreLocal::set('msgrepeat', 0);
        $json = $brd->handle(10, 1, array());
        $this->assertEquals('-100DP10', CoreLocal::get('strEntered'));
        $this->assertEquals('/PaidOutComment.php', substr($json['main_frame'], -19));
        CoreLocal::set('msgrepeat', 0);
        CoreLocal::set('strEntered', '');
    }

    function testVariableReWrite()
    {
        $v = new VariableWeightReWrite();
        $this->assertEquals('foo', $v->translate('foo'));

        $nocheck = '0021234500000';
        $check =   '021234500000X';

        $v = new ItemNumberOnlyReWrite();
        $this->assertEquals('0000000012345', $v->translate($nocheck));
        $this->assertEquals('0000000012345', $v->translate($check, true));

        $v = new ZeroedPriceReWrite();
        $this->assertEquals('0021234500000', $v->translate($nocheck));
        $this->assertEquals('0212345000000', $v->translate($check, true));
    }

}
