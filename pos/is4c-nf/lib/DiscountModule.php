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
    protected $my_discount = 0;
    public $name = 'custdata';

    /**
      Add or update a discount module in the
      current transaction. Automatically 
      subtotals if the discount changes.
    */
    public static function updateDiscount(DiscountModule $mod, $do_subtotal=true)
    {
        $changed = true;
        // serialize/unserialize before saving to avoid
        // auto-session errors w/ undefined classes
        $current_discounts = unserialize(CoreLocal::get('CurrentDiscounts'));
        if (!is_array($current_discounts)) {
            $current_discounts = array();
        }

        /**
          Examine current discounts to see if this
          one has already applied
        */
        foreach ($current_discounts as $class => $obj) {
            if ($mod->name == $obj->name) {
                if ($mod->percentage() == $obj->percentage()) {
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
            $current_discounts[$mod->name] = $mod;
            $old_effective_discount = CoreLocal::get('percentDiscount');
            $new_effective_discount = 0;
            foreach ($current_discounts as $obj) {
                if (CoreLocal::get('NonStackingDiscounts') && $obj->percentage() > $new_effective_discount) {
                    $new_effective_discount = $obj->percentage();
                } else {
                    $new_effective_discount += $obj->percentage();
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
                if ($do_subtotal) {
                    PrehLib::ttl();
                }
            }

            // serialize/unserialize before saving to avoid
            // auto-session errors w/ undefined classes
            CoreLocal::set('CurrentDiscounts', serialize($current_discounts));
        }
    }

    /**
      Reset all discounts
    */
    public static function transReset()
    {
        CoreLocal::set('CurrentDiscounts', serialize(array()));
    }

    /**
      Add a log record w/ upc DISCLINEITEM for
      * Each discount in stacking mode
      * The applicable discount in non-stacking mode
    */
    public static function lineItems()
    {
        $current_discounts = unserialize(CoreLocal::get('CurrentDiscounts'));
        if (!is_array($current_discounts)) {
            $current_discounts = array();
        }
        if (CoreLocal::get('NonStackingDiscounts')) {
            $applies = null;
            foreach ($current_discounts as $name => $obj) {
                if ($applies == null) {
                    $applies = $name;
                } elseif ($current_discounts[$name]->percentage() < $obj->percentage()) {
                    $applies = $name;
                }
            }
            if ($applies != null && isset($current_discounts[$applies])) {
                TransRecord::addLogRecord(array(
                    'upc' => 'DISCLINEITEM',
                    'description' => $applies,
                    'amount1' => $current_discounts[$applies]->percentage(),
                    'amount2' => ($current_discounts[$applies]->percentage()/100.00) * CoreLocal::get('discountableTotal'),
                ));
            }
        } else {
            foreach ($current_discounts as $name => $obj) {
                TransRecord::addLogRecord(array(
                    'upc' => 'DISCLINEITEM',
                    'description' => $name,
                    'amount1' => $obj->percentage(),
                    'amount2' => $obj->calculate(CoreLocal::get('discountableTotal')),
                ));
            }
        }
    }

    public function __construct($percent, $name='custdata')
    {
        $this->my_discount = $percent;
        $this->name = $name;
    }

	/**
	  Calculate the discount based on current
	  transaction state
	  @return double discount amount

	  Note return value should be positive unless
	  you're doing something odd
	*/
	public function calculate($discountable_total=0)
    {
        if ($discountable_total == 0) {
            $discountable_total = CoreLocal::get('discountableTotal');
        }

		return MiscLib::truncate2(($this->my_discount/100.00) * $discountable_total);
	}

	/**
	  Decide what percent discount to apply to this
	  transaction.
	  @return int percentage (i.e., 5 == 5%)
	*/
	public function percentage()
    {
		return $this->my_discount;
	}
}

