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

class ssd extends ScaleDriverWrapper {

	function SavePortConfiguration($portName){
		global $IS4C_PATH;

		/* read in c code file */
		$fp = fopen($IS4C_PATH."scale-drivers/drivers/rs232/ssd.c","r");
		$lines = array();
		while(!feof($fp)) $lines[] = fgets($fp);
		fclose($fp);

		/* replace SSD_SERIAL_PORT definition */
		$fp = fopen($IS4C_PATH."scale-drivers/drivers/rs232/ssd.c","w");
		foreach($lines as $l){
			if (strstr($l,"#define SSD_SERIAL_PORT ") === False) fwrite($fp,$l);
			else {
				fwrite($fp,sprintf('#define SSD_SERIAL_PORT "%s"',$portName));
				fwrite($fp,"\n");
			}
		}
		fclose($fp);
	}

	function SaveDirectoryConfiguration($absPath){
		global $IS4C_PATH;

		/* read in c code file */
		$fp = fopen($IS4C_PATH."scale-drivers/drivers/rs232/ssd.c","r");
		$lines = array();
		while(!feof($fp)) $lines[] = fgets($fp);
		fclose($fp);

		/* replace file location #defines */
		$fp = fopen($IS4C_PATH."scale-drivers/drivers/rs232/ssd.c","w");
		foreach($lines as $l){
			if (strstr($l,"#define SCALE_OUTPUT_FILE ") !== False){
				fwrite($fp,sprintf('#define SCALE_OUTPUT_FILE "%s"',
					$absPath."scale-drivers/drivers/rs232/scale"));
				fwrite($fp,"\n");
			}
			elseif (strstr($l,"#define SCANNER_OUTPUT_FILE ") !== False){
				fwrite($fp,sprintf('#define SCANNER_OUTPUT_FILE "%s"',
					$absPath."scale-drivers/drivers/rs232/scanner"));
				fwrite($fp,"\n");
			}
			else fwrite($fp,$l);
		}
		fclose($fp);
	}

	function ReadFromScale(){
		global $IS4C_LOCAL,$IS4C_PATH;

		$scale_data = file_get_contents($IS4C_PATH.'scale-drivers/drivers/rs232/scale');
		$fp = open($IS4C_PATH.'scale-drivers/drivers/rs232/scale','w');
		fclose($fp);

		$scan_data = file_get_contents($IS4C_PATH.'scale-drivers/drivers/rs232/scanner');
		$fp = open($IS4C_PATH.'scale-drivers/drivers/rs232/scanner','w');
		fclose($fp);
	
		$scale_display = '';
		$scans = array();
		if ($scale_data !== False && !empty($scale_data))
			$scale_display = scaledisplaymsg($scale_data);
		if ($scan_data !== False && !empty($scan_data))
			$scans[] = $scan_data;

		$output = array();
		if (!empty($scale_display)) $output['scale'] = $scale_display;
		if (!empty($scans)) $output['scans'] = $scans;

		if (!empty($output)) echo array_to_json($output);
		else echo "{}";
	}

	function WriteToScale($str){
		global $IS4C_LOCAL;
		$port = $IS4C_LOCAL->get("scalePort");

		switch(strtolower($str)){
		case 'goodbeep':
			system('echo -e "S334\r" > '.$port);
			break;
		case 'errorbeep':
			system('echo -e "S334\r" > '.$port);
			usleep(100);
			system('echo -e "S334\r" > '.$port);
			usleep(100);
			system('echo -e "S334\r" > '.$port);
			break;
		case 'twopairs':
			system('echo -e "S334\r" > '.$port);
			usleep(100);
			system('echo -e "S334\r" > '.$port);
			usleep(300);
			system('echo -e "S334\r" > '.$port);
			usleep(100);
			system('echo -e "S334\r" > '.$port);
			break;
		case 'repoll':
			system('echo -e "S14\r" > '.$port);
			break;
		case 'wakeup':
			system('echo -e "S11\r" > '.$port);
			system('echo -e "S14\r" > '.$port);
			break;
		}
	}
}

?>
