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

class qtty2 extends BasicPage {

	var $box_color;
	var $msg;

	function preprocess(){
		global $CORE_LOCAL;

		$this->box_color="coloredArea";
		$this->msg = _("quantity required");

		if (!isset($_REQUEST['reginput'])) return True;

		$qtty = strtoupper(trim($_REQUEST["reginput"]));
		if ($qtty == "CL") {
			$CORE_LOCAL->set("qttyvalid",0);
			$CORE_LOCAL->set("quantity",0);
			$CORE_LOCAL->set("msgrepeat",0);
			$this->change_page($this->page_url."gui-modules/pos2.php");
			return False;
		}
		elseif (is_numeric($qtty) && $qtty < 9999 && $qtty >= 0) {
			$input_string = $CORE_LOCAL->get("item");
			$plu = "";
			while(!empty($input_string) && is_numeric($input_string[strlen($input_string)-1])){
				$plu = $input_string[strlen($input_string)-1] . $plu;
				$input_string = substr($input_string,0,strlen($input_string)-1);
			}
			$CORE_LOCAL->set("qttyvalid",1);
			$CORE_LOCAL->set("strRemembered",$input_string.$qtty."*".$plu);
			$CORE_LOCAL->set("msgrepeat",1);
			$this->change_page($this->page_url."gui-modules/pos2.php");
			return False;
		}

		$this->box_color="errorColoredArea";
		$this->msg = _("invalid quantity");
		return True;
	}

    function head_content()
    {
        $this->noscan_parsewrapper_js();
    }

	function body_content(){
		global $CORE_LOCAL;
		$this->input_header();
		echo DisplayLib::printheaderb();
		?>
		<div class="baseHeight">
		<div class="<?php echo $this->box_color; ?> centeredDisplay">
		<span class="larger">
		<?php echo $this->msg ?>
		</span><br />
		<p>
		<?php echo _("enter number or clear to cancel"); ?>
		</p> 
		</div>
		</div>

		<?php
		$CORE_LOCAL->set("msgrepeat",2);
		$CORE_LOCAL->set("item",$CORE_LOCAL->get("strEntered"));
		UdpComm::udpSend('errorBeep');
		echo "<div id=\"footer\">";
		echo DisplayLib::printfooter();
		echo "</div>";
	} // END true_body() FUNCTION
}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF']))
	new qtty2();

?>
