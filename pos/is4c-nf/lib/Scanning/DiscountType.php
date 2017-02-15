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
use COREPOS\pos\lib\Scanning\DiscountTypes\NormalPricing;
use COREPOS\pos\lib\MiscLib;
use \CoreLocal;

/**
  @class DiscountType
  Base module for computing sale prices
*/
class DiscountType 
{
    static public $MAP = array(
        0   => 'COREPOS\\pos\\lib\\Scanning\\DiscountTypes\\NormalPricing',
        1   => 'COREPOS\\pos\\lib\\Scanning\\DiscountTypes\\EveryoneSale',
        2   => 'COREPOS\\pos\\lib\\Scanning\\DiscountTypes\\MemberSale',
        3   => 'COREPOS\\pos\\lib\\Scanning\\DiscountTypes\\PercentMemSale',
        4   => 'COREPOS\\pos\\lib\\Scanning\\DiscountTypes\\StaffSale',
        5   => 'COREPOS\\pos\\lib\\Scanning\\DiscountTypes\\SlidingMemSale',
    );

    /**
      Convenience variable to save prieInfo() argument
      for later if needed
    */
    protected $savedRow;
    /**
      Convenience variable to save prieInfo() return
      value for later if needed
    */
    protected $savedInfo;

    protected $session;

    public function __construct($session)
    {
        $this->session = $session;
    }

    /**
      Calculate pricing
      @param $row A record from the products table
      @param $quantity Scanned quantity
      @return Keyed array
       - regPrice The normal price per item
       - unitPrice The actual price per unit
         If it's not on sale, unitPrice will
         match regPrice
       - discount The discount amount for everyone
       - memDiscount The discount amount for members
    */
    public function priceInfo(array $row,$quantity=1)
    {
        return array(
            "regPrice"=>0,
            "unitPrice"=>0,
            "discount"=>0,
            "memDiscount"=>0
        );
    }

    /**
      Add a discount notification
      @return None

      Optionally add an informational record
      to the transaction so a savings message
      appears on screen.
    */
    public function addDiscountLine()
    {

    }

    /**
      @return
       - True The item is on sale
       - False The item is not on sale
    */
    public function isSale()
    {
        return false;
    }

    /**
      @return
       - True The sale is only for members
       - False The sale is for everyone
    */
    public function isMemberOnly()
    {
        return false;
    }

    /**
      Alias for isMemberOnly()
    */
    public function isMemberSale()
    {
        return $this->isMemberOnly();
    }

    /**
      @return
       - True The sale is only for staff
       - False The sale is for everyone
    */
    public function isStaffOnly()
    {
        return false;
    }

    /**
      Alias for isStaffOnly()
    */
    public function isStaffSale()
    {
        return $this->isStaffOnly();
    }

    /* get discount object 

       CORE reserves values 0 through 63 in 
       DiscountType::$MAP for default options.

       Additional discounts provided by plugins
       can use values 64 through 127. Because
       the DiscountTypeClasses array is zero-indexed,
       subtract 64 as an offset  
    */
    public static function getObject($discounttype, $session)
    {
        $discounttype = MiscLib::nullwrap($discounttype);
        $dtClasses = CoreLocal::get("DiscountTypeClasses");
        if ($discounttype < 64 && isset(DiscountType::$MAP[$discounttype])) {
            $class = DiscountType::$MAP[$discounttype];
            return new $class($session);
        } elseif ($discounttype >= 64 && isset($dtClasses[($discounttype-64)])) {
            $class = $dtClasses[($discounttype)-64];
            return new $class($session);
        }

        // If the requested discounttype isn't available,
        // fallback to normal pricing. Debatable whether
        // this should be a hard error.
        return new NormalPricing($session);
    }

}

