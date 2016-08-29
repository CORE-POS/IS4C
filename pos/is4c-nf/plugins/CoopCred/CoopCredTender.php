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

use COREPOS\pos\lib\CoreState;
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\Tenders\TenderModule;

/**
  @class CoopCredTender
  Tender module for Coop Cred accounts
  Used for purchases under all Coop Cred programs.
*/
class CoopCredTender extends TenderModule 
{

    /**
      Singleton database connection
    */
    protected $conn = null;

    /**
      Check for errors
      @return True or an error message string
    */
    public function errorCheck()
    {
        global $CORE_LOCAL;

        $this->conn = CoopCredLib::ccDataConnect();
        if ($this->conn === False) {
            return "Error: ccDataConnect() failed.";
        }

        $programOK = CoopCredLib::programOK($this->tender_code, $this->conn);
        if ($programOK !== True) {
            return DisplayLib::boxMsg("$programOK");
        }

        $subtotals = CoopCredLib::getCCredSubtotals($this->tender_code, $this->conn);
        if ($subtotals !== True) {
            return DisplayLib::boxMsg("$subtotals");
        }

        $pc = $CORE_LOCAL->get("CCredProgramCode");
        //$pc = $CORE_LOCAL->get("programCode");

        /* For Refunding the total without entering the exact amount
         *  i.e. with QA alone.
         */
        if ($this->amount == '' && $this->DefaultTotal() < 0) {
            $this->amount = $this->DefaultTotal();
        }

        /* No Available Balance.
         */
        if ($CORE_LOCAL->get("{$pc}availBal") < 0) {
            return DisplayLib::boxMsg(
                _("Member")." #".$CORE_LOCAL->get("memberID").' '.
                _("does not have enough Coop Cred in ") .
                '<b>'.  $CORE_LOCAL->get("{$pc}programName"). '</b>' .
                _(" to cover this purchase."));
        }
        /* Tender more than Available Balance
         * the amount remaining less the amount of this type already tendered
         * in the current transaction.
         * I think availBal already includes memChargeTotal.
         */
        if ((abs($CORE_LOCAL->get("{$pc}memChargeTotal"))+$this->amount) >=
            ($CORE_LOCAL->get("{$pc}availBal") + 0.005)
            ) {
            $memChargeCommitted = $CORE_LOCAL->get("{$pc}availBal") +
                                $CORE_LOCAL->get("{$pc}memChargeTotal");
            return DisplayLib::xboxMsg(
                _("The amount of Coop Cred you have in ").
                '<b>'.  $CORE_LOCAL->get("{$pc}programName"). '</b>' .
                _(" is only \$") .
                number_format($memChargeCommitted,2) .
                '.');
        }
        /* Tender more than Amount Due.
         */
        if(MiscLib::truncate2($CORE_LOCAL->get("amtdue")) <
            MiscLib::truncate2($this->amount)
        ) {
            return DisplayLib::xboxMsg(
                _("The amount of Coop Cred tendered may not exceed the Amount Due."));
        }

        /* Add the tender to those used in this transaction.
         */
        if ($CORE_LOCAL->get('CCredTendersUsed') == '') {
            $CORE_LOCAL->set('CCredTendersUsed', array(
                "$this->tender_code" => $CORE_LOCAL->get("CCredProgramID")
            ));
        } else {
            $tu = $CORE_LOCAL->get('CCredTendersUsed');
            if (!array_key_exists("$this->tender_code", $tu)) {
                $tu["$this->tender_code"] = $CORE_LOCAL->get("CCredProgramID");
                $CORE_LOCAL->set('CCredTendersUsed',$tu);
            }
        }

        return true;

    // errorCheck()
    }

    /**
      What to do if the tender code entered alone,
       implying "pay for all of amount due with this tender"
      @return a URL to redirect
     */
    public function defaultPrompt()
    {
        global $CORE_LOCAL;
        $amt = $this->DefaultTotal();
        /* Make it as though the amount due preceded the tender code
         * in the regular input box.
         */
        $CORE_LOCAL->set('strEntered', (100*$amt).$this->tender_code);
        // Don't (autoconfirm=1) ask for OK. Just apply the tender.
        return MiscLib::base_url().'gui-modules/boxMsg2.php?autoconfirm=0';
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
                $CORE_LOCAL->set("boxMsg",("<BR>please verify member ID</B>" .
                    "<BR>press [enter] to continue" .
                    "<P><FONT size='-1'>[clear] to cancel</FONT>"));
                $CORE_LOCAL->set('lastRepeat', 'storeChargeSeeID');

                return MiscLib::base_url().'gui-modules/boxMsg2.php?quiet=1';
            } else if ($CORE_LOCAL->get('msgrepeat') == 1 &&
                    $CORE_LOCAL->get('lastRepeat') == 'storeChargeSeeID') {
                $CORE_LOCAL->set('msgrepeat', 0);
                $CORE_LOCAL->set('lastRepeat', '');
            }
        }

        return true;
    }

// CoopCredTender class
}

