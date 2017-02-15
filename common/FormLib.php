<?php
/*******************************************************************************

  Copyright 2014 Whole Foods Co-op

  This file is part of IT CORE.

  IT CORE is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  IT CORE is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  in the file license.txt along with IT CORE; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA

*********************************************************************************/

namespace COREPOS\common;
 
class FormLib 
{
    public static function get($name, $default='')
    {
        return self::getFormValue($name, $default);
    }

    /**
      Safely fetch a form value
      @param $name the field name
      @param $default default value if the form value doesn't exist
      @return The form value, if available, otherwise the default.
    */
    public static function getFormValue($name, $default='')
    {
        $val = filter_input(INPUT_GET, $name, FILTER_CALLBACK, array('options'=>array('COREPOS\\common\\FormLib', 'filterCallback')));
        if ($val === null) {
            $val = filter_input(INPUT_POST, $name, FILTER_CALLBACK, array('options'=>array('COREPOS\\common\\FormLib', 'filterCallback')));
        }
        if ($val === null) {
            $val = $default;
        }

        return $val;
    }

    /**
      Using callback style filtering so the form retrieval
      message can return both strings and arrays of strings.
    */
    private static function filterCallback($item)
    {
        return $item;
    }
}

