<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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
*/

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

if (!isset($IS4C_LOCAL)) include($IS4C_PATH.'lib/LocalStorage/conf.php');
if (!class_exists("ScaleDriverWrapper")) include($IS4C_PATH."scale-drivers/php-wrappers/ScaleDriverWrapper.php");
if (!function_exists('array_to_json')) include($IS4C_PATH.'lib/array_to_json.php');
if (!function_exists('udpSend')) include($IS4C_PATH.'lib/udpSend.php');

class NM_Ingenico extends ScaleDriverWrapper {

	function ReadFromScale(){
		global $IS4C_LOCAL,$IS4C_PATH;

		$input = "";
		$readdir = $IS4C_PATH.'scale-drivers/drivers/NewMagellan/cc-output';
		$dh  = opendir($readdir);

		while (false !== ($fn = readdir($dh))) {
			if (is_dir($readdir."/".$fn)) continue;
			$data = file_get_contents($readdir."/".$fn);
			unlink($readdir."/".$fn);
			$line = rtrim($data,"\r\n");
			if (empty($line)) continue;
			$input = $line;
			break;
		}

		$output = array();
		if (!empty($input)) $output['scans'] = $input;

		if (!empty($output)) echo array_to_json($output);
		else echo "{}";
	}

	/* just wraps UDP send because commands 
	   ARE case-sensitive on the c# side */
	function WriteToScale($str){
		$str = strtolower($str);
		if (substr($str,0,6) == "total:" && strlen($str) > 6)
			udpSend($str);
		elseif (substr($str,0,11) == "resettotal:" && strlen($str) > 11)
			udpSend($str);
		elseif (substr($str,0,9) == "approval:" && strlen($str) > 9)
			udpSend($str);
		elseif ($str == "reset")
			udpSend($str);
		elseif ($str == "sig")
			udpSend($str);
	}
}

?>
