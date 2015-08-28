<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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
  @class EquityPaymentPlansModel
*/
class EquityPaymentPlansModel extends BasicModel
{

    protected $name = "EquityPaymentPlans";
    protected $preferred_db = 'op';

    protected $columns = array(
    'equityPaymentPlanID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'name' => array('type'=>'VARCHAR(100)'),
    'initialPayment' => array('type'=>'MONEY'),
    'recurringPayment' => array('type'=>'MONEY'),
    'finalBalance' => array('type'=>'MONEY'),
    'billingCycle' => array('type'=>'VARCHAR(10)', 'default'=>"'1Y'"),
    'dueDateBasis' => array('type'=>'TINYINT', 'default'=>0),
    'overDueLimit' => array('type'=>'SMALLINT', 'default'=>31),
    'reasonMask' => array('type'=>'INT', 'default'=>1),
    );


    /* START ACCESSOR FUNCTIONS */

    public function equityPaymentPlanID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["equityPaymentPlanID"])) {
                return $this->instance["equityPaymentPlanID"];
            } else if (isset($this->columns["equityPaymentPlanID"]["default"])) {
                return $this->columns["equityPaymentPlanID"]["default"];
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
                'left' => 'equityPaymentPlanID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["equityPaymentPlanID"]) || $this->instance["equityPaymentPlanID"] != func_get_args(0)) {
                if (!isset($this->columns["equityPaymentPlanID"]["ignore_updates"]) || $this->columns["equityPaymentPlanID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["equityPaymentPlanID"] = func_get_arg(0);
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

    public function initialPayment()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["initialPayment"])) {
                return $this->instance["initialPayment"];
            } else if (isset($this->columns["initialPayment"]["default"])) {
                return $this->columns["initialPayment"]["default"];
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
                'left' => 'initialPayment',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["initialPayment"]) || $this->instance["initialPayment"] != func_get_args(0)) {
                if (!isset($this->columns["initialPayment"]["ignore_updates"]) || $this->columns["initialPayment"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["initialPayment"] = func_get_arg(0);
        }
        return $this;
    }

    public function recurringPayment()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["recurringPayment"])) {
                return $this->instance["recurringPayment"];
            } else if (isset($this->columns["recurringPayment"]["default"])) {
                return $this->columns["recurringPayment"]["default"];
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
                'left' => 'recurringPayment',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["recurringPayment"]) || $this->instance["recurringPayment"] != func_get_args(0)) {
                if (!isset($this->columns["recurringPayment"]["ignore_updates"]) || $this->columns["recurringPayment"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["recurringPayment"] = func_get_arg(0);
        }
        return $this;
    }

    public function finalBalance()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["finalBalance"])) {
                return $this->instance["finalBalance"];
            } else if (isset($this->columns["finalBalance"]["default"])) {
                return $this->columns["finalBalance"]["default"];
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
                'left' => 'finalBalance',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["finalBalance"]) || $this->instance["finalBalance"] != func_get_args(0)) {
                if (!isset($this->columns["finalBalance"]["ignore_updates"]) || $this->columns["finalBalance"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["finalBalance"] = func_get_arg(0);
        }
        return $this;
    }

    public function billingCycle()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["billingCycle"])) {
                return $this->instance["billingCycle"];
            } else if (isset($this->columns["billingCycle"]["default"])) {
                return $this->columns["billingCycle"]["default"];
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
                'left' => 'billingCycle',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["billingCycle"]) || $this->instance["billingCycle"] != func_get_args(0)) {
                if (!isset($this->columns["billingCycle"]["ignore_updates"]) || $this->columns["billingCycle"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["billingCycle"] = func_get_arg(0);
        }
        return $this;
    }

    public function dueDateBasis()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["dueDateBasis"])) {
                return $this->instance["dueDateBasis"];
            } else if (isset($this->columns["dueDateBasis"]["default"])) {
                return $this->columns["dueDateBasis"]["default"];
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
                'left' => 'dueDateBasis',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["dueDateBasis"]) || $this->instance["dueDateBasis"] != func_get_args(0)) {
                if (!isset($this->columns["dueDateBasis"]["ignore_updates"]) || $this->columns["dueDateBasis"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["dueDateBasis"] = func_get_arg(0);
        }
        return $this;
    }

    public function overDueLimit()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["overDueLimit"])) {
                return $this->instance["overDueLimit"];
            } else if (isset($this->columns["overDueLimit"]["default"])) {
                return $this->columns["overDueLimit"]["default"];
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
                'left' => 'overDueLimit',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["overDueLimit"]) || $this->instance["overDueLimit"] != func_get_args(0)) {
                if (!isset($this->columns["overDueLimit"]["ignore_updates"]) || $this->columns["overDueLimit"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["overDueLimit"] = func_get_arg(0);
        }
        return $this;
    }

    public function reasonMask()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["reasonMask"])) {
                return $this->instance["reasonMask"];
            } else if (isset($this->columns["reasonMask"]["default"])) {
                return $this->columns["reasonMask"]["default"];
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
                'left' => 'reasonMask',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["reasonMask"]) || $this->instance["reasonMask"] != func_get_args(0)) {
                if (!isset($this->columns["reasonMask"]["ignore_updates"]) || $this->columns["reasonMask"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["reasonMask"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

