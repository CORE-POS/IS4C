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
class StoreTransferTender extends TenderModule {

	/**
	  Check for errors
	  @return True or an error message string
	*/
	function ErrorCheck(){
		global $CORE_LOCAL;

		if(MiscLib::truncate2($CORE_LOCAL->get("amtdue")) < MiscLib::truncate2($this->amount)) {
			return DisplayLib::xboxMsg(_("store transfer exceeds purchase amount"));
		}

		return True;
	}
	
	/**
	  Set up state and redirect if needed
	  @return True or a URL to redirect
	*/
	function PreReqCheck(){
		global $CORE_LOCAL;
		$my_url = MiscLib::base_url();

		if ($CORE_LOCAL->get("transfertender") != 1){
			$CORE_LOCAL->set("adminRequestLevel","30");
			$CORE_LOCAL->set("adminLoginMsg",_("Login for store transfer"));
			$tenderStr = ($this->amount*100).$this->tender_code;
			$CORE_LOCAL->set("adminRequest",
				$my_url."gui-modules/pos2.php?reginput=".$tenderStr);

			$CORE_LOCAL->set("away",1);
			$CORE_LOCAL->set("transfertender",1);
			return $my_url."gui-modules/adminlogin.php";
		}
		else {
			$CORE_LOCAL->set("transfertender",0);
			return True;
		}
	}
}

?>
