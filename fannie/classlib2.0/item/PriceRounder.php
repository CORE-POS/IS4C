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

namespace COREPOS\Fannie\API\item {

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

      This function will always round UP so the return price
      ends in a 5 or a 9. Values ending in 05 and 09 are excluded
      so a price $x.00 through $x.14 will round up to $x.15.
    */
    public function round($price, $extra_parameters=array())
    {
        // operate in cents
        $price = floor($price * 100);

        // last digit will always round up to 9
        while ($price % 10 != 9) {
            $price++;
        }

	// adding a comment
        // mystery
        if ($price % 100 == 5 || $price % 100 == 9) {
            $price += 10;
        }
		
        // if price < $6.00 BUT > $1.00 and cents < 20, round down to nearest x.99
        if ($price < 600 && $price > 100) {
            if ($price % 100 <= 19){
                $price = $price - ($price % 100) - 1;
            }
        }

        
        // if price >= 6.00 and cents < 30, round down to nearest x.99
        if ($price >= 600) {
            if ($price % 100 <= 29){
                $price = $price - ($price % 100) - 1;
            }
        }
        
        // if price is >= $10.00 and the dollar amount is zero (20.99, 30.99, etc.) round down to nearest $xx.99
        if ($price >= 1000){
            if ( ($price - ($price % 100) ) % 1000 == 0 ){
                $price = $price - ($price % 100) - 1;
            }
        }
        
        return round($price/100.00, 2);
    }

    /**
      This is just an example of what a more complex rounding
      scheme might look like. Nothing should be calling this 
      method. 
    */
    private function example($price, $extra_parameters=array())
    {
        // operate in cents
        $price = floor($price * 100);

        // acceptable price ending digits vary
        // depending what range the price falls in
        $acceptable_endings = array(19, 29, 39, 49, 59, 69, 79, 89, 99);
        if ($price > 1000) {
            $acceptable_endings = array(99);
        } elseif ($price > 600) {
            $acceptable_endings = array(69, 99);
        } elseif ($price > 300) {
            $acceptable_endings = array(39, 69, 99);
        } elseif ($price > 100) {
            $acceptable_endings = array(19, 39, 69, 99);
        }

        // find the next higher price w/ correct ending
        $next = $price;
        while (!in_array($next % 100, $acceptable_endings)) {
            $next++;
        }
        // find the previous lower price w/ correct ending
        $prev = $price;
        while (!in_array($prev % 100, $acceptable_endings)) {
            $prev--;
        }

        // return whichever price point is closest
        // to the original price provided
        if (($next-$price) <= ($price-$prev)) {
            return round($next/100.00, 2);
        } else {
            return round($prev/100.00, 2);
        }
    }
}

}
