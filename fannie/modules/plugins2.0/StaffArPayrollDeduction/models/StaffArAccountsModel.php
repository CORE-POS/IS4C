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
  @class StaffArAccountsModel
*/
class StaffArAccountsModel extends BasicModel
{

    protected $name = "StaffArAccounts";

    protected $columns = array(
    'staffArAccountID' => array('type'=>'INT', 'index'=>true, 'increment'=>true),
    'card_no' => array('type'=>'INT', 'primary_key'=>true),
    'payrollIdentifier' => array('type'=>'VARCHAR(30)'),
    'nextPayment' => array('type'=>'MONEY', 'default'=>0),
    );

    /* START ACCESSOR FUNCTIONS */

    public function staffArAccountID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["staffArAccountID"])) {
                return $this->instance["staffArAccountID"];
            } else if (isset($this->columns["staffArAccountID"]["default"])) {
                return $this->columns["staffArAccountID"]["default"];
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
                'left' => 'staffArAccountID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["staffArAccountID"]) || $this->instance["staffArAccountID"] != func_get_args(0)) {
                if (!isset($this->columns["staffArAccountID"]["ignore_updates"]) || $this->columns["staffArAccountID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["staffArAccountID"] = func_get_arg(0);
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

    public function payrollIdentifier()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["payrollIdentifier"])) {
                return $this->instance["payrollIdentifier"];
            } else if (isset($this->columns["payrollIdentifier"]["default"])) {
                return $this->columns["payrollIdentifier"]["default"];
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
                'left' => 'payrollIdentifier',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["payrollIdentifier"]) || $this->instance["payrollIdentifier"] != func_get_args(0)) {
                if (!isset($this->columns["payrollIdentifier"]["ignore_updates"]) || $this->columns["payrollIdentifier"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["payrollIdentifier"] = func_get_arg(0);
        }
        return $this;
    }

    public function nextPayment()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["nextPayment"])) {
                return $this->instance["nextPayment"];
            } else if (isset($this->columns["nextPayment"]["default"])) {
                return $this->columns["nextPayment"]["default"];
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
                'left' => 'nextPayment',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["nextPayment"]) || $this->instance["nextPayment"] != func_get_args(0)) {
                if (!isset($this->columns["nextPayment"]["ignore_updates"]) || $this->columns["nextPayment"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["nextPayment"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

