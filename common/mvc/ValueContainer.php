<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

namespace COREPOS\common\mvc;

class ValueContainer implements \Iterator
{
    protected $values = array();

    protected $iterator_position = 0;

    public function __construct()
    {
    }

    public function __get($name)
    {
        if (isset($this->values[$name])) {
            return $this->values[$name];
        } else {
            throw new \Exception("Unknown value {$name}");
        }
    }

    public function tryGet($name, $default='')
    {
        return isset($this->values[$name]) ? $this->values[$name] : $default;
    }

    public function __isset($name)
    {
        return isset($this->values[$name]);
    }

    public function __unset($name)
    {
        if (isset($this->values[$name])) {
            unset($this->values[$name]);
        }
    }

    public function __set($name, $value)
    {
        $this->values[$name] = $value;
    }

    public function current()
    {
        $keys = array_keys($this->values);
        return $this->__get($keys[$this->iterator_position]);
    }

    public function key()
    {
        $keys = array_keys($this->values);
        return $keys[$this->iterator_position];
    }

    public function next()
    {
        $this->iterator_position++;
    }

    public function valid()
    {
        $keys = array_keys($this->values);
        return isset($keys[$this->iterator_position]);
    }

    public function rewind()
    {
        $this->iterator_position = 0;
    }
}

