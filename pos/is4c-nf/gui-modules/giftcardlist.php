<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

class giftcardlist extends NoInputPage {

	function preprocess(){
		global $CORE_LOCAL;
		if (isset($_REQUEST["selectlist"])){
			$CORE_LOCAL->set("prefix",$_REQUEST["selectlist"]);
			$this->change_page($this->page_url."gui-modules/pos2.php");
			return False;
		}
		return True;
	}
	
	function head_content(){
		?>
        <script type="text/javascript" src="../js/selectSubmit.js"></script>
		<?php
        $this->add_onload_command("selectSubmit('#selectlist', '#selectform')\n");
		$this->add_onload_command("\$('#selectlist').focus();\n");
	} // END head() FUNCTION

	function body_content() {
		global $CORE_LOCAL;
		?>
		<div class="baseHeight">
		<div class="centeredDisplay colored">
		<span class="larger">gift card transaction</span>
		<form name="selectform" method="post" id="selectform"
			action="<?php echo $_SERVER['PHP_SELF']; ?>">
		<select id="selectlist" name="selectlist" 
			onblur="$('#selectlist').focus()">
		<option value="">Sale
		<option value="AC">Activate
		<option value="AV">Add Value
		<option value="PV">Balance
		</select>
		</form>
		<p>
		<span class="smaller">[clear] to cancel</span>
		</p>
		</div>
		</div>
		<?php
	} // END body_content() FUNCTION
}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF']))
	new giftcardlist();
?>
