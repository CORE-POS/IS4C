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

namespace COREPOS\pos\lib\Scanning;

/**
  @class SpecialUPC
  Handler module for non-product UPCs

  If a scanned UPC does not correspond
  to an entry in the products table, enabled
  SpecialUPC modules can supplement processing.

  CouponCode is the most universal example.
*/

class SpecialUPC 
{
    private static $builtin = array(
        'CouponCode',
        'DatabarCoupon',
        'HouseCoupon',
        'SpecialOrder',
    );

    protected $session;

    public static function factory($class, $session)
    {
        if ($class != '' && in_array($class, self::$builtin)) {
            $class = 'COREPOS\\pos\\lib\\Scanning\\SpecialUPCs\\' . $class;
            return new $class($session);
        } elseif ($class != '' && class_exists($class)) {
            return new $class($session);
        }

        return new self($session);
    }

    public function __construct($session)
    {
        $this->session = $session;
    }

    /**
      Check function
      @param $upc The UPC
      @return
       - True This module handles this UPC
       - False This module doesn't handle this UPC
    */
    public function isSpecial($upc)
    {
        return false;
    }

    /**
      Process the UPC
      @param $upc The UPC
      @param $json Keyed array
      See the Parser class for array format
      @return Keyed array
      See the Parser class for array format

      These modules supplement parsing to make
      UPC handling more customizable. The module
      will be invoked within a Paser object and
      hence uses the same return format.
    */
    public function handle($upc,$json)
    {

    }

}

