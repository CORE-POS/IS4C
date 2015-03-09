<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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

function pricePerOunce($price,$sizeStr){
    $num = "";
    $unit = "";
    $mult = 1;
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

    $unit = ltrim($unit);
    $unit = strtoupper($unit);
    if ($num == "") $num = 1;
    $num = (float)$num;
    $num = $num*$mult;

    switch($unit){
    case '#':
    case 'LB':
    case 'LBS': 
        return round($price/($num*16),3)."/OZ";
    case 'ML':
        return round($price/($num*0.034),3)."/OZ";
    case 'OZ':
    case 'Z':
        return round($price/$num,3)."/OZ";
    case 'PINT':
    case 'PINTS':
        return round($price/($num*16),3)."/OZ";
    case 'GR':
    case 'GRAM':
    case 'GM':
    case 'GRM':
    case 'G':
        return round($price/($num*0.035),3)."/OZ";
    case 'LTR':
        return round($price/($num*33.814),3)."/OZ";
    case 'GAL':
        return round($price/($num*128),3)."/OZ";
    }   
    return "";
}

?>
