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

    function ReadFromScale()
    {
        $readdir = dirname(__FILE__) . '/../drivers/NewMagellan/ss-output';
        // do not process any scale input while 
        // transaction is ending
        if (CoreLocal::get('End') != 0) {
            usleep(100);
            echo '{}';
            return;
        }

        $scale_display = "";
        $scans = array();
        $files = scandir($readdir);
        $files = array_filter($files, function($file) use ($readdir) { return !is_dir($readdir . '/' . $file); });
        foreach ($files as $fn) {
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
            } else {
                $scans[] = $line;
            }
            break;
        }

        $output = array();
        if (!empty($scale_display)) $output['scale'] = $scale_display;
        if (!empty($scans)) $output['scans'] = ltrim($scans[0],'0');

        if (!empty($output)) {
            echo JsonLib::array_to_json($output);
        } else {
            echo "{}";
        }
    }

    function ReadReset(){
        $readdir = dirname(__FILE__) . '/../drivers/NewMagellan/ss-output';
        $dh  = opendir($readdir);
        while (false !== ($fn = readdir($dh))) {
            if (is_dir($readdir."/".$fn)) continue;
            unlink($readdir."/".$fn);
        }
        closedir($dh);
        //$this->WriteToScale('rePoll');
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

