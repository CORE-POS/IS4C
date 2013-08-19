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

ini_set('display_errors','1');

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class login3 extends BasicPage {

	var $color;
	var $img;
	var $msg;

	function preprocess(){
		$this->color = "coloredArea";
		$this->img = $this->page_url."graphics/bluekey4.gif";
		$this->msg = _("please enter password");
		if (isset($_REQUEST['reginput'])){
			if (Authenticate::check_password($_REQUEST['reginput'],4)){
				$sd = MiscLib::scaleObject();
				if (is_object($sd))
					$sd->ReadReset();
				$this->change_page($this->page_url."gui-modules/pos2.php");
				return False;
			}
			else {
				$this->color = "errorColoredArea";
				$this->img = $this->page_url."graphics/redkey4.gif";
				$this->msg = _("password invalid, please re-enter");
			}
		}
		return True;
	}

	function head_content(){
		$this->default_parsewrapper_js();
	}

	function body_content(){
		global $CORE_LOCAL;
		$this->input_header();
		echo DisplayLib::printheaderb();
		?>
		<div class="baseHeight">
			<div class="<?php echo $this->color; ?> centeredDisplay">
			<img alt="key" src='<?php echo $this->img ?>' />
			<p>
			<?php echo $this->msg ?>
			</p>
			</div>
		</div>
		<?php
		TransRecord::addactivity(3);
		$CORE_LOCAL->set("scan","noScan");
		Database::getsubtotals();
		echo "<div id=\"footer\">";
		echo DisplayLib::printfooter();
		echo "</div>";
	} // END true_body() FUNCTION

}

new login3();

?>
