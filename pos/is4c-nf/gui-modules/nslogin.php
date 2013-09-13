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

class nslogin extends NoInputPage {

	var $color;
	var $heading;
	var $msg;

	function preprocess(){
		$this->color ="coloredArea";
		$this->heading = _("enter manager password");
		$this->msg = _("confirm no sales");

		if (isset($_REQUEST['reginput'])){
			if (strtoupper($_REQUEST['reginput']) == "CL"){
				$this->change_page($this->page_url."gui-modules/pos2.php");
				return False;
			}
			elseif (Authenticate::ns_check_password($_REQUEST['reginput'])){
				$this->change_page($this->page_url."gui-modules/pos2.php");
				return False;
			}
			else {
				$this->color ="errorColoredArea";
				$this->heading = _("re-enter manager password");
				$this->msg = _("invalid password");
			}
		}
		UdpComm::udpSend('twoPairs');

		return True;
	}

	function head_content(){
		$this->default_parsewrapper_js('reginput','nsform');
		$this->scanner_scale_polling(True);
	}

	function body_content(){
		global $CORE_LOCAL;
		?>
		<div class="baseHeight">
		<div class="<?php echo $this->color; ?> centeredDisplay">
		<span class="larger">
		<?php echo $this->heading ?>
		</span><br />
		<form name="form" id="nsform" method="post" autocomplete="off" 
			action="<?php echo $_SERVER['PHP_SELF']; ?>">
		<input type="password" name="reginput" tabindex="0" 
			onblur="$('#reginput').focus();" id="reginput" />
		</form>
		<p>
		<?php echo $this->msg ?>
		</p>
		</div>
		</div>
		<?php
		$this->add_onload_command("\$('#reginput').focus();\n");
	} // END true_body() FUNCTION

}

new nslogin();

?>
