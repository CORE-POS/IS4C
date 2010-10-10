<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

if (!class_exists("PaycardProcessPage")) include_once($IS4C_PATH."gui-class-lib/PaycardProcessPage.php");
if (!function_exists("paycard_reset")) include_once($IS4C_PATH."lib/paycardLib.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

class paycardboxMsgGift extends PaycardProcessPage {

	function preprocess(){
		global $IS4C_LOCAL,$IS4C_PATH;
		// check for posts before drawing anything, so we can redirect
		if( isset($_REQUEST['reginput'])) {
			$input = strtoupper(trim($_REQUEST['reginput']));
			// CL always exits
			if( $input == "CL") {
				$IS4C_LOCAL->set("msgrepeat",0);
				$IS4C_LOCAL->set("toggletax",0);
				$IS4C_LOCAL->set("endorseType","");
				$IS4C_LOCAL->set("togglefoodstamp",0);
				paycard_reset();
				header("Location: {$IS4C_PATH}gui-modules/pos2.php");
				return False;
			}
	
			// when (de)activating/adding-value, double check that the current amount is acceptable
			// before checking input (similar logic is later when generating the message)
			$amtValid = false;
			$amt = $IS4C_LOCAL->get("paycard_amount");
			if( !is_numeric($amt) || $amt < 0.005) {
			} else {
				// all errors are caught above; here, the amount is okay
				$amtValid = true;
			}
	
			// no input is confirmation to proceed
			if( $input == "" && $amtValid) {
				$this->add_onload_command("paycard_submitWrapper();");
				$this->action = "onsubmit=\"return false;\"";
			}
			else if( $input != "" && substr($input,-2) != "CL") {
				// any other input is an alternate amount
				$IS4C_LOCAL->set("paycard_amount","invalid");
				if( is_numeric($input))
					$IS4C_LOCAL->set("paycard_amount",$input/100);
			}
			// if we're still here, we haven't accepted a valid amount yet; display prompt again
		} // post?
		return True;
	}

	function body_content(){
		global $IS4C_LOCAL;
		?>
		<div class="baseHeight">
		<?php
		// generate message to print
		$type = $IS4C_LOCAL->get("paycard_type");
		$mode = $IS4C_LOCAL->get("paycard_mode");
		$amt = $IS4C_LOCAL->get("paycard_amount");
		if( $amt == 0) {
			if( $mode == PAYCARD_MODE_ACTIVATE)
				echo paycard_msgBox($type,"Enter Activation Amount",
					"Enter the amount to put on the card",
					"[clear] to cancel");
			else if( $mode == PAYCARD_MODE_ADDVALUE)
				echo paycard_msgBox($type,"Enter Add-Value Amount",
					"Enter the amount to put on the card",
					"[clear] to cancel");
		} else if( !is_numeric($amt) || $amt < 0.005) {
			echo paycard_msgBox($type,"Invalid Amount",
				"Enter a positive amount to put on the card",
				"[clear] to cancel");
		} else if( $mode == PAYCARD_MODE_ACTIVATE) {
			echo paycard_msgBox($type,"Activate ".paycard_moneyFormat($amt)."?","",
				"[enter] to continue if correct<br>Enter a different amount if incorrect<br>[clear] to cancel");
		} else if( $mode == PAYCARD_MODE_ADDVALUE) {
			echo paycard_msgBox($type,"Add Value ".paycard_moneyFormat($amt)."?","",
				"[enter] to continue if correct<br>Enter a different amount if incorrect<br>[clear] to cancel");
		}
		$IS4C_LOCAL->set("msgrepeat",2);
		?>
		</div>
		<?php
	}
}

new paycardboxMsgGift(0,1);
