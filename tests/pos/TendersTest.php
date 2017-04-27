<?php

use COREPOS\pos\lib\Tenders\TenderModule;
use COREPOS\pos\lib\Tenders\CheckTender;
use COREPOS\pos\lib\Tenders\CreditCardTender;
use COREPOS\pos\lib\Tenders\DisabledTender;
use COREPOS\pos\lib\Tenders\FoodstampTender;
use COREPOS\pos\lib\Tenders\GiftCardTender;
use COREPOS\pos\lib\Tenders\GiftCertificateTender;
use COREPOS\pos\lib\Tenders\ManagerApproveTender;
use COREPOS\pos\lib\Tenders\NoChangeTender;
use COREPOS\pos\lib\Tenders\NoDefaultAmountTender;
use COREPOS\pos\lib\Tenders\StoreChargeTender;
use COREPOS\pos\lib\Tenders\SignedStoreChargeTender;
use COREPOS\pos\lib\Tenders\StoreTransferTender;
use COREPOS\pos\lib\LocalStorage\WrappedStorage;

/**
 * @backupGlobals disabled
 */
class TendersTest extends PHPUnit_Framework_TestCase
{
    public function testAll()
    {
        $defaults = array(
            'COREPOS\\pos\\lib\\Tenders\\TenderModule',
            'COREPOS\\pos\\lib\\Tenders\\CheckTender',
            'COREPOS\\pos\\lib\\Tenders\\CreditCardTender',
            'COREPOS\\pos\\lib\\Tenders\\DisabledTender',
            'COREPOS\\pos\\lib\\Tenders\\FoodstampTender',
            'COREPOS\\pos\\lib\\Tenders\\GiftCardTender',
            'COREPOS\\pos\\lib\\Tenders\\GiftCertificateTender',
            'COREPOS\\pos\\lib\\Tenders\\RefundAndCashbackTender',
            'COREPOS\\pos\\lib\\Tenders\\StoreChargeTender',
            'COREPOS\\pos\\lib\\Tenders\\StoreTransferTender'
        );

        $all = AutoLoader::ListModules('COREPOS\\pos\\lib\\Tenders\\TenderModule',True);
        foreach($defaults as $d){
            $this->assertContains($d, $all);
        }

        foreach($all as $class){
            $obj = new $class('CA',1.00);
            $this->assertInstanceOf('COREPOS\\pos\\lib\\Tenders\\TenderModule',$obj);

            $err = $obj->ErrorCheck();
            $this->assertThat($err,
                $this->logicalOr(
                    $this->isType('boolean',$err),
                    $this->isType('string',$err)
                )
            );

            $pre = $obj->preReqCheck();
            $this->assertThat($pre,
                $this->logicalOr(
                    $this->isType('boolean',$pre),
                    $this->isType('string',$pre)
                )
            );

            $change = $obj->ChangeType();
            $this->assertInternalType('string',$change);
            $this->assertEquals(true, is_numeric($obj->defaultTotal()));
            $this->assertInternalType('boolean', $obj->allowDefault());
        }

    }

    function testTenderDbRecords()
    {
        lttLib::clear();
        $t = new TenderModule('CA', 1.00);
        $t->add();
        $record = lttLib::genericRecord();
        $record['trans_type'] = 'T';
        $record['trans_subtype'] = 'CA';
        $record['description'] = 'Cash';
        $record['total'] = -1.00;
        lttLib::verifyRecord(1, $record, $this);
        CoreLocal::set('currentid', 1);
        $v = new COREPOS\pos\parser\parse\VoidCmd(new WrappedStorage());
        $this->assertEquals(true, $v->check('VD'));
        $json = $v->parse('VD');
        $this->assertInstanceOf('COREPOS\\pos\\parser\\ParseResult', $json);
        $record['total'] *= -1;
        $record['voided'] = 1;
        $record['trans_status'] = 'V';
        lttLib::verifyRecord(2, $record, $this);
    }

