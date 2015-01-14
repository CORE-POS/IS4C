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

class NewMagellan extends ScaleDriverWrapper {

	function SavePortConfiguration($portName){
		$rel = MiscLib::base_url();

		/* read in config file  */
		$fp = fopen($rel."scale-drivers/drivers/NewMagellan/ports.conf","r");
		$lines = array();
		while(!feof($fp)) $lines[] = fgets($fp);
		fclose($fp);

		/* replace port setting */
		$fp = fopen($rel."scale-drivers/drivers/NewMagellan/ports.conf","w");
		if ($fp){
			foreach($lines as $l){
				if (strstr($l,"SPH_Magellan_Scale") === False) fwrite($fp,$l);
				else {
					fwrite($fp,sprintf('%s SPH_Magellan_Scale',$portName));
					fwrite($fp,"\n");
				}
			}
			fclose($fp);
		}
	}

	function SaveDirectoryConfiguration($absPath){
		$rel = MiscLib::base_url();

		/* read in c# code file */
		$fp = fopen($rel."scale-drivers/drivers/NewMagellan/SPH_Magellan_Scale.cs","r");
		$lines = array();
		while(!feof($fp)) $lines[] = fgets($fp);
		fclose($fp);

		/* replace file location #defines */
		$fp = fopen($rel."scale-drivers/drivers/NewMagellan/SPH_Magellan_Scale.cs","w");
		if ($fp){
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
	}

	function ReadFromScale()
    {
		$rel = MiscLib::base_url();

		$readfile = $rel.'scale-drivers/drivers/NewMagellan/scanner-scale';
		$readdir = $rel.'scale-drivers/drivers/NewMagellan/ss-output';
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
		$files = scandir($readdir);
		foreach($files as $fn){
			if (is_dir($readdir."/".$fn)) continue;
			$data = file_get_contents($readdir."/".$fn);
			unlink($readdir."/".$fn);
			$line = rtrim($data,"\r\n");
			if (empty($line)) continue;
			if ($line[0] == 'S'){
				$scale_display = DisplayLib::scaledisplaymsg($line);
				if (is_array($scale_display)){
					if (isset($scale_display['upc']))
						$scans[] = $scale_display['upc'];
					$scale_display = $scale_display['display'];
				}
			}
			else {
				$scans[] = $line;
			}
			break;
		}

		$output = array();
		if (!empty($scale_display)) $output['scale'] = $scale_display;
		if (!empty($scans)) $output['scans'] = ltrim($scans[0],'0');

		if (!empty($output)) echo JsonLib::array_to_json($output);
		else echo "{}";
	}

	function ReadReset(){
		$rel = MiscLib::base_url();
		$readdir = $rel.'scale-drivers/drivers/NewMagellan/ss-output';
		$dh  = opendir($readdir);
		while (false !== ($fn = readdir($dh))) {
			if (is_dir($readdir."/".$fn)) continue;
			unlink($readdir."/".$fn);
		}
		closedir($dh);
		$this->WriteToScale('rePoll');
	}

	/* just wraps UDP send because commands 
	   ARE case-sensitive on the c# side */
	function WriteToScale($str){
		switch(strtolower($str)){
		case 'goodbeep':
			UdpComm::udpSend('goodBeep');
			break;
		case 'errorbeep':
			UdpComm::udpSend('errorBeep');
			break;
		case 'twopairs':
			UdpComm::udpSend('twoPairs');
			break;
		case 'repoll':
			UdpComm::udpSend('rePoll');
			break;
		case 'wakeup':
			UdpComm::udpSend('wakeup');
			break;
		}
	}
}

?>
