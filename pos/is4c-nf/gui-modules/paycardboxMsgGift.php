<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class paycardboxMsgGift extends PaycardProcessPage {

	function preprocess(){
		global $CORE_LOCAL;
		// check for posts before drawing anything, so we can redirect
		if( isset($_REQUEST['reginput'])) {
			$input = strtoupper(trim($_REQUEST['reginput']));
			// CL always exits
			if( $input == "CL") {
				$CORE_LOCAL->set("msgrepeat",0);
				$CORE_LOCAL->set("toggletax",0);
				$CORE_LOCAL->set("togglefoodstamp",0);
				PaycardLib::paycard_reset();
				$this->change_page($this->page_url."gui-modules/pos2.php");
				return False;
			}
	
			// when (de)activating/adding-value, double check that the current amount is acceptable
			// before checking input (similar logic is later when generating the message)
			$amtValid = false;
			$amt = $CORE_LOCAL->get("paycard_amount");
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
				$CORE_LOCAL->set("paycard_amount","invalid");
				if( is_numeric($input))
					$CORE_LOCAL->set("paycard_amount",$input/100);
			}
			// if we're still here, we haven't accepted a valid amount yet; display prompt again
		} // post?
		return True;
	}

	function body_content(){
		global $CORE_LOCAL;
		?>
		<div class="baseHeight">
		<?php
		// generate message to print
		$type = $CORE_LOCAL->get("paycard_type");
		$mode = $CORE_LOCAL->get("paycard_mode");
		$amt = $CORE_LOCAL->get("paycard_amount");
		if( $amt == 0) {
			if( $mode == PaycardLib::PAYCARD_MODE_ACTIVATE)
				echo PaycardLib::paycard_msgBox($type,"Enter Activation Amount",
					"Enter the amount to put on the card",
					"[clear] to cancel");
			else if( $mode == PaycardLib::PAYCARD_MODE_ADDVALUE)
				echo PaycardLib::paycard_msgBox($type,"Enter Add-Value Amount",
					"Enter the amount to put on the card",
					"[clear] to cancel");
		} else if( !is_numeric($amt) || $amt < 0.005) {
			echo PaycardLib::paycard_msgBox($type,"Invalid Amount",
				"Enter a positive amount to put on the card",
				"[clear] to cancel");
		} else if( $mode == PaycardLib::PAYCARD_MODE_ACTIVATE) {
			echo PaycardLib::paycard_msgBox($type,"Activate ".PaycardLib::paycard_moneyFormat($amt)."?","",
				"[enter] to continue if correct<br>Enter a different amount if incorrect<br>[clear] to cancel");
		} else if( $mode == PaycardLib::PAYCARD_MODE_ADDVALUE) {
			echo PaycardLib::paycard_msgBox($type,"Add Value ".PaycardLib::paycard_moneyFormat($amt)."?","",
				"[enter] to continue if correct<br>Enter a different amount if incorrect<br>[clear] to cancel");
		}
		$CORE_LOCAL->set("msgrepeat",2);
		?>
		</div>
		<?php
	}
}

new paycardboxMsgGift(0,1);
