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
  @class VendorDepartmentsModel
*/
class VendorDepartmentsModel extends BasicModel
{

    protected $name = "vendorDepartments";

    protected $columns = array(
    'vendorID' => array('type'=>'INT', 'primary_key'=>true),
    'deptID' => array('type'=>'INT', 'primary_key'=>true),
    'name' => array('type'=>'VARCHAR(125)'),
    'margin' => array('type'=>'FLOAT'),
    'testing' => array('type'=>'FLOAT'),
    'posDeptID' => array('type'=>'INT'),
    );

    public function doc()
    {
        return '
Table: vendorDepartments

Columns:
    vendorID int
    deptID int
    name varchar
    margin float
    testing float
    posDeptID int

Depends on:
    vendors (table)

Use:
This table contains a vendors product categorization.
Two float fields, margin and testing, are provided
so you can try out new margins (i.e., calculate SRPs)
in testing without changing the current margin 
setting.

Traditional deptID corresponds to a UNFI\'s category
number. This may differ for other vendors.
        ';
    }

    /* START ACCESSOR FUNCTIONS */

    public function vendorID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["vendorID"])) {
                return $this->instance["vendorID"];
            } else if (isset($this->columns["vendorID"]["default"])) {
                return $this->columns["vendorID"]["default"];
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
                'left' => 'vendorID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["vendorID"]) || $this->instance["vendorID"] != func_get_args(0)) {
                if (!isset($this->columns["vendorID"]["ignore_updates"]) || $this->columns["vendorID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["vendorID"] = func_get_arg(0);
        }
        return $this;
    }

    public function deptID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["deptID"])) {
                return $this->instance["deptID"];
            } else if (isset($this->columns["deptID"]["default"])) {
                return $this->columns["deptID"]["default"];
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
                'left' => 'deptID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["deptID"]) || $this->instance["deptID"] != func_get_args(0)) {
                if (!isset($this->columns["deptID"]["ignore_updates"]) || $this->columns["deptID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["deptID"] = func_get_arg(0);
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

    public function margin()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["margin"])) {
                return $this->instance["margin"];
            } else if (isset($this->columns["margin"]["default"])) {
                return $this->columns["margin"]["default"];
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
                'left' => 'margin',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["margin"]) || $this->instance["margin"] != func_get_args(0)) {
                if (!isset($this->columns["margin"]["ignore_updates"]) || $this->columns["margin"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["margin"] = func_get_arg(0);
        }
        return $this;
    }

    public function testing()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["testing"])) {
                return $this->instance["testing"];
            } else if (isset($this->columns["testing"]["default"])) {
                return $this->columns["testing"]["default"];
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
                'left' => 'testing',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["testing"]) || $this->instance["testing"] != func_get_args(0)) {
                if (!isset($this->columns["testing"]["ignore_updates"]) || $this->columns["testing"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["testing"] = func_get_arg(0);
        }
        return $this;
    }

    public function posDeptID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["posDeptID"])) {
                return $this->instance["posDeptID"];
            } else if (isset($this->columns["posDeptID"]["default"])) {
                return $this->columns["posDeptID"]["default"];
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
                'left' => 'posDeptID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["posDeptID"]) || $this->instance["posDeptID"] != func_get_args(0)) {
                if (!isset($this->columns["posDeptID"]["ignore_updates"]) || $this->columns["posDeptID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["posDeptID"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

