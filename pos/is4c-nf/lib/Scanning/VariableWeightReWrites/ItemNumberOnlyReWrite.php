<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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
  @class ItemNumberOnlyReWrite

  Use five digit item number as the UPC

  Ex: 0021234500000 becomes 0000000012345
*/
class ItemNumberOnlyReWrite extends VariableWeightReWrite 
{
    public function translate($upc, $includes_check_digit=false)
    {
        $item_number = '';
        if ($includes_check_digit) {
            $item_number = substr($upc, 2, 5);
        } else {
            $item_number = substr($upc, 3, 5);
        }

        return str_pad($item_number, 13, '0', STR_PAD_LEFT);
    }
}

