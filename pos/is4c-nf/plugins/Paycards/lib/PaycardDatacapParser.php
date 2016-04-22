<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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

/**
  Using Datacap ActiveX requires the cashier
  to select a card type. Commands are DATACAP
  followed by one of these:
  * EMV
  * CC (Credit)
  * DC (Debit)
  * EF (EBT Food)
  * EC (EBT Cash)
  This will set up the default paymount amount
  correctly based on the card type and then
  go to the amount confirmation screen
*/
class PaycardDatacapParser extends Parser 
{
    private $valid = array(
        'DATACAP',
        'DATACAPEMV',
        'DATACAPCC',
        'DATACAPCCAUTO',
        'DATACAPDC',
        'DATACAPEF',
        'DATACAPEC',
        'DATACAPGD',
        'PVDATACAPGD',
        'PVDATACAPEF',
        'PVDATACAPEC',
        'ACDATACAPGD',
        'AVDATACAPGD',
    );

    public function check($str)
    {
        if (in_array($str, $this->valid)) {
            return true;
        } else {
            return false;
        }
    }

    public function parse($str)
    {
        $ret = $this->default_json();
        if (CoreLocal::get("ttlflag") != 1) { // must subtotal before running card
            $ret['output'] = PaycardLib::paycard_msgBox('',"No Total",
                "Transaction must be totaled before tendering or refunding","[clear] to cancel");
            return $ret;
        }
        $plugin_info = new Paycards();
        $ret['main_frame'] = $plugin_info->pluginUrl().'/gui/PaycardEmvPage.php';
        Database::getsubtotals();
        CoreLocal::set('paycard_amount', CoreLocal::get('amtdue'));
        CoreLocal::set('paycard_mode', PaycardLib::PAYCARD_MODE_AUTH);
        CoreLocal::set('paycard_type', PaycardLib::PAYCARD_TYPE_CREDIT);
        switch ($str) {
            case 'DATACAP':
                $ret['main_frame'] = $plugin_info->pluginUrl().'/gui/PaycardEmvMenu.php';
                break; 
            case 'DATACAPEMV': 
                CoreLocal::set('CacheCardType', 'EMV');
                break;
            case 'DATACAPCC':
                CoreLocal::set('CacheCardType', 'CREDIT');
                break;
            case 'DATACAPCCAUTO':
                CoreLocal::set('CacheCardType', 'CREDIT');
                $ret['main_frame'] .= '?reginput=';
                break;
            case 'DATACAPDC':
                if (CoreLocal::get('CacheCardCashBack')) {
                    CoreLocal::set('paycard_amount', CoreLocal::get('amtdue') + CoreLocal::get('CacheCardCashBack'));
                }
                CoreLocal::set('CacheCardType', 'DEBIT');
                break;
            case 'DATACAPEF':
                if (CoreLocal::get('fntlflag') == 0) {
                    /* try to automatically do fs total */
                    $try = PrehLib::fsEligible();
                    if ($try !== true) {
                        $ret['output'] = PaycardLib::paycard_msgBox($type,"Type Mismatch",
                            "Foodstamp eligible amount inapplicable","[clear] to cancel");
                        $ret['main_frame'] = false;
                        return $ret;
                    } 
                }
                CoreLocal::set('paycard_amount', CoreLocal::get('fsEligible'));
                CoreLocal::set('CacheCardType', 'EBTFOOD');
                break;
            case 'DATACAPEC':
                if (CoreLocal::get('CacheCardCashBack')) {
                    CoreLocal::set('paycard_amount', CoreLocal::get('amtdue') + CoreLocal::get('CacheCardCashBack'));
                }
                CoreLocal::set('CacheCardType', 'EBTCASH');
                break;
            case 'DATACAPGD':
                CoreLocal::set('CacheCardType', 'GIFT');
                CoreLocal::set('paycard_type', PaycardLib::PAYCARD_TYPE_GIFT);
                break;
            case 'PVDATACAPGD':
                CoreLocal::set('CacheCardType', 'GIFT');
                CoreLocal::set('paycard_mode', PaycardLib::PAYCARD_MODE_BALANCE);
                CoreLocal::set('paycard_type', PaycardLib::PAYCARD_TYPE_GIFT);
                $ret['main_frame'] = $plugin_info->pluginUrl().'/gui/PaycardEmvBalance.php';
                break;
            case 'PVDATACAPEF':
                CoreLocal::set('CacheCardType', 'EBTFOOD');
                CoreLocal::set('paycard_mode', PaycardLib::PAYCARD_MODE_BALANCE);
                $ret['main_frame'] = $plugin_info->pluginUrl().'/gui/PaycardEmvBalance.php';
                break;
            case 'PVDATACAPEC':
                CoreLocal::set('CacheCardType', 'EBTCASH');
                CoreLocal::set('paycard_mode', PaycardLib::PAYCARD_MODE_BALANCE);
                $ret['main_frame'] = $plugin_info->pluginUrl().'/gui/PaycardEmvBalance.php';
                break;
            case 'ACDATACAPGD':
                CoreLocal::set('CacheCardType', 'GIFT');
                CoreLocal::set('paycard_mode', PaycardLib::PAYCARD_MODE_ACTIVATE);
                CoreLocal::set('paycard_type', PaycardLib::PAYCARD_TYPE_GIFT);
                $ret['main_frame'] = $plugin_info->pluginUrl().'/gui/PaycardEmvGift.php?mode=' . CoreLocal::get('paycard_mode');
                break;
            case 'AVDATACAPGD':
                CoreLocal::set('CacheCardType', 'GITFT');
                CoreLocal::set('paycard_mode', PaycardLib::PAYCARD_MODE_ADDVALUE);
                CoreLocal::set('paycard_type', PaycardLib::PAYCARD_TYPE_GIFT);
                $ret['main_frame'] = $plugin_info->pluginUrl().'/gui/PaycardEmvGift.php?mode=' . CoreLocal::get('paycard_mode');
                break;
        }
        CoreLocal::set('paycard_id', CoreLocal::get('LastID')+1);

        return $ret;
    }
}

