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

namespace COREPOS\pos\lib;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\PrehLib;
use COREPOS\pos\lib\TransRecord;
use \CoreLocal;

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
    protected $myDiscount = 0;
    public $name = 'custdata';

    /**
      Add or update a discount module in the
      current transaction. Automatically 
      subtotals if the discount changes.
    */
    public static function updateDiscount(DiscountModule $mod, $doSubtotal=true)
    {
        $changed = true;
        // serialize/unserialize before saving to avoid
        // auto-session errors w/ undefined classes
        $currentDiscounts = unserialize(CoreLocal::get('CurrentDiscounts'));
        if (!is_array($currentDiscounts)) {
            $currentDiscounts = array();
        }

        /**
          Examine current discounts to see if this
          one has already applied
        */
        foreach ($currentDiscounts as $obj) {
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
            $currentDiscounts[$mod->name] = $mod;
            $oldEffectiveDiscount = CoreLocal::get('percentDiscount');
            $newEffectiveDiscount = 0;
            foreach ($currentDiscounts as $obj) {
                if (CoreLocal::get('NonStackingDiscounts') && $obj->percentage() > $newEffectiveDiscount) {
                    $newEffectiveDiscount = $obj->percentage();
                } elseif (CoreLocal::get('NonStackingDiscounts') == 0) {
                    $newEffectiveDiscount += $obj->percentage();
                }
            }

            /**
              When discount changes:
              1. Update the session value
              2. Update the localtemptrans.percentDiscount value
              3. Subtotal the transaction
            */
            if ($oldEffectiveDiscount != $newEffectiveDiscount) {
                CoreLocal::set('percentDiscount', $newEffectiveDiscount);
                $dbc = Database::tDataConnect();
                $dbc->query('UPDATE localtemptrans SET percentDiscount=' . ((int)$newEffectiveDiscount));
                if ($doSubtotal) {
                    PrehLib::ttl();
                }
            }

            // serialize/unserialize before saving to avoid
            // auto-session errors w/ undefined classes
            CoreLocal::set('CurrentDiscounts', serialize($currentDiscounts));
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
        $currentDiscounts = unserialize(CoreLocal::get('CurrentDiscounts'));
        if (!is_array($currentDiscounts)) {
            $currentDiscounts = array();
        }
        if (CoreLocal::get('NonStackingDiscounts')) {
            $applies = null;
            foreach ($currentDiscounts as $name => $obj) {
                if ($applies == null) {
                    $applies = $name;
                } elseif ($currentDiscounts[$name]->percentage() < $obj->percentage()) {
                    $applies = $name;
                }
            }
            if ($applies != null && isset($currentDiscounts[$applies])) {
                TransRecord::addLogRecord(array(
                    'upc' => 'DISCLINEITEM',
                    'description' => $applies,
                    'amount1' => $currentDiscounts[$applies]->percentage(),
                    'amount2' => ($currentDiscounts[$applies]->percentage()/100.00) * CoreLocal::get('discountableTotal'),
                ));
            }
        } else {
            foreach ($currentDiscounts as $name => $obj) {
                TransRecord::addLogRecord(array(
                    'upc' => 'DISCLINEITEM',
                    'description' => $name,
                    'amount1' => $obj->percentage(),
                ));
            }
        }
    }

    public function __construct($percent, $name='custdata')
    {
        $this->myDiscount = $percent;
        $this->name = $name;
    }

    /**
      Decide what percent discount to apply to this
      transaction.
      @return int percentage (i.e., 5 == 5%)
    */
    public function percentage()
    {
        return $this->myDiscount;
    }
}

