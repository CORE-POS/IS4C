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

/**
  @class RefundAndCashBackTender
  Module for tenders where change records
  on refund transactions should have one
  trans_subtype (tenders.TenderCode) and
  change on cash-back transactions should
  have a different one (tenders.TenderType) 
*/
class RefundAndCashBackTender extends TenderModule 
{

    /**
      What type should be used for change records associated with this tender.
      @return string tender code
    */
    public function changeType()
    {
        return ($this->amount == 0 ? $this->tender_code : $this->change_type);
    }

}

