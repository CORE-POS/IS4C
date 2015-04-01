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
  @class PurchaseOrderSummaryModel
*/
class PurchaseOrderSummaryModel extends BasicModel
{

    protected $name = "PurchaseOrderSummary";
    protected $preferred_db = 'op';

    protected $columns = array(
    'vendorID' => array('type'=>'INT', 'primary_key'=>true),
    'sku' => array('type'=>'VARCHAR(13)', 'primary_key'=>true),
    'totalReceived' => array('type'=>'INT'),
    'casesReceived' => array('type'=>'INT'),
    'numOrders' => array('type'=>'INT'),
    'numCredits' => array('type'=>'INT'),
    'oldest' => array('type'=>'DATETIME'),
    'newest' => array('type'=>'DATETIME'),
    );

    public function doc()
    {
        return '
Table: PurchaseOrderSummary

Columns:
    vendorID INT
    sku VARCHAR
    totalReceived INT
    casesReceived INT
    oldest DATETIME
    newest DATETIME

Depends on:
    PurchaseOrder, PurchaseOrderItems

Use:
Stores total quantities ordered for recent
orders where "recent" covers the same
timeframe as transarchive. Calculating this
on the fly can be prohibitively slow.

totalReceived is in individual units for comparison
against sales records. casesReceived is in cases.

numOrders indicates how many times the item has
been ordered. Credits are counted separately as
numCredits. oldest and newest are bounds on when
the item has been ordered.
        ';
    }

    /* START ACCESSOR FUNCTIONS */

    public function vendorID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["vendorID"])) {
                return $this->instance["vendorID"];
            } else if (isset($this->columns["vendorID"]["default"])) {
                return $this->columns["vendorID"]["default"];
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
                'left' => 'vendorID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["vendorID"]) || $this->instance["vendorID"] != func_get_args(0)) {
                if (!isset($this->columns["vendorID"]["ignore_updates"]) || $this->columns["vendorID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["vendorID"] = func_get_arg(0);
        }
        return $this;
    }

    public function sku()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["sku"])) {
                return $this->instance["sku"];
            } else if (isset($this->columns["sku"]["default"])) {
                return $this->columns["sku"]["default"];
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
                'left' => 'sku',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["sku"]) || $this->instance["sku"] != func_get_args(0)) {
                if (!isset($this->columns["sku"]["ignore_updates"]) || $this->columns["sku"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["sku"] = func_get_arg(0);
        }
        return $this;
    }

    public function totalReceived()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["totalReceived"])) {
                return $this->instance["totalReceived"];
            } else if (isset($this->columns["totalReceived"]["default"])) {
                return $this->columns["totalReceived"]["default"];
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
                'left' => 'totalReceived',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["totalReceived"]) || $this->instance["totalReceived"] != func_get_args(0)) {
                if (!isset($this->columns["totalReceived"]["ignore_updates"]) || $this->columns["totalReceived"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["totalReceived"] = func_get_arg(0);
        }
        return $this;
    }

    public function casesReceived()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["casesReceived"])) {
                return $this->instance["casesReceived"];
            } else if (isset($this->columns["casesReceived"]["default"])) {
                return $this->columns["casesReceived"]["default"];
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
                'left' => 'casesReceived',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["casesReceived"]) || $this->instance["casesReceived"] != func_get_args(0)) {
                if (!isset($this->columns["casesReceived"]["ignore_updates"]) || $this->columns["casesReceived"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["casesReceived"] = func_get_arg(0);
        }
        return $this;
    }

    public function numOrders()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["numOrders"])) {
                return $this->instance["numOrders"];
            } else if (isset($this->columns["numOrders"]["default"])) {
                return $this->columns["numOrders"]["default"];
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
                'left' => 'numOrders',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["numOrders"]) || $this->instance["numOrders"] != func_get_args(0)) {
                if (!isset($this->columns["numOrders"]["ignore_updates"]) || $this->columns["numOrders"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["numOrders"] = func_get_arg(0);
        }
        return $this;
    }

    public function numCredits()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["numCredits"])) {
                return $this->instance["numCredits"];
            } else if (isset($this->columns["numCredits"]["default"])) {
                return $this->columns["numCredits"]["default"];
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
                'left' => 'numCredits',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["numCredits"]) || $this->instance["numCredits"] != func_get_args(0)) {
                if (!isset($this->columns["numCredits"]["ignore_updates"]) || $this->columns["numCredits"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["numCredits"] = func_get_arg(0);
        }
        return $this;
    }

    public function oldest()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["oldest"])) {
                return $this->instance["oldest"];
            } else if (isset($this->columns["oldest"]["default"])) {
                return $this->columns["oldest"]["default"];
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
                'left' => 'oldest',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["oldest"]) || $this->instance["oldest"] != func_get_args(0)) {
                if (!isset($this->columns["oldest"]["ignore_updates"]) || $this->columns["oldest"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["oldest"] = func_get_arg(0);
        }
        return $this;
    }

    public function newest()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["newest"])) {
                return $this->instance["newest"];
            } else if (isset($this->columns["newest"]["default"])) {
                return $this->columns["newest"]["default"];
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
                'left' => 'newest',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["newest"]) || $this->instance["newest"] != func_get_args(0)) {
                if (!isset($this->columns["newest"]["ignore_updates"]) || $this->columns["newest"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["newest"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

