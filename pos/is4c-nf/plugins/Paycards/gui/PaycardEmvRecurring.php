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

use COREPOS\pos\lib\FormLib;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\UdpComm;
use COREPOS\pos\plugins\Paycards\card\CardValidator;
if (!class_exists('AutoLoader')) include_once(dirname(__FILE__).'/../../../lib/AutoLoader.php');

class PaycardEmvRecurring extends PaycardProcessPage 
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
            }
            // if we're still here, we haven't accepted a valid amount yet; display prompt again
        } elseif (FormLib::get('xml-resp') !== '') {
            $xml = FormLib::get('xml-resp');
            $this->emvResponseHandler($xml);
            return false;
        } // post?

        return true;
    }

    function head_content()
    {
        $url = MiscLib::baseURL();
        echo '<script type="text/javascript" src="' . $url . '/js/singleSubmit.js"></script>';
        echo '<script type="text/javascript" src="../js/emv.js"></script>';
        if (!$this->runTransaction) {
            return '';
        }
        $e2e = new MercuryDC();
        $xmlData = $e2e->prepareDataCapAuth($this->conf->get('CacheCardType'), $this->conf->get('paycard_amount'), $this->prompt);
        $xmlData = $e2e->switchToRecurring($xmlData);
        ?>
<script type="text/javascript">
function emvSubmit() {
    $('div.baseHeight').html('Processing transaction');
    // POST XML request to driver using AJAX
    var xmlData = '<?php echo json_encode($xmlData); ?>';
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
        echo '<div class="baseHeight">';
        // generate message to print
        $amt = 20;
        $this->conf->set('paycard_amount', $amt);
        $this->conf->set('paycard_mode', PaycardLib::PAYCARD_MODE_AUTH);
        $this->conf->set('paycard_type', PaycardLib::PAYCARD_TYPE_CREDIT);
        $this->conf->set('paycard_recurring', true);
        $mode = $this->conf->get('PaycardsDatacapMode') == 1 ? 'EMV' : 'Credit';
        $this->conf->set('CacheCardType', $mode);
        if (CoreLocal::get('runningTotal') < $amt) {
            echo PaycardLib::paycardMsgBox("Invalid Amount: $amt",
                $validmsg, "[clear] to cancel");
        } else {
            $msg = "Tender ".PaycardLib::moneyFormat($amt)
                . ' as ' . $mode;
            echo PaycardLib::paycardMsgBox($msg,"Recurring Payment","[enter] to continue if incorrect<br>[clear] to cancel");
        }
        echo '</div>';
        $this->addOnloadCommand("singleSubmit.restrict('#formlocal');\n");
    }
}

AutoLoader::dispatch();

