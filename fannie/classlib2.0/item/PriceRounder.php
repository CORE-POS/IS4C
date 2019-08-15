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
  @class PriceRounder
  API class to define rounding rules
  used when setting prices
*/
class PriceRounder 
{
    /**
      The round function takes a numeric price and returns
      it with the correct rounding rules applied
      @param $price [decimal] number to be rounded
      @param $extra_parameters [array] optional extra arguments
        if pricing rules are extremely complex
      @return [decimal] rounded price

      This function will always round so the return price
      ends in a 9.
    */
    public function round($price, $extra_parameters=array())
    {
        $wholeP = floor($price);
        $fractionP = $price - $wholeP;

        // acceptible price endings by $endingCaps
        $endings = array(
            0 => array(0.29, 0.39, 0.49, 0.69, 0.79, 0.89, 0.99),
            1 => array(0.19, 0.39, 0.49, 0.69, 0.89, 0.99),
            2 => array(0.39, 0.69, 0.99),
            3 => array(0.69, 0.99),
            4 => array(0.99),
        );
        $endingCaps = array(0.99, 2.99, 5.99, 9.99, 9999.00);
        // special round defines when to round down by $endingCaps
        $specialRound = array(
            0 => 0,
            1 => 0.16,
            2 => 0.16,
            3 => 0.30,
        );

        $end = substr($price, -2);
        foreach ($endingCaps as $level => $cap) {
            if ($price <= $cap) {
                foreach ($endings as $k => $endArray) {
                    if ($k == $level ) {
                        foreach ($endArray as $end) {
                            if ($fractionP < $end) {
                                if ($wholeP >= 10) {
                                    // whole >= 10
                                    if ($wholeP % 10 == 0 || $fractionP == 0) {
                                        // whole == 10, round down to 9.99
                                        $wholeP--;
                                        $end = 0.99;
                                    } else {
                                        // whole >= 11, round up to whole.99
                                        $end = 0.99;
                                    }
                                } elseif ($fractionP <= $specialRound[$level]) {
                                    // special round down to nearest whole.99
                                    $wholeP--;
                                    $end = 0.99;
                                }
                                // don't end prices like 30.99, 40.99, round down 
                                if ($wholeP % 10 == 0 && $end == 0.99) {
                                    $wholeP--;
                                }
                                $price = $wholeP + $end;

                                return $price;
                            }
                        }
                    }
                }
            }
        }
        if ($wholeP % 10 == 0 && $end == 0.99) {
            $wholeP--;
        }
        $price = $wholeP + $end;
        return substr($price, 0, 5);
    }

}
