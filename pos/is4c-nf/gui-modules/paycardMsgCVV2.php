<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

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
if (!class_exists("MainFramePage")) include_once($_SESSION["INCLUDE_PATH"]."/gui-class-lib/MainFramePage.php");
if (!function_exists("paycard_reset")) require_once($_SESSION["INCLUDE_PATH"]."/cc-modules/lib/paycardLib.php");
if (!function_exists("changeCurrentPage")) require_once($_SESSION["INCLUDE_PATH"]."/gui-base.php");
if (!isset($CORE_LOCAL)) include($_SESSION["INCLUDE_PATH"]."/lib/LocalStorage/conf.php");

class paycardMsgCVV2 extends MainFramePage {

	function preprocess(){
		global $CORE_LOCAL;
		// check for posts before drawing anything, so we can redirect
		if( isset($_POST['input'])) {
			$input = strtoupper(trim($_POST['input']));
			// CL always exits
			if( $input == "CL") {
				$CORE_LOCAL->set("msgrepeat",0);
				$CORE_LOCAL->set("toggletax",0);
				$CORE_LOCAL->set("togglefoodstamp",0);
				paycard_reset();
				changeCurrentPageJS("/gui-modules/pos2.php");
				return False;
			}
	
			// no input is confirmation to proceed
			if( $input != "" && is_numeric($input)) {
				$CORE_LOCAL->set("paycard_cvv2",$input);
				changeCurrentPageJS("/paycardAuthorize.php");
				return False;
			}
			// if we're still here, we haven't accepted a valid amount yet; display prompt again
		} // post?
		return True;
	}

	function body_content(){
		global $CORE_LOCAL;
	?>
	<form action="/gui-modules/paycardMsgCVV2.php" method=post name="form1" tabindex="0">
	<input type=hidden name="input" size=20>
	</form>
	<div class="baseHeight">
	<?php
		// generate message to print
		paycard_msgBox($type,"Requires CVV2",
				"Enter Verification # from back of card","[clear] to cancel");
		$CORE_LOCAL->set("msgrepeat",2);
	?>
	</div>
	<script type="text/javascript">
	var msgrepeat=2;
	</script>
	<?php
	}
}

new paycardMsgCVV2(0,1);
