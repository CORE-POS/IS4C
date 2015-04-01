<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

    /* START ACCESSOR FUNCTIONS */

    public function specialDeptModuleName()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["specialDeptModuleName"])) {
                return $this->instance["specialDeptModuleName"];
            } else if (isset($this->columns["specialDeptModuleName"]["default"])) {
                return $this->columns["specialDeptModuleName"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'specialDeptModuleName',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["specialDeptModuleName"]) || $this->instance["specialDeptModuleName"] != func_get_args(0)) {
                if (!isset($this->columns["specialDeptModuleName"]["ignore_updates"]) || $this->columns["specialDeptModuleName"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["specialDeptModuleName"] = func_get_arg(0);
        }
        return $this;
    }

    public function dept_no()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["dept_no"])) {
                return $this->instance["dept_no"];
            } else if (isset($this->columns["dept_no"]["default"])) {
                return $this->columns["dept_no"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'dept_no',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["dept_no"]) || $this->instance["dept_no"] != func_get_args(0)) {
                if (!isset($this->columns["dept_no"]["ignore_updates"]) || $this->columns["dept_no"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["dept_no"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

