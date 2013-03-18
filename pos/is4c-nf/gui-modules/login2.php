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
AutoLoader::LoadMap();

class login2 extends BasicPage {

	var $box_color;
	var $msg;

	function preprocess(){
		$this->box_color = '#004080';
		$this->msg = _('please enter your password');

		if (isset($_REQUEST['reginput'])){
			if (Authenticate::check_password($_REQUEST['reginput'])){
				Database::testremote();
				$sd = MiscLib::scaleObject();
				if (is_object($sd))
					$sd->ReadReset();
				$my_drawer = ReceiptLib::currentDrawer();
				if ($my_drawer == 0)
					$this->change_page($this->page_url."gui-modules/drawerPage.php");
				else
					$this->change_page($this->page_url."gui-modules/pos2.php");
				return False;
			}
			else {
				$this->box_color = '#800000';
				$this->msg = _('password invalid, please re-enter');
			}
		}

		return True;
	}

	function head_content(){
		?>
		<script type="text/javascript">
		function closeFrames() {
			window.top.close();
		}
		</script>
		<?php
		$this->default_parsewrapper_js();
	}

	function body_content(){
		global $CORE_LOCAL;
		// 18Agu12 EL Add separately for readability of source.
		$this->add_onload_command("\$('#reginput').focus();");
		$this->add_onload_command("\$('#scalebox').css('display','none');");
		$this->add_onload_command("\$('body').css('background-image','none');");

		?>
		<div id="loginTopBar">
			<div class="name">I S 4 C</div>
			<div class="version">P H P &nbsp; D E V E L O P M E N T
			&nbsp; V E R S I O N &nbsp; 2 .0 .0</div>
			<div class="welcome"><?php echo _("W E L C O M E"); ?></div>
		</div>
		<div id="loginCenter">
		<div class="box" style="background:<?php echo $this->box_color; ?>;" >
				<b><?php echo _("log in"); ?></b>
				<form id="formlocal" name="form" method="post" autocomplete="off" 
					action="<?php echo $_SERVER['PHP_SELF']; ?>">
				<input type="password" name="reginput" size="20" tabindex="0" 
					onblur="$('#reginput').focus();" id="reginput" >
				<p>
				<?php echo $this->msg ?>
				</p>
				</form>
			</div>	
		</div>
		<div id="loginExit">
			<?php echo _("EXIT"); ?>
			<?php
			if ($CORE_LOCAL->get("browserOnly") == 1) {
				echo "<a href=\"\" onclick=\"window.top.close();\" ";
			}
			else {
				//echo "<a href='/bye.html' onclick=\"var cw=window.open('','Customer_Display'); cw.close()\" ";
				echo "<a href=\"/bye.html\" ";
			}
			echo "onmouseover=\"document.exit.src='{$this->page_url}graphics/switchred2.gif';\" ";
			echo "onmouseout=\"document.exit.src='{$this->page_url}graphics/switchblue2.gif';\">";
			?>
			<img id="exit" style="border:0;" alt="exit"  src="<?php echo $this->page_url; ?>graphics/switchblue2.gif" /></a>
	
		</div>
		<form name="hidden">
		<input type="hidden" name="scan" value="noScan">
		</form>
		<?php
	} // END true_body() FUNCTION

}

new login2();

?>
