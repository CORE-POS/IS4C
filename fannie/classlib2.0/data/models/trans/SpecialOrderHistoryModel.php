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
  @class SpecialOrderHistoryModel
*/
class SpecialOrderHistoryModel extends BasicModel
{

    protected $name = "SpecialOrderHistory";
    protected $preferred_db = 'trans';

    protected $columns = array(
    'specialOrderHistoryID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'order_id' => array('type'=>'INT', 'index'=>true),
    'entry_type' => array('type'=>'VARCHAR(20)'),
    'entry_date' => array('type'=>'DATETIME'),
    'entry_value' => array('type'=>'TEXT'),
    );

    public function doc()
    {
        return '
Table: SpecialOrderHistory

Columns:
    specialOrderHistoryID int
    order_id int
    entry_type varchar
    entry_date datetime
    entry_value text

Depends on:
    PendingSpecialOrder

Use:
This table is for a work-in-progress special
order tracking system. Conceptually, it will
work like a partial suspended transactions,
where rows with a given order_id can be
pulled in at a register when someone picks up
their special order.

This table stores a dated history for the order
        ';
    }

    /* START ACCESSOR FUNCTIONS */

    public function specialOrderHistoryID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["specialOrderHistoryID"])) {
                return $this->instance["specialOrderHistoryID"];
            } else if (isset($this->columns["specialOrderHistoryID"]["default"])) {
                return $this->columns["specialOrderHistoryID"]["default"];
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
                'left' => 'specialOrderHistoryID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["specialOrderHistoryID"]) || $this->instance["specialOrderHistoryID"] != func_get_args(0)) {
                if (!isset($this->columns["specialOrderHistoryID"]["ignore_updates"]) || $this->columns["specialOrderHistoryID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["specialOrderHistoryID"] = func_get_arg(0);
        }
        return $this;
    }

    public function order_id()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["order_id"])) {
                return $this->instance["order_id"];
            } else if (isset($this->columns["order_id"]["default"])) {
                return $this->columns["order_id"]["default"];
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
                'left' => 'order_id',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["order_id"]) || $this->instance["order_id"] != func_get_args(0)) {
                if (!isset($this->columns["order_id"]["ignore_updates"]) || $this->columns["order_id"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["order_id"] = func_get_arg(0);
        }
        return $this;
    }

    public function entry_type()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["entry_type"])) {
                return $this->instance["entry_type"];
            } else if (isset($this->columns["entry_type"]["default"])) {
                return $this->columns["entry_type"]["default"];
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
                'left' => 'entry_type',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["entry_type"]) || $this->instance["entry_type"] != func_get_args(0)) {
                if (!isset($this->columns["entry_type"]["ignore_updates"]) || $this->columns["entry_type"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["entry_type"] = func_get_arg(0);
        }
        return $this;
    }

    public function entry_date()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["entry_date"])) {
                return $this->instance["entry_date"];
            } else if (isset($this->columns["entry_date"]["default"])) {
                return $this->columns["entry_date"]["default"];
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
                'left' => 'entry_date',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["entry_date"]) || $this->instance["entry_date"] != func_get_args(0)) {
                if (!isset($this->columns["entry_date"]["ignore_updates"]) || $this->columns["entry_date"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["entry_date"] = func_get_arg(0);
        }
        return $this;
    }

    public function entry_value()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["entry_value"])) {
                return $this->instance["entry_value"];
            } else if (isset($this->columns["entry_value"]["default"])) {
                return $this->columns["entry_value"]["default"];
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
                'left' => 'entry_value',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["entry_value"]) || $this->instance["entry_value"] != func_get_args(0)) {
                if (!isset($this->columns["entry_value"]["ignore_updates"]) || $this->columns["entry_value"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["entry_value"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

