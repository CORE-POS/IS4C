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
        global $CORE_LOCAL;
        $charge_ok = PrehLib::chargeOk();
    
        if ($charge_ok == 0) {
            return DisplayLib::boxMsg(_("member")." ".$CORE_LOCAL->get("memberID")."<br />".
                _("is not authorized")."<br />"._("to make charges"));
        } else if ($CORE_LOCAL->get("availBal") < 0) {
            return DisplayLib::boxMsg(_("member")." ".$CORE_LOCAL->get("memberID")."<br />"._("is over limit"));
        } elseif ((abs($CORE_LOCAL->get("memChargeTotal"))+ $this->amount) >= ($CORE_LOCAL->get("availBal") + 0.005)) {
            $memChargeRemain = $CORE_LOCAL->get("availBal");
            $memChargeCommitted = $memChargeRemain + $CORE_LOCAL->get("memChargeTotal");
            return DisplayLib::xboxMsg(_("available balance for charge")."<br />"._("is only \$").$memChargeCommitted);
        } elseif(MiscLib::truncate2($CORE_LOCAL->get("amtdue")) < MiscLib::truncate2($this->amount)) {
            return DisplayLib::xboxMsg(_("charge tender exceeds purchase amount"));
        }

        return true;
    }

    public function defaultPrompt()
    {
        // don't prompt at all. just apply the tender
        global $CORE_LOCAL;
        $amt = $this->DefaultTotal();
        $CORE_LOCAL->set('strEntered', (100*$amt).$this->tender_code);
        return MiscLib::base_url().'gui-modules/boxMsg2.php?autoconfirm=1';
    }
    
    /**
      Set up state and redirect if needed
      @return True or a URL to redirect
    */
    public function preReqCheck()
    {
        global $CORE_LOCAL;
        $pref = CoreState::getCustomerPref('store_charge_see_id');
        if ($pref == 'yes') {
            if ($CORE_LOCAL->get('msgrepeat') == 0) {
                $CORE_LOCAL->set("boxMsg","<BR>please verify member ID</B><BR>press [enter] to continue<P><FONT size='-1'>[clear] to cancel</FONT>");
                $CORE_LOCAL->set('lastRepeat', 'storeChargeSeeID');

                return MiscLib::base_url().'gui-modules/boxMsg2.php?quiet=1';
            } else if ($CORE_LOCAL->get('msgrepeat') == 1 && $CORE_LOCAL->get('lastRepeat') == 'storeChargeSeeID') {
                $CORE_LOCAL->set('msgrepeat', 0);
                $CORE_LOCAL->set('lastRepeat', '');
            }
        }

        return true;
    }
}

