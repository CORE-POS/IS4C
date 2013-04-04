<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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
*/

$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."css/pos.css")) $CORE_PATH .= "../"; }

if (!isset($CORE_LOCAL)) include($CORE_PATH.'lib/LocalStorage/conf.php');
if (!class_exists("ScaleDriverWrapper")) include($CORE_PATH."scale-drivers/php-wrappers/ScaleDriverWrapper.php");
if (!function_exists('scaledisplaymsg')) include($CORE_PATH.'lib/drawscreen.php');
if (!function_exists('array_to_json')) include($CORE_PATH.'lib/array_to_json.php');
if (!function_exists('udpSend')) include($CORE_PATH.'lib/udpSend.php');

class NewMagellan extends ScaleDriverWrapper {

	function SavePortConfiguration($portName){
		global $CORE_PATH;

		/* read in config file  */
		$fp = fopen($CORE_PATH."scale-drivers/drivers/NewMagellan/ports.conf","r");
		$lines = array();
		while(!feof($fp)) $lines[] = fgets($fp);
		fclose($fp);

		/* replace port setting */
		$fp = fopen($CORE_PATH."scale-drivers/drivers/NewMagellan/ports.conf","w");
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
		global $CORE_PATH;

		/* read in c# code file */
		$fp = fopen($CORE_PATH."scale-drivers/drivers/NewMagellan/SPH_Magellan_Scale.cs","r");
		$lines = array();
		while(!feof($fp)) $lines[] = fgets($fp);
		fclose($fp);

		/* replace file location #defines */
		$fp = fopen($CORE_PATH."scale-drivers/drivers/NewMagellan/SPH_Magellan_Scale.cs","w");
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
		global $CORE_LOCAL,$CORE_PATH;

		$readfile = $CORE_PATH.'scale-drivers/drivers/NewMagellan/scanner-scale';
		$readdir = $CORE_PATH.'scale-drivers/drivers/NewMagellan/ss-output';
		$scale_display = "";
		$scans = array();
		/*
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
		*/
		$dh  = opendir($readdir);
		while (false !== ($fn = readdir($dh))) {
			if (is_dir($readdir."/".$fn)) continue;
			$data = file_get_contents($readdir."/".$fn);
			unlink($readdir."/".$fn);
			$line = rtrim($data,"\r\n");
			if (empty($line)) continue;
			if ($line[0] == 'S'){
				$scale_display = scaledisplaymsg($line);
			}
			else {
				$scans[] = $line;
			}
			break;
		}

		$output = array();
		if (!empty($scale_display)) $output['scale'] = $scale_display;
		if (!empty($scans)) $output['scans'] = $scans[0];

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
