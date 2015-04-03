<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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
  @class DepartmentsModel
*/
class DepartmentsModel extends BasicModel 
{

    protected $name = "departments";

    protected $preferred_db = 'op';

    protected $normalize_lanes = true;

    protected $columns = array(
    'dept_no' => array('type'=>'SMALLINT','primary_key'=>True),
    'dept_name' => array('type'=>'VARCHAR(30)','index'=>True),
    'dept_tax' => array('type'=>'TINYINT'),
    'dept_fs' => array('type'=>'TINYINT'),
    'dept_limit' => array('type'=>'MONEY'),
    'dept_minimum' => array('type'=>'MONEY'),
    'dept_discount' => array('type'=>'TINYINT'),
    'dept_see_id' => array('type'=>'TINYINT', 'default'=>0),
    'modified' => array('type'=>'DATETIME'),
    'modifiedby' => array('type'=>'INT'),
    'margin' => array('type'=>'DOUBLE'),
    'salesCode' => array('type'=>'INT'),
    'memberOnly' => array('type'=>'SMALLINT', 'default'=>0),
    );

    protected function hookAddColumnmargin()
    {
        if ($this->connection->table_exists('deptMargin')) {
            $dataR = $this->connection->query('SELECT dept_ID, margin FROM deptMargin');
            $tempModel = new DepartmentsModel($this->connection);
            while($dataW = $this->connection->fetch_row($dataR)) {
                $tempModel->reset();
                $tempModel->dept_no($dataW['dept_ID']);
                if ($tempModel->load()) {
                    $tempModel->margin($dataW['margin']);
                    $tempModel->save();
                }
            }
        }
    }

    protected function hookAddColumnsalesCode()
    {
        if ($this->connection->table_exists('deptSalesCodes')) {
            $dataR = $this->connection->query('SELECT dept_ID, salesCode FROM deptSalesCodes');
            $tempModel = new DepartmentsModel($this->connection);
            while($dataW = $this->connection->fetch_row($dataR)) {
                $tempModel->reset();
                $tempModel->dept_no($dataW['dept_ID']);
                if ($tempModel->load()) {
                    $tempModel->salesCode($dataW['salesCode']);
                    $tempModel->save();
                }
            }
        }
    }

