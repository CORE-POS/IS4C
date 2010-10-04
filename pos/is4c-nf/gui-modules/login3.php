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

if (!class_exists("BasicPage")) include_once($_SERVER["DOCUMENT_ROOT"]."/gui-class-lib/BasicPage.php");
if (!function_exists("authenticate")) include($_SERVER["DOCUMENT_ROOT"]."/lib/authenticate.php");
if (!isset($IS4C_LOCAL)) include($_SERVER["DOCUMENT_ROOT"]."/lib/LocalStorage/conf.php");

class login3 extends BasicPage {

	var $color;
	var $img;
	var $msg;

	function preprocess(){
		$this->color = "#004080";
		$this->img = "/graphics/bluekey4.gif";
		$this->msg = "please enter password";
		if (isset($_REQUEST['reginput'])){
			if (authenticate($_REQUEST['reginput'],4)){
				header("Location: /gui-modules/pos2.php");
				return False;
			}
			else {
				$this->color = "#800000";
				$this->img = "/graphics/redkey4.gif";
				$this->msg = "password invalid, please re-enter";
			}
		}
		return True;
	}

	function body_content(){
		global $IS4C_LOCAL;
		$style = "style=\"background: {$this->color};\"";
		$this->input_header();
		echo printheaderb();
		?>
		<div class="baseHeight">
			<div class="colored centeredDisplay" <?php echo $style;?>>
			<img src='<?php echo $this->img ?>' />
			<p />
			<?php echo $this->msg ?>
			<p />
			</div>
		</div>
		<?php
		addactivity(3);
		$IS4C_LOCAL->set("scan","noScan");
		getsubtotals();
		echo "<div id=\"footer\">";
		echo printfooter();
		echo "</div>";
	} // END true_body() FUNCTION

}

new login3();

?>
