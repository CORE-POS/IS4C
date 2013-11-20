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
  @class StoreTransfer
  Tender module for inter-departmental transfers
  Requires Mgr. password
*/
class StoreTransferTender extends TenderModule 
{

    /**
      Check for errors
      @return True or an error message string
    */
    public function errorCheck()
    {
        global $CORE_LOCAL;

        if(MiscLib::truncate2($CORE_LOCAL->get("amtdue")) < MiscLib::truncate2($this->amount)) {
            return DisplayLib::xboxMsg(_("store transfer exceeds purchase amount"));
        }

        $db = Database::pDataConnect();
        $query = 'SELECT chargeOk FROM custdata WHERE chargeOk=1 AND CardNo='.$CORE_LOCAL->get('memberID');
        $result = $db->query($query);
        if ($db->num_rows($result) == 0) {
            return DisplayLib::xboxMsg(_("member cannot make transfers"));
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
        $my_url = MiscLib::base_url();

        if ($CORE_LOCAL->get("transfertender") != 1) {
            $CORE_LOCAL->set("transfertender",1);
            return $my_url."gui-modules/adminlogin.php?class=StoreTransferTender";
        } else {
            $CORE_LOCAL->set("transfertender",0);
            return true;
        }
    }

    /**
      adminlogin callback to approve store transfers
    */
    public static $adminLoginMsg = 'Login for store transfer';

    public static $adminLoginLevel = 30;

    static public function adminLoginCallback($success)
    {
        global $CORE_LOCAL;
        if ($success) {
            $CORE_LOCAL->set('strRemembered', $CORE_LOCAL->get('strEntered'));    
            $CORE_LOCAL->set('msgrepeat', 1);
            return true;
        } else {
            $CORE_LOCAL->set('transfertender', 0);
            return false;
        }
    }
}

