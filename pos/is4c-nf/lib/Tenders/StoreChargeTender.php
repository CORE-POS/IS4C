<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

namespace COREPOS\pos\lib\Tenders;
use COREPOS\pos\lib\CoreState;
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\MiscLib;
use \CoreLocal;

/**
  @class StoreChargeTender
  Tender module for charge accounts
*/
class StoreChargeTender extends TenderModule 
{

    /**
      Check for errors
      @return True or an error message string
    */
    public function errorCheck()
    {
        $chargeOk = \COREPOS\pos\lib\MemberLib::chargeOk();
    
        $buttons = array('[clear]' => 'parseWrapper(\'CL\');');
        if ($chargeOk == 0) {
            return DisplayLib::boxMsg(
                _("member") . ' ' . CoreLocal::get("memberID") . '<br />' .
                _("is not authorized") . '<br />' ._("to make charges"),
                'Not Allowed',
                false,
                $buttons
            );
        } else if (CoreLocal::get("availBal") < 0) {
            return DisplayLib::boxMsg(
                _("member") . ' ' . CoreLocal::get("memberID") . '<br />' .
                _("is over limit"),
                'Over Limit',
                false,
                $buttons
            );
        } elseif ((abs(CoreLocal::get("memChargeTotal"))+ $this->amount) >= (CoreLocal::get("availBal") + 0.005)) {
            $memChargeRemain = CoreLocal::get("availBal");
            $memChargeCommitted = $memChargeRemain + CoreLocal::get("memChargeTotal");
            return DisplayLib::xboxMsg(
                _("available balance for charge") . '<br />' .
                _("is only \$") . $memChargeCommitted,
                $buttons
            );
        } elseif (abs(MiscLib::truncate2(CoreLocal::get("amtdue"))) < abs(MiscLib::truncate2($this->amount))) {
            return DisplayLib::xboxMsg(
                _("charge tender exceeds purchase amount"),
                $buttons
            );
        }

        return true;
    }

    public function defaultPrompt()
    {
        // don't prompt at all. just apply the tender
        $amt = $this->DefaultTotal();
        CoreLocal::set('strEntered', (100*$amt).$this->tender_code);
        return MiscLib::base_url().'gui-modules/boxMsg2.php?autoconfirm=1';
    }
    
    /**
      Set up state and redirect if needed
      @return True or a URL to redirect
    */
    public function preReqCheck()
    {
        $pref = CoreState::getCustomerPref('store_charge_see_id');
        if ($pref == 'yes') {
            if (CoreLocal::get('msgrepeat') == 0) {
                CoreLocal::set("boxMsg",_("<BR>please verify member ID</B><BR>press [enter] to continue<P><FONT size='-1'>[clear] to cancel</FONT>"));
                CoreLocal::set('lastRepeat', 'storeChargeSeeID');

                return MiscLib::base_url().'gui-modules/boxMsg2.php?quiet=1';
            } else if (CoreLocal::get('msgrepeat') == 1 && CoreLocal::get('lastRepeat') == 'storeChargeSeeID') {
                CoreLocal::set('msgrepeat', 0);
                CoreLocal::set('lastRepeat', '');
            }
        }

        return true;
    }
}

