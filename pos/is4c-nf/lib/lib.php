<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

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
 // session_start();
 
$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }

if (!isset($CORE_LOCAL)) include($CORE_PATH."lib/LocalStorage/conf.php");

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
	global $CORE_LOCAL;

	$host = str_replace("[", "", $host);
	$host = str_replace("]", "", $host);

	if (strstr($host,"\\")){
		$tmp = explode("\\",$host);
		$host = $tmp[0];
	}

	$intConnected = 0;
	if ($CORE_LOCAL->get("OS") == "win32") {
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
	global $CORE_LOCAL, $CORE_PATH;
	$scaleDriver = $CORE_LOCAL->get("scaleDriver");
	$sd = 0;
	if ($scaleDriver != "" && !class_exists($scaleDriver)){
		include($CORE_PATH.'scale-drivers/php-wrappers/'.$scaleDriver.'.php');
		$sd = new $scaleDriver();
	}
	return $sd;
}

function sigTermObject(){
	global $CORE_LOCAL, $CORE_PATH;
	$termDriver = $CORE_LOCAL->get("termDriver");
	$st = 0;
	if ($termDriver != ""){  
		if (!class_exists($termDriver))
			include($CORE_PATH.'scale-drivers/php-wrappers/'.$termDriver.'.php');
		$st = new $termDriver();
	}
	return $st;
}

function goodBeep() {
	global $CORE_LOCAL;
	$CORE_LOCAL->set("beep","goodBeep");
	$sd = scaleObject();
	if (is_object($sd))
		$sd->WriteToScale("goodBeep");
}

function rePoll() {
	global $CORE_LOCAL;
	$CORE_LOCAL->set("beep","rePoll");
	$sd = scaleObject();
	if (is_object($sd))
		$sd->WriteToScale("rePoll");
}

function errorBeep() {
	global $CORE_LOCAL;
	$CORE_LOCAL->set("beep","errorBeep");
	$sd = scaleObject();
	if (is_object($sd))
		$sd->WriteToScale("errorBeep");
}

function twoPairs() {
	global $CORE_LOCAL;
	$CORE_LOCAL->set("beep","twoPairs");
	$sd = scaleObject();
	if (is_object($sd))
		$sd->WriteToScale("twoPairs");
}

?>
