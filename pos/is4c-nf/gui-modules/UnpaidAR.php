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

if (!class_exists("BasicPage")) include_once($IS4C_PATH."gui-class-lib/BasicPage.php");
if(!function_exists("boxMsg")) include($IS4C_PATH."lib/drawscreen.php");
if (!function_exists("deptkey")) include($IS4C_PATH."lib/prehkeys.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");


class UnpaidAR extends BasicPage {

	function preprocess(){
		global $IS4C_LOCAL,$IS4C_PATH;
		if (isset($_REQUEST['reginput'])){
			$dec = $_REQUEST['reginput'];
			$amt = $IS4C_LOCAL->get("old_ar_balance");

			$IS4C_LOCAL->set("msgrepeat",1);
			$IS4C_LOCAL->set("strRemembered","");

			if (strtoupper($dec) == "CL"){
				if ($IS4C_LOCAL->get('inactMem') == 1){
					setMember($IS4C_LOCAL->get("defaultNonMem"),1);
				}
				header("Location: {$IS4C_PATH}gui-modules/pos2.php");
				return False;
			}
			elseif ($dec == "" || strtoupper($dec) == "BQ"){
				$IS4C_LOCAL->set('warned',1);
				$IS4C_LOCAL->set('warnBoxType','warnAR');
				if (strtoupper($dec)=="BQ")
					$amt = $IS4C_LOCAL->get("balance");
				deptkey($amt*100,9900,True);
				$memtype = $IS4C_LOCAL->get("memType");
				$type = $IS4C_LOCAL->get("Type");
				if ($memtype == 1 || $memtype == 3 || $type == "INACT"){
					$IS4C_LOCAL->set("isMember",1);
					ttl();
				}
				header("Location: {$IS4C_PATH}gui-modules/pos2.php");
				return False;
			}
		}
		return True;
	}
	
	function body_content(){
		global $IS4C_LOCAL;
		$amt = $IS4C_LOCAL->get("old_ar_balance");
		$this->input_header();
		?>
		<div class="baseHeight">

		<?php
		if ($amt == $IS4C_LOCAL->get("balance")){
			boxMsg(sprintf("Old A/R Balance: $%.2f<br />
				[Enter] to pay balance now<br />
				[Clear] to leave balance",$amt));
		}
		else {
			boxMsg(sprintf("Old A/R Balance: $%.2f<br />
				Total A/R Balance: $%.2f<br />
				[Enter] to pay old balance<br />
				[Balance] to pay the entire balance<br />
				[Clear] to leave the balance",
				$amt,$IS4C_LOCAL->get("balance")));
		}
		echo "</div>";
		echo "<div id=\"footer\">";
		echo printfooter();
		echo "</div>";
		$IS4C_LOCAL->set("msgrepeat",2);
		$IS4C_LOCAL->set("beep","noBeep");
	} // END body_content() FUNCTION
}

new UnpaidAR();

?>
