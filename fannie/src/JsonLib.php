<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op.

    This file is part of IT CORE.

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

/**
  @class JsonLib
  Functions for JSON support in PHP < 5.3
*/
class JsonLib {

/**
  Convert an array to a JSON string
  @param $arr an array of values
  @return A JSON string representing the array
*/
static public function array_to_json($arr){
	$ret = "[";
	for($i=0;$i<count($arr);$i++){
		if (isset($arr[$i]))
			$ret .= self::encode_value_json($arr[$i]).",";
		else {
			$ret = "";
			break; // not a numeric indexed array
		}
	}
	if (!empty($ret)){
		$ret = substr($ret,0,strlen($ret)-1)."]";
		return $ret;
	}

	$ret = "{";
	foreach($arr as $k=>$v){
		$ret .= '"'.$k.'":';
		$ret .= self::encode_value_json($v).",";
	}
	$ret = substr($ret,0,strlen($ret)-1)."}";
	return $ret;
}

/**
  Convert a variable to a JSON string
  @param $val a single variable
  @return A JSON string representing the variable
*/
static public function encode_value_json($val){
	if (is_array($val)) return self::array_to_json($val);
	if (is_numeric($val)) return '"'.$val.'"';
	if ($val === true) return 'true';
	if ($val === false) return 'false';
	else return '"'.addcslashes($val,"\\\"\r\n\t").'"';
}

/**
  Remove newlines, carriage returns, and tabs
  from the string (some browser don't like these
  in JSON strings)
  @str a string
  @return the modified string
*/
static public function fixstring($str){
	$str = str_replace("\n","",$str);
	$str = str_replace("\r","",$str);
	$str = str_replace("\t","",$str);
}

}

?>
