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
  @class CreditCardTender
  Tender module for credit cards
*/
class CreditCardTender extends TenderModule 
{

    /**
      Check for errors
      @return True or an error message string
    */
    public function errorCheck()
    {
        global $CORE_LOCAL;
        
        //force negative entered value when the total is negative.
        if ($CORE_LOCAL->get("amtdue") <0 && $this->amount >= 0){
            $this->amount = -1 * $this->amount;
        }

        if (($this->amount > ($CORE_LOCAL->get("amtdue") + 0.005)) && $CORE_LOCAL->get("amtdue") >= 0){ 
            return DisplayLib::xboxMsg(_("tender cannot exceed purchase amount"));
        } elseif ((($this->amount < ($CORE_LOCAL->get("amtdue") - 0.005)) || ($this->amount > ($CORE_LOCAL->get("amtdue") + 0.005)))
                     && $CORE_LOCAL->get("amtdue") < 0 
                     && $this->amount !=0) {
            // the return tender needs to be exact because the transaction state can get weird.
            return DisplayLib::xboxMsg(_("return tender must be exact"));
        } elseif($CORE_LOCAL->get("amtdue")>0 && $this->amount < 0) {
            return DisplayLib::xboxMsg(_("Why are you useing a negative number?"));
        }

        return true;
    }
    
    /**
      Set up state and redirect if needed
      @return True or a URL to redirect
    */
    public function preReqCheck()
    {
        global $CORE_LOCAL;
        if ($this->tender_code == 'CC' && $CORE_LOCAL->get('store') == 'wfc')
            $CORE_LOCAL->set('kickOverride',true);

        return true;
    }

    public function allowDefault()
    {
        if ($this->tender_code == 'CC' && $CORE_LOCAL->get('store') == 'wfc') {
            return True;
        } else {
            return False;
        }
    }
}

