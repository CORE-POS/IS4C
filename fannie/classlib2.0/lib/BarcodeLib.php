<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

    This file is part of Fannie.

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
  @class BarcodeLib
  Barcode related functions
*/
class BarcodeLib
{

    /**
      Zero-padd a UPC to standard length
      @param $upc string upc
      @return standard length upc
    */
    static public function padUPC($upc)
    {
        return str_pad(trim($upc), 13, '0', STR_PAD_LEFT);
    }

    /**
      Calculate standard check digit for UPCs, EANs, etc
      @param $upc string upc
      @return int check digit
    */
    static public function getCheckDigit($upc)
    {
        // GTIN standard provides weights for 17 digits
        // values must be right aligned, so left pad with 0s
        $upc = str_pad($upc, 17, '0', STR_PAD_LEFT);

        $sum = 0;
        for ($i=0; $i<17; $i++) {
            if ($i % 2 == 0) {
                $sum += 3 * $upc[$i];
            } else {
                $sum += $upc[$i];
            }
        }

        $mod = $sum % 10;
        if ($mod == 0) {
            return 0;
        } else {
            return 10 - $mod;
        }
    }

}

