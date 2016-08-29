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
use COREPOS\pos\lib\UdpComm;
use COREPOS\pos\plugins\Paycards\card\CardValidator;
if (!class_exists('AutoLoader')) include_once(dirname(__FILE__).'/../../../lib/AutoLoader.php');

class paycardboxMsgAuth extends PaycardProcessPage {

    function preprocess()
    {
        // check for posts before drawing anything, so we can redirect
        $this->addOnloadCommand("\$('#formlocal').submit(paycardboxmsgAuth.submitWrapper);\n");
        $cval = new CardValidator();
        if (FormLib::get('validate') !== '') { // ajax callback to validate inputs
            list($valid, $msg) = $cval->validateAmount($this->conf);
            echo json_encode(array('valid'=>$valid, 'msg'=>$msg));
            return false;
        } elseif (FormLib::get('reginput', false) !== false) {
            $input = strtoupper(trim(FormLib::get('reginput')));
            // CL always exits
            if ($input === "CL") {
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
            } elseif ($input == "") {
                list($valid, $msg) = $cval->validateAmount($this->conf);
                if ($valid) {
                    $this->action = "onsubmit=\"return false;\"";    
                    $this->addOnloadCommand("paycard_submitWrapper();");
                }
            } else {
                // any other input is an alternate amount
                $this->conf->set("paycard_amount","invalid");
                if (is_numeric($input)){
                    $this->setAmount($input/100);
                }
            }
            // if we're still here, we haven't accepted a valid amount yet; display prompt again
        } // post?

        return true;
    }

    private function setAmount($amt)
    {
        $this->conf->set("paycard_amount",$amt);
        if ($this->conf->get('CacheCardCashBack') > 0 && $this->conf->get('CacheCardCashBack') <= 40) {
            $this->conf->set('paycard_amount',($amt)+$this->conf->get('CacheCardCashBack'));
        }
    }

    function head_content()
    {
        echo '<script type="text/javascript" src="../js/paycardboxmsgAuth.js"></script>';
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
        } elseif ($amt > 0) {
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
        } elseif( $amt < 0) {
            echo PaycardLib::paycardMsgBox("Refund ".PaycardLib::moneyFormat($amt)."?","","[enter] to continue if correct<br>Enter a different amount if incorrect<br>[clear] to cancel");
        }
        echo '</div>';
    }
}

AutoLoader::dispatch();

