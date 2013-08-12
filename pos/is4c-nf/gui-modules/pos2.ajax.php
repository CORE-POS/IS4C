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

ini_set('display_errors','1');
 
session_cache_limiter('nocache');

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class pos2 extends BasicPage {

	function head_content(){
		global $CORE_LOCAL;
		?>
		<script type="text/javascript" src="<?php echo $this->page_url; ?>js/ajax-parser.js"></script>
		<script type="text/javascript">
		function submitWrapper(){
			var str = $('#reginput').val();
			runParser(str,'<?php echo $this->page_url; ?>');
			return false;
		}
		function parseWrapper(str){
			runParser(str,'<?php echo $this->page_url; ?>');
		}
		function lockScreen(){
			location = '<?php echo $this->page_url; ?>gui-modules/login3.php';
		}
		</script>
		<?php
	}

	function body_content(){
		global $CORE_LOCAL;
		$this->input_header('onsubmit="return submitWrapper();"');
		if ($CORE_LOCAL->get("timeout") != "")
			$this->add_onload_command("setTimeout('lockScreen()', ".$CORE_LOCAL->get("timeout").");\n");
		$this->add_onload_command("\$('#reginput').keydown(function(ev){
					switch(ev.keyCode){
					case 33:
						\$('#reginput').val('U11');
						submitWrapper();
						break;
					case 38:
						\$('#reginput').val('U');
						submitWrapper();
						break;
					case 34:
						\$('#reginput').val('D11');
						submitWrapper();
						break;
					case 40:
						\$('#reginput').val('D');
						submitWrapper();
						break;
					}
				});\n");
		if ($CORE_LOCAL->get("msgrepeat") == 1)
			$this->add_onload_command("submitWrapper();");
		?>
		<div class="baseHeight">
		<?php

		$CORE_LOCAL->set("quantity",0);
		$CORE_LOCAL->set("multiple",0);
		$CORE_LOCAL->set("casediscount",0);

		// set memberID if not set already
		if (!$CORE_LOCAL->get("memberID")) {
			$CORE_LOCAL->set("memberID","0");
		}

		if ($CORE_LOCAL->get("plainmsg") && strlen($CORE_LOCAL->get("plainmsg")) > 0) {
			echo DisplayLib::printheaderb();
			echo "<div class=\"centerOffset\">";
			echo DisplayLib::plainmsg($CORE_LOCAL->get("plainmsg"));
			$CORE_LOCAL->set("plainmsg",0);
			echo "</div>";
		}
		else
			echo DisplayLib::lastpage();

		echo "</div>"; // end base height

		echo "<div id=\"footer\">";
		echo DisplayLib::printfooter();
		echo "</div>";
	} // END body_content() FUNCTION
}

new pos2();

?>
