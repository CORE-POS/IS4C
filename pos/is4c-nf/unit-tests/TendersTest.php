<?php

/**
 * @backupGlobals disabled
 */
class TendersTest extends PHPUnit_Framework_TestCase
{
	public function testAll()
    {
		$defaults = array(
			'TenderModule',
			'CheckTender',
			'CreditCardTender',
			'DisabledTender',
			'FoodstampTender',
			'GiftCardTender',
			'GiftCertificateTender',
			'RefundAndCashbackTender',
			'StoreChargeTender',
			'StoreTransferTender'
		);

		$all = AutoLoader::ListModules('TenderModule',True);
		foreach($defaults as $d){
			$this->assertContains($d, $all);
		}

		foreach($all as $class){
			$obj = new $class('CA',1.00);
			$this->assertInstanceOf('TenderModule',$obj);

			$err = $obj->ErrorCheck();
			$this->assertThat($err,
				$this->logicalOr(
					$this->isType('boolean',$err),
					$this->isType('string',$err)
				)
			);

			$pre = $obj->ErrorCheck();
			$this->assertThat($pre,
				$this->logicalOr(
					$this->isType('boolean',$pre),
					$this->isType('string',$pre)
				)
			);

			$change = $obj->ChangeType();
			$this->assertInternalType('string',$change);
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
        $v = new Void();
        $this->assertEquals(true, $v->check('VD'));
        $json = $v->parse('VD');
        $this->assertInternalType('array', $json);
        $record['total'] *= -1;
        $record['voided'] = 1;
        $record['trans_status'] = 'V';
        lttLib::verifyRecord(2, $record, $this);
    }
}
