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
 // session_start();
 
$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

// These functions have been translated from lib.asp by Brandon on 07.13.03.
// The "/blah" notation in the function heading indicates the Type of argument that should be given.

// ----------int($num /numeric)----------
//
// Given $num, detemine if it is numeric.
// If so, int returns the integral part of $num.
// Else generate a fatal error.

function int($num) {
	if(is_numeric($num)) {
		return (int) $num;
	}
	else {
		die("FATAL ERROR: Argument, '".$num.",' given to int() is not numeric.");
	} 
}

// -----------nullwrap($num /numeric)----------
//
// Given $num, if it is empty or of length less than one, nullwrap becomes "0".
// If the argument is a non-numeric, generate a fatal error.
// Else nullwrap becomes the number.

function nullwrap($num) {


	if ( !$num ) {
		 return 0;
	}
	elseif (!is_numeric($num) && strlen($num) < 1) {
		return " ";
	}
	else {
		return $num;
	}
}

// ----------truncate2($num /numeric)----------
//
// Round $num to two (2) digits after the decimal and return it as a STRING.

function truncate2($num) {
	return number_format($num, 2);
}

// ----------pinghost($host /string)----------
//
// Given $host, pinghost() looks up that host name or IP address.
// If it can connect function returned as true, else false.

function pinghost($host)
{
	global $IS4C_LOCAL;

	$host = str_replace("[", "", $host);
	$host = str_replace("]", "", $host);

	if (strstr($host,"\\")){
		$tmp = explode("\\",$host);
		$host = $tmp[0];
	}

	$intConnected = 0;
	if ($IS4C_LOCAL->get("OS") == "win32") {
		$pingReturn = exec("ping -n 1 $host", $aPingReturn);
		$packetLoss = "(0% loss";
	} else {
		$pingReturn = exec("ping -c 1 $host", $aPingReturn);
		$packetLoss = "1 received, 0% packet loss";
	}

	foreach($aPingReturn as $returnLine)

	{	$pos = strpos($returnLine, $packetLoss);
		if  ($pos) {
			$intConnected = 1; 
			break;
			}
	}
	return $intConnected;
}

function win32() {
	$winos = 0;
	if (substr(PHP_OS, 0, 3) == "WIN") $winos = 1;
	return $winos;
}

function scaleObject(){
	global $IS4C_LOCAL, $IS4C_PATH;
	$scaleDriver = $IS4C_LOCAL->get("scaleDriver");
	$sd = 0;
	if ($scaleDriver != "" && !class_exists($scaleDriver)){
		include($IS4C_PATH.'scale-drivers/php-wrappers/'.$scaleDriver.'.php');
		$sd = new $scaleDriver();
	}
	return $sd;
}

function goodBeep() {
	global $IS4C_LOCAL;
	$IS4C_LOCAL->set("beep","goodBeep");
	$sd = scaleObject();
	if (is_object($sd))
		$sd->WriteToScale("goodBeep");
}

function rePoll() {
	global $IS4C_LOCAL;
	$IS4C_LOCAL->set("beep","rePoll");
	$sd = scaleObject();
	if (is_object($sd))
		$sd->WriteToScale("rePoll");
}

function errorBeep() {
	global $IS4C_LOCAL;
	$IS4C_LOCAL->set("beep","errorBeep");
	$sd = scaleObject();
	if (is_object($sd))
		$sd->WriteToScale("errorBeep");
}

function twoPairs() {
	global $IS4C_LOCAL;
	$IS4C_LOCAL->set("beep","twoPairs");
	$sd = scaleObject();
	if (is_object($sd))
		$sd->WriteToScale("twoPairs");
}

?>
