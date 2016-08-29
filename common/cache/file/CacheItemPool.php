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

namespace COREPOS\common\cache\file;

/**
  A very simple PSR-6 CacheItemPoolInterface implementation

  It uses serialize & unserialize while reading/writing from
  a file. 
*/
class CacheItemPool
{
    protected $items = array();
    protected $file = 'core.cache';

    public function __construct($file='core.cache')
    {
        $this->file = $this->getCacheFile($file);
        $this->items = $this->loadFromFile($this->file);
    }

    public function getItem($key)
    {
        if ($this->illegalKey($key)) {
            throw new \Exception('Invalid key ' . $key);
        }

        $val = null;
        $hit = false;
        if (isset($this->items[$key])) {
            $val = isset($this->items[$key]['val']) ? $this->items[$key]['val'] : null;
            if (isset($this->items[$key]['exp']) && $this->items[$key]['exp'] < time()) {
                $hit = false;
            } else {
                $hit = true;
            }
        }

        return new CacheItem($key, $val, $hit);
    }

    public function getItems($keys=array())
    {
        return array_map(array($this, 'getItem'), $keys);
    }

    public function hasItem($key)
    {
        return (isset($this->items[$key]));
    }

    public function clear()
    {
        $this->items = array();

        return true;
    }

    public function deleteItem($key)
    {
        if ($this->illegalKey($key)) {
            throw new \Exception('Invalid key ' . $key);
        }

        if (isset($this->items[$key])) {
            unset($this->items[$key]);
        }

        return true;
    }

    public function save($item)
    {
        $this->items[$item->getKey()] = $this->itemToInternal($item);

        return $this->saveToFile($this->file, $this->items);
    }

    public function saveDeferred($item)
    {
        $this->items[$item->getKey()] = $this->itemToInternal($item);

        return true;
    }

    public function commit()
    {
        return $this->saveToFile($this->file, $this->items);
    }

    protected function itemToInternal($item)
    {
        $arr = array('val' => $item->get());
        $exp = $item->expires();
        if ($exp) {
            $arr['exp'] = $exp;
        }

        return $arr;
    }

    protected function illegalKey($key)
    {
        if (preg_match('#\{\}\(\)/\\@:#', $key)) {
            return true;
        } else {
            return false;
        }
    }

    protected function loadFromFile($file)
    {
        // mark path as in use
        if (!file_exists($file)) {
            file_put_contents($file, array());
        }
        $str = file_get_contents($file);
        $decode = unserialize($str);

        return is_array($decode) ? $decode : array();
    }

    protected function saveToFile($file, $items)
    {
        return file_put_contents($file, serialize($items), LOCK_EX);
    }

    /**
      Locate writable cache file. Just a safety check
      against odd permissions issues.
    */
    protected function getCacheFile($name)
    {
        $temp = sys_get_temp_dir();
        $sep = DIRECTORY_SEPARATOR;

        return $temp . $sep . $name;
    }
}

