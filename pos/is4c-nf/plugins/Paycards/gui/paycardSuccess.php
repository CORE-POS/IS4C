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

use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\FormLib;
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\PrehLib;
use COREPOS\pos\lib\ReceiptLib;
use COREPOS\pos\lib\UdpComm;
use COREPOS\pos\lib\gui\BasicCorePage;
if (!class_exists('AutoLoader')) include_once(dirname(__FILE__).'/../../../lib/AutoLoader.php');

class paycardSuccess extends BasicCorePage 
{
    private $bmpPath;

    function preprocess()
    {
        $this->conf = new PaycardConf();
        $this->bmpPath = $this->page_url . 'scale-drivers/drivers/NewMagellan/ss-output/tmp/';
        $this->capture = new SigCapture($this->conf);

        // check for input
        if (FormLib::get('reginput', false) !== false) {
            $input = strtoupper(trim(FormLib::get('reginput')));

            // capture file if present; otherwise re-request 
            // signature via terminal
            if (FormLib::get('doCapture') == 1 && $input == '') {
                if (!file_exists(FormLib::get('bmpfile'))) {
                    UdpComm::udpSend('termSig');

                    return true;
                }
                $this->capture->save(FormLib::get('bmpfile'), Database::tDataConnect());
                // continue to below. finishing transaction is the same
                // as with paper signature slip
            }

            $mode = $this->conf->get("paycard_mode");
            if ($input == "") { // [enter] exits this screen
                // remember the mode, type and transid before we reset them
                $this->conf->set("boxMsg","");

                /**
                  paycard_mode is sometimes cleared pre-emptively
                  perhaps by a double keypress on enter so tender out
                  if the last record in the transaction is a tender
                  record 
                */
                $peek = PrehLib::peekItem(true);
                if ($mode == PaycardLib::PAYCARD_MODE_AUTH || 
                    ($peek !== false && isset($peek['trans_type']) && $peek['trans_type'] == 'T')) {
                    $this->conf->set("strRemembered","TO");
                    $this->conf->set("msgrepeat",1);
                    $this->conf->set('paycardTendered', true);
                }

                // only reset terminal if the terminal was used for the transaction
                // activating a gift card should not reset terminal
                if ($this->conf->get("paycard_type") == PaycardLib::PAYCARD_TYPE_ENCRYPTED) {
                    UdpComm::udpSend('termReset');
                    $this->conf->set('ccTermState','swipe');
                    $this->conf->set("CacheCardType","");
                }
                $this->conf->reset();

                $this->change_page($this->page_url."gui-modules/pos2.php");

                return false;
            } elseif ($mode == PaycardLib::PAYCARD_MODE_AUTH && $input == "VD" 
                && ($this->conf->get('CacheCardType') == 'CREDIT' || $this->conf->get('CacheCardType') == '')){
                $pluginInfo = new Paycards();
                $this->change_page($pluginInfo->pluginUrl()."/gui/paycardboxMsgVoid.php");
                return false;
            }
        }
        /* shouldn't happen unless session glitches
           but getting here implies the transaction
           succeeded */
        $var = $this->conf->get("boxMsg");
        if (empty($var)){
            $this->conf->set("boxMsg",
                "<b>Approved</b><font size=-1>
                <p>&nbsp;
                <p>[enter] to continue
                <br>[void] " . _('to reverse the charge') . "
                </font>");
        }

        return true;
    }

    function head_content()
    {
        ?>
        <script type="text/javascript">
        var formSubmitted = false;
        function submitWrapper(){
            var str = $('#reginput').val();
            if (str.toUpperCase() == 'RP'){
                $.ajax({url: '<?php echo $this->page_url; ?>ajax-callbacks/AjaxEnd.php',
                    cache: false,
                    type: 'post',
                    data: 'receiptType='+$('#rp_type').val()+'&ref=<?php echo ReceiptLib::receiptNumber(); ?>'
                }).done(function(data) {
                    // If a paper signature slip is requested during
                    // electronic signature capture, abort capture
                    // Paper slip will be used instead.
                    if ($('input[name=doCapture]').length != 0) {
                        $('input[name=doCapture]').val(0);    
                        $('div.boxMsgAlert').html('Verify Signature');
                        $('#sigInstructions').html('[enter] to approve, [void] to reverse the charge<br />[reprint] to print slip');
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
                var fn = '<?php echo $this->bmpPath; ?>' + str.substring(7);
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

    function body_content()
    {
        $this->input_header("onsubmit=\"return submitWrapper();\" action=\"".filter_input(INPUT_SERVER,'PHP_SELF')."\"");
        echo '<div class="baseHeight">';
        if ($this->capture->required()) {
            $reginput = FormLib::get('reginput');
            $openB = ($reginput === '' || $reginput === 'CL') ? '<b>' : '';
            $closeB = ($reginput === '' || $reginput === 'CL') ? '</b>' : '';
            $amount = sprintf('%.2f', $this->conf->get('paycard_amount'));
            echo <<<HTML
<div id="boxMsg" class="centeredDisplay">
    <div class="boxMsgAlert coloredArea">
        Waiting for signature
    </div>
    <div class="">
        <div id="imgArea"></div>
        <div class="textArea">
            \${$amount} as CREDIT
            <br />
            <span id="sigInstructions" style="font-size:90%;">
                [enter] to get re-request signature, [void] to reverse the charge
                <br />
                {$openB}[reprint] to quit &amp; use paper slip{$closeB}
            </span>
        </div>
    </div>
</div>
HTML;
            UdpComm::udpSend('termSig');
            $this->addOnloadCommand("addToForm('doCapture', '1');\n");
        } else {
            echo DisplayLib::boxMsg($this->conf->get("boxMsg"), "", true);
            if ($this->conf->get("paycard_type") == PaycardLib::PAYCARD_TYPE_ENCRYPTED) {
                UdpComm::udpSend('termApproved');
            }
        }
        $this->conf->set("CachePanEncBlock","");
        $this->conf->set("CachePinEncBlock","");
        echo '</div>';
        echo "<div id=\"footer\">";
        echo DisplayLib::printfooter();
        echo "</div>";

        $rpType = $this->rpType($this->conf->get('paycard_type'));
        printf("<input type=\"hidden\" id=\"rp_type\" value=\"%s\" />",$rpType);
    }
    
    private function rpType($type)
    {
        switch ($type) {
            case PaycardLib::PAYCARD_TYPE_GIFT:
                return $this->conf->get('paycard_mode') == PaycardLib::PAYCARD_MODE_BALANCE ? 'gcBalSlip' : 'gcSlip';
            case PaycardLib::PAYCARD_TYPE_CREDIT:
            case PaycardLib::PAYCARD_TYPE_ENCRYPTED:
            default:
                return 'ccSlip';
        }
    }
}

AutoLoader::dispatch();

