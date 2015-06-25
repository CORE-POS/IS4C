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
    public function check($str)
    {
        if ($str == 'DATACAPEMV') {
            return true;
        } elseif ($str == 'DATACAPCC') {
            return true;
        } elseif ($str == 'DATACAPDC') {
            return true;
        } elseif ($str == 'DATACAPEF') {
            return true;
        } elseif ($str == 'DATACAPEC') {
            return true;
        } elseif ($str == 'DATACAPGD') {
            return true;
        } elseif ($str == 'PVDATACAPGD') {
            return true;
        } elseif ($str == 'PVDATACAPEF') {
            return true;
        } elseif ($str == 'PVDATACAPEC') {
            return true;
        }
    }

    public function parse($str)
    {
        $ret = $this->default_json();
        $plugin_info = new Paycards();
        $ret['main_frame'] = $plugin_info->plugin_url().'/gui/PaycardEmvPage.php';
        Database::getsubtotals();
        switch ($str) {
            case 'DATACAPEMV': 
                CoreLocal::set('paycard_amount', CoreLocal::get('amtdue'));
                CoreLocal::set('CacheCardType', 'EMV');
                CoreLocal::set('paycard_mode', PaycardLib::PAYCARD_MODE_AUTH);
                CoreLocal::set('paycard_type', PaycardLib::PAYCARD_TYPE_CREDIT);
                break;
            case 'DATACAPCC':
                CoreLocal::set('paycard_amount', CoreLocal::get('amtdue'));
                CoreLocal::set('CacheCardType', 'CREDIT');
                CoreLocal::set('paycard_mode', PaycardLib::PAYCARD_MODE_AUTH);
                CoreLocal::set('paycard_type', PaycardLib::PAYCARD_TYPE_CREDIT);
                break;
            case 'DATACAPDC':
                CoreLocal::set('paycard_amount', CoreLocal::get('amtdue'));
                if (CoreLocal::get('CacheCardCashBack')) {
                    CoreLocal::set('paycard_amount', CoreLocal::get('amtdue') + CoreLocal::get('CacheCardCashBack'));
                }
                CoreLocal::set('CacheCardType', 'DEBIT');
                CoreLocal::set('paycard_mode', PaycardLib::PAYCARD_MODE_AUTH);
                CoreLocal::set('paycard_type', PaycardLib::PAYCARD_TYPE_CREDIT);
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
                CoreLocal::set('paycard_mode', PaycardLib::PAYCARD_MODE_AUTH);
                CoreLocal::set('paycard_type', PaycardLib::PAYCARD_TYPE_CREDIT);
                break;
            case 'DATACAPEC':
                CoreLocal::set('paycard_amount', CoreLocal::get('amtdue'));
                if (CoreLocal::get('CacheCardCashBack')) {
                    CoreLocal::set('paycard_amount', CoreLocal::get('amtdue') + CoreLocal::get('CacheCardCashBack'));
                }
                CoreLocal::set('CacheCardType', 'EBTCASH');
                CoreLocal::set('paycard_mode', PaycardLib::PAYCARD_MODE_AUTH);
                CoreLocal::set('paycard_type', PaycardLib::PAYCARD_TYPE_CREDIT);
                break;
            case 'DATACAPGD':
                CoreLocal::set('paycard_amount', CoreLocal::get('amtdue'));
                CoreLocal::set('CacheCardType', 'GIFT');
                CoreLocal::set('paycard_mode', PaycardLib::PAYCARD_MODE_AUTH);
                CoreLocal::set('paycard_type', PaycardLib::PAYCARD_TYPE_GIFT);
                break;
            case 'PVDATACAPGD':
                CoreLocal::set('CacheCardType', 'GIFT');
                CoreLocal::set('paycard_mode', PaycardLib::PAYCARD_MODE_BALANCE);
                CoreLocal::set('paycard_type', PaycardLib::PAYCARD_TYPE_GIFT);
                $ret['main_frame'] = $plugin_info->plugin_url().'/gui/PaycardEmvBalance.php';
                break;
            case 'PVDATACAPEF':
                CoreLocal::set('CacheCardType', 'EBTFOOD');
                CoreLocal::set('paycard_mode', PaycardLib::PAYCARD_MODE_BALANCE);
                CoreLocal::set('paycard_type', PaycardLib::PAYCARD_TYPE_CREDIT);
                $ret['main_frame'] = $plugin_info->plugin_url().'/gui/PaycardEmvBalance.php';
                break;
            case 'PVDATACAPEC':
                CoreLocal::set('CacheCardType', 'EBTCASH');
                CoreLocal::set('paycard_mode', PaycardLib::PAYCARD_MODE_BALANCE);
                CoreLocal::set('paycard_type', PaycardLib::PAYCARD_TYPE_CREDIT);
                $ret['main_frame'] = $plugin_info->plugin_url().'/gui/PaycardEmvBalance.php';
                break;
        }
        CoreLocal::set('paycard_id', CoreLocal::get('LastID')+1);

        return $ret;
    }
}

