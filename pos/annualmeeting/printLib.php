<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

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

if (!class_exists("ESCPOSPrintHandler")) include_once("PrintHandlers/ESCPOSPrintHandler.class.php");
if (!class_exists("Bitmap")) include_once("Bitmap4.class.php");
define('PRINTER_OUT_PORT','LPT1:');
//define('PRINTER_OUT_PORT','fake-receipt.txt');


// --------------------------------------------------------------
function build_time($timestamp) {

	return strftime("%m/%d/%y %I:%M %p", $timestamp);
}
// --------------------------------------------------------------
function centerString($text) {

		return center($text, 59);
}
// --------------------------------------------------------------
function writeLine($text) {
	$fp = fopen(PRINTER_OUT_PORT, "w");
	fwrite($fp, $text);
	fclose($fp);
}
// --------------------------------------------------------------

function center($text, $linewidth) {
	$blank = str_repeat(" ", 59);
	$text = trim($text);
	$lead = (int) (($linewidth - strlen($text)) / 2);
	$newline = substr($blank, 0, $lead).$text;
	return $newline;
}
// -------------------------------------------------------------
function drawerKick() {

		writeLine(chr(27).chr(112).chr(0).chr(48)."0");
}

// -------------------------------------------------------------
function printImage($image_fn) {
	$receipt = ""
		.chr(27).chr(33).chr(5);
	$img = RenderBitmapFromFile($image_fn);
	$receipt .= $img."\n";
		
	return $receipt;

}
// -------------------------------------------------------------
function promoMsg() {

}

/***** jqh 09/29/05 functions added for new receipt *****/
function biggerFont($str) {
	$receipt=chr(29).chr(33).chr(17);
	$receipt.=$str;
	$receipt.=chr(29).chr(33).chr(00);

	return $receipt;
}
function centerBig($text) {
	$blank = str_repeat(" ", 30);
	$text = trim($text);
	$lead = (int) ((30 - strlen($text)) / 2);
	$newline = substr($blank, 0, $lead).$text;
	return $newline;
}
/***** jqh end change *****/

function normalFont() {
	return chr(27).chr(33).chr(5);
}
function boldFont() {
	return chr(27).chr(33).chr(9);
}

function cut(){
	$receipt = str_repeat("\n", 8);
	$receipt .= chr(27).chr(105);
	return $receipt;
}

function twoColumns($col1, $col2) {
	// init
	$max = 56;
	$text = "";
	// find longest string in each column, ignoring font change strings
	$c1max = 0;
	$col1s = array();
	foreach( $col1 as $c1) {
		$c1s = trim(str_replace(array(boldFont(),normalFont()), "", $c1));
		$col1s[] = $c1s;
		$c1max = max($c1max, strlen($c1s));
	}
	$c2max = 0;
	$col2s = array();
	foreach( $col2 as $c2) {
		$c2s = trim(str_replace(array(boldFont(),normalFont()), "", $c2));
		$col2s[] = $c2s;
		$c2max = max($c2max, strlen($c2s));
	}
	// space the columns as much as they'll fit
	$spacer = $max - $c1max - $c2max;
	// scan both columns
	for( $x=0; isset($col1[$x]) && isset($col2[$x]); $x++) {
		$c1 = trim($col1[$x]);  $c1l = strlen($col1s[$x]);
		$c2 = trim($col2[$x]);  $c2l = strlen($col2s[$x]);
		if( ($c1max+$spacer+$c2l) <= $max) {
			$text .= $c1 . @str_repeat(" ", ($c1max+$spacer)-$c1l) . $c2 . "\n";
		} else {
			$text .= $c1 . "\n" . str_repeat(" ", $c1max+$spacer) . $c2 . "\n";
		}
	}
	// if one column is longer than the other, print the extras
	// (only one of these should happen since the loop above runs as long as both columns still have rows)
	for( $y=$x; isset($col1[$y]); $y++) {
		$text .= trim($col1[$y]) . "\n";
	} // col1 extras
	for( $y=$x; isset($col2[$y]); $y++) {
		$text .= str_repeat(" ", $c1max+$spacer) . trim($col2[$y]) . "\n";
	} // col2 extras
	return $text;
}

?>
