<?php

// takes a csv formated string and returns it's elements as an array
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
	$quoted = false;
	foreach ($input as $x){
		if ($x == $QUOTE_CHAR)
			$quoted = !$quoted;
		else if ($x == $SEPARATOR && !$quoted){
			$cur++;
			$ret[$cur] = "";
		}
		else if ($x != "\r" && $x != "\n")
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
