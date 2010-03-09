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

function nullwrap_old($num) {
	if (($num == " ") || (strlen($num) < 1)) {
		 return 0;
	}
	elseif (!is_numeric($num)) {
		return " ";
	}
	else {
		return $num;
	}
}

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

// ----------truthwrap($num /numeric)----------
//
// If $num is any nonzero number, truthwrap return "1".
// If $num is zero, truthwrap will return "0" as well.
// Else fatal error.

function truthwrap($num) {
	if (!is_numeric($num)) {
		die("FATAL ERROR: Argument, '".$num.",' given to truthwrap() is not numeric.");
	}
	elseif ($num != 0) {
		return 1;
	}
	elseif ($num == 0) {
		return 0;
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

	$host = str_replace("[", "", $host);
	$host = str_replace("]", "", $host);

	$intConnected = 0;
	if ($_SESSION["OS"] == "win32") {
		$pingReturn = exec("ping -n 1 $host", $aPingReturn);
		$packetLoss = "(0% loss";
	} else {
		$pingReturn = exec("ping -c 1 $host", $aPingReturn);			//  Dropped the -w (timeout) flag, it was throwing errors!  ~joel 2006-12-13
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

// ----------------- Scanner scale functions ----------------

function win32() {
	$winos = 0;
	if (substr(PHP_OS, 0, 3) == "WIN") $winos = 1;
	return $winos;
} 


function execScript($script) {
	if (win32() == 1) {
		$_SESSION["beep"] = $script;
	} else {
		exec("/pos/is4c/rs232/".$script);
	}
}

function goodBeep() {
	execScript("goodBeep");
}

function rePoll() {
	execScript("rePoll");
}

function errorBeep() {
	execScript("errorBeep");
}

function twoPairs() {
	execScript("twoPairs");
}

?>
