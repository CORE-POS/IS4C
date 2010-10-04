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
if (!class_exists("MainFramePage")) include_once($_SERVER["DOCUMENT_ROOT"]."/gui-class-lib/MainFramePage.php");
if (!function_exists("paycard_reset")) require_once($_SERVER["DOCUMENT_ROOT"]."/lib/paycardLib.php");
if (!function_exists("changeCurrentPage")) require_once($_SERVER["DOCUMENT_ROOT"]."/gui-base.php");
if (!isset($IS4C_LOCAL)) include($_SERVER["DOCUMENT_ROOT"]."/lib/LocalStorage/conf.php");

class paycardMsgCVV2 extends MainFramePage {

	function preprocess(){
		global $IS4C_LOCAL;
		// check for posts before drawing anything, so we can redirect
		if( isset($_POST['input'])) {
			$input = strtoupper(trim($_POST['input']));
			// CL always exits
			if( $input == "CL") {
				$IS4C_LOCAL->set("msgrepeat",0);
				$IS4C_LOCAL->set("toggletax",0);
				// $_SESSION["chargetender"] = 0;
				$IS4C_LOCAL->set("endorseType","");
				$IS4C_LOCAL->set("togglefoodstamp",0);
				paycard_reset();
				changeCurrentPageJS("/gui-modules/pos2.php");
				return False;
			}
	
			// no input is confirmation to proceed
			if( $input != "" && is_numeric($input)) {
				$IS4C_LOCAL->set("paycard_cvv2",$input);
				changeCurrentPageJS("/paycardAuthorize.php");
				return False;
			}
			// if we're still here, we haven't accepted a valid amount yet; display prompt again
		} // post?
		return True;
	}

	function body_content(){
		global $IS4C_LOCAL;
	?>
	<form action="/gui-modules/paycardMsgCVV2.php" method=post name="form1" tabindex="0">
	<input type=hidden name="input" size=20>
	</form>
	<div class="baseHeight">
	<?php
		// generate message to print
		paycard_msgBox($type,"Requires CVV2",
				"Enter Verification # from back of card","[clear] to cancel");
		$IS4C_LOCAL->set("msgrepeat",2);
		$IS4C_LOCAL->set("beep","noBeep"); // to override the errorBeep() that is called inside boxMsg() (which is called inside paycard_msgBox())
	?>
	</div>
	<script type="text/javascript">
	var msgrepeat=2;
	</script>
	<?php
	}
}

new paycardMsgCVV2(0,1);
