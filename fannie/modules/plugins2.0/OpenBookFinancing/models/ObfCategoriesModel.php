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
  @class ObfCategoriesModel
*/
class ObfCategoriesModel extends BasicModel
{

    protected $name = "ObfCategories";

    protected $columns = array(
    'obfCategoryID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'name' => array('type'=>'VARCHAR(50)'),
    'hasSales' => array('type'=>'TINYINT', 'default'=>1),
    'growthTarget' => array('type'=>'DOUBLE'),
    'laborTarget' => array('type'=>'DOUBLE'),
    'hoursTarget' => array('type'=>'DOUBLE'),
    'averageWage' => array('type'=>'MONEY'),
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

    public function name()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["name"])) {
                return $this->instance["name"];
            } else if (isset($this->columns["name"]["default"])) {
                return $this->columns["name"]["default"];
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
                'left' => 'name',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["name"]) || $this->instance["name"] != func_get_args(0)) {
                if (!isset($this->columns["name"]["ignore_updates"]) || $this->columns["name"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["name"] = func_get_arg(0);
        }
        return $this;
    }

    public function hasSales()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["hasSales"])) {
                return $this->instance["hasSales"];
            } else if (isset($this->columns["hasSales"]["default"])) {
                return $this->columns["hasSales"]["default"];
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
                'left' => 'hasSales',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["hasSales"]) || $this->instance["hasSales"] != func_get_args(0)) {
                if (!isset($this->columns["hasSales"]["ignore_updates"]) || $this->columns["hasSales"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["hasSales"] = func_get_arg(0);
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

    public function laborTarget()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["laborTarget"])) {
                return $this->instance["laborTarget"];
            } else if (isset($this->columns["laborTarget"]["default"])) {
                return $this->columns["laborTarget"]["default"];
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
                'left' => 'laborTarget',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["laborTarget"]) || $this->instance["laborTarget"] != func_get_args(0)) {
                if (!isset($this->columns["laborTarget"]["ignore_updates"]) || $this->columns["laborTarget"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["laborTarget"] = func_get_arg(0);
        }
        return $this;
    }

    public function hoursTarget()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["hoursTarget"])) {
                return $this->instance["hoursTarget"];
            } else if (isset($this->columns["hoursTarget"]["default"])) {
                return $this->columns["hoursTarget"]["default"];
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
                'left' => 'hoursTarget',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["hoursTarget"]) || $this->instance["hoursTarget"] != func_get_args(0)) {
                if (!isset($this->columns["hoursTarget"]["ignore_updates"]) || $this->columns["hoursTarget"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["hoursTarget"] = func_get_arg(0);
        }
        return $this;
    }

    public function averageWage()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["averageWage"])) {
                return $this->instance["averageWage"];
            } else if (isset($this->columns["averageWage"]["default"])) {
                return $this->columns["averageWage"]["default"];
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
                'left' => 'averageWage',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["averageWage"]) || $this->instance["averageWage"] != func_get_args(0)) {
                if (!isset($this->columns["averageWage"]["ignore_updates"]) || $this->columns["averageWage"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["averageWage"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

