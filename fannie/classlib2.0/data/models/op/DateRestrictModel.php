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
  @class DateRestrictModel
*/
class DateRestrictModel extends BasicModel
{

    protected $name = "dateRestrict";
    protected $preferred_db = 'op';

    protected $columns = array(
    'upc' => array('type'=>'VARCHAR(13)', 'index'=>true),
    'dept_ID' => array('type'=>'INT', 'index'=>true),
    'restrict_date' => array('type'=>'DATE'),
    'restrict_dow' => array('type'=>'SMALLINT'),
    'restrict_start' => array('type'=>'TIME'),
    'restrict_end' => array('type'=>'TIME'),
    );

    public function doc()
    {
        return '
Table: dateRestrict

Columns:
    upc varchar
    dept_ID int
    restrict_date date
    restrict_dow smallint
    restrict_start time
    restrict_end time

Depends on:
    products (table)
    departments (table)
Use:
Store restrictions for selling products at
certain dates & times. Restrictions can be specified
by UPC or department number as well as by 
exact date or day of week. If start and end
times are entered, restriction will only apply
during that span
        ';
    }

    /* START ACCESSOR FUNCTIONS */

    public function upc()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["upc"])) {
                return $this->instance["upc"];
            } else if (isset($this->columns["upc"]["default"])) {
                return $this->columns["upc"]["default"];
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
                'left' => 'upc',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["upc"]) || $this->instance["upc"] != func_get_args(0)) {
                if (!isset($this->columns["upc"]["ignore_updates"]) || $this->columns["upc"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["upc"] = func_get_arg(0);
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

    public function restrict_date()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["restrict_date"])) {
                return $this->instance["restrict_date"];
            } else if (isset($this->columns["restrict_date"]["default"])) {
                return $this->columns["restrict_date"]["default"];
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
                'left' => 'restrict_date',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["restrict_date"]) || $this->instance["restrict_date"] != func_get_args(0)) {
                if (!isset($this->columns["restrict_date"]["ignore_updates"]) || $this->columns["restrict_date"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["restrict_date"] = func_get_arg(0);
        }
        return $this;
    }

    public function restrict_dow()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["restrict_dow"])) {
                return $this->instance["restrict_dow"];
            } else if (isset($this->columns["restrict_dow"]["default"])) {
                return $this->columns["restrict_dow"]["default"];
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
                'left' => 'restrict_dow',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["restrict_dow"]) || $this->instance["restrict_dow"] != func_get_args(0)) {
                if (!isset($this->columns["restrict_dow"]["ignore_updates"]) || $this->columns["restrict_dow"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["restrict_dow"] = func_get_arg(0);
        }
        return $this;
    }

    public function restrict_start()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["restrict_start"])) {
                return $this->instance["restrict_start"];
            } else if (isset($this->columns["restrict_start"]["default"])) {
                return $this->columns["restrict_start"]["default"];
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
                'left' => 'restrict_start',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["restrict_start"]) || $this->instance["restrict_start"] != func_get_args(0)) {
                if (!isset($this->columns["restrict_start"]["ignore_updates"]) || $this->columns["restrict_start"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["restrict_start"] = func_get_arg(0);
        }
        return $this;
    }

    public function restrict_end()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["restrict_end"])) {
                return $this->instance["restrict_end"];
            } else if (isset($this->columns["restrict_end"]["default"])) {
                return $this->columns["restrict_end"]["default"];
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
                'left' => 'restrict_end',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["restrict_end"]) || $this->instance["restrict_end"] != func_get_args(0)) {
                if (!isset($this->columns["restrict_end"]["ignore_updates"]) || $this->columns["restrict_end"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["restrict_end"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

