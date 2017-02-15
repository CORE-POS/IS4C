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

namespace COREPOS\pos\lib\LocalStorage;

/**
  @class LaneCache

  Wraps CORE's PSR-6 cache implementation because
  1) with no real DI the singleton object avoids reloading from file
  2) easier to swap out with a "good" cache later
*/
class LaneCache 
{
    private static $instance = null;
    private static $changed = false;

    private static function init()
    {
        if (self::$instance === null) {
            if (function_exists('opcache_compile_file')) {
                self::$instance = new \COREPOS\common\cache\php\CacheItemPool('lane.cache');
            } else {
                self::$instance = new \COREPOS\common\cache\file\CacheItemPool('lane.cache');
            }
        }
    }

    public static function get($key)
    {
        self::init();

        return self::$instance->getItem($key);
    }

    public static function set($item)
    {
        self::init();
        self::$instance->saveDeferred($item);
        if (self::$changed === false) {
            register_shutdown_function(array('COREPOS\\pos\\lib\\LocalStorage\\LaneCache', 'flush'));
            self::$changed = true;
        }
    }

    public static function clear()
    {
        self::init();
        self::$instance->clear();
        self::$instance->commit();
    }

    public static function flush()
    {
        self::init();
        self::$instance->commit();
    }
}

