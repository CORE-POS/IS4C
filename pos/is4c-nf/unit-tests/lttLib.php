<?php

/**
  Helper functions for dealing with transactions table
*/
class lttLib {

	/**
	  Clear localtemptrans
	*/
	public static function clear(){
		$db = Database::tDataConnect();
		$db->query('TRUNCATE TABLE localtemptrans');
	}

	/**
	  Check what's in localtemptrans record
	  @param $trans_id record's trans_id value
	  @param $values array of column names and
		exepected values
	*/
	public static function verifyRecord($trans_id,$values, $testObj){
		$db = Database::tDataConnect();
		$p = $db->prepare_statement('SELECT * FROM localtemptrans WHERE trans_id=?');
		$r = $db->exec_statement($p, array($trans_id));
		$testObj->assertEquals(1,$db->num_rows($r),'Record not found');
		$w = $db->fetch_row($r);
		$testObj->assertInternalType('array',$w);
		foreach($values as $col => $val){
			$testObj->assertArrayHasKey($col,$w,'missing column '.$col);
			$testObj->assertEquals($val,$w[$col],'wrong value '.$col);
		}
	}

	/**
	  Get array of all ltt columns with default values
	*/
	public static function genericRecord(){
		return array(
			'upc' => '0',
			'description' => '',
			'trans_type' => '0',
			'trans_subtype' => '',
			'trans_status' => '',
			'department' => 0,
			'quantity' => 0,
			'cost' => 0,
			'unitPrice' => 0,
			'total' => 0,
			'regPrice' => 0,
			'scale' => 0,
			'tax' => 0,
			'foodstamp' => 0,
			'discount' => 0,	
			'memDiscount' => 0,
			'discountable' => 0,
			'discounttype' => 0,
			'ItemQtty' => 0,
			'volDiscType' => 0,
			'volume' => 0,
			'VolSpecial' => 0,
			'mixMatch' => 0,
			'matched' => 0,
			'voided' => 0,
			'memType' => 0,
			'staff' => 0,
			'numflag' => 0,
			'charflag' => '',
			'card_no' => 0
		);
	}
}

?>
