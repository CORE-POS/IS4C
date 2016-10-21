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

use COREPOS\pos\lib\Scanning\SpecialUPC;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\DisplayLib;

/**
  @class MemberBarcode
  WFC barcoded member ID implementation

  Checks for UPC prefix specified
  by memberUpcPrefix in session

  Looks up member number via memberCards table
*/
class MemberBarcode extends SpecialUPC 
{

    public function isSpecial($upc)
    {
        $prefix = CoreLocal::get("memberUpcPrefix");
        if (substr($upc,0,strlen($prefix)) == $prefix) {
            return true;
        }

        return false;
    }

    public function handle($upc,$json)
    {
        $db = Database::pDataConnect();
        $query = "select card_no from memberCards where upc='$upc'";
        $result = $db->query($query);

        if ($db->num_rows($result) < 1) {
            $json['output'] = DisplayLib::boxMsg(
                _("Card not assigned"),
                '',
                false,
                DisplayLib::standardClearButton()
            );

            return $json;
        }

        $row = $db->fetchRow($result);
        CoreLocal::set("memberCardUsed",1);
        $json = COREPOS\pos\lib\MemberLib::memberID($row[0]);

        return $json;
    }
}

