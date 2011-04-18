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
if (!function_exists('scaleObject')) include_once($IS4C_PATH.'lib/lib.php');
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

class pos2 extends BasicPage {

	var $display;

	function preprocess(){
		global $IS4C_LOCAL,$IS4C_PATH;
		$this->display = "";

		$sd = scaleObject();
		//$st = sigTermObject();

		$entered = "";
		if (isset($_REQUEST["reginput"])) {
			$entered = strtoupper(trim($_REQUEST["reginput"]));
		}

		if (substr($entered, -2) == "CL") $entered = "CL";

		if ($entered == "RI") $entered = $IS4C_LOCAL->get("strEntered");

		if ($IS4C_LOCAL->get("msgrepeat") == 1 && $entered != "CL") {
			$entered = $IS4C_LOCAL->get("strRemembered");
		}
		$IS4C_LOCAL->set("strEntered",$entered);

		$json = array();
		if ($entered != ""){
			/* this breaks the model a bit, but I'm putting
			 * putting the CC parser first manually to minimize
			 * code that potentially handles the PAN */
			include_once($IS4C_PATH."cc-modules/lib/paycardEntered.php");
			$pe = new paycardEntered();
			if ($pe->check($entered)){
				$valid = $pe->parse($entered);
				$entered = "PAYCARD";
				$IS4C_LOCAL->set("strEntered","");
				$json = $valid;
			}

			$IS4C_LOCAL->set("quantity",0);
			$IS4C_LOCAL->set("multiple",0);

			/* FIRST PARSE CHAIN:
			 * Objects belong in the first parse chain if they
			 * modify the entered string, but do not process it
			 * This chain should be used for checking prefixes/suffixes
			 * to set up appropriate $IS4C_LOCAL variables.
			 */
			$parser_lib_path = $IS4C_PATH."parser-class-lib/";
			if (!is_array($IS4C_LOCAL->get("preparse_chain")))
				$IS4C_LOCAL->set("preparse_chain",get_preparse_chain());

			foreach ($IS4C_LOCAL->get("preparse_chain") as $cn){
				if (!class_exists("cn"))
					include_once($parser_lib_path."preparse/".$cn.".php");
				$p = new $cn();
				if ($p->check($entered))
					$entered = $p->parse($entered);
					if (!$entered || $entered == "")
						break;
			}

			if ($entered != "" && $entered != "PAYCARD"){
				/* 
				 * SECOND PARSE CHAIN
				 * these parser objects should process any input
				 * completely. The return value of parse() determines
				 * whether to call lastpage() [list the items on screen]
				 */
				if (!is_array($IS4C_LOCAL->get("parse_chain")))
					$IS4C_LOCAL->set("parse_chain",get_parse_chain());

				$result = False;
				foreach ($IS4C_LOCAL->get("parse_chain") as $cn){
					if (!class_exists($cn))
						include_once($parser_lib_path."parse/".$cn.".php");
					$p = new $cn();
					if ($p->check($entered)){
						$result = $p->parse($entered);
						break;
					}
				}
				if ($result && is_array($result)){
					$json = $result;
					if (isset($result['udpmsg']) && $result['udpmsg'] !== False){
						if (is_object($sd))
							$sd->WriteToScale($result['udpmsg']);
						/*
						if (is_object($st))
							$st->WriteToScale($result['udpmsg']);
						*/
					}
				}
				else {
					$arr = array(
						'main_frame'=>false,
						'target'=>'.baseHeight',
						'output'=>inputUnknown());
					$json = $arr;
					if (is_object($sd))
						$sd->WriteToScale('errorBeep');
				}
			}
		}
		$IS4C_LOCAL->set("msgrepeat",0);
		if (isset($json['main_frame']) && $json['main_frame'] != False){
			header("Location: ".$json['main_frame']);
			return False;
		}
		if (isset($json['output']) && !empty($json['output']))
			$this->display = $json['output'];

		if (isset($json['retry']) && $json['retry'] != False){
			$this->add_onload_command("setTimeout(\"inputRetry('".$json['retry']."');\", 700);\n");
		}

		if (isset($json['receipt']) && $json['receipt'] != False){
			$this->add_onload_command("receiptFetch('".$json['receipt']."');\n");
		}

		return True;
	}

	function head_content(){
		global $IS4C_LOCAL,$IS4C_PATH;
		?>
		<script type="text/javascript" src="<?php echo $IS4C_PATH; ?>js/ajax-parser.js"></script>
		<script type="text/javascript">
		function submitWrapper(){
			var str = $('#reginput').val();
			if (str.indexOf("tw") != -1 || str.indexOf("TW") != -1 || str.search(/^[0-9]+$/) == 0){
				$('#reginput').val('');
				runParser(str,'<?php echo $IS4C_PATH; ?>');
				return false;
			}
			return true;
		}
		function parseWrapper(str){
			$('#reginput').val(str);
			$('#formlocal').submit();
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
		function receiptFetch(r_type){
			$.ajax({
				url: '<?php echo $IS4C_PATH; ?>ajax-callbacks/ajax-end.php',
				type: 'get',
				data: 'receiptType='+r_type,
				cache: false,
				success: function(data){
				}
			});
		}
		function inputRetry(str){
			$('#reginput').val(str);
			submitWrapper();
		}
		</script>
		<?php
	}

	function body_content(){
		global $IS4C_LOCAL;
		$this->input_header('action="pos2.php" onsubmit="return submitWrapper();"');
		$this->add_onload_command("setTimeout('lockScreen()', 180000);\n");
		$this->add_onload_command("\$('#reginput').keydown(function(ev){
					switch(ev.which){
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
		/*
		if ($IS4C_LOCAL->get("msgrepeat") == 1)
			$this->add_onload_command("submitWrapper();");
		*/
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
		elseif (!empty($this->display))
			echo $this->display;
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
