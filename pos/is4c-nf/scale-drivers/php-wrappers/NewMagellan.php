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
if (!function_exists('scaledisplaymsg')) include($IS4C_PATH.'lib/drawscreen.php');
if (!function_exists('array_to_json')) include($IS4C_PATH.'lib/array_to_json.php');
if (!function_exists('udpSend')) include($IS4C_PATH.'lib/udpSend.php');

class NewMagellan extends ScaleDriverWrapper {

	function SavePortConfiguration($portName){
		global $IS4C_PATH;

		/* read in config file  */
		$fp = fopen($IS4C_PATH."scale-drivers/drivers/NewMagellan/ports.conf","r");
		$lines = array();
		while(!feof($fp)) $lines[] = fgets($fp);
		fclose($fp);

		/* replace port setting */
		$fp = fopen($IS4C_PATH."scale-drivers/drivers/NewMagellan/ports.conf","w");
		foreach($lines as $l){
			if (strstr($l,"SPH_Magellan_Scale") === False) fwrite($fp,$l);
			else {
				fwrite($fp,sprintf('%s SPH_Magellan_Scale',$portName));
				fwrite($fp,"\n");
			}
		}
		fclose($fp);
	}

	function SaveDirectoryConfiguration($absPath){
		global $IS4C_PATH;

		/* read in c# code file */
		$fp = fopen($IS4C_PATH."scale-drivers/drivers/NewMagellan/SPH_Magellan_Scale.cs","r");
		$lines = array();
		while(!feof($fp)) $lines[] = fgets($fp);
		fclose($fp);

		/* replace file location #defines */
		$fp = fopen($IS4C_PATH."scale-drivers/drivers/NewMagellan/SPH_Magellan_Scale.cs","w");
		foreach($lines as $l){
			if (strstr($l,"static String MAGELLAN_OUTPUT_FILE ") !== False){
				fwrite($fp,sprintf('private static String MAGELLAN_OUTPUT_FILE = "%s";',
					$absPath."scale-drivers/drivers/NewMagellan/scanner-scale.data"));
				fwrite($fp,"\n");
			}
			elseif (strstr($l,"static String MAGELLAN_LOCK_FILE ") !== False){
				fwrite($fp,sprintf('private static String MAGELLAN_LOCK_FILE = "%s";',
					$absPath."scale-drivers/drivers/NewMagellan/scanner-scale.lock"));
				fwrite($fp,"\n");
			}
			else fwrite($fp,$l);
		}
		fclose($fp);
	}

	function ReadFromScale(){
		global $IS4C_LOCAL,$IS4C_PATH;

		$readfile = $IS4C_PATH.'scale-drivers/NewMagellan/scanner-scale';
		$scale_display = "";
		$scans = array();
		if (file_exists($readfile.".data") && !file_exists($readfile.".lock")){

			$fp = fopen($readfile.".lock","w");
			fclose($fp);

			$data = file_get_contents($readfile.".data");

			unlink($readfile.".data");
			unlink($readfile.".lock");

			foreach(explode("\n",$data) as $line){
				$line = rtrim($line,"\r"); // in case OS adds it
				if (empty($line)) continue;
				if ($line[0] == 'S'){
					$scale_display = scaledisplaymsg($line);
				}
				else {
					$scans[] = $line;
				}
			}
		}

		$output = array();
		if (!empty($scale_display)) $output['scale'] = $scale_display;
		if (!empty($scans)) $output['scans'] = $scans;

		if (!empty($output)) echo array_to_json($output);
		else echo "{}";
	}

	/* just wraps UDP send because commands 
	   ARE case-sensitive on the c# side */
	function WriteToScale($str){
		switch(strtolower($str)){
		case 'goodbeep':
			udpSend('goodBeep');
			break;
		case 'errorbeep':
			udpSend('errorBeep');
			break;
		case 'twopairs':
			udpSend('twoPairs');
			break;
		case 'repoll':
			udpSend('rePoll');
			break;
		case 'wakeup':
			udpSend('wakeup');
			break;
		}
	}
}

?>
