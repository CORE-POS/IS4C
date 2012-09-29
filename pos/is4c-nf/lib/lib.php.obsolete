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

/**
  @file
  @brief A collection of very basic functions with nowhere else to go.
  @deprecated See MiscLib
*/

// These functions have been translated from lib.asp by Brandon on 07.13.03.
// The "/blah" notation in the function heading indicates the Type of argument that should be given.

// ----------int($num /numeric)----------
//
// Given $num, detemine if it is numeric.
// If so, int returns the integral part of $num.
// Else generate a fatal error.

/**
  Cast value to integer
  @param $num must be numeric
  @return integer
*/
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

/**
  Sanitizes values
  @param $num a value
  @return a sanitized value

  Probably an artifact of ASP implementation.
  In practice any argument that evaluates to False
  get translated to integer zero.
*/
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

/**
  Convert number to string with two decimal digits
  @param $num a number
  @return formatted string
*/
function truncate2($num) {
	return number_format($num, 2);
}

// ----------pinghost($host /string)----------
//
// Given $host, pinghost() looks up that host name or IP address.
// If it can connect function returned as true, else false.

/**
  Ping a host
  @param $host the name or IP
  @return 
   - 1 on success
   - 0 on failure
  @deprecated
  Doesn't work reliably in all environments.
  Use pingport().
*/
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

/**
  Connect to a host briefly
  @param $host name or IP
  @param $dbms database type (supported: mysql, mssql)
  @return
   - 1 on success
   - 0 on failure

  This still works if the environment doesn't have
  ping or ping has odd output. It also verifies the
  database is running as well as the host is up.
*/
function pingport($host,$dbms){
	$port = ($dbms == 'mysql') ? 3306 : 1433;	
	if (strstr($host,":"))
		list($host,$port) = explode(":",$host);
	$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	socket_set_option($sock, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 1, 'usec' => 0)); 
	socket_set_block($sock);
	$test = socket_connect($sock,$host,$port);
	socket_close($sock);
	return ($test ? 1 : 0);	
}

/**
  Guess whether PHP is running on windows
  @return
   1 - windows
   0 - not windows
*/
function win32() {
	$winos = 0;
	if (substr(PHP_OS, 0, 3) == "WIN") $winos = 1;
	return $winos;
}

/**
  Get the scale wrapper object
  @return An ScaleDriverWrapper object
  
  The driver is chosen via "scaleDriver"
  in $CORE_LOCAL. If the object cannot be 
  found this returns zero
*/
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

/**
  Get the signature capture wrapper object
  @return An ScaleDriverWrapper object
  
  The driver is chosen via "termDriver"
  in $CORE_LOCAL. If the object cannot be 
  found this returns zero.

  Signature capture support is very alpha.
*/
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

/**
  Send good beep message to the scale
*/
function goodBeep() {
	global $CORE_LOCAL;
	$CORE_LOCAL->set("beep","goodBeep");
	$sd = scaleObject();
	if (is_object($sd))
		$sd->WriteToScale("goodBeep");
}

/**
  Send re-poll message to the scale
*/
function rePoll() {
	global $CORE_LOCAL;
	$CORE_LOCAL->set("beep","rePoll");
	$sd = scaleObject();
	if (is_object($sd))
		$sd->WriteToScale("rePoll");
}

/**
  Send error beep message to the scale
*/
function errorBeep() {
	global $CORE_LOCAL;
	$CORE_LOCAL->set("beep","errorBeep");
	$sd = scaleObject();
	if (is_object($sd))
		$sd->WriteToScale("errorBeep");
}

/**
  Send two pairs beep message to the scale
*/
function twoPairs() {
	global $CORE_LOCAL;
	$CORE_LOCAL->set("beep","twoPairs");
	$sd = scaleObject();
	if (is_object($sd))
		$sd->WriteToScale("twoPairs");
}

?>
