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

namespace COREPOS\pos\lib\TotalActions;

/**
  @class TotalAction
  Base class for applying actions to
  subtotalling
*/
class TotalAction 
{
    private static $builtin = array(
        'ArOverpayAction',
        'AutoCoupon',
        'MemTotalAction',
        'OntarioMealTaxRebateAction',
        'TotalAction',
    );

    public static function factory($class)
    {
        if ($class != '' && in_array($class, self::$builtin)) {
            $class = 'COREPOS\\pos\\lib\\TotalActions\\' . $class;
            return new $class();
        } elseif ($class != '' && class_exists($class)) {
            return new $class();
        }

        return new self();
    }

    /**
      Apply action
      @return [boolean] true if the action
        completes successfully (or is not
        necessary at all) or [string] url
        to redirect to another page for
        further decisions/input.
    */
    public function apply()
    {
        return true;
    }
}

