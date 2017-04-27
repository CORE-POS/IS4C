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
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\adminlogin\AdminLoginInterface;
use \CoreLocal;

/**
  @class StoreTransfer
  Tender module for inter-departmental transfers
  Requires Mgr. password
*/
class StoreTransferTender extends TenderModule implements AdminLoginInterface
{
    /**
      Check for errors
      @return True or an error message string
    */
    public function errorCheck()
    {
        if(MiscLib::truncate2(CoreLocal::get("amtdue")) < MiscLib::truncate2($this->amount)) {
            return DisplayLib::xboxMsg(
                _("store transfer exceeds purchase amount"),
                DisplayLib::standardClearButton()
            );
        }

        $dbc = Database::pDataConnect();
        $query = 'SELECT chargeOk FROM custdata WHERE chargeOk=1 AND CardNo='.CoreLocal::get('memberID');
        $result = $dbc->query($query);
        if ($dbc->numRows($result) == 0) {
            return DisplayLib::xboxMsg(
                _("member cannot make transfers"),
                DisplayLib::standardClearButton()
            );
        }

        return true;
    }
    
    /**
      Set up state and redirect if needed
      @return True or a URL to redirect
    */
    public function preReqCheck()
    {
        $myUrl = MiscLib::baseURL();

        if (CoreLocal::get("transfertender") != 1) {
            CoreLocal::set("transfertender",1);
            return $myUrl."gui-modules/adminlogin.php?class=COREPOS-pos-lib-Tenders-StoreTransferTender";
        }
        CoreLocal::set("transfertender",0);

        return true;
    }

    /**
      adminlogin callback to approve store transfers
    */
    public static function messageAndLevel()
    {
        return array(_('Login for store transfer'), 30);
    }

    static public function adminLoginCallback($success)
    {
        if ($success) {
            $inp = urlencode(CoreLocal::get('strEntered'));
            return MiscLib::baseURL() . 'gui-modules/pos2.php?reginput=' . $inp . '&repeat=1';
        }
        CoreLocal::set('transfertender', 0);

        return false;
    }
}