    function testTenderModule()
    {
        $t1 = new TenderModule('CA', 1.00);
        $t2 = new TenderModule('FOOBAR', 1.00);
        $this->assertEquals('CA', $t2->changeType());
        $this->assertEquals('Change', $t2->changeMsg());
        $this->assertEquals(true, $t1->allowDefault());
        $this->assertNotEquals(0, strlen($t2->disabledPrompt()));

        CoreLocal::set('amtdue', 1.99);
        $this->assertEquals(1.99, $t1->defaultTotal());
        $this->assertEquals('?quiet=1', substr($t1->defaultPrompt(), -8)); 

        CoreLocal::set('LastID', 0);
        $out = $t1->errorCheck();
        $this->assertNotEquals(0, strlen($out));
        CoreLocal::set('LastID', 1);

        CoreLocal::set('refund', 1);
        $out = $t1->errorCheck();
        $this->assertNotEquals(0, strlen($out));
        CoreLocal::set('refund', 0);

        $t3 = new TenderModule('ca', 100000);
        $out = $t3->errorCheck();
        $this->assertNotEquals(0, strlen($out));

        CoreLocal::set('ttlflag', 0);
        $out = $t1->errorCheck();
        $this->assertNotEquals(0, strlen($out));
        CoreLocal::set('ttlflag', 1);

        $out = $t2->errorCheck();
        $this->assertNotEquals(0, strlen($out));

        $out = $t1->errorCheck();
        $this->assertEquals(true, $out);

        CoreLocal::set('ttlflag', 0);
        CoreLocal::set('LastID', 0);
        CoreLocal::set('amtdue', 0);

        CoreLocal::set('msgrepeat', 0);
        $out = $t2->preReqCheck();
        $this->assertNotEquals(0, strlen($out));

        CoreLocal::set('msgrepeat', 1);
        CoreLocal::set('lastRepeat', 'confirmTenderAmount');
        $out = $t1->preReqCheck();
        $this->assertEquals(true, $out);
        $this->assertEquals(0, CoreLocal::get('msgrepeat'));
    }

    function testStoreTransferTender()
    {
        $st = new StoreTransferTender('CA', 5);
        CoreLocal::set('amtdue', 1);
        $this->assertNotEquals(0, strlen($st->errorCheck()));
        CoreLocal::set('amtdue', 8);
        $this->assertNotEquals(0, strlen($st->errorCheck()));
        CoreLocal::set('amtdue', 0);

        CoreLocal::set('transfertender', 0);
        $out = $st->preReqCheck();
        $this->assertEquals(1, CoreLocal::get('transfertender'));
        $this->assertEquals('-StoreTransferTender', substr($out, -20));
        $out = $st->preReqCheck();
        $this->assertEquals(0, CoreLocal::get('transfertender'));
        $this->assertEquals(true, $out);
    }

    function testStoreChargeTender()
    {
        $sc = new StoreChargeTender('CA', 1);
        $this->assertEquals('?autoconfirm=1', substr($sc->defaultPrompt(), -14));
        $this->assertEquals(true, $sc->preReqCheck());
    }

    function testSignedStoreChargeTender()
    {
        $sc = new SignedStoreChargeTender('CA', 1);
        CoreLocal::set('msgrepeat', 0);
        $this->assertEquals('&code=CA', substr($sc->preReqCheck(), -8));
        CoreLocal::set('msgrepeat', 1);
        CoreLocal::set('lastRepeat', 'signStoreCharge');
        $this->assertEquals(true, $sc->preReqCheck());
    }

    function testNoDefaultAmountTender()
    {
        $obj = new NoDefaultAmountTender('CA', 1);
        $this->assertEquals(false, $obj->allowDefault());
    }

