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
  @class ObfQuartersModel
*/
class ObfQuartersModel extends BasicModel
{

    protected $name = "ObfQuarters";

    protected $columns = array(
    'obfQuarterID' => array('type'=>'INT', 'primary_key'=>true, 'increment'=>true),
    'name' => array('type'=>'VARCHAR(10)'),
    'year' => array('type'=>'SMALLINT'),
    'weeks' => array('type'=>'TINYINT', 'default'=>13),
    'salesTarget' => array('type'=>'MONEY'),
    'laborTarget' => array('type'=>'MONEY'),
    );

    /* START ACCESSOR FUNCTIONS */

    public function obfQuarterID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["obfQuarterID"])) {
                return $this->instance["obfQuarterID"];
            } else if (isset($this->columns["obfQuarterID"]["default"])) {
                return $this->columns["obfQuarterID"]["default"];
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
                'left' => 'obfQuarterID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["obfQuarterID"]) || $this->instance["obfQuarterID"] != func_get_args(0)) {
                if (!isset($this->columns["obfQuarterID"]["ignore_updates"]) || $this->columns["obfQuarterID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["obfQuarterID"] = func_get_arg(0);
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

    public function year()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["year"])) {
                return $this->instance["year"];
            } else if (isset($this->columns["year"]["default"])) {
                return $this->columns["year"]["default"];
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
                'left' => 'year',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["year"]) || $this->instance["year"] != func_get_args(0)) {
                if (!isset($this->columns["year"]["ignore_updates"]) || $this->columns["year"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["year"] = func_get_arg(0);
        }
        return $this;
    }

    public function weeks()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["weeks"])) {
                return $this->instance["weeks"];
            } else if (isset($this->columns["weeks"]["default"])) {
                return $this->columns["weeks"]["default"];
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
                'left' => 'weeks',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["weeks"]) || $this->instance["weeks"] != func_get_args(0)) {
                if (!isset($this->columns["weeks"]["ignore_updates"]) || $this->columns["weeks"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["weeks"] = func_get_arg(0);
        }
        return $this;
    }

    public function salesTarget()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["salesTarget"])) {
                return $this->instance["salesTarget"];
            } else if (isset($this->columns["salesTarget"]["default"])) {
                return $this->columns["salesTarget"]["default"];
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
                'left' => 'salesTarget',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["salesTarget"]) || $this->instance["salesTarget"] != func_get_args(0)) {
                if (!isset($this->columns["salesTarget"]["ignore_updates"]) || $this->columns["salesTarget"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["salesTarget"] = func_get_arg(0);
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
    /* END ACCESSOR FUNCTIONS */
}

