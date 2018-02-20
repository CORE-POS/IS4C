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

use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\PrehLib;
use COREPOS\pos\parser\Parser;

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
        'DATACAPRECUR',
        'PVDATACAP',
    );
    
    public function __construct($session)
    {
        parent::__construct($session);
        $this->conf = new PaycardConf();
    }

    public function check($str)
    {
        if (in_array($str, $this->valid)) {
            return true;
        }
        return false;
    }

    public function parse($str)
    {
        $ret = $this->default_json();
        if ($this->conf->get("ttlflag") != 1 && $str !== 'DATACAP' && substr($str, 0, 9) !== 'PVDATACAP') { // must subtotal before running card
            $ret['output'] = PaycardLib::paycardMsgBox("No Total",
                "Transaction must be totaled before tendering or refunding","[clear] to cancel");
            return $ret;
        }
        $pluginInfo = new Paycards();
        $ret['main_frame'] = $pluginInfo->pluginUrl().'/gui/PaycardEmvPage.php';
        Database::getsubtotals();
        $this->conf->set('paycard_amount', $this->conf->get('amtdue'));
        $this->conf->set('paycard_mode', PaycardLib::PAYCARD_MODE_AUTH);
        $this->conf->set('paycard_type', PaycardLib::PAYCARD_TYPE_CREDIT);
        $str = $this->remap($str);
        switch ($str) {
            case 'DATACAP':
                $ret['main_frame'] = $pluginInfo->pluginUrl().'/gui/PaycardEmvMenu.php';
                if ($this->conf->get('ttlflag') != 1) {
                    $ret['main_frame'] .= '?selectlist=PV';
                }
                break; 
            case 'PVDATACAP':
                $ret['main_frame'] = $pluginInfo->pluginUrl().'/gui/PaycardEmvMenu.php?selectlist=PV';
                break; 
            case 'DATACAPEMV': 
                $this->conf->set('CacheCardType', 'EMV');
                $this->conf->set('CacheCardCashBack', 0);
                break;
            case 'DATACAPCC':
                $this->conf->set('CacheCardType', 'CREDIT');
                $this->conf->set('CacheCardCashBack', 0);
                break;
            case 'DATACAPCCAUTO':
                $autoMode = $this->conf->get('PaycardsDatacapMode') == 1 ? 'EMV' : 'CREDIT';
                $this->conf->set('CacheCardType', $autoMode);
                $this->conf->set('CacheCardCashBack', 0);
                $ret['main_frame'] .= '?reginput=';
                break;
            case 'DATACAPDC':
                if ($this->conf->get('CacheCardCashBack')) {
                    $this->conf->set('paycard_amount', $this->conf->get('amtdue') + $this->conf->get('CacheCardCashBack'));
                }
                $this->conf->set('CacheCardType', 'DEBIT');
                break;
            case 'DATACAPEF':
                if ($this->conf->get('fntlflag') == 0) {
                    /* try to automatically do fs total */
                    $try = PrehLib::fsEligible();
                    if ($try !== true) {
                        $ret['output'] = PaycardLib::paycardMsgBox("Type Mismatch",
                            "Foodstamp eligible amount inapplicable","[clear] to cancel");
                        $ret['main_frame'] = false;
                        return $ret;
                    } 
                }
                $this->conf->set('paycard_amount', $this->conf->get('fsEligible'));
                $this->conf->set('CacheCardType', 'EBTFOOD');
                $this->conf->set('CacheCardCashBack', 0);
                break;
            case 'DATACAPEC':
                if ($this->conf->get('CacheCardCashBack')) {
                    $this->conf->set('paycard_amount', $this->conf->get('amtdue') + $this->conf->get('CacheCardCashBack'));
                }
                $this->conf->set('CacheCardType', 'EBTCASH');
                break;
            case 'DATACAPGD':
                $this->conf->set('CacheCardType', 'GIFT');
                $this->conf->set('paycard_type', PaycardLib::PAYCARD_TYPE_GIFT);
                $this->conf->set('CacheCardCashBack', 0);
                break;
            case 'PVDATACAPGD':
                $this->conf->set('CacheCardType', 'GIFT');
                $this->conf->set('paycard_mode', PaycardLib::PAYCARD_MODE_BALANCE);
                $this->conf->set('paycard_type', PaycardLib::PAYCARD_TYPE_GIFT);
                $ret['main_frame'] = $pluginInfo->pluginUrl().'/gui/PaycardEmvBalance.php';
                break;
            case 'PVDATACAPEF':
                $this->conf->set('CacheCardType', 'EBTFOOD');
                $this->conf->set('paycard_mode', PaycardLib::PAYCARD_MODE_BALANCE);
                $ret['main_frame'] = $pluginInfo->pluginUrl().'/gui/PaycardEmvBalance.php';
                break;
            case 'PVDATACAPEC':
                $this->conf->set('CacheCardType', 'EBTCASH');
                $this->conf->set('paycard_mode', PaycardLib::PAYCARD_MODE_BALANCE);
                $ret['main_frame'] = $pluginInfo->pluginUrl().'/gui/PaycardEmvBalance.php';
                break;
            case 'ACDATACAPGD':
                $this->conf->set('CacheCardType', 'GIFT');
                $this->conf->set('paycard_mode', PaycardLib::PAYCARD_MODE_ACTIVATE);
                $this->conf->set('paycard_type', PaycardLib::PAYCARD_TYPE_GIFT);
                $ret['main_frame'] = $pluginInfo->pluginUrl().'/gui/PaycardEmvGift.php?mode=' . $this->conf->get('paycard_mode');
                break;
            case 'AVDATACAPGD':
                $this->conf->set('CacheCardType', 'GIFT');
                $this->conf->set('paycard_mode', PaycardLib::PAYCARD_MODE_ADDVALUE);
                $this->conf->set('paycard_type', PaycardLib::PAYCARD_TYPE_GIFT);
                $ret['main_frame'] = $pluginInfo->pluginUrl().'/gui/PaycardEmvGift.php?mode=' . $this->conf->get('paycard_mode');
                break;
            case 'DATACAPRECUR':
                $ret['main_frame'] = $pluginInfo->pluginUrl().'/gui/PaycardEmvRecurring.php';
                break;
        }
        $this->conf->set('paycard_id', $this->conf->get('LastID')+1);

        return $ret;
    }

    /**
     * If the customer selected a tender type, re-write the generic
     * DATACAP command to skip the menu and proceed with the chosen
     * tender type. DC maps to magstripe debit for cashback purposes.
     * CC maps to EMV if that functionality is enabled or magstripe credit
     * if not.
     */
    private function remap($input)
    {
        $selection = $this->conf->get('ccTermState');
        if ($input !== 'DATACAP' || strlen($selection) !== 4 || substr($selection, 0, 2) !== 'DC') {
            return $input;
        }
        switch (substr($selection, -2)) {
            case 'DC':
                return 'DATACAPDC';
            case 'EC':
                return 'DATACAPEC';
            case 'EF':
                return 'DATACAPEF';
            case 'CC':
                return $this->conf->get('PaycardsDatacapMode') == 1 ? 'DATACAPEMV' : 'DATACAPCC';
        }

        return $input;
    }
}

