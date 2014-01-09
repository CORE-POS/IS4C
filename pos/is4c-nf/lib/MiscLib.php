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
 
/**
  @clss MiscLib
  Generic functions
*/
class MiscLib extends LibraryClass 
{

/**
  Path detection. Find the relative URL for 
  POS root.
  @param $check_file file to search for
  @return A relative URL with trailing slash
*/
static public function baseURL($check_file="css/pos.css")
{
	$ret = "";
	$cutoff = 0;
	while($cutoff < 20 && !file_exists($ret.$check_file)) {
		$ret .= "../";
		$cutoff++;
	}
	if ($cutoff >= 20) {
        return false;
	} else {
        return $ret;	
    }
}

static public function base_url($check_file="css/pos.css")
{
    return self::baseURL($check_file);
}

/**
  Sanitizes values
  @param $num a value
  @return a sanitized value

  Probably an artifact of ASP implementation.
  In practice any argument that evaluates to False
  get translated to integer zero.
*/
static public function nullwrap($num) 
{

	if ( !$num ) {
		 return 0;
	} elseif (!is_numeric($num) && strlen($num) < 1) {
		return " ";
	} else {
		return $num;
	}
}

/**
  Convert number to string with two decimal digits
  @param $num a number
  @return formatted string
*/
static public function truncate2($num) 
{
	return number_format($num, 2);
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
static public function pingport($host, $dbms)
{
	$port = strstr($dbms,'mysql') ? 3306 : 1433;	
	if (strstr($host,":")) {
		list($host,$port) = explode(":",$host);
    }
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
static public function win32() 
{
	$winos = 0;
	if (substr(PHP_OS, 0, 3) == "WIN") {
        $winos = 1;
    }

	return $winos;
}

/**
  Get the scale wrapper object
  @return An ScaleDriverWrapper object
  
  The driver is chosen via "scaleDriver"
  in $CORE_LOCAL. If the object cannot be 
  found this returns zero
*/
static public function scaleObject()
{
	global $CORE_LOCAL;
	$scaleDriver = $CORE_LOCAL->get("scaleDriver");
	$sd = 0;
	if ($scaleDriver != ""){
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
static public function sigTermObject()
{
	global $CORE_LOCAL;
	$termDriver = $CORE_LOCAL->get("termDriver");
	$st = 0;
	if ($termDriver != "") {
		$st = new $termDriver();
	}

	return $st;
}

/**
  Send good beep message to the scale
*/
static public function goodBeep() 
{
	global $CORE_LOCAL;
	$sd = self::scaleObject();
	if (is_object($sd)) {
		$sd->WriteToScale("goodBeep");
    }
}

/**
  Send re-poll message to the scale
*/
static public function rePoll() 
{
	global $CORE_LOCAL;
	$sd = self::scaleObject();
	if (is_object($sd)) {
		$sd->WriteToScale("rePoll");
    }
}

/**
  Send error beep message to the scale
*/
static public function errorBeep() 
{
	global $CORE_LOCAL;
	$sd = self::scaleObject();
	if (is_object($sd)) {
		$sd->WriteToScale("errorBeep");
    }
}

/**
  Send two pairs beep message to the scale
*/
static public function twoPairs() 
{
	global $CORE_LOCAL;
	$sd = self::scaleObject();
	if (is_object($sd)) {
		$sd->WriteToScale("twoPairs");
    }
}

} // end class MiscLib

