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

/**
  @class ObfCategorySuperDeptMapModel
*/
class ObfCategorySuperDeptMapModel extends BasicModel
{

    protected $name = "ObfCategorySuperDeptMap";

    protected $columns = array(
    'obfCategoryID' => array('type'=>'INT', 'primary_key'=>true),
    'superID' => array('type'=>'INT', 'primary_key'=>true),
    'growthTarget' => array('type'=>'DOUBLE'),
    );

    /* START ACCESSOR FUNCTIONS */

    public function obfCategoryID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["obfCategoryID"])) {
                return $this->instance["obfCategoryID"];
            } else if (isset($this->columns["obfCategoryID"]["default"])) {
                return $this->columns["obfCategoryID"]["default"];
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
                'left' => 'obfCategoryID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["obfCategoryID"]) || $this->instance["obfCategoryID"] != func_get_args(0)) {
                if (!isset($this->columns["obfCategoryID"]["ignore_updates"]) || $this->columns["obfCategoryID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["obfCategoryID"] = func_get_arg(0);
        }
        return $this;
    }

    public function superID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["superID"])) {
                return $this->instance["superID"];
            } else if (isset($this->columns["superID"]["default"])) {
                return $this->columns["superID"]["default"];
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
                'left' => 'superID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["superID"]) || $this->instance["superID"] != func_get_args(0)) {
                if (!isset($this->columns["superID"]["ignore_updates"]) || $this->columns["superID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["superID"] = func_get_arg(0);
        }
        return $this;
    }

    public function growthTarget()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["growthTarget"])) {
                return $this->instance["growthTarget"];
            } else if (isset($this->columns["growthTarget"]["default"])) {
                return $this->columns["growthTarget"]["default"];
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
                'left' => 'growthTarget',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["growthTarget"]) || $this->instance["growthTarget"] != func_get_args(0)) {
                if (!isset($this->columns["growthTarget"]["ignore_updates"]) || $this->columns["growthTarget"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["growthTarget"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

