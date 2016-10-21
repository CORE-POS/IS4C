<?php
/*******************************************************************************

    Copyright 2007 Whole Foods Co-op

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

use COREPOS\pos\lib\PrehLib;
use COREPOS\pos\lib\UdpComm;
use COREPOS\pos\parser\Parser;
use COREPOS\pos\plugins\Paycards\card\CardReader;

if (!class_exists("PaycardLib")) 
    include_once(realpath(dirname(__FILE__)."/paycardLib.php"));

class paycardEntered extends Parser 
{
    private $swipetype;
    private $manual = false;

    public function __construct()
    {
        $this->conf = new PaycardConf();
    }

    function check($str)
    {
        $this->swipetype = PaycardLib::PAYCARD_TYPE_UNKNOWN;
        if (substr($str,-1,1) == "?"){
            return true;
        } elseif (substr($str,0,8) == "02E60080" || substr($str,0,7)=="2E60080" || substr($str, 0, 5) == "23.0%" || substr($str, 0, 5) == "23.0;") {
            $this->swipetype = PaycardLib::PAYCARD_TYPE_ENCRYPTED;
            return true;
        } elseif (substr($str, 0, 2) === "02" && substr($str, -2) === "03" && strstr($str, '***')) {
            $this->swipetype = PaycardLib::PAYCARD_TYPE_ENCRYPTED;
            return true;
        } elseif ((is_numeric($str) && strlen($str) >= 16) || (is_numeric(substr($str,2)) && strlen($str) >= 18)) {
            $this->manual = true;
            return true;
        }

        return false;
    }

    function parse($str)
    {
        $ret = array();
        if( substr($str,0,2) == "PV") {
            $ret = $this->paycard_entered(PaycardLib::PAYCARD_MODE_BALANCE, substr($str,2), $this->manual, $this->swipetype);
        } elseif( substr($str,0,2) == "AV") {
            $ret = $this->paycard_entered(PaycardLib::PAYCARD_MODE_ADDVALUE, substr($str,2), $this->manual, $this->swipetype);
        } elseif( substr($str,0,2) == "AC") {
            $ret = $this->paycard_entered(PaycardLib::PAYCARD_MODE_ACTIVATE, substr($str,2), $this->manual, $this->swipetype);
        } else {
            $ret = $this->paycard_entered(PaycardLib::PAYCARD_MODE_AUTH, $str, $this->manual, $this->swipetype);
        }

        // if successful, paycard_entered() redirects to a confirmation page and exit()s; if we're still here, there was an error, so reset all data
        if ($ret['main_frame'] === false) {
            $this->conf->reset();
        }

        return $ret;
    }

    private function checkTotal($mode)
    {
        // error checks based on transaction
        if ($mode == PaycardLib::PAYCARD_MODE_AUTH) {
            if( $this->conf->get("ttlflag") != 1) { // must subtotal before running card
                throw new Exception(PaycardLib::paycardMsgBox("No Total",
                    "Transaction must be totaled before tendering or refunding","[clear] to cancel"));
            } elseif( abs($this->conf->get("amtdue")) < 0.005) { // can't tender for more than due
                throw new Exception(PaycardLib::paycardMsgBox("No Total",
                    "Nothing to tender or refund","[clear] to cancel"));
            }
        }

        return true;
    }

    private function initAmount($type)
    {
        /* assign amount due. EBT food should use eligible amount */
        $this->conf->set("paycard_amount",$this->conf->get("amtdue"));
        if ($type == 'EBTFOOD'){
            if ($this->conf->get('fntlflag') == 0){
                /* try to automatically do fs total */
                $try = PrehLib::fsEligible();
                if ($try !== True){
                    throw new Exception(PaycardLib::paycardMsgBox("Type Mismatch",
                        "Foodstamp eligible amount inapplicable","[clear] to cancel"));
                } 
            }
            /**
              Always validate amount as non-zero
            */
            if ($this->conf->get('fsEligible') <= 0.005 && $this->conf->get('fsEligible') >= -0.005) {
                throw new Exception(PaycardLib::paycardMsgBox(_('Zero Total'),
                    "Foodstamp eligible amount is zero","[clear] to cancel"));
                UdpComm::udpSend('termReset');
            } 
            $this->conf->set("paycard_amount",$this->conf->get("fsEligible"));
        }
        if (($type == 'EBTCASH' || $type == 'DEBIT') && $this->conf->get('CacheCardCashBack') > 0){
            $this->conf->set('paycard_amount',
                $this->conf->get('amtdue') + $this->conf->get('CacheCardCashBack'));
        }

        return true;
    }

    function paycard_entered($mode,$card,$manual,$type)
    {
        $ret = $this->default_json();
        // initialize
        $validate = true; // run Luhn's on PAN, check expiration date
        $reader = new CardReader();
        $this->conf->reset();
        $this->conf->set("paycard_mode",$mode);
        $this->conf->set("paycard_manual",($manual ? 1 : 0));

        try {
            $this->checkTotal($mode);

            // parse card data
            if ($this->conf->get("paycard_manual")) {
                // make sure it's numeric
                if (!ctype_digit($card) || strlen($card) < 18) { // shortest known card # is 14 digits, plus MMYY
                    throw new Exception(PaycardLib::paycardMsgBox("Manual Entry Unknown",
                        "Please enter card data like:<br>CCCCCCCCCCCCCCCCMMYY","[clear] to cancel"));
                }
                // split up input (and check for the Concord test card)
                if ($type == PaycardLib::PAYCARD_TYPE_UNKNOWN){
                    $type = $reader->type($card);
                }
                if( $type == PaycardLib::PAYCARD_TYPE_GIFT) {
                    $this->conf->set("paycard_PAN",$card); // our gift cards have no expiration date or conf code
                } else {
                    $this->conf->set("paycard_PAN",substr($card,0,-4));
                    $this->conf->set("paycard_exp",substr($card,-4,4));
                }
            } elseif ($type == PaycardLib::PAYCARD_TYPE_ENCRYPTED){
                // add leading zero back to fix hex encoding, if needed
                if (substr($card,0,7)=="2E60080")
                    $card = "0".$card;
                $this->conf->set("paycard_PAN",$card);
            } else {
                // swiped magstripe (reference to ISO format at end of this file)
                $stripe = $reader->magstripe($card);
                if (!is_array($stripe)) {
                    throw new Exception(PaycardLib::paycardErrBox($this->conf->get("paycard_manual")."Card Data Invalid","Please swipe again or type in manually","[clear] to cancel"));
                }
                $this->conf->set("paycard_PAN",$stripe["pan"]);
                $this->conf->set("paycard_exp",$stripe["exp"]);
                $this->conf->set("paycard_name",$stripe["name"]);
                $this->conf->set("paycard_tr1",$stripe["tr1"]);
                $this->conf->set("paycard_tr2",$stripe["tr2"]);
                $this->conf->set("paycard_tr3",$stripe["tr3"]);
            } // manual/swiped

            // determine card issuer and type
            $this->conf->set("paycard_type",$reader->type($this->conf->get("paycard_PAN")));
            $this->conf->set("paycard_issuer",$reader->issuer($this->conf->get("paycard_PAN")));

            /* check card type. Credit is default. */
            $type = $this->conf->get("CacheCardType");
            if ($type == '' && $this->conf->get('paycard_type') !== PaycardLib::PAYCARD_TYPE_GIFT) {
                $type = 'CREDIT';
                $this->conf->set("CacheCardType","CREDIT");
            }
            $this->initAmount($type);

            // if we knew the type coming in, make sure it agrees
            if ($type != PaycardLib::PAYCARD_TYPE_UNKNOWN && $type != $this->conf->get("paycard_type")) {
                throw new Exception(PaycardLib::paycardMsgBox("Type Mismatch",
                    "Card number does not match card type","[clear] to cancel"));
            }
        } catch (Exception $ex) {
            $ret['output'] = $ex->getMessage();
            return $ret;
        }
    

        foreach($this->conf->get("RegisteredPaycardClasses") as $rpc){
            if (!class_exists($rpc)) continue;
            $myObj = new $rpc();
            if ($myObj->handlesType($this->conf->get("paycard_type")))
                return $myObj->entered($validate,$ret);
        }

        $ret['output'] = PaycardLib::paycardErrBox("Unknown Card Type ".$this->conf->get("paycard_type"),"","[clear] to cancel");
        return $ret;
    }

    function doc(){
        return "<table cellspacing=0 cellpadding=3 border=1>
            <tr>
                <th>Input</th><th>Result</th>
            </tr>
            <tr>    
                <td>Card swipe or card number</td>
                <td>Try to charge amount to card</td>
            </tr>
            <tr>
                <td>PV<i>swipe</i> or PV<i>number</i></td>
                <td>Check balance of gift card</td>
            </tr>
            <tr>
                <td>AC<i>swipe</i> or AC<i>number</i></td>
                <td>Activate gift card</td>
            </tr>
            <tr>
                <td>AV<i>swipe</i> or AV<i>number</i></td>
                <td>Add value to gift card</td>
            </tr>
            </table>";
    }
}

