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
  @class Accounting
  API class related to chart of account values

  This base class does not modify any values
  but child classes might perform transformations.
*/
class Accounting
{
    /**
      Convert a sales chart of account number to
      the corresponding purchases account number
      @param $code [string] sales account number
      @return [string] purchase account number
    */
    public static function toPurchaseCode($code)
    {
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
        return $code;
    }

    public static function extend($code, $store_id)
    {
        return $code;
    }
}

