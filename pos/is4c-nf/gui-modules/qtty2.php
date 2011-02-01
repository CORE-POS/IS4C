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
if (!function_exists("udpSend")) include_once($IS4C_PATH."lib/udpSend.php");
if (!function_exists("printfooter")) include_once($IS4C_PATH."lib/drawscreen.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

class qtty2 extends BasicPage {

	var $box_color;
	var $msg;

	function preprocess(){
		global $IS4C_PATH,$IS4C_LOCAL;

		$this->box_color="#004080";
		$this->msg = "quantity required";

		if (!isset($_REQUEST['reginput'])) return True;

		$qtty = strtoupper(trim($_REQUEST["reginput"]));
		if ($qtty == "CL") {
			$IS4C_LOCAL->set("qttyvalid",0);
			$IS4C_LOCAL->set("quantity",0);
			$IS4C_LOCAL->set("msgrepeat",0);
			header("Location: {$IS4C_PATH}gui-modules/pos2.php");
			return False;
		}
		elseif (is_numeric($qtty) && $qtty < 9999 && $qtty >= 0) {
			$IS4C_LOCAL->set("qttyvalid",1);
			$IS4C_LOCAL->set("strRemembered",$qtty."*".$IS4C_LOCAL->get("item"));
			$IS4C_LOCAL->set("msgrepeat",1);
			header("Location: {$IS4C_PATH}gui-modules/pos2.php");
			return False;
		}

		$this->box_color="#800000";
		$this->msg = "invalid quantity";
		return True;
	}

	function body_content(){
		global $IS4C_LOCAL;
		$this->input_header();
		echo printheaderb();
		$style = "style=\"background:{$this->box_color};\"";
		?>
		<div class="baseHeight">
		<div class="colored centeredDisplay" <?php echo $style; ?>>
		<span class="larger">
		<?php echo $this->msg ?>
		</span><br />
		<p />
		enter number or [clear] to cancel
		<p />
		</div>
		</div>

		<?php
		$IS4C_LOCAL->set("msgrepeat",2);
		$IS4C_LOCAL->set("item",$IS4C_LOCAL->get("strEntered"));
		udpSend('errorBeep');
		echo "<div id=\"footer\">";
		echo printfooter();
		echo "</div>";
	} // END true_body() FUNCTION
}

new qtty2();

?>
