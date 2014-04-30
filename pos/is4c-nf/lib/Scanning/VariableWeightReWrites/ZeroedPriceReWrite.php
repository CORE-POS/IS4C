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
  @class ZeroedPriceReWrite

  Replaces the price portion of the UPC
  with zeros.

  Ex:
  0021234500199 beomces 0021234500000

  Note: if using check digits, the check digit
  is also zeroed out rather than re-calculating
  the correct value for the zero-price UPC.
*/
class ZeroedPriceReWrite extends VariableWeightReWrite 
{
    public function translate($upc, $includes_check_digit=false)
    {
        if ($includes_check_digit) {
            // 02 + 5 digit item number + 000000
            return substr($upc, 0, 7) . '000000';
        } else {
            // 002 + 5 digit item number + 00000
            return substr($upc, 0, 8) . '00000';
        }
    }
}

