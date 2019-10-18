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

namespace COREPOS\pos\lib\Scanning\SpecialDepts;
use COREPOS\pos\lib\Scanning\SpecialDept;
use COREPOS\pos\lib\MiscLib;

class BottleReturnDept extends SpecialDept
{
    public $help_summary = 'Invert entered amount e.g. 100 means $1 refund not $1 sale';

    public function handle($deptID,$amount,$json)
    {
        // msgrepeat == 0 implies price is not inverted yet.
        if (strstr($this->session->get('strEntered'), 'DP') &&
            $this->session->get('msgrepeat') == 0)
        {
            // Re-compose the entered string with inverted price.
            // First, compose the multiple part, if there is one.
            $quantityString = ($this->session->get("quantity") > 0 &&
                $this->session->get("multiple") == 1) ?
                $qtyString = $this->session->get("quantity") . '*' :
                '';
            // Compose with inverted price.
            $this->session->set('strEntered', $qtyString.(100*$amount * -1).'DP'.$deptID);
            // Re-submit with inverted price, and msgrepeat == 1 to prevent
            //  this inversion from being done again.
            $this->session->set('msgrepeat', 1);
            $json['main_frame'] = MiscLib::baseURL().'gui-modules/boxMsg2.php?autoconfirm=1';
        }

        return $json;
    }
}

