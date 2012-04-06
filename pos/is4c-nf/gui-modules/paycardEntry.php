<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op
    Modifications copyright 2010 Whole Foods Co-op

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

$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }

if (!class_exists("BasicPage")) include_once($CORE_PATH."gui-class-lib/BasicPage.php");
if (!function_exists("paycard_reset")) require_once($CORE_PATH."lib/paycardLib.php");
if (!function_exists("printfooter")) require_once($CORE_PATH."lib/drawscreen.php");
if (!function_exists("sigTermObject")) require_once($CORE_PATH."lib/lib.php");
if (!isset($CORE_LOCAL)) include($CORE_PATH."lib/LocalStorage/conf.php");

class paycardEntry extends BasicPage {

	var $errors;

	function preprocess(){
		global $CORE_LOCAL,$CORE_PATH;
		$this->errors = "";
		// check for posts before drawing anything, so we can redirect
		if(isset($_REQUEST['reginput'])) {
			$input = $_REQUEST['reginput'];
			// CL always exits
			if( strtoupper($input) == "CL") {
				$CORE_LOCAL->set("msgrepeat",0);
				$CORE_LOCAL->set("toggletax",0);
				$CORE_LOCAL->set("endorseType","");
				$CORE_LOCAL->set("togglefoodstamp",0);
				$CORE_LOCAL->set("ccTermOut","resettotal:".
					str_replace(".","",sprintf("%.2f",$CORE_LOCAL->get("amtdue"))));
				$st = sigTermObject();
				if (is_object($st))
					$st->WriteToScale($CORE_LOCAL->get("ccTermOut"));
				paycard_reset();
				$CORE_LOCAL->set("inputMasked",0);
				header("Location: {$CORE_PATH}gui-modules/pos2.php");
				return False;
			}
			else if ($input[0] == "?" || strlen($input) >= 18){
				/* card data was entered
				   extract the pan, expiration, and/or track data
					
				   PAN and track data are NOT stored in PHP session.
				   They only exist in memory and will be gone when
				   this script finishes executing
				*/
				$pan = array();
				if ($input[0] != "?"){
					$CORE_LOCAL->set("paycard_manual",1);
					if (!ctype_digit($input)){
						$this->errors = "Entry unknown. Please enter data like:<br>
							CCCCCCCCCCCCCCCCMMYY";
						return True;
					}
					$pan['pan'] = substr($input,0,-4);
					$CORE_LOCAL->set("paycard_exp",substr($input,-4,4));
				}
				else {
					$stripe = paycard_magstripe($card);
					if (!is_array($stripe)){
						$this->errors = "Bad swipe. Please try again or type in manually";
						return True;
					}
					$pan['pan'] = $stripe['pan'];
					$pan['tr1'] = $stripe['tr1'];
					$pan['tr2'] = $stripe['tr2'];
					$pan['tr3'] = $stripe['tr3'];
					$CORE_LOCAL->set("paycard_exp",$stripe["exp"]);
					$CORE_LOCAL->set("paycard_name",$stripe["name"]);
				}
				$CORE_LOCAL->set("paycard_type",paycard_type($pan['pan']));
				$CORE_LOCAL->set("paycard_issuer",paycard_issuer($pan['pan']));

				/* find the module for this card type */
				$ccMod = null;
				foreach($CORE_LOCAL->get("RegisteredPaycardClasses") as $rpc){
					if (!class_exists($rpc)) include_once($CORE_PATH."cc-modules/$rpc.php");
					$ccMod = new $rpc();
					if ($ccMod->handlesType($CORE_LOCAL->get("paycard_type")))
						break;
				}
				if ($ccMod == null){
					$this->errors = "Unknown or unsupported card type";
					return True;
				}

				/* module performs additional validation */
				$ccMod->setPAN($pan);
				$chk = $ccMod->entered(True,array());
				if(isset($chk['output']) && !empty($chk['output'])){
					$this->errors = $chk;
					return True;
				}

				/* submit the transaction to the gateway */
				$json = array();
				$json['main_frame'] = $CORE_PATH.'gui-modules/paycardSuccess.php';
				$json['receipt'] = false;
				$result = $ccMod->doSend($CORE_LOCAL->get("paycard_mode"));
				if ($result == PAYCARD_ERR_OK){
					$json = $ccMod->cleanup($json);
					$CORE_LOCAL->set("strRemembered","");
					$CORE_LOCAL->set("msgrepeat",0);
				}
				else {
					paycard_reset();
					$CORE_LOCAL->set("msgrepeat",0);
					$json['main_frame'] = $CORE_PATH.'gui-modules/boxMsg2.php';
				}
				/* transaction complete; go to success or error page */
				header("Location: ".$json['main_frame']);
				return False;
			}
			else if( substr(strtoupper($input),-2) != "CL") {
				// any other input is an alternate amount
				$CORE_LOCAL->set("paycard_amount","invalid");
				if( is_numeric($input))
					$CORE_LOCAL->set("paycard_amount",$input/100);
			}
		} // form post to self
		else {
			paycard_reset();
			$CORE_LOCAL->set("paycard_mode",PAYCARD_MODE_AUTH);
			$CORE_LOCAL->set("paycard_type",PAYCARD_TYPE_CREDIT);
			$CORE_LOCAL->set("paycard_amount",$CORE_LOCAL->get("amtdue"));		
			$CORE_LOCAL->set("paycard_manual",0);
		} 

		$CORE_LOCAL->set("inputMasked",1);
		return True;
	}

