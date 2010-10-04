<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

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

if (!class_exists("BasicPage")) include_once($_SERVER["DOCUMENT_ROOT"]."/gui-class-lib/BasicPage.php");
if (!function_exists("paycard_reset")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/paycardLib.php");
if (!function_exists("ttl")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/prehkeys.php");
if (!function_exists("printfooterb")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/drawscreen.php");
if (!function_exists("tDataConnect")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/connect.php");
if (!function_exists("udpSend")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/udpSend.php");
if (!isset($IS4C_LOCAL)) include($_SERVER["DOCUMENT_ROOT"]."/lib/LocalStorage/conf.php");

class paycardSuccess extends BasicPage {

	function preprocess(){
		global $IS4C_LOCAL;
		// check for input
		if( isset($_REQUEST["reginput"])) {
			$input = strtoupper(trim($_POST["reginput"]));
			$mode = $IS4C_LOCAL->get("paycard_mode");
			$type = $IS4C_LOCAL->get("paycard_type");
			$tender_id = $IS4C_LOCAL->get("paycard_id");
			if( $input == "" || $input == "CL") { // [enter] or [clear] exits this screen
				// remember the mode, type and transid before we reset them
				$IS4C_LOCAL->set("boxMsg","");
				// store signature if present
				// if this is just a signature request, not
				// a full cc/gift transaction, associate
				// it with last trans_id
				/*
				if ($IS4C_LOCAL->get("SigCapture") != ""){
					$db = tDataConnect();
					if ($tender_id == 0) $tender_id = $IS4C_LOCAL->get("LastID");
					$sigQ = sprintf("INSERT INTO CapturedSignature VALUES
							(%s,%d,%d,%d,%d,'%s','%s')",
							$db->now(),$IS4C_LOCAL->get("CashierNo"),
							$IS4C_LOCAL->get("laneno"),
							$IS4C_LOCAL->get("transno"),
							$tender_id,
							substr($IS4C_LOCAL->get("CapturedSigFile"),-3),
							$db->escape(file_get_contents($_SERVER['DOCUMENT_ROOT'].
							"/graphics/SigImages/".$IS4C_LOCAL->get("CapturedSigFile")))
						);
					$db->query($sigQ);
					$IS4C_LOCAL->set("CapturedSigFile","");
					$IS4C_LOCAL->set("SigSlipType","");
				}
				*/

				paycard_reset();
				$IS4C_LOCAL->set("strRemembered","TO");
				$IS4C_LOCAL->set("msgrepeat",1);

				header("Location: /gui-modules/pos2.php");
				return False;
			}
			else if ($mode == PAYCARD_MODE_AUTH && $input == "VD"){
				header("Location: /gui-modules/paycardboxMsgVoid.php");
				return False;
			}
		}
		return True;
	}

	function head_content(){
		?>
		<script type="text/javascript">
		function submitWrapper(){
			var str = $('#reginput').val();
			if (str.toUpperCase() == 'RP'){
				$.ajax({url: '/ajax-callbacks/ajax-end.php',
					cache: false,
					type: 'post',
					data: 'receiptType='+$('#rp_type').val(),
					success: function(data){}
				});
				$('#reginput').val('');
				return false;
			}
			return true;
		}
		</script>
		<?php
	}

	function body_content(){
		global $IS4C_LOCAL;
		$this->input_header("onsubmit=\"return submitWrapper();\" action=\"/gui-modules/paycardSuccess.php\"");
		?>
		<div class="baseHeight">
		<?php
		/*
		$header = "Wedge - Payment Card";
		if( $IS4C_LOCAL->get("paycard_type") == PAYCARD_TYPE_CREDIT)     $header = "Wedge - Credit Card";
		else if( $IS4C_LOCAL->get("paycard_type") == PAYCARD_TYPE_GIFT)  $header = "Wedge - Gift Card";
		else $IS4C_LOCAL->set("boxMsg","Please verify cardholder signature");
		 */
		// show signature if available
		/*
		if ($IS4C_LOCAL->get("SigCapture") != ""){
			$msg = $IS4C_LOCAL->get("boxMsg");
			$img = $IS4C_LOCAL->get("CapturedSigFile");
			$msg = str_replace("Please verify cardholder signature",
				"<img src=\"/graphics/SigImages/$img\" width=200 style=\"border:solid 1px black;\" />",
				$msg);
			$IS4C_LOCAL->set("boxMsg",$msg);
		}
		 */
		echo boxMsg($IS4C_LOCAL->get("boxMsg"));
		$IS4C_LOCAL->set("msgrepeat",2);
		udpSend('goodBeep');
		?>
		</div>
		<?php
		echo "<div id=\"footer\">";
		echo printfooter();
		echo "</div>";

		$rp_type = '';
		if( $IS4C_LOCAL->get("paycard_type") == PAYCARD_TYPE_GIFT) {
			if( $IS4C_LOCAL->get("paycard_mode") == PAYCARD_MODE_BALANCE) {
				$rp_type = "gcBalSlip";
			} else {
				$rp_type ="gcSlip";
			}
		} else if( $IS4C_LOCAL->get("paycard_type") == PAYCARD_TYPE_CREDIT) {
			$rp_type = "ccSlip";
		}
		printf("<input type=\"hidden\" id=\"rp_type\" value=\"%s\" />",$rp_type);
	}
}

new paycardSuccess();
