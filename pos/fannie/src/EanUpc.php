<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

    This file is part of Fannie.

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

*******************************************************************************/

function Ean13CheckDigit($str){
	$ean = str_pad($str,12,'0',STR_PAD_LEFT);

	$evens = 0;
	$odds = 0;
	for($i=0;$i<12;$i++){
		if ($i%2 == 0) $evens += (int)$ean[$i];
		else $odds += (int)$ean[$i];
	}
	$odds *= 3;
	
	$total = $evens + $odds;
	$chk = (10 - ($total%10)) % 10;

	return $ean.$chk;
}

function UpcACheckDigit($str){
	$upc = str_pad($str,11,'0',STR_PAD_LEFT);

	$evens = 0;
	$odds = 0;
	for ($i=0;$i<11;$i++){
		if($i%2==0) $odds += (int)$upc[$i];
		else $evens += (int)$upc[$i];
	}
	$odds *= 3;

	$total = $evens+$odds;
	$chk = (10 - ($total%10)) % 10;

	return $upc.$chk;
}

function Normalize13($str){
	$str = ltrim($str,'0');
	if (strlen($str) <= 11) return '0'.UpcACheckDigit($str);
	else return Ean13CheckDigit($str);
}

?>
