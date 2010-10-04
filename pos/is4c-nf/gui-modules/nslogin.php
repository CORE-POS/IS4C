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

if (!class_exists("NoInputPage")) include_once($_SERVER["DOCUMENT_ROOT"]."/gui-class-lib/NoInputPage.php");
if (!function_exists("nsauthenticate")) include($_SERVER['DOCUMENT_ROOT']."/lib/authenticate.php");
if (!isset($IS4C_LOCAL)) include($_SERVER["DOCUMENT_ROOT"]."/lib/LocalStorage/conf.php");

class nslogin extends NoInputPage {

	var $color;
	var $heading;
	var $msg;

	function preprocess(){
		$this->color ="#004080";
		$this->heading = "enter manager password";
		$this->msg = "confirm no sales";

		if (isset($_REQUEST['reginput'])){
			if (strtoupper($_REQUEST['reginput']) == "CL"){
				header("Location: /gui-modules/pos2.php");
				return False;
			}
			elseif (nsauthenticate($_REQUEST['reginput'])){
				header("Location: /gui-modules/pos2.php");
				return False;
			}
			else {
				$this->color ="#800000";
				$this->heading = "re-enter manager password";
				$this->msg = "invalid password";
			}
		}

		return True;
	}

	function body_content(){
		global $IS4C_LOCAL;
		$style = "style=\"background:{$this->color};\"";
		?>
		<div class="baseHeight">
		<div class="colored centeredDisplay" <?php echo $style; ?>>
		<span class="larger">
		<?php echo $this->heading ?>
		</span><br />
		<form name="form" id="nsform" method="post" autocomplete="off" 
			action="<?php echo $_SERVER['PHP_SELF']; ?>">
		<input type="password" name="reginput" tabindex="0" 
			onblur="$('#reginput').focus();" id="reginput" />
		</form>
		<p />
		<?php echo $this->msg ?>
		<p />
		</div>
		</div>
		<?php
		$IS4C_LOCAL->set("scan","noScan");
		$this->add_onload_command("\$('#reginput').focus();\n");
	} // END true_body() FUNCTION

}

new nslogin();

?>
