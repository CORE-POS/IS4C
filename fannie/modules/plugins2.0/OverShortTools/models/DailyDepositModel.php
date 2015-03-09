<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of IT CORE.

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

class DailyDepositModel extends BasicModel {

    protected $name = 'dailyDeposit';

    protected $columns = array(
    'dateStr' => array('type'=>'VARCHAR(21)','primary_key'=>True),
    'rowName' => array('type'=>'VARCHAR(15)','primary_key'=>True),
    'denomination' => array('type'=>'VARCHAR(6)','primary_key'=>True),
    'amt' => array('type'=>'MONEY','default'=>0)
    );

    /* START ACCESSOR FUNCTIONS */

    public function dateStr()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["dateStr"])) {
                return $this->instance["dateStr"];
            } else if (isset($this->columns["dateStr"]["default"])) {
                return $this->columns["dateStr"]["default"];
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
                'left' => 'dateStr',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["dateStr"]) || $this->instance["dateStr"] != func_get_args(0)) {
                if (!isset($this->columns["dateStr"]["ignore_updates"]) || $this->columns["dateStr"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["dateStr"] = func_get_arg(0);
        }
        return $this;
    }

    public function rowName()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["rowName"])) {
                return $this->instance["rowName"];
            } else if (isset($this->columns["rowName"]["default"])) {
                return $this->columns["rowName"]["default"];
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
                'left' => 'rowName',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["rowName"]) || $this->instance["rowName"] != func_get_args(0)) {
                if (!isset($this->columns["rowName"]["ignore_updates"]) || $this->columns["rowName"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["rowName"] = func_get_arg(0);
        }
        return $this;
    }

    public function denomination()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["denomination"])) {
                return $this->instance["denomination"];
            } else if (isset($this->columns["denomination"]["default"])) {
                return $this->columns["denomination"]["default"];
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
                'left' => 'denomination',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["denomination"]) || $this->instance["denomination"] != func_get_args(0)) {
                if (!isset($this->columns["denomination"]["ignore_updates"]) || $this->columns["denomination"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["denomination"] = func_get_arg(0);
        }
        return $this;
    }

    public function amt()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["amt"])) {
                return $this->instance["amt"];
            } else if (isset($this->columns["amt"]["default"])) {
                return $this->columns["amt"]["default"];
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
                'left' => 'amt',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["amt"]) || $this->instance["amt"] != func_get_args(0)) {
                if (!isset($this->columns["amt"]["ignore_updates"]) || $this->columns["amt"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["amt"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}
