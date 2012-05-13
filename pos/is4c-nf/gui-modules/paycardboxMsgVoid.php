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

$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');
if (!function_exists("paycard_reset")) 
	include_once(realpath(dirname(__FILE__)."/../cc-modules/lib/paycardLib.php"));

class paycardboxMsgVoid extends BasicPage {

	function preprocess(){
		global $CORE_LOCAL,$CORE_PATH;
		$CORE_LOCAL->set("inputMasked",1);
		// check for posts before drawing anything, so we can redirect
		if( isset($_REQUEST['reginput'])) {
			$input = strtoupper(trim($_REQUEST['reginput']));
			// CL always exits
			if( $input == "CL") {
				paycard_reset();
				$CORE_LOCAL->set("msgrepeat",1);
				$CORE_LOCAL->set("strRemembered",'TO');
				$CORE_LOCAL->set("toggletax",0);
				$CORE_LOCAL->set("endorseType","");
				$CORE_LOCAL->set("togglefoodstamp",0);
				$CORE_LOCAL->set("inputMasked",0);
				$this->change_page($CORE_PATH."gui-modules/pos2.php");
				return False;
			}
	
			$continue = false;
			// when voiding tenders, the input must be an FEC's passcode
			if( $CORE_LOCAL->get("paycard_mode") == PAYCARD_MODE_VOID && $input != "" && substr($input,-2) != "CL") {
				$sql = "select emp_no, FirstName, LastName from employees" .
					" where EmpActive=1 and frontendsecurity>=11 and AdminPassword=".(int)$input;
				$db = Database::pDataConnect();
				$result = $db->query($sql);
				if( $db->num_rows($result) > 0) {
					$CORE_LOCAL->set("adminP",$input);
					$continue = true;
				}
			}
			// when voiding items, no code is necessary, only confirmation
			if( $CORE_LOCAL->get("paycard_mode") != PAYCARD_MODE_VOID && $input == "")
				$continue = true;
			// go?
			if( $continue) {
				// send the request, then disable the form
				$CORE_LOCAL->set("inputMasked",0);
				$this->add_onload_command('paycard_submitWrapper();');
				$this->action = "onsubmit=\"return false;\"";
			}
			// if we're still here, display prompt again
		} // post?
		else if ($CORE_LOCAL->get("paycard_mode") == PAYCARD_MODE_AUTH){
			// call paycard_void on first load to set up
			// transaction and check for problems
			$id = $CORE_LOCAL->get("paycard_id");
			foreach($CORE_LOCAL->get("RegisteredPaycardClasses") as $rpc){
				$myObj = new $rpc();
				if ($myObj->handlesType($CORE_LOCAL->get("paycard_type"))){
					$ret = $myObj->paycard_void($id);
					if (isset($ret['output']) && !empty($ret['output'])){
						$CORE_LOCAL->set("boxMsg",$ret['output']);
						$this->change_page($CORE_PATH."gui-modules/boxMsg2.php");
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
		if( $mode == PAYCARD_MODE_VOIDITEM) {
			echo paycard_msgBox($type,"Void " . paycard_moneyFormat($amt) . " Gift Card?","","[enter] to continue voiding<br>[clear] to cancel the void");
		} else if( $amt > 0) {
			echo paycard_msgBox($type,"Void " . paycard_moneyFormat($amt) . " Payment?","Please enter password then","[enter] to continue voiding or<br>[clear] to cancel the void");
		} else {
			echo paycard_msgBox($type,"Void " . paycard_moneyFormat($amt) . " Refund?","Please enter password then","[enter] to continue voiding or<br>[clear] to cancel the void");
		}
		$CORE_LOCAL->set("msgrepeat",2);
		?>
		</div>
		<?php
	}
}

new paycardboxMsgVoid();
