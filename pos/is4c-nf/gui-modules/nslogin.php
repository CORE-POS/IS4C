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

class nslogin extends NoInputPage 
{

	private $color;
	private $heading;
	private $msg;

	function preprocess()
    {
        global $CORE_LOCAL;
		$this->color ="coloredArea";
		$this->heading = _("enter password");
		$this->msg = _("confirm no sales");

		if (isset($_REQUEST['reginput']) || isset($_REQUEST['userPassword'])) {

			$passwd = '';
			if (isset($_REQUEST['reginput']) && !empty($_REQUEST['reginput'])) {
				$passwd = $_REQUEST['reginput'];
			} elseif (isset($_REQUEST['userPassword']) && !empty($_REQUEST['userPassword'])) {
				$passwd = $_REQUEST['userPassword'];
            }

			if (strtoupper($passwd) == "CL") {
				$this->change_page($this->page_url."gui-modules/pos2.php");
				return False;
			} elseif (Authenticate::checkPassword($passwd)) {
                ReceiptLib::drawerKick();
                if ($CORE_LOCAL->get('LoudLogins') == 1) {
                    UdpComm::udpSend('goodBeep');
                }
				$this->change_page($this->page_url."gui-modules/pos2.php");
				return false;
			} else {
				$this->color ="errorColoredArea";
				$this->heading = _("re-enter password");
				$this->msg = _("invalid password");

                if ($CORE_LOCAL->get('LoudLogins') == 1) {
                    UdpComm::udpSend('twoPairs');
                }
			}
		}

		return true;
	}

	function head_content()
    {
		$this->default_parsewrapper_js('reginput','nsform');
		$this->scanner_scale_polling(true);
	}

	function body_content()
    {
		global $CORE_LOCAL;
		?>
		<div class="baseHeight">
		<div class="<?php echo $this->color; ?> centeredDisplay">
		<span class="larger">
		<?php echo $this->heading ?>
		</span><br />
		<form name="form" id="nsform" method="post" autocomplete="off" 
			action="<?php echo $_SERVER['PHP_SELF']; ?>">
		<input type="password" name="userPassword" tabindex="0" 
			onblur="$('#userPassword').focus();" id="userPassword" />
		<input type="hidden" id="reginput" name="reginput" value="" />
		</form>
		<p>
		<?php echo $this->msg ?>
		</p>
		</div>
		</div>
		<?php
		$this->add_onload_command("\$('#userPassword').focus();\n");
	} // END true_body() FUNCTION

}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
	new nslogin();
}

?>
