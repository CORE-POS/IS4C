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

namespace COREPOS\pos\lib\TotalActions;
use \CoreLocal;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\TransRecord;

/**
  @class OntarioMealTaxRebateAction
  Update tax codes and add comment records
  to account for Ontario Meal Tax Rebate
  when subtotaling the transaction.
*/
class OntarioMealTaxRebateAction extends TotalAction
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
        // Is the before-tax total within range?
        if (CoreLocal::get("runningTotal") <= 4.00 ) {
            $totalBefore = CoreLocal::get("amtdue");
            $ret = Database::changeLttTaxCode("HST","GST");
            if ( $ret !== true ) {
                TransRecord::addcomment("$ret");
            } else {
                Database::getsubtotals();
                $saved = ($totalBefore - CoreLocal::get("amtdue"));
                $comment = sprintf("OMTR OK. You saved: $%.2f", $saved);
                TransRecord::addcomment("$comment");
            }
        } else {
            TransRecord::addcomment("Does NOT qualify for OMTR");
        }

        return true;
    }
}

