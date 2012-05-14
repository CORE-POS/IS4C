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
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }

class ssd extends ScaleDriverWrapper {

	function SavePortConfiguration($portName){
		global $CORE_PATH;

		/* read in c code file */
		$fp = fopen($CORE_PATH."scale-drivers/drivers/rs232/ssd.c","r");
		$lines = array();
		while(!feof($fp)) $lines[] = fgets($fp);
		fclose($fp);

		/* replace SSD_SERIAL_PORT definition */
		$fp = fopen($CORE_PATH."scale-drivers/drivers/rs232/ssd.c","w");
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
		global $CORE_PATH;

		/* read in c code file */
		$fp = fopen($CORE_PATH."scale-drivers/drivers/rs232/ssd.c","r");
		$lines = array();
		while(!feof($fp)) $lines[] = fgets($fp);
		fclose($fp);

		/* replace file location #defines */
		$fp = fopen($CORE_PATH."scale-drivers/drivers/rs232/ssd.c","w");
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
		global $CORE_LOCAL,$CORE_PATH;

		$scale_data = file_get_contents($CORE_PATH.'scale-drivers/drivers/rs232/scale');
		$fp = fopen($CORE_PATH.'scale-drivers/drivers/rs232/scale','w');
		fclose($fp);

		$scan_data = file_get_contents($CORE_PATH.'scale-drivers/drivers/rs232/scanner');
		$fp = fopen($CORE_PATH.'scale-drivers/drivers/rs232/scanner','w');
		fclose($fp);
	
		$scale_display = '';
		$scans = array();
		if ($scale_data !== False && !empty($scale_data))
			$scale_display = DisplayLib::scaledisplaymsg($scale_data);
		if ($scan_data !== False && !empty($scan_data))
			$scans[] = $scan_data;

		$output = array();
		if (!empty($scale_display)) $output['scale'] = $scale_display;
		if (!empty($scans)) $output['scans'] = ltrim($scans[0],'0');

		if (!empty($output)) echo JsonLib::array_to_json($output);
		else echo "{}";
	}

	function WriteToScale($str){
		global $CORE_LOCAL;
		$port = $CORE_LOCAL->get("scalePort");

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
