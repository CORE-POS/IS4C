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
  @class ProductSummaryLastQuarterModel
*/
class ProductSummaryLastQuarterModel extends BasicModel
{

    protected $name = "productSummaryLastQuarter";
    protected $preferred_db = 'arch';

    protected $columns = array(
    'productSummaryLastQuarterID' => array('type'=>'INT', 'increment'=>true, 'index'=>true),
    'upc' => array('type'=>'INT', 'primary_key'=>true),
    'qtyThisWeek' => array('type'=>'DECIMAL(10,2)'),
    'totalThisWeek' => array('type'=>'MONEY'),
    'qtyLastQuarter' => array('type'=>'DECIMAL(10,2)'),
    'totalLastQuarter' => array('type'=>'MONEY'),
    'percentageStoreSales' => array('type'=>'DECIMAL(10,5)'),
    'percentageSuperDeptSales' => array('type'=>'DECIMAL(10,5)'),
    'percentageDeptSales' => array('type'=>'DECIMAL(10,5)'),
    );

    public function doc()
    {
        return '
Table: productSummaryLastQuarter

Columns:
    productSummaryLastQuarterID int
    upc varchar
    qtyThisWeek
    totalThisWeek
    qtyLastQuarter
    totalLastQuarter
    percentageStoreSales
    percentageSuperDeptSales
    percentageDeptSales

Depends on:
    productWeeklyLastQuarter
    weeksLastQuarter

Use:
Provides per-item sales for the previous quarter.
See weeksLastQuarter for more information about
how the quarter is defined.

Quantity columns are number of items sold; total
columns are in monetary value. Percentages are
calculated in terms of monetary value.

Percentages in this table represent a weighted
average of sales - i.e., sales last week count more
heavily than sales ten weeks ago. The primary purpose
of this table and everything that feeds into it is
to forecast margin. The percentage captures how an
individual item contributes to margin, and weighting
over a longer period should capture long-term trends
while smoothing over random fluctuations.
        ';
    }

    /* START ACCESSOR FUNCTIONS */

    public function productSummaryLastQuarterID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["productSummaryLastQuarterID"])) {
                return $this->instance["productSummaryLastQuarterID"];
            } else if (isset($this->columns["productSummaryLastQuarterID"]["default"])) {
                return $this->columns["productSummaryLastQuarterID"]["default"];
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
                'left' => 'productSummaryLastQuarterID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["productSummaryLastQuarterID"]) || $this->instance["productSummaryLastQuarterID"] != func_get_args(0)) {
                if (!isset($this->columns["productSummaryLastQuarterID"]["ignore_updates"]) || $this->columns["productSummaryLastQuarterID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["productSummaryLastQuarterID"] = func_get_arg(0);
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

    public function qtyThisWeek()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["qtyThisWeek"])) {
                return $this->instance["qtyThisWeek"];
            } else if (isset($this->columns["qtyThisWeek"]["default"])) {
                return $this->columns["qtyThisWeek"]["default"];
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
                'left' => 'qtyThisWeek',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["qtyThisWeek"]) || $this->instance["qtyThisWeek"] != func_get_args(0)) {
                if (!isset($this->columns["qtyThisWeek"]["ignore_updates"]) || $this->columns["qtyThisWeek"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["qtyThisWeek"] = func_get_arg(0);
        }
        return $this;
    }

    public function totalThisWeek()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["totalThisWeek"])) {
                return $this->instance["totalThisWeek"];
            } else if (isset($this->columns["totalThisWeek"]["default"])) {
                return $this->columns["totalThisWeek"]["default"];
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
                'left' => 'totalThisWeek',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["totalThisWeek"]) || $this->instance["totalThisWeek"] != func_get_args(0)) {
                if (!isset($this->columns["totalThisWeek"]["ignore_updates"]) || $this->columns["totalThisWeek"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["totalThisWeek"] = func_get_arg(0);
        }
        return $this;
    }

    public function qtyLastQuarter()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["qtyLastQuarter"])) {
                return $this->instance["qtyLastQuarter"];
            } else if (isset($this->columns["qtyLastQuarter"]["default"])) {
                return $this->columns["qtyLastQuarter"]["default"];
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
                'left' => 'qtyLastQuarter',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["qtyLastQuarter"]) || $this->instance["qtyLastQuarter"] != func_get_args(0)) {
                if (!isset($this->columns["qtyLastQuarter"]["ignore_updates"]) || $this->columns["qtyLastQuarter"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["qtyLastQuarter"] = func_get_arg(0);
        }
        return $this;
    }

    public function totalLastQuarter()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["totalLastQuarter"])) {
                return $this->instance["totalLastQuarter"];
            } else if (isset($this->columns["totalLastQuarter"]["default"])) {
                return $this->columns["totalLastQuarter"]["default"];
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
                'left' => 'totalLastQuarter',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["totalLastQuarter"]) || $this->instance["totalLastQuarter"] != func_get_args(0)) {
                if (!isset($this->columns["totalLastQuarter"]["ignore_updates"]) || $this->columns["totalLastQuarter"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["totalLastQuarter"] = func_get_arg(0);
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

