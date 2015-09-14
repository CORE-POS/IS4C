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
        if( isset($_REQUEST['reginput'])) {
            $input = strtoupper(trim($_REQUEST['reginput']));
            // CL always exits
            if( $input == "CL") {
                CoreLocal::set("msgrepeat",0);
                CoreLocal::set("toggletax",0);
                CoreLocal::set("togglefoodstamp",0);
                CoreLocal::set("ccTermOut","resettotal:".
                    str_replace(".","",sprintf("%.2f",CoreLocal::get("amtdue"))));
                $st = MiscLib::sigTermObject();
                if (is_object($st))
                    $st->WriteToScale(CoreLocal::get("ccTermOut"));
                PaycardLib::paycard_reset();
                CoreLocal::set("CachePanEncBlock","");
                CoreLocal::set("CachePinEncBlock","");
                CoreLocal::set("CacheCardType","");
                CoreLocal::set("CacheCardCashBack",0);
                CoreLocal::set('ccTermState','swipe');
                UdpComm::udpSend("termReset");
                $this->change_page($this->page_url."gui-modules/pos2.php");
                return False;
            }
            else if ($input == ""){
                if ($this->validate_amount()){
                    $this->action = "onsubmit=\"return false;\"";    
                    $this->add_onload_command("paycard_submitWrapper();");
                }
            }
            else if( $input != "" && substr($input,-2) != "CL") {
                // any other input is an alternate amount
                CoreLocal::set("paycard_amount","invalid");
                if( is_numeric($input)){
                    CoreLocal::set("paycard_amount",$input/100);
                    if (CoreLocal::get('CacheCardCashBack') > 0 && CoreLocal::get('CacheCardCashBack') <= 40)
                        CoreLocal::set('paycard_amount',($input/100)+CoreLocal::get('CacheCardCashBack'));
                }
            }
            // if we're still here, we haven't accepted a valid amount yet; display prompt again
        } // post?
        return True;
    }

    function validate_amount()
    {
        $amt = CoreLocal::get("paycard_amount");
        $due = CoreLocal::get("amtdue");
        $type = CoreLocal::get("CacheCardType");
        $cb = CoreLocal::get('CacheCardCashBack');
        if ($type == 'EBTFOOD') {
            $due = CoreLocal::get('fsEligible');
        }
        if( !is_numeric($amt) || abs($amt) < 0.005) {
        } else if( $amt > 0 && $due < 0) {
        } else if( $amt < 0 && $due > 0) {
        } else if ( ($amt-$due)>0.005 && $type != 'DEBIT' && $type != 'EBTCASH'){
        } else if ( ($amt-$due-0.005)>$cb && ($type == 'DEBIT' || $type == 'EBTCASH')){
        } else {
            return True;
        }
        return False;
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
        $due = CoreLocal::get("amtdue");
        $cb = CoreLocal::get('CacheCardCashBack');
        $balance_limit = CoreLocal::get('PaycardRetryBalanceLimit');
        if ($type == 'EBTFOOD') {
            $due = CoreLocal::get('fsEligible');
        }
        if ($cb > 0) $amt -= $cb;
        if( !is_numeric($amt) || abs($amt) < 0.005) {
            echo PaycardLib::paycard_msgBox($type,"Invalid Amount: $amt",
                "Enter a different amount","[clear] to cancel");
        } else if( $amt > 0 && $due < 0) {
            echo PaycardLib::paycard_msgBox($type,"Invalid Amount",
                "Enter a negative amount","[clear] to cancel");
        } else if( $amt < 0 && $due > 0) {
            echo PaycardLib::paycard_msgBox($type,"Invalid Amount",
                "Enter a positive amount","[clear] to cancel");
        } else if ( ($amt-$due)>0.005 && $type != 'DEBIT' && $type != 'EBTCASH'){
            echo PaycardLib::paycard_msgBox($type,"Invalid Amount",
                "Cannot exceed amount due","[clear] to cancel");
        } else if ( ($amt-$due-0.005)>$cb && ($type == 'DEBIT' || $type == 'EBTCASH')){
            echo PaycardLib::paycard_msgBox($type,"Invalid Amount",
                "Cannot exceed amount due plus cashback","[clear] to cancel");
        } else if ($balance_limit > 0 && ($amt-$balance_limit) > 0.005) {
            echo PaycardLib::paycard_msgBox($type,"Exceeds Balance",
                "Cannot exceed card balance","[clear] to cancel");
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
        } else if( $amt > 0) {
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
        } else if( $amt < 0) {
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

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__))
    new paycardboxMsgAuth();
