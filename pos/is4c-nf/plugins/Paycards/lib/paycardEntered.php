<?php
/*******************************************************************************

    Copyright 2007 Whole Foods Co-op

    This file is part of IT CORE.

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

if (!class_exists("PaycardLib")) 
	include_once(realpath(dirname(__FILE__)."/paycardLib.php"));
if (!isset($CORE_LOCAL)){
	include_once(realpath(dirname(__FILE__)."/LS_Access.php"));
	$CORE_LOCAL = new LS_Access();	
}

class paycardEntered extends Parser {
	var $swipestr;
	var $swipetype;
	var $manual;

	function check($str){
		if (substr($str,-1,1) == "?"){
			$this->swipestr = $str;
			$this->swipetype = PaycardLib::PAYCARD_TYPE_UNKNOWN;
			$this->manual = False;
			return True;
		}
		elseif (substr($str,0,8) == "02E60080" || substr($str,0,7)=="2E60080"){
			$this->swipestr = $str;
			$this->swipetype = PaycardLib::PAYCARD_TYPE_ENCRYPTED;
			$this->manual = False;
			return True;
		}
		elseif (is_numeric($str) && strlen($str) >= 16){
			$this->swipestr = $str;
			$this->swipetype = PaycardLib::PAYCARD_TYPE_UNKNOWN;
			$this->manual = True;
			return True;
		}
		elseif (is_numeric(substr($str,2)) && strlen($str) >= 18){
			$this->swipestr = $str;
			$this->swipetype = PaycardLib::PAYCARD_TYPE_UNKNOWN;
			$this->manual = True;
			return True;
		}
		return False;
	}

	function parse($str){
		$ret = array();
		$str = $this->swipestr;
		if( substr($str,0,2) == "PV") {
			$ret = $this->paycard_entered(PaycardLib::PAYCARD_MODE_BALANCE, substr($str,2), $this->manual, $this->swipetype);
		} else if( substr($str,0,2) == "AV") {
			$ret = $this->paycard_entered(PaycardLib::PAYCARD_MODE_ADDVALUE, substr($str,2), $this->manual, $this->swipetype);
		} else if( substr($str,0,2) == "AC") {
			$ret = $this->paycard_entered(PaycardLib::PAYCARD_MODE_ACTIVATE, substr($str,2), $this->manual, $this->swipetype);
		} else {
			$ret = $this->paycard_entered(PaycardLib::PAYCARD_MODE_AUTH, $str, $this->manual, $this->swipetype);
		}
		// if successful, paycard_entered() redirects to a confirmation page and exit()s; if we're still here, there was an error, so reset all data
		if ($ret['main_frame'] == false)
			PaycardLib::paycard_reset();
		return $ret;
	}

	function paycard_entered($mode,$card,$manual,$type){
		global $CORE_LOCAL;
		$ret = $this->default_json();
		// initialize
		$validate = true; // run Luhn's on PAN, check expiration date
		PaycardLib::paycard_reset();
		$CORE_LOCAL->set("paycard_mode",$mode);
		$CORE_LOCAL->set("paycard_manual",($manual ? 1 : 0));

		// error checks based on transaction
		if( $mode == PaycardLib::PAYCARD_MODE_AUTH) {
			if( $CORE_LOCAL->get("ttlflag") != 1) { // must subtotal before running card
				$ret['output'] = PaycardLib::paycard_msgBox($type,"No Total",
					"Transaction must be totaled before tendering or refunding","[clear] to cancel");
				return $ret;
			} else if( abs($CORE_LOCAL->get("amtdue")) < 0.005) { // can't tender for more than due
				$ret['output'] = PaycardLib::paycard_msgBox($type,"No Total",
					"Nothing to tender or refund","[clear] to cancel");
				return $ret;
			}
		}

		// check for pre-validation override
		if( strtoupper(substr($card,0,1)) == 'O') {
			$validate = false;
			$card = substr($card, 1);
		}
	
		// parse card data
		if( $CORE_LOCAL->get("paycard_manual")) {
			// make sure it's numeric
			if( !ctype_digit($card) || strlen($card) < 18) { // shortest known card # is 14 digits, plus MMYY
				$ret['output'] = PaycardLib::paycard_msgBox($type,"Manual Entry Unknown",
					"Please enter card data like:<br>CCCCCCCCCCCCCCCCMMYY","[clear] to cancel");
				return $ret;
			}
			// split up input (and check for the Concord test card)
			if ($type == PaycardLib::PAYCARD_TYPE_UNKNOWN){
				$type = PaycardLib::paycard_type($card);
			}
			if( $type == PaycardLib::PAYCARD_TYPE_GIFT) {
				$CORE_LOCAL->set("paycard_PAN",$card); // our gift cards have no expiration date or conf code
			} else {
				$CORE_LOCAL->set("paycard_PAN",substr($card,0,-4));
				$CORE_LOCAL->set("paycard_exp",substr($card,-4,4));
			}
		} 
		else if ($type == PaycardLib::PAYCARD_TYPE_ENCRYPTED){
			// add leading zero back to fix hex encoding, if needed
			if (substr($card,0,7)=="2E60080")
				$card = "0".$card;
			$CORE_LOCAL->set("paycard_PAN",$card);
		} 
		else {
			// swiped magstripe (reference to ISO format at end of this file)
			$stripe = PaycardLib::paycard_magstripe($card);
			if( !is_array($stripe)) {
				$ret['output'] = PaycardLib::paycard_errBox($type,$CORE_LOCAL->get("paycard_manual")."Card Data Invalid","Please swipe again or type in manually","[clear] to cancel");
				return $ret;
			}
			$CORE_LOCAL->set("paycard_PAN",$stripe["pan"]);
			$CORE_LOCAL->set("paycard_exp",$stripe["exp"]);
			$CORE_LOCAL->set("paycard_name",$stripe["name"]);
			$CORE_LOCAL->set("paycard_tr1",$stripe["tr1"]);
			$CORE_LOCAL->set("paycard_tr2",$stripe["tr2"]);
			$CORE_LOCAL->set("paycard_tr3",$stripe["tr3"]);
		} // manual/swiped

		// determine card issuer and type
		$CORE_LOCAL->set("paycard_type",PaycardLib::paycard_type($CORE_LOCAL->get("paycard_PAN")));
		$CORE_LOCAL->set("paycard_issuer",PaycardLib::paycard_issuer($CORE_LOCAL->get("paycard_PAN")));

		/* check card type. Credit is default. */
		$type = $CORE_LOCAL->get("CacheCardType");
		if ($type == ''){
			$type = 'CREDIT';
			$CORE_LOCAL->set("CacheCardType","CREDIT");
		}
	
		/* assign amount due. EBT food should use eligible amount */
		$CORE_LOCAL->set("paycard_amount",$CORE_LOCAL->get("amtdue"));
		if ($type == 'EBTFOOD'){
			if ($CORE_LOCAL->get('fntlflag') == 0){
				/* try to automatically do fs total */
				$try = PrehLib::fsEligible();
				if ($try !== True){
					$ret['output'] = PaycardLib::paycard_msgBox($type,"Type Mismatch",
						"Foodstamp eligible amount inapplicable","[clear] to cancel");
					return $ret;
				} 
			}
            /**
              Always validate amount as non-zero
            */
            if ($CORE_LOCAL->get('fsEligible') <= 0.005 && $CORE_LOCAL->get('fsEligible') >= -0.005) {
                $ret['output'] = PaycardLib::paycard_msgBox($type,_('Zero Total'),
                    "Foodstamp eligible amount is zero","[clear] to cancel");
                UdpComm::udpSend('termReset');
                return $ret;
            } 
			$CORE_LOCAL->set("paycard_amount",$CORE_LOCAL->get("fsEligible"));
		}
		if (($type == 'EBTCASH' || $type == 'DEBIT') && $CORE_LOCAL->get('CacheCardCashBack') > 0){
			$CORE_LOCAL->set('paycard_amount',
				$CORE_LOCAL->get('amtdue') + $CORE_LOCAL->get('CacheCardCashBack'));
		}
	
		// if we knew the type coming in, make sure it agrees
		if( $type != PaycardLib::PAYCARD_TYPE_UNKNOWN && $type != $CORE_LOCAL->get("paycard_type")) {
			$ret['output'] = PaycardLib::paycard_msgBox($type,"Type Mismatch",
				"Card number does not match card type","[clear] to cancel");
			return $ret;
		}

		foreach($CORE_LOCAL->get("RegisteredPaycardClasses") as $rpc){
			if (!class_exists($rpc)) continue;
			$myObj = new $rpc();
			if ($myObj->handlesType($CORE_LOCAL->get("paycard_type")))
				return $myObj->entered($validate,$ret);
		}

		$ret['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_UNKNOWN,"Unknown Card Type ".$CORE_LOCAL->get("paycard_type"),"","[clear] to cancel");
		return $ret;
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>	
				<td>Card swipe or card number</td>
				<td>Try to charge amount to card</td>
			</tr>
			<tr>
				<td>PV<i>swipe</i> or PV<i>number</i></td>
				<td>Check balance of gift card</td>
			</tr>
			<tr>
				<td>AC<i>swipe</i> or AC<i>number</i></td>
				<td>Activate gift card</td>
			</tr>
			<tr>
				<td>AV<i>swipe</i> or AV<i>number</i></td>
				<td>Add value to gift card</td>
			</tr>
			</table>";
	}
}

?>
