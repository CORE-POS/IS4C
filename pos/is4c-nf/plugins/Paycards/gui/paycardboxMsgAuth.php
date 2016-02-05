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

class paycardboxMsgAuth extends PaycardProcessPage {

    function preprocess()
    {
        // check for posts before drawing anything, so we can redirect
        $this->add_onload_command("\$('#formlocal').submit(paycardboxmsgAuth.submitWrapper);\n");
        if (isset($_REQUEST['validate'])) { // ajax callback to validate inputs
            list($valid, $msg) = PaycardLib::validateAmount();
            echo json_encode(array('valid'=>$valid, 'msg'=>$msg));
            return false;
        } elseif (isset($_REQUEST['reginput'])) {
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
            } elseif ($input == "") {
                list($valid, $msg) = PaycardLib::validateAmount();
                if ($valid) {
                    $this->action = "onsubmit=\"return false;\"";    
                    $this->add_onload_command("paycard_submitWrapper();");
                }
            } else {
                // any other input is an alternate amount
                CoreLocal::set("paycard_amount","invalid");
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
        CoreLocal::set("paycard_amount",$amt);
        if (CoreLocal::get('CacheCardCashBack') > 0 && CoreLocal::get('CacheCardCashBack') <= 40) {
            CoreLocal::set('paycard_amount',($amt)+CoreLocal::get('CacheCardCashBack'));
        }
    }

    function head_content()
    {
        echo '<script type="text/javascript" src="../js/paycardboxmsgAuth.js"></script>';
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
        } elseif ($balance_limit > 0) {
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
        } elseif ($amt > 0) {
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
        } elseif( $amt < 0) {
            echo PaycardLib::paycard_msgBox($type,"Refund ".PaycardLib::paycard_moneyFormat($amt)."?","","[enter] to continue if correct<br>Enter a different amount if incorrect<br>[clear] to cancel");
        } else {
            echo PaycardLib::paycard_errBox($type,"Invalid Entry",
                "Enter a different amount","[clear] to cancel");
        }
        CoreLocal::set("msgrepeat",2);
        ?>
        </div>
        <?php
    }
}

AutoLoader::dispatch();

