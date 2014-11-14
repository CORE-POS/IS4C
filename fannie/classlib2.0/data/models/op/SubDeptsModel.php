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
  @class SubDeptsModel
*/
class SubDeptsModel extends BasicModel
{

    protected $name = "subdepts";
    protected $preferred_db = 'op';

    protected $columns = array(
    'subdept_no' => array('type'=>'SMALLINT', 'primary_key'=>true),
    'subdept_name' => array('type'=>'VARCHAR(30)'),
    'dept_ID' => array('type'=>'SMALLINT'),
    );

    public function doc()
    {
        return '
Table: subdepts

Columns:
    subdept_no smallint
    subdept_name varchar
    dept_ID smallint

Depends on:
    departments (table)

Use:
A department may contain multiple subdepartments.
In most implementations I\'ve seen, invidual products
can be tagged with a subdepartment, but that
setting doesn\'t go into the final transaction log
        ';
    }

    /* START ACCESSOR FUNCTIONS */

    public function subdept_no()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["subdept_no"])) {
                return $this->instance["subdept_no"];
            } else if (isset($this->columns["subdept_no"]["default"])) {
                return $this->columns["subdept_no"]["default"];
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
                'left' => 'subdept_no',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["subdept_no"]) || $this->instance["subdept_no"] != func_get_args(0)) {
                if (!isset($this->columns["subdept_no"]["ignore_updates"]) || $this->columns["subdept_no"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["subdept_no"] = func_get_arg(0);
        }
        return $this;
    }

    public function subdept_name()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["subdept_name"])) {
                return $this->instance["subdept_name"];
            } else if (isset($this->columns["subdept_name"]["default"])) {
                return $this->columns["subdept_name"]["default"];
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
                'left' => 'subdept_name',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["subdept_name"]) || $this->instance["subdept_name"] != func_get_args(0)) {
                if (!isset($this->columns["subdept_name"]["ignore_updates"]) || $this->columns["subdept_name"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["subdept_name"] = func_get_arg(0);
        }
        return $this;
    }

    public function dept_ID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["dept_ID"])) {
                return $this->instance["dept_ID"];
            } else if (isset($this->columns["dept_ID"]["default"])) {
                return $this->columns["dept_ID"]["default"];
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
                'left' => 'dept_ID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["dept_ID"]) || $this->instance["dept_ID"] != func_get_args(0)) {
                if (!isset($this->columns["dept_ID"]["ignore_updates"]) || $this->columns["dept_ID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["dept_ID"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

