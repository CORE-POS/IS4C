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
  A very simple PSR-6 CacheItemInterface implementation
*/
class CacheItem
{

    private $key = null;
    private $value =  null;
    private $hit = false;
    private $expires = null;

    public function __construct($key, $val, $hit)
    {
        $this->key = $key;
        $this->value = $val;
        $this->hit = $hit ? true : false;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function get()
    {
        return $this->value;
    }

    public function isHit()
    {
        return $this->hit;
    }

    public function set($value)
    {
        $this->value = $value;

        return $this;
    }

    public function expiresAt($expiration)
    {
        if (is_object($expiration) && method_exists($expiration, 'format')) {
            $this->expires = $expiration->format('U');
        } elseif ($expiration === null) {
            $this->expires = $this->defaultExpire();
        }

        return $this;
    }

    public function expiresAfter($time)
    {
        if (is_int($time)) {
            $this->expires = time() + $time;
        } elseif (is_object($time) && method_exists($time, 'format')) {
            $this->expires = time() + $time->format('%s');
        } elseif ($time === null) {
            $this->expires = $this->defaultExpire();
        }

        return $this;
    }

    public function expires()
    {
        return $this->expires;
    }

    private function defaultExpire()
    {
        // default: 10 years (basically forever)
        return time() + 315360000;
    }

}

