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

use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\TransRecord;
use COREPOS\pos\lib\Scanning\DiscountType;

class CasePriceDiscount extends DiscountType 
{

    public function priceInfo($row,$quantity=1)
    {
        if (is_array($this->savedInfo)) {
            return $this->savedInfo;
        }

        $ret = array();

        $ret["regPrice"] = $row['normal_price'];
        $ret["unitPrice"] = $row['normal_price'];
        $ret['discount'] = 0;
        $ret['memDiscount'] = 0;

        if (CoreLocal::get("casediscount") > 0 && CoreLocal::get("casediscount") <= 100) {
            $casediscount = (100 - CoreLocal::get("casediscount"))/100;
            $ret['unitPrice'] = MiscLib::truncate2($casediscount * $ret['unitPrice']);
            $ret['regPrice'] = $ret['unitPrice'];
            CoreLocal::set("casediscount",0);
        }
        

        $this->savedRow = $row;
        $this->savedInfo = $ret;

        return $ret;
    }

    public function addDiscountLine()
    {
        TransRecord::addcdnotify();
    }

    public function isSale()
    {
        return false;
    }

    public function isMemberOnly()
    {
        return false;
    }

    public function isStaffOnly()
    {
        return false;
    }

}

