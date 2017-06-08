<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

/**
  @class FannieDB

  Object for getting database connections.
  Singleton pattern so there's only one
  instance of SQLManager.
*/

class FannieDB 
{

    private static $db = null;

    private static $read_only = null;

    private function __construct(){}

    /**
      Get a database connection
      @param $db_name the database name
      @return A connected SQLManager instance
    */
    public static function get($db_name, &$previous_db=null)
    {
        if (!is_string($db_name) || !self::dbIsConfigured()) {
            return false;
        } else if (self::$db == null) {
            $previous_db = $db_name;
            self::newDB($db_name);
        } else if (!isset(self::$db->connections[$db_name])) {
            $previous_db = self::$db->defaultDatabase();
            self::addDB($db_name);
        } else {
            $previous_db = self::$db->defaultDatabase();
        }

        self::$db->setDefaultDB($db_name);

        return self::$db;
    }

    /**
      This method exists to support unit testing where test runs
      that take several minutes may run into issues with the
      single database connection timing out.
    */
    public static function forceReconnect($db_name)
    {
        if (self::$db !== null) {
            self::$db->close('', true);
            self::$db = null;
        }
        return self::get($db_name);
    }

    private static function dbIsConfigured()
    {
        $config = FannieConfig::factory();
        if ($config->get('SERVER') == '' || $config->get('SERVER_DBMS') == '' || $config->get('SERVER_USER') == '') {
            return false;
        } else {
            return true;
        }
    }

    private static function newDB($db_name)
    {
        $config = FannieConfig::factory();
        if (!class_exists('SQLManager')) {
            include(dirname(__FILE__) . '/../../src/SQLManager.php');
        }
        self::$db = new SQLManager(
            $config->get('SERVER'),
            $config->get('SERVER_DBMS'),
            $db_name, 
            $config->get('SERVER_USER'),
            $config->get('SERVER_PW'),
            false, 
            true);
    }

    private static function addDB($db_name)
    {
        self::$db->selectDB($db_name);
    }

    /**
      Get a read-only database connection
      @param $db_name the database name
      
      Unlike the normal get() method which returns
      a connection to the master database server,
      multiple read-only databases might be available. 
      Load balancing among them is simply random.
    */
    public static function getReadOnly($db_name)
    {
        if (self::$read_only === null) {
            $config = FannieConfig::factory();
            $json = json_decode($config->get('READONLY_JSON'), true);
            if (!is_array($json)) {
                return self::get($db_name);
            }

            $key = mt_rand(0, count($json)-1);
            $pick = $json[$key];
            if (!isset($pick['host']) || !isset($pick['type']) || !isset($pick['user']) || !isset($pick['pw'])) {
                return self::get($db_name);
            }

            self::$read_only = new SQLManager(
                $pick['host'],
                $pick['type'],
                $db_name,
                $pick['user'],
                $pick['pw'],
                false,
                true);
        } else {
            self::$read_only->selectDB($db_name);
        }

        return self::$read_only;
    }
}

