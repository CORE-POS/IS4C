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

namespace COREPOS\pos\lib;

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
static public function arrayToJson($arr)
{
    $ret = "[";
    for($i=0;$i<count($arr);$i++) {
        if (isset($arr[$i])) {
            $ret .= self::encodeValueJson($arr[$i]).",";
        } else {
            $ret = "";
            break; // not a numeric indexed array
        }
    }
    if (!empty($ret)) {
        $ret = substr($ret,0,strlen($ret)-1)."]";
        return $ret;
    }

    $ret = "{";
    foreach($arr as $k=>$v) {
        $ret .= '"'.$k.'":';
        $ret .= self::encodeValueJson($v).",";
    }
    $ret = substr($ret,0,strlen($ret)-1)."}";

    return $ret;
}

static public function array_to_json($arr)
{
    return self::arrayToJson($arr);
}

/**
  Convert a variable to a JSON string
  @param $val a single variable
  @return A JSON string representing the variable
*/
static public function encodeValueJson($val)
{
    if (is_array($val)) {
        return self::array_to_json($val);
    }
    if (is_numeric($val)) {
        return ltrim($val,'0');
    }
    if ($val === true) {
        return 'true';
    }
    if ($val === false) {
        return 'false';
    }

    return '"'.addcslashes($val,"\\\"\r\n\t").'"';
}

/**
  Remove newlines, carriage returns, and tabs
  from the string (some browser don't like these
  in JSON strings)
  @str a string
  @return the modified string
*/
static public function fixstring($str)
{
    $str = str_replace("\n","",$str);
    $str = str_replace("\r","",$str);
    $str = str_replace("\t","",$str);
}

static public function prettyJSON($json)
{
    $result= '';
    $pos = 0;
    $strLen= strlen($json);
    $indentStr = '    ';
    $newLine = "\n";
    $prevChar= '';
    $outOfQuotes = true;

    for ($i=0; $i<=$strLen; $i++) {
        // Grab the next character in the string.
        $char = substr($json, $i, 1);

        // Are we inside a quoted string?
        if ($char == '"' && $prevChar != '\\') {
            $outOfQuotes = !$outOfQuotes;
        // If this character is the end of an element, 
        // output a new line and indent the next line.
        } else if (($char == '}' || $char == ']') && $outOfQuotes) {
            $result .= $newLine;
            $pos--;
            for ($j=0; $j<$pos; $j++) {
                $result .= $indentStr;
            }
        }

        // Add the character to the result string.
        $result .= $char;

        // If the last character was the beginning of an element, 
        // output a new line and indent the next line.
        if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
            $result .= $newLine;
            if ($char == '{' || $char == '[') {
                $pos ++;
            }

            for ($j = 0; $j < $pos; $j++) {
                $result .= $indentStr;
            }
        }

        $prevChar = $char;
    }

    return $result;
}

}

