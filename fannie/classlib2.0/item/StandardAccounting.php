<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op, Duluth, MN

    This file is part of CORE-POS.

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

namespace COREPOS\Fannie\API\item;

/**
  @class StandardAccounting

  Sale account numbers start with four.
  Purchase account numbers start with five.
  Store IDs are appended to account numbers
  with a hyphen.
*/
class StandardAccounting extends Accounting
{
    /**
      Convert a sales chart of account number to
      the corresponding purchases account number
      @param $code [string] sales account number
      @return [string] purchase account number
    */
    public static function toPurchaseCode($code)
    {
        if (substr($code, 0, 1) == '4') {
            return '5' . substr($code, 1);
        }
        return $code;
    }

    /**
      Convert a purchasing chart of account number to
      the corresponding sales account number
      @param $code [string] purchasing account number
      @return [string] sale account number
    */
    public static function toSaleCode($code)
    {
        if (substr($code, 0, 1) == '5') {
            return '4' . substr($code, 1);
        }
        return $code;
    }

    /**
      Convert a chart of account number to a
      store-specific value
      @param $code [string] account number
      @param $store_id [int] store identifier
      @return [string] modified account number
    */
    public static function perStoreCode($code, $store_id)
    {
        return $code . '-' . str_pad($store_id, 2, '0', STR_PAD_LEFT);
    }

    public static function extend($code, $store_id)
    {
        if (strlen($code) != 5) {
            return $code;
        }
        switch (substr($code, 1)) {
            case '1201':
            case '1205':
            case '1600':
                return self::perStoreCode($code, $store_id) . '-20';
            case '1300':
            case '1310':
            case '1315':
                return self::perStoreCode($code, $store_id) . '-30';
            case '1305':
            case '1400':
            case '1405':
            case '1407':
            case '1410':
            case '1415':
            case '1420':
            case '1425':
            case '1430':
            case '1435':
            case '1500':
            case '1505':
            case '1510':
            case '1515':
            case '1520':
            case '1605':
            case '1610':
            case '1640':
            case '1645':
            case '1700':
            case '1705':
                return self::perStoreCode($code, $store_id) . '-60';
            default:
                return self::perStoreCode($code, $store_id) . '-00';
        }
    }
}

