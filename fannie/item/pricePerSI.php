<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op
    Copyright 2013 West End Food Co-op, Toronto

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/


/* Express price in dollars per gram or millilitre: 0.0000/G 0.0000/ML
 * Convert litre and kg.
 * Convert US measures lb, pound, oz, fl. oz, qt, gallon to SI equivalents.
 *  http://en.wikipedia.org/wiki/United_States_customary_units
*/
function pricePerSI($price,$sizeStr,$size='',$unitofmeasure='',$systemOut=''){

 global $FANNIE_POUNDS_AS_POUNDS;

    $num = "";
    $unit = "";
    $mult = 1;

    if ( is_numeric($size) && $unitofmeasure != '' ) {
        $num = $size;
        $unit = $unitofmeasure;
    }
    /* Parse from $sizeStr . E.g.
     * 4ct       5 ea
     * 200 g    10oz
     * 1.2kg     2 lb.
     * 1 l       1 qt
     * 250ml     8 fl oz
     * Multiples, meaning the package contains n of the named size.
     * 3/250 ml  2/1qt
     * 4-100 g   4-3.25oz
    */
    else {
    $inNum = 1;
        for ($i=0; $i < strlen($sizeStr); $i++){
            if ($inNum == 1){
                if (is_numeric($sizeStr[$i]) or $sizeStr[$i] == ".")
                    $num .= $sizeStr[$i];
                else if ($sizeStr[$i] == "/" or $sizeStr[$i] == "-"){
                    $mult = $num;
                    $num = "";
                }
                else {
                    $inNum = 0;
                    $unit .= $sizeStr[$i];
                }
            }
            else
                $unit .= $sizeStr[$i];
        }
    }

    $unit = ltrim($unit);
    $unit = rtrim($unit,'.');
    $unit = strtoupper($unit);
    if ( strpos($unit,"FL") !== False )
        $unit = "FLOZ";

    if ($num == "") $num = 1;
    if ( preg_match("/\bKG\b|\bKILO\b/", $unit) ) {
        $num = $num * 1000;
        $unit = 'G';
    }
    elseif ( preg_match("/\bL\b|\bLITRE\b|\bLTR\b/", $unit) ) {
        $num = $num * 1000;
        $unit = 'ML';
    }
    $num = (float)$num;
    $num = $num*$mult;

    $decim = 3;

    switch($unit){
        case 'CT':
        case 'EA':
        case 'EACH':
            return round($price/$num,$decim)."/EA";
        case 'G':
        case 'GR':
        case 'GRAM':
        case 'GM':
        case 'GRM':
            return round($price/$num,$decim)."/G";
            //return round($price/($num*0.035),$decim)."/OZ";
        case '#':
        case 'LB':
        case 'LBS': 
            $num = $num * 16;
            if ( isset($FANNIE_POUNDS_AS_POUNDS) && $FANNIE_POUNDS_AS_POUNDS == 1 )
                return round($price/$num,$decim)."/OZ";
            else
                return round($price/($num*28.349523125),$decim)."/G";
            //return round($price/($num*16),$decim)."/OZ";
        case 'OZ':
        case 'Z':
            return round($price/($num*28.349523125),$decim)."/G";
            //return round($price/$num,$decim)."/OZ";
        // Liquid
        case 'ML':
            return round($price/$num,$decim)."/ML";
            //return round($price/($num*0.034),$decim)."/OZ";
        case 'FLOZ':
            return round($price/($num*29.5735295625),$decim)."/ML";
            1;
        case 'PINT':
        case 'PINTS':
            $num = $num * 16;
            return round($price/($num*29.5735295625),$decim)."/ML";
            //return round($price/($num*16),$decim)."/OZ";
        case 'QT':
            $num = $num * 32;
            return round($price/($num*29.5735295625),$decim)."/ML";
            //return round($price/($num*128),$decim)."/OZ";
        case 'GAL':
            $num = $num * 128;
            return round($price/($num*29.5735295625),$decim)."/ML";
            //return round($price/($num*128),$decim)."/OZ";
    }   

    return "";

}

?>
