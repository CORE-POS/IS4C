<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

ini_set('display_errors','1');
 
session_cache_limiter('nocache');

if (!class_exists("BasicPage")) include_once($IS4C_PATH."gui-class-lib/BasicPage.php");

if (!function_exists("lastpage")) include($IS4C_PATH."lib/listitems.php");
if (!function_exists("printheaderb")) include($IS4C_PATH."lib/drawscreen.php");
if (!function_exists("tender")) include($IS4C_PATH."lib/prehkeys.php");
if (!function_exists("drawerKick")) include_once($IS4C_PATH."lib/printLib.php");
if (!function_exists("get_preparse_chain")) include_once($IS4C_PATH."parser-class-lib/Parser.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

class pos2 extends BasicPage {

	function head_content(){
		global $IS4C_LOCAL,$IS4C_PATH;
		?>
		<script type="text/javascript" src="<?php echo $IS4C_PATH; ?>js/ajax-parser.js"></script>
		<script type="text/javascript">
		function submitWrapper(){
			var str = $('#reginput').val();
			$('#reginput').val('');
			runParser(str,'<?php echo $IS4C_PATH; ?>');
			return false;
		}
		function parseWrapper(str){
			runParser(str,'<?php echo $IS4C_PATH; ?>');
		}
		function lockScreen(){
			$.ajax({
				'url': '<?php echo $IS4C_PATH; ?>ajax-callbacks/ajax-lock.php',
				'type': 'get',
				'cache': false,
				'success': function(){
					location = '<?php echo $IS4C_PATH; ?>gui-modules/login3.php';
				}
			});
		}
		</script>
		<?php
	}

	function body_content(){
		global $IS4C_LOCAL;
		$this->input_header('onsubmit="return submitWrapper();"');
		$this->add_onload_command("setTimeout('lockScreen()', 180000);\n");
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
		$this->add_onload_command("pollScale(false);\n");
		if ($IS4C_LOCAL->get("msgrepeat") == 1)
			$this->add_onload_command("submitWrapper();");
		?>
		<div class="baseHeight">
		<?php

		$IS4C_LOCAL->set("quantity",0);
		$IS4C_LOCAL->set("multiple",0);
		$IS4C_LOCAL->set("casediscount",0);
		$IS4C_LOCAL->set("away",0);

		// set memberID if not set already
		if (!$IS4C_LOCAL->get("memberID")) {
			$IS4C_LOCAL->set("memberID","0");
		}

		// handle messages
		if ( $IS4C_LOCAL->get("msg") == "0") {
			$IS4C_LOCAL->set("msg",99);
			$IS4C_LOCAL->set("unlock",0);
		}

		if ($IS4C_LOCAL->get("plainmsg") && strlen($IS4C_LOCAL->get("plainmsg")) > 0) {
			echo printheaderb();
			echo "<div class=\"centerOffset\">";
			echo plainmsg($IS4C_LOCAL->get("plainmsg"));
			$IS4C_LOCAL->set("plainmsg",0);
			$IS4C_LOCAL->set("msg",99);
			echo "</div>";
		}
		else
			echo lastpage();

		echo "</div>"; // end base height

		echo "<div id=\"footer\">";
		if ($IS4C_LOCAL->get("away") == 1)
			echo printfooterb();
		else
			echo printfooter();
		echo "</div>";

		$IS4C_LOCAL->set("away",0);
	} // END body_content() FUNCTION
}

new pos2();

?>
