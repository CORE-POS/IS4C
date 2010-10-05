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

if (!class_exists("NoInputPage")) include_once($IS4C_PATH."gui-class-lib/NoInputPage.php");
if (!function_exists("getsubtotals")) include($IS4C_PATH."lib/connect.php");
if (!function_exists("checksuspended")) include($IS4C_PATH."lib/special.php");
if (!function_exists("tenderReport")) include($IS4C_PATH."lib/tenderReport.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

class adminlist extends NoInputPage {

	function preprocess(){
		global $IS4C_LOCAL,$IS4C_PATH;

		if (isset($_REQUEST['selectlist'])){
			if (empty($_REQUEST['selectlist'])){
				header("Location: {$IS4C_PATH}gui-modules/pos2.php");
				return False;
			}
			elseif ($_REQUEST['selectlist'] == 'SUSPEND'){
				getsubtotals();
				if ($IS4C_LOCAL->get("LastID") == 0) {
					$IS4C_LOCAL->set("boxMsg","no transaction in progress");
					header("Location: {$IS4C_PATH}gui-modules/boxMsg2.php");
					return False;
				}
				else {
					// ajax call to end transaction
					// and print receipt
					suspendorder();
					$this->add_onload_command("\$.ajax({
						type:'post',
						url:'{$IS4C_PATH}ajax-callbacks/ajax-end.php',
						cache: false,
						data: 'receiptType=suspended',
						success: function(data){
							location='{$IS4C_PATH}gui-modules/pos2.php';
						}
						});");
					return True;
				}
			}
			else if ($_REQUEST['selectlist'] == 'RESUME'){
				getsubtotals();
				if ($IS4C_LOCAL->get("LastID") != 0) {
					$IS4C_LOCAL->set("boxMsg","transaction in progress");
					header("Location: {$IS4C_PATH}gui-modules/boxMsg2.php");
				}
				elseif (checksuspended() == 0) {
					$IS4C_LOCAL->set("boxMsg","no suspended transaction");
					$IS4C_LOCAL->set("strRemembered","");
					header("Location: {$IS4C_PATH}gui-modules/boxMsg2.php");
				}
				else {
					header("Location: {$IS4C_PATH}gui-modules/suspendedlist.php");
				}
				return False;
			}
			else if ($_REQUEST['selectlist'] == 'TR'){
				tenderReport();
				header("Location: {$IS4C_PATH}gui-modules/pos2.php");
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
		<form id="selectform" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
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
