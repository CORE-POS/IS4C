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
  session as 'transDiscount'. The default version
  simply returns the value calculated by the 
  translog.subtotals view.
*/
class DiscountModule 
{
    private static $current_discounts = array();

    /**
      Add or update a discount module in the
      current transaction. Automatically 
      subtotals if the discount changes.
    */
    public static function updateDiscount(DiscountModule $mod)
    {
        $reflector = new ReflectionClass($mod);
        $changed = true;
        /**
          Examine current discounts to see if this
          one has already applied
        */
        foreach (self::$current_discounts as $class => $obj) {
            if ($reflector->name == $class) {
                if ($mod->percent() == $obj->percent()) {
                    $changed = false;
                }
                break;
            }
        }

        if ($changed) {
            /**
              Add object to the list of active discounts
              Then loop through to see whether it changes
              the effective discount with stacking settings
              taken into account
            */
            self::$current_discounts[$reflector->name] = $mod;
            $old_effective_discount = CoreLocal::get('percentDiscount');
            $new_effective_discount = 0;
            foreach (self::$current_discounts as $obj) {
                if (CoreLocal::get('NonStackingDiscounts') && $obj->percent() > $new_effective_discount) {
                    $new_effective_discount = $obj->percent();
                } else {
                    $new_effective_discount += $obj->percent();
                }
            }

            /**
              When discount changes:
              1. Update the session value
              2. Update the localtemptrans.percentDiscount value
              3. Subtotal the transaction
            */
            if ($old_effective_discount != $new_effective_discount) {
                CoreLocal::set('percentDiscount', $new_effective_discount);
                $dbc = Database::tDataConnect();
                $dbc->query('UPDATE localtemptrans SET percentDiscount=' . ((int)$new_effective_discount));
                PrehLib::ttl();
            }
        }
    }

    /**
      Reset all discounts
    */
    public static function transReset()
    {
        self::$current_discounts = array();
    }

    /**
      Add a log record w/ upc DISCLINEITEM for
      * Each discount in stacking mode
      * The applicable discount in non-stacking mode
    */
    public static function lineItems()
    {
        if (CoreLocal::get('NonStackingDiscounts')) {
            $applies = null;
            foreach (self::$current_discounts as $class => $obj) {
                if ($applies == null) {
                    $applies = $class;
                } elseif (self::$current_discounts[$applies]->percent() < $obj->percent()) {
                    $applies = $class;
                }
            }
            if ($applies != null && isset(self::$current_discounts[$applies])) {
                TransRecord::addLogRecord(array(
                    'upc' => 'DISCLINEITEM',
                    'description' => $applies,
                    'amount1' => self::$current_discounts[$applies]->percent(),
                    'amount2' => (self::$current_discounts[$applies]->percent()/100.00) * CoreLocal::get('dicountableTotal'),
                ));
            }
        } else {
            foreach (self::$current_discounts as $class => $obj) {
                TransRecord::addLogRecord(array(
                    'upc' => 'DISCLINEITEM',
                    'description' => $class,
                    'amount1' => $obj->percent(),
                    'amount2' => ($obj->percent()/100.00) * CoreLocal::get('dicountableTotal'),
                ));
            }
        }
    }

	/**
	  Calculate the discount based on current
	  transaction state
	  @return double discount amount

	  Note return value should be positive unless
	  you're doing something odd
	*/
	public function calculate()
    {
		$subtotalsDiscount = CoreLocal::get('transDiscount');
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

