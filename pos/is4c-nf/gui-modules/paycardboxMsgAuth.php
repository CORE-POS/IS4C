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

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class paycardboxMsgAuth extends PaycardProcessPage {

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
				$CORE_LOCAL->set("ccTermOut","resettotal:".
					str_replace(".","",sprintf("%.2f",$CORE_LOCAL->get("amtdue"))));
				$st = MiscLib::sigTermObject();
				if (is_object($st))
					$st->WriteToScale($CORE_LOCAL->get("ccTermOut"));
				PaycardLib::paycard_reset();
				$CORE_LOCAL->set("CachePanEncBlock","");
				$CORE_LOCAL->set("CachePinEncBlock","");
				$CORE_LOCAL->set("CacheCardType","");
				UdpComm::udpSend("termReset");
				$this->change_page($this->page_url."gui-modules/pos2.php");
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
				$CORE_LOCAL->set("paycard_amount","invalid");
				if( is_numeric($input))
					$CORE_LOCAL->set("paycard_amount",$input/100);
			}
			// if we're still here, we haven't accepted a valid amount yet; display prompt again
		} // post?
		return True;
	}

	function validate_amount(){
		global $CORE_LOCAL;
		$amt = $CORE_LOCAL->get("paycard_amount");
		$due = $CORE_LOCAL->get("amtdue");
		if( !is_numeric($amt) || abs($amt) < 0.005) {
		} else if( $amt > 0 && $due < 0) {
		} else if( $amt < 0 && $due > 0) {
		} else if( abs($amt) > abs($due) && $CORE_LOCAL->get("CacheCardType") != "DEBIT" && $CORE_LOCAL->get("CacheCardType") != "EBTCASH") {
		} else {
			return True;
		}
		return False;
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
		$due = $CORE_LOCAL->get("amtdue");
		if( !is_numeric($amt) || abs($amt) < 0.005) {
			echo PaycardLib::paycard_msgBox($type,"Invalid Amount: $amt $due",
				"Enter a different amount","[clear] to cancel");
		} else if( $amt > 0 && $due < 0) {
			echo PaycardLib::paycard_msgBox($type,"Invalid Amount",
				"Enter a negative amount","[clear] to cancel");
		} else if( $amt < 0 && $due > 0) {
			echo PaycardLib::paycard_msgBox($type,"Invalid Amount",
				"Enter a positive amount","[clear] to cancel");
		} else if( abs($amt) > abs($due) && $CORE_LOCAL->get("CacheCardType") != "DEBIT" && $CORE_LOCAL->get("CacheCardType") != "EBTCASH") {
			echo PaycardLib::paycard_msgBox($type,"Invalid Amount",
				"Enter a lesser amount","[clear] to cancel");
		} else if( $amt > 0) {
			$msg = "Tender ".PaycardLib::paycard_moneyFormat($amt);
			if ($CORE_LOCAL->get("CacheCardType") != "")
				$msg .= " as ".$CORE_LOCAL->get("CacheCardType");
			echo PaycardLib::paycard_msgBox($type,$msg."?","","[enter] to continue if correct<br>Enter a different amount if incorrect<br>[clear] to cancel");
		} else if( $amt < 0) {
			echo PaycardLib::paycard_msgBox($type,"Refund ".PaycardLib::paycard_moneyFormat($amt)."?","","[enter] to continue if correct<br>Enter a different amount if incorrect<br>[clear] to cancel");
		} else {
			echo PaycardLib::paycard_errBox($type,"Invalid Entry",
				"Enter a different amount","[clear] to cancel");
		}
		$CORE_LOCAL->set("msgrepeat",2);
		?>
		</div>
		<?php
	}
}

new paycardboxMsgAuth();
