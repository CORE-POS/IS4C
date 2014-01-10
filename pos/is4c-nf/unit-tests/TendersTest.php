<?php

/**
 * @backupGlobals disabled
 */
class TendersTest extends PHPUnit_Framework_TestCase
{
	public function testAll(){
		global $CORE_LOCAL;

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
}
