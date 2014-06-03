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
class PriceMethod {

    public static $MAP = array(
        0   => 'BasicPM',
        1   => 'GroupPM',
        2   => 'QttyEnforcedGroupPM',
        3   => 'SplitABGroupPM',
        4   => 'ABGroupPM',
        5   => 'BigGroupPM',
        6   => 'MoreThanQttyPM',
    );

    var $savedRow;
    var $savedInfo;

    /**
      Add the item to the transaction
      @param $row A product table record
      @param $quantity Scan quantity
      @param $priceObj A DiscountType object 
      @return boolean success/failure
    */
    function addItem($row,$quantity,$priceObj){
        return true;
    }

    /**
      Information about error(s) adding the
      item to the transaction
      @return string message
    */
    function errorInfo(){
        return '';
    }
}

?>
