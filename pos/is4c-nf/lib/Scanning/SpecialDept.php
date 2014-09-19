<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

/**
  @class SpecialDept

  A class to add custom actions to
  certain departments
*/
class SpecialDept 
{

    /**
      A short summary of what the special dept does
      Shown on mouse hover
    */
    public $help_summary = 'Documentation Needed!';

    /**
      More extensive help text, if needed
    */
    public function help_text()
    {
        return $help_summary;
    }
    
    /**
      Utility function
      Add the class to a handler map
      @param $deptID the department number
      @param $arr a handler map (array)
      @return handler map (array)
    */
    public function register($deptID,$arr)
    {
        if (!is_array($arr)) {
            $arr = array();
        }
        if (!isset($arr[$deptID]) || !is_array($arr[$deptID])) {
            $arr[$deptID] = array();
        }
        $inst = new ReflectionClass($this);
        $arr[$deptID][] = $inst->name;

        return $arr;
    }
    
    /**
      Process an open ring
      @param $upc The department ID
      @param $amount the sale amount
      @param $json Keyed array
      See the Parser class for array format
      @return Keyed array
      See the Parser class for array format

      These modules supplement parsing to make
      open ring handling more customizable. The module
      will be invoked within a Parser object and
      hence uses the same return format.
    */
    public function handle($deptID,$amount,$json)
    {
        return $json;
    }
}

