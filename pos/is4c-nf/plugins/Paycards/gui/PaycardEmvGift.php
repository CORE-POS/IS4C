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
if (!class_exists('AutoLoader')) include_once(dirname(__FILE__).'/../../../lib/AutoLoader.php');

class PaycardEmvGift extends PaycardProcessPage 
{
    private $prompt = false;
    private $runTransaction = false;
    private $amount = false;
    private $mode = false;

    function preprocess()
    {
        // check for posts before drawing anything, so we can redirect
        if (FormLib::get('mode') !== '') {
            $this->mode = FormLib::get('mode');
            if ($this->mode != PaycardLib::PAYCARD_MODE_ACTIVATE && $this->mode != PaycardLib::PAYCARD_MODE_ADDVALUE) {
                $this->conf->set('boxMsg', 'Invalid Gift Card Mode');
                $this->change_page(MiscLib::baseURL() . 'gui-modules/boxMsg2.php');

                return false;
            }
        }
        if (is_numeric(FormLib::get('amount'))) {
            $this->amount = FormLib::get('amount');
        }

        if (FormLib::get('reginput', false) !== false) {
            $input = strtoupper(trim(FormLib::get('reginput')));
            // CL always exits
            if( $input == "CL") {
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
            } elseif ($this->amount && ($input == "" || $input == 'MANUAL')) {
                $this->action = "onsubmit=\"return false;\"";    
                $this->addOnloadCommand("emvSubmit();");
                if ($input == 'MANUAL') {
                    $this->prompt = true;
                }
                $this->runTransaction = true;
            } elseif ($input != "" && is_numeric($input)) {
                // any other input is an alternate amount
                $this->amount = $input / 100.00;
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
        if (!$this->runTransaction) {
            return '';
        }
        $e2e = new MercuryDC();
        ?>
<script type="text/javascript" src="../js/emv.js"></script>
<script type="text/javascript">
function emvSubmit()
{
    $('div.baseHeight').html('Processing transaction');
    // POST XML request to driver using AJAX
    var xmlData = '<?php echo json_encode($e2e->prepareDataCapGift($this->mode, $this->amount, $this->prompt)); ?>';
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
        $title = ($this->mode == PaycardLib::PAYCARD_MODE_ACTIVATE) ? 'Activate Gift Card' : 'Add Value to Gift Card';
        $msg = '';
        if (!$this->amount) {
            $msg .= 'Enter amount<br />
                [clear] to cancel';
        } else {
            $msg .= 'Value: $' . sprintf('%.2f', $this->amount) . '
                    [enter] to continue if correct<br>Enter a different amount if incorrect<br>
                    [clear] to cancel';
            $this->addOnloadCommand("\$('#formlocal').append(\$('<input type=\"hidden\" name=\"amount\" />').val({$this->amount}));\n");
        }
        // generate message to print
        echo PaycardLib::paycardMsgBox(
                $title,
                '',
                $msg
        );
        echo '</div>';
        $this->addOnloadCommand("\$('#formlocal').append(\$('<input type=\"hidden\" name=\"mode\" />').val({$this->mode}));\n");
    }
}

AutoLoader::dispatch();

