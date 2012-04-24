<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

    This file is part of Fannie.

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
  @class FileFormats
  Functions for manipulating common file formats
*/
class FileFormats extends FannieModule {

	public $required = True;

	public $description = "
	Provides functions for manipulating files of
	various formats.
	";

	function provides_functions(){
		return array(
		'csv_parser',
		'sanitize_xls_money',
		'array_to_csv',
		'array_to_xls',
		'html_to_array'
		);
	}
}

include(__DIR__.'/Excel/Spreadsheet_Excel_Reader.php');
include(__DIR__.'/xmlData.php');
@include_once('System.php');
if (class_exists('System'))
	include(__DIR__.'/Excel/Spreadsheet_Excel_Writer.php');

// function sys_get_temp_dir doesn't exist in PHP < 5.2.1
if ( !function_exists('sys_get_temp_dir')) {
  function sys_get_temp_dir() {
    if (!empty($_ENV['TMP'])) { return realpath($_ENV['TMP']); }
    if (!empty($_ENV['TMPDIR'])) { return realpath( $_ENV['TMPDIR']); }
    if (!empty($_ENV['TEMP'])) { return realpath( $_ENV['TEMP']); }
    $tempfile=tempnam(__FILE__,'');
    if (file_exists($tempfile)) {
      unlink($tempfile);
      return realpath(dirname($tempfile));
    }
    return null;
  }
}

/**
  @file
  @brief Provides file manipulation functions
*/

/**
  Convert a CSV line into an array
  @param $input the line
  @param $q the quoting character (default double quote)
  @param $s the separator (default comma)
  @return An array of field values
*/
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

/**
  Convert currency-formatted XLS numbers
  to sane values
  @param $val the XLS value
  @return a numeric value
*/
function sanitize_xls_money($val){
	$val = str_replace('$','',$val);
	$val = str_replace(',','',$val);
	$val = trim($val);
	if ($val=='-') $val = 0;
	return $val;
}

/**
  Convert array to CSV file
  @param $array two dimensional array
  @return string CSV contents
*/
function array_to_csv($array){
	$ret = "";

	foreach($array as $row){
		foreach($row as $col){
			$r = "\"";
			if ( ($pos = strpos($col,chr(0))) !== False){
				$col = substr($col,0,$pos);
			}
			$r .= str_replace("\"","",$col);
			$r .= "\",";
			$ret .= $r;
		}
		$ret = rtrim($ret,",")."\r\n";
	}

	return $ret;
}

/**
  Convert an array to XLS file
  @param $array a two dimensional array
  @return string XLS contents

  This function requires Pear and
  will return False if Pear is not
  found. 
*/
function array_to_xls($array){
	if (!class_exists('Spreadsheet_Excel_Writer'))
		return False;

	$fn = tempnam(sys_get_temp_dir(),"xlstemp");
	$workbook = new Spreadsheet_Excel_Writer($fn);
	$worksheet =& $workbook->addWorksheet();

	$format_bold =& $workbook->addFormat();
	$format_bold->setBold();

	for($i=0;$i<count($array);$i++){
		for($j=0;$j<count($array[$i]);$j++){
			if ( ($pos = strpos($array[$i][$j],chr(0))) !== False){
				$val = substr($array[$i][$j],0,$pos);
				$worksheet->write($i,$j,$val,$format_bold);
			}
			else 
				$worksheet->write($i,$j,$array[$i][$j]);
		}
	}

	$workbook->close();

	$ret = file_get_contents($fn);
	unlink($fn);
	return $ret;
}

/**
  Convert an HTML table to an array
  @param $str An HTML string
  @return Two dimensional array
*/
function html_to_array($str){

	$dom = new DOMDocument();
	@$dom->loadHTML($str); // ignore warning on [my] poorly formed html

	$tables = $dom->getElementsByTagName("table");
	$rows = $tables->item(0)->getElementsByTagName('tr');

	/* convert tables to 2-d array */
	$ret = array();
	$i = 0;
	foreach($rows as $row){
		$ret[$i] = array();
		foreach($row->childNodes as $node){
			if (!property_exists($node,'tagName')) continue;
			$val = trim($node->nodeValue,chr(160).chr(194));
			if ($node->tagName=="th") $val .= chr(0).'bold';

			if ($node->tagName=="th" || $node->tagName=="td")
				$ret[$i][] = $val;
		}
		$i++;
	}

	/* prepend any other lines to the array */
	$str = preg_replace("/<table.*?>.*<\/table>/s","",$str);
	$str = preg_replace("/<head.*?>.*<\/head>/s","",$str);
	$str = preg_replace("/<body.*?>/s","",$str);
	$str = str_replace("</body>","",$str);
	$str = str_replace("<html>","",$str);
	$str = str_replace("</html>","",$str);

	$extra = preg_split("/<br.*?>/s",$str);
	foreach(array_reverse($extra) as $e){
		if (!empty($e))
			array_unshift($ret,array($e));
	}

	return $ret;
}

?>
