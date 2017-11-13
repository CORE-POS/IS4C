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

    private static $config = null;

    private function __construct(){}

    private static function config()
    {
        if (self::$config === null) {
            $config = FannieConfig::factory();
        }

        return $config;
    }

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

    /**
     * Convert bare table name to fully-qualified name
     * @param $table [string] table name
     * @param $dbGeneric [string] database identifier
     * @return [string] fully-qualified table name
     */
    public static function fqn($table, $dbGeneric)
    {
        $config = self::config();
        $sep = $config->get('SERVER_DBMS') == 'mssql' || $config->get('SERVER_DBMS') == 'pdo_mssql' ? '.dbo.' : '.';
        if ($dbGeneric == 'op') {
            return $config->get('OP_DB') . $sep . $table;
        } elseif ($dbGeneric == 'trans') {
            return $config->get('TRANS_DB') . $sep . $table;
        } elseif ($dbGeneric == 'arch') {
            return $config->get('ARCHIVE_DB') . $sep . $table;
        } elseif (substr($dbGeneric, 0, 7) == 'plugin:') {
            $settings = $config->get('PLUGIN_SETTINGS');
            $dbName = substr($dbGeneric, 7);
            return isset($settings[$dbName]) ? $settings[$dbName] . $sep . $table : $table;
        }

        return $table;
    }

    private static function dbIsConfigured()
    {
        $config = self::config();
        if ($config->get('SERVER') == '' || $config->get('SERVER_DBMS') == '' || $config->get('SERVER_USER') == '') {
            return false;
        } else {
            return true;
        }
    }

    private static function newDB($db_name)
    {
        $config = self::config();
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
        self::$db->setCharSet($config->get('CHARSET'), $db_name);
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
            $config = self::config();
            $json = json_decode($config->get('READONLY_JSON'), true);
            if (!is_array($json)) {
                return self::get($db_name);
            }

            $key = mt_rand(0, count($json)-1);
            $pick = $json[$key];
            if (!isset($pick['host']) || !isset($pick['type']) || !isset($pick['user']) || !isset($pick['pw'])) {
                return self::get($db_name);
            }

            try {
                self::$read_only = new SQLManager(
                    $pick['host'],
                    $pick['type'],
                    $db_name,
                    $pick['user'],
                    $pick['pw']);
            } catch (Exception $ex) {
                return self::get($db_name);
            }
        } else {
            self::$read_only->selectDB($db_name);
        }

        return self::$read_only;
    }
}

