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

if (!class_exists('COREPOS\common\cache\file\CacheItemPool', false)) {
    include(dirname(__FILE__) . '/../../../../common/cache/file/CacheItemPool.php');
}
if (!class_exists('COREPOS\common\cache\file\CacheItem', false)) {
    include(dirname(__FILE__) . '/../../../../common/cache/file/CacheItem.php');
}

/**
  @class LaneCache

  Wraps CORE's PSR-6 cache implementation because
  1) with no real DI the singleton object avoids reloading from file
  2) easier to swap out with a "good" cache later
*/
class LaneCache 
{
    private static $instance = null;

    public static function get($key)
    {
        if (self::$instance === null) {
            self::$instance = new \COREPOS\common\cache\file\CacheItemPool('lane.cache');
        }

        return self::$instance->getItem($key);
    }

    public static function set($item)
    {
        if (self::$instance === null) {
            self::$instance = new \COREPOS\common\cache\file\CacheItemPool('lane.cache');
        }
        self::$instance->save($item);
    }

    public static function clear()
    {
        if (self::$instance === null) {
            self::$instance = new \COREPOS\common\cache\file\CacheItemPool('lane.cache');
        }
        self::$instance->clear();
        self::$instance->commit();
    }
}

