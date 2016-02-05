<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op
    Modifications copyright 2010 Whole Foods Co-op

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

class PaycardEmvPage extends PaycardProcessPage 
{
    private $prompt = false;
    private $run_transaction = false;

    function preprocess()
    {
        // check for posts before drawing anything, so we can redirect
        if (isset($_REQUEST['reginput'])) {
            $input = strtoupper(trim($_REQUEST['reginput']));
            // CL always exits
            if( $input == "CL") {
                CoreLocal::set("msgrepeat",0);
                CoreLocal::set("toggletax",0);
                CoreLocal::set("togglefoodstamp",0);
                PaycardLib::paycard_reset();
                CoreLocal::set("CachePanEncBlock","");
                CoreLocal::set("CachePinEncBlock","");
                CoreLocal::set("CacheCardType","");
                CoreLocal::set("CacheCardCashBack",0);
                CoreLocal::set('ccTermState','swipe');
                UdpComm::udpSend("termReset");
                $this->change_page($this->page_url."gui-modules/pos2.php");
                return False;
            } elseif ($input == "" || $input == 'MANUAL') {
                list($valid, $msg) = PaycardLib::validateAmount();
                if ($valid) {
                    $this->action = "onsubmit=\"return false;\"";    
                    $this->add_onload_command("emvSubmit();");
                    if ($input == 'MANUAL') {
                        $this->prompt = true;
                    }
                    $this->run_transaction = true;
                }
            } elseif ( $input != "" && substr($input,-2) != "CL") {
                // any other input is an alternate amount
                CoreLocal::set("paycard_amount","invalid");
                if( is_numeric($input)){
                    CoreLocal::set("paycard_amount",$input/100);
                    if (CoreLocal::get('CacheCardCashBack') > 0 && CoreLocal::get('CacheCardCashBack') <= 40)
                        CoreLocal::set('paycard_amount',($input/100)+CoreLocal::get('CacheCardCashBack'));
                }
            }
            // if we're still here, we haven't accepted a valid amount yet; display prompt again
        } elseif (isset($_REQUEST['xml-resp'])) {
            $xml = $_REQUEST['xml-resp'];
            $this->emvResponseHandler($xml);
            if (isset($_REQUEST['err-info'])) {
                $fp = fopen(dirname(__FILE__) . '/../resp.xml', 'a');
                fwrite($fp, $_REQUEST['err-info'] . "\n\n");
                fclose($fp);
            }
            return false;
        } // post?

        return true;
    }

    function head_content()
    {
        $url = MiscLib::baseURL();
        echo '<script type="text/javascript" src="' . $url . '/js/singleSubmit.js"></script>';
        echo '<script type="text/javascript" src="../js/emv.js"></script>';
        if (!$this->run_transaction) {
            return '';
        }
        $e2e = new MercuryE2E();
        ?>
<script type="text/javascript">
function emvSubmit() {
    $('div.baseHeight').html('Processing transaction');
    // POST XML request to driver using AJAX
    var xmlData = '<?php echo json_encode($e2e->prepareDataCapAuth(CoreLocal::get('CacheCardType'), CoreLocal::get('paycard_amount'), $this->prompt)); ?>';
    if (xmlData == '"Error"') { // failed to save request info in database
        location = '<?php echo MiscLib::baseURL(); ?>gui-modules/boxMsg2.php';
        return false;
    }
    emv.submit(xmlData);
}
</script>
        <?php
    }

    function body_content()
    {
        ?>
        <div class="baseHeight">
        <?php
        // generate message to print
        $type = CoreLocal::get("paycard_type");
        $mode = CoreLocal::get("paycard_mode");
        $amt = CoreLocal::get("paycard_amount");
        $cb = CoreLocal::get('CacheCardCashBack');
        $balance_limit = CoreLocal::get('PaycardRetryBalanceLimit');
        if ($cb > 0) $amt -= $cb;
        list($valid, $validmsg) = PaycardLib::validateAmount();
        if ($valid === false) {
            echo PaycardLib::paycard_msgBox($type, "Invalid Amount: $amt",
                $validmsg, "[clear] to cancel");
        } else if ($balance_limit > 0) {
            $msg = "Tender ".PaycardLib::paycard_moneyFormat($amt);
            if (CoreLocal::get("CacheCardType") != "") {
                $msg .= " as ".CoreLocal::get("CacheCardType");
            } elseif (CoreLocal::get('paycard_type') == PaycardLib::PAYCARD_TYPE_GIFT) {
                $msg .= ' as GIFT';
            }
            echo PaycardLib::paycard_msgBox($type,$msg."?","",
                    "Card balance is {$balance_limit}<br>
                    [enter] to continue if correct<br>Enter a different amount if incorrect<br>
                    [clear] to cancel");
        } elseif ( $amt > 0) {
            $msg = "Tender ".PaycardLib::paycard_moneyFormat($amt);
            if (CoreLocal::get("CacheCardType") != "") {
                $msg .= " as ".CoreLocal::get("CacheCardType");
            } elseif (CoreLocal::get('paycard_type') == PaycardLib::PAYCARD_TYPE_GIFT) {
                $msg .= ' as GIFT';
            }
            if ($cb > 0) {
                $msg .= ' (CB:'.PaycardLib::paycard_moneyFormat($cb).')';
            }
            $msg .= '?';
            if (CoreLocal::get('CacheCardType') == 'EBTFOOD' && abs(CoreLocal::get('subtotal') - CoreLocal::get('fsEligible')) > 0.005) {
                $msg .= '<br />'
                    . _('Not all items eligible');
            }
            echo PaycardLib::paycard_msgBox($type,$msg,"","[enter] to continue if correct<br>Enter a different amount if incorrect<br>[clear] to cancel");
        } elseif ( $amt < 0) {
            echo PaycardLib::paycard_msgBox($type,"Refund ".PaycardLib::paycard_moneyFormat($amt)."?","","[enter] to continue if correct<br>Enter a different amount if incorrect<br>[clear] to cancel");
        } else {
            echo PaycardLib::paycard_errBox($type,"Invalid Entry",
                "Enter a different amount","[clear] to cancel");
        }
        CoreLocal::set("msgrepeat",2);
        ?>
        </div>
        <?php
        $this->add_onload_command("singleSubmit.restrict('#formlocal');\n");
    }
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__))
    new PaycardEmvPage();
