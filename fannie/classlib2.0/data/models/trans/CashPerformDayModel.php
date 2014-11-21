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
  @class CashPerformDayModel
*/
class CashPerformDayModel extends BasicModel
{

    protected $name = "CashPerformDay";

    protected $columns = array(
    'proc_date' => array('type'=>'DATETIME'),
    'emp_no' => array('type'=>'SMALLINT', 'index'=>true),
    'trans_num' => array('type'=>'VARCHAR(25)'),
    'startTime' => array('type'=>'DATETIME'),
    'endTime' => array('type'=>'DATETIME'),
    'transInterval' => array('type'=>'INT'),
    'items' => array('type'=>'FLOAT'),
    'rings' => array('type'=>'INT'),
    'Cancels' => array('type'=>'INT'),
    'card_no' => array('type'=>'INT'),
    );
    protected $preferred_db = 'trans';

    public function doc()
    {
        return '
Table: CashPerformDay

Columns:
    proc_date datetime
    emp_no int
    trans_num char
    startTime datetime
    endTime datetime
    transInterval int
    items int
    rings int
    cancels int
    card_no int

Depends on:
    none

Use:
Stores cashier performance metrics to
speed up reporting. 
        ';
    }

    /* START ACCESSOR FUNCTIONS */

    public function proc_date()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["proc_date"])) {
                return $this->instance["proc_date"];
            } else if (isset($this->columns["proc_date"]["default"])) {
                return $this->columns["proc_date"]["default"];
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
                'left' => 'proc_date',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["proc_date"]) || $this->instance["proc_date"] != func_get_args(0)) {
                if (!isset($this->columns["proc_date"]["ignore_updates"]) || $this->columns["proc_date"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["proc_date"] = func_get_arg(0);
        }
        return $this;
    }

    public function emp_no()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["emp_no"])) {
                return $this->instance["emp_no"];
            } else if (isset($this->columns["emp_no"]["default"])) {
                return $this->columns["emp_no"]["default"];
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
                'left' => 'emp_no',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["emp_no"]) || $this->instance["emp_no"] != func_get_args(0)) {
                if (!isset($this->columns["emp_no"]["ignore_updates"]) || $this->columns["emp_no"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["emp_no"] = func_get_arg(0);
        }
        return $this;
    }

    public function trans_num()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["trans_num"])) {
                return $this->instance["trans_num"];
            } else if (isset($this->columns["trans_num"]["default"])) {
                return $this->columns["trans_num"]["default"];
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
                'left' => 'trans_num',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["trans_num"]) || $this->instance["trans_num"] != func_get_args(0)) {
                if (!isset($this->columns["trans_num"]["ignore_updates"]) || $this->columns["trans_num"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["trans_num"] = func_get_arg(0);
        }
        return $this;
    }

    public function startTime()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["startTime"])) {
                return $this->instance["startTime"];
            } else if (isset($this->columns["startTime"]["default"])) {
                return $this->columns["startTime"]["default"];
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
                'left' => 'startTime',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["startTime"]) || $this->instance["startTime"] != func_get_args(0)) {
                if (!isset($this->columns["startTime"]["ignore_updates"]) || $this->columns["startTime"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["startTime"] = func_get_arg(0);
        }
        return $this;
    }

    public function endTime()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["endTime"])) {
                return $this->instance["endTime"];
            } else if (isset($this->columns["endTime"]["default"])) {
                return $this->columns["endTime"]["default"];
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
                'left' => 'endTime',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["endTime"]) || $this->instance["endTime"] != func_get_args(0)) {
                if (!isset($this->columns["endTime"]["ignore_updates"]) || $this->columns["endTime"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["endTime"] = func_get_arg(0);
        }
        return $this;
    }

    public function transInterval()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["transInterval"])) {
                return $this->instance["transInterval"];
            } else if (isset($this->columns["transInterval"]["default"])) {
                return $this->columns["transInterval"]["default"];
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
                'left' => 'transInterval',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["transInterval"]) || $this->instance["transInterval"] != func_get_args(0)) {
                if (!isset($this->columns["transInterval"]["ignore_updates"]) || $this->columns["transInterval"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["transInterval"] = func_get_arg(0);
        }
        return $this;
    }

    public function items()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["items"])) {
                return $this->instance["items"];
            } else if (isset($this->columns["items"]["default"])) {
                return $this->columns["items"]["default"];
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
                'left' => 'items',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["items"]) || $this->instance["items"] != func_get_args(0)) {
                if (!isset($this->columns["items"]["ignore_updates"]) || $this->columns["items"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["items"] = func_get_arg(0);
        }
        return $this;
    }

    public function rings()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["rings"])) {
                return $this->instance["rings"];
            } else if (isset($this->columns["rings"]["default"])) {
                return $this->columns["rings"]["default"];
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
                'left' => 'rings',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["rings"]) || $this->instance["rings"] != func_get_args(0)) {
                if (!isset($this->columns["rings"]["ignore_updates"]) || $this->columns["rings"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["rings"] = func_get_arg(0);
        }
        return $this;
    }

    public function Cancels()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["Cancels"])) {
                return $this->instance["Cancels"];
            } else if (isset($this->columns["Cancels"]["default"])) {
                return $this->columns["Cancels"]["default"];
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
                'left' => 'Cancels',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["Cancels"]) || $this->instance["Cancels"] != func_get_args(0)) {
                if (!isset($this->columns["Cancels"]["ignore_updates"]) || $this->columns["Cancels"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["Cancels"] = func_get_arg(0);
        }
        return $this;
    }

    public function card_no()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["card_no"])) {
                return $this->instance["card_no"];
            } else if (isset($this->columns["card_no"]["default"])) {
                return $this->columns["card_no"]["default"];
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
                'left' => 'card_no',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["card_no"]) || $this->instance["card_no"] != func_get_args(0)) {
                if (!isset($this->columns["card_no"]["ignore_updates"]) || $this->columns["card_no"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["card_no"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

