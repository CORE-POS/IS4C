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
use COREPOS\pos\lib\Scanning\PriceMethods\BasicPM;
use \CoreLocal;

/**
  @class PriceMethod
  Base class for handling different price methods

  These modules add an item to the transaction

  The default case is to add an item with the
  specified price (BasicPM) but other methods
  with group deals, buy-one-get-one, etc can
  get really convoluted. UPC parsing is easier
  to follow with that code relegated to a module.

  Stores can also swap out modules as needed and
  rearrange them so products.pricemethod=X doesn't
  need to mean the same thing at every store.
*/
class PriceMethod 
{
    public static $MAP = array(
        0   => 'COREPOS\\pos\\lib\\Scanning\\PriceMethods\\BasicPM',
        1   => 'COREPOS\\pos\\lib\\Scanning\\PriceMethods\\GroupPM',
        2   => 'COREPOS\\pos\\lib\\Scanning\\PriceMethods\\QttyEnforcedGroupPM',
        3   => 'COREPOS\\pos\\lib\\Scanning\\PriceMethods\\SplitABGroupPM',
        4   => 'COREPOS\\pos\\lib\\Scanning\\PriceMethods\\ABGroupPM',
        5   => 'COREPOS\\pos\\lib\\Scanning\\PriceMethods\\BigGroupPM',
        6   => 'COREPOS\\pos\\lib\\Scanning\\PriceMethods\\MoreThanQttyPM',
    );

    protected $savedRow;
    protected $savedInfo;
    protected $session;

    public function __construct($session)
    {
        $this->session = $session;
    }

    /**
      Add the item to the transaction
      @param $row A product table record
      @param $quantity Scan quantity
      @param $priceObj A DiscountType object 
      @return boolean success/failure
    */
    public function addItem(array $row, $quantity, $priceObj)
    {
        return true;
    }

    /**
      Information about error(s) adding the
      item to the transaction
      @return string message
    */
    public function errorInfo()
    {
        return '';
    }

    /* get price method object  & add item
    
       CORE reserves values 0 through 99 in 
       PriceMethod::$MAP for default methods.

       Additional methods provided by plugins
       can use values 100 and up. Because
       the PriceMethodClasses array is zero-indexed,
       subtract 100 as an offset  
    */
    public static function getObject($pricemethod, $session)
    {
        $pmClasses = CoreLocal::get("PriceMethodClasses");
        $class = 'COREPOS\\pos\\lib\\Scanning\\PriceMethods\\BasicPM';
        if ($pricemethod < 100 && isset(PriceMethod::$MAP[$pricemethod])) {
            $class = PriceMethod::$MAP[$pricemethod];
        } elseif ($pricemethod >= 100 && isset($pmClasses[($pricemethod-100)])) {
            $class = $pmClasses[($pricemethod-100)];
        }

        return new $class($session);
    }
}

