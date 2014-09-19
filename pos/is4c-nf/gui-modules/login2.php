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

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');
AutoLoader::LoadMap();
CoreState::loadParams();

class login2 extends BasicPage 
{

	private $box_css_class;
	private $msg;

	public $body_class = '';

	public function preprocess()
    {
		global $CORE_LOCAL;
		$this->box_css_class = 'coloredArea';
		$this->msg = _('please enter your password');

		if (isset($_REQUEST['reginput']) || isset($_REQUEST['userPassword'])) {

			$passwd = '';
			if (isset($_REQUEST['reginput']) && !empty($_REQUEST['reginput'])) {
				$passwd = $_REQUEST['reginput'];
				UdpComm::udpSend('goodBeep');
			} elseif (isset($_REQUEST['userPassword']) && !empty($_REQUEST['userPassword'])) {
				$passwd = $_REQUEST['userPassword'];
            }

			if (Authenticate::checkPassword($passwd)) {
				Database::testremote();
				$sd = MiscLib::scaleObject();
				if (is_object($sd)) {
					$sd->ReadReset();
                }

				/**
				  Find a drawer for the cashier
				*/
				$my_drawer = ReceiptLib::currentDrawer();
				if ($my_drawer == 0) {
					$available = ReceiptLib::availableDrawers();	
					if (count($available) > 0) { 
						ReceiptLib::assignDrawer($CORE_LOCAL->get('CashierNo'),$available[0]);
						$my_drawer = $available[0];
					}
				} else {
					ReceiptLib::assignDrawer($CORE_LOCAL->get('CashierNo'),$my_drawer);
                }

                TransRecord::addLogRecord(array(
                    'upc' => 'SIGNIN',
                    'description' => 'Sign In Emp#' . $CORE_LOCAL->get('CashierNo'),
                ));

				/**
				  Use Kicker object to determine whether the drawer should open
				  The first line is just a failsafe in case the setting has not
				  been configured.
				*/
				if (session_id() != '') {
					session_write_close();
                }
				$kicker_class = ($CORE_LOCAL->get("kickerModule")=="") ? 'Kicker' : $CORE_LOCAL->get('kickerModule');
				$kicker_object = new $kicker_class();
				if ($kicker_object->kickOnSignIn()) {
					ReceiptLib::drawerKick();
                }

				if ($my_drawer == 0) {
					$this->change_page($this->page_url."gui-modules/drawerPage.php");
				} else {
					$this->change_page($this->page_url."gui-modules/pos2.php");
                }

				return false;
			} else {
				$this->box_css_class = 'errorColoredArea';
				$this->msg = _('password invalid, please re-enter');
			}
		}

		return true;
	}

	public function head_content()
    {
		?>
		<script type="text/javascript">
		function closeFrames() {
			window.top.close();
		}
		</script>
		<?php
		$this->default_parsewrapper_js();
		$this->scanner_scale_polling(True);
	}

	public function body_content()
    {
		global $CORE_LOCAL;
		// 18Agu12 EL Add separately for readability of source.
		$this->add_onload_command("\$('#userPassword').focus();");
		$this->add_onload_command("\$('#scalebox').css('display','none');");

		?>
		<div id="loginTopBar">
			<div class="name">I S 4 C</div>
			<div class="version">P H P &nbsp; D E V E L O P M E N T
			&nbsp; V E R S I O N &nbsp; 2 .0 .0</div>
			<div class="welcome coloredArea"><?php echo _("W E L C O M E"); ?></div>
		</div>
		<div id="loginCenter">
		<div class="box <?php echo $this->box_css_class; ?>">
				<b><?php echo _("log in"); ?></b>
				<form id="formlocal" name="form" method="post" autocomplete="off" 
					action="<?php echo $_SERVER['PHP_SELF']; ?>">
				<input type="password" name="userPassword" size="20" tabindex="0" 
					onblur="$('#userPassword').focus();" id="userPassword" >
				<input type="hidden" name="reginput" id="reginput" value="" />
				<p>
				<?php echo $this->msg ?>
				</p>
				</form>
			</div>	
		</div>
		<div id="loginExit">
			<?php 
            echo _("EXIT");
            echo "<a href=\"\" ";
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

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
	new login2();
}

?>
