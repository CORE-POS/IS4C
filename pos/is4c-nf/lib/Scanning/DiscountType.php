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

/**
  @class DiscountType
  Base module for computing sale prices
*/
class DiscountType 
{

    static public $MAP = array(
        0   => 'NormalPricing',
        1   => 'EveryoneSale',
        2   => 'MemberSale',
        3   => 'PercentMemSale',
        4   => 'StaffSale',
        5   => 'SlidingMemSale',
        6   => 'CasePriceDiscount',
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
    public function priceInfo($row,$quantity=1)
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

}

