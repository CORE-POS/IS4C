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

namespace COREPOS\pos\lib;
use COREPOS\pos\lib\DriverWrappers\ScaleDriverWrapper;
use \CoreLocal;
 
/**
  @clss MiscLib
  Generic functions
*/
class MiscLib 
{

/**
  Path detection. Find the relative URL for 
  POS root.
  @param $checkFile file to search for
  @return A relative URL with trailing slash
*/
static public function baseURL($checkFile="css/pos.css")
{
    $ret = "";
    $cutoff = 0;
    while($cutoff < 20 && !file_exists($ret.$checkFile)) {
        $ret .= "../";
        $cutoff++;
    }
    if ($cutoff >= 20) {
        return false;
    }

    return $ret;    
}

static public function base_url($checkFile="css/pos.css")
{
    return self::baseURL($checkFile);
}

/**
  Sanitizes values
  @param $num a value
  @param $char [optional] boolean is character
  @return a sanitized value

  Probably an artifact of ASP implementation.
  In practice any argument that evaluates to False
  get translated to integer zero.
*/
static public function nullwrap($num, $char=false) 
{

    if ($char && ($num === '' || $num === null)) {
        return '';
    } elseif (!$num) {
         return 0;
    } elseif (!is_numeric($num) && strlen($num) < 1) {
        return ' ';
    }

    return $num;
}

/**
  Convert number to string with two decimal digits
  @param $num a number
  @return formatted string
*/
static public function truncate2($num) 
{
    if ($num === '') {
        $num = 0;
    }

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
    $port = strstr(strtolower($dbms),'mysql') ? 3306 : 1433;    
    if (strstr($host,":")) {
        list($host,$port) = explode(":",$host);
    }
    $sock = @stream_socket_client('tcp://' . $host . ':' . $port, $errno, $error, 1);
    if ($sock) {
        fclose($sock);
        return 1;
    }

    return 0;
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
    if (strtoupper(substr(PHP_OS, 0, 3)) == "WIN") {
        $winos = 1;
    }

    return $winos;
}

/**
  Get the scale wrapper object
  @return An ScaleDriverWrapper object
  
  The driver is chosen via "scaleDriver"
  in session. If the object cannot be 
  found this returns zero
*/
static public function scaleObject()
{
    return ScaleDriverWrapper::factory(CoreLocal::get('scaleDriver'));
}

/**
  Send good beep message to the scale
*/
static public function goodBeep() 
{
    $sdh = self::scaleObject();
    if (is_object($sdh)) {
        $sdh->writeToScale("goodBeep");
    }
}

/**
  Send re-poll message to the scale
*/
static public function rePoll() 
{
    $sdh = self::scaleObject();
    if (is_object($sdh)) {
        $sdh->writeToScale("rePoll");
    }
}

/**
  Send error beep message to the scale
*/
static public function errorBeep() 
{
    $sdh = self::scaleObject();
    if (is_object($sdh)) {
        $sdh->writeToScale("errorBeep");
    }
}

/**
  Send two pairs beep message to the scale
*/
static public function twoPairs() 
{
    $sdh = self::scaleObject();
    if (is_object($sdh)) {
        $sdh->writeToScale("twoPairs");
    }
}

/**
  Use ipconfig.exe or ifconfig, depending on OS,
  to determine all available IP addresses
  @return [array] of [string] IP addresses
*/
static public function getAllIPs()
{
    /**
      First: use OS utilities to check IP(s)
      This should be most complete but also
      may be blocked by permission settings
    */
    $ret = array();
    if (strstr(strtoupper(PHP_OS), 'WIN')) {
        // windows
        $ret = self::getWindowsIPs();
    } else {
        // unix-y system
        $ret = self::getLinuxIPs();
    }

    /**
      PHP 5.3 adds gethostname() function
      Try getting host name and resolving to an IP
    */
    if (function_exists('gethostname')) {
        $name = gethostname();
        $resolved = gethostbyname($name);
        if (preg_match('/^[\d\.+]$/', $resolved) && !in_array($resolved, $ret)) {
            $ret[] = $resolved;
        }
    }
    
    $ret = self::globalIPs($ret);

    return $ret;
}

static private function globalIPs(array $ret)
{
    /**
      $_SERVER may simply contain an IP address
    */
    $addr = filter_input(INPUT_SERVER, 'SERVER_ADDR');
    if ($addr !== null && !in_array($addr, $ret)) {
        $ret[] = $addr;
    }

    /**
      $_SERVER may contain a host name that can
      be resolved to an IP address
    */
    $sname = filter_input(INPUT_SERVER, 'SERVER_NAME');
    if ($sname !== null) {
        $resolved = gethostbyname($sname);
        if (preg_match('/^[\d\.+]$/', $resolved) && !in_array($resolved, $ret)) {
            $ret[] = $resolved;
        }
    }

    return $ret;
}

static private function getWindowsIPs()
{
    $cmd = "ipconfig.exe";
    exec($cmd, $outputLines);
    $ret = array();
    foreach ($outputLines as $line) {
        if (preg_match('/IP Address[\. ]+?: ([\d\.]+)/', $line, $matches)) {
            $ret[] = $matches[1];
        } elseif (preg_match('/IPv4 Address[\. ]+?: ([\d\.]+)/', $line, $matches)) {
            $ret[] = $matches[1];
        }
    }

    return $ret;
}

static private function getLinuxIPs()
{
    $bins = array('/sbin/', '/usr/sbin/', '/usr/bin/', '/bin/', '/usr/local/sbin/', '/usr/local/bin/');
    $bins = array_filter($bins, function($i){ return file_exists($i . 'ifconfig'); });
    if (count($bins) > 0) {
        $cmd = array_shift($bins) . 'ifconfig';
    } else {
        // give up; hope $PATH is correct
        $cmd = 'ifconfig';
    }

    exec($cmd, $outputLines);
    $ret = array();
    foreach ($outputLines as $line) {
        if (preg_match('/inet addr:([\d\.]+?) /', $line, $matches)) {
            $ret[] = $matches[1];
        }
    }

    return $ret;
}

static public function getNumbers($string)
{
    if (empty($string)) {
        return array(-999999);
    } elseif (is_array($string)) {
        $ret = array();
        foreach ($string as $s) {
            $ret[] = (int)$s;
        }
        return $ret;
    }
    $pieces = preg_split('/[^\d]+/', $string, 0, PREG_SPLIT_NO_EMPTY);
    for ($i=0; $i<count($pieces); $i++) {
        $pieces[$i] = (int)$pieces[$i];
    }

    return $pieces;
}

public static function centStrToDouble($str)
{
    if (strlen($str) == 0) {
        return 0.0;
    }
    /* when processing as strings, weird things happen
     * in excess of 1000, so use floating point */
    $str .= ""; // force type to string
    $mult = 1;
    if ($str[0] == "-") {
        $mult = -1;
        $str = substr($str,1,strlen($str));
    }
    $dollars = (int)substr($str,0,strlen($str)-2);
    $cents = ((int)substr($str,-2))/100.0;
    $ret = (double)($dollars+round($cents,2));
    $ret *= $mult;

    return $ret;
}

public static function jqueryFile()
{
    return self::win32() ? 'jquery-1.8.3.min.js' : 'jquery.js';
}

} // end class MiscLib

