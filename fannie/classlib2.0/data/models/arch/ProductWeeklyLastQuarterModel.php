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
  @class ProductWeeklyLastQuarterModel
*/
class ProductWeeklyLastQuarterModel extends BasicModel
{

    protected $name = "productWeeklyLastQuarter";
    protected $preferred_db = 'arch';

    protected $columns = array(
    'productWeeklyLastQuarterID' => array('type'=>'INT', 'increment'=>true, 'index'=>true),
    'upc' => array('type'=>'VARCHAR(13)', 'primary_key'=>true),
    'weekLastQuarterID' => array('type'=>'INT', 'primary_key'=>true),
    'quantity' => array('type'=>'DECIMAL(10,2)'),
    'total' => array('type'=>'MONEY'),
    'percentageStoreSales' => array('type'=>'DECIMAL(10,5)'),
    'percentageSuperDeptSales' => array('type'=>'DECIMAL(10,5)'),
    'percentageDeptSales' => array('type'=>'DECIMAL(10,5)'),
    );

    public function doc()
    {
        return '
Table: productWeeklyLastQuarter

Columns:
    productWeeklyLastQuarterID int
    upc varchar
    quantity double
    total double
    percentageStoreSales
    percentageSuperDeptSales
    percentageDeptSales

Depends on:
    none

Use:
Per-item sales numbers for a given week. As always,
quantity is the number of items sold and total is
the monetary value. Percentages are calculated in
terms of monetary value.

This is essentially an intermediate calculation
for building productSummaryLastQuarter. The results
are saved here on the off-chance they prove useful
for something else.
        ';
    }

    /* START ACCESSOR FUNCTIONS */

    public function productWeeklyLastQuarterID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["productWeeklyLastQuarterID"])) {
                return $this->instance["productWeeklyLastQuarterID"];
            } else if (isset($this->columns["productWeeklyLastQuarterID"]["default"])) {
                return $this->columns["productWeeklyLastQuarterID"]["default"];
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
                'left' => 'productWeeklyLastQuarterID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["productWeeklyLastQuarterID"]) || $this->instance["productWeeklyLastQuarterID"] != func_get_args(0)) {
                if (!isset($this->columns["productWeeklyLastQuarterID"]["ignore_updates"]) || $this->columns["productWeeklyLastQuarterID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["productWeeklyLastQuarterID"] = func_get_arg(0);
        }
        return $this;
    }

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

    public function weekLastQuarterID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["weekLastQuarterID"])) {
                return $this->instance["weekLastQuarterID"];
            } else if (isset($this->columns["weekLastQuarterID"]["default"])) {
                return $this->columns["weekLastQuarterID"]["default"];
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
                'left' => 'weekLastQuarterID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["weekLastQuarterID"]) || $this->instance["weekLastQuarterID"] != func_get_args(0)) {
                if (!isset($this->columns["weekLastQuarterID"]["ignore_updates"]) || $this->columns["weekLastQuarterID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["weekLastQuarterID"] = func_get_arg(0);
        }
        return $this;
    }

    public function quantity()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["quantity"])) {
                return $this->instance["quantity"];
            } else if (isset($this->columns["quantity"]["default"])) {
                return $this->columns["quantity"]["default"];
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
                'left' => 'quantity',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["quantity"]) || $this->instance["quantity"] != func_get_args(0)) {
                if (!isset($this->columns["quantity"]["ignore_updates"]) || $this->columns["quantity"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["quantity"] = func_get_arg(0);
        }
        return $this;
    }

    public function total()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["total"])) {
                return $this->instance["total"];
            } else if (isset($this->columns["total"]["default"])) {
                return $this->columns["total"]["default"];
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
                'left' => 'total',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["total"]) || $this->instance["total"] != func_get_args(0)) {
                if (!isset($this->columns["total"]["ignore_updates"]) || $this->columns["total"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["total"] = func_get_arg(0);
        }
        return $this;
    }

    public function percentageStoreSales()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["percentageStoreSales"])) {
                return $this->instance["percentageStoreSales"];
            } else if (isset($this->columns["percentageStoreSales"]["default"])) {
                return $this->columns["percentageStoreSales"]["default"];
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
                'left' => 'percentageStoreSales',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["percentageStoreSales"]) || $this->instance["percentageStoreSales"] != func_get_args(0)) {
                if (!isset($this->columns["percentageStoreSales"]["ignore_updates"]) || $this->columns["percentageStoreSales"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["percentageStoreSales"] = func_get_arg(0);
        }
        return $this;
    }

    public function percentageSuperDeptSales()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["percentageSuperDeptSales"])) {
                return $this->instance["percentageSuperDeptSales"];
            } else if (isset($this->columns["percentageSuperDeptSales"]["default"])) {
                return $this->columns["percentageSuperDeptSales"]["default"];
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
                'left' => 'percentageSuperDeptSales',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["percentageSuperDeptSales"]) || $this->instance["percentageSuperDeptSales"] != func_get_args(0)) {
                if (!isset($this->columns["percentageSuperDeptSales"]["ignore_updates"]) || $this->columns["percentageSuperDeptSales"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["percentageSuperDeptSales"] = func_get_arg(0);
        }
        return $this;
    }

    public function percentageDeptSales()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["percentageDeptSales"])) {
                return $this->instance["percentageDeptSales"];
            } else if (isset($this->columns["percentageDeptSales"]["default"])) {
                return $this->columns["percentageDeptSales"]["default"];
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
                'left' => 'percentageDeptSales',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["percentageDeptSales"]) || $this->instance["percentageDeptSales"] != func_get_args(0)) {
                if (!isset($this->columns["percentageDeptSales"]["ignore_updates"]) || $this->columns["percentageDeptSales"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["percentageDeptSales"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