    public function doc()
    {
        return '
Table: departments

Columns:
    dept_no smallint
    dept_name varchar
    dept_tax tinyint
    dept_fs tinyint
    dept_limit dbms currency
    dept_minimum dbms currency
    dept_discount tinyint
    dept_see_id tinyint
    modified datetime
    modifiedby int
    margin double
    salesCode int
    memberOnly smallint

Depends on:
    none

Use:
Departments are the primary level of granularity
for products. Each product may belong to one department,
and when items are rung up the department setting
is what\'s saved in the transaction log

dept_no and dept_name identify a department

dept_tax,dept_fs, and dept_discount indicate whether
items in that department are taxable, foodstampable,
and discountable (respectively). Mostly these affect
open rings at the register, although WFC also uses
them to speed up new item entry. dept_see_id is for
departments where customers should show ID (e.g., alcohol).
The value is the age required for purchase.

dept_limit and dept_minimum are the highest and lowest
sales allowed in the department. These also affect open
rings. The prompt presented if limits are exceeded is
ONLY a warning, not a full stop.

margin is desired margin for products in the department.
It can be used for calculating retail pricing based
on costs. By convention, values are less than one.
A value of 0.35 means 35% margin. This value has
no meaning on the lane.

salesCode is yet another way of categorizing items.
It is typically used for chart of account numbers.
Often the financial accounting side of the business
wants to look at sales figures differently than
the operational side of the business. It\'s an organizational
and reporting field with no meaning on the lane.

memberOnly restricts sales based on customer membership
status. Values 0 through 99 are reserved. 100 and above
may be used for custom settings. Currently defined values:
    0 => No restrictions
    1 => Active members only (custdata.Type = \'PC\')
    2 => Active members only but cashier can override
    3 => Any custdata account *except* the default non-member account
        ';
    }

    /* START ACCESSOR FUNCTIONS */

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

    public function dept_name()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["dept_name"])) {
                return $this->instance["dept_name"];
            } else if (isset($this->columns["dept_name"]["default"])) {
                return $this->columns["dept_name"]["default"];
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
                'left' => 'dept_name',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["dept_name"]) || $this->instance["dept_name"] != func_get_args(0)) {
                if (!isset($this->columns["dept_name"]["ignore_updates"]) || $this->columns["dept_name"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["dept_name"] = func_get_arg(0);
        }
        return $this;
    }

    public function dept_tax()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["dept_tax"])) {
                return $this->instance["dept_tax"];
            } else if (isset($this->columns["dept_tax"]["default"])) {
                return $this->columns["dept_tax"]["default"];
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
                'left' => 'dept_tax',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["dept_tax"]) || $this->instance["dept_tax"] != func_get_args(0)) {
                if (!isset($this->columns["dept_tax"]["ignore_updates"]) || $this->columns["dept_tax"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["dept_tax"] = func_get_arg(0);
        }
        return $this;
    }

    public function dept_fs()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["dept_fs"])) {
                return $this->instance["dept_fs"];
            } else if (isset($this->columns["dept_fs"]["default"])) {
                return $this->columns["dept_fs"]["default"];
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
                'left' => 'dept_fs',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["dept_fs"]) || $this->instance["dept_fs"] != func_get_args(0)) {
                if (!isset($this->columns["dept_fs"]["ignore_updates"]) || $this->columns["dept_fs"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["dept_fs"] = func_get_arg(0);
        }
        return $this;
    }

    public function dept_limit()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["dept_limit"])) {
                return $this->instance["dept_limit"];
            } else if (isset($this->columns["dept_limit"]["default"])) {
                return $this->columns["dept_limit"]["default"];
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
                'left' => 'dept_limit',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["dept_limit"]) || $this->instance["dept_limit"] != func_get_args(0)) {
                if (!isset($this->columns["dept_limit"]["ignore_updates"]) || $this->columns["dept_limit"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["dept_limit"] = func_get_arg(0);
        }
        return $this;
    }

    public function dept_minimum()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["dept_minimum"])) {
                return $this->instance["dept_minimum"];
            } else if (isset($this->columns["dept_minimum"]["default"])) {
                return $this->columns["dept_minimum"]["default"];
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
                'left' => 'dept_minimum',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["dept_minimum"]) || $this->instance["dept_minimum"] != func_get_args(0)) {
                if (!isset($this->columns["dept_minimum"]["ignore_updates"]) || $this->columns["dept_minimum"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["dept_minimum"] = func_get_arg(0);
        }
        return $this;
    }

    public function dept_discount()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["dept_discount"])) {
                return $this->instance["dept_discount"];
            } else if (isset($this->columns["dept_discount"]["default"])) {
                return $this->columns["dept_discount"]["default"];
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
                'left' => 'dept_discount',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["dept_discount"]) || $this->instance["dept_discount"] != func_get_args(0)) {
                if (!isset($this->columns["dept_discount"]["ignore_updates"]) || $this->columns["dept_discount"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["dept_discount"] = func_get_arg(0);
        }
        return $this;
    }

    public function dept_see_id()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["dept_see_id"])) {
                return $this->instance["dept_see_id"];
            } else if (isset($this->columns["dept_see_id"]["default"])) {
                return $this->columns["dept_see_id"]["default"];
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
                'left' => 'dept_see_id',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["dept_see_id"]) || $this->instance["dept_see_id"] != func_get_args(0)) {
                if (!isset($this->columns["dept_see_id"]["ignore_updates"]) || $this->columns["dept_see_id"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["dept_see_id"] = func_get_arg(0);
        }
        return $this;
    }

    public function modified()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["modified"])) {
                return $this->instance["modified"];
            } else if (isset($this->columns["modified"]["default"])) {
                return $this->columns["modified"]["default"];
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
                'left' => 'modified',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["modified"]) || $this->instance["modified"] != func_get_args(0)) {
                if (!isset($this->columns["modified"]["ignore_updates"]) || $this->columns["modified"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["modified"] = func_get_arg(0);
        }
        return $this;
    }

    public function modifiedby()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["modifiedby"])) {
                return $this->instance["modifiedby"];
            } else if (isset($this->columns["modifiedby"]["default"])) {
                return $this->columns["modifiedby"]["default"];
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
                'left' => 'modifiedby',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["modifiedby"]) || $this->instance["modifiedby"] != func_get_args(0)) {
                if (!isset($this->columns["modifiedby"]["ignore_updates"]) || $this->columns["modifiedby"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["modifiedby"] = func_get_arg(0);
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

    public function salesCode()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["salesCode"])) {
                return $this->instance["salesCode"];
            } else if (isset($this->columns["salesCode"]["default"])) {
                return $this->columns["salesCode"]["default"];
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
                'left' => 'salesCode',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["salesCode"]) || $this->instance["salesCode"] != func_get_args(0)) {
                if (!isset($this->columns["salesCode"]["ignore_updates"]) || $this->columns["salesCode"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["salesCode"] = func_get_arg(0);
        }
        return $this;
    }

    public function memberOnly()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["memberOnly"])) {
                return $this->instance["memberOnly"];
            } else if (isset($this->columns["memberOnly"]["default"])) {
                return $this->columns["memberOnly"]["default"];
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
                'left' => 'memberOnly',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["memberOnly"]) || $this->instance["memberOnly"] != func_get_args(0)) {
                if (!isset($this->columns["memberOnly"]["ignore_updates"]) || $this->columns["memberOnly"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["memberOnly"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

