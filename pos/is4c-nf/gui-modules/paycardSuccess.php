<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

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

$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }

if (!class_exists("BasicPage")) include_once($CORE_PATH."gui-class-lib/BasicPage.php");
if (!function_exists("paycard_reset")) include_once($CORE_PATH."cc-modules/lib/paycardLib.php");
if (!function_exists("ttl")) include_once($CORE_PATH."lib/prehkeys.php");
if (!function_exists("printfooterb")) include_once($CORE_PATH."lib/drawscreen.php");
if (!function_exists("tDataConnect")) include_once($CORE_PATH."lib/connect.php");
if (!function_exists("udpSend")) include_once($CORE_PATH."lib/udpSend.php");
if (!isset($CORE_LOCAL)) include($CORE_PATH."lib/LocalStorage/conf.php");

class paycardSuccess extends BasicPage {

	function preprocess(){
		global $CORE_LOCAL,$CORE_PATH;

		/* ajax poll to check for sig img */
		if (isset($_REQUEST['poll'])){
			$attempt = $this->sig_check();
			if ($attempt === False)
				echo "notfound";
			else
				echo $attempt;
			return False;
		}

		// check for input
		if(isset($_REQUEST["reginput"])) {
			$input = strtoupper(trim($_POST["reginput"]));

			$mode = $CORE_LOCAL->get("paycard_mode");
			$type = $CORE_LOCAL->get("paycard_type");
			$tender_id = $CORE_LOCAL->get("paycard_id");
			if( $input == "" || $input == "CL") { // [enter] or [clear] exits this screen
				// remember the mode, type and transid before we reset them
				$CORE_LOCAL->set("boxMsg","");
				// store signature if present
				// if this is just a signature request, not
				// a full cc/gift transaction, associate
				// it with last trans_id
				if ($CORE_LOCAL->get("SigCapture") != "" && 
				   $CORE_LOCAL->get("paycard_amount") >= $CORE_LOCAL->get("CCSigLimit")){
					$db = tDataConnect();
					if ($tender_id == 0) $tender_id = $CORE_LOCAL->get("LastID");
					$sigQ = sprintf("INSERT INTO CapturedSignature VALUES
							(%s,%d,%d,%d,%d,'%s','%s')",
							$db->now(),$CORE_LOCAL->get("CashierNo"),
							$CORE_LOCAL->get("laneno"),
							$CORE_LOCAL->get("transno"),
							$tender_id,
							substr($CORE_LOCAL->get("CapturedSigFile"),-3),
							$db->escape(file_get_contents($CORE_LOCAL->get("CapturedSigFile")))
						);
					$db->query($sigQ);
					$CORE_LOCAL->set("CapturedSigFile","");
					$CORE_LOCAL->set("SigSlipType","");
					$this->sig_check(True);
				}

				paycard_reset();
				$CORE_LOCAL->set("strRemembered","TO");
				$CORE_LOCAL->set("msgrepeat",1);

				$this->change_page($CORE_PATH."gui-modules/pos2.php");
				return False;
			}
			else if ($mode == PAYCARD_MODE_AUTH && $input == "VD"){
				$this->change_page($CORE_PATH."gui-modules/paycardboxMsgVoid.php");
				return False;
			}
		}
		/* shouldn't happen unless session glitches
		   but getting here implies the transaction
		   succeeded */
		$var = $CORE_LOCAL->get("boxMsg");
		if (empty($var)){
			$CORE_LOCAL->set("boxMsg",
				"<b>Approved</b><font size=-1>
				<p>&nbsp;
				<p>[enter] to continue
				<br>[void] to cancel and void
				</font>");
		}
		return True;
	}

	function sig_check($clear=False){
		global $CORE_LOCAL,$CORE_PATH;

		if ($CORE_LOCAL->get("SigCapture")=="") return False;

		$deviceClass = $CORE_LOCAL->get("SigCapture");
		if (!class_exists($deviceClass)){
			include($CORE_PATH.'scale-drivers/php-wrappers/'.$deviceClass.'.php');
		}
		$device = new $deviceClass();
		if (!is_object($device)) return False;

		if ($clear)
			$device->WriteToScale("reset");
		else {
			$img = $device->poll("getsig");
			$img = str_replace("\\","/",$img);
			$CORE_LOCAL->set("CapturedSigFile",$device->getpath().$img);
		}

		return $CORE_LOCAL->get("CapturedSigFile");
	}

	function head_content(){
		global $CORE_PATH;
		?>
		<script type="text/javascript">
		function submitWrapper(){
			var str = $('#reginput').val();
			if (str.toUpperCase() == 'RP'){
				$.ajax({url: '<?php echo $CORE_PATH; ?>ajax-callbacks/ajax-end.php',
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
		function getImg(){
			$.ajax({url: 'paycardSuccess.php',
				cache: false,
				type: 'post',
				data: 'poll=1',
				success: setImg
			});
		}
		function setImg(ret_data){
			if (ret_data){
				var ext = ret_data.substr(ret_data.lastIndexOf('.') + 1);
				if (ext == "bmp"){
					$('#sigimg').attr('src',ret_data);
				}
				else{
					setTimeout('getImg()',500);
				}
			}
			else
				setTimeout('getImg()',500);
		}
		</script>
		<?php
	}

	function body_content(){
		global $CORE_LOCAL,$CORE_PATH;
		$this->input_header("onsubmit=\"return submitWrapper();\" action=\"{$CORE_PATH}gui-modules/paycardSuccess.php\"");
		?>
		<div class="baseHeight">
		<?php
		/*
		$header = "Wedge - Payment Card";
		if( $CORE_LOCAL->get("paycard_type") == PAYCARD_TYPE_CREDIT)     $header = "Wedge - Credit Card";
		else if( $CORE_LOCAL->get("paycard_type") == PAYCARD_TYPE_GIFT)  $header = "Wedge - Gift Card";
		else $CORE_LOCAL->set("boxMsg","Please verify cardholder signature");
		 */
		// show signature if available
		if ($CORE_LOCAL->get("SigCapture") != "" && 
		   $CORE_LOCAL->get("paycard_amount") >= $CORE_LOCAL->get("CCSigLimit")){
			$img = $CORE_LOCAL->get("CapturedSigFile");
			if(!is_file($img)) $img="";
			$newstr = "<img src=\"$img\" width=200 id=\"sigimg\" style=\"border:solid 1px black;\" />";
			$msg = "<b>Approved</b><font size=-1>
				<p>$newstr
				<p>[enter] to continue
				<br>[void] to cancel and void";
			$CORE_LOCAL->set("boxMsg",$msg);
			$this->add_onload_command("setTimeout('getImg()',500);");
		}
		echo boxMsg($CORE_LOCAL->get("boxMsg"),"",True);
		$CORE_LOCAL->set("msgrepeat",2);
		//udpSend('goodBeep');
		?>
		</div>
		<?php
		echo "<div id=\"footer\">";
		echo printfooter();
		echo "</div>";

		$rp_type = '';
		if( $CORE_LOCAL->get("paycard_type") == PAYCARD_TYPE_GIFT) {
			if( $CORE_LOCAL->get("paycard_mode") == PAYCARD_MODE_BALANCE) {
				$rp_type = "gcBalSlip";
			} else {
				$rp_type ="gcSlip";
			}
		} else if( $CORE_LOCAL->get("paycard_type") == PAYCARD_TYPE_CREDIT) {
			$rp_type = "ccSlip";
		}
		printf("<input type=\"hidden\" id=\"rp_type\" value=\"%s\" />",$rp_type);
	}
}

new paycardSuccess();
