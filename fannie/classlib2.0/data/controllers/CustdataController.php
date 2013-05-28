<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of Fannie.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/**
  @class CustdataController

*/

if (!class_exists('FannieDB'))
	include(dirname(__FILE__).'/../FannieDB.php');

class CustdataController {
	
	/**
	  Update custdata record(s) for an account
	  @param $card_no the member number
	  @param $fields array of column names and values

	  The values for personNum, LastName, and FirstName
	  should be arrays. 
	*/
	public static function update($card_no,$fields){
		global $FANNIE_OP_DB;
		$dbc = FannieDB::get($FANNIE_OP_DB);
		$ret = True;

		/** deal with number of names first */
		if (isset($fields['personNum']) && is_array($fields['personNum'])){

			/** delete secondary members */
			$delQ = "DELETE FROM custdata WHERE personNum > 1 AND CardNo=?";
			$delP = $dbc->prepare_statement($delQ);
			$delR = $dbc->exec_statement($delP,array($card_no));

			/** create records for secondary members
			    based on the primary member */
			$insQ = "INSERT INTO custdata (CardNo,personNum,LastName,FirstName,
				CashBack,Balance,Discount,MemDiscountLimit,ChargeOk,
				WriteChecks,StoreCoupons,Type,memType,staff,SSI,Purchases,
				NumberOfChecks,memCoupons,blueLine,Shown)
				SELECT  CardNo,?,?,?,
				CashBack,Balance,Discount,MemDiscountLimit,ChargeOk,
				WriteChecks,StoreCoupons,Type,memType,staff,SSI,Purchases,
				NumberOfChecks,memCoupons,?,Shown
				FROM custdata WHERE personNum=1 AND CardNo=?";
			$insP = $dbc->prepare_statement($insQ);
			for($i=0;$i<count($fields['personNum']);$i++){
				$pn = $i+1;
				$ln = isset($fields['LastName'][$i])?$fields['LastName'][$i]:'';
				$fn = isset($fields['FirstName'][$i])?$fields['FirstName'][$i]:'';
				if ($pn == 1){
					/** update primary member */
					$upQ = "UPDATE custdata SET LastName=?,FirstName=?,blueLine=?
						WHERE personNum=1 AND CardNo=?";
					$upP = $dbc->prepare_statement($upQ);
					$args = array($ln,$fn,$card_no." ".$ln,$card_no);
					$upR = $dbc->exec_statement($upP,$args);
					if ($upR === False) $ret = False;
				}
				else {
					/** create/re-create secondary member */
					$insR = $dbc->exec_statement($insP,
						array($pn,$ln,$fn,$card_no." ".$ln,$card_no));
					if ($insR === False) $ret = False;
				}
			}
		}

		/** update all other fields for the account
		    The switch statement is to filter out
		    bad input */
		$updateQ = "UPDATE custdata SET ";
		$updateArgs = array();
		foreach($fields as $name => $value){
			switch($name){
			case 'CashBack':
			case 'Balance':
			case 'Discount':
			case 'MemDiscountLimit':
			case 'ChargeOk':
			case 'WriteChecks':
			case 'StoreCoupons':
			case 'Type':
			case 'memType':
			case 'staff':
			case 'SSI':
			case 'Purchases':
			case 'NumberOfChecks':
			case 'memCoupons':	
			case 'Shown':
				if ($name === 0 || $name === True)
					break; // switch does loose comparison...
				$updateQ .= $name." = ?,";
				$updateArgs[] = $value;
				break;
			default:
				break;
			}
		}

		/** if only name fields were provided, there's
		    nothing to do here */
		if ($updateQ != "UPDATE custdata SET "){
			$updateQ = rtrim($updateQ,",");
			$updateQ .= " WHERE CardNo=?";
			$updateArgs[] = $card_no;

			$updateP = $dbc->prepare_statement($updateQ);
			$updateR = $dbc->exec_statement($updateP,$updateArgs);
			if ($updateR === False) $ret = False;
		}

		return $ret;
	}
}

?>
