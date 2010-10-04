<?php
/*******************************************************************************

    Copyright 2001, 20010 Whole Foods Co-op

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
if (!function_exists("changeCurrentPage")) include_once($_SERVER["DOCUMENT_ROOT"]."/gui-base.php");
if (!function_exists("printfooterb")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/drawscreen.php");
if (!function_exists("receipt")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/clientscripts.php");
if (!isset($IS4C_LOCAL)) include($_SERVER["DOCUMENT_ROOT"]."/lib/LocalStorage/conf.php");

class paycardSignature extends MainFramePage{

	function preprocess(){
		global $IS4C_LOCAL;
		// check for input
		if( isset($_POST["input"])) {
			$input = strtoupper(trim($_POST["input"]));
			$done = False;
			if( $input == "" || $input == "CL") { // [enter] or [clear] skips this step
				$done = True;
			}
			else if (strlen($input) > 0){
				$file = $_POST['input'];
				$saveas = $_SERVER["DOCUMENT_ROOT"]."/graphics/SigImages/".basename($_POST['input']);
				copy($_POST['input'],$saveas);
				unlink($_POST['input']);
				$IS4C_LOCAL->set("CapturedSigFile",basename($saveas));
				if (file_exists($saveas))
					$done = True;
			}

			if ($done){
				if ($IS4C_LOCAL->get("SigSlipType") == "")
					$IS4C_LOCAL->set("SigSlipType","ccSlip");
				receipt($IS4C_LOCAL->get("SigSlipType"));

				changeCurrentPageJS("/gui-modules/paycardSuccess.php");
			}
		}
		return True;
	}

	function body_content(){
		global $IS4C_LOCAL;
	?>
	<form action="paycardSignature.php" method=post name="form1" tabindex="0">
	<input type=hidden name="input" size=20>
	</form>
	<div class="baseHeight">
	<?php
		$header = "Signature";
		boxMsg("Waiting for signature<br />Press [Enter] to skip this step",$header);
		$IS4C_LOCAL->set("msgrepeat",2);
		$IS4C_LOCAL->set("ccTermOut","sig");
	?>
	</div>
	<?php
	}
}

new paycardSignature(0,1);
