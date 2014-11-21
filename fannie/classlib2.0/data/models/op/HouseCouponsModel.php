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
  @class HouseCouponsModel
*/
class HouseCouponsModel extends BasicModel
{

    protected $name = "houseCoupons";
    protected $preferred_db = 'op';
    protected $normalize_lanes = true;

    protected $columns = array(
    'coupID' => array('type'=>'INT', 'primary_key'=>true),
    'description' => array('type'=>'VARCHAR(30)'),
    'startDate' => array('type'=>'DATETIME'),
    'endDate' => array('type'=>'DATETIME'),
    'limit' => array('type'=>'SMALLINT'),
    'memberOnly' => array('type'=>'SMALLINT'),
    'discountType' => array('type'=>'VARCHAR(2)'),
    'discountValue' => array('type'=>'MONEY'),
    'minType' => array('type'=>'VARCHAR(2)'),
    'minValue' => array('type'=>'MONEY'),
    'department' => array('type'=>'INT'),
    'auto' => array('type'=>'TINYINT', 'default'=>0),
    );

    public function doc()
    {
        return '
Table: houseCoupons

Columns:
    coupID int
    description varchar
    startDate datetime
    endDate datetime
    limit smallint
    memberOnly smallint
    discountType varchar
    discountValue double
    minType varchar
    minValue double
    department int

Depends on:
    houseCouponItems

Use:
WFC runs custom barcoded coupons with
upc prefix 499999, then the coupon ID
(zero padded to 5 digits). There\'s a
tool in legacy for putting these together
that may eventually make it into fannie.

startDate is the first day the coupon is valid

endDate is the last day the coupon is valid

limit is the number of times the coupon can be
used in one transaction

memberOnly means the coupon can only be used
by a member (custdata.Type=\'PC\')

minType and minValue specify how one
qualifies for the coupon - certain item(s),
purchase amount, etc

* \'Q\' must purchase at least minValue
  qualifying items (by quantity)
* \'Q+\' must purchase more than minValue
  qualifying items (by quantity)
* \'D\' must purchase at least minValue
  items from qualifying departments
  (by $ value)
* \'D+\' must purchase more than minValue
  items from qualifying departments
  (by $ value)
* \'M\' is mixed. Must purchase at least
  minValue qualifying items and at least
  one discount item
* \'$\' must puchase at least minValue
  goods (by $ value)
* \'$+\' must puchase more than minValue
  goods (by $ value)
* \'\' blank means no minimum purchase

discountType and discountValue specify
how the discount is calculated

(item related discounts)
* \'Q\' discount equals discountValue times
  unitPrice for the cheapest qualifying 
  item (essentially percent discount)
* \'P\' discount equals unitPrice minus
  discountValue for the cheapest qualifying
  item (essentially an sale price)
* \'FI\' discount equals discountValues times
  quantity for the cheapest qualifying item
  (works with by-weight items)

(department related discounts)
* \'FD\' discount equals discountValue times
  quantity for the cheapest qualifying item
* \'AD\' discount equals discountValue times
  sum(quantity) for ALL qualifying items

(other discounts)
* \'F\' discount equals discountValue
* \'%\' discountValue is a percent discount for
  all discountable items

Qualifying items are stored in houseCouponItems. Not
all coupons require entries here. Records can be
items (by UPC) or departments (by department number).
Some minimum and discount types only work with one
or the other.

houseCouponItems.coupID is the coupon ID

houseCouponItems.upc is an item UPC or a department number.

houseCouponItems.type is only relevant to the mixed (M)
minimum type. Values are:
* \'QUALIFIER\' counts as a qualifying item for mixed
* \'DISCOUNT\' counts as a discount item for mixed
* \'BOTH\' can be a qualfying item or a discount item
If not using the mixed minimum, always choose \'BOTH\'

The nuts and bolts of this are in
the UPC.php parser module (IT CORE).
        ';
    }

    /* START ACCESSOR FUNCTIONS */

    public function coupID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["coupID"])) {
                return $this->instance["coupID"];
            } else if (isset($this->columns["coupID"]["default"])) {
                return $this->columns["coupID"]["default"];
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
                'left' => 'coupID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["coupID"]) || $this->instance["coupID"] != func_get_args(0)) {
                if (!isset($this->columns["coupID"]["ignore_updates"]) || $this->columns["coupID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["coupID"] = func_get_arg(0);
        }
        return $this;
    }

    public function description()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["description"])) {
                return $this->instance["description"];
            } else if (isset($this->columns["description"]["default"])) {
                return $this->columns["description"]["default"];
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
                'left' => 'description',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["description"]) || $this->instance["description"] != func_get_args(0)) {
                if (!isset($this->columns["description"]["ignore_updates"]) || $this->columns["description"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["description"] = func_get_arg(0);
        }
        return $this;
    }

    public function startDate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["startDate"])) {
                return $this->instance["startDate"];
            } else if (isset($this->columns["startDate"]["default"])) {
                return $this->columns["startDate"]["default"];
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
                'left' => 'startDate',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["startDate"]) || $this->instance["startDate"] != func_get_args(0)) {
                if (!isset($this->columns["startDate"]["ignore_updates"]) || $this->columns["startDate"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["startDate"] = func_get_arg(0);
        }
        return $this;
    }

    public function endDate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["endDate"])) {
                return $this->instance["endDate"];
            } else if (isset($this->columns["endDate"]["default"])) {
                return $this->columns["endDate"]["default"];
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
                'left' => 'endDate',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["endDate"]) || $this->instance["endDate"] != func_get_args(0)) {
                if (!isset($this->columns["endDate"]["ignore_updates"]) || $this->columns["endDate"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["endDate"] = func_get_arg(0);
        }
        return $this;
    }

    public function limit()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["limit"])) {
                return $this->instance["limit"];
            } else if (isset($this->columns["limit"]["default"])) {
                return $this->columns["limit"]["default"];
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
                'left' => 'limit',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["limit"]) || $this->instance["limit"] != func_get_args(0)) {
                if (!isset($this->columns["limit"]["ignore_updates"]) || $this->columns["limit"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["limit"] = func_get_arg(0);
        }
        return $this;
    }

    public function memberOnly()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["memberOnly"])) {
                return $this->instance["memberOnly"];
            } else if (isset($this->columns["memberOnly"]["default"])) {
                return $this->columns["memberOnly"]["default"];
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
                'left' => 'memberOnly',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["memberOnly"]) || $this->instance["memberOnly"] != func_get_args(0)) {
                if (!isset($this->columns["memberOnly"]["ignore_updates"]) || $this->columns["memberOnly"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["memberOnly"] = func_get_arg(0);
        }
        return $this;
    }

    public function discountType()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["discountType"])) {
                return $this->instance["discountType"];
            } else if (isset($this->columns["discountType"]["default"])) {
                return $this->columns["discountType"]["default"];
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
                'left' => 'discountType',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["discountType"]) || $this->instance["discountType"] != func_get_args(0)) {
                if (!isset($this->columns["discountType"]["ignore_updates"]) || $this->columns["discountType"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["discountType"] = func_get_arg(0);
        }
        return $this;
    }

    public function discountValue()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["discountValue"])) {
                return $this->instance["discountValue"];
            } else if (isset($this->columns["discountValue"]["default"])) {
                return $this->columns["discountValue"]["default"];
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
                'left' => 'discountValue',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["discountValue"]) || $this->instance["discountValue"] != func_get_args(0)) {
                if (!isset($this->columns["discountValue"]["ignore_updates"]) || $this->columns["discountValue"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["discountValue"] = func_get_arg(0);
        }
        return $this;
    }

    public function minType()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["minType"])) {
                return $this->instance["minType"];
            } else if (isset($this->columns["minType"]["default"])) {
                return $this->columns["minType"]["default"];
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
                'left' => 'minType',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["minType"]) || $this->instance["minType"] != func_get_args(0)) {
                if (!isset($this->columns["minType"]["ignore_updates"]) || $this->columns["minType"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["minType"] = func_get_arg(0);
        }
        return $this;
    }

    public function minValue()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["minValue"])) {
                return $this->instance["minValue"];
            } else if (isset($this->columns["minValue"]["default"])) {
                return $this->columns["minValue"]["default"];
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
                'left' => 'minValue',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["minValue"]) || $this->instance["minValue"] != func_get_args(0)) {
                if (!isset($this->columns["minValue"]["ignore_updates"]) || $this->columns["minValue"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["minValue"] = func_get_arg(0);
        }
        return $this;
    }

    public function department()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["department"])) {
                return $this->instance["department"];
            } else if (isset($this->columns["department"]["default"])) {
                return $this->columns["department"]["default"];
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
                'left' => 'department',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["department"]) || $this->instance["department"] != func_get_args(0)) {
                if (!isset($this->columns["department"]["ignore_updates"]) || $this->columns["department"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["department"] = func_get_arg(0);
        }
        return $this;
    }

    public function auto()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["auto"])) {
                return $this->instance["auto"];
            } else if (isset($this->columns["auto"]["default"])) {
                return $this->columns["auto"]["default"];
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
                'left' => 'auto',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["auto"]) || $this->instance["auto"] != func_get_args(0)) {
                if (!isset($this->columns["auto"]["ignore_updates"]) || $this->columns["auto"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["auto"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

