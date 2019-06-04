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

use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\FormLib;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\UdpComm;
use COREPOS\pos\lib\LaneLogger;
use COREPOS\pos\plugins\Paycards\card\CardValidator;
if (!class_exists('AutoLoader')) include_once(dirname(__FILE__).'/../../../lib/AutoLoader.php');

class PaycardEmvPage extends PaycardProcessPage 
{
    private $prompt = false;
    private $runTransaction = false;

    function preprocess()
    {
        // check for posts before drawing anything, so we can redirect
        if (FormLib::get('reginput', false) !== false) {
            $input = strtoupper(trim(FormLib::get('reginput')));
            // CL always exits
            if ($input == "CL") {
                $this->conf->set("msgrepeat",0);
                $this->conf->set("toggletax",0);
                $this->conf->set("togglefoodstamp",0);
                $this->conf->reset();
                $this->conf->set("CachePanEncBlock","");
                $this->conf->set("CachePinEncBlock","");
                $this->conf->set("CacheCardType","");
                $this->conf->set("CacheCardCashBack",0);
                $this->conf->set('ccTermState','swipe');
                UdpComm::udpSend("termReset");
                $this->change_page($this->page_url."gui-modules/pos2.php");
                return False;
            } elseif ($input == "" || $input == 'MANUAL' || $input === 'M') {
                $cval = new CardValidator();
                list($valid, ) = $cval->validateAmount($this->conf);
                if ($valid) {
                    $this->action = "onsubmit=\"return false;\"";    
                    $this->addOnloadCommand("emvSubmit();");
                    if ($input == 'MANUAL' || $input === 'M') {
                        $this->prompt = true;
                    }
                    $this->runTransaction = true;
                }
            } elseif ( $input != "" && substr($input,-2) != "CL") {
                // any other input is an alternate amount
                $cbLimit = $this->conf->get('PaycardsTermCashBackLimit');
                if (!is_numeric($cbLimit)) {
                    $cbLimit = 40;
                }
                $cval = new CardValidator();
                if (substr($input, -2) == 'CA' && is_numeric(substr($input, 0, strlen($input)-2)) && $cval->allowCashback($this->conf->get('CacheCardType'))) {
                    $cashback = substr($input, 0, strlen($input)-2) / 100;
                    if ($cashback > 0 && $cashback <= $cbLimit) {
                        $this->conf->set('CacheCardCashBack', $cashback);
                        $this->conf->set('paycard_amount', $this->conf->get('amtdue') + $cashback);
                    }
                } elseif (is_numeric($input)) {
                    $this->conf->set("paycard_amount",$input/100);
                    if ($this->conf->get('CacheCardCashBack') > 0 && $this->conf->get('CacheCardCashBack') <= $cbLimit) {
                        $this->conf->set('paycard_amount',($input/100)+$this->conf->get('CacheCardCashBack'));
                    }
                }
            }
            // if we're still here, we haven't accepted a valid amount yet; display prompt again
        } elseif (FormLib::get('xml-resp', false) !== false) {
            $xml = FormLib::get('xml-resp');
            $err = FormLib::get('err-info');
            if ($err) {
                $log = new LaneLogger();
                $log->error('javascript: ' . $err);
            }
            $this->emvResponseHandler($xml);
            return false;
        } elseif (FormLib::get('retry')) {
            $ref = FormLib::get('retry');
            $dbc = Database::tDataConnect();
            $prep = $dbc->prepare('SELECT entry FROM MagellanLog WHERE tdate > CURDATE() AND entryKey=?');
            $entry = $prep->getValue(array($ref));
            list($headers, $xml) = explode('<?xml', $entry, 2);
            $this->emvResponseHandler('<?xml' . $xml);
            return false;
        } elseif (FormLib::get('cancel') == 1) {
            UdpComm::udpSend("termReset");
            echo 'Canceled';
            return false;
        } // post?

        return true;
    }

