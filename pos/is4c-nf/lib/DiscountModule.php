<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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
  @class DiscountModule
  Calculate a per-transaction discount

  This module is called in Database::getsubtotals()
  and the value it calculates will be stored in
  $CORE_LOCAL as 'transDiscount'. The default version
  simply returns the value calculated by the 
  translog.subtotals view.
*/
class DiscountModule 
{

	/**
	  Calculate the discount based on current
	  transaction state
	  @return double discount amount

	  Note return value should be positive unless
	  you're doing something odd
	*/
	public function calculate()
    {
		global $CORE_LOCAL;
		$subtotalsDiscount = $CORE_LOCAL->get('transDiscount');
		if ($subtotalsDiscount === '') {
			$subtotalsDiscount = 0.00;
        }

		return $subtotalsDiscount;
	}

	/**
	  Decide what percent discount to apply to this
	  transaction.
	  @param $custdata_discount value in custdata.Discount
	  @return int percentage (i.e., 5 == 5%)
	*/
	public function percentage($custdata_discount=0)
    {
		return $custdata_discount;
	}
}

