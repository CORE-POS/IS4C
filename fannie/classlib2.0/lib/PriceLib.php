<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

namespace COREPOS\Fannie\API\lib;

/**
  @class PriceLib
*/
class PriceLib 
{

    public static function pricePerUnit($price,$sizeStr)
    {
        $country = \FannieConfig::factory()->get('COUNTRY', 'US');

        $num = "";
        $unit = "";
        $mult = 1;
        $inNum = 1;
        for ($i=0; $i < strlen($sizeStr); $i++) {
            if ($inNum == 1) {
                if (is_numeric($sizeStr[$i]) or $sizeStr[$i] == ".") {
                    $num .= $sizeStr[$i];
                } else if ($sizeStr[$i] == "/" or $sizeStr[$i] == "-") {
                    $mult = $num;
                    $num = "";
                } else {
                    $inNum = 0;
                    $unit .= $sizeStr[$i];
                }
            } else {
                $unit .= $sizeStr[$i];
            }
        }

        $unit = ltrim($unit);
        $unit = strtoupper($unit);
        if (strpos($unit,"FL") !== False) {
            $unit = "FLOZ";
        }
        if ($num == "") {
            $num = 1;
        }
        $num = (float)$num;
        $num = $num*$mult;
        if ($num == 0) {
            return '';
        }

        switch($unit) {
            case '#':
            case 'LB':
            case 'LBS':    
                if ($country == "US") {
                    return round($price/($num*16),3)."/OZ";
                } else {
                    return round($price/($num*453.59),3)."/G";
                }
            case 'ML':
                if ($country == "US") {
                    return round($price/($num*0.034),3)."/OZ";
                } else {
                    return round($price/$num,3)."/ML";
                }
            case 'FLOZ':
                if ( $country == 'US' ) {
                    return round($price/$num,3)."/OZ";
                } else {
                    return round($price/($num*29.5735),3)."/ML"; 
                }
            case 'OZ':
            case 'Z':
                if ( $country == 'US' ) {
                    return round($price/$num,3)."/OZ";
                } else {
                    return round($price/($num*28.35),3)."/G"; 
                }
            case 'PINT':
            case 'PINTS':
                if ($country == "US") {
                    return round($price/($num*16),3)."/OZ";
                } else {
                    return round($price/($num*473.18),3)."/ML";
                }
            case 'GR':
            case 'GRAM':
            case 'GM':
            case 'GRM':
            case 'G':
                if ($country == "US"){
                    return round($price/($num*0.035),3)."/OZ";
                } else {
                    return round($price/$num,3)."/G";
                }
            case 'LTR':
            case 'L':
                if ($country == "US"){
                    return round($price/($num*33.814),3)."/OZ";
                } else {
                    return round($price/1000,3)."/ML";
                }
            case 'GAL':
                if ($country == "US") {
                    return round($price/($num*128),3)."/OZ";
                } else {
                    return round($price/($num*3785.41),3)."/ML";
                }
            default:
                return round($price/$num,3)."/".$unit;
        }

        return "";
    }
}

