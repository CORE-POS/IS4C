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
if (!function_exists("printfooter")) include_once($IS4C_PATH."lib/drawscreen.php");
if (!function_exists("pDataConnect")) include_once($IS4C_PATH."lib/connect.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");


class paycardboxMsgVoid extends BasicPage {

	function preprocess(){
		global $IS4C_LOCAL,$IS4C_PATH;
		$IS4C_LOCAL->set("inputMasked",1);
		// check for posts before drawing anything, so we can redirect
		if( isset($_REQUEST['reginput'])) {
			$input = strtoupper(trim($_REQUEST['reginput']));
			// CL always exits
			if( $input == "CL") {
				paycard_reset();
				$IS4C_LOCAL->set("msgrepeat",1);
				$IS4C_LOCAL->set("strRemembered",'TO');
				$IS4C_LOCAL->set("toggletax",0);
				$IS4C_LOCAL->set("endorseType","");
				$IS4C_LOCAL->set("togglefoodstamp",0);
				$IS4C_LOCAL->set("inputMasked",0);
				header("Location: {$IS4C_PATH}gui-modules/pos2.php");
				return False;
			}
	
			$continue = false;
			// when voiding tenders, the input must be an FEC's passcode
			if( $IS4C_LOCAL->get("paycard_mode") == PAYCARD_MODE_VOID && $input != "" && substr($input,-2) != "CL") {
				$sql = "select emp_no, FirstName, LastName from employees" .
					" where EmpActive=1 and frontendsecurity>=11 and AdminPassword=".(int)$input;
				$db = pDataConnect();
				$result = $db->query($sql);
				if( $db->num_rows($result) > 0) {
					$IS4C_LOCAL->set("adminP",$input);
					$continue = true;
				}
			}
			// when voiding items, no code is necessary, only confirmation
			if( $IS4C_LOCAL->get("paycard_mode") != PAYCARD_MODE_VOID && $input == "")
				$continue = true;
			// go?
			if( $continue) {
				// send the request, then disable the form
				$IS4C_LOCAL->set("inputMasked",0);
				$this->add_onload_command('paycard_submitWrapper();');
				$this->action = "onsubmit=\"return false;\"";
			}
			// if we're still here, display prompt again
		} // post?
		else if ($IS4C_LOCAL->get("paycard_mode") == PAYCARD_MODE_AUTH){
			// call paycard_void on first load to set up
			// transaction and check for problems
			$id = $IS4C_LOCAL->get("paycard_id");
			foreach($IS4C_LOCAL->get("RegisteredPaycardClasses") as $rpc){
				if (!class_exists($rpc)) 
					include_once($_SESSION["INCLUDE_PATH"]."/cc-modules/$rpc.php");
				$myObj = new $rpc();
				if ($myObj->handlesType($IS4C_LOCAL->get("paycard_type"))){
					$ret = $myObj->paycard_void($id);
					if (isset($ret['output']) && !empty($ret['output'])){
						$IS4C_LOCAL->set("boxMsg",$ret['output']);
						header("Location: {$IS4C_PATH}gui-modules/boxMsg2.php");
						return False;
					}
					break;
				}
			}
		}
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
		if( $mode == PAYCARD_MODE_VOIDITEM) {
			echo paycard_msgBox($type,"Void " . paycard_moneyFormat($amt) . " Gift Card?","","[enter] to continue voiding<br>[clear] to cancel the void");
		} else if( $amt > 0) {
			echo paycard_msgBox($type,"Void " . paycard_moneyFormat($amt) . " Payment?","Please enter password then","[enter] to continue voiding or<br>[clear] to cancel the void");
		} else {
			echo paycard_msgBox($type,"Void " . paycard_moneyFormat($amt) . " Refund?","Please enter password then","[enter] to continue voiding or<br>[clear] to cancel the void");
		}
		$IS4C_LOCAL->set("msgrepeat",2);
		?>
		</div>
		<?php
	}
}

new paycardboxMsgVoid();
