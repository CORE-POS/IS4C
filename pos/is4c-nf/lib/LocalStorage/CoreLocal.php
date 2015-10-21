<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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

if (!class_exists("LocalStorage")) {
    include_once(realpath(dirname(__FILE__).'/LocalStorage.php'));
}

/**
  @class CoreLocal
*/

class CoreLocal
{
    private static $storage_object = null;
    private static $mechanism = 'SessionStorage';

    private static $INI_SETTINGS = array(
        'laneno',
        'store_id',
        'localhost',
        'DBMS',
        'localUser',
        'localPass',
        'pDatabase',
        'tDatabase',
        'LaneMap',
    );

    /**
      Singleton constructor
    */
    private static function init()
    {
        if (class_exists(self::$mechanism)) {
            self::$storage_object = new self::$mechanism();
        } else {
            self::$storage_object = new SessionStorage();
        }
    }

    /**
      Get value from session
      @param $key [string] variable name
    */
    public static function get($key)
    {
        if (self::$storage_object === null) {
            self::init();
        }

        return self::$storage_object->get($key);
    }

    /**
      Set value in session
      @param $key [string] variable name
      @param $val [mixed] variable value
      @param $immutable [boolean, optional] 
      @return [mixed] variable value OR empty string if variable
        does not exist
    */
    public static function set($key, $val, $immutable=false)
    {
        if (self::$storage_object === null) {
            self::init();
        }

        return self::$storage_object->set($key, $val, $immutable);
    }

    /**
      Re-read values from config file
    */
    public static function refresh()
    {
        self::init();
        self::loadIni();
    }

    /**
      Load values from an ini file. 
      Will read the first file found from:
      1. ini.php
      2. ini.json
    */
    private static function loadIni()
    {
        /**
          UnitTestStorage is backed by a simple array rather
          than $_SESSION. This loads the ini.php settings into
          a temporary, *non-global* $CORE_LOCAL and then loops
          through to add them to the actual global session
        */
        if (!class_exists('UnitTestStorage')) {
            include(dirname(__FILE__) . '/UnitTestStorage.php');
        }
        $settings = array();
        if (file_exists(dirname(__FILE__) . '/../../ini.php')) {
            $settings = self::readIniPhp();
        } elseif (file_exists(dirname(__FILE__) . '/../../ini.json')) {
            $settings = self::readIniJson();
        }
        foreach ($settings as $key => $value) {
            if (!in_array($key, self::$INI_SETTINGS)) {
                // setting does not belong in ini.php
                // eventually these settings should be
                // ignored
            }
            self::set($key, $value);
        }
    }

    /**
      Examine configuration file. Extract settings
      that do not belong in the configuration file,
      write them to opdata.parameters, then remove
      them from the configuration file
    */
    public static function migrateSettings()
    {
        if (file_exists(dirname(__FILE__).'/../../ini.php') && class_exists('InstallUtilities')) {
            $file = dirname(__FILE__).'/../../ini.php';
            $settings = self::readIniPhp();
            foreach ($settings as $key => $value) {
                if (!in_array($key, self::$INI_SETTINGS)) {
                    if ($key == 'SpecialDeptMap') {
                        // SpecialDeptMap has a weird array structure
                        // and gets moved to a dedicated table
                        $db = Database::pDataConnect();
                        if ($db->table_exists('SpecialDeptMap')) {
                            $mapModel = new \COREPOS\pos\lib\models\op\SpecialDeptMapModel($db);
                            $mapModel->initTable($sconf);
                            InstallUtilities::confRemove($key);
                        }
                    } else {
                        // other settings go into opdata.parameters
                        $saved = InstallUtilities::paramSave($key, $value); 
                        if ($saved && is_writable($file)) {
                            InstallUtilities::confRemove($key);
                        }
                    }
                }
            }
        }
    }

    /**
      Read the settings from ini.php and return
      an equivalent JSON string
    */
    public static function convertIniPhpToJson()
    {
        $php = dirname(__FILE__) . '/../../ini.php';
        $php = self::readIniPhp();
        $json = array();
        foreach ($php as $key => $val) {
            $json[$key] = $val;
        }

        return JsonLib::prettyJSON(json_encode($json));
    }

    /**
      Read settings from ini.php
      @return [LocalStorage object] containing the settings  
    */
    public static function readIniPhp()
    {
        $php = dirname(__FILE__) . '/../../ini.php';
        $CORE_LOCAL = new UnitTestStorage();
        if (file_exists($php)) {
            include($php);
        }

        return $CORE_LOCAL;
    }

    /**
      Read settings from ini.json
      @return [LocalStorage object] containing the settings  
    */
    public static function readIniJson()
    {
        $json = dirname(__FILE__) . '/../../ini.json';
        $ret = new UnitTestStorage();
        if (file_exists($json)) {
            $encoded = file_get_contents($json);
            $decoded = json_decode($encoded, true);
            if (is_array($decoded)) {
                foreach ($decoded as $key => $val) {
                    $ret->set($key, $val);
                }
            }
        }

        return $ret;
    }

    /**
      Set the LocalStorage class used for storing
      session values
      @param $m [string] class name
    */
    public static function setHandler($m)
    {
        self::$mechanism = $m;
    }

    /**
      Check whether the given variable is immutable
      @param $key [string] variable name
      @return [boolean]

      Only here for unit test compatibility
    */
    public static function isImmutable($key)
    {
        if (self::$storage_object === null) {
            self::init();
        }

        return self::$storage_object->isImmutable($key);
    }

    /**
      Get list of stored variable names
      @return [array] of variable names

      LocalStorage objects implement the Iterator
      interface. This method helps the WrappedStorage
      class that provides backward compatibillity 
      between $CORE_LOCAL and CoreLocal meet
      the interface requirements.
    */
    public static function iteratorKeys()
    {
        if (self::$storage_object === null) {
            self::init();
        }

        return self::$storage_object->iteratorKeys();
    }
}

