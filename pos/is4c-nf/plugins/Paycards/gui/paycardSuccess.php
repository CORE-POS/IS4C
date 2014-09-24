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

include_once(dirname(__FILE__).'/../../../lib/AutoLoader.php');

class paycardSuccess extends BasicPage {

    private $bmp_path;

	function preprocess(){
		global $CORE_LOCAL;

        $this->bmp_path = $this->page_url . 'scale-drivers/drivers/NewMagellan/ss-output/tmp/';

		// check for input
		if(isset($_REQUEST["reginput"])) {
			$input = strtoupper(trim($_POST["reginput"]));

            // capture file if present; otherwise re-request 
            // signature via terminal
            if (isset($_REQUEST['doCapture']) && $_REQUEST['doCapture'] == 1 && $input == '') {
                if (isset($_REQUEST['bmpfile']) && !empty($_REQUEST['bmpfile']) && file_exists($_REQUEST['bmpfile'])) {
                    $bmp = file_get_contents($_REQUEST['bmpfile']);
                    $format = 'BMP';
                    $img_content = $bmp;

                    $dbc = Database::tDataConnect();
                    $capQ = 'INSERT INTO CapturedSignature
                                (tdate, emp_no, register_no, trans_no,
                                 trans_id, filetype, filecontents)
                             VALUES
                                (?, ?, ?, ?,
                                 ?, ?, ?)';
                    $capP = $dbc->prepare_statement($capQ);
                    $args = array(
                        date('Y-m-d H:i:s'),
                        $CORE_LOCAL->get('CashierNo'),
                        $CORE_LOCAL->get('laneno'),
                        $CORE_LOCAL->get('transno'),
                        $CORE_LOCAL->get('paycard_id'),
                        $format,
                        $img_content,
                    );
                    $capR = $dbc->exec_statement($capP, $args);

                    unlink($_REQUEST['bmpfile']);
                    // continue to below. finishing transaction is the same
                    // as with paper signature slip

                } else {
                    UdpComm::udpSend('termSig');

                    return true;
                }
            }

			$mode = $CORE_LOCAL->get("paycard_mode");
			$type = $CORE_LOCAL->get("paycard_type");
			$tender_id = $CORE_LOCAL->get("paycard_id");
			if( $input == "") { // [enter] exits this screen
				// remember the mode, type and transid before we reset them
				$CORE_LOCAL->set("boxMsg","");

                /**
                  paycard_mode is sometimes cleared pre-emptively
                  perhaps by a double keypress on enter so tender out
                  if the last record in the transaction is a tender
                  record 
                */
                $peek = PrehLib::peekItem(true);
                if ($mode == PaycardLib::PAYCARD_MODE_AUTH || 
                    ($peek !== false && isset($peek['trans_type']) && $peek['trans_type'] == 'T')) {
                    $CORE_LOCAL->set("strRemembered","TO");
                    $CORE_LOCAL->set("msgrepeat",1);
                    $CORE_LOCAL->set('paycardTendered', true);
                } else {
                    TransRecord::debugLog('Not Tendering Out (mode): ' . print_r($mode, true));
                }

                // only reset terminal if the terminal was used for the transaction
                // activating a gift card should not reset terminal
                if ($CORE_LOCAL->get("paycard_type") == PaycardLib::PAYCARD_TYPE_ENCRYPTED) {
                    UdpComm::udpSend('termReset');
                    $CORE_LOCAL->set('ccTermState','swipe');
                    $CORE_LOCAL->set("CacheCardType","");
                }
				PaycardLib::paycard_reset();

				$this->change_page($this->page_url."gui-modules/pos2.php");

				return False;
			} else if ($mode == PaycardLib::PAYCARD_MODE_AUTH && $input == "VD" 
				&& ($CORE_LOCAL->get('CacheCardType') == 'CREDIT' || $CORE_LOCAL->get('CacheCardType') == '')){
				$plugin_info = new Paycards();
				$this->change_page($plugin_info->plugin_url()."/gui/paycardboxMsgVoid.php");
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
				<br>[void] " . _('to reverse the charge') . "
				</font>");
		}
		return True;
	}

	function head_content(){
		?>
		<script type="text/javascript">
        var formSubmitted = false;
		function submitWrapper(){
			var str = $('#reginput').val();
			if (str.toUpperCase() == 'RP'){
				$.ajax({url: '<?php echo $this->page_url; ?>ajax-callbacks/ajax-end.php',
					cache: false,
					type: 'post',
					data: 'receiptType='+$('#rp_type').val()+'&ref=<?php echo ReceiptLib::receiptNumber(); ?>',
					success: function(data) {
                        // If a paper signature slip is requested during
                        // electronic signature capture, abort capture
                        // Paper slip will be used instead.
                        if ($('input[name=doCapture]').length != 0) {
                            $('input[name=doCapture]').val(0);    
                            $('div.boxMsgAlert').html('Verify Signature');
                            $('#sigInstructions').html('[enter] to approve, [void] to reverse the charge<br />[reprint] to print slip');
                        }
                    }
				});
				$('#reginput').val('');
				return false;
			}
            // avoid double submit
            if (!formSubmitted) {
                formSubmitted = true;
                return true;
            } else {
                return false;
            }
		}
        function parseWrapper(str) {
            if (str.substring(0, 7) == 'TERMBMP') {
                var fn = '<?php echo $this->bmp_path; ?>' + str.substring(7);
                $('<input>').attr({
                    type: 'hidden',
                    name: 'bmpfile',
                    value: fn
                }).appendTo('#formlocal');

                var img = $('<img>').attr({
                    src: fn,
                    width: 250 
                });
                $('#imgArea').append(img);
                $('.boxMsgAlert').html('Approve Signature');
                $('#sigInstructions').html('[enter] to approve, [void] to reverse the charge');
            } 
        }
        function addToForm(n, v) {
            $('<input>').attr({
                name: n,
                value: v,
                type: 'hidden'
            }).appendTo('#formlocal');
        }
		</script>
        <style type="text/css">
        #imgArea img { border: solid 1px; black; margin:5px; }
        </style>
		<?php
	}

	function body_content(){
		global $CORE_LOCAL;
		$this->input_header("onsubmit=\"return submitWrapper();\" action=\"".$_SERVER['PHP_SELF']."\"");
		?>
		<div class="baseHeight">
		<?php
		// Signature Capture support
        // If:
        //   a) enabled
        //   b) a Credit transaction
        //   c) Over limit threshold OR a return
        $isCredit = ($CORE_LOCAL->get('CacheCardType') == 'CREDIT' || $CORE_LOCAL->get('CacheCardType') == '') ? true : false;
        // gift doesn't set CacheCardType so customer swipes and
        // cashier types don't overwrite each other's type
        if ($CORE_LOCAL->get('paycard_type') == PaycardLib::PAYCARD_TYPE_GIFT) {
            $isCredit = false;
        }
        $needSig = ($CORE_LOCAL->get('paycard_amount') > $CORE_LOCAL->get('CCSigLimit') || $CORE_LOCAL->get('paycard_amount') < 0) ? true : false;
        $isVoid = ($CORE_LOCAL->get('paycard_mode') == PaycardLib::PAYCARD_MODE_VOID) ? true : false;
		if ($CORE_LOCAL->get("PaycardsSigCapture") == 1 && $isCredit && $needSig && !$isVoid) {
            echo "<div id=\"boxMsg\" class=\"centeredDisplay\">";

            echo "<div class=\"boxMsgAlert coloredArea\">";
            echo "Waiting for signature";
            echo "</div>";

            echo "<div class=\"\">";

            echo "<div id=\"imgArea\"></div>";
            echo '<div class="textArea">';
            echo '$' . sprintf('%.2f', $CORE_LOCAL->get('paycard_amount')) . ' as CREDIT';
            echo '<br />';
            echo '<span id="sigInstructions" style="font-size:90%;">';
            echo '[enter] to get re-request signature, [void] ' . _('to reverse the charge');
            echo '<br />';
            if (isset($_REQUEST['reginput']) && ($_REQUEST['reginput'] == '' || $_REQUEST['reginput'] == 'CL')) {
                echo '<b>';
            }
            echo '[reprint] to quit &amp; use paper slip';
            if (isset($_REQUEST['reginput']) && ($_REQUEST['reginput'] == '' || $_REQUEST['reginput'] == 'CL')) {
                echo '</b>';
            }
            echo '</span>';
            echo "</div>";

            echo "</div>"; // empty class
            echo "</div>"; // #boxMsg

            UdpComm::udpSend('termSig');
            $this->add_onload_command("addToForm('doCapture', '1');\n");
		} else {
            echo DisplayLib::boxMsg($CORE_LOCAL->get("boxMsg"), "", true);
            UdpComm::udpSend('termApproved');
        }
		$CORE_LOCAL->set("CachePanEncBlock","");
		$CORE_LOCAL->set("CachePinEncBlock","");
		?>
		</div>
		<?php
		echo "<div id=\"footer\">";
		echo DisplayLib::printfooter();
		echo "</div>";

		$rp_type = '';
		if( $CORE_LOCAL->get("paycard_type") == PaycardLib::PAYCARD_TYPE_GIFT) {
			if( $CORE_LOCAL->get("paycard_mode") == PaycardLib::PAYCARD_MODE_BALANCE) {
				$rp_type = "gcBalSlip";
			} 
			else {
				$rp_type ="gcSlip";
			}
		} 
		else if( $CORE_LOCAL->get("paycard_type") == PaycardLib::PAYCARD_TYPE_CREDIT) {
			$rp_type = "ccSlip";
		}
		else if( $CORE_LOCAL->get("paycard_type") == PaycardLib::PAYCARD_TYPE_ENCRYPTED) {
			$rp_type = "ccSlip";
		}
		printf("<input type=\"hidden\" id=\"rp_type\" value=\"%s\" />",$rp_type);
	}
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__))
	new paycardSuccess();

