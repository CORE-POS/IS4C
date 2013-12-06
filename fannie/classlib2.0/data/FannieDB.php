<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of Fannie.

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

/**
  @class FannieDB

  Object for getting database connections.
  Singleton pattern so there's only one
  instance of SQLManager.
*/

class FannieDB 
{

    private static $db = null;

    private function __construct(){}

    /**
      Get a database connection
      @param $db_name the database name
      @return A connected SQLManager instance
    */
    public static function get($db_name)
    {
        if (self::$db == null) {
            self::newDB($db_name);
        } elseif (!isset(self::$db->connections[$db_name])) {
            self::addDB($db_name);
        }

        self::$db->default_db = $db_name;
        self::$db->query('use '.$db_name);
        $info = debug_backtrace();
        if (isset($info[1]['function'])) {
            self::$db->logger($db_name.' used by '.$info[1]['function']);
        }
        return self::$db;
    }

    private static function newDB($db_name)
    {
        global $FANNIE_ROOT, $FANNIE_SERVER, $FANNIE_SERVER_DBMS,
            $FANNIE_SERVER_USER, $FANNIE_SERVER_PW;
        if (!class_exists('SQLManager')) {
            include($FANNIE_ROOT.'src/SQLManager.php');
        }
        self::$db = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,
            $db_name, $FANNIE_SERVER_USER, $FANNIE_SERVER_PW, false, true);
    }

    private static function addDB($db_name)
    {
        global $FANNIE_ROOT, $FANNIE_SERVER, $FANNIE_SERVER_DBMS,
            $FANNIE_SERVER_USER, $FANNIE_SERVER_PW;
        if (!class_exists('SQLManager')) {
            include($FANNIE_ROOT.'src/SQLManager.php');
        }
        self::$db->add_connection($FANNIE_SERVER,$FANNIE_SERVER_DBMS,
            $db_name, $FANNIE_SERVER_USER, $FANNIE_SERVER_PW);
    }
}

