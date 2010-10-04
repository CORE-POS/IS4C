<?php

if (!class_exists("NoInputPage")) include_once($_SERVER["DOCUMENT_ROOT"]."/gui-class-lib/NoInputPage.php");
if (!function_exists("getsubtotals")) include($_SERVER["DOCUMENT_ROOT"]."/lib/connect.php");
if (!function_exists("checksuspended")) include($_SERVER["DOCUMENT_ROOT"]."/lib/special.php");
if (!function_exists("tenderReport")) include($_SERVER["DOCUMENT_ROOT"]."/lib/tenderReport.php");
if (!isset($IS4C_LOCAL)) include($_SERVER["DOCUMENT_ROOT"]."/lib/LocalStorage/conf.php");

class adminlist extends NoInputPage {

	function preprocess(){
		global $IS4C_LOCAL;

		if (isset($_REQUEST['selectlist'])){
			if (empty($_REQUEST['selectlist'])){
				header("Location: /gui-modules/pos2.php");
				return False;
			}
			elseif ($_REQUEST['selectlist'] == 'SUSPEND'){
				getsubtotals();
				if ($IS4C_LOCAL->get("LastID") == 0) {
					$IS4C_LOCAL->set("boxMsg","no transaction in progress");
					header("Location: /gui-modules/boxMsg2.php");
					return False;
				}
				else {
					// ajax call to end transaction
					// and print receipt
					suspendorder();
					$this->add_onload_command("\$.ajax({
						type:'post',
						url:'/ajax-callbacks/ajax-end.php',
						cache: false,
						data: 'receiptType=suspended',
						success: function(data){
							location='/gui-modules/pos2.php';
						}
						});");
					return True;
				}
			}
			else if ($_REQUEST['selectlist'] == 'RESUME'){
				getsubtotals();
				if ($IS4C_LOCAL->get("LastID") != 0) {
					$IS4C_LOCAL->set("boxMsg","transaction in progress");
					header("Location: /gui-modules/boxMsg2.php");
				}
				elseif (checksuspended() == 0) {
					$IS4C_LOCAL->set("boxMsg","no suspended transaction");
					$IS4C_LOCAL->set("strRemembered","");
					header("Location: /gui-modules/boxMsg2.php");
				}
				else {
					header("Location: /gui-modules/suspendedlist.php");
				}
				return False;
			}
			else if ($_REQUEST['selectlist'] == 'TR'){
				tenderReport();
				header("Location: /gui-modules/pos2.php");
				return False;
			}
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
					$('#selectlist :selected').val('');
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
			<span class="larger">administrative tasks</span>
			<br />
		<form id="selectform" method="post" action="/gui-modules/adminlist.php">
		<select name="selectlist" id="selectlist" onblur="$('#selectlist').focus();">
		<option value=''>
		<option value='SUSPEND'>1. Suspend Transaction
		<option value='RESUME'>2. Resume Transaction
		<option value='TR'>3. Tender Reports
		</select>
		</form>
		<span class="smaller">[clear] to cancel</span>
		<p />
		</div>
		</div>
		<?php
		$this->add_onload_command("\$('#selectlist').focus();");
		$this->add_onload_command("\$('#selectlist').keypress(processkeypress);");
		$IS4C_LOCAL->set("scan","noScan");
	} // END body_content() FUNCTION
}

new adminlist();
?>