	function validate_amount(){
		global $CORE_LOCAL;
		$amt = $CORE_LOCAL->get("paycard_amount");
		$due = $CORE_LOCAL->get("amtdue");
		if( !is_numeric($amt) || abs($amt) < 0.005) {
		} else if( $amt > 0 && $due < 0) {
		} else if( $amt < 0 && $due > 0) {
		} else if( abs($amt) > abs($due)) {
		} else {
			return True;
		}
		return False;
	}

	function body_content(){
		global $CORE_LOCAL;
		$this->input_header();
		?>
		<div class="baseHeight">
		<?php
		// generate message to print
		$type = $CORE_LOCAL->get("paycard_type");
		$mode = $CORE_LOCAL->get("paycard_mode");
		$amt = $CORE_LOCAL->get("paycard_amount");
		$due = $CORE_LOCAL->get("amtdue");
		if (!empty($this->errors)){
			if (is_array($this->errors)) echo $this->errors['output'];
			else echo paycard_msgBox($type,$this->errors);
		}
		elseif( !is_numeric($amt) || abs($amt) < 0.005) {
			echo paycard_msgBox($type,"Invalid Amount: $amt $due",
				"Enter a different amount","[clear] to cancel");
		} else if( $amt > 0 && $due < 0) {
			echo paycard_msgBox($type,"Invalid Amount",
				"Enter a negative amount","[clear] to cancel");
		} else if( $amt < 0 && $due > 0) {
			echo paycard_msgBox($type,"Invalid Amount",
				"Enter a positive amount","[clear] to cancel");
		} else if( abs($amt) > abs($due)) {
			echo paycard_msgBox($type,"Invalid Amount",
				"Enter a lesser amount","[clear] to cancel");
		} else if( $amt > 0) {
			echo paycard_msgBox($type,"Tender ".paycard_moneyFormat($amt)."?","","[swipe] to continue if correct<br>Enter a different amount if incorrect<br>[clear] to cancel");
		} else if( $amt < 0) {
			echo paycard_msgBox($type,"Refund ".paycard_moneyFormat($amt)."?","","[swipe] to continue if correct<br>Enter a different amount if incorrect<br>[clear] to cancel");
		} else {
			echo paycard_errBox($type,"Invalid Entry",
				"Enter a different amount","[clear] to cancel");
		}
		$CORE_LOCAL->set("msgrepeat",2);
		?>
		</div>
		<?php
		echo "<div id=\"footer\">";
		echo printfooter();
		echo "</div>";
	}
}

new paycardEntry();
