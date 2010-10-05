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

if (!class_exists("InvalidMainPage")) include_once($_SESSION["INCLUDE_PATH"]."/gui-class-lib/InvalidMainPage.php");
if (!function_exists("changeBothPages")) include_once($_SESSION["INCLUDE_PATH"]."/gui-base.php");
if (!isset($IS4C_LOCAL)) include($_SESSION["INCLUDE_PATH"]."/lib/LocalStorage/conf.php");

class undo extends InvalidMainPage {
	var $result;
	var $db;

	function body_tag(){
		echo "<body onload=document.forms[0].elements[0].focus()>";
	}

	function true_body($box_color,$msg){
		global $IS4C_LOCAL;
		$style = "style=\"background:$box_color;\"";
		?>
		<div class="baseHeight">
		<div class="colored centeredDisplay" <?php echo $style; ?>>
		<span class="larger">
		<?php echo $msg ?>
		</span><br />
		<form name='form' method='post' autocomplete='off' action='/undoTransaction.php'>
		<input type='text' name='reginput' tabindex='0' onBlur='document.form.reginput.focus();'>
		</form>
		<p />
		Enter transaction number<br />[clear to cancel]
		<p />
		</div>
		</div>
		<?php
		$IS4C_LOCAL->set("beep","noScan");
	}

	function valid_body(){
		$this->true_body("#004080","Undo transaction");
	}

	function invalid_body(){
		$this->true_body("#800000","Transaction not found");
	}
}

new undo();
