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

class paycardboxMsgVoid extends PaycardProcessPage {

	protected $mask_input = True;

	function preprocess(){
		global $CORE_LOCAL;
		// check for posts before drawing anything, so we can redirect
		if( isset($_REQUEST['reginput'])) {
			$input = strtoupper(trim($_REQUEST['reginput']));
			// CL always exits
			if( $input == "CL") {
				PaycardLib::paycard_reset();
				$CORE_LOCAL->set("msgrepeat",1);
				$CORE_LOCAL->set("strRemembered",'TO');
				$CORE_LOCAL->set("toggletax",0);
				$CORE_LOCAL->set("togglefoodstamp",0);
				$this->change_page($this->page_url."gui-modules/pos2.php");
				return False;
			}
	
			$continue = false;
			// when voiding tenders, the input must be an FEC's passcode
			if( $CORE_LOCAL->get("paycard_mode") == PaycardLib::PAYCARD_MODE_VOID && $input != "" && substr($input,-2) != "CL") {
				$db = Database::pDataConnect();
				$passwd = $db->escape($input);
				$sql = "select emp_no, FirstName, LastName from employees" .
					" where EmpActive=1 and frontendsecurity>=11 and AdminPassword='$passwd'";
				$result = $db->query($sql);
				if( $db->num_rows($result) > 0) {
					$CORE_LOCAL->set("adminP",$input);
					$continue = true;
				}
			}
			// when voiding items, no code is necessary, only confirmation
			if( $CORE_LOCAL->get("paycard_mode") != PaycardLib::PAYCARD_MODE_VOID && $input == "")
				$continue = true;
			// go?
			if( $continue) {
				// send the request, then disable the form
				$this->add_onload_command('paycard_submitWrapper();');
				$this->action = "onsubmit=\"return false;\"";
			}
			// if we're still here, display prompt again
		} // post?
		else if ($CORE_LOCAL->get("paycard_mode") == PaycardLib::PAYCARD_MODE_AUTH){
			// call paycard_void on first load to set up
			// transaction and check for problems
			$id = $CORE_LOCAL->get("paycard_id");
			foreach($CORE_LOCAL->get("RegisteredPaycardClasses") as $rpc){
				$myObj = new $rpc();
				if ($myObj->handlesType($CORE_LOCAL->get("paycard_type"))){
					$ret = $myObj->paycard_void($id);
					if (isset($ret['output']) && !empty($ret['output'])){
						$CORE_LOCAL->set("boxMsg",$ret['output']);
						$this->change_page($this->page_url."gui-modules/boxMsg2.php");
						return False;
					}
					break;
				}
			}
		}
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
		if( $mode == PaycardLib::PAYCARD_MODE_VOIDITEM) {
			echo PaycardLib::paycard_msgBox($type,"Void " . PaycardLib::paycard_moneyFormat($amt) . " Gift Card?","","[enter] to continue voiding<br>[clear] to cancel the void");
		} else if( $amt > 0) {
			echo PaycardLib::paycard_msgBox($type,"Void " . PaycardLib::paycard_moneyFormat($amt) . " Payment?","Please enter password then","[enter] to continue voiding or<br>[clear] to cancel the void");
		} else {
			echo PaycardLib::paycard_msgBox($type,"Void " . PaycardLib::paycard_moneyFormat($amt) . " Refund?","Please enter password then","[enter] to continue voiding or<br>[clear] to cancel the void");
		}
		$CORE_LOCAL->set("msgrepeat",2);
		?>
		</div>
		<?php
	}
}

new paycardboxMsgVoid();
