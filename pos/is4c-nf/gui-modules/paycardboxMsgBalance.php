<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

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

class paycardboxMsgBalance extends PaycardProcessPage {

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
	
			// when checking balance, no input is confirmation to proceed
			if( $input == "") {
				$this->add_onload_command("paycard_submitWrapper();");
				$this->action = "onsubmit=\"return false;\"";
			}
			// any other input is unrecognized, display prompt again
		} // post?
		return True;
	}

	function body_content(){
		global $IS4C_LOCAL;
		?>
		<div class="baseHeight">
		<?php
		echo paycard_msgBox(PAYCARD_TYPE_GIFT,"Check Card Balance?",
			"If you proceed, you <b>cannot void</b> any previous action on this card!",
			"[enter] to continue<br>[clear] to cancel");
		$IS4C_LOCAL->set("msgrepeat",2);
		?>
		</div>
		<?php
	}
}

new paycardboxMsgBalance();
