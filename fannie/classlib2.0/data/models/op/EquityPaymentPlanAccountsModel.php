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
  @class EquityPaymentPlanAccountsModel
*/
class EquityPaymentPlanAccountsModel extends BasicModel
{
    protected $name = "EquityPaymentPlanAccounts";
    protected $preferred_db = 'op';

    protected $columns = array(
    'equityPaymentPlanAccountID' => array('type'=>'INT', 'increment'=>true, 'index'=>true),
    'cardNo' => array('type'=>'INT', 'primary_key'=>true),
    'equityPaymentPlanID' => array('type'=>'INT', 'primary_key'=>true),
    'lastPaymentDate' => array('type'=>'DATETIME'),
    'lastPaymentAmount' => array('type'=>'MONEY'),
    'nextPaymentDate' => array('type'=>'DATETIME'),
    'nextPaymentAmount' => array('type'=>'MONEY'),
    );

    /* START ACCESSOR FUNCTIONS */

    public function equityPaymentPlanAccountID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["equityPaymentPlanAccountID"])) {
                return $this->instance["equityPaymentPlanAccountID"];
            } else if (isset($this->columns["equityPaymentPlanAccountID"]["default"])) {
                return $this->columns["equityPaymentPlanAccountID"]["default"];
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
                'left' => 'equityPaymentPlanAccountID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["equityPaymentPlanAccountID"]) || $this->instance["equityPaymentPlanAccountID"] != func_get_args(0)) {
                if (!isset($this->columns["equityPaymentPlanAccountID"]["ignore_updates"]) || $this->columns["equityPaymentPlanAccountID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["equityPaymentPlanAccountID"] = func_get_arg(0);
        }
        return $this;
    }

    public function cardNo()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["cardNo"])) {
                return $this->instance["cardNo"];
            } else if (isset($this->columns["cardNo"]["default"])) {
                return $this->columns["cardNo"]["default"];
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
                'left' => 'cardNo',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["cardNo"]) || $this->instance["cardNo"] != func_get_args(0)) {
                if (!isset($this->columns["cardNo"]["ignore_updates"]) || $this->columns["cardNo"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["cardNo"] = func_get_arg(0);
        }
        return $this;
    }

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

    public function lastPaymentDate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["lastPaymentDate"])) {
                return $this->instance["lastPaymentDate"];
            } else if (isset($this->columns["lastPaymentDate"]["default"])) {
                return $this->columns["lastPaymentDate"]["default"];
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
                'left' => 'lastPaymentDate',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["lastPaymentDate"]) || $this->instance["lastPaymentDate"] != func_get_args(0)) {
                if (!isset($this->columns["lastPaymentDate"]["ignore_updates"]) || $this->columns["lastPaymentDate"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["lastPaymentDate"] = func_get_arg(0);
        }
        return $this;
    }

    public function lastPaymentAmount()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["lastPaymentAmount"])) {
                return $this->instance["lastPaymentAmount"];
            } else if (isset($this->columns["lastPaymentAmount"]["default"])) {
                return $this->columns["lastPaymentAmount"]["default"];
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
                'left' => 'lastPaymentAmount',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["lastPaymentAmount"]) || $this->instance["lastPaymentAmount"] != func_get_args(0)) {
                if (!isset($this->columns["lastPaymentAmount"]["ignore_updates"]) || $this->columns["lastPaymentAmount"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["lastPaymentAmount"] = func_get_arg(0);
        }
        return $this;
    }

    public function nextPaymentDate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["nextPaymentDate"])) {
                return $this->instance["nextPaymentDate"];
            } else if (isset($this->columns["nextPaymentDate"]["default"])) {
                return $this->columns["nextPaymentDate"]["default"];
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
                'left' => 'nextPaymentDate',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["nextPaymentDate"]) || $this->instance["nextPaymentDate"] != func_get_args(0)) {
                if (!isset($this->columns["nextPaymentDate"]["ignore_updates"]) || $this->columns["nextPaymentDate"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["nextPaymentDate"] = func_get_arg(0);
        }
        return $this;
    }

    public function nextPaymentAmount()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["nextPaymentAmount"])) {
                return $this->instance["nextPaymentAmount"];
            } else if (isset($this->columns["nextPaymentAmount"]["default"])) {
                return $this->columns["nextPaymentAmount"]["default"];
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
                'left' => 'nextPaymentAmount',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["nextPaymentAmount"]) || $this->instance["nextPaymentAmount"] != func_get_args(0)) {
                if (!isset($this->columns["nextPaymentAmount"]["ignore_updates"]) || $this->columns["nextPaymentAmount"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["nextPaymentAmount"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

