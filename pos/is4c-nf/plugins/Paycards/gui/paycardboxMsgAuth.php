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
        $this->add_onload_command("\$('#formlocal').submit(paycardAuth.submitWrapper);\n");
        if (isset($_REQUEST['validate'])) { // ajax callback to validate inputs
            list($valid, $msg) = $this->validateAmount();
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
                list($valid, $msg) = $this->validateAmount();
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

    function validateAmount()
    {
        $amt = CoreLocal::get('paycard_amount');
        $due = CoreLocal::get("amtdue");
        $type = CoreLocal::get("CacheCardType");
        $cb = CoreLocal::get('CacheCardCashBack');
        $balance_limit = CoreLocal::get('PaycardRetryBalanceLimit');
        if ($type == 'EBTFOOD') {
            $due = CoreLocal::get('fsEligible');
        }
        if ($cb > 0) $amt -= $cb;
        if (!is_numeric($amt) || abs($amt) < 0.005) {
            return array(false, 'Enter a different amount');
        } elseif ($amt > 0 && $due < 0) {
            return array(false, 'Enter a negative amount');
        } elseif ($amt < 0 && $due > 0) {
            return array(false, 'Enter a positive amount');
        } elseif (($amt-$due)>0.005 && $type != 'DEBIT' && $type != 'EBTCASH') {
            return array(false, 'Cannot exceed amount due');
        } elseif (($amt-$due-0.005)>$cb && ($type == 'DEBIT' || $type == 'EBTCASH')) {
            return array(false, 'Cannot exceed amount due plus cashback');
        } elseif ($balance_limit > 0 && ($amt-$balance_limit) > 0.005) {
            return array(false, 'Cannot exceed card balance');
        } else {
            return array(true, 'valid');
        }

        return array(false, 'invalid');
    }

    function head_content()
    {
        ?>
<script type="text/javascript">
var paycardAuth = (function($) {
    var mod = {};
    var called = false;

    var reloadOnError = function() {
        window.location = 'paycardboxMsgAuth.php';
    };

    /**
      Trying to cope with rare errors where paycard_submitWrapper's
      AJAX call ends in an error with 0 status, 0 readyState.
      Originally I tried to use singleSubmit to keep the form from
      submitting more than once but having the process be:
        submit => page reload => AJAX call fires
      seemed to still have the occasional bug. With such generic
      error information it's tough to say for sure what the problem
      is. The guess is something triggers page navigation while
      the AJAX call is processing but that really is just a guess.
    */
    mod.submitWrapper = function(e) {
        if ($('#reginput').val() === '' || called) {
            e.preventDefault();
            if (!called) {
                console.log('ajax');
                var validate = $.ajax({
                    data: 'validate=1',
                    dataType: 'json',
                }).done(function (resp) {
                    if (resp.valid) {
                        console.log('send a request!');
                        paycard_submitWrapper();
                    } else {
                        console.log('no send');
                        reloadOnError();
                    }
                }).fail(function(xhr,stat,msg) {
                    reloadOnError();
                });
            }
            called = true;

            return false;
        }

        return true;
    };

    return mod;
}(jQuery));
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
        list($valid, $validmsg) = $this->validateAmount();
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