    function head_content()
    {
        $url = MiscLib::baseURL();
        echo '<script type="text/javascript" src="' . $url . '/js/singleSubmit.js"></script>';
        echo '<script type="text/javascript" src="../js/emv.js?date=20180308"></script>';
        if (!$this->runTransaction) {
            return '';
        }
        $e2e = new MercuryDC($this->conf->get('PaycardsDatacapName'));
        $overrides = $this->conf->get('portOverrides');
        ?>
<script type="text/javascript">
function emvSubmit() {
    emv.showProcessing('div.baseHeight');
    // POST XML request to driver using AJAX
    var xmlData = '<?php echo json_encode($e2e->prepareDataCapAuth($this->conf->get('CacheCardType'), $this->conf->get('paycard_amount'), $this->prompt)); ?>';
    if (xmlData == '"Error"') { // failed to save request info in database
        location = '<?php echo MiscLib::baseURL(); ?>gui-modules/boxMsg2.php';
        return false;
    }
    <?php if ($this->conf->Get('training') == 1) { ?>
    emv.setURL('../ajax/AjaxPaycardTest.php');
    <?php } elseif (isset($overrides['t8999'])) { ?>
    emv.setURL('http://localhost:<?php echo $overrides['t8999']; ?>');
    <?php } ?>
    emv.submit(xmlData);
    $(document).keyup(checkForCancel);
}
var ccKey1;
var ccKey2;
function checkForCancel(ev) {
    var jsKey = ev.which ? ev.which : ev.keyCode;
    if (jsKey == 13 && (ccKey2 == 99 || ccKey2 == 67) && (ccKey1 == 108 || ccKey1 == 76)) {
        $.ajax({
            url: 'PaycardEmvPage.php',
            data: 'cancel=1'
        }).done(function(){
        });
    }
    ccKey2 = ccKey1;
    ccKey1 = jsKey;
}
</script>
        <?php
    }

    function body_content()
    {
        echo '<div class="baseHeight">';
        // generate message to print
        $amt = $this->conf->get("paycard_amount");
        $cashback = $this->conf->get('CacheCardCashBack');
        $balanceLimit = $this->conf->get('PaycardRetryBalanceLimit');
        if ($cashback > 0) $amt -= $cashback;
        $cval = new CardValidator();
        list($valid, $validmsg) = $cval->validateAmount($this->conf);
        if ($valid === false) {
            echo PaycardLib::paycardMsgBox("Invalid Amount: $amt",
                $validmsg, "[clear] to cancel");
        } elseif ($balanceLimit > 0) {
            $msg = "Tender ".PaycardLib::moneyFormat($amt);
            if ($this->conf->get("CacheCardType") != "") {
                $msg .= " as ".$this->conf->get("CacheCardType");
            } elseif ($this->conf->get('paycard_type') == PaycardLib::PAYCARD_TYPE_GIFT) {
                $msg .= ' as GIFT';
            }
            echo PaycardLib::paycardMsgBox($msg."?","",
                    "Card balance is {$balanceLimit}<br>
                    [enter] to continue if correct<br>Enter a different amount if incorrect<br>
                    [clear] to cancel");
        } elseif ( $amt > 0) {
            $msg = "Tender ".PaycardLib::moneyFormat($amt);
            if ($this->conf->get("CacheCardType") != "") {
                $msg .= " as ".$this->conf->get("CacheCardType");
            } elseif ($this->conf->get('paycard_type') == PaycardLib::PAYCARD_TYPE_GIFT) {
                $msg .= ' as GIFT';
            }
            if ($cashback > 0) {
                $msg .= ' (CB:'.PaycardLib::moneyFormat($cashback).')';
            }
            $msg .= '?';
            if ($this->conf->get('CacheCardType') == 'EBTFOOD' && abs($this->conf->get('subtotal') - $this->conf->get('fsEligible')) > 0.005) {
                $msg .= '<br />'
                    . _('Not all items eligible');
            }
            echo PaycardLib::paycardMsgBox($msg,"","[enter] to continue if correct<br>Enter a different amount if incorrect<br>[clear] to cancel");
        } elseif ( $amt < 0) {
            echo PaycardLib::paycardMsgBox("Refund ".PaycardLib::moneyFormat($amt)."?","","[enter] to continue if correct<br>Enter a different amount if incorrect<br>[clear] to cancel");
        }
        echo '</div>';
        $this->addOnloadCommand("singleSubmit.restrict('#formlocal');\n");
    }
}

AutoLoader::dispatch();

