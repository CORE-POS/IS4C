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

class ssd extends ScaleDriverWrapper {

	function SavePortConfiguration($portName){
		$rel = MiscLib::base_url();

		if (!file_exists($rel."scale-drivers/drivers/rs232/ssd.conf")) return;

		/* read in config file */
		$fp = fopen($rel."scale-drivers/drivers/rs232/ssd.conf","r");
		$lines = array();
		while(!feof($fp)) $lines[] = fgets($fp);
		fclose($fp);

		/* replace SerialPort config */
		$fp = fopen($rel."scale-drivers/drivers/rs232/ssd.conf","w");
		foreach($lines as $l){
			if (strstr($l,"SerialPort ") === False) fwrite($fp,$l);
			else {
				fwrite($fp,sprintf('SerialPort %s',$portName));
				fwrite($fp,"\n");
			}
		}
		fclose($fp);
	}

	function SaveDirectoryConfiguration($absPath){
		$rel = MiscLib::base_url();

		if (!file_exists($rel."scale-drivers/drivers/rs232/ssd.conf")) return;

		/* read in config file */
		$fp = fopen($rel."scale-drivers/drivers/rs232/ssd.conf","r");

		$lines = array();
		while(!feof($fp)) $lines[] = fgets($fp);
		fclose($fp);

		/* replace file location config */
		$fp = fopen($rel."scale-drivers/drivers/rs232/ssd.conf","w");
		foreach($lines as $l){
			if (strstr($l,"ScannerFile ") !== False){
				fwrite($fp,sprintf('ScannerFile %s',
					$absPath."scale-drivers/drivers/rs232/scale"));
				fwrite($fp,"\n");
			}
			elseif (strstr($l,"ScaleFile ") !== False){
				fwrite($fp,sprintf('ScaleFile %s',
					$absPath."scale-drivers/drivers/rs232/scanner"));
				fwrite($fp,"\n");
			}
			else fwrite($fp,$l);
		}
		fclose($fp);
	}

	function ReadFromScale(){
		$rel = MiscLib::base_url();

		$scale_data = file_get_contents($rel.'scale-drivers/drivers/rs232/scale');
		$fp = fopen($rel.'scale-drivers/drivers/rs232/scale','w');
		fclose($fp);

		$scan_data = file_get_contents($rel.'scale-drivers/drivers/rs232/scanner');
		$fp = fopen($rel.'scale-drivers/drivers/rs232/scanner','w');
		fclose($fp);
	
		$scale_display = '';
		$scans = array();
		if ($scale_data !== False && !empty($scale_data)){
			$scale_display = DisplayLib::scaledisplaymsg($scale_data);
			if (is_array($scale_display)){
				if (isset($scale_display['upc']))
					$scans[] = $scale_display['upc'];
				$scale_display = $scale_display['display'];
			}
		}
		if ($scan_data !== False && !empty($scan_data))
			$scans[] = $scan_data;

		$output = array();
		if (!empty($scale_display)) $output['scale'] = $scale_display;
		if (!empty($scans)) $output['scans'] = ltrim($scans[0],'0');

		if (!empty($output)) echo JsonLib::array_to_json($output);
		else echo "{}";
	}

	function WriteToScale($str){
		$port = CoreLocal::get("scalePort");

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
