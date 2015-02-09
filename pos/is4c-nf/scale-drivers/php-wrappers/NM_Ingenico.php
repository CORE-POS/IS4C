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

class NM_Ingenico extends ScaleDriverWrapper {

	function ReadFromScale()
    {
		$rel = MiscLib::base_url();

		$input = "";
		$readdir = $rel.'scale-drivers/drivers/NewMagellan/cc-output';
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

		if (!empty($output)) echo JsonLib::array_to_json($output);
		else echo "{}";
	}

	function poll($msg){
		$res = UdpConn::udpPoke($msg);
		return $res;
	}

	function getpath(){
		$rel = MiscLib::base_url();
		return $rel.'scale-drivers/drivers/NewMagellan/';
	}

	/* just wraps UDP send because commands 
	   ARE case-sensitive on the c# side */
	function WriteToScale($str){

		if (strlen($str) > 8 && substr($str,0,8)=="display:"){}
		else // don't change case on display messages
			$str = strtolower($str);

		if (substr($str,0,6) == "total:" && strlen($str) > 6)
			UdpConn::udpSend($str);
		elseif (substr($str,0,11) == "resettotal:" && strlen($str) > 11)
			UdpConn::udpSend($str);
		elseif (substr($str,0,9) == "approval:" && strlen($str) > 9)
			UdpConn::udpSend($str);
		elseif (substr($str,0,8) == "display:" && strlen($str) > 8)
			UdpConn::udpSend($str);
		elseif ($str == "reset")
			UdpConn::udpSend($str);
		elseif ($str == "sig")
			UdpConn::udpSend($str);
	}
}

?>
