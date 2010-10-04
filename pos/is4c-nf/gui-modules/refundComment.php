<?php

if (!class_exists("NoInputPage")) include_once($_SERVER["DOCUMENT_ROOT"]."/gui-class-lib/NoInputPage.php");
if (!function_exists("addcomment")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/additem.php");
if (!isset($IS4C_LOCAL)) include($_SERVER["DOCUMENT_ROOT"]."/lib/LocalStorage/conf.php");

class RefundComment extends NoInputPage {

	function preprocess(){
		global $IS4C_LOCAL;
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
			header("Location: /gui-modules/pos2.php");
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
			id="selectform" action="/gui-modules/refundComment.php">
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
