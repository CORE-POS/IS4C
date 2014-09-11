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
  @class ObfSalesCacheModel
*/
class ObfSalesCacheModel extends BasicModel
{

    protected $name = "ObfSalesCache";

    protected $columns = array(
    'obfSalesCacheID' => array('type'=>'INT', 'primary_key'=>true, 'increment'=>true),
    'obfWeekID' => array('type'=>'INT', 'index'=>true),
    'obfCategoryID' => array('type'=>'INT', 'index'=>true),
    'superID' => array('type'=>'INT', 'index', true),
    'actualSales' => array('type'=>'MONEY'),
    'lastYearSales' => array('type'=>'MONEY'),
    'transactions' => array('type'=>'INT'),
    'lastYearTransactions' => array('type'=>'INT'),
    'growthTarget' => array('type'=>'DOUBLE'),
    );

    protected $unique = array('obfWeekID', 'obfCategoryID', 'superID');

    /* START ACCESSOR FUNCTIONS */

    public function obfSalesCacheID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["obfSalesCacheID"])) {
                return $this->instance["obfSalesCacheID"];
            } else if (isset($this->columns["obfSalesCacheID"]["default"])) {
                return $this->columns["obfSalesCacheID"]["default"];
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
                'left' => 'obfSalesCacheID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["obfSalesCacheID"]) || $this->instance["obfSalesCacheID"] != func_get_args(0)) {
                if (!isset($this->columns["obfSalesCacheID"]["ignore_updates"]) || $this->columns["obfSalesCacheID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["obfSalesCacheID"] = func_get_arg(0);
        }
        return $this;
    }

    public function obfWeekID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["obfWeekID"])) {
                return $this->instance["obfWeekID"];
            } else if (isset($this->columns["obfWeekID"]["default"])) {
                return $this->columns["obfWeekID"]["default"];
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
                'left' => 'obfWeekID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["obfWeekID"]) || $this->instance["obfWeekID"] != func_get_args(0)) {
                if (!isset($this->columns["obfWeekID"]["ignore_updates"]) || $this->columns["obfWeekID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["obfWeekID"] = func_get_arg(0);
        }
        return $this;
    }

    public function obfCategoryID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["obfCategoryID"])) {
                return $this->instance["obfCategoryID"];
            } else if (isset($this->columns["obfCategoryID"]["default"])) {
                return $this->columns["obfCategoryID"]["default"];
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
                'left' => 'obfCategoryID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["obfCategoryID"]) || $this->instance["obfCategoryID"] != func_get_args(0)) {
                if (!isset($this->columns["obfCategoryID"]["ignore_updates"]) || $this->columns["obfCategoryID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["obfCategoryID"] = func_get_arg(0);
        }
        return $this;
    }

    public function superID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["superID"])) {
                return $this->instance["superID"];
            } else if (isset($this->columns["superID"]["default"])) {
                return $this->columns["superID"]["default"];
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
                'left' => 'superID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["superID"]) || $this->instance["superID"] != func_get_args(0)) {
                if (!isset($this->columns["superID"]["ignore_updates"]) || $this->columns["superID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["superID"] = func_get_arg(0);
        }
        return $this;
    }

    public function actualSales()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["actualSales"])) {
                return $this->instance["actualSales"];
            } else if (isset($this->columns["actualSales"]["default"])) {
                return $this->columns["actualSales"]["default"];
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
                'left' => 'actualSales',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["actualSales"]) || $this->instance["actualSales"] != func_get_args(0)) {
                if (!isset($this->columns["actualSales"]["ignore_updates"]) || $this->columns["actualSales"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["actualSales"] = func_get_arg(0);
        }
        return $this;
    }

    public function lastYearSales()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["lastYearSales"])) {
                return $this->instance["lastYearSales"];
            } else if (isset($this->columns["lastYearSales"]["default"])) {
                return $this->columns["lastYearSales"]["default"];
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
                'left' => 'lastYearSales',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["lastYearSales"]) || $this->instance["lastYearSales"] != func_get_args(0)) {
                if (!isset($this->columns["lastYearSales"]["ignore_updates"]) || $this->columns["lastYearSales"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["lastYearSales"] = func_get_arg(0);
        }
        return $this;
    }

    public function transactions()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["transactions"])) {
                return $this->instance["transactions"];
            } else if (isset($this->columns["transactions"]["default"])) {
                return $this->columns["transactions"]["default"];
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
                'left' => 'transactions',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["transactions"]) || $this->instance["transactions"] != func_get_args(0)) {
                if (!isset($this->columns["transactions"]["ignore_updates"]) || $this->columns["transactions"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["transactions"] = func_get_arg(0);
        }
        return $this;
    }

    public function lastYearTransactions()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["lastYearTransactions"])) {
                return $this->instance["lastYearTransactions"];
            } else if (isset($this->columns["lastYearTransactions"]["default"])) {
                return $this->columns["lastYearTransactions"]["default"];
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
                'left' => 'lastYearTransactions',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["lastYearTransactions"]) || $this->instance["lastYearTransactions"] != func_get_args(0)) {
                if (!isset($this->columns["lastYearTransactions"]["ignore_updates"]) || $this->columns["lastYearTransactions"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["lastYearTransactions"] = func_get_arg(0);
        }
        return $this;
    }

    public function growthTarget()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["growthTarget"])) {
                return $this->instance["growthTarget"];
            } else if (isset($this->columns["growthTarget"]["default"])) {
                return $this->columns["growthTarget"]["default"];
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
                'left' => 'growthTarget',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["growthTarget"]) || $this->instance["growthTarget"] != func_get_args(0)) {
                if (!isset($this->columns["growthTarget"]["ignore_updates"]) || $this->columns["growthTarget"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["growthTarget"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

