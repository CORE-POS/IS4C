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

if (!class_exists("NoInputPage")) include_once($IS4C_PATH."gui-class-lib/NoInputPage.php");
if (!function_exists("addcomment")) include_once($IS4C_PATH."lib/additem.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

class RefundComment extends NoInputPage {

	function preprocess(){
		global $IS4C_LOCAL,$IS4C_PATH;
		if (isset($_REQUEST["selectlist"])){
			$input = $_REQUEST["selectlist"];
			if ($input == "CL"){
				$IS4C_LOCAL->set("msgrepeat",0);
				$IS4C_LOCAL->set("strRemembered","");
				$IS4C_LOCAL->set("refundComment","");
			}
			else if ($input == "Other"){
				return True;
			}
			else {
				$input = str_replace("'","",$input);
				addcomment("RF: ".$input);
				$IS4C_LOCAL->set("msgrepeat",1);
				$IS4C_LOCAL->set("strRemembered",$IS4C_LOCAL->get("refundComment"));
			}
			header("Location: {$IS4C_PATH}gui-modules/pos2.php");
			return False;
		}
		return True;
	}
	
	function head_content(){
		?>
		<script type="text/javascript" >
		var prevKey = -1;
		var prevPrevKey = -1;
		function processkeypress(e) {
			var jsKey;
			if (e.keyCode) // IE
				jsKey = e.keyCode;
			else if(e.which) // Netscape/Firefox/Opera
				jsKey = e.which;
			if (jsKey==13) {
				if ( (prevPrevKey == 99 || prevPrevKey == 67) &&
				(prevKey == 108 || prevKey == 76) ){ //CL<enter>
					$('#selectlist :selected').val('CL');
				}
				$('#selectform').submit();
			}
			prevPrevKey = prevKey;
			prevKey = jsKey;
		}
		</script> 
		<?php
	} // END head() FUNCTION

	function body_content() {
		global $IS4C_LOCAL;
		?>
		<div class="baseHeight">
		<div class="centeredDisplay colored">
		<span class="larger">reason for refund</span>
		<form name="selectform" method="post" 
			id="selectform" action="<?php echo $_SERVER['PHP_SELF']; ?>">
		<?php
		if (isset($_POST['selectlist']) && $_POST['selectlist'] == 'Other') {
		?>
			<input type="text" id="selectlist" name="selectlist" 
				onblur="$('#selectlist').focus();" />
		<?php
		}
		else {
		?>
			<select name="selectlist" id="selectlist"
				onblur="$('#selectlist').focus();">
			<option>Overcharge</option>
			<option>Spoiled</option>
			<option>Did not Need</option>
			<option>Did not Like</option>
			<option>Other</option>
			</select>
		<?php
		}
		?>
		</form>
		<span class="smaller">[clear] to cancel</span>
		<p />
		</div>
		</div>	
		<?php
		$IS4C_LOCAL->set("scan","noScan");
		$this->add_onload_command("\$('#selectlist').focus();\n");
		if (isset($_POST['selectlist']) && $_POST['selectlist'] == 'Other') 
			$this->add_onload_command("\$('#selectlist').keypress(processkeypress);\n");
	} // END body_content() FUNCTION
}

new RefundComment();
?>
