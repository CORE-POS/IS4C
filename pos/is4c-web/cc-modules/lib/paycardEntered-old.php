<?php
/*******************************************************************************

    Copyright 2007 Whole Foods Co-op

    This file is part of IS4C.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

if (!class_exists("Parser")) include_once($_SESSION["INCLUDE_PATH"]."/parser-class-lib/Parser.php");
if (!function_exists("paycard_reset")) include_once($_SESSION["INCLUDE_PATH"]."/lib/paycardLib.php");
if (!isset($IS4C_LOCAL)) include($_SESSION["INCLUDE_PATH"]."/lib/LocalStorage/conf.php");

class paycardEntered extends Parser {
	var $swipestr;
	var $swipetype;
	var $manual;
	function check($str){
		if (substr($str,-1,1) == "?"){
			$this->swipestr = $str;
			$this->swipetype = PAYCARD_TYPE_UNKNOWN;
			$this->manual = False;
			return True;
		}
		elseif (is_numeric($str) && strlen($str) >= 16){
			$this->swipestr = $str;
			$this->swipetype = PAYCARD_TYPE_UNKNOWN;
			$this->manual = True;
			return True;
		}
		elseif (is_numeric(substr($str,2)) && strlen($str) >= 18){
			$this->swipestr = $str;
			$this->swipetype = PAYCARD_TYPE_UNKNOWN;
			$this->manual = True;
			return True;
		}
		elseif (strstr($str,"?") !== False && strstr($str,"%") !== False){
			// most likely track data plus garbage
			// make sure it doesn't get passed on to
			// the other parse modules
			$this->swipestr = "BAD CARD";
			$this->swipetype = PAYCARD_TYPE_UNKNOWN;
			$this->manual = False;
			return True;
		}
		return False;
	}

	function parse($str){
		$success = False;
		$str = $this->swipestr;
		if( substr($str,0,2) == "PV") {
			$success = $this->paycard_entered(PAYCARD_MODE_BALANCE, substr($str,2), $this->manual, $this->swipetype);
		} else if( substr($str,0,2) == "AV") {
			$success = $this->paycard_entered(PAYCARD_MODE_ADDVALUE, substr($str,2), $this->manual, $this->swipetype);
		} else if( substr($str,0,2) == "AC") {
			$success = $this->paycard_entered(PAYCARD_MODE_ACTIVATE, substr($str,2), $this->manual, $this->swipetype);
		} else if( substr($str,0,2) == "VD") {
			$success = $this->paycard_entered(PAYCARD_MODE_VOID, substr($str,2), $this->manual, $this->swipetype);
		} else if( substr($str,0,2) == "FC") {
			$success = $this->paycard_entered(PAYCARD_MODE_NONPOS_AUTH, substr($str,2), $this->manual, $this->swipetype);
		} else {
			$success = $this->paycard_entered(PAYCARD_MODE_AUTH, $str, $this->manual, $this->swipetype);
		}
		// if successful, paycard_entered() redirects to a confirmation page and exit()s; if we're still here, there was an error, so reset all data
		if (!$success)
			paycard_reset();
		return False;
	}

	function paycard_entered($mode,$card,$manual,$type){
		global $IS4C_LOCAL;
		// initialize
		$validate = true; // run Luhn's on PAN, check expiration date
		paycard_reset();
		$IS4C_LOCAL->set("paycard_mode",$mode);
		$IS4C_LOCAL->set("paycard_manual",($manual ? 1 : 0));

		// error checks based on transaction
		if( $mode == PAYCARD_MODE_AUTH) {
			if( $IS4C_LOCAL->get("ttlflag") != 1) { // must subtotal before running card
				return paycard_msgBox($type,"No Total",
					"Transaction must be totaled before tendering or refunding","[clear] to cancel");
			} else if( abs($IS4C_LOCAL->get("amtdue")) < 0.005) { // can't tender for more than due
				return paycard_msgBox($type,"No Total",
					"Nothing to tender or refund","[clear] to cancel");
			}
		}

		// check for pre-validation override
		if( strtoupper(substr($card,0,1)) == 'O') {
			$validate = false;
			$card = substr($card, 1);
		}
	
		// parse card data
		if( $IS4C_LOCAL->get("paycard_manual")) {
			if( strtoupper(substr($card,0,4)) == "TEST" && !paycard_live($type)) {
				// in testing mode, we have shorthand for various training card numbers
				if( $type == PAYCARD_TYPE_CREDIT) {
					switch( strtoupper(substr($card,4))) {
					case "VISA":  $card = "4455010000000001"    . "12" . "09";  break; 
					case "MC":    $card = "5233272716340016"    . "05" . "08";  break;
					case "AMEX":  $card = "371449635398431"     . "05" . "08";  break;
					case "DISC":  $card = "6011031100333334"    . "06" . "08";  break;
					case "DC":    $card = "38555565010005"      . "11" . "07";  break;
					case "DCMC":  $card = "36555501890009"      . "11" . "07";  break;
					} // switch test credit card name
				} else if( $type == PAYCARD_TYPE_GIFT) {
					switch( strtoupper(substr($card,4))) {
					case "GIFT1": $card = "7018525757980004473";  break; // valutec test card, no expiration/conf code
					case "GIFT2": $card = "7018525757980004481";  break; // valutec test card, no expiration/conf code
					} // switch test gift card name
				} // switch test card name
			} // test card shortcuts
			// now make sure it's numeric
			if( !ctype_digit($card) || strlen($card) < 18) { // shortest known card # is 14 digits, plus MMYY
				return paycard_msgBox($type,"Manual Entry Unknown",
					"Please enter card data like:<br>CCCCCCCCCCCCCCCCMMYY","[clear] to cancel");
			}
			// split up input (and check for the Concord test card)
			if ($type == PAYCARD_TYPE_UNKNOWN){
				$type = paycard_type($card);
			}
			if( $type == PAYCARD_TYPE_GIFT) {
				$IS4C_LOCAL->set("paycard_PAN",$card); // our gift cards have no expiration date or conf code
			} else if( !paycard_live($type) && substr($card,0,7) == "9999999") {
				// fill in EFSnet's test visa account, since the physical test cards only work in standalone
				$IS4C_LOCAL->set("paycard_PAN","4455010000000001");
				$IS4C_LOCAL->set("paycard_exp","1209");
			} else {
				$IS4C_LOCAL->set("paycard_PAN",substr($card,0,-4));
				$IS4C_LOCAL->set("paycard_exp",substr($card,-4,4));
			}
		} else {
			// swiped magstripe (reference to ISO format at end of this file)
			$stripe = paycard_magstripe($card);
			if( !is_array($stripe)) {
				return paycard_errBox($type,$IS4C_LOCAL->get("paycard_manual")."Card Data Invalid","Please swipe again or type in manually","[clear] to cancel");
			}
			// check for the Concord test card
			if( !paycard_live(PAYCARD_TYPE_CREDIT) && substr($stripe["pan"],0,7) == "9999999") {
				$stripe = paycard_magstripe("%B4455010000000001^TEST/VISA^0912101?;4455010000000001=0912101?");
				if( !is_array($stripe)) {
					return paycard_errBox($type,
						"Test Card Malfunction","Please contact IT","[clear] to cancel");
				}
			}
			$IS4C_LOCAL->set("paycard_PAN",$stripe["pan"]);
			$IS4C_LOCAL->set("paycard_exp",$stripe["exp"]);
			$IS4C_LOCAL->set("paycard_name",$stripe["name"]);
			$IS4C_LOCAL->set("paycard_tr1",$stripe["tr1"]);
			$IS4C_LOCAL->set("paycard_tr2",$stripe["tr2"]);
			$IS4C_LOCAL->set("paycard_tr3",$stripe["tr3"]);
		} // manual/swiped

		// determine card issuer and type
		$IS4C_LOCAL->set("paycard_type",paycard_type($IS4C_LOCAL->get("paycard_PAN")));
		$IS4C_LOCAL->set("paycard_issuer",paycard_issuer($IS4C_LOCAL->get("paycard_PAN")));
	
		// if we knew the type coming in, make sure it agrees
		if( $type != PAYCARD_TYPE_UNKNOWN && $type != $IS4C_LOCAL->get("paycard_type")) {
			paycard_reset();
			return paycard_msgBox($type,"Type Mismatch",
				"Card number does not match card type","[clear] to cancel");
		}

		foreach($IS4C_LOCAL->get("RegisteredPaycardClasses") as $rpc){
			if (!class_exists($rpc)) include_once($_SESSION["INCLUDE_PATH"]."/cc-modules/$rpc.php");
			$myObj = new $rpc();
			if ($myObj->handlesType($IS4C_LOCAL->get("paycard_type")))
				return $myObj->entered($validate);
		}

		paycard_reset();
		return paycard_errBox(PAYCARD_TYPE_UNKNOWN,"Unknown Card Type ".$IS4C_LOCAL->get("paycard_type"),"","[clear] to cancel");
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