    function testNoChangeTender()
    {
        $st = new NoChangeTender('CA', 5);
        CoreLocal::set('amtdue', 1);
        $this->assertNotEquals(0, strlen($st->errorCheck()));
        CoreLocal::set('amtdue', 8);
        $this->assertEquals(true, $st->errorCheck());
        CoreLocal::set('amtdue', 0);
    }
    
    function testGiftCard()
    {
        $st = new GiftCardTender('GD', 5);
        CoreLocal::set('amtdue', 1);
        $this->assertNotEquals(0, strlen($st->errorCheck()));
        CoreLocal::set('amtdue', 8);
        $this->assertEquals(true, $st->errorCheck());
        CoreLocal::set('amtdue', 0);
    }

    function testCreditCard()
    {
        $st = new CreditCardTender('GD', 5);
        CoreLocal::set('amtdue', 1);
        $this->assertNotEquals(0, strlen($st->errorCheck()));
        CoreLocal::set('amtdue', 8);
        $this->assertEquals(true, $st->errorCheck());
        CoreLocal::set('amtdue', 0);
    }

    function testManagerApproveTender()
    {
        $st = new ManagerApproveTender('CA', 5);
        CoreLocal::set('amtdue', 1);
        $this->assertNotEquals(0, strlen($st->errorCheck()));
        CoreLocal::set('amtdue', 8);
        $this->assertEquals(true, $st->errorCheck());
        CoreLocal::set('amtdue', 0);

        CoreLocal::set('approvetender', 0);
        $out = $st->preReqCheck();
        $this->assertEquals('-ManagerApproveTender', substr($out, -21));
        $this->assertEquals(1, CoreLocal::get('approvetender'));
        $out = $st->preReqCheck();
        $this->assertEquals(true, $out);
        $this->assertEquals(0, CoreLocal::get('approvetender'));
    }

    function testDisabledTender()
    {
        $obj = new DisabledTender('CA', 1);
        $this->assertNotEquals(0, strlen($obj->errorCheck()));
    }

    function testGiftCert()
    {
        $obj = new GiftCertificateTender('TC', 1);
        CoreLocal::set('enableFranking', 1, true);
        CoreLocal::set('msgrepeat', 0);
        $ret = $obj->preReqCheck();
        $this->assertEquals(true, $ret !== true);
        CoreLocal::set('enableFranking', 0, true);
        CoreLocal::set('msgrepeat', 0);
    }

    function testCheck()
    {
        $obj = new CheckTender('CK', 1);
        CoreLocal::set('enableFranking', 1, true);
        CoreLocal::set('msgrepeat', 0);
        $ret = $obj->preReqCheck();
        $this->assertEquals(true, $ret !== true);
        CoreLocal::set('enableFranking', 0, true);
        CoreLocal::set('msgrepeat', 0);

        CoreLocal::set('isMember', 1);
        CoreLocal::set('dollarOver', 0, true);
        CoreLocal::set('amtdue', 0.50);
        $this->assertNotEquals(0, strlen($obj->ErrorCheck()));
        CoreLocal::set('isMember', 0);
        CoreLocal::set('amtdue', 0);
    }

    function testFoodstamp()
    {
        $obj = new FoodstampTender('EF', 10);
        CoreLocal::set('fntlflag', 1);
        CoreLocal::set('fsEligible', 1);
        $this->assertNotEquals(0, strlen($obj->ErrorCheck()));
        CoreLocal::set('fsEligible', 15);
        $this->assertEquals(true, $obj->ErrorCheck());
        $obj = new FoodstampTender('EF', -10);
        $this->assertNotEquals(0, strlen($obj->ErrorCheck()));
        CoreLocal::set('fsEligible', -9);
        $this->assertNotEquals(0, strlen($obj->ErrorCheck()));
        CoreLocal::set('fsEligible', -10);
        $this->assertEquals(true, $obj->ErrorCheck());
        CoreLocal::set('fntlflag', 0);
        CoreLocal::set('fsEligible', 0);
    }
}
