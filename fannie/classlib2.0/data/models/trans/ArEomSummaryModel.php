<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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
  @class ArEomSummaryModel
*/
class ArEomSummaryModel extends BasicModel
{

    protected $name = "AR_EOM_Summary";
    protected $preferred_db = 'trans';

    protected $columns = array(
    'cardno' => array('type'=>'INT', 'primary_key'=>true),
    'memName' => array('type'=>'VARCHAR(100)'),
    'priorBalance' => array('type'=>'MONEY'),
    'threeMonthCharges' => array('type'=>'MONEY'),
    'threeMonthPayments' => array('type'=>'MONEY'),
    'threeMonthBalance' => array('type'=>'MONEY'),
    'twoMonthCharges' => array('type'=>'MONEY'),
    'twoMonthPayments' => array('type'=>'MONEY'),
    'twoMonthBalance' => array('type'=>'MONEY'),
    'lastMonthCharges' => array('type'=>'MONEY'),
    'lastMonthPayments' => array('type'=>'MONEY'),
    'lastMonthBalance' => array('type'=>'MONEY'),
    );

    public function doc()
    {
        return '
Table: AR_EOM_Summary

Columns:
    card_no int
    memName varchar
    priorBalance money
    threeMonthCharges money
    threeMonthPayments money
    threeMonthBalance money
    twoMonthCharges money
    twoMonthPayments money
    twoMonthBalance money
    lastMonthCharges money
    lastMonthPayments money
    lastMonthBalance money

Use:
List of customer start/end AR balances
over past few months

Maintenance:
cron/nightly.ar.php, after updating ar_history,
 truncates ar_history_backup and then appends all of ar_history
        ';
    }

    /* START ACCESSOR FUNCTIONS */

