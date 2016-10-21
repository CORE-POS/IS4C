<?php
/*******************************************************************************

    Copyright 2016 Whole Foods Co-op

    This file is part of CORE-POS.

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

namespace COREPOS\Fannie\API\data;

/**
  @class Util
  Database helper methods
*/
class Util 
{
    private static function getPortByType($dbms)
    {
        switch (strtoupper($dbms)) {
            case 'MYSQL':
            case 'MYSQLI':
            case 'PDO_MYSQL':
                return 3306;
            case 'MSSQL':
                return 1433;
            case 'PGSQL':
                return 5432;
            default:
                return false;
        }
    }

    private static function socketCheck($host, $port)
    {
        $test = false;
        $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($sock, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 1, 'usec' => 0)); 
        socket_set_block($sock);
        try {
            $test = @socket_connect($sock,$host,$port);
        } catch(Exception $ex) {}
        socket_close($sock);

        return ($test ? true : false);    
    }

    public static function checkHost($host,$dbms)
    {
        if (!function_exists("socket_create")) {
            return true; // test not possible
        }
        if (empty($host)) {
            return false;
        }

        $port = self::getPortByType($dbms);
        if (strstr($host,":")) {
            list($host,$port) = explode(":",$host,2);
        }
        if (!$port) {
            return false;
        }

        return self::socketCheck($host, $port);
    }
}


