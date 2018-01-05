<?php
/*******************************************************************************

    Copyright 2007 Whole Foods Co-op

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

*********************************************************************************/

namespace COREPOS\pos\parser\parse;
use COREPOS\pos\parser\Parser;
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\TransRecord;

class Wakeup extends Parser 
{
    function check($str)
    {
        if ($str == "WAKEUP" || $str == 'WAKEUP2' || $str == 'PROFILE') {
            return True;
        }
        return False;
    }

    function parse($str)
    {
        $ret = $this->default_json();
        if ($str == 'PROFILE') {
            return $this->perfLog($ret);
        }
        $ret['udpmsg'] = $str == 'WAKEUP' ? 'wakeup' : 'reBoot';
        TransRecord::addLogRecord(array('upc'=>$str));
        return $ret;
    }

    private function perfLog($ret)
    {
        $perf = $this->session->get('perfLog');
        $msg = '';
        if (is_array($perf) || count($perf) > 0) {
            $msg .= '<table>';
            foreach ($perf as $p) {
                if (strpos($p['action'], '\\')) {
                    $tmp = explode('\\', $p['action']);
                    $p['action'] = $tmp[count($tmp) - 1];
                }
                $msg .= "<tr><td>{$p['action']}</td><td>{$p['time']}</td><td>{$p['input']}</td></tr>";
            }
            $msg .= '</table>';
        }
        if ($msg === '') {
            $msg = _('No data available');
        }
        $ret['output'] = DisplayLib::boxMsg($msg, '', true, DisplayLib::standardClearButton());

        return $ret;
    }

    function doc(){
        return "<table cellspacing=0 cellpadding=3 border=1>
            <tr>
                <th>Input</th><th>Result</th>
            </tr>
            <tr>
                <td>WAKEUP</td>
                <td>Try to coax a stuck scale back
                into operation</td>
            </tr>
            <tr>
                <td>WAKEUP2</td>
                <td>Different method, same goal</td>
            </tr>
            </table>";
    }
}