    public function cardno()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["cardno"])) {
                return $this->instance["cardno"];
            } else if (isset($this->columns["cardno"]["default"])) {
                return $this->columns["cardno"]["default"];
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
                'left' => 'cardno',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["cardno"]) || $this->instance["cardno"] != func_get_args(0)) {
                if (!isset($this->columns["cardno"]["ignore_updates"]) || $this->columns["cardno"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["cardno"] = func_get_arg(0);
        }
        return $this;
    }

    public function memName()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["memName"])) {
                return $this->instance["memName"];
            } else if (isset($this->columns["memName"]["default"])) {
                return $this->columns["memName"]["default"];
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
                'left' => 'memName',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["memName"]) || $this->instance["memName"] != func_get_args(0)) {
                if (!isset($this->columns["memName"]["ignore_updates"]) || $this->columns["memName"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["memName"] = func_get_arg(0);
        }
        return $this;
    }

    public function priorBalance()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["priorBalance"])) {
                return $this->instance["priorBalance"];
            } else if (isset($this->columns["priorBalance"]["default"])) {
                return $this->columns["priorBalance"]["default"];
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
                'left' => 'priorBalance',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["priorBalance"]) || $this->instance["priorBalance"] != func_get_args(0)) {
                if (!isset($this->columns["priorBalance"]["ignore_updates"]) || $this->columns["priorBalance"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["priorBalance"] = func_get_arg(0);
        }
        return $this;
    }

    public function threeMonthCharges()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["threeMonthCharges"])) {
                return $this->instance["threeMonthCharges"];
            } else if (isset($this->columns["threeMonthCharges"]["default"])) {
                return $this->columns["threeMonthCharges"]["default"];
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
                'left' => 'threeMonthCharges',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["threeMonthCharges"]) || $this->instance["threeMonthCharges"] != func_get_args(0)) {
                if (!isset($this->columns["threeMonthCharges"]["ignore_updates"]) || $this->columns["threeMonthCharges"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["threeMonthCharges"] = func_get_arg(0);
        }
        return $this;
    }

    public function threeMonthPayments()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["threeMonthPayments"])) {
                return $this->instance["threeMonthPayments"];
            } else if (isset($this->columns["threeMonthPayments"]["default"])) {
                return $this->columns["threeMonthPayments"]["default"];
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
                'left' => 'threeMonthPayments',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["threeMonthPayments"]) || $this->instance["threeMonthPayments"] != func_get_args(0)) {
                if (!isset($this->columns["threeMonthPayments"]["ignore_updates"]) || $this->columns["threeMonthPayments"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["threeMonthPayments"] = func_get_arg(0);
        }
        return $this;
    }

    public function threeMonthBalance()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["threeMonthBalance"])) {
                return $this->instance["threeMonthBalance"];
            } else if (isset($this->columns["threeMonthBalance"]["default"])) {
                return $this->columns["threeMonthBalance"]["default"];
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
                'left' => 'threeMonthBalance',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["threeMonthBalance"]) || $this->instance["threeMonthBalance"] != func_get_args(0)) {
                if (!isset($this->columns["threeMonthBalance"]["ignore_updates"]) || $this->columns["threeMonthBalance"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["threeMonthBalance"] = func_get_arg(0);
        }
        return $this;
    }

    public function twoMonthCharges()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["twoMonthCharges"])) {
                return $this->instance["twoMonthCharges"];
            } else if (isset($this->columns["twoMonthCharges"]["default"])) {
                return $this->columns["twoMonthCharges"]["default"];
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
                'left' => 'twoMonthCharges',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["twoMonthCharges"]) || $this->instance["twoMonthCharges"] != func_get_args(0)) {
                if (!isset($this->columns["twoMonthCharges"]["ignore_updates"]) || $this->columns["twoMonthCharges"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["twoMonthCharges"] = func_get_arg(0);
        }
        return $this;
    }

    public function twoMonthPayments()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["twoMonthPayments"])) {
                return $this->instance["twoMonthPayments"];
            } else if (isset($this->columns["twoMonthPayments"]["default"])) {
                return $this->columns["twoMonthPayments"]["default"];
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
                'left' => 'twoMonthPayments',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["twoMonthPayments"]) || $this->instance["twoMonthPayments"] != func_get_args(0)) {
                if (!isset($this->columns["twoMonthPayments"]["ignore_updates"]) || $this->columns["twoMonthPayments"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["twoMonthPayments"] = func_get_arg(0);
        }
        return $this;
    }

    public function twoMonthBalance()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["twoMonthBalance"])) {
                return $this->instance["twoMonthBalance"];
            } else if (isset($this->columns["twoMonthBalance"]["default"])) {
                return $this->columns["twoMonthBalance"]["default"];
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
                'left' => 'twoMonthBalance',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["twoMonthBalance"]) || $this->instance["twoMonthBalance"] != func_get_args(0)) {
                if (!isset($this->columns["twoMonthBalance"]["ignore_updates"]) || $this->columns["twoMonthBalance"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["twoMonthBalance"] = func_get_arg(0);
        }
        return $this;
    }

    public function lastMonthCharges()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["lastMonthCharges"])) {
                return $this->instance["lastMonthCharges"];
            } else if (isset($this->columns["lastMonthCharges"]["default"])) {
                return $this->columns["lastMonthCharges"]["default"];
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
                'left' => 'lastMonthCharges',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["lastMonthCharges"]) || $this->instance["lastMonthCharges"] != func_get_args(0)) {
                if (!isset($this->columns["lastMonthCharges"]["ignore_updates"]) || $this->columns["lastMonthCharges"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["lastMonthCharges"] = func_get_arg(0);
        }
        return $this;
    }

    public function lastMonthPayments()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["lastMonthPayments"])) {
                return $this->instance["lastMonthPayments"];
            } else if (isset($this->columns["lastMonthPayments"]["default"])) {
                return $this->columns["lastMonthPayments"]["default"];
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
                'left' => 'lastMonthPayments',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["lastMonthPayments"]) || $this->instance["lastMonthPayments"] != func_get_args(0)) {
                if (!isset($this->columns["lastMonthPayments"]["ignore_updates"]) || $this->columns["lastMonthPayments"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["lastMonthPayments"] = func_get_arg(0);
        }
        return $this;
    }

    public function lastMonthBalance()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["lastMonthBalance"])) {
                return $this->instance["lastMonthBalance"];
            } else if (isset($this->columns["lastMonthBalance"]["default"])) {
                return $this->columns["lastMonthBalance"]["default"];
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
                'left' => 'lastMonthBalance',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["lastMonthBalance"]) || $this->instance["lastMonthBalance"] != func_get_args(0)) {
                if (!isset($this->columns["lastMonthBalance"]["ignore_updates"]) || $this->columns["lastMonthBalance"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["lastMonthBalance"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

