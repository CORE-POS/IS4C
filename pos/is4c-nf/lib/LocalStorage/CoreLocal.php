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

    private static function init()
    {
        if (class_exists(self::$mechanism)) {
            self::$storage_object = new self::$mechanism();
        } else {
            self::$storage_object = new SessionStorage();
        }
    }

    public static function get($key)
    {
        if (self::$storage_object === null) {
            self::init();
        }

        return self::$storage_object->get($key);
    }

    public static function set($key, $val, $immutable=false)
    {
        if (self::$storage_object === null) {
            self::init();
        }

        return self::$storage_object->set($key, $val, $immutable);
    }

    public static function refresh()
    {
        self::init();
        if (file_exists(dirname(__FILE__).'/../../ini.php')) {
            self::loadPhpIni(dirname(__FILE__).'/../../ini.php');
        }
    }

    private static function loadPhpIni($file)
    {
        /**
          UnitTestStorage is backed by a simple array rather
          than $_SESSION. This loads the ini.php settings into
          a temporary, *non-global* $CORE_LOCAL and then loops
          through to add them to the actual global session
        */
        $CORE_LOCAL = new UnitTestStorage();
        include($file);
        foreach ($CORE_LOCAL as $key => $value) {
            if (!in_array($key, self::$INI_SETTINGS)) {
                // setting does not belong in ini.php
            }
            self::set($key, $value);
        }
    }

    public static function setHandler($m)
    {
        self::$mechanism = $m;
    }

    public static function isImmutable($key)
    {
        if (self::$storage_object === null) {
            self::init();
        }

        return self::$storage_object->isImmutable($key);
    }

    public static function iteratorKeys()
    {
        if (self::$storage_object === null) {
            self::init();
        }

        return self::$storage_object->iteratorKeys();
    }
}

