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

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	 4Sep12 Eric Lee Add Functions fix_text_for_db, fix_money_for_db
*/

// takes a csv formated string and returns its elements as an array
// optional args are quote character and separator character
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
		if ($x == "\r" || $x == "\n") continue;
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

function sanitize_xls_money($val){
	$val = str_replace('$','',$val);
	$val = str_replace(',','',$val);
	$val = trim($val);
	if ($val=='-') $val = 0;
	return $val;
}

/* Prepare text for writing to the database.
	Another approach is VALUES ($dbc->escape($foo), ... )
*/
function fix_text_for_db ($str) {

	// Remove apostrophes.  It may be better to double, i.e. escape, them.
	$str = preg_replace("/\'/","",$str);
	// Double, i.e. escape apostrophes
	//$str = preg_replace("/\'/","''",$str);

	return $str;

//fix_text_for_db
}

/* May be redundant with: csv_parser.sanitize_xls_money($val)
*/
function fix_money_for_db ($str) {

	$str = trim($str);
	$str = preg_replace("/\\\$/","",$str);
	$str = str_replace('$','',$str);
	$str = str_replace(",","",$str);
	if ($str == '-') $str = 0;

	return $str;

//fix_money_for_db
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
