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
class SignedStoreChargeTender extends StoreChargeTender 
{

    /**
      Set up state and redirect if needed
      @return True or a URL to redirect
    */
    public function preReqCheck()
    {
        global $CORE_LOCAL;
        if ($CORE_LOCAL->get('msgrepeat') == 0) {
            $CORE_LOCAL->set('strRemembered', ($this->amount*100) . $this->tender_code);
            $CORE_LOCAL->set('lastRepeat', 'signStoreCharge');

            return MiscLib::base_url().'gui-modules/SigCapturePage.php?type='.$this->name_string.'&amt='.$this->amount.'&code='.$this->tender_code;
        } else if ($CORE_LOCAL->get('msgrepeat') == 1 && $CORE_LOCAL->get('lastRepeat') == 'signStoreCharge') {
            $CORE_LOCAL->set('msgrepeat', 0);
            $CORE_LOCAL->set('lastRepeat', '');
        }

        return true;
    }
}

