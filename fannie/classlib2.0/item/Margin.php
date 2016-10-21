<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op, Duluth, MN

    This file is part of CORE-POS.

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

namespace COREPOS\Fannie\API\item;

/**
  @class Margin
  API class with margin related methods
*/
class Margin
{
    /**
      Calculate effective cost of an item
      @param $base_cost [number] the cost of the item
      @param $discount_percent [number] a percent discount off the listed cost
      @param $shipping_percent [number] a percent markup for shipping
      @return [number] adjusted cost

      Both percent parameters default to zero.
      Percents should be expressed as decimals - i.e., 0.10 means 10%
    */
    public static function adjustedCost($base_cost, $discount_percent=0, $shipping_percent=0)
    {
        $base_cost = (float)$base_cost;
        $base_cost *= (1 - $discount_percent);
        $base_cost *= (1 + $shipping_percent);

        return $base_cost;
    }

    /**
      Calculate effective cost of an item
      @param $base_cost [expression] the cost of the item
      @param $discount_percent [expression] a percent discount off the listed cost
      @param $shipping_percent [expression] a percent markup for shipping
      @return [string] SQL for calculating adjusted cost

      Intended for building queries. User input should not be part
      of any of the parameters
    */
    public static function adjustedCostSQL($base_cost, $discount_percent=0, $shipping_percent=0)
    {
        return "( ({$base_cost}) * (1.0 - ({$discount_percent})) * (1.0 + ({$shipping_percent})) )";
    }

    /**
      Calculate margin
      @param $cost [number] item cost
      @param $price [number] item price
      @param $format [array] (multiplier, rounding)
      @return [number] margin

      By default this returns a decimal (i.e. 0.10 for 10%)
      without any rounding. Passing in format (100, 2)
      will give the return value 10.00 for 10%.
    */
    public static function toMargin($cost, $price, $format=array(1,false))
    {
        if ($price == 0) {
            return 0;
        }

        $margin = ($price - $cost) / ((float)$price);

        $margin *= $format[0];
        if (is_int($format[1])) {
            $margin = round($margin, $format[1]);
        }

        return $margin;
    }

    /**
      Calculate margin
      @param $cost [number] item cost
      @param $price [number] item price
      @return [string] SQL for calculating adjusted cost

      Intended for building queries. User input should not be part
      of any of the parameters
    */
    public static function toMarginSQL($cost, $price)
    {
        if (is_numeric($price) && $price == 0) {
            return '(0)';
        } else {
            return "( (({$price}) - ({$cost})) / ({$price}) )";
        }
    }

    /**
      Calculate price
      @param $cost [number] item cost
      @param $margin [number] desired margin
      @return [number] price

      Margin should be a decimal (i.e. 0.10 for 10%)
    */
    public static function toPrice($cost, $margin)
    {
        if ($margin == 1) {
            return $cost;
        }

        $price = ((float)$cost) / (1 - $margin);

        return $price;
    }

    /**
      Calculate price
      @param $cost [number] item cost
      @param $margin [number] desired margin
      @return [string] SQL for calculating adjusted cost

      Intended for building queries. User input should not be part
      of any of the parameters
    */
    public static function toPriceSQL($cost, $margin)
    {
        if ($margin == 1) {
            return '(' . $cost . ')';
        } else {
            return "( ({$cost}) / (1.0 - ({$margin})) )";
        }
    }
}

