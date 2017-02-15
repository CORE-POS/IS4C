<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

namespace COREPOS\pos\lib\Scanning\DiscountTypes;
use COREPOS\pos\lib\Scanning\DiscountType;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\TransRecord;

class MemberSale extends DiscountType 
{

    public function priceInfo(array $row, $quantity=1)
    {
        if (is_array($this->savedInfo)) {
            return $this->savedInfo;
        }

        $ret = array();

        $ret["regPrice"] = $row['normal_price'];
        $ret["unitPrice"] = $row['normal_price'];

        $ret['discount'] = 0;
        $ret['memDiscount'] = MiscLib::truncate2(($ret['regPrice'] - $row['special_price']) * $quantity);

        if ($this->session->get("isMember") == 1 || (
            $this->session->get("memberID") == $this->session->get("visitingMem") && $this->session->get('visitingMem') !== ''
            )) {
            $ret["unitPrice"] = $row['special_price'];
        }

        if ($row['line_item_discountable'] == 1 && $this->session->get("itemPD") > 0) {
            $discount = $ret['unitPrice'] * (($this->session->get("itemPD")/100));
            $ret["unitPrice"] -= $discount;
            $ret["discount"] += ($discount * $quantity);
        }

        $this->savedRow = $row;
        $this->savedInfo = $ret;

        return $ret;
    }

    public function addDiscountLine()
    {
        if ($this->session->get("isMember") == 1 || $this->session->get("memberID") == $this->session->get("visitingMem")) {
            TransRecord::adddiscount($this->savedInfo['memDiscount'],
                $this->savedRow['department']);
        }
        if ($this->savedInfo['discount'] != 0) {
            TransRecord::adddiscount($this->savedInfo['discount'],
                    $this->savedRow['department']);
        }
    }

    public function isSale()
    {
        return true;
    }

    public function isMemberOnly()
    {
        return true;
    }

    public function isStaffOnly()
    {
        return false;
    }

}

