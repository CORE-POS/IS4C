<?php

if (!class_exists("NoInputPage")) include_once($_SERVER["DOCUMENT_ROOT"]."/gui-class-lib/NoInputPage.php");
if (!function_exists("paycard_reset")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/paycardLib.php");
if (!isset($IS4C_LOCAL)) include($_SERVER["DOCUMENT_ROOT"]."/lib/LocalStorage/conf.php");

class giftcardlist extends NoInputPage {

	function preprocess(){
		global $IS4C_LOCAL;
		if (isset($_REQUEST["selectlist"])){
			$IS4C_LOCAL->set("prefix",$_REQUEST["selectlist"]);
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
			if(!e)e = window.event;
			if (e.keyCode) // IE
				jsKey = e.keyCode;
			else if(e.which) // Netscape/Firefox/Opera
				jsKey = e.which;
			if (jsKey==13) {
				if ( (prevPrevKey == 99 || prevPrevKey == 67) &&
				(prevKey == 108 || prevKey == 76) ){ //CL<enter>
					$('#selectlist').val('');
				}
				$('#selectform').submit();
			}
			prevPrevKey = prevKey;
			prevKey = jsKey;
		}
		</script> 
		<?php
		$this->add_onload_command("\$('#selectlist').keypress(processkeypress);\n");
		$this->add_onload_command("\$('#selectlist').focus();\n");
	} // END head() FUNCTION

	function body_content() {
		global $IS4C_LOCAL;
		?>
		<div class="baseHeight">
		<div class="centeredDisplay colored">
		<span class="larger">gift card transaction</span>
		<form name="selectform" method="post" id="selectform"
			action="/gui-modules/giftcardlist.php">
		<select id="selectlist" name="selectlist" 
			onblur="$('#selectlist').focus()">
		<option value="">Sale
		<option value="AC">Activate
		<option value="AV">Add Value
		<option value="PV">Balance
		</select>
		</form>
		<span class="smaller">[clear] to cancel</span>
		<p />
		</div>
		</div>
		<?php
		$IS4C_LOCAL->set("scan","noScan");
	} // END body_content() FUNCTION
}

new giftcardlist();
?>
