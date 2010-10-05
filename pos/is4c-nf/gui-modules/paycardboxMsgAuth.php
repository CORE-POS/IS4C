<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op
    Modifications copyright 2010 Whole Foods Co-op

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
if (!class_exists("PaycardProcessPage")) include_once($_SESSION["INCLUDE_PATH"]."/gui-class-lib/PaycardProcessPage.php");
if (!function_exists("paycard_reset")) require_once($_SESSION["INCLUDE_PATH"]."/lib/paycardLib.php");
if (!function_exists("printfooter")) require_once($_SESSION["INCLUDE_PATH"]."/lib/drawscreen.php");
if (!isset($IS4C_LOCAL)) include($_SESSION["INCLUDE_PATH"]."/lib/LocalStorage/conf.php");

class paycardboxMsgAuth extends PaycardProcessPage {

	function preprocess(){
		global $IS4C_LOCAL;
		// check for posts before drawing anything, so we can redirect
		if( isset($_REQUEST['reginput'])) {
			$input = strtoupper(trim($_REQUEST['reginput']));
			// CL always exits
			if( $input == "CL") {
				$IS4C_LOCAL->set("msgrepeat",0);
				$IS4C_LOCAL->set("toggletax",0);
				$IS4C_LOCAL->set("endorseType","");
				$IS4C_LOCAL->set("togglefoodstamp",0);
				$IS4C_LOCAL->set("ccTermOut","resettotal:".
					str_replace(".","",sprintf("%.2f",$IS4C_LOCAL->get("amtdue"))));
				paycard_reset();
				header("Location: /gui-modules/pos2.php");
				return False;
			}
			else if ($input == ""){
				if ($this->validate_amount()){
					$this->action = "onsubmit=\"return false;\"";	
					$this->add_onload_command("paycard_submitWrapper();");
				}
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

	function validate_amount(){
		global $IS4C_LOCAL;
		$amt = $IS4C_LOCAL->get("paycard_amount");
		$due = $IS4C_LOCAL->get("amtdue");
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
		global $IS4C_LOCAL;
		?>
		<div class="baseHeight">
		<?php
		// generate message to print
		$type = $IS4C_LOCAL->get("paycard_type");
		$mode = $IS4C_LOCAL->get("paycard_mode");
		$amt = $IS4C_LOCAL->get("paycard_amount");
		$due = $IS4C_LOCAL->get("amtdue");
		if( !is_numeric($amt) || abs($amt) < 0.005) {
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
			echo paycard_msgBox($type,"Tender ".paycard_moneyFormat($amt)."?","","[enter] to continue if correct<br>Enter a different amount if incorrect<br>[clear] to cancel");
		} else if( $amt < 0) {
			echo paycard_msgBox($type,"Refund ".paycard_moneyFormat($amt)."?","","[enter] to continue if correct<br>Enter a different amount if incorrect<br>[clear] to cancel");
		} else {
			echo paycard_errBox($type,"Invalid Entry",
				"Enter a different amount","[clear] to cancel");
		}
		$IS4C_LOCAL->set("msgrepeat",2);
		?>
		</div>
		<?php
	}
}

new paycardboxMsgAuth();
