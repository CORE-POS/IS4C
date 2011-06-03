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

// takes a csv formated string and returns its elements as an array
// optional args are quote character and separator character
// trim new lines / carriage returns off the end of the string first if
// you don't want them
function csv_parser($input,$q="\"",$s=","){
	$QUOTE_CHAR = $q;
	$SEPARATOR = $s;

	// break string into array so PHP's foreach can handle it
	$input = preg_split('//', $input, -1, PREG_SPLIT_NO_EMPTY);

	$ret = array();
	$cur = 0;
	$ret[$cur] = "";
	$quoted = false;
	foreach ($input as $x){
		if ($x == $QUOTE_CHAR)
			$quoted = !$quoted;
		else if ($x == $SEPARATOR && !$quoted){
			$cur++;
			$ret[$cur] = "";
		}
		else
			$ret[$cur] .= $x;
	}
	return $ret;
}

// testing
/*
$test = "asdf,1.0,\"asdf\",\"a,s,d,f\",5";
echo "INPUT<br />";
echo $test."<br /><br />";
echo "OUTPUT<br />";
var_dump(csv_parser($test));
*/

?>
