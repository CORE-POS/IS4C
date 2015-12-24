<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op.

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

class PaycardModule
{
    public static function ccEntered($pan, $validate, $json)
    {
        try {
            $enabled = PaycardDialogs::enabledCheck();
            // error checks based on processing mode
            switch (CoreLocal::get("paycard_mode")) {
                case PaycardLib::PAYCARD_MODE_VOID:
                    // use the card number to find the trans_id
                    $pan4 = substr($pan,-4);
                    $trans = array(CoreLocal::get('CashierNo'), CoreLocal::get('laneno'), CoreLocal::get('transno'));
                    $result = PaycardDialogs::voidableCheck($pan4, $trans);
                    return self::ccVoid($result,$trans[1],$trans[2],$json);

                case PaycardLib::PAYCARD_MODE_AUTH:
                    if ($validate) {
                        $valid = PaycardDialogs::validateCard($pan);
                    }
                    return PaycardLib::setupAuthJson($json);
            } // switch mode
        } catch (Exception $ex) {
            $json['output'] = $ex->getMessage();
            return $json;
        }

        // if we're still here, it's an error
        PaycardLib::paycard_reset();
        $json['output'] = PaycardDialogs::invalidMode();
        return $json;
    }

    public static function ccVoid($transID,$laneNo=-1,$transNo=-1,$json=array()) 
    {
        // initialize
        $cashier = CoreLocal::get("CashierNo");
        $lane = CoreLocal::get("laneno");
        $trans = CoreLocal::get("transno");
        if ($laneNo != -1) $lane = $laneNo;
        if ($transNo != -1) $trans = $transNo;
        try {
            $enabled = PaycardDialogs::enabledCheck();
            $request = PaycardDialogs::getRequest(array($cashier, $lane, $trans), $transID);
            $response = PaycardDialogs::getResponse(array($cashier, $lane, $trans), $transID);
            // look up any previous successful voids
            $eligible = PaycardDialogs::notVoided(array($cashier, $lane, $trans), $transID);
            $lineitem = PaycardDialogs::getTenderLine(array($cashier, $lane, $trans), $transID);
            $valid = PaycardDialogs::validateVoid($request, $response, $lineitem, $transID);
        } catch (Exception $ex) {
            $json['output'] = $ex->getMessage();
            return $json;
        }
    
        // save the details
        CoreLocal::set("paycard_amount",self::isReturn($request['mode']) ? -1*$request['amount'] :  $request['amount']);
        CoreLocal::set("paycard_id",$transID);
        CoreLocal::set("paycard_trans",$cashier."-".$lane."-".$trans);
        CoreLocal::set("paycard_type",PaycardLib::PAYCARD_TYPE_CREDIT);
        CoreLocal::set("paycard_mode",PaycardLib::PAYCARD_MODE_VOID);
        CoreLocal::set("paycard_name",$request['name']);
    
        // display FEC code box
        CoreLocal::set("inputMasked",1);
        $plugin_info = new Paycards();
        $json['main_frame'] = $plugin_info->pluginUrl().'/gui/paycardboxMsgVoid.php';

        return $json;
    }

    public static function isReturn($mode)
    {
        switch (strtolower($mode)) {
            case 'refund':
            case 'retail_alone_credit':
            case 'return':
                return true;
            default:
                return false;
        }
    }

    public static function commError($authResult)
    {
        if ($authResult['curlErr'] != CURLE_OK || $authResult['curlHTTP'] != 200){
            if ($authResult['curlHTTP'] == '0'){
                CoreLocal::set("boxMsg","No response from processor<br />
                            The transaction did not go through");
                return PaycardLib::PAYCARD_ERR_PROC;
            }    
            return true;
        }

        return false;
    }
}

