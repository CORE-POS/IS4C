<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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
  @class MemcacheStorage

  A LocalStorage implementation using
  memcached

  Requires memcached
*/
class MemcacheStorage extends LocalStorage 
{

    private $con;

    public function MemcacheStorage(){
        $this->con = new Memcache();
        $this->con->connect('127.0.0.1',11211);
    }

    public function get($key)
    {
        if ($this->isImmutable($key)) {
            return $this->immutables[$key];
        }
        $val = $this->con->get($key);
        if ($val === false) {
            $exists = $this->con->replace($key,false);
            if ($exists === false) {
                return "";
            }
        }

        return $val;
    }

    public function set($key,$val,$immutable=false)
    {
        if ($immutable) {
            $this->immutableSet($key,$val);
        } else {
            $this->con->set($key,$val);
        }
        $this->debug($key,$val);
    }
}

