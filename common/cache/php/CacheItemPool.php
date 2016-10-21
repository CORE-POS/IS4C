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

namespace COREPOS\common\cache\php;

use COREPOS\common\cache\file\CacheItemPool as FilePool;
use COREPOS\common\cache\file\CacheItem;

/**
  A PSR-6 CacheItemPoolInterface implementation
  It stores data as a .php file that can be opcode cached
*/
class CacheItemPool extends FilePool
{
    protected $items = array();
    protected $file = 'core.cache.php';

    public function __construct($file='core.cache.php')
    {
        if (substr($file, -4) !== '.php') {
            $file .= '.php';
        }
        $this->file = $this->getCacheFile($file);
        $this->items = $this->loadFromFile($this->file);
    }

    protected function loadFromFile($file)
    {
        // mark path as in use
        if (!file_exists($file)) {
            file_put_contents($file, "<?php\n return array();\n");
        }
        $cache = include($file);

        return $cache;
    }

    protected function saveToFile($file, $items)
    {
        $exp = var_export($items, true);
        $ret = file_put_contents($file, "<?php\n return {$exp};\n", LOCK_EX);
        if (function_exists('opcache_compile_file')) {
            opcache_compile_file($file);
        }

        return $ret;
    }
}

