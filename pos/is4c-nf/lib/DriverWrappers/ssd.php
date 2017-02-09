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
use \CoreLocal;

class ssd extends ScaleDriverWrapper {

    function readFromScale()
    {
        $scale_data = $this->getFile('scale');
        $scan_data = $this->getFile('scanner');
    
        $scale_display = '';
        $scans = array();
        if ($scale_data !== false && !empty($scale_data)){
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

        if (!empty($output)) echo json_encode($output);
        else echo "{}";
    }

    private function getFile($filename)
    {
        $file = __DIR__ . '/../../scale-drivers/drivers/rs232/' . $filename;
        if (file_exists($file)) {
            $scale_data = file_get_contents($file);
        } else {
            $scale_data = '';
        }
        $fptr = fopen($file, 'w');
        fclose($fptr);

        return $scale_data;
    }

    function writeToScale($str)
    {
        switch(strtolower($str)){
        case 'goodbeep':
            $this->sendCmd('S334');
            break;
        case 'errorbeep':
            $this->sendCmd('S334');
            usleep(100);
            $this->sendCmd('S334');
            usleep(100);
            $this->sendCmd('S334');
            break;
        case 'twopairs':
            $this->sendCmd('S334');
            usleep(100);
            $this->sendCmd('S334');
            usleep(300);
            $this->sendCmd('S334');
            usleep(100);
            $this->sendCmd('S334');
            break;
        case 'repoll':
            $this->sendCmd('S14');
            break;
        case 'wakeup':
            $this->sendCmd('S11');
            $this->sendCmd('S14');
            break;
        }
    }

    private function sendCmd($cmd)
    {
        $port = CoreLocal::get("scalePort");
        system('echo -e "' . $cmd . '\r" > '.$port);
    }
}

