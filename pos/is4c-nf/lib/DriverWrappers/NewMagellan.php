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

namespace COREPOS\pos\lib\DriverWrappers;
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\UdpComm;
use \CoreLocal;

class NewMagellan extends ScaleDriverWrapper {

    function readFromScale()
    {
        $readdir = __DIR__ . '/../../scale-drivers/drivers/NewMagellan/ss-output';
        // do not process any scale input while 
        // transaction is ending
        if (CoreLocal::get('End') != 0) {
            usleep(100);
            echo '{}';
            return;
        }

        $scaleDisplay = "";
        $scans = array();
        $files = scandir($readdir);
        $files = array_filter($files, function($file) use ($readdir) { return !is_dir($readdir . '/' . $file); });
        foreach ($files as $file) {
            $data = file_get_contents($readdir."/".$file);
            unlink($readdir."/".$file);
            $line = rtrim($data,"\r\n");
            if (empty($line)) continue;
            if ($line[0] == 'S'){
                $scaleDisplay = DisplayLib::scaledisplaymsg($line);
                if (is_array($scaleDisplay)){
                    if (isset($scaleDisplay['upc']))
                        $scans[] = $scaleDisplay['upc'];
                    $scaleDisplay = $scaleDisplay['display'];
                }
            } else {
                $scans[] = $line;
            }
            break;
        }

        $output = array();
        if (!empty($scaleDisplay)) $output['scale'] = $scaleDisplay;
        if (!empty($scans)) $output['scans'] = ltrim($scans[0],'0');

        echo !empty($output) ? json_encode($output) : '{}';
    }

    function readReset()
    {
        $readdir = __DIR__ . '/../../scale-drivers/drivers/NewMagellan/ss-output';
        $dir  = opendir($readdir);
        while (false !== ($file = readdir($dir))) {
            if (is_dir($readdir."/".$file)) continue;
            unlink($readdir."/".$file);
        }
        closedir($dir);
    }

    /* just wraps UDP send because commands 
       ARE case-sensitive on the c# side */
    function writeToScale($str)
    {
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
        case 'reboot':
            UdpComm::udpSend('reBoot');
            break;
        case 'wakeup':
            UdpComm::udpSend('wakeup');
            break;
        }
    }
}

