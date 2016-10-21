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
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\DisplayLib;
use \CoreLocal;

/**
  @class CheckTender
  Tender module for checks
*/
class CheckTender extends TenderModule 
{
    /**
      Check for errors
      @return True or an error message string
    */
    public function errorCheck()
    {
        $clearButton = array(_('OK [clear]') => 'parseWrapper(\'CL\');');
        if ( CoreLocal::get("isMember") != 0 && (($this->amount - CoreLocal::get("amtdue") - 0.005) > CoreLocal::get("dollarOver")) && (CoreLocal::get("cashOverLimit") == 1)) {
            return DisplayLib::boxMsg(
                _("member check tender cannot exceed total purchase by over $") . CoreLocal::get("dollarOver"),
                '',
                false,
                $clearButton
            );
        } elseif (CoreLocal::get("store")=="wfc" && CoreLocal::get("isMember") != 0 && ($this->amount - CoreLocal::get("amtdue") - 0.005) > 0) { 
            // This should really be a separate tender 
            // module for store-specific behavior
            $dbc = Database::pDataConnect();
            $query = sprintf("SELECT card_no FROM custReceiptMessage
                WHERE card_no=%d AND modifier_module='WfcEquityMessage'",
                CoreLocal::get('memberID'));
            $res = $dbc->query($query);
            if ($dbc->num_rows($res) > 0) {
                return DisplayLib::xboxMsg(
                    _("member check tender cannot exceed total purchase if equity is owed"),
                    $clearButton
                );
            }

            // multi use
            if (CoreLocal::get('standalone')==0) {
                $chkQ = "select trans_num from dlog 
                    where trans_type='T' and trans_subtype in ('CA','CK') 
                    and card_no=".((int)CoreLocal::get('memberID'))."
                    group by trans_num 
                    having sum(case when trans_subtype='CK' then total else 0 end) < 0 
                    and sum(Case when trans_subtype='CA' then total else 0 end) > 0";
                $dbc = Database::mDataConnect();
                $chkR = $dbc->query($chkQ);
                if ($dbc->num_rows($chkR) > 0) {
                    return DisplayLib::xboxMsg(_("already used check over benefit today"), $clearButton);
                }
            }
        } elseif( CoreLocal::get("isMember") == 0  && ($this->amount - CoreLocal::get("amtdue") - 0.005) > 0) { 
            $msg = _('Non-members may not write checks for more than the total purchase.');
            return DisplayLib::xboxMsg($msg, $clearButton);
        }

        return true;
    }
    
    /**
      Set up state and redirect if needed
      @return True or a URL to redirect
    */
    public function preReqCheck()
    {
        if (CoreLocal::get("enableFranking") != 1) {
            return true;
        }

        // check endorsing
        if (CoreLocal::get("msgrepeat") == 0) {
            return $this->DefaultPrompt();
        }

        return true;
    }

    public function add()
    {
        // count rebate and travelers as regular checks
        if (CoreLocal::get("store")=="wfc" && ($this->tender_code == "TV" || $this->tender_code == "RC")){
            $this->tender_code = "CK";
        }
        parent::add();
    }

    public function defaultPrompt()
    {
        return parent::frankingPrompt();
    }

}

