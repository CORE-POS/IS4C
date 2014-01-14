<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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
  @class ArOverpayAction
  Check total AR payments again current balance
  when subtotalling. Issue a warning if payments
  exceed balance owed.
*/
class ArOverpayAction  extends TotalAction
{
    /**
      Apply action
      @return [boolean] true if the action
        completes successfully (or is not
        necessary at all) or [string] url
        to redirect to another page for
        further decisions/input.
    */
    public function apply()
    {
        global $CORE_LOCAL;
		$temp = PrehLib::chargeOk();
		if ($CORE_LOCAL->get("balance") < $CORE_LOCAL->get("memChargeTotal") && $CORE_LOCAL->get("memChargeTotal") > 0) {
			if ($CORE_LOCAL->get('msgrepeat') == 0) {
				$CORE_LOCAL->set("boxMsg",sprintf("<b>A/R Imbalance</b><br />
					Total AR payments $%.2f exceeds AR balance %.2f<br />
					<font size=-1>[enter] to continue, [clear] to cancel</font>",
					$CORE_LOCAL->get("memChargeTotal"),
					$CORE_LOCAL->get("balance")));
				$CORE_LOCAL->set("strEntered","TL");
				return MiscLib::baseURL()."gui-modules/boxMsg2.php?quiet=1";
			}
		}

        return true;
    }
}

