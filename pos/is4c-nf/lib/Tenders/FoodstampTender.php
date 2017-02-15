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
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\TransRecord;
use \CoreLocal;

/**
  @class FoodstampTender
  Tender module for EBT
*/
class FoodstampTender extends TenderModule 
{

    /**
      Check for errors
      @return True or an error message string
    */
    public function errorCheck()
    {
        if (CoreLocal::get("fntlflag") == 0) {
            return DisplayLib::boxMsg(
                _("eligible amount must be totaled before foodstamp tender can be accepted"),
                '',
                false,
                array(_('Total [FS Total]') => 'parseWrapper(\'FNTL\');$(\'#reginput\').focus();', _('Dimiss [clear]') => 'parseWrapper(\'CL\');')
            );
        } elseif ($this->amount !== false && $this->amount > 0 && $this->amount > (CoreLocal::get("fsEligible") + 0.005)) {
            return DisplayLib::xboxMsg(
                _('Foodstamp tender cannot exceed eligible amount'),
                DisplayLib::standardClearButton()
            );
        } elseif ($this->amount !== false && $this->amount <= 0 && $this->amount < (CoreLocal::get("fsEligible") - 0.005)) {
            return DisplayLib::xboxMsg(
                _('Foodstamp return cannot exceed eligible amount' . CoreLocal::get('fsEligible')), 
                DisplayLib::standardClearButton()
            );
        }

        return true;
    }
    
    /**
      Set up state and redirect if needed
      @return True or a URL to redirect
    */
    public function preReqCheck(){
        return true;
    }

    public function add()
    {
        parent::add();
        TransRecord::addfsTaxExempt();
    }

    public function allowDefault()
    {
        return false;
    }
}

