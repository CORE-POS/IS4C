<?php
/*******************************************************************************

    Copyright 2001, 20010 Whole Foods Co-op

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
if (!function_exists("changeCurrentPage")) include_once($_SESSION["INCLUDE_PATH"]."/gui-base.php");
if (!function_exists("printfooter")) include_once($_SESSION["INCLUDE_PATH"]."/lib/drawscreen.php");
if (!function_exists("receipt")) include_once($_SESSION["INCLUDE_PATH"]."/lib/clientscripts.php");
if (!isset($CORE_LOCAL)) include($_SESSION["INCLUDE_PATH"]."/lib/LocalStorage/conf.php");

class paycardSignature extends MainFramePage{

	function preprocess(){
		global $CORE_LOCAL;
		// check for input
		if( isset($_POST["input"])) {
			$input = strtoupper(trim($_POST["input"]));
			$done = False;
			if( $input == "" || $input == "CL") { // [enter] or [clear] skips this step
				$done = True;
			}
			else if (strlen($input) > 0){
				$file = $_POST['input'];
				$saveas = $_SESSION["INCLUDE_PATH"]."/graphics/SigImages/".basename($_POST['input']);
				copy($_POST['input'],$saveas);
				unlink($_POST['input']);
				$CORE_LOCAL->set("CapturedSigFile",basename($saveas));
				if (file_exists($saveas))
					$done = True;
			}

			if ($done){
				if ($CORE_LOCAL->get("SigSlipType") == "")
					$CORE_LOCAL->set("SigSlipType","ccSlip");
				receipt($CORE_LOCAL->get("SigSlipType"));

				changeCurrentPageJS("/gui-modules/paycardSuccess.php");
			}
		}
		return True;
	}

	function body_content(){
		global $CORE_LOCAL;
	?>
	<form action="paycardSignature.php" method=post name="form1" tabindex="0">
	<input type=hidden name="input" size=20>
	</form>
	<div class="baseHeight">
	<?php
		$header = "Signature";
		boxMsg("Waiting for signature<br />Press [Enter] to skip this step",$header);
		$CORE_LOCAL->set("msgrepeat",2);
		$CORE_LOCAL->set("ccTermOut","sig");
	?>
	</div>
	<?php
	}
}

new paycardSignature(0,1);
