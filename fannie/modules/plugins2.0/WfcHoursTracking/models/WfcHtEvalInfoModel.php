<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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
  @class WfcHtEvalInfoModel
*/
class WfcHtEvalInfoModel extends BasicModel
{

    protected $name = "evalInfo";

    protected $columns = array(
    'empID' => array('type'=>'INT', 'primary_key'=>true),
    'positions' => array('type'=>'VARCHAR(255)'),
    'nextEval' => array('type'=>'DATETIME'),
    'hireDate' => array('type'=>'DATETIME'),
    'nextTypeID' => array('type'=>'INT'),
    );

    /* START ACCESSOR FUNCTIONS */

    public function empID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["empID"])) {
                return $this->instance["empID"];
            } else if (isset($this->columns["empID"]["default"])) {
                return $this->columns["empID"]["default"];
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
                'left' => 'empID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["empID"]) || $this->instance["empID"] != func_get_args(0)) {
                if (!isset($this->columns["empID"]["ignore_updates"]) || $this->columns["empID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["empID"] = func_get_arg(0);
        }
        return $this;
    }

    public function positions()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["positions"])) {
                return $this->instance["positions"];
            } else if (isset($this->columns["positions"]["default"])) {
                return $this->columns["positions"]["default"];
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
                'left' => 'positions',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["positions"]) || $this->instance["positions"] != func_get_args(0)) {
                if (!isset($this->columns["positions"]["ignore_updates"]) || $this->columns["positions"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["positions"] = func_get_arg(0);
        }
        return $this;
    }

    public function nextEval()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["nextEval"])) {
                return $this->instance["nextEval"];
            } else if (isset($this->columns["nextEval"]["default"])) {
                return $this->columns["nextEval"]["default"];
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
                'left' => 'nextEval',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["nextEval"]) || $this->instance["nextEval"] != func_get_args(0)) {
                if (!isset($this->columns["nextEval"]["ignore_updates"]) || $this->columns["nextEval"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["nextEval"] = func_get_arg(0);
        }
        return $this;
    }

    public function hireDate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["hireDate"])) {
                return $this->instance["hireDate"];
            } else if (isset($this->columns["hireDate"]["default"])) {
                return $this->columns["hireDate"]["default"];
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
                'left' => 'hireDate',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["hireDate"]) || $this->instance["hireDate"] != func_get_args(0)) {
                if (!isset($this->columns["hireDate"]["ignore_updates"]) || $this->columns["hireDate"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["hireDate"] = func_get_arg(0);
        }
        return $this;
    }

    public function nextTypeID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["nextTypeID"])) {
                return $this->instance["nextTypeID"];
            } else if (isset($this->columns["nextTypeID"]["default"])) {
                return $this->columns["nextTypeID"]["default"];
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
                'left' => 'nextTypeID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["nextTypeID"]) || $this->instance["nextTypeID"] != func_get_args(0)) {
                if (!isset($this->columns["nextTypeID"]["ignore_updates"]) || $this->columns["nextTypeID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["nextTypeID"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

