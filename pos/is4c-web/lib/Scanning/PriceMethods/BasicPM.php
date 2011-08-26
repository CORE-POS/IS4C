<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

    This file is part of IS4C.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/* Basic PriceMethod module
   
   This module just adds the item with the given
   quantity and price/discount information

   Classically, this is pricemethod=0
*/
$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

if (!class_exists('PriceMethod')) include($IS4C_PATH.'lib/Scanning/PriceMethod.php');
if (!function_exists('addItem')) include($IS4C_PATH.'lib/additem.php');
if (!function_exists('truncate2')) include($IS4C_PATH.'lib/lib.php');

class BasicPM extends PriceMethod {

	function addItem($row,$quantity,$priceObj){
		if ($quantity == 0) return false;

		$pricing = $priceObj->priceInfo($row,$quantity);

		addItem($row['upc'],
			$row['description'],
			'I',
			' ',
			' ',
			$row['department'],
			$quantity,
			$pricing['unitPrice'],
			truncate2($pricing['unitPrice'] * $quantity),
			$pricing['regPrice'],
			$row['scale'],
			$row['tax'],
			$row['foodstamp'],
			$pricing['discount'],
			$pricing['memDiscount'],
			$row['discount'],
			$row['discounttype'],
			$quantity,
			$row['pricemethod'],
			$row['quantity'],
			$row['groupprice'],
			$row['mixmatchcode'],
			0,
			0,
			(isset($row['cost'])?$row['cost']*$quantity:0.00),
			(isset($row['numflag'])?$row['numflag']:0),
			(isset($row['charflag'])?$row['charflag']:'')
		);
	}
}

?>
