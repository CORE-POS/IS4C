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

// autoloading hasn't kicked in yet
// this could be improved
if (!class_exists('COREPOS\common\cache\file\CacheItemPool', false)) {
    include(dirname(__FILE__) . '/../../../../common/cache/file/CacheItemPool.php');
}
if (!class_exists('COREPOS\common\cache\php\CacheItemPool', false)) {
    include(dirname(__FILE__) . '/../../../../common/cache/php/CacheItemPool.php');
}
if (!class_exists('COREPOS\common\cache\file\CacheItem', false)) {
    include(dirname(__FILE__) . '/../../../../common/cache/file/CacheItem.php');
}

/**
  @class LaneConfig

  Wraps CORE's PSR-6 cache implementation because
  1) with no real DI the singleton object avoids reloading from file
  2) easier to swap out with a "good" cache later

  Unlike LaneCache, this presents a simpler get/set interface
  without using PSR-6 style cache item objects
*/
class LaneConfig 
{
    private static $instance = null;
    private static $changed = false;

    private static function init()
    {
        if (self::$instance === null) {
            if (function_exists('opcache_compile_file')) {
                self::$instance = new \COREPOS\common\cache\php\CacheItemPool('lane.config.cache');
            } else {
                self::$instance = new \COREPOS\common\cache\file\CacheItemPool('lane.config.cache');
            }
        }
    }

    public static function refresh()
    {
        self::clear();
        $json = __DIR__ . '/../../ini.json';
        if (file_exists($json)) {
            $json = json_decode(file_get_contents($json), true);
            if (is_array($json)) {
                foreach ($json as $key => $val) {
                    self::set($key, $val);
                }
                self::flush();
            }
        }
    }

    public static function get($key)
    {
        self::init();
        $item = self::$instance->getItem($key);

        if (!$item->isHit() || $item->get() === null) {
            return '';
        } else {
            return $item->get();
        }
    }

    public static function set($key, $val)
    {
        self::init();
        $item = self::$instance->getItem($key);
        $item->set($val);
        self::$instance->saveDeferred($item);
        if (self::$changed === false) {
            register_shutdown_function(array('COREPOS\\pos\\lib\\LocalStorage\\LaneConfig', 'flush'));
            self::$changed = true;
        }
    }

    public static function has($key)
    {
        self::init();

        return self::$instance->hasItem($key);
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
        self::$changed = false;
    }
}

