<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

    This file is part of Fannie.

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

namespace COREPOS\pos\lib\models\op;
use COREPOS\pos\lib\models\BasicModel;

/**
  @class SpecialDeptMapModel
*/
class SpecialDeptMapModel extends BasicModel
{

    protected $name = "SpecialDeptMap";

    protected $columns = array(
    'specialDeptModuleName' => array('type'=>'VARCHAR(100)', 'primary_key'=>true),
    'dept_no' => array('type'=>'INT', 'primary_key'=>true),
    );

    public $preferred_db = 'op';

    public function doc()
    {
        return '';
    }

    public function buildMap()
    {
        $map = array();
        foreach ($this->find() as $obj) {
            if (!isset($map[$obj->dept_no()])) {
                $map[$obj->dept_no()] = array();
            }
            if (!in_array($obj->specialDeptModuleName(), $map[$obj->dept_no()])) {
                $map[$obj->dept_no()][] = $obj->specialDeptModuleName();
            }
        }

        return $map;
    }

    public function initTable($map)
    {
        foreach ($map as $dept_no => $mod_list) {
            foreach ($mod_list as $module) {
                $this->reset();
                $this->specialDeptModuleName($module);
                $this->dept_no($dept_no);
                $this->save();
            }
        }
    }
}

