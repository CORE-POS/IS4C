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
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\UdpComm;
use COREPOS\pos\parser\Parser;

class DriverStatus extends Parser 
{
    public function check($str)
    {
        return ($str === 'POS');
    }

    public function parse($str)
    {
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_bind($socket, '127.0.0.1', 9451);
        socket_set_option($socket,SOL_SOCKET,SO_RCVTIMEO,array("sec"=>1,"usec"=>0));
        UdpComm::udpSend('status');
        /**
          Yes, error suppression. PHP5.3 throws a warning here that
          breaks a unit test. PHP5.6 doesn't have the same issue so
          this can go away once the min supported version increases
        */
        if (@socket_recvfrom($socket, $buffer, 1024, 0, $host, $port)) {
            $msg = str_replace("\n", '<br>', $buffer);
        } else {
            $msg = _('No response to status request');
        }
        socket_close($socket);
        $ret = $this->default_json();
        $ret['output'] = DisplayLib::boxMsg($msg, _('Status Check'), true, DisplayLib::standardClearButton());

        return $ret;
    }

    public function doc()
    {
        return "<table cellspacing=0 cellpadding=3 border=1>
            <tr>
                <th>Input</th><th>Result</th>
            </tr>
            <tr>
                <td>STATUS</td>
                <td>Ask hardware driver its status</td>
            </tr>
            </table>";
    }
}

