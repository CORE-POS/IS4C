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
use \CoreLocal;

if (!class_exists("COREPOS\\pos\\lib\\LocalStorage\\LocalStorage")) {
    include_once(__DIR__ . '/LocalStorage.php');
}

/**
  @class WrappedStorage
*/
class WrappedStorage extends LocalStorage 
{
    public function __construct()
    {
    }

    public function get($key)
    {
        return CoreLocal::get($key);
    }

    public function set($key,$val)
    {
        // WrappedStorage is used to load configuration
        // values from ini.php. These should be treated
        // as immutable
        return CoreLocal::set($key, $val, true);
    }
}

