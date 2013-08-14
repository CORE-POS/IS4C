<?php

if (!class_exists("MainFramePage")) include_once($_SESSION["INCLUDE_PATH"]."/gui-class-lib/MainFramePage.php");
if (!function_exists("paycard_reset")) include_once($_SESSION["INCLUDE_PATH"]."/cc-modules/lib/paycardLib.php");
if (!function_exists("changeBothPages")) include_once($_SESSION["INCLUDE_PATH"]."/gui-base.php");
if (!isset($CORE_LOCAL)) include($_SESSION["INCLUDE_PATH"]."/lib/LocalStorage/conf.php");

class giftcardlist extends MainFramePage {

	function preprocess(){
		global $CORE_LOCAL;
		if (isset($_POST["selectlist"])){
			$prefix = $_POST["selectlist"];
			if ($prefix == "CCM"){
				$CORE_LOCAL->set("strRemembered","CCM");
				$CORE_LOCAL->set("msgrepeat",1);
				changeBothPages("/gui-modules/input.php","/gui-modules/pos2.php");
			}
			else
				changeBothPages("/gui-modules/input.php?in=$prefix","/gui-modules/pos2.php");
			return False;
		}
		return True;
	}
	
	function body_tag() {
		echo "<body onload=\"document.selectform.selectlist.selectedIndex=0; document.selectform.selectlist.focus();\">";
	}

	function head(){
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
					document.selectform.selectlist[0].value = '';
					document.selectform.selectlist.selectedIndex = 0;
				}
				document.selectform.submit();
			}
			prevPrevKey = prevKey;
			prevKey = jsKey;
		}
		</script> 
		<?php
	} // END head() FUNCTION

	function body_content() {
		global $CORE_LOCAL;
		?>
		<div class="baseHeight">
		<div class="centeredDisplay colored">
		<span class="larger">credit card transaction</span>
		<form name='selectform' method='post' action='/gui-modules/cclist.php'>
		<select name='selectlist' onblur='document.selectform.selectlist.focus()' onkeypress='processkeypress(event)' >
		<option value=''>Sale
		<option value='CCM'>Sale Via Terminal
		<option value='VD'>Void Previous Card
		<option value='FC'>Correction Charge
		</select>
		</form>
		<span class="smaller">[clear] to cancel</span>
		<p />
		</div>
		</div>	
		<?php
	} // END body_content() FUNCTION
}

new giftcardlist();
?>
